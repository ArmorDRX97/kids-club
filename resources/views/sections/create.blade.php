
@extends('layouts.app')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <h1 class="h3 mb-0">Новая секция</h1>
        <a href="{{ route('sections.index') }}" class="btn btn-outline-secondary">Вернуться к списку</a>
    </div>

    <form method="POST" action="{{ route('sections.store') }}" class="card shadow-sm">
        @csrf
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="name">Название</label>
                    <input id="name" name="name" class="form-control" value="{{ old('name') }}" required maxlength="150">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="direction_id">Направление</label>
                    <select id="direction_id" name="direction_id" class="form-select">
                        <option value="">Без направления</option>
                        @foreach($directions as $direction)
                            <option value="{{ $direction->id }}" @selected(old('direction_id') == $direction->id)>{{ $direction->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="room_id">Помещение</label>
                    <select id="room_id" name="room_id" class="form-select">
                        <option value="">Не назначено</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" @selected(old('room_id') == $room->id)>{{ $room->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-center">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Секция активна</label>
                    </div>
                </div>
            </div>
        </div>

        @php
            $oldSchedule = collect(old('schedule', []))->mapWithKeys(function ($slots, $weekday) {
                $weekday = (int) $weekday;
                $rows = collect($slots)->map(function ($slot) {
                    return [
                        'starts_at' => $slot['starts_at'] ?? '',
                        'ends_at' => $slot['ends_at'] ?? '',
                        'locked' => false,
                    ];
                })->values()->all();
                return [$weekday => $rows];
            })->toArray();
        @endphp

        @include('sections.partials.schedule-builder', [
            'initialSchedule' => $oldSchedule,
        ])

        <div class="card-footer d-flex justify-content-between align-items-center">
            <button type="submit" class="btn btn-primary">Сохранить секцию</button>
            <a href="{{ route('sections.index') }}" class="btn btn-outline-secondary">Отмена</a>
        </div>
    </form>
@endsection
