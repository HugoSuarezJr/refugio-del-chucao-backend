<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-email', function () {

    Mail::raw('Test email from Refugio del Chucao', function ($message) {

        $message->to('husuarezjr@gmail.com')

            ->subject('Test Email');

    });

    return 'Email sent!';

});
