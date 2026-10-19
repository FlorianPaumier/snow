@php($status = $status ?? $exception->getStatusCode())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ $status }} — Frostline OPS</title>
        <style>
            :root { color-scheme: light dark; font-family: system-ui, sans-serif; background: #f7fbfc; color: #182d36; }
            body { margin: 0; display: grid; place-items: center; min-height: 100svh; }
            main { padding: 24px; text-align: center; }
            p { color: #526971; font-size: 14px; }
            h1 { font-size: clamp(28px, 5vw, 36px); letter-spacing: -.025em; }
            a { display: inline-flex; align-items: center; min-height: 44px; margin-top: 16px; padding: 0 20px; border-radius: 12px; background: #076d76; color: #fff; text-decoration: none; }
            a:focus-visible { outline: 3px solid #076d76; outline-offset: 4px; }
            @media (prefers-color-scheme: dark) { :root { background: #10242b; color: #edf7f8; } p { color: #b2cbd0; } a { background: #68d8d3; color: #123139; } }
        </style>
    </head>
    <body>
        <main>
            <p>{{ $status }}</p>
            <h1>{{ __('errors.titles')[$status] ?? __('errors.generic') }}</h1>
            <a href="{{ route('home') }}">{{ __('errors.home') }}</a>
        </main>
    </body>
</html>
