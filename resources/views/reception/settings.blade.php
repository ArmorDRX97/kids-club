@extends('layouts.app')

@section('content')
<div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Настройки ресепшена</h1>
        <p class="text-secondary mb-0">Определите рабочий график и режим закрытия смен для каждого ресепшиониста.</p>
    </div>
</div>

@if($receptionists->isEmpty())
    <div class="alert alert-info">Нет пользователей с ролью «Receptionist».</div>
@else
    <div class="d-flex flex-column gap-4">
        @foreach($receptionists as $receptionist)
            @php
                $setting = $receptionist->receptionSetting;
                $startValue = $setting?->shift_starts_at ? substr($setting->shift_starts_at, 0, 5) : '09:00';
                $endValue = $setting?->shift_ends_at ? substr($setting->shift_ends_at, 0, 5) : '18:00';
            @endphp
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                        <div>
                            <h2 class="h5 mb-1">{{ $receptionist->name }}</h2>
                            <div class="text-secondary small">{{ $receptionist->email }}</div>
                        </div>
                        <div class="text-secondary small align-self-lg-center">Последнее обновление: {{ optional($setting?->updated_at ?? $setting?->created_at)->format('d.m.Y H:i') ?? 'ещё не настраивалось' }}</div>
                    </div>
                    <form method="POST" action="{{ route('reception.settings.update', $receptionist) }}" class="row g-3 align-items-end">
                        @csrf
                        @method('PUT')
                        <div class="col-md-3">
                            <label class="form-label">Начало смены</label>
                            <input type="time" name="shift_starts_at" class="form-control" value="{{ old('shift_starts_at', $startValue) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Завершение смены</label>
                            <input type="time" name="shift_ends_at" class="form-control" value="{{ old('shift_ends_at', $endValue) }}" required>
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <label class="form-label d-block">Автоматическое закрытие</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="auto_close_enabled" value="1" id="auto-close-{{ $receptionist->id }}" {{ old('auto_close_enabled', $setting?->auto_close_enabled) ? 'checked' : '' }}>
                                <label class="form-check-label" for="auto-close-{{ $receptionist->id }}">Закрывать смену автоматически</label>
                            </div>
                            <div class="form-text">Если отключить, смену нужно завершать вручную после конца дня.</div>
                        </div>
                        <div class="col-md-2 col-lg-3">
                            <button class="btn btn-primary w-100">Сохранить</button>
                        </div>
                    </form>
                    <div class="text-secondary small mt-3">
                        @if($setting?->auto_close_enabled)
                            Смена завершится автоматически в {{ $endValue }}. При раннем начале сотрудник увидит уведомление, что окончание останется в заданное время.
                        @else
                            Кнопка завершения смены станет активной только после {{ $endValue }}. Это предотвращает преждевременное закрытие вручную.
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
