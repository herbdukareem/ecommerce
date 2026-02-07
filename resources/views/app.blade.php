<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>E-commerce Platform</title>
    {{--
        Load the compiled CSS and JS from the resources/js directory.  The
        original scaffold placed the SPA inside a backend subfolder; since
        everything now lives at the project root, reference the assets
        directly in resources/js.
    --}}
    @vite(['resources/js/index.css', 'resources/js/main.js'])
</head>
<body class="antialiased">
    <div id="app"></div>
</body>
</html>