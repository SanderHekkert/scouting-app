@include('errors.layout', [
    'code' => 503,
    'title' => 'Tijdelijk niet beschikbaar',
    'message' => 'De app is tijdelijk niet beschikbaar door onderhoud of een storing.',
    'hint' => 'Probeer het later opnieuw.',
])
