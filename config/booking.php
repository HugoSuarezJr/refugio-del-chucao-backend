<?php

return [
    'currency' => env('BOOKING_CURRENCY', 'CLP'),
    'check_in_time' => env('BOOKING_CHECK_IN_TIME', '16:00'),
    'check_out_time' => env('BOOKING_CHECK_OUT_TIME', '12:00'),
    'default_reservation_source' => env('BOOKING_DEFAULT_SOURCE', 'website'),
    'pending_payment_hold_minutes' => (int) env('BOOKING_PENDING_PAYMENT_HOLD_MINUTES', 30),
    'admin_notification_email' => env('BOOKING_ADMIN_NOTIFICATION_EMAIL'),
];
