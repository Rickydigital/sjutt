<?php

return [
    /*
     * HMAC secret used to sign every cast vote.
     * A vote row with a missing or mismatched hmac is skipped at tally time.
     * Rotate this value only between elections — never mid-election.
     */
    'hmac_secret' => env('VOTE_HMAC_SECRET', ''),
];
