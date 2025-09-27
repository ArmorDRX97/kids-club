@extends('layouts.app')
@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0">Секции и подсекции</h1>
        @role('Admin')
            <a href="{{ route('sections.create') }}" class="btn btn-primary">Добавить секцию</a>
        @endrole
    </div>

    @foreach($parents as $parent)
        <div class="card mb-3 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between flex-wrap gap-3">
                    <div>
                        <div class="text-primary fw-semibold">{{ $parent->name }}</div>
                        <div class="text-secondary small">Комната: {{ $parent->room ? ($parent->room->name.' ('.$parent->room->number_label.')') : '—' }}</div>
                        <div class="mt-2 d-flex flex-wrap gap-2">
                            <span class="badge text-bg-primary">Детей: {{ $parent->enrollments_count }}</span>
                            <span class="badge text-bg-info">Пакетов: {{ $parent->packages_count }}</span>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('sections.members.index', $parent) }}" class="btn btn-sm btn-outline-success">Дети</a>
                        @role('Admin')
                            <a href="{{ route('sections.packages.index', $parent) }}" class="btn btn-sm btn-outline-primary">Пакеты</a>
                            <a href="{{ route('sections.edit', $parent) }}" class="btn btn-sm btn-outline-secondary">Настройки</a>
                        @endrole
                    </div>
                </div>

                @if($parent->children->count())
                    <div class="mt-3">
                        <div class="small text-secondary mb-2">Подсекции</div>
                        <div class="row g-2">
                            @foreach($parent->children as $child)
                                <div class="col-md-6">
                                    <div class="border rounded p-2 h-100">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div>
                                                <div class="fw-semibold">{{ $child->name }}</div>
                                                <div class="text-secondary small">Комната: {{ $child->room ? ($child->room->name.' ('.$child->room->number_label.')') : '—' }}</div>
                                                <div class="d-flex gap-2 mt-2">
                                                    <span class="badge text-bg-primary">Детей: {{ $child->enrollments_count }}</span>
                                                    <span class="badge text-bg-info">Пакетов: {{ $child->packages_count }}</span>
                                                </div>
                                            </div>
                                            <div class="d-flex flex-column gap-1">
                                                <a href="{{ route('sections.members.index', $child) }}" class="btn btn-sm btn-outline-success">Дети</a>
                                                @role('Admin')
                                                    <a href="{{ route('sections.packages.index', $child) }}" class="btn btn-sm btn-outline-primary">Пакеты</a>
                                                    <a href="{{ route('sections.edit', $child) }}" class="btn btn-sm btn-outline-secondary">Настройки</a>
                                                @endrole
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
@endsection
