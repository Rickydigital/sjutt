# SJUT Voting API Documentation

> **Status:** Core voting endpoints are live. Results browsing endpoints still need to be built.
>
> | Endpoint | Status |
> |----------|--------|
> | `GET /api/elections` | ✅ Live |
> | `GET /api/elections/voting` | ✅ Live |
> | `POST /api/elections/vote` | ✅ Live |
> | `GET /api/elections/my-votes` | ✅ Live |
> | `GET /api/elections/{id}/results` | ✅ Live |

---

## Base URL

```
http://<server>/api
```

---

## Authentication

All voting endpoints require a **Sanctum bearer token** obtained from the student login endpoint.

```
POST /api/login
Content-Type: application/json

{
  "reg_no": "STU/2022/001",
  "password": "secret"
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "token": "1|abc123...",
    "student": {
      "id": 1,
      "reg_no": "STU/2022/001",
      "first_name": "John",
      "last_name": "Doe",
      "faculty_id": 2,
      "program_id": 5,
      "status": "Active"
    },
    "profile_complete": true,
    "requires_update": false
  }
}
```

Include the token in all subsequent requests:
```
Authorization: Bearer 1|abc123...
```

---

## Data Models

### Election
| Field        | Type     | Description                                      |
|--------------|----------|--------------------------------------------------|
| `id`         | integer  | Primary key                                      |
| `title`      | string   | Election name                                    |
| `start_date` | date     | First day of voting window                       |
| `end_date`   | date     | Last day of voting window                        |
| `open_time`  | string   | Daily opening time (e.g. `"08:00:00"`)           |
| `close_time` | string   | Daily closing time (e.g. `"17:00:00"`)           |
| `status`     | string   | `draft` / `open` / `closed` / `published`        |
| `is_active`  | boolean  | Legacy flag                                      |

### ElectionPosition
| Field                    | Type    | Description                                      |
|--------------------------|---------|--------------------------------------------------|
| `id`                     | integer | Primary key                                      |
| `election_id`            | integer | Parent election                                  |
| `position_definition_id` | integer | Links to the position name/description           |
| `scope_type`             | string  | `global` / `faculty` / `program`                 |
| `is_enabled`             | boolean | Whether students can vote for this position      |

**Scope rules:**
- `global` — every active student is eligible
- `faculty` — only students whose `faculty_id` is attached to the position
- `program` — only students whose `program_id` is attached to the position

### ElectionCandidate
| Field                  | Type    | Description                              |
|------------------------|---------|------------------------------------------|
| `id`                   | integer | Primary key                              |
| `election_position_id` | integer | Which position this candidate is running for |
| `student_id`           | integer | The candidate student                    |
| `faculty_id`           | integer | Candidate's faculty (for faculty scope)  |
| `program_id`           | integer | Candidate's program (for program scope)  |
| `photo`                | string  | Profile photo path                       |
| `description`          | string  | Manifesto / short bio                    |
| `is_approved`          | boolean | Admin must approve before student can vote for them |

A candidate may have a `vice` (running mate) via `ElectionViceCandidate`.

### ElectionVote
| Field                  | Type    | Description                              |
|------------------------|---------|------------------------------------------|
| `election_id`          | integer |                                          |
| `election_position_id` | integer |                                          |
| `candidate_id`         | integer |                                          |
| `student_id`           | integer | The voter                                |

**One vote per student per position** — the system enforces this.

---

## Endpoints

---

### 1. Get Open Elections (Ballot for the Logged-In Student) ✅ LIVE

```
GET /api/elections/voting
Authorization: Bearer <token>
```

Returns all elections with `status = open`, filtered to only the positions the
authenticated student is **eligible** to vote on and **has not yet voted** for.

Each position includes its approved candidates (filtered to match the student's
faculty/program for scoped positions).

**Response shape:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 3,
      "title": "SRC Elections 2026",
      "start_date": "2026-06-20",
      "end_date": "2026-06-25",
      "open_time": "08:00:00",
      "close_time": "17:00:00",
      "status": "open",
      "positions": [
        {
          "id": 12,
          "scope_type": "global",
          "definition": {
            "name": "President",
            "description": "Student Representative Council President"
          },
          "candidates": [
            {
              "id": 45,
              "student": {
                "id": 101,
                "first_name": "Alice",
                "last_name": "Mensah",
                "faculty": { "id": 2, "name": "Engineering" },
                "program": { "id": 5, "name": "Computer Science" }
              },
              "photo": "candidates/alice.jpg",
              "description": "I will fight for student rights...",
              "is_approved": true,
              "vice": {
                "id": 8,
                "student": {
                  "first_name": "Bob",
                  "last_name": "Asante"
                }
              }
            }
          ]
        }
      ]
    }
  ]
}
```

**Business rules applied server-side:**
- Only `open` elections returned
- Positions the student already voted on are excluded
- Candidates filtered by scope (global = all; faculty/program = matching only)
- Only `is_approved = true` candidates shown
- Only candidates whose student `status = Active` shown

---

### 2. Cast a Vote ✅ LIVE

```
POST /api/elections/vote
Authorization: Bearer <token>
Content-Type: application/json

