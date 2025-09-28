
@extends('layouts.app')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Панель управления</h1>
            <p class="text-secondary mb-0">Следите за динамикой посещений, оплат и загрузкой секций в режиме реального времени.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="{{ route('reception.index') }}">Перейти на ресепшен</a>
            @role('Admin')
                <a class="btn btn-outline-secondary" href="{{ route('sections.index') }}">Управление секциями</a>
            @endrole
            <a class="btn btn-outline-secondary" href="{{ route('children.index') }}">Список детей</a>
        </div>
    </div>

    @php
        $metrics = $metrics ?? [];
        $childGrowthDiff = ($metrics['newChildrenThisMonth'] ?? 0) - ($metrics['newChildrenLastMonth'] ?? 0);
        $paymentsDelta = ($metrics['paymentsThisMonth'] ?? 0) - ($metrics['paymentsLastMonth'] ?? 0);
        $paymentsPercent = ($metrics['paymentsLastMonth'] ?? 0) > 0
            ? round($paymentsDelta / max($metrics['paymentsLastMonth'], 1) * 100, 1)
            : null;
        $attendanceDiff = ($metrics['attendanceThisWeek'] ?? 0) - ($metrics['attendanceLastWeek'] ?? 0);
        $formatMoney = fn ($value, $decimals = 0) => number_format((float) $value, $decimals, ',', ' ');
        $statusLabels = [
            'pending' => 'Требуется оплата',
            'partial' => 'Оплачено частично',
            'paid' => 'Оплачено',
            'expired' => 'Истёк срок',
        ];
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="text-secondary small">Всего детей</div>
                    <div class="display-6 fw-bold">{{ $metrics['totalChildren'] ?? 0 }}</div>
                    <div class="text-success small">Активны: {{ $metrics['activeChildren'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="text-secondary small">Новые дети за месяц</div>
                    <div class="display-6 fw-bold">{{ $metrics['newChildrenThisMonth'] ?? 0 }}</div>
                    <div class="small {{ $childGrowthDiff >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $childGrowthDiff >= 0 ? '+' : '' }}{{ $childGrowthDiff }} к прошлому месяцу
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="text-secondary small">Платежи за месяц</div>
                    <div class="display-6 fw-bold">{{ $formatMoney($metrics['paymentsThisMonth'] ?? 0, 0) }} ₸</div>
                    <div class="small {{ $paymentsDelta >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $paymentsDelta >= 0 ? '+' : '' }}{{ $formatMoney($paymentsDelta, 0) }} ₸
                        @if(!is_null($paymentsPercent))
                            ({{ $paymentsPercent >= 0 ? '+' : '' }}{{ $paymentsPercent }}%)
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="text-secondary small">Средний платёж</div>
                    <div class="display-6 fw-bold">{{ $formatMoney($metrics['avgPaymentThisMonth'] ?? 0, 0) }} ₸</div>
                    <div class="text-secondary small">По всем платежам текущего месяца</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="text-secondary small">Активные секции</div>
                    <div class="display-6 fw-bold">{{ $metrics['activeSections'] ?? 0 }}</div>
                    <div class="text-secondary small">Направлений: {{ $metrics['directionsCount'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="text-secondary small">Активные прикрепления</div>
                    <div class="display-6 fw-bold">{{ $metrics['activeEnrollments'] ?? 0 }}</div>
                    <div class="text-secondary small">Всего секций: {{ $metrics['sectionsCount'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="text-secondary small">Посещений (неделя)</div>
                    <div class="display-6 fw-bold">{{ $metrics['attendanceThisWeek'] ?? 0 }}</div>
                    <div class="small {{ $attendanceDiff >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $attendanceDiff >= 0 ? '+' : '' }}{{ $attendanceDiff }} к прошлой неделе
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="text-secondary small">Долг по оплатам</div>
                    <div class="display-6 fw-bold">{{ $formatMoney($metrics['outstandingBalance'] ?? 0, 0) }} ₸</div>
                    <div class="text-secondary small">Сумма по статусам «требуется оплата» и «частично»</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-6 col-xl-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h6 mb-3">Новые дети по месяцам</h2>
                    <canvas id="childrenGrowthChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6 col-xl-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h6 mb-3">Платежи по месяцам</h2>
                    <canvas id="paymentsChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h6 mb-3">Посещения по неделям</h2>
                    <canvas id="attendanceChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h6 mb-3">Статусы прикреплений</h2>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="legend-dot" data-color-index="0"></span>
                        <span class="text-secondary small">Требует оплаты</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="legend-dot" data-color-index="1"></span>
                        <span class="text-secondary small">Частичная оплата</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="legend-dot" data-color-index="2"></span>
                        <span class="text-secondary small">Оплачено</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="legend-dot" data-color-index="3"></span>
                        <span class="text-secondary small">Истёк срок</span>
                    </div>
                    <canvas id="enrollmentStatusChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h6 mb-3">Ближайшие окончания пакетов</h2>
                    @if($expiringEnrollments->isEmpty())
                        <p class="text-secondary mb-0">В ближайшие две недели пакеты не заканчиваются.</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach($expiringEnrollments as $enrollment)
                                <li class="mb-2">
                                    <div class="fw-semibold">{{ $enrollment->child?->full_name ?? '—' }}</div>
                                    <div class="text-secondary small">{{ $enrollment->section?->name ?? '—' }} · истекает {{ $enrollment->expires_at?->format('d.m.Y') ?? '—' }} ({{ $enrollment->days_left }} дн.)</div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h6 mb-3">Последние платежи</h2>
                    @if($recentPayments->isEmpty())
                        <p class="text-secondary mb-0">Платежей пока нет.</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach($recentPayments as $payment)
                                <li class="mb-2">
                                    <div class="fw-semibold">{{ $payment->child?->full_name ?? '—' }}</div>
                                    <div class="text-secondary small">{{ number_format($payment->amount, 2, ',', ' ') }} ₸ · {{ $payment->paid_at?->format('d.m.Y H:i') ?? '—' }} · {{ $payment->method ?? '—' }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h6 mb-3">Секции сегодня и завтра</h2>
                    @php
                        $today = $sectionsToday;
                        $tomorrow = $sectionsTomorrow;
                    @endphp
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold mb-2">Сегодня · {{ $today['weekday'] ?? '' }}</div>
                                @if(!empty($today['sections']))
                                    <ul class="list-unstyled mb-0 small">
                                        @foreach($today['sections'] as $entry)
                                            <li class="mb-1">{{ $entry['time'] }} — {{ $entry['name'] }} ({{ $entry['active_enrollments'] }} детей)</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-secondary small mb-0">Занятий нет.</p>
                                @endif
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold mb-2">Завтра · {{ $tomorrow['weekday'] ?? '' }}</div>
                                @if($tomorrow && !empty($tomorrow['sections']))
                                    <ul class="list-unstyled mb-0 small">
                                        @foreach($tomorrow['sections'] as $entry)
                                            <li class="mb-1">{{ $entry['time'] }} — {{ $entry['name'] }} ({{ $entry['active_enrollments'] }} детей)</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-secondary small mb-0">Занятий нет.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h6 mb-3">Календарь на две недели</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Дата</th>
                                <th>Секции</th>
                                <th class="text-end">Детей</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($calendarDays as $day)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $day['date']->format('d.m') }}</div>
                                        <div class="text-secondary small">{{ $day['weekday'] }}</div>
                                    </td>
                                    <td>
                                        @if($day['sections']->isEmpty())
                                            <span class="text-secondary small">—</span>
                                        @else
                                            <ul class="list-unstyled mb-0 small">
                                                @foreach($day['sections'] as $entry)
                                                    <li>{{ $entry['time'] }} — {{ $entry['name'] }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold">{{ $day['total_children'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
    <script>
        const chartPalette = ['#4f46e5', '#22c55e', '#f97316', '#14b8a6', '#a855f7', '#ef4444'];

        const childrenGrowthCtx = document.getElementById('childrenGrowthChart');
        const paymentsCtx = document.getElementById('paymentsChart');
        const attendanceCtx = document.getElementById('attendanceChart');
        const statusCtx = document.getElementById('enrollmentStatusChart');

        const childrenGrowthData = @json($childrenGrowthChart, JSON_UNESCAPED_UNICODE);
        const paymentsData = @json($paymentsChart, JSON_UNESCAPED_UNICODE);
        const attendanceData = @json($attendanceChart, JSON_UNESCAPED_UNICODE);
        const statusData = @json($statusBreakdown, JSON_UNESCAPED_UNICODE);

        if (childrenGrowthCtx) {
            new Chart(childrenGrowthCtx, {
                type: 'line',
                data: {
                    labels: childrenGrowthData.labels,
                    datasets: [{
                        label: 'Новых детей',
                        data: childrenGrowthData.values,
                        borderColor: chartPalette[0],
                        backgroundColor: chartPalette[0] + '33',
                        fill: true,
                        tension: 0.3,
                    }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        if (paymentsCtx) {
            new Chart(paymentsCtx, {
                type: 'bar',
                data: {
                    labels: paymentsData.labels,
                    datasets: [{
                        label: 'Платежи, ₸',
                        data: paymentsData.values,
                        backgroundColor: chartPalette[2]
                    }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        if (attendanceCtx) {
            new Chart(attendanceCtx, {
                type: 'line',
                data: {
                    labels: attendanceData.labels,
                    datasets: [{
                        label: 'Посещений',
                        data: attendanceData.values,
                        borderColor: chartPalette[1],
                        backgroundColor: chartPalette[1] + '33',
                        fill: true,
                        tension: 0.3,
                    }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: statusData.labels,
                    datasets: [{
                        data: statusData.values,
                        backgroundColor: statusData.labels.map((_, index) => chartPalette[index % chartPalette.length])
                    }]
                },
                options: {
                    plugins: { legend: { display: false } }
                }
            });
        }

        document.querySelectorAll('.legend-dot').forEach((element) => {
            const index = parseInt(element.getAttribute('data-color-index'), 10) || 0;
            element.style.display = 'inline-block';
            element.style.width = '0.75rem';
            element.style.height = '0.75rem';
            element.style.borderRadius = '999px';
            element.style.backgroundColor = chartPalette[index % chartPalette.length];
        });

        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((tooltipTriggerEl) => {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
@endpush
