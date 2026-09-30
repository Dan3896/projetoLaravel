<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title')</title>
        <script src="/js/script.js"></script>
        
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
        <link rel="stylesheet" href="/css/style.css">

    </head> 
    <body>
        <header>
            <nav class="navbar navbar-expand-lg navabar-light">
                <div class="collapse navbar-collapse" id="navbar">
                    <a href="/" class="navbar-brand">
                        <img src="/img/hdcevents_logo.svg" alt="">
                    </a>
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a href="/" class="nav-link">Eventos</a>
                        </li>
                        <li class="nav-item">
                            <a href="/" class="nav-link">cadastrar</a>
                        </li>
                        <li class="nav-item">
                            <a href="/" class="nav-link">entrar</a>
                        </li>
                        <li class="nav-item">
                            <a href="/events/create" class="nav-link">criar Eventos</a>
                        </li>
                    </ul>
                </div>
            </nav>
        </header>    
            <main>
                <div class="container-fluid">
                    <div class="row">

                        @if(session('msg'))
                            <p class="msg">{{ session('msg') }}</p>
                        @endif

                        @yield('content')
                    </div>
                </div>
            </main>
        <footer>
            <p>HDC Events &copy; 2026</p>
        </footer>
    </body>
</html>
