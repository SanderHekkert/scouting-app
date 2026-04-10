@include('errors.layout', [
    'code' => 500,
    'title' => 'Interne fout',
    'message' => 'Er is iets misgegaan aan onze kant.',
    'hint' => 'Probeer het over een paar minuten opnieuw. Blijft dit gebeuren, meld het bij het bestuur.',
])
