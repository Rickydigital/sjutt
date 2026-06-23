# Vote Security Model

This document describes how SJUT election votes are protected against fraud, double-voting, tampering, and data leakage.

---

## 1. Double-Vote Prevention (Database Layer)

**Mechanism:** Unique composite index on `election_votes`.

```sql
UNIQUE KEY unique_vote_per_position (election_id, election_position_id, student_id)
```

Even if the application layer is bypassed (e.g., a replay attack via curl), the database will reject a second vote for the same student, election, and position with a `SQLSTATE[23000]` integrity constraint violation. The API catches this and returns a structured `409 Conflict` response.

**Application layer:** The `store()` method in `ElectionVotingController` wraps each vote in a DB transaction with `lockForUpdate()` on the student's existing votes row, preventing race conditions under concurrent requests:

```php
DB::transaction(function () use ($request, $student, $election) {
    $existing = ElectionVote::where([...])
        ->lockForUpdate()
        ->first();

    abort_if($existing, 409, 'You have already voted for this position.');

    ElectionVote::create([...]);
});
```

---

## 2. Voter Eligibility Scoping

Before a vote is accepted, the backend verifies:

- Student is **Active** (`students.status = 'Active'`).
- The position's `scope_type` matches the student's faculty/program:
  - `global` → all active students may vote.
  - `faculty` → only students in the position's faculty.
  - `program` → only students in the position's program.

This is enforced in `ElectionVotingController::index()` (ballot generation) and again in `store()` (vote acceptance), so eligibility is checked at both display and submission time.

---

## 3. Results Snapshot Immutability

When an officer publishes results, the system **snapshots** all vote tallies into a separate set of read-only tables:

```
election_result_publishes
  └── election_result_scopes        (one row per scope: global / faculty / program)
        └── election_result_positions   (one row per position within that scope)
              └── election_result_candidates  (one row per candidate, with vote_count)
```

This snapshot is taken with a single DB transaction. Once created, **no update path exists** for these rows — they are insert-only. Future re-publishes create a new version (`version` column increments) and do not overwrite old ones.

**Why this matters:** Even if someone modifies raw votes in `election_votes` after publication, the published snapshot reflects what was counted at publish time. Auditors can compare the snapshot against live vote data.

---

## 4. SHA-256 Checksum (Integrity Seal)

At publish time, after all snapshot rows are inserted, the server computes a SHA-256 hash of the complete results payload:

```php
$snapshot = ElectionResultScope::where('result_publish_id', $publish->id)
    ->with(['positions.candidates'])
    ->orderBy('scope_type')
    ->get()
    ->toArray();

$checksum = hash('sha256', json_encode($snapshot, JSON_UNESCAPED_UNICODE));
$publish->update(['checksum' => $checksum]);
```

The checksum is stored in `election_result_publishes.checksum` (64-char hex string).

**Verification:** Any time a user or auditor wants to verify that published results have not been tampered with, they can recompute the hash from the same snapshot rows and compare it to the stored checksum. A mismatch means the snapshot data was altered after publication.

---

## 5. FCM Notification on Publish

When results are published, **all active students with a registered FCM token** receive a push notification via Firebase Cloud Messaging. Notifications are dispatched in batches of 500 tokens using the `SendNewsNotificationBatch` job (queued, 3 retries).

This serves two security purposes:
1. **Transparency** — students are immediately informed that results exist and can verify outcomes.
2. **Fraud detection** — a student who voted but sees a different winner has a narrow time window to raise a concern before the announcement fades from memory.

Invalid FCM tokens discovered during sending are automatically cleaned from the database.

---

## 6. HMAC Signature on Every Vote

**Mechanism:** When a vote is cast through the API, the server computes:

```php
hash_hmac('sha256', "{$electionId}|{$positionId}|{$candidateId}|{$studentId}", config('vote.hmac_secret'))
```

and stores the result in `election_votes.vote_hmac`. The secret lives only in `.env` as `VOTE_HMAC_SECRET` — it is never in the database.

**At tally time (publish):** The three counting methods (`votesPerCandidateForPositionScope`, `countDistinctVotersForPositionScope`, `countDistinctVotersForScope`) load raw vote rows into PHP and call `hash_equals($expected, $vote->vote_hmac)` on each one before counting it. Rows that fail verification are skipped and logged as anomalies.

**What this prevents:** Someone with direct DB write access inserting fake `election_votes` rows cannot know `VOTE_HMAC_SECRET`, so they cannot produce a valid HMAC. Their row is silently excluded from every tally and flagged in `laravel.log`.

**All existing votes were backfilled** at migration time using the same algorithm, so pre-feature votes remain valid.

**Trade-off:** Tally computation moves from SQL `GROUP BY` to a PHP loop. For election-scale vote counts this is negligible in time.

---

## 7. Authentication Boundary

All voting endpoints are behind `mobile-auth` middleware which:
1. Validates the Sanctum bearer token.
2. Binds the authenticated `Student` model to `$request->user()`.
3. Returns `401 Unauthorized` (JSON) for any unauthenticated request.

The `ForceJsonResponse` middleware ensures the API always returns `application/json`, even for framework-level errors — no HTML leak-through.

---

## Summary Table

| Control | Where enforced | Covers |
|---|---|---|
| Unique vote constraint | Database index | Double-vote at DB level |
| `lockForUpdate` transaction | `ElectionVotingController::store()` | Race conditions |
| Eligibility check | Controller (display + submit) | Scope bypass |
| Snapshot tables | `OfficerPublishResultsController` | Results tampering post-publish |
| SHA-256 checksum | `election_result_publishes.checksum` | Snapshot integrity verification |
| FCM notification | `SendNewsNotificationBatch` job | Transparency + fraud awareness |
| Bearer token auth | `MobileAuthMiddleware` | Unauthenticated access |
| HMAC signature on votes | `election_votes.vote_hmac` | Fake DB-inserted votes never counted |
