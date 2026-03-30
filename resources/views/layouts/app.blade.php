<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Shore Reporting')</title>

    <link rel="icon" href="{{ asset('images/shore-reporting-logo.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('images/shore-reporting-logo.svg') }}">

    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Nunito', sans-serif; margin: 0; -webkit-font-smoothing: antialiased; background-color: #f3f4f6; min-height: 100vh; }
        .container { max-width: 42rem; margin-left: auto; margin-right: auto; padding: 1.5rem; }
        .card { background: #fff; border-radius: 0.5rem; box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1); padding: 2rem; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; font-weight: 600; color: #374151; margin-bottom: 0.375rem; font-size: 0.875rem; }
        input[type="email"], input[type="password"], input[type="text"] { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 1rem; }
        input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgb(37 99 235 / 0.2); }
        .btn { display: inline-block; padding: 0.5rem 1rem; font-weight: 600; font-size: 0.875rem; border-radius: 0.375rem; cursor: pointer; border: none; text-decoration: none; text-align: center; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #6b7280; color: #fff; }
        .btn-secondary:hover { background: #4b5563; }
        .text-danger { color: #dc2626; font-size: 0.875rem; margin-top: 0.25rem; }
        .text-center { text-align: center; }
        .mb-4 { margin-bottom: 1rem; }
        .mt-4 { margin-top: 1rem; }
        .flex { display: flex; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .checkbox { width: 1rem; height: 1rem; margin-right: 0.5rem; }
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
