@include('errors.layout', [
    'code' => '429',
    'title' => 'Too Many Requests',
    'message' => "You're sending requests a little too quickly. Please wait a moment and try again.",
    'eyebrow' => 'Request activity monitor',
    'icon' => 'speed',
    'visual' => 'rate',
    'tone' => 'yellow',
    'primary' => ['label' => 'Try Again', 'action' => 'reload', 'icon' => 'replay'],
])
