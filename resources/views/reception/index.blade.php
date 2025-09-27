@extends('layouts.app')
@section('content')
<div class="reception-wrapper d-flex flex-column gap-4" data-reception-wrapper data-has-search="{{ $q !== '' ? 'true' : 'false' }}">
    <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-end">
        <div>
            <h1 class="h3 mb-1">Приём</h1>
            <p class="text-secondary mb-0">Контролируйте посещаемость секций и быстро находите нужных детей.</p>
        </div>
        <form class="reception-search ms-lg-auto w-100 w-lg-auto" method="GET" action="{{ route('reception.index') }}">
            <div class="reception-search__field">
                <i class="bi bi-search"></i>
                <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Имя или телефон ребёнка">
                @if($q !== '')
                    <button type="button" class="btn btn-link reception-search__clear" onclick="this.closest('form').querySelector('input[name=q]').value=''; this.closest('form').submit();">
                        <i class="bi bi-x-lg"></i>
                    </button>
                @endif
            </div>
            <button class="btn btn-primary" type="submit">Найти</button>
        </form>
    </div>

    <div class="card shadow-sm border-0 reception-shift">
        <div class="card-body p-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <h2 class="h4 mb-3">Текущее дежурство</h2>
                    @php
                        $settingStart = $shiftSetting ? substr($shiftSetting->shift_starts_at, 0, 5) : null;
                        $settingEnd = $shiftSetting ? substr($shiftSetting->shift_ends_at, 0, 5) : null;
                    @endphp
                    @if($shiftSetting)
                        <div class="text-secondary small mb-2">
                            График: {{ $settingStart }} — {{ $settingEnd }} · {{ $shiftSetting->auto_close_enabled ? 'автозакрытие включено' : 'закрытие вручную' }}
                        </div>
                    @endif
                    @if($shift)
                        <div class="d-flex flex-column gap-2">
                            <div class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Открыто с {{ $shift->started_at->format('d.m.Y H:i') }}</div>
                            <div class="small text-secondary">Плановое завершение: {{ optional($shift->scheduled_end_at)->format('d.m.Y H:i') ?? '—' }}</div>
                            <div class="small text-secondary">Время в смене: <span class="fw-semibold" data-shift-timer data-start="{{ $shift->started_at->toIso8601String() }}">{{ $shiftElapsed }}</span></div>
                            @if($shift->auto_close_enabled && $shift->scheduled_end_at)
                                <div class="small text-secondary">Смена закроется автоматически в {{ $shift->scheduled_end_at->format('H:i') }}.</div>
                            @elseif(!$shift->auto_close_enabled && $shiftStopLockedUntil && now()->lt($shiftStopLockedUntil))
                                <div class="small text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Завершение будет доступно после {{ $shiftStopLockedUntil->format('H:i') }}.</div>
                            @endif
                        </div>
                    @else
                        <p class="text-secondary mb-0">Смена ещё не начата. Нажмите «Начать смену», чтобы открыть рабочий день.</p>
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
                        <div class="text-secondary small text-end">Управление сменой доступно только сотрудникам с правами ресепшена.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(!$shiftActive && $shiftBlockReason)
        <div class="alert alert-warning mb-0">{{ $shiftBlockReason }}</div>
    @endif

    @if($q !== '' && $sectionCards->sum('total') === 0)
        <div class="alert alert-warning mb-0">Нет результатов по запросу «{{ $q }}».</div>
    @endif

    @php
        $hasSearch = $q !== '';
        $sectionsTotal = $sectionCards->count();
    @endphp

    @if($sectionCards->isNotEmpty())
        <div class="reception-tabs" data-section-tabs>
            <button type="button" class="reception-tab active" data-section-filter="all" aria-pressed="true">
                <span>Показывать все</span>
                <span class="reception-tab__badge">{{ $sectionsTotal }}</span>
            </button>
            @foreach($sectionCards as $card)
                @php $section = $card['section']; @endphp
                <button type="button" class="reception-tab" data-section-filter="{{ $section->id }}" aria-pressed="false">
                    <span>{{ $section->name }}</span>
                    <span class="reception-tab__badge">{{ $card['total'] }}</span>
                </button>
            @endforeach
        </div>
    @endif

    <div class="reception-sections">
        @forelse($sectionCards as $card)
            @php
                /** @var \App\Models\Section $section */
                $section = $card['section'];
                $enrollments = $card['enrollments'];
                $attendedToday = $card['attended_today'];
                $hasMatches = $card['total'] > 0;
                $roomLabel = $section->room?->name;
                if ($section->room?->number_label) {
                    $roomLabel .= ' ('.$section->room->number_label.')';
                }
                $cardClasses = 'reception-section card shadow-sm border-0';
                if ($hasSearch) {
                    $cardClasses .= $hasMatches ? ' reception-section--highlight' : ' reception-section--muted';
                }
            @endphp
            <article class="{{ $cardClasses }}" data-section-card data-section-id="{{ $section->id }}" data-has-matches="{{ $hasMatches ? 'true' : 'false' }}" id="sec{{ $section->id }}">
                <div class="reception-section__header">
                    <div class="reception-section__title">
                        <h2 class="reception-section__name">{{ $section->name }}</h2>
                        @if($section->parent)
                            <div class="reception-section__parent"><i class="bi bi-diagram-3 me-1"></i>Входит в направление: {{ $section->parent->name }}</div>
                        @endif
                        <div class="reception-section__meta">
                            <span><i class="bi bi-geo-alt me-1"></i>{{ $section->room ? $roomLabel : 'Локация не назначена' }}</span>
                            <span><i class="bi bi-clock-history me-1"></i>Расписание: {{ $section->schedule_type === 'weekly' ? 'по неделям' : 'по датам' }}</span>
                        </div>
                    </div>
                    <div class="reception-section__stats">
                        <span class="badge text-bg-primary-subtle text-primary-emphasis">Детей в секции: {{ $card['total'] }}</span>
                    </div>
                </div>

                <div class="reception-section__body">
                    @forelse($enrollments as $enrollment)
                        @php
                            $child = $enrollment->child;
                            if(!$child) {
                                continue;
                            }
                            $already = in_array($child->id, $attendedToday, true);
                            $package = $enrollment->package;
                            $status = $enrollment->status ?? 'pending';
                            $price = $enrollment->price ?? ($package->price ?? null);
                            $paid = (float)($enrollment->total_paid ?? 0);
                            $needsPayment = ($price !== null && (float)$price > 0) ? $paid + 0.0001 < (float)$price : false;
                            $statusLabels = [
                                'paid' => 'Оплачено',
                                'partial' => 'Частично оплачено',
                                'pending' => 'Требуется оплата',
                                'expired' => 'Пакет истёк',
                            ];
                            $statusClasses = [
                                'paid' => 'badge bg-success-subtle text-success-emphasis',
                                'partial' => 'badge bg-warning-subtle text-warning-emphasis',
                                'pending' => 'badge bg-danger-subtle text-danger-emphasis',
                                'expired' => 'badge bg-secondary-subtle text-secondary-emphasis',
                            ];
                            $statusLabel = $statusLabels[$status] ?? 'Статус не определён';
                            $statusClass = $statusClasses[$status] ?? 'badge bg-secondary';
                            $modalId = 'payment-modal-'.$enrollment->id;
                            $inputId = 'payment-amount-'.$enrollment->id;
                            $markDisabled = false;
                            $markButtonLabel = 'Отметить приход';
                            $markHelper = null;
                            if($already) {
                                $markDisabled = true;
                                $markButtonLabel = 'Уже отмечен сегодня';
                            } elseif(!$shiftActive) {
                                $markDisabled = true;
                                $markButtonLabel = 'Смена ещё не открыта';
                                $markHelper = 'Откройте смену, чтобы отмечать посещения и платежи.';
                            } elseif($needsPayment) {
                                $markDisabled = true;
                                $markButtonLabel = 'Записать оплату';
                                $markHelper = 'По данному абонементу осталось внести оплату перед отметкой посещения.';
                            } elseif($status === 'expired') {
                                $markDisabled = true;
                                $markButtonLabel = 'Пакет истёк';
                                $markHelper = 'Продлите или смените пакет, чтобы продолжить отмечать посещения.';
                            }
                            if(!$canManage) {
                                $markDisabled = true;
                                $markButtonLabel = 'Только для ресепшена';
                                $markHelper = 'Отметить посещение могут только сотрудники ресепшена.';
                            }
                        @endphp
                        <div class="reception-visit">
                            <div class="reception-visit__info">
                                <div class="reception-visit__title">
                                    <span class="reception-visit__name">{{ $child->full_name }}</span>
                                    <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                                    @if($status === 'pending')
                                        <span class="badge bg-danger-subtle text-danger-emphasis">Оплата ожидается</span>
                                    @endif
                                </div>
                                <div class="reception-visit__meta">
                                    <span>Пакет: {{ $package?->name ?? '—' }}
                                        @if($package?->billing_type === 'visits' && $package?->visits_count)
                                            · на {{ $package->visits_count }} посещений
                                        @elseif($package?->billing_type === 'period' && $package?->days)
                                            · на {{ $package->days }} дней
                                        @endif
                                    </span>
                                    @if(!is_null($enrollment->visits_left))
                                        <span>Осталось посещений: {{ $enrollment->visits_left }}</span>
                                    @else
                                        <span>Действует до: {{ optional($enrollment->expires_at)->format('d.m.Y') ?? '—' }}</span>
                                    @endif
                                </div>
                                <div class="reception-visit__meta">
                                    <span>Оплачено: {{ number_format((float)($enrollment->total_paid ?? 0), 2, ',', ' ') }} ₽</span>
                                    @if($price)
                                        <span>К оплате сейчас: {{ number_format((float)$price, 2, ',', ' ') }} ₽</span>
                                    @endif
                                </div>
                            </div>
                            <div class="reception-visit__actions">
                                @if($canManage)
                                    <form class="reception-visit__form" method="POST" action="{{ route('reception.mark') }}" data-attendance-form>
                                        @csrf
                                        <input type="hidden" name="child_id" value="{{ $child->id }}">
                                        <input type="hidden" name="section_id" value="{{ $section->id }}">
                                        <button class="btn btn-primary btn-sm" type="submit" {{ $markDisabled ? 'disabled' : '' }}>
                                            {{ $markButtonLabel }}
                                        </button>
                                    </form>
                                    @if($needsPayment)
                                        <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                            Принять оплату
                                        </button>
                                    @endif
                                @endif
                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('sections.members.index', $section) }}">Участники</a>
                            </div>
                        </div>
                        @if($markHelper)
                            <div class="reception-visit__helper text-danger small">{{ $markHelper }}</div>
                        @endif

                        @if($needsPayment && $canManage)
                            <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Принять оплату</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
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
                                                        <input type="number" class="form-control" step="0.01" min="0" name="amount" id="{{ $inputId }}" placeholder="Сумма в рублях" required>
                                                        <button class="btn btn-outline-primary" type="button" data-fill-amount data-target="{{ $inputId }}" data-amount="{{ $price ?? 0 }}">Внести полную сумму</button>
                                                    </div>
                                                    @if($price)
                                                        <div class="form-text">Рекомендуемая сумма: {{ number_format((float)$price, 2, ',', ' ') }} ₽</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отменить</button>
                                                <button type="submit" class="btn btn-primary">Сохранить оплату</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="reception-section__empty">{{ $hasSearch ? 'Ничего не найдено' : 'Нет прикреплённых детей на сегодня.' }}</div>
                    @endforelse
                </div>

                @if($card['total'] > $card['per_page'])
                    @php
                        $pages = (int) ceil($card['total'] / $card['per_page']);
                        $currentQuery = request()->query();
                    @endphp
                    <div class="reception-section__footer">
                        @for($i = 1; $i <= $pages; $i++)
                            @php
                                $query = array_merge($currentQuery, ['p_'.$section->id => $i]);
                                $url = route('reception.index', $query) . '#sec' . $section->id;
                            @endphp
                            <a class="btn btn-sm {{ $i === $card['page'] ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ $url }}">{{ $i }}</a>
                        @endfor
                    </div>
                @endif
            </article>
        @empty
            <div class="alert alert-info mb-0">На сегодня нет активных секций.</div>
        @endforelse
    </div>
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

        const tabs = document.querySelectorAll('[data-section-filter]');
        const cards = document.querySelectorAll('[data-section-card]');
        const activateTab = (target) => {
            tabs.forEach(btn => {
                const filter = btn.dataset.sectionFilter;
                const isActive = target === 'all' ? filter === 'all' : filter === target;
                btn.classList.toggle('active', isActive);
                btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
            cards.forEach(card => {
                const visible = target === 'all' || card.dataset.sectionId === target;
                card.classList.toggle('d-none', !visible);
            });
        };
        if (tabs.length && cards.length) {
            tabs.forEach(button => {
                button.addEventListener('click', event => {
                    event.preventDefault();
                    activateTab(button.dataset.sectionFilter);
                });
            });
            activateTab('all');
            if (window.location.hash.startsWith('#sec')) {
                const sectionId = window.location.hash.replace('#sec', '');
                const targetTab = document.querySelector(`[data-section-filter="${sectionId}"]`);
                if (targetTab) {
                    activateTab(sectionId);
                }
            }
        }
    });
</script>
@endpush

