<?php

declare(strict_types=1);

/*
 * Copy this file to config/gemini.php and replace the placeholder API key.
 * Keep the real file out of version control.
 */
return [
    'api_key' => 'YOUR_GEMINI_API_KEY_HERE',
    'model' => 'gemini-3.8-flash',
    'max_upload_bytes' => 100 * 1024 * 1024,
    'request_timeout' => 180,
];
