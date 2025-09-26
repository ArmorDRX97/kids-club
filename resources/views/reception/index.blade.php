@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-end">
        <div>
            <h1 class="h3 mb-1">Ресепшен</h1>
            <p class="text-secondary mb-0">Управляйте сменой и отмечайте детей, которые пришли на занятия сегодня.</p>
        </div>
        <form class="ms-lg-auto w-100 w-lg-auto" method="GET" action="{{ route('reception.index') }}">
            <div class="input-group">
                <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Поиск ребёнка или телефона">
                <button class="btn btn-primary" type="submit">Поиск</button>
                @if($q !== '')
                    <a class="btn btn-outline-secondary" href="{{ route('reception.index') }}">Сбросить</a>
                @endif
            </div>
        </form>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <h2 class="h4 mb-3">Управление сменой</h2>
                    @php
                        $settingStart = $shiftSetting ? substr($shiftSetting->shift_starts_at, 0, 5) : null;
                        $settingEnd = $shiftSetting ? substr($shiftSetting->shift_ends_at, 0, 5) : null;
                    @endphp
                    @if($shiftSetting)
                        <div class="text-secondary small mb-2">
                            График: {{ $settingStart }} — {{ $settingEnd }} ·
                            {{ $shiftSetting->auto_close_enabled ? 'автозакрытие включено' : 'ручное завершение' }}
                        </div>
                    @endif
                    @if($shift)
                        <div class="d-flex flex-column gap-2">
                            <div class="text-success fw-semibold">Смена активна с {{ $shift->started_at->format('d.m.Y H:i') }}</div>
                            <div class="small text-secondary">Запланированное завершение: {{ optional($shift->scheduled_end_at)->format('d.m.Y H:i') ?? '—' }}</div>
                            <div class="small text-secondary">Прошло времени: <span class="fw-semibold" data-shift-timer data-start="{{ $shift->started_at->toIso8601String() }}">{{ $shiftElapsed }}</span></div>
                            @if($shift->auto_close_enabled && $shift->scheduled_end_at)
                                <div class="small text-secondary">Смена завершится автоматически в {{ $shift->scheduled_end_at->format('H:i') }}.</div>
                            @elseif(!$shift->auto_close_enabled && $shiftStopLockedUntil && now()->lt($shiftStopLockedUntil))
                                <div class="small text-warning">Завершить смену можно после {{ $shiftStopLockedUntil->format('H:i') }}.</div>
                            @endif
                        </div>
                    @else
                        <p class="text-secondary mb-0">Смена ещё не начата. Нажмите «Начать смену», чтобы зафиксировать старт рабочего дня.</p>
                    @endif
                </div>
                <div class="col-lg-5">
                    @if($canManage)
                        <div class="d-flex flex-column flex-sm-row justify-content-end gap-3">
                            <form method="POST" action="{{ route('shift.start') }}" class="flex-fill">
                                @csrf
                                <button type="submit" class="btn btn-success btn-lg w-100" {{ $shift ? 'disabled' : '' }}>Начать смену</button>
                            </form>
                            <form method="POST" action="{{ route('shift.stop') }}" class="flex-fill">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-lg w-100" {{ ($shift && $shiftCanStop) ? '' : 'disabled' }}>Завершить смену</button>
                            </form>
                        </div>
                        @if($shift && !$shiftCanStop && $shiftStopLockedUntil)
                            <div class="small text-warning text-end mt-2">Доступно после {{ $shiftStopLockedUntil->format('H:i') }}.</div>
                        @endif
                    @else
                        <div class="text-secondary small text-end">
                            Управление сменой доступно только ресепшионистам.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(!$shiftActive && $shiftBlockReason)
        <div class="alert alert-warning mb-0">{{ $shiftBlockReason }}</div>
    @endif

    @if($q !== '' && $sectionCards->sum('total') === 0)
        <div class="alert alert-warning mb-0">По запросу «{{ $q }}» ничего не найдено.</div>
    @endif

    @forelse($sectionCards as $card)
        @php
            /** @var \App\Models\Section $section */
            $section = $card['section'];
            $enrollments = $card['enrollments'];
            $attendedToday = $card['attended_today'];
        @endphp
        <div class="card shadow-sm border-0" id="sec{{ $section->id }}">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-start">
                    <div>
                        <div class="h5 mb-1">{{ $section->name }} @if($section->parent) <span class="text-secondary">→ {{ $section->parent->name }}</span> @endif</div>
                        @php
                            $roomLabel = $section->room?->name;
                            if ($section->room?->number_label) {
                                $roomLabel .= ' ('.$section->room->number_label.')';
                            }
                        @endphp
                        <div class="small text-secondary">Комната: {{ $section->room ? $roomLabel : '—' }}</div>
                        <div class="small text-secondary">Тип расписания: {{ $section->schedule_type==='weekly' ? 'по дням недели' : 'по дням месяца' }}</div>
                    </div>
                    <div class="text-secondary small">Всего детей сегодня: {{ $card['total'] }}</div>
                </div>

                <div class="mt-4">
                    @forelse($enrollments as $enrollment)
                        @php
                            $child = $enrollment->child;
                            if(!$child) { continue; }
                            $already = in_array($child->id, $attendedToday, true);
                            $package = $enrollment->package;
                            $status = $enrollment->status ?? 'pending';
                            $price = $enrollment->price ?? ($package->price ?? null);
                            $paid = (float)($enrollment->total_paid ?? 0);
                            $needsPayment = ($price !== null && (float)$price > 0) ? $paid + 0.0001 < (float)$price : false;
                            $statusLabels = [
                                'paid' => 'Оплачено',
                                'partial' => 'Оплачено частично',
                                'pending' => 'Не оплачено',
                                'expired' => 'Срок истёк',
                            ];
                            $statusClasses = [
                                'paid' => 'badge bg-success-subtle text-success-emphasis',
                                'partial' => 'badge bg-warning-subtle text-warning-emphasis',
                                'pending' => 'badge bg-danger-subtle text-danger-emphasis',
                                'expired' => 'badge bg-secondary-subtle text-secondary-emphasis',
                            ];
                            $statusLabel = $statusLabels[$status] ?? 'Статус неизвестен';
                            $statusClass = $statusClasses[$status] ?? 'badge bg-secondary';
                            $modalId = 'payment-modal-'.$enrollment->id;
                            $inputId = 'payment-amount-'.$enrollment->id;
                            $markDisabled = false;
                            $markButtonLabel = 'Пришёл';
                            $markHelper = null;
                            if($already){
                                $markDisabled = true;
                                $markButtonLabel = 'Уже отмечен';
                            } elseif(!$shiftActive) {
                                $markDisabled = true;
                                $markButtonLabel = 'Смена не начата';
                                $markHelper = 'Начните смену, чтобы отмечать посещения.';
                            } elseif($needsPayment) {
                                $markDisabled = true;
                                $markButtonLabel = 'Нужна оплата';
                                $markHelper = 'Необходимо принять оплату, прежде чем отмечать посещение.';
                            } elseif($status === 'expired') {
                                $markDisabled = true;
                                $markButtonLabel = 'Пакет истёк';
                                $markHelper = 'Продлите пакет, чтобы отметить посещение.';
                            }
                            if(!$canManage) {
                                $markDisabled = true;
                                $markButtonLabel = 'Недоступно';
                                $markHelper = 'Рабочие действия доступны только ресепшионистам.';
                            }
                        @endphp
                        <div class="border rounded-3 p-3 mb-3 bg-light">
                            <div class="row align-items-center g-3">
                                <div class="col-md">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <div class="fw-semibold">{{ $child->full_name }}</div>
                                        <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                                        @if($status === 'pending')
                                            <span class="small text-danger">Не оплачено</span>
                                        @endif
                                    </div>
                                    <div class="text-secondary small">
                                        Пакет: {{ $package?->name ?? '—' }}
                                        @if($package?->billing_type === 'visits' && $package?->visits_count)
                                            · {{ $package->visits_count }} занятий
                                        @elseif($package?->billing_type === 'period' && $package?->days)
                                            · {{ $package->days }} дн.
                                        @endif
                                        @if(!is_null($enrollment->visits_left))
                                            — осталось {{ $enrollment->visits_left }}
                                        @else
                                            — до {{ optional($enrollment->expires_at)->format('d.m.Y') ?? '∞' }}
                                        @endif
                                    </div>
                                    <div class="text-secondary small mt-1">
                                        Оплачено: {{ number_format((float)($enrollment->total_paid ?? 0), 2, ',', ' ') }} ₽
                                        @if($price)
                                            из {{ number_format((float)$price, 2, ',', ' ') }} ₽
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-auto d-flex flex-wrap gap-2">
                                    @if($canManage)
                                        <form class="d-inline" method="POST" action="{{ route('reception.mark') }}" data-attendance-form>
                                            @csrf
                                            <input type="hidden" name="child_id" value="{{ $child->id }}">
                                            <input type="hidden" name="section_id" value="{{ $section->id }}">
                                            <button class="btn btn-primary" type="submit" {{ $markDisabled ? 'disabled' : '' }}>
                                                {{ $markButtonLabel }}
                                            </button>
                                        </form>
                                        @if($needsPayment)
                                            <button class="btn btn-outline-warning" type="button" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}" {{ $shiftActive ? '' : 'disabled' }}>Оплатить</button>
                                        @endif
                                    @else
                                        <button class="btn btn-primary" type="button" disabled>
                                            {{ $markButtonLabel }}
                                        </button>
                                        @if($needsPayment)
                                            <button class="btn btn-outline-warning" type="button" disabled>Оплатить</button>
                                        @endif
                                    @endif
                                </div>
                                @if($markHelper)
                                    <div class="small text-danger mt-2">{{ $markHelper }}</div>
                                @endif
                            </div>
                        </div>

                        @if($needsPayment && $canManage)
                            <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Оплата занятия</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST" action="{{ route('payments.store') }}">
                                            @csrf
                                            <input type="hidden" name="enrollment_id" value="{{ $enrollment->id }}">
                                            <div class="modal-body">
                                                <dl class="row mb-4">
                                                    <dt class="col-sm-4">Ребёнок</dt>
                                                    <dd class="col-sm-8 mb-2">{{ $child->full_name }}</dd>
                                                    <dt class="col-sm-4">Секция</dt>
                                                    <dd class="col-sm-8 mb-2">{{ $section->name }}</dd>
                                                    <dt class="col-sm-4">Пакет</dt>
                                                    <dd class="col-sm-8 mb-0">{{ $package?->name ?? '—' }}</dd>
                                                </dl>
                                                <div class="mb-3">
                                                    <label for="{{ $inputId }}" class="form-label">Сумма оплаты</label>
                                                    <div class="input-group">
                                                        <input type="number" class="form-control" step="0.01" min="0" name="amount" id="{{ $inputId }}" placeholder="0.00" required>
                                                        <button class="btn btn-outline-primary" type="button" data-fill-amount data-target="{{ $inputId }}" data-amount="{{ $price ?? 0 }}">Оплатить</button>
                                                    </div>
                                                    @if($price)
                                                        <div class="form-text">Стоимость пакета: {{ number_format((float)$price, 2, ',', ' ') }} ₽</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                                                <button type="submit" class="btn btn-primary">Сохранить</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="text-center text-secondary py-4">Нет прикреплённых детей на сегодня.</div>
                    @endforelse

                    @if($card['total'] > $card['per_page'])
                        @php
                            $pages = (int) ceil($card['total'] / $card['per_page']);
                            $currentQuery = request()->query();
                        @endphp
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            @for($i = 1; $i <= $pages; $i++)
                                @php
                                    $query = array_merge($currentQuery, ['p_'.$section->id => $i]);
                                    $url = route('reception.index', $query) . '#sec' . $section->id;
                                @endphp
                                <a class="btn btn-sm {{ $i === $card['page'] ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ $url }}">{{ $i }}</a>
                            @endfor
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-info mb-0">На сегодня нет активных секций.</div>
    @endforelse
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const timerEl = document.querySelector('[data-shift-timer]');
        if (timerEl) {
            const start = timerEl.getAttribute('data-start');
            if (start) {
                const startDate = new Date(start);
                const updateTimer = () => {
                    const now = new Date();
                    let diff = Math.max(0, Math.floor((now.getTime() - startDate.getTime()) / 1000));
                    const hours = Math.floor(diff / 3600);
                    diff -= hours * 3600;
                    const minutes = Math.floor(diff / 60);
                    const seconds = diff - minutes * 60;
                    timerEl.textContent = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                };
                updateTimer();
                setInterval(updateTimer, 1000);
            }
        }

        document.querySelectorAll('[data-fill-amount]').forEach(button => {
            button.addEventListener('click', function () {
                const targetId = this.getAttribute('data-target');
                const amount = this.getAttribute('data-amount');
                const input = document.getElementById(targetId);
                if (input) {
                    input.value = amount || '';
                    input.focus();
                }
            });
        });

        document.querySelectorAll('form[data-attendance-form]').forEach(form => {
            const submitButton = form.querySelector('button[type="submit"]');
            form.addEventListener('submit', function () {
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.dataset.originalText = submitButton.textContent;
                    submitButton.textContent = 'Отмечаем...';
                }
            });
        });
    });
</script>
@endpush
