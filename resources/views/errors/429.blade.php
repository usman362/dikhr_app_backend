@include('errors.layout', [
    'code'    => '429',
    'title'   => 'Too Many Requests',
    'message' => 'You\'re going a bit too fast. Please wait a moment and try again.',
])
