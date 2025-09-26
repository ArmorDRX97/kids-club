@extends('layouts.app')

@section('content')
<div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Аналитика и отчётность</h1>
        <p class="text-secondary mb-0">Собранные показатели по посещениям, оплатам и работе ресепшена.</p>
    </div>
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
        <div>
            <label class="form-label small mb-1">Период с</label>
            <input type="date" name="from" value="{{ $from }}" class="form-control">
        </div>
        <div>
            <label class="form-label small mb-1">по</label>
            <input type="date" name="to" value="{{ $to }}" class="form-control">
        </div>
        <button class="btn btn-outline-primary">Показать</button>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-kpi shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-secondary small">Всего посещений</div>
                <div class="kpi">{{ $attendanceTotal }}</div>
                <div class="text-secondary small mt-1">Уникальных детей: {{ $uniqueChildren }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-kpi shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-secondary small">Оплаты за период</div>
                <div class="kpi">{{ number_format((float)$paymentsTotal, 2, ',', ' ') }} ₽</div>
                <div class="text-secondary small mt-1">Новых пакетов: {{ $newEnrollments }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-kpi shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-secondary small">Среднее посещений в день</div>
                <div class="kpi">{{ number_format($attendanceTotal / $daysInRange, 1, ',', ' ') }}</div>
                <div class="text-secondary small mt-1">за {{ $daysInRange }} дн.</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-kpi shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-secondary small">Рабочие часы ресепшена</div>
                @php
                    $totalMinutes = $shiftTotals->sum('total_minutes');
                    $totalHours = intdiv($totalMinutes, 60);
                @endphp
                <div class="kpi">{{ $totalHours }}ч {{ $totalMinutes % 60 }}м</div>
                <div class="text-secondary small mt-1">Смен: {{ $shiftTotals->sum('shifts_count') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Посещения по секциям</h2>
                @if($attendanceBySection->isEmpty())
                    <div class="text-secondary">Нет посещений в выбранный период.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Секция</th><th class="text-end">Посещений</th></tr>
                            </thead>
                            <tbody>
                            @foreach($attendanceBySection as $row)
                                <tr>
                                    <td>{{ $row->section?->name ?? '—' }}</td>
                                    <td class="text-end">{{ $row->total }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Оплаты по секциям</h2>
                @if($paymentsBySection->isEmpty())
                    <div class="text-secondary">Не было оплат за выбранный период.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Секция</th><th class="text-end">Сумма</th></tr>
                            </thead>
                            <tbody>
                            @foreach($paymentsBySection as $row)
                                <tr>
                                    <td>{{ $row->name }}</td>
                                    <td class="text-end">{{ number_format((float)$row->total, 2, ',', ' ') }} ₽</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Динамика посещений по дням</h2>
        @if($attendanceTimeline->isEmpty())
            <div class="text-secondary">Нет данных для отображения.</div>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Дата</th><th class="text-end">Посещений</th></tr></thead>
                    <tbody>
                    @foreach($attendanceTimeline as $row)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($row->attended_on)->format('d.m.Y') }}</td>
                            <td class="text-end">{{ $row->total }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Сводка по сменам</h2>
                @if($shiftTotals->isEmpty())
                    <div class="text-secondary">Смены в выбранный период не фиксировались.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light"><tr><th>Сотрудник</th><th class="text-end">Смен</th><th class="text-end">Часы</th></tr></thead>
                            <tbody>
                            @foreach($shiftTotals as $row)
                                @php
                                    $minutes = $row['total_minutes'];
                                    $hours = intdiv($minutes, 60);
                                @endphp
                                <tr>
                                    <td>{{ $row['user']->name }}</td>
                                    <td class="text-end">{{ $row['shifts_count'] }}</td>
                                    <td class="text-end">{{ $hours }}ч {{ $minutes % 60 }}м</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Детализация смен</h2>
                @if($shiftRecords->isEmpty())
                    <div class="text-secondary">Нет открытых смен в выбранный период.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Сотрудник</th>
                                    <th>Начало</th>
                                    <th>Плановое окончание</th>
                                    <th>Фактическое окончание</th>
                                    <th class="text-end">Длительность</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($shiftRecords as $shift)
                                <tr>
                                    <td>{{ $shift->user?->name }}</td>
                                    <td>{{ $shift->started_at->format('d.m.Y H:i') }}</td>
                                    <td>{{ optional($shift->scheduled_end_at)->format('d.m.Y H:i') ?? '—' }}</td>
                                    <td>{{ $shift->ended_at ? $shift->ended_at->format('d.m.Y H:i') : '—' }}</td>
                                    <td class="text-end">{{ $shift->calculated_duration_human }}</td>
                                    <td>
                                        @if($shift->closed_automatically)
                                            <span class="badge bg-success-subtle text-success-emphasis">Авто</span>
                                        @elseif($shift->ended_at)
                                            <span class="badge bg-primary-subtle text-primary-emphasis">Ручное</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning-emphasis">Открыта</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
