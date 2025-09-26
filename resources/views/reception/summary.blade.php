@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-end">
        <div>
            <h1 class="h3 mb-1">Сводка ресепшена</h1>
            <p class="text-secondary mb-0">Обзор посещений, оплат и работы ресепшионистов за выбранный период.</p>
        </div>
        <form class="ms-lg-auto w-100 w-lg-auto" method="GET" action="{{ route('reception.summary') }}">
            <div class="row g-2">
                <div class="col-6 col-lg-auto">
                    <label class="form-label small text-secondary">С</label>
                    <input type="date" name="from" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-6 col-lg-auto">
                    <label class="form-label small text-secondary">По</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control">
                </div>
                <div class="col-12 col-lg-auto d-flex align-items-end gap-2">
                    <button class="btn btn-primary" type="submit">Показать</button>
                    @if(request()->hasAny(['from','to','period']))
                        <a class="btn btn-outline-secondary" href="{{ route('reception.summary') }}">Сбросить</a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <div class="d-flex flex-wrap gap-2">
        @foreach($quickRanges as $key => $label)
            @php
                $query = array_merge(request()->query(), ['period' => $key]);
                unset($query['from'], $query['to']);
            @endphp
            <a href="{{ route('reception.summary', $query) }}" class="btn btn-sm {{ $period === $key ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 card-kpi">
                <div class="card-body">
                    <div class="text-secondary small text-uppercase">Всего посещений</div>
                    <div class="kpi mt-2">{{ $totalVisits }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 card-kpi">
                <div class="card-body">
                    <div class="text-secondary small text-uppercase">Уникальных детей</div>
                    <div class="kpi mt-2">{{ $uniqueChildren }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 card-kpi">
                <div class="card-body">
                    <div class="text-secondary small text-uppercase">Оплаты</div>
                    <div class="kpi mt-2">{{ number_format((float)$paymentsTotal, 2, ',', ' ') }} ₽</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h2 class="h5 mb-3">Секции и посещения</h2>
            @if($sectionsSummary->isEmpty())
                <p class="text-secondary mb-0">За выбранный период посещений не было.</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Секция</th>
                            <th>Посещений</th>
                            <th>Детей</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($sectionsSummary as $summary)
                            <tr>
                                <td>{{ $summary['section']?->name ?? '—' }}</td>
                                <td>{{ $summary['visits'] }}</td>
                                <td>{{ $summary['unique_children'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Приходы детей</h2>
                    @if($attendances->isEmpty())
                        <p class="text-secondary mb-0">Нет отмеченных посещений.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Время</th>
                                    <th>Ребёнок</th>
                                    <th>Секция</th>
                                    <th>Отметил</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($attendances as $attendance)
                                    <tr>
                                        <td>{{ optional($attendance->attended_at)->format('d.m.Y H:i') ?? $attendance->attended_on->format('d.m.Y') }}</td>
                                        <td>{{ $attendance->child?->full_name ?? '—' }}</td>
                                        <td>{{ $attendance->section?->name ?? '—' }}</td>
                                        <td>{{ $attendance->marker?->name ?? '—' }}</td>
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
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Принятые оплаты</h2>
                    @if($payments->isEmpty())
                        <p class="text-secondary mb-0">Оплат за период не зафиксировано.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Время</th>
                                    <th>Ребёнок</th>
                                    <th>Секция</th>
                                    <th>Сумма</th>
                                    <th>Принял</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($payments as $payment)
                                    <tr>
                                        <td>{{ optional($payment->paid_at)->format('d.m.Y H:i') }}</td>
                                        <td>{{ $payment->child?->full_name ?? '—' }}</td>
                                        <td>{{ $payment->enrollment?->section?->name ?? '—' }}</td>
                                        <td>{{ number_format((float)$payment->amount, 2, ',', ' ') }} ₽</td>
                                        <td>{{ $payment->user?->name ?? '—' }}</td>
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

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h2 class="h5 mb-3">Рабочее время ресепшионистов</h2>
            @if($shiftTotals->isEmpty())
                <p class="text-secondary mb-0">Смены в выбранный период отсутствуют.</p>
            @else
                <div class="table-responsive mb-4">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Ресепшионист</th>
                            <th>Смен</th>
                            <th>Общее время</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($shiftTotals as $total)
                            <tr>
                                <td>{{ $total['user']?->name ?? '—' }}</td>
                                <td>{{ $total['shifts_count'] }}</td>
                                <td>{{ $total['total_formatted'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <h3 class="h6 mb-3">Подробности смен</h3>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Ресепшионист</th>
                            <th>Начало</th>
                            <th>Завершение</th>
                            <th>Длительность</th>
                            <th>Закрытие</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($shiftRecords as $shift)
                            <tr>
                                <td>{{ $shift->user?->name ?? '—' }}</td>
                                <td>{{ optional($shift->started_at)->format('d.m.Y H:i') }}</td>
                                <td>
                                    @if($shift->ended_at)
                                        {{ $shift->ended_at->format('d.m.Y H:i') }}
                                    @elseif($shift->scheduled_end_at && $shift->scheduled_end_at->lessThan(now()))
                                        {{ $shift->scheduled_end_at->format('d.m.Y H:i') }} <span class="badge bg-secondary ms-1">Авто</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $shift->summary_duration_human }}</td>
                                <td>{{ $shift->closed_automatically ? 'Автоматически' : ($shift->ended_at ? 'Вручную' : 'Открыта') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
