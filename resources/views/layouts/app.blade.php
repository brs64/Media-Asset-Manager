<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Application - @yield('title', 'Page par défaut')</title> 
    
    <link href="/css/main.css" rel="stylesheet">
    
    @stack('styles')
</head>
<body>

    @include('layouts.header') 

    <main>
        @yield('content') 
    </main>

    @include('layouts.footer')

    <script src="/js/app.js"></script>
    
    @stack('scripts')
</body>
</html>