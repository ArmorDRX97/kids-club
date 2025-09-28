<!doctype html>
<html lang="ru" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'KidsClub') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/kidsclub-theme.css') }}">
    @stack('styles')
    @vite(['resources/js/app.js'])
</head>
<body>
@php
    $user = auth()->user();
    $isReceptionistOnly = $user && $user->hasRole('Receptionist') && ! $user->hasRole('Admin');
    $homeRoute = $isReceptionistOnly ? 'reception.index' : 'dashboard';
@endphp
<div class="app-shell">
    <header class="app-navbar">
        <div class="app-navbar-inner">
            <div class="app-navbar-left">
                <button class="btn btn-icon btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar" aria-label="Открыть меню">
                    <i class="bi bi-list"></i>
                </button>
                <a class="app-brand d-flex align-items-center gap-2" href="{{ route($homeRoute) }}">
                    <span class="kc-avatar"><i class="bi bi-stars"></i></span>
                    <span>KidsClub</span>
                </a>
            </div>
            <div class="app-navbar-actions">
                <button class="btn btn-icon" type="button" aria-label="Уведомления">
                    <i class="bi bi-bell"></i>
                </button>
                <button class="btn btn-icon" type="button" aria-label="Настройки">
                    <i class="bi bi-gear"></i>
                </button>
                @auth
                    <a class="btn btn-outline-secondary app-profile" href="{{ route('account.index') }}">
                        <i class="bi bi-person-circle"></i>
                        <span>{{ auth()->user()->name }}</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <div class="app-shell-body">
        <aside class="app-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="appSidebar" aria-labelledby="appSidebarLabel">
            <div class="offcanvas-header d-lg-none">
                <div class="d-flex align-items-center gap-2">
                    <span class="kc-avatar"><i class="bi bi-stars"></i></span>
                    <span class="fw-bold">KidsClub</span>
                </div>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Закрыть"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column">
                <nav class="app-menu">
                    @unless($isReceptionistOnly)
                        <a class="app-menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="bi bi-speedometer2"></i>
                            <span>Дашборд</span>
                        </a>
                    @endunless
                    <a class="app-menu-link {{ request()->routeIs('reception.*') ? 'active' : '' }}" href="{{ route('reception.index') }}">
                        <i class="bi bi-people"></i>
                        <span>Приём</span>
                    </a>
                    <a class="app-menu-link {{ request()->routeIs('children.*') ? 'active' : '' }}" href="{{ route('children.index') }}">
                        <i class="bi bi-balloon-heart"></i>
                        <span>Дети</span>
                    </a>
                    <a class="app-menu-link {{ request()->routeIs('sections.*') ? 'active' : '' }}" href="{{ route('sections.index') }}">
                        <i class="bi bi-diagram-3"></i>
                        <span>Секции</span>
                    </a>
                    @role('Admin')
                        <a class="app-menu-link {{ request()->routeIs('reception.summary') ? 'active' : '' }}" href="{{ route('reception.summary') }}">
                            <i class="bi bi-clipboard-data"></i>
                            <span>Сводка</span>
                        </a>
                        <a class="app-menu-link {{ request()->routeIs('reception.settings') ? 'active' : '' }}" href="{{ route('reception.settings') }}">
                            <i class="bi bi-sliders"></i>
                            <span>Настройки приёма</span>
                        </a>
                        <a class="app-menu-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                            <i class="bi bi-graph-up-arrow"></i>
                            <span>Отчёты</span>
                        </a>
                        <a class="app-menu-link {{ request()->routeIs('rooms.*') ? 'active' : '' }}" href="{{ route('rooms.index') }}">
                            <i class="bi bi-building"></i>
                            <span>Комнаты</span>
                        </a>
                        <a class="app-menu-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                            <i class="bi bi-shield-check"></i>
                            <span>Пользователи</span>
                        </a>
                    @endrole
                </nav>
            </div>
        </aside>

        <div class="app-content-area">
            <main class="app-main">
                <div class="app-main-inner">
                    @hasSection('page-header')
                        @yield('page-header')
                    @endif

                    @include('partials.flash')

                    @yield('content')
                </div>
            </main>

            <footer class="footer-muted">
                Мы растём вместе с детьми • KidsClub {{ now()->format('Y') }}
            </footer>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
@stack('scripts')
</body>
</html>
