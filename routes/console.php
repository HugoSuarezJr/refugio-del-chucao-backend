<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mail {email}', function (string $email) {
    Mail::raw('SMTP test from Refugio del Chucao.', function ($message) use ($email) {
        $message->to($email)
            ->subject('Refugio del Chucao mail test');
    });

    $this->info("Test email sent to {$email}");
})->purpose('Send a temporary test email');
