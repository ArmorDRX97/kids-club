@extends('layouts.app')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Дашборд</h1>
            <p class="text-secondary mb-0">Ключевые показатели развития клуба, динамика оплат и расписание секций.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="{{ route('reception.index') }}">Перейти на ресепшен</a>
            @role('Admin')
                <a class="btn btn-outline-secondary" href="{{ route('sections.index') }}">Все секции</a>
            @endrole
            <a class="btn btn-outline-secondary" href="{{ route('children.index') }}">Все дети</a>
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
            'pending' => 'Ожидает оплаты',
            'partial' => 'Частичная оплата',
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
                    <div class="text-success small">Активных: {{ $metrics['activeChildren'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="text-secondary small">Новые дети (месяц)</div>
                    <div class="display-6 fw-bold">{{ $metrics['newChildrenThisMonth'] ?? 0 }}</div>
                    <div class="small {{ $childGrowthDiff >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $childGrowthDiff >= 0 ? '+' : '' }}{{ $childGrowthDiff }} vs прошлый месяц
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="text-secondary small">Оплаты за месяц</div>
                    <div class="display-6 fw-bold">{{ $formatMoney($metrics['paymentsThisMonth'] ?? 0, 0) }} ₽</div>
                    <div class="small {{ $paymentsDelta >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $paymentsDelta >= 0 ? '+' : '' }}{{ $formatMoney($paymentsDelta, 0) }} ₽
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
                    <div class="text-secondary small">Средний чек</div>
                    <div class="display-6 fw-bold">{{ $formatMoney($metrics['avgPaymentThisMonth'] ?? 0, 0) }} ₽</div>
                    <div class="text-secondary small">по проведённым оплатам этого месяца</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="text-secondary small">Активные пакеты</div>
                    <div class="display-6 fw-bold">{{ $metrics['activeEnrollments'] ?? 0 }}</div>
                    <div class="text-secondary small">Ожидаемые поступления: {{ $formatMoney($metrics['outstandingBalance'] ?? 0, 0) }} ₽</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="text-secondary small">Секции и подсекции</div>
                    <div class="display-6 fw-bold">{{ $metrics['sectionsCount'] ?? 0 }}</div>
                    <div class="text-secondary small">Основные: {{ $metrics['rootSectionsCount'] ?? 0 }}, Подсекции: {{ $metrics['subSectionsCount'] ?? 0 }}</div>
                    @role('Admin')
                        <a class="btn btn-sm btn-link px-0" href="{{ route('sections.index') }}">Управлять</a>
                    @endrole
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="text-secondary small">Расписание сегодня</div>
                    <div class="display-6 fw-bold">{{ $sectionsToday['total_children'] ?? 0 }}</div>
                    <div class="text-secondary small">детей в {{ isset($sectionsToday['sections']) ? count($sectionsToday['sections']) : 0 }} секциях</div>
                    @if(!empty($sectionsToday['sections']))
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            @foreach($sectionsToday['sections'] as $section)
                                <a class="badge rounded-pill text-bg-light" href="{{ route('sections.members.index', ['section' => $section['id']]) }}">{{ $section['name'] }}</a>
                            @endforeach
                        </div>
                    @else
                        <div class="text-secondary small">Нет занятий</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="text-secondary small">Расписание завтра</div>
                    <div class="display-6 fw-bold">{{ $sectionsTomorrow['total_children'] ?? 0 }}</div>
                    <div class="text-secondary small">детей в {{ isset($sectionsTomorrow['sections']) ? count($sectionsTomorrow['sections']) : 0 }} секциях</div>
                    @if(!empty($sectionsTomorrow['sections']))
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            @foreach($sectionsTomorrow['sections'] as $section)
                                <a class="badge rounded-pill text-bg-light" href="{{ route('sections.members.index', ['section' => $section['id']]) }}">{{ $section['name'] }}</a>
                            @endforeach
                        </div>
                    @else
                        <div class="text-secondary small">Нет занятий</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Рост числа детей</h2>
                        <span class="text-secondary small">12 месяцев</span>
                    </div>
                    <canvas id="childrenGrowthChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Динамика оплат</h2>
                        <span class="text-secondary small">12 месяцев</span>
                    </div>
                    <canvas id="paymentsChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h5 mb-3">Статусы пакетов</h2>
                    <canvas id="enrollmentStatusChart" height="220"></canvas>
                    <div class="d-flex flex-wrap gap-3 mt-3">
                        @foreach($statusBreakdown['labels'] as $index => $label)
                            @php $labelText = $statusLabels[$label] ?? ucfirst($label); @endphp
                            <div class="small"><span class="legend-dot me-1" data-color-index="{{ $index }}"></span>{{ $labelText }} — {{ $statusBreakdown['values'][$index] }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Посещения по неделям</h2>
                        <span class="text-secondary small">{{ $attendanceDiff >= 0 ? '+' : '' }}{{ $attendanceDiff }} vs прошлая неделя</span>
                    </div>
                    <canvas id="attendanceChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h5 mb-3">Популярные секции</h2>
                    <ol class="list-group list-group-numbered list-group-flush">
                        @forelse($topSections as $section)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">{{ $section->name }}</div>
                                    <div class="text-secondary small">Активных детей: {{ $section->active_enrollments_count }}</div>
                                </div>
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('sections.members.index', ['section' => $section->id]) }}">Состав</a>
                            </li>
                        @empty
                            <li class="list-group-item text-secondary">Нет данных</li>
                        @endforelse
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Сроки действия пакетов</h2>
                        @role('Admin')
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('reports.index') }}">Отчёт</a>
                        @endrole
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Ребёнок</th>
                                    <th>Секция</th>
                                    <th>Истекает</th>
                                    <th class="text-end">Осталось</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expiringEnrollments as $enrollment)
                                    <tr>
                                        <td>{{ $enrollment->child->full_name ?? $enrollment->child->last_name }}</td>
                                        <td>{{ $enrollment->section->name ?? '—' }}</td>
                                        <td>{{ optional($enrollment->expires_at)->format('d.m.Y') }}</td>
                                        <td class="text-end">
                                            @if(isset($enrollment->days_left) && $enrollment->days_left >= 0)
                                                {{ $enrollment->days_left }} дн.
                                            @else
                                                <span class="text-danger">просрочено</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-secondary">Нет пакетов, истекающих в ближайшие 2 недели</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h5 mb-3">Последние оплаты</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Ребёнок</th>
                                    <th>Сумма</th>
                                    <th>Метод</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPayments as $payment)
                                    <tr>
                                        <td>{{ optional($payment->paid_at)->format('d.m.Y H:i') }}</td>
                                        <td>{{ $payment->child->full_name ?? $payment->child->last_name ?? '—' }}</td>
                                        <td>{{ $formatMoney($payment->amount ?? 0, 0) }} ₽</td>
                                        <td>{{ $payment->method ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-secondary">Ещё нет платежей</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Календарь на 2 недели</h2>
                <span class="text-secondary small">Наведи курсор на дату для подробностей</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>День недели</th>
                            <th>Секции</th>
                            <th class="text-end">Детей</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($calendarDays as $day)
                            @php
                                $sectionsForDay = collect($day['sections'] ?? []);
                                $tooltip = $sectionsForDay->isEmpty()
                                    ? 'Нет занятий'
                                    : $sectionsForDay->map(fn ($section) => e($section['name']) . ' — ' . $section['active_enrollments'] . ' детей')->implode('<br>');
                            @endphp
                            <tr>
                                <td>
                                    <span class="badge text-bg-light" data-bs-toggle="tooltip" data-bs-html="true" title="{!! $tooltip !!}">
                                        {{ $day['date']->format('d.m') }}
                                    </span>
                                </td>
                                <td>{{ $day['weekday'] }}</td>
                                <td>
                                    @if($sectionsForDay->isEmpty())
                                        <span class="text-secondary">—</span>
                                    @else
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($sectionsForDay as $section)
                                                <a class="badge text-bg-secondary" href="{{ route('sections.members.index', ['section' => $section['id']]) }}" data-bs-toggle="tooltip" title="{{ $section['active_enrollments'] }} детей">
                                                    {{ $section['name'] }}
                                                </a>
                                            @endforeach
                                        </div>
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
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        if (paymentsCtx) {
            new Chart(paymentsCtx, {
                type: 'bar',
                data: {
                    labels: paymentsData.labels,
                    datasets: [{
                        label: 'Оплаты, ₽',
                        data: paymentsData.values,
                        backgroundColor: chartPalette[2]
                    }]
                },
                options: {
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        if (attendanceCtx) {
            new Chart(attendanceCtx, {
                type: 'line',
                data: {
                    labels: attendanceData.labels,
                    datasets: [{
                        label: 'Посещения',
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
                    plugins: {
                        legend: { display: false }
                    }
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
