@include('errors.layout', [
    'code' => '404',
    'title' => 'Page Not Found',
    'message' => "The page you're looking for may have been moved, deleted, or doesn't exist.",
    'eyebrow' => 'Searching learning resources',
    'icon' => 'manage_search',
    'visual' => 'search',
    'tone' => 'blue',
    'primary' => ['label' => 'Return Home', 'href' => url('/'), 'icon' => 'home'],
    'secondary' => ['label' => 'Go Back', 'action' => 'back', 'fallback' => url('/'), 'icon' => 'arrow_back'],
])
