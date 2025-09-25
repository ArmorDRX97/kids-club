@extends('layouts.app')
@section('content')
    @if($kidsCount>0)
        <div class="alert alert-warning">Нельзя деактивировать или удалить секцию — прикреплено детей: {{ $kidsCount }}</div>
    @endif
    <h1 class="h4 mb-3">Настройки секции «{{ $section->name }}»</h1>
    <form method="POST" action="{{ route('sections.update',$section) }}" class="card card-body">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Название</label>
                <input name="name" class="form-control" value="{{ old('name',$section->name) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Родитель (для подсекции)</label>
                <select name="parent_id" class="form-select">
                    <option value="">— нет —</option>
                    @foreach($parents as $p)
                        <option value="{{ $p->id }}" @selected(old('parent_id',$section->parent_id)==$p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Комната</label>
                <select name="room_id" class="form-select">
                    <option value="">— не указана —</option>
                    @foreach($rooms as $r)
                        <option value="{{ $r->id }}" @selected(old('room_id',$section->room_id)==$r->id)>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 form-check form-switch">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active"
                       value="1" {{ old('is_active',$section->is_active)?'checked':'' }}>
                <label class="form-check-label" for="is_active">Активна</label>
            </div>
            <div class="col-md-3">
                <label class="form-label">Тип расписания</label>
                <select name="schedule_type" class="form-select" required id="schedType">
                    <option value="weekly" @selected(old('schedule_type',$section->schedule_type)==='weekly')>По дням недели</option>
                    <option value="monthly" @selected(old('schedule_type',$section->schedule_type)==='monthly')>По дням месяца</option>
                </select>
            </div>
            <div class="col-md-9" id="wrapWeekly">
                <label class="form-label">Дни недели</label>
                <div class="d-flex flex-wrap gap-2">
                    @php
                        $selectedWeekdays = collect(old('weekdays', $section->weekdays ?? []))
                            ->map(fn ($v) => (int) $v)
                            ->all();
                    @endphp
                    @foreach([[1,'Пн'],[2,'Вт'],[3,'Ср'],[4,'Чт'],[5,'Пт'],[6,'Сб'],[7,'Вс']] as [$d,$t])
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="weekdays[]" value="{{ $d }}" id="wd{{ $d }}"
                                   @checked(in_array($d, $selectedWeekdays, true))>
                            <label class="form-check-label" for="wd{{ $d }}">{{ $t }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="col-md-9 d-none" id="wrapMonthly">
                <label class="form-label">Дни месяца</label>
                @php
                    $monthDaysOld = old('month_days');
                    $monthDaysValue = is_array($monthDaysOld)
                        ? implode(', ', $monthDaysOld)
                        : ($monthDaysOld ?? implode(', ', $section->month_days ?? []));
                @endphp
                <input class="form-control" name="month_days" placeholder="Например: 1, 10, 20" value="{{ $monthDaysValue }}">
                <small class="text-secondary">Укажите числа через запятую. Допустимы значения от 1 до 31.</small>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary">Сохранить</button>
            <a href="{{ route('sections.index') }}" class="btn btn-link">Отмена</a>
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