{
  "election_position_id": 12,
  "candidate_id": 45
}
```

**Response (success):**
```json
{
  "status": "success",
  "message": "Vote submitted successfully."
}
```

**Error responses:**

| HTTP | Condition                                             |
|------|-------------------------------------------------------|
| 403  | Election is not `open`                                |
| 403  | Student not eligible for this position's scope        |
| 403  | Candidate not in the student's faculty/program scope  |
| 403  | Candidate is not approved                             |
| 422  | Student already voted for this position               |
| 404  | Position or candidate not found / not enabled         |

**Business rules enforced:**
- Position must be `is_enabled = true`
- Election `status` must be `open`
- Student eligibility checked against scope type
- Candidate must belong to the position
- One vote per student per position (idempotency guard)

---

### 3. Get Voted Positions (My Votes) ✅ LIVE

```
GET /api/elections/my-votes
Authorization: Bearer <token>
```

Returns which `election_position_id`s the student has already voted on.
Useful for the Flutter app to mark positions as "voted" without re-fetching the full ballot.

**Response:**
```json
{
  "status": "success",
  "data": {
    "voted_position_ids": [12, 13]
  }
}
```

---

### 4. Get Published Results ✅ LIVE

```
GET /api/elections/{election_id}/results
Authorization: Bearer <token>
```

Only works when the election `status = published`. Returns the latest published
results snapshot, including winners and vote counts per position.

**Response shape:**
```json
{
  "status": "success",
  "data": {
    "election": {
      "id": 3,
      "title": "SRC Elections 2026",
      "status": "published"
    },
    "published_at": "2026-06-26T10:30:00Z",
    "version": 1,
    "positions": [
      {
        "position_name": "President",
        "scope_type": "global",
        "eligible_students": 1200,
        "voters": 890,
        "turnout_percent": 74.17,
        "candidates": [
          {
            "candidate_name": "Alice Mensah",
            "candidate_reg_no": "STU/2022/001",
            "vote_count": 540,
            "vote_percent": 45.0,
            "rank": 1,
            "is_winner": true
          },
          {
            "candidate_name": "Bob Asante",
            "candidate_reg_no": "STU/2021/088",
            "vote_count": 350,
            "vote_percent": 29.17,
            "rank": 2,
            "is_winner": false
          }
        ]
      }
    ]
  }
}
```

---

### 5. List All Elections (Browse) ✅ LIVE

```
GET /api/elections
Authorization: Bearer <token>
```

Returns all elections the student can see — both `open` and `published` ones.
Useful for a browse/history screen.

**Query params:**
| Param    | Type   | Description                          |
|----------|--------|--------------------------------------|
| `status` | string | Filter: `open`, `published`, `closed`|

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 3,
      "title": "SRC Elections 2026",
      "start_date": "2026-06-20",
      "end_date": "2026-06-25",
      "open_time": "08:00:00",
      "close_time": "17:00:00",
      "status": "published"
    }
  ]
}
```

---

## Notes for Flutter Integration

1. **Token storage** — store the Sanctum token in `flutter_secure_storage` after login.
2. **Voting is one-shot per position** — once submitted, the position disappears from the ballot. Call `GET /api/elections/my-votes` on app resume to stay in sync.
3. **Voting window** — the server checks `open_time` / `close_time` daily. If the app needs to show a countdown, use `open_time` + `close_time` from the election object (times are in `HH:MM:SS` 24hr format).
4. **Scope filtering is server-side** — the Flutter app does NOT need to implement scope logic. It just renders what the server returns.
5. **Results are only available when `status = published`** — poll or check this field before navigating to a results screen.
6. **Vice candidates** — some positions have a running mate (`vice` field on the candidate). Display them together as a ticket.
7. **Photo URLs** — candidate photos are relative paths. Prepend the base server URL to construct full image URLs.

---

## Implementation

All five endpoints live in `app/Http/Controllers/Mobile/ElectionVotingController.php`
and are registered in `routes/api.php` under the `mobile-auth` middleware group.
