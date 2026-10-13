<?php

return [
    'admin_emails' => array_filter(
        array_map('trim', explode(',', env('ADMIN_EMAILS', ''))),
    ),
    'localhost_free' => env('LOCALHOST_FREE_PLAN', true),
    'openai_key' => env('OPENAI_API_KEY', ''),
    'openai_model' => env('OPENAI_MODEL', ''),
    'paypal_secret' => env('PAYPAL_CLIENT_SECRET', ''),
];
