@include('errors.layout', [
    'code' => '419',
    'title' => 'Session Expired',
    'message' => 'Your session has expired for security reasons. Please refresh the page and try again.',
    'eyebrow' => 'Session timer',
    'icon' => 'timer',
    'visual' => 'timer',
    'tone' => 'yellow',
    'primary' => ['label' => 'Refresh Page', 'action' => 'reload', 'icon' => 'refresh'],
    'secondary' => ['label' => 'Return Home', 'href' => url('/'), 'icon' => 'home'],
])
