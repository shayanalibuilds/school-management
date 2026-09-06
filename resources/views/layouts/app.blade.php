<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <title>{{ config('app.name', 'School Management System') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400..600&display=swap" rel="stylesheet" />

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

    <body class="min-h-screen bg-zinc-50 text-zinc-950 antialiased">
        <x-navigation />

        <main class="mx-auto w-full max-w-5xl px-4 py-10 sm:px-6">
            {{ $slot }}
        </main>

        @filamentScripts
    </body>
</html>
