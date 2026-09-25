<?php

return [
    'enabled' => env('OPENAI_TUTOR_ENABLED', false),
    'key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_TUTOR_MODEL', 'gpt-6-astra'),
    'per_minute' => 5,
    'per_day' => 30,
];
