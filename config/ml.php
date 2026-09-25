<?php

return [
    'enabled' => env('ML_PREDICTIONS_ENABLED', false),
    'endpoint' => env('ML_PREDICTION_API_URL', 'http://127.0.0.1:8001'),
    'timeout' => (int) env('ML_PREDICTION_TIMEOUT', 5),
    'model_name' => env('ML_READING_MODEL_NAME', 'reading_level_random_forest_v1'),
];
