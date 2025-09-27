@extends('layouts.app')
@section('content')
    @if($kidsCount>0)
        <div class="alert alert-warning">Секция активно используется: прикреплено детей — {{ $kidsCount }}. Будьте аккуратны с изменениями.</div>
    @endif

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">Редактирование секции «{{ $section->name }}»</h1>
        <a href="{{ route('sections.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Назад</a>
    </div>

    <form method="POST" action="{{ route('sections.update',$section) }}" class="sections-form card card-body">
        @csrf
        @method('PUT')
        <div class="sections-form__grid">
            <div class="sections-form__group">
                <label class="form-label" for="name">Название</label>
                <input id="name" name="name" class="form-control" value="{{ old('name',$section->name) }}" required>
            </div>
            <div class="sections-form__group">
                <label class="form-label" for="parent_id">Родительская секция</label>
                <select id="parent_id" name="parent_id" class="form-select">
                    <option value="">Без родителя</option>
                    @foreach($parents as $p)
                        <option value="{{ $p->id }}" @selected(old('parent_id',$section->parent_id)==$p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sections-form__group">
                <label class="form-label" for="room_id">Кабинет</label>
                <select id="room_id" name="room_id" class="form-select">
                    <option value="">Без кабинета</option>
                    @foreach($rooms as $r)
                        <option value="{{ $r->id }}" @selected(old('room_id',$section->room_id)==$r->id)>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sections-form__group">
                <label class="form-label" for="schedType">График</label>
                <select name="schedule_type" class="form-select" required id="schedType">
                    <option value="weekly" @selected(old('schedule_type',$section->schedule_type)==='weekly')>По дням недели</option>
                    <option value="monthly" @selected(old('schedule_type',$section->schedule_type)==='monthly')>По датам месяца</option>
                </select>
            </div>
            <div class="sections-form__group sections-form__group--wide" id="wrapWeekly">
                <label class="form-label">Дни недели</label>
                <div class="sections-form__tags">
                    @php
                        $selectedWeekdays = collect(old('weekdays', $section->weekdays ?? []))
                            ->map(fn ($v) => (int) $v)
                            ->all();
                    @endphp
                    @foreach([[1,'Пн'],[2,'Вт'],[3,'Ср'],[4,'Чт'],[5,'Пт'],[6,'Сб'],[7,'Вс']] as [$d,$t])
                        <input class="sections-chip__input" type="checkbox" name="weekdays[]" value="{{ $d }}" id="wd{{ $d }}" @checked(in_array($d, $selectedWeekdays, true))>
                        <label class="sections-chip" for="wd{{ $d }}">{{ $t }}</label>
                    @endforeach
                </div>
            </div>
            <div class="sections-form__group sections-form__group--wide d-none" id="wrapMonthly">
                <label class="form-label" for="month_days">Дни месяца</label>
                @php
                    $monthDaysOld = old('month_days');
                    $monthDaysValue = is_array($monthDaysOld)
                        ? implode(', ', $monthDaysOld)
                        : ($monthDaysOld ?? implode(', ', $section->month_days ?? []));
                @endphp
                <input class="form-control" name="month_days" id="month_days" placeholder="Например: 1, 10, 20" value="{{ $monthDaysValue }}">
                <div class="form-text">Перечислите числа месяца через запятую (от 1 до 31).</div>
            </div>
            <div class="sections-form__group sections-form__group--switch">
                <input type="hidden" name="is_active" value="0">
                <label class="kc-switch mb-0">
                    <input class="kc-switch__input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active',$section->is_active)?'checked':'' }}>
                    <span class="kc-switch__track"><span class="kc-switch__handle"></span></span>
                    <span class="kc-switch__label">Секция активна</span>
                </label>
            </div>
        </div>

        <div class="sections-form__footer">
            <button class="btn btn-primary">Сохранить</button>
            <a href="{{ route('sections.index') }}" class="btn btn-outline-secondary">Отменить</a>
        </div>
    </form>
    <script>
        const schedType = document.getElementById('schedType');
        const wrapWeekly = document.getElementById('wrapWeekly');
        const wrapMonthly = document.getElementById('wrapMonthly');
        function toggleSched(){
            const isMonthly = schedType.value === 'monthly';
            wrapWeekly.classList.toggle('d-none', isMonthly);
            wrapMonthly.classList.toggle('d-none', !isMonthly);
        }
        toggleSched();
        schedType.addEventListener('change', toggleSched);
    </script>
@endsection
