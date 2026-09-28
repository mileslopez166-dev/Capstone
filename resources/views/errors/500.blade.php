@include('errors.layout', [
    'code' => '500',
    'title' => 'Something Went Wrong',
    'message' => "AI-PGAALS encountered an unexpected error. The system couldn't complete your request.",
    'eyebrow' => 'System diagnostic',
    'icon' => 'hub',
    'visual' => 'diagnostic',
    'tone' => 'red',
    'primary' => ['label' => 'Try Again', 'action' => 'reload', 'icon' => 'replay'],
    'secondary' => ['label' => 'Return Home', 'href' => url('/'), 'icon' => 'home'],
])
