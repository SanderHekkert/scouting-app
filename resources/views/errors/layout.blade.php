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
<body class="min-h-screen antialiased">
    <div class="relative min-h-screen bg-gradient-to-br from-slate-950 via-[#0c2847] to-slate-900">
        <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -top-32 right-[-10%] h-[28rem] w-[28rem] rounded-full bg-brand-blue/20 blur-3xl"></div>
            <div class="absolute bottom-[-15%] left-[-5%] h-[22rem] w-[22rem] rounded-full bg-brand-red/18 blur-3xl"></div>
            <div class="absolute top-[40%] left-[20%] h-64 w-64 rounded-full bg-brand-green/12 blur-3xl"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(0,104,183,0.12),transparent)]"></div>
        </div>

        <div class="relative z-10 flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
            <div class="w-full max-w-md">
                <div class="mb-8 flex flex-col items-center gap-3 text-center">
                    <img src="/images/logo.png" alt="Fridtjof Nansen Groep 12" class="h-24 max-h-28 w-auto max-w-[14rem] drop-shadow-lg sm:h-28 sm:max-h-[7.5rem]">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-white/90">Fridtjof Nansen Groep 12</p>
                </div>

                <div class="relative w-full overflow-hidden rounded-2xl border border-white/20 bg-white/95 shadow-2xl shadow-brand-blue/15 backdrop-blur-md dark:border-brand-blue/35 dark:bg-slate-900/90">
                    <div class="h-1 w-full bg-gradient-to-r from-brand-red via-brand-yellow to-brand-blue" aria-hidden="true"></div>

                    <div class="px-6 py-8 text-center sm:px-10 sm:py-10">
                        <p class="inline-flex rounded-full bg-brand-blue/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand-blue-dark dark:bg-brand-blue/20 dark:text-brand-blue-light">
                            Fout {{ $code ?? '---' }}
                        </p>

                        <h1 class="mt-4 text-2xl font-semibold tracking-tight text-app-ink dark:text-app-ink-dark">
                            {{ $title ?? 'Er is iets misgegaan' }}
                        </h1>

                        <p class="mt-3 text-sm leading-6 text-app-muted dark:text-app-muted-dark">
                            {{ $message ?? 'Er ging iets fout. Probeer het opnieuw of neem contact op met het bestuur.' }}
                        </p>

                        @if (!empty($hint))
                            <div class="mt-6 rounded-lg border border-brand-blue/20 bg-brand-blue/5 px-4 py-3 text-start text-sm text-app-muted dark:border-brand-blue/35 dark:bg-brand-blue/10 dark:text-app-muted-dark">
                                <p class="font-semibold text-brand-blue-dark dark:text-brand-blue-light">Tip</p>
                                <p class="mt-1">{{ $hint }}</p>
                            </div>
                        @endif

                        <div class="mt-7 flex flex-wrap items-center justify-center gap-2">
                            <a href="{{ url()->previous() }}" class="inline-flex min-h-11 items-center rounded-md border-2 border-brand-blue bg-transparent px-4 py-2.5 text-sm font-semibold tracking-wide text-brand-blue-dark shadow-sm transition hover:bg-brand-blue/10 dark:border-brand-blue-light dark:text-brand-blue-light dark:hover:bg-brand-blue/20">
                                Vorige pagina
                            </a>
                            <a href="{{ url('/') }}" class="inline-flex min-h-11 items-center rounded-md border-2 border-brand-blue-dark bg-brand-blue px-4 py-2.5 text-sm font-semibold tracking-wide text-white transition hover:bg-brand-blue-dark">
                                Naar dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
