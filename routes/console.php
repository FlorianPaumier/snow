<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Client-facing reminders run on business days only and are capped by the
// action's anti-spam policy (week window, weekly cooldown, 3 per
// installment). The digest is the organization's own weekly recap.
Schedule::command('payments:send-reminders')
    ->weekdays()
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('payments:send-digest')
    ->weeklyOn(1, '08:00')
    ->withoutOverlapping()
    ->onOneServer();

// Personal-data retention (Quebec Law 25 s. 22): clients whose last
// season ended more than config('privacy.client_retention_years') ago
// are anonymized; contractless ones are erased entirely.
Schedule::command('clients:anonymize-expired')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('contact-inquiries:purge-expired')
    ->dailyAt('03:30')
    ->withoutOverlapping()
    ->onOneServer();
