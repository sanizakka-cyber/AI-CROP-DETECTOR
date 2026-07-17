<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paystack' => [
        'public_key'  => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key'  => env('PAYSTACK_SECRET_KEY'),
        'payment_url' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
    ],

    'flutterwave' => [
        'public_key'      => env('FLUTTERWAVE_PUBLIC_KEY'),
        'secret_key'      => env('FLUTTERWAVE_SECRET_KEY'),
        'encryption_key'  => env('FLUTTERWAVE_ENCRYPTION_KEY'),
    ],

    'ai_engine' => [
        'url' => env('AI_ENGINE_URL', 'http://127.0.0.1:8001'),
        'key' => env('AI_ENGINE_KEY'),
    ],

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'), // log | termii | africas_talking | twilio
        'termii' => [
            'api_key' => env('TERMII_API_KEY'),
            'from'    => env('TERMII_SENDER_ID', 'MSAS'),
            'channel' => env('TERMII_CHANNEL', 'generic'),
        ],
        'africas_talking' => [
            'api_key'  => env('AT_API_KEY'),
            'username' => env('AT_USERNAME'),
            'from'     => env('AT_SENDER_ID', 'MSAS'),
        ],
        'twilio' => [
            'sid'   => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from'  => env('TWILIO_FROM'),
        ],
    ],

];
