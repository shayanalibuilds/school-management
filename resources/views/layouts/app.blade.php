<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <title>{{ config('app.name', 'School Management System') }}</title>

        <!-- Fonts: Newsreader for institutional headlines, Plus Jakarta Sans for the interface. -->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
            href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,400..700;1,6..72,400..700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
            rel="stylesheet"
        />

        <!-- Styles: app CSS first, Filament component CSS second so components win. -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link
            rel="stylesheet"
            href="{{ asset('css/filament/filament/app.css').'?v='.filemtime(public_path('css/filament/filament/app.css')) }}"
        />
        @filamentStyles
        <style>
            /* Filament's asset pipeline registers its default amber as the
               named primary palette; the public web uses the school's blue. */
            :root {
                --primary-50: #eff6ff;
                --primary-100: #dbeafe;
                --primary-200: #bfdbfe;
                --primary-300: #93c5fd;
                --primary-400: #60a5fa;
                --primary-500: #3b82f6;
                --primary-600: #2563eb;
                --primary-700: #1d4ed8;
                --primary-800: #1e40af;
                --primary-900: #1e3a8a;
                --primary-950: #172554;
            }
        </style>
    </head>

    <body class="min-h-screen bg-paper font-sans text-ink antialiased">
        <a
            href="#main"
            class="sr-only z-50 rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white focus:not-sr-only focus:fixed focus:left-4 focus:top-4"
        >
            Skip to content
        </a>

        <x-navigation />

        <main id="main" class="mx-auto w-full max-w-7xl scroll-mt-20 px-4 pb-14 pt-8 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>

        <x-footer />

        @filamentScripts
    </body>
</html>
