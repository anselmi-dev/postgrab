<?php

return [

    'cache_ttl_seconds' => 60 * 60 * 24,

    'max_file_bytes' => 512 * 1024 * 1024,

    'max_zip_bytes' => 1024 * 1024 * 1024,

    'penalties' => [60, 300, 900, 3600],

    'x_bearer_token' => env('X_BEARER_TOKEN'),

    'turnstile_site_key' => env('TURNSTILE_SITE_KEY'),

    'turnstile_secret' => env('TURNSTILE_SECRET_KEY'),

    'contact_email' => env('CONTACT_EMAIL', 'hola@postgrab.test'),

    'dmca_email' => env('DMCA_EMAIL', 'dmca@postgrab.test'),

    'admin_email' => env('ADMIN_EMAIL'),

    'admin_password' => env('ADMIN_PASSWORD'),

    'ad_top' => env('AD_SLOT_TOP'),

    'ad_middle' => env('AD_SLOT_MIDDLE'),

    'ad_footer' => env('AD_SLOT_FOOTER'),

];
