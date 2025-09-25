@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-3">Карточка ребёнка</h1>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-semibold h5 mb-0">
                    {{ $child->last_name }} {{ $child->first_name }} {{ $child->patronymic }}
                </div>
                <div>
                    @if($child->is_active)
                        <span class="badge text-bg-success">Активен</span>
                    @else
                        <span class="badge text-bg-secondary">Неактивен</span>
                    @endif
                </div>
            </div>

            <dl class="row mb-0">
                <dt class="col-sm-3">Дата рождения</dt>
                <dd class="col-sm-9">{{ $child->dob?->format('d.m.Y') ?? '—' }}</dd>

                <dt class="col-sm-3">Телефон ребёнка</dt>
                <dd class="col-sm-9">{{ $child->child_phone ?? '—' }}</dd>

                <dt class="col-sm-3">Телефон родителя</dt>
                <dd class="col-sm-9">{{ $child->parent_phone ?? '—' }}</dd>

                <dt class="col-sm-3">Телефон второго родителя</dt>
                <dd class="col-sm-9">{{ $child->parent2_phone ?? '—' }}</dd>

                <dt class="col-sm-3">Заметки</dt>
                <dd class="col-sm-9">{{ $child->notes ?? '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('children.edit', $child) }}" class="btn btn-primary">Редактировать</a>
        <a href="{{ route('children.index') }}" class="btn btn-link">Назад к списку</a>
    </div>

    {{-- Блоки для расширения в будущем --}}
    <div class="mt-4">
        <h2 class="h5 mb-3">Прикрепления и посещения</h2>
        <div class="card">
            <div class="card-body">
                <p class="text-secondary mb-0">Здесь позже можно вывести список секций, пакетов и историю посещений ребёнка.</p>
            </div>
        </div>
    </div>
@endsection
