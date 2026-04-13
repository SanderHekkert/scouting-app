<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Er is iets misgegaan' }} - Scouting App</title>
    @if (!app()->runningUnitTests() && (app()->environment('local') || file_exists(public_path('build/manifest.json'))))
        @vite(['resources/css/app.css'])
    @endif
</head>
<body class="min-h-screen bg-app-canvas text-app-ink antialiased dark:bg-app-canvas-dark dark:text-app-ink-dark">
    <div class="min-h-screen px-6 py-10">
        <div class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-2xl items-center justify-center">
            <div class="w-full rounded-2xl border border-white/20 bg-white/95 p-8 text-center shadow-2xl backdrop-blur dark:border-brand-blue/35 dark:bg-app-panel-dark/95">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-blue-dark dark:text-brand-blue-light">Fridtjof Nansen Groep 12</p>
                <p class="mt-3 inline-flex rounded-full bg-brand-blue/10 px-3 py-1 text-xs font-semibold text-brand-blue-dark dark:bg-brand-blue/20 dark:text-brand-blue-light">
                    Fout {{ $code ?? '---' }}
                </p>
                <h1 class="mt-4 text-3xl font-bold text-slate-900 dark:text-app-ink-dark">{{ $title ?? 'Er is iets misgegaan' }}</h1>
                <p class="mt-3 text-sm leading-6 text-slate-700 dark:text-app-muted-dark">
                    {{ $message ?? 'Er ging iets fout. Probeer het opnieuw of neem contact op met het bestuur.' }}
                </p>

                @if (!empty($hint))
                    <div class="mt-6 rounded-xl border border-brand-blue/20 bg-brand-blue/5 px-4 py-3 text-left text-sm text-slate-700 dark:border-brand-blue/35 dark:bg-brand-blue/10 dark:text-app-muted-dark">
                        <p class="font-semibold text-brand-blue-dark dark:text-brand-blue-light">Tip</p>
                        <p class="mt-1">{{ $hint }}</p>
                    </div>
                @endif

                <div class="mt-7 flex items-center justify-center gap-2">
                    <a href="{{ url()->previous() }}" class="inline-flex items-center rounded-lg border border-app-border bg-white px-4 py-2.5 text-sm font-semibold text-app-ink shadow-sm hover:bg-slate-50 dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark">
                        Vorige pagina
                    </a>
                    <a href="{{ url('/') }}" class="inline-flex items-center rounded-lg bg-brand-blue px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-blue-dark">
                        Naar dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
