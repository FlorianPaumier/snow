<?php

declare(strict_types=1);

return [
    'subject' => ':organization — Contract :season',
    'intro' => 'Hello :client, please find your contract for :season attached.',
    'attachment' => 'Your contract is attached as a PDF.',
    'no_email' => ':client has no valid email address or has been anonymized. No contracts were queued.',
    'queued' => ':count contract(s) queued for email.',
    'already_queued' => 'These contracts are already queued for email.',
];
