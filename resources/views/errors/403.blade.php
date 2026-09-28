@include('errors.layout', [
    'code' => '403',
    'title' => 'Access Restricted',
    'message' => "You don't have permission to access this page.",
    'eyebrow' => 'Protected learning space',
    'icon' => 'shield_lock',
    'visual' => 'shield',
    'tone' => 'green',
    'primary' => ['label' => 'Return Home', 'href' => url('/'), 'icon' => 'home'],
])
