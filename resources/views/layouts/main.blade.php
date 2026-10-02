<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'OiCram - Ordens de Serviço')</title>
        
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head> 
    <body class="bg-gray-100 text-gray-800 antialiased">
        <x-header />
        
        <main class="container mx-auto px-6 py-8">
            @if(session('success'))
                <div class="mb-4 px-4 py-3 bg-green-100 text-green-700 border border-green-400 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @yield('content')
        </main>

        <x-footer />
    </body>
</html>
