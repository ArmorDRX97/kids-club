
@extends('layouts.app')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <h1 class="h3 mb-0">Редактирование секции</h1>
        <a href="{{ route('sections.index') }}" class="btn btn-outline-secondary">Вернуться к списку</a>
    </div>

    <form method="POST" action="{{ route('sections.update', $section) }}" class="card shadow-sm">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="name">Название</label>
                    <input id="name" name="name" class="form-control" value="{{ old('name', $section->name) }}" required maxlength="150">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="direction_id">Направление</label>
                    <select id="direction_id" name="direction_id" class="form-select">
                        <option value="">Без направления</option>
                        @foreach($directions as $direction)
                            <option value="{{ $direction->id }}" @selected(old('direction_id', $section->direction_id) == $direction->id)>{{ $direction->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="room_id">Помещение</label>
                    <select id="room_id" name="room_id" class="form-select">
                        <option value="">Не назначено</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" @selected(old('room_id', $section->room_id) == $room->id)>{{ $room->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-center">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $section->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Секция активна</label>
                    </div>
                </div>
            </div>
            @if($enrollmentsCount > 0)
                <div class="alert alert-info mt-3 mb-0">
                    У секции {{ $enrollmentsCount }} активных прикреплений. Используемые интервалы защищены от удаления.
                </div>
            @endif
        </div>

        @php
            $initialSchedule = collect(old('schedule', $scheduleMatrix))->mapWithKeys(function ($slots, $weekday) use ($lockedScheduleIds) {
                $weekday = (int) $weekday;
                $rows = collect($slots)->map(function ($slot) use ($lockedScheduleIds) {
                    $slotId = $slot['id'] ?? null;
                    return [
                        'id' => $slot['id'] ?? null,
                        'starts_at' => $slot['starts_at'] ?? '',
                        'ends_at' => $slot['ends_at'] ?? '',
                        'locked' => $slotId ? in_array($slotId, $lockedScheduleIds, true) : false,
                    ];
                })->values()->all();
                return [$weekday => $rows];
            })->toArray();
        @endphp

        @include('sections.partials.schedule-builder', [
            'initialSchedule' => $initialSchedule,
        ])

        <div class="card-footer d-flex justify-content-between align-items-center">
            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
            <a href="{{ route('sections.index') }}" class="btn btn-outline-secondary">Отмена</a>
        </div>
    </form>

    <form method="POST" action="{{ route('sections.destroy', $section) }}" class="mt-4" onsubmit="return confirm('Удалить секцию? Действие нельзя отменить.');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger" {{ $enrollmentsCount > 0 ? 'disabled' : '' }}>Удалить секцию</button>
        @if($enrollmentsCount > 0)
            <span class="text-secondary small ms-2">Нельзя удалить секцию с активными прикреплениями.</span>
        @endif
    </form>
@endsection
