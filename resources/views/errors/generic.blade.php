@include('errors.layout', [
    'code' => $status ?? 500,
    'title' => 'Er is iets misgegaan',
    'message' => 'Er trad een onverwachte fout op.',
    'hint' => 'Probeer de pagina te verversen of ga terug naar het dashboard.',
])
