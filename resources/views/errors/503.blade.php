@include('errors.layout', [
    'code' => '503',
    'title' => 'AI-PGAALS is Temporarily Unavailable',
    'message' => "We're performing maintenance or deploying an update. Please check again shortly.",
    'eyebrow' => 'Maintenance mode',
    'status' => 'System maintenance in progress',
    'icon' => 'settings_suggest',
    'visual' => 'maintenance',
    'tone' => 'blue',
    'primary' => ['label' => 'Try Again', 'action' => 'reload', 'icon' => 'refresh'],
])
