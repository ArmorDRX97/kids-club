<!doctype html>
<html lang="ru" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KidsClub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/js/app.js'])
    <style>
        body{ background:#f6f8fb; }
        .card-kpi .kpi{ font: 800 28px/1.1 system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial; }
        .badge-soft{ background: #eef2ff; color:#3730a3; }
        .table thead th{ white-space: nowrap; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="/dashboard">KidsClub</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topnav"><span class="navbar-toggler-icon"></span></button>
        <div id="topnav" class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="/dashboard">Дашборд</a></li>
                <li class="nav-item"><a class="nav-link" href="/reception">Ресепшен</a></li>
                <li class="nav-item"><a class="nav-link" href="/children">Дети</a></li>
                <li class="nav-item"><a class="nav-link" href="/sections">Секции</a></li>
                @role('Admin')
                <li class="nav-item"><a class="nav-link" href="{{ route('reception.settings') }}">Настройки ресепшена</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('reports.index') }}">Отчёты</a></li>
                <li class="nav-item"><a class="nav-link" href="/rooms">Комнаты</a></li>
                <li class="nav-item"><a class="nav-link" href="/users">Пользователи</a></li>
                @endrole
            </ul>
            <div class="d-flex align-items-center gap-3 ms-lg-auto">
                <a class="nav-link" href="/account">Личный кабинет {{ auth()->user()->name ?? '' }}</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-link nav-link px-0">Выйти</button>
                </form>
            </div>
        </div>
    </div>
</nav>
<main class="container py-4">
    @include('partials.flash')
    @yield('content')
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
