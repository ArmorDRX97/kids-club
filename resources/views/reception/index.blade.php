
@extends('layouts.app')

@section('content')
<div class="reception-wrapper d-flex flex-column gap-4" data-reception-wrapper data-has-search="{{ $hasSearch ? 'true' : 'false' }}">
    <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-end">
        <div>
            <h1 class="h3 mb-1">Ресепшен</h1>
            <p class="text-secondary mb-0">Отмечайте посещения и принимайте оплату строго в рамках активного временного слота.</p>
        </div>
        <form class="reception-search ms-lg-auto w-100 w-lg-auto" method="GET" action="{{ route('reception.index') }}">
            <div class="reception-search__field">
                <i class="bi bi-search"></i>
                <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Имя ребёнка или телефон">
                @if($hasSearch)
                    <button type="button" class="btn btn-link reception-search__clear"
                            onclick="this.closest('form').querySelector('input[name=q]').value=''; this.closest('form').submit();">
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
                    <h2 class="h4 mb-3">Текущая смена</h2>
                    @php
                        $settingStart = $shiftSetting ? substr($shiftSetting->shift_starts_at, 0, 5) : null;
                        $settingEnd = $shiftSetting ? substr($shiftSetting->shift_ends_at, 0, 5) : null;
                    @endphp
                    @if($shiftSetting)
                        <div class="text-secondary small mb-2">
                            График: {{ $settingStart }} – {{ $settingEnd }} ({{ $shiftSetting->auto_close_enabled ? 'автозакрытие включено' : 'автозакрытие отключено' }})
                        </div>
                    @endif
                    @if($shift)
                        <div class="d-flex flex-column gap-2">
                            <div class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Смена открыта с {{ $shift->started_at->format('d.m.Y H:i') }}</div>
                            <div class="small text-secondary">Запланировано завершение: {{ optional($shift->scheduled_end_at)->format('d.m.Y H:i') ?? '—' }}</div>
                            <div class="small text-secondary">Рабочее время: <span class="fw-semibold" data-shift-timer data-start="{{ $shift->started_at->toIso8601String() }}">{{ $shiftElapsed }}</span></div>
                            @if($shift->auto_close_enabled && $shift->scheduled_end_at)
                                <div class="small text-secondary">Смена закроется автоматически в {{ $shift->scheduled_end_at->format('H:i') }}.</div>
                            @elseif(!$shift->auto_close_enabled && $shiftStopLockedUntil && now()->lt($shiftStopLockedUntil))
                                <div class="small text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Закрытие будет доступно после {{ $shiftStopLockedUntil->format('H:i') }}.</div>
                            @endif
                        </div>
                    @else
                        <p class="text-secondary mb-0">Смена не открыта. Начните смену, чтобы отмечать посещения и принимать платежи.</p>
                    @endif
                </div>
                <div class="col-lg-5">
                    @if($canManage)
                        <div class="d-flex flex-column flex-sm-row justify-content-end gap-3">
                            <form method="POST" action="{{ route('shift.start') }}" class="flex-fill">
                                @csrf
                                <button type="submit" class="btn btn-success btn-lg w-100" {{ $shift ? 'disabled' : '' }}>Открыть смену</button>
                            </form>
                            <form method="POST" action="{{ route('shift.stop') }}" class="flex-fill">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-lg w-100" {{ ($shift && $shiftCanStop) ? '' : 'disabled' }}>Закрыть смену</button>
                            </form>
                        </div>
                        @if($shift && !$shiftCanStop && $shiftStopLockedUntil)
                            <div class="small text-warning text-end mt-2">Остановка будет доступна после {{ $shiftStopLockedUntil->format('H:i') }}.</div>
                        @endif
                    @else
                        <div class="text-secondary small text-end">Управление сменой доступно только ресепшионистам.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(!$shiftActive && $shiftBlockReason)
        <div class="alert alert-warning mb-0">{{ $shiftBlockReason }}</div>
    @endif

    @if($hasSearch)
        @php
            $totalMatches = $directionGroups->flatten(1)->filter(fn ($card) => $card['has_matches'])->count();
        @endphp
        @if($totalMatches === 0)
            <div class="alert alert-warning mb-0">Совпадений не найдено.</div>
        @endif
    @endif

    @foreach($directionGroups as $directionName => $cards)
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">{{ $directionName }}</h2>
            </div>
            <div class="card-body">
                @forelse($cards as $card)
                    @php
                        $section = $card['section'];
                        $highlightClass = $hasSearch ? ($card['has_matches'] ? 'reception-section--highlight' : 'reception-section--muted') : '';
                    @endphp
                    <article class="reception-section card border-0 shadow-sm mb-4 {{ $highlightClass }}" id="section-{{ $section->id }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                                <div>
                                    <h3 class="h5 mb-1">{{ $section->name }}</h3>
                                    <div class="text-secondary small">Помещение: {{ $card['room'] ?? 'не назначено' }}</div>
                                    <div class="text-secondary small mt-2">
                                        Сегодня: {{ $card['today_slots']->isEmpty() ? 'занятий нет' : $card['today_slots']->implode('; ') }}
                                    </div>
                                    <div class="text-secondary small">Полное расписание: {{ $card['full_schedule'] ?: 'не задано' }}</div>
                                </div>
                                <div class="text-end">
                                    @if($card['active_slot'])
                                        <span class="badge bg-success-subtle text-success-emphasis">Сейчас занятие ({{ $card['active_slot'] }})</span>
                                    @elseif($card['next_slot'])
                                        <span class="badge bg-warning-subtle text-warning-emphasis">Следующее занятие: {{ $card['next_slot'] }}</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis">Сегодня без занятий</span>
                                    @endif
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Ребёнок</th>
                                        <th>Пакет</th>
                                        <th>Время</th>
                                        <th>Оплата</th>
                                        <th class="text-end">Действия</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($card['enrollments'] as $row)
                                        @php
                                            $enrollment = $row['enrollment'];
                                            $child = $row['child'];
                                            $package = $row['package'];
                                            $paid = $enrollment->total_paid ?? 0;
                                            $price = $enrollment->price ?? 0;
                                        @endphp
                                        <tr class="{{ $row['mark_disabled'] ? 'table-light' : '' }}">
                                            <td>
                                                <div class="fw-semibold">{{ $child->full_name }}</div>
                                                <div class="text-secondary small">Телефон: {{ $child->parent_phone ?? $child->child_phone ?? '—' }}</div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $package?->name ?? '—' }}</div>
                                                <div class="text-secondary small">
                                                    {{ $package?->billing_type === 'visits' ? 'По занятиям' : 'По периоду' }}
                                                    @if($package?->billing_type === 'visits' && $package?->visits_count)
                                                        — {{ $package->visits_count }} занятий
                                                    @endif
                                                    @if($package?->billing_type === 'period' && $package?->days)
                                                        — {{ $package->days }} дней
                                                    @endif
                                                </div>
                                                <span class="{{ $row['status_class'] }}">{{ $row['status_label'] }}</span>
                                            </td>
                                            <td>{{ $row['schedule_label'] }}</td>
                                            <td>
                                                {{ number_format($paid, 2, ',', ' ') }} ₸ / {{ number_format($price, 2, ',', ' ') }} ₸
                                                @if($row['needs_payment'])
                                                    <div class="text-danger small">Нужно доплатить</div>
                                                @endif
                                                @if($row['payment_helper'])
                                                    <div class="text-secondary small">{{ $row['payment_helper'] }}</div>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                                                    <form method="POST" action="{{ route('reception.mark') }}" data-attendance-form>
                                                        @csrf
                                                        <input type="hidden" name="child_id" value="{{ $child->id }}">
                                                        <input type="hidden" name="section_id" value="{{ $section->id }}">
                                                        <button type="submit" class="btn btn-sm btn-success" {{ $row['mark_disabled'] ? 'disabled' : '' }}>Отметить</button>
                                                    </form>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#payment-modal-{{ $enrollment->id }}" {{ $row['payment_disabled'] ? 'disabled' : '' }}>Оплата</button>
                                                </div>
                                                @if($row['mark_helper'])
                                                    <div class="text-secondary small mt-1">{{ $row['mark_helper'] }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-secondary py-3">Активных записей нет.</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="text-secondary mb-0">В этом направлении пока нет секций.</p>
                @endforelse
            </div>
        </div>
    @endforeach
</div>

@foreach($directionGroups as $cards)
    @foreach($cards as $card)
        @foreach($card['enrollments'] as $row)
            @php $enrollment = $row['enrollment']; @endphp
            <div class="modal fade" id="payment-modal-{{ $enrollment->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Оплата занятий</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                        </div>
                        <form method="POST" action="{{ route('payments.store') }}">
                            @csrf
                            <input type="hidden" name="enrollment_id" value="{{ $enrollment->id }}">
                            <div class="modal-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Ребёнок</dt>
                                    <dd class="col-sm-8">{{ $row['child']->full_name }}</dd>
                                    <dt class="col-sm-4">Секция</dt>
                                    <dd class="col-sm-8">{{ $card['section']->name }}</dd>
                                    <dt class="col-sm-4">Пакет</dt>
                                    <dd class="col-sm-8">{{ $row['package']->name ?? '—' }}</dd>
                                </dl>
                                <div class="mb-3 mt-3">
                                    <label class="form-label" for="payment-amount-{{ $enrollment->id }}">Сумма, ₸</label>
                                    <input type="number" step="0.01" min="0.01" class="form-control" id="payment-amount-{{ $enrollment->id }}" name="amount" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="payment-method-{{ $enrollment->id }}">Способ оплаты</label>
                                    <input type="text" class="form-control" id="payment-method-{{ $enrollment->id }}" name="method" maxlength="50" placeholder="Например, Kaspi">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="payment-comment-{{ $enrollment->id }}">Комментарий</label>
                                    <textarea class="form-control" id="payment-comment-{{ $enrollment->id }}" name="comment" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                                <button type="submit" class="btn btn-primary">Принять оплату</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endforeach
@endforeach
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

            document.querySelectorAll('form[data-attendance-form]').forEach((form) => {
                const submitButton = form.querySelector('button[type="submit"]');
                form.addEventListener('submit', () => {
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.dataset.originalText = submitButton.textContent;
                        submitButton.textContent = 'Отмечаем…';
                    }
                });
            });
        });
    </script>
@endpush
