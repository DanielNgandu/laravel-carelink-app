<?php
return [
'name' => env('APP_NAME', 'CareLink Follow-Up'), 'env' => env('APP_ENV', 'production'),
'debug' => (bool) env('APP_DEBUG', false), 'url' => env('APP_URL', 'http://localhost'),
'key' => env('APP_KEY'), 'cipher' => 'AES-256-CBC', 'timezone' => 'Africa/Lusaka',
'locale' => 'en', 'fallback_locale' => 'en', 'faker_locale' => 'en_US',
'maintenance' => ['driver' => 'file'],
];
