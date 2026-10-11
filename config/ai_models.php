<?php

return [
    'endpoint' => env('ML_PREDICTION_API_URL', 'http://127.0.0.1:8001'),
    'admin_token' => env('ML_ADMIN_TOKEN'),
    'training_timeout' => (int) env('ML_TRAINING_TIMEOUT', 900),
    'minimum_training_rows' => (int) env('ML_MINIMUM_TRAINING_ROWS', 30),
    'recommendation_k' => 5,
    'collaborative_minimum' => [
        'students' => 5,
        'activities' => 5,
        'interactions' => 20,
    ],
    'hybrid_alpha' => (float) env('ML_HYBRID_ALPHA', 0.7),
];
