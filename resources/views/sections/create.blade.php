
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

            <!-- Пробные занятия -->
            <div class="row g-3 mt-3">
                <div class="col-12">
                    <h5 class="mb-3">Пробные занятия</h5>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="has_trial" name="has_trial" value="1" {{ old('has_trial') ? 'checked' : '' }}>
                        <input type="hidden" name="has_trial" value="0">
                        <label class="form-check-label" for="has_trial">Есть пробные занятия</label>
                    </div>
                </div>
                <div class="col-md-6" id="trial-free-container" style="display: none;">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="trial_is_free" name="trial_is_free" value="1" {{ old('trial_is_free', true) ? 'checked' : '' }}>
                        <input type="hidden" name="trial_is_free" value="0">
                        <label class="form-check-label" for="trial_is_free">Пробное занятие бесплатное</label>
                    </div>
                </div>
                <div class="col-md-6" id="trial-price-container" style="display: none;">
                    <label class="form-label" for="trial_price">Цена пробного занятия (₸)</label>
                    <input id="trial_price" name="trial_price" type="number" step="0.01" min="0" class="form-control" value="{{ old('trial_price') }}">
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hasTrialCheckbox = document.getElementById('has_trial');
            const trialFreeContainer = document.getElementById('trial-free-container');
            const trialPriceContainer = document.getElementById('trial-price-container');
            const trialIsFreeCheckbox = document.getElementById('trial_is_free');
            const trialPriceInput = document.getElementById('trial_price');

            function toggleTrialFields() {
                if (hasTrialCheckbox.checked) {
                    trialFreeContainer.style.display = 'block';
                    if (trialIsFreeCheckbox.checked) {
                        trialPriceContainer.style.display = 'none';
                        trialPriceInput.value = '';
                    } else {
                        trialPriceContainer.style.display = 'block';
                    }
                } else {
                    trialFreeContainer.style.display = 'none';
                    trialPriceContainer.style.display = 'none';
                    trialIsFreeCheckbox.checked = true;
                    trialPriceInput.value = '';
                }
            }

            // Обработчики для скрытых полей
            hasTrialCheckbox.addEventListener('change', function() {
                const hiddenField = this.parentNode.querySelector('input[type="hidden"]');
                hiddenField.value = this.checked ? '1' : '0';
            });

            trialIsFreeCheckbox.addEventListener('change', function() {
                const hiddenField = this.parentNode.querySelector('input[type="hidden"]');
                hiddenField.value = this.checked ? '1' : '0';
            });

            hasTrialCheckbox.addEventListener('change', toggleTrialFields);
            trialIsFreeCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    trialPriceContainer.style.display = 'none';
                    trialPriceInput.value = '';
                } else {
                    trialPriceContainer.style.display = 'block';
                }
            });

            // Инициализация при загрузке
            toggleTrialFields();
        });
    </script>
@endsection
