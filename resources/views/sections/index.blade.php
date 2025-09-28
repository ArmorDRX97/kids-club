
@extends('layouts.app')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0">Секции и направления</h1>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#directionCreateModal">
                Добавить направление
            </button>
            <a href="{{ route('sections.create') }}" class="btn btn-primary">Добавить секцию</a>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-secondary mb-3">Направления</h2>
            @if($directions->isEmpty())
                <p class="text-secondary mb-0">Пока нет ни одного направления. Создайте первое, чтобы упорядочить секции.</p>
            @else
                <div class="d-flex flex-wrap gap-2">
                    @foreach($directions as $direction)
                        <button type="button"
                                class="btn btn-outline-primary d-flex align-items-center gap-2"
                                data-bs-toggle="modal"
                                data-bs-target="#directionEditModal-{{ $direction->id }}">
                            <span>{{ $direction->name }}</span>
                            <span class="badge text-bg-primary">{{ $direction->sections->count() }}</span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @foreach($directions as $direction)
        <div class="card mb-4 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h2 class="h5 mb-0">{{ $direction->name }}</h2>
                    <span class="text-secondary small">Секций: {{ $direction->sections->count() }}</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#directionEditModal-{{ $direction->id }}">
                    Управлять направлением
                </button>
            </div>
            <div class="card-body">
                @include('sections.partials.section-grid', ['sections' => $direction->sections])
            </div>
        </div>
    @endforeach

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="h5 mb-0">Без направления</h2>
                <span class="text-secondary small">Секции без присвоенного направления</span>
            </div>
        </div>
        <div class="card-body">
            @if($orphanSections->isEmpty())
                <p class="text-secondary mb-0">Все секции распределены по направлениям.</p>
            @else
                @include('sections.partials.section-grid', ['sections' => $orphanSections])
            @endif
        </div>
    </div>

    <div class="modal fade" id="directionCreateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="{{ route('directions.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Новое направление</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="direction-name" class="form-label">Название</label>
                        <input type="text" class="form-control" id="direction-name" name="name" required maxlength="150">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($directions as $direction)
        <div class="modal fade" id="directionEditModal-{{ $direction->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('directions.update', $direction) }}" id="direction-update-{{ $direction->id }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $direction->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="direction-name-{{ $direction->id }}">Название</label>
                                <input type="text"
                                       class="form-control"
                                       id="direction-name-{{ $direction->id }}"
                                       name="name"
                                       value="{{ $direction->name }}"
                                       required
                                       maxlength="150">
                            </div>
                            @if($direction->sections->isNotEmpty())
                                <p class="text-secondary small mb-0">Удаление направления перенесёт {{ \Illuminate\Support\Str::plural('секцию', $direction->sections->count(), true) }} в блок «Без направления».</p>
                            @endif
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="submit"
                                    form="direction-delete-{{ $direction->id }}"
                                    class="btn btn-outline-danger"
                                    onclick="return confirm('Удалить направление? Все секции останутся, но будут без направления.');">
                                Удалить
                            </button>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                                <button type="submit" form="direction-update-{{ $direction->id }}" class="btn btn-primary">Сохранить</button>
                            </div>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('directions.destroy', $direction) }}" id="direction-delete-{{ $direction->id }}" class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection
