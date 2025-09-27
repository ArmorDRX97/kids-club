@extends('layouts.app')
@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1">Дети</h1>
            <p class="text-secondary mb-0">Следите за актуальностью контактов и статусом каждого ребёнка.</p>
        </div>
        <a href="{{ route('children.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>
            Добавить ребёнка
        </a>
    </div>

    <form class="children-toolbar card card-body mb-4" method="GET" action="{{ route('children.index') }}">
        <div class="row g-3 align-items-center">
            <div class="col-lg">
                <label class="children-toolbar__label" for="children-search">Поиск</label>
                <div class="children-search">
                    <i class="bi bi-search"></i>
                    <input id="children-search" name="q" type="search" value="{{ $q }}" class="form-control" placeholder="Введите имя или телефон">
                </div>
            </div>
            <div class="col-auto">
                <label class="kc-switch">
                    <input class="kc-switch__input" type="checkbox" id="showDeleted" name="deleted" value="1" {{ $showDeleted ? 'checked' : '' }}>
                    <span class="kc-switch__track">
                        <span class="kc-switch__handle"></span>
                    </span>
                    <span class="kc-switch__label">Неактивные дети</span>
                </label>
            </div>
            <div class="col-auto d-flex flex-wrap gap-2">
                <button class="btn btn-primary" type="submit">Применить</button>
                @if($q !== '' || $showDeleted)
                    <a class="btn btn-outline-secondary" href="{{ route('children.index') }}">Сбросить</a>
                @endif
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>ФИО</th>
                    <th>Контакты</th>
                    <th class="text-end">Действия</th>
                </tr>
                </thead>
                <tbody>
                @forelse($children as $c)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $c->full_name }}</div>
                            @unless($c->is_active)
                                <span class="badge text-bg-secondary mt-1">Неактивен</span>
                            @endunless
                        </td>
                        <td>
                            <div>{{ $c->parent_phone ?? $c->child_phone ?? '—' }}</div>
                            @if($c->parent2_phone)
                                <div class="text-secondary small">{{ $c->parent2_phone }}</div>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex flex-wrap gap-2 justify-content-end">
                                <a href="{{ route('children.show', $c) }}" class="btn btn-sm btn-light border">Посмотреть</a>
                                <a href="{{ route('children.edit', $c) }}" class="btn btn-sm btn-outline-secondary">Править</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-secondary py-4">Записей пока нет</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $children->links() }}</div>
@endsection
