@include('errors.layout', [
    'code' => 403,
    'title' => 'Geen toegang',
    'message' => 'Je hebt geen rechten om deze pagina te bekijken.',
    'hint' => 'Controleer of je bent ingelogd met het juiste account of neem contact op met het bestuur.',
])
