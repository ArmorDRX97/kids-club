
@extends('layouts.app')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0">Карточка ребёнка</h1>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('children.index') }}" class="btn btn-link">← Ко всем детям</a>
            <a href="{{ route('children.edit', $child) }}" class="btn btn-primary">Редактировать</a>
            <form method="POST" action="{{ route('children.destroy', $child) }}" class="d-inline"
                  onsubmit="return confirm('Удалить карточку ребёнка полностью? Действие нельзя отменить.');">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger" type="submit">Удалить</button>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <div class="fw-semibold h5 mb-0">
                    {{ $child->last_name }} {{ $child->first_name }} {{ $child->patronymic }}
                </div>
                <div>
                    @if($child->is_active)
                        <span class="badge text-bg-success">Активен</span>
                    @else
                        <span class="badge text-bg-secondary">Неактивен</span>
                    @endif
                </div>
            </div>

            <dl class="row mb-0">
                <dt class="col-sm-3">Дата рождения</dt>
                <dd class="col-sm-9">{{ $child->dob?->format('d.m.Y') ?? '—' }}</dd>

                <dt class="col-sm-3">Телефон ребёнка</dt>
                <dd class="col-sm-9">{{ $child->child_phone ?? '—' }}</dd>

                <dt class="col-sm-3">Телефон родителя</dt>
                <dd class="col-sm-9">{{ $child->parent_phone ?? '—' }}</dd>

                <dt class="col-sm-3">Дополнительный телефон</dt>
                <dd class="col-sm-9">{{ $child->parent2_phone ?? '—' }}</dd>

                <dt class="col-sm-3">Заметки</dt>
                <dd class="col-sm-9">{{ $child->notes ?? '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="h6 mb-0">Записать на секцию</h2>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#enrollModal" {{ $sectionsPayload->isEmpty() ? 'disabled' : '' }}>
                            Записать
                        </button>
                    </div>
                    @if($sectionsPayload->isEmpty())
                        <p class="text-secondary mb-0">Нет активных секций с расписанием. Добавьте расписание на странице секций.</p>
                    @else
                        <p class="text-secondary small mb-0">Сначала выберите секцию, затем временной слот и подходящий пакет. Оплату можно провести сразу или позже.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6 mb-2">Задолженности</h2>
                    @if($outstandingEnrollments->isEmpty() && $child->trialAttendances->where('is_free', false)->where('paid_amount', 0)->isEmpty())
                        <p class="text-secondary mb-0">Все прикрепления оплачены.</p>
                    @else
                        <div class="fw-semibold mb-3">Общая задолженность: {{ number_format($totalDebt, 2, ',', ' ') }} ₸</div>
                        
                        <!-- Обычные прикрепления -->
                        @foreach($outstandingEnrollments as $enrollment)
                            @php
                                $sectionName = $enrollment->section?->name ?? 'Без секции';
                                $packageName = $enrollment->package?->name ?? 'Без пакета';
                                $paid = $enrollment->total_paid ?? 0;
                                $debt = max(0, ($enrollment->price ?? 0) - $paid);
                                $statusLabels = [
                                    'pending' => 'Требуется оплата',
                                    'partial' => 'Оплачено частично',
                                    'expired' => 'Истёк срок',
                                ];
                                $statusLabel = $statusLabels[$enrollment->status] ?? $enrollment->status;
                            @endphp
                            <div class="card mb-2 border-warning">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ $sectionName }}</h6>
                                            <p class="text-muted small mb-1">{{ $packageName }}</p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="small">
                                                    <span class="text-success">Оплачено: {{ number_format($paid, 2, ',', ' ') }} ₸</span>
                                                    <span class="text-danger ms-2">Осталось: {{ number_format($debt, 2, ',', ' ') }} ₸</span>
                                                </div>
                                                <span class="badge bg-warning-subtle text-warning-emphasis">{{ $statusLabel }}</span>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary ms-2" 
                                                data-bs-toggle="modal" data-bs-target="#paymentModal" 
                                                data-enrollment-id="{{ $enrollment->id }}"
                                                data-debt="{{ $debt }}"
                                                data-type="enrollment">
                                            Оплатить
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <!-- Пробные занятия -->
                        @foreach($child->trialAttendances->where('is_free', false)->where('paid_amount', 0) as $trialAttendance)
                            <div class="card mb-2 border-info">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ $trialAttendance->section->name }}</h6>
                                            <p class="text-muted small mb-1">Пробное занятие</p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="small">
                                                    <span class="text-danger">К доплате: {{ number_format($trialAttendance->price, 2, ',', ' ') }} ₸</span>
                                                </div>
                                                <span class="badge bg-info-subtle text-info-emphasis">Пробное занятие</span>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary ms-2" 
                                                data-bs-toggle="modal" data-bs-target="#paymentModal" 
                                                data-trial-id="{{ $trialAttendance->id }}"
                                                data-debt="{{ $trialAttendance->price }}"
                                                data-type="trial">
                                            Оплатить
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Прикрепления</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Секция</th>
                        <th>Расписание</th>
                        <th>Пакет</th>
                        <th>Период</th>
                        <th>Оплата</th>
                        <th>Статус</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($child->enrollments as $enrollment)
                        @php
                            $schedule = $enrollment->schedule;
                            $weekdayNames = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'];
                            $scheduleText = $schedule
                                ? ($weekdayNames[$schedule->weekday] ?? $schedule->weekday) . ' ' . $schedule->starts_at->format('H:i') . ' – ' . $schedule->ends_at->format('H:i')
                                : 'Не выбрано';
                            $periodStart = $enrollment->started_at?->format('d.m.Y');
                            $periodEnd = $enrollment->expires_at?->format('d.m.Y');
                            $period = $periodStart ?? '—';
                            if ($periodEnd) {
                                $period .= ' — ' . $periodEnd;
                            }
                            $price = $enrollment->price ?? 0;
                            $paid = $enrollment->total_paid ?? 0;
                            $due = max(0, $price - $paid);
                            $statusLabels = [
                                'pending' => 'Требуется оплата',
                                'partial' => 'Оплачено частично',
                                'paid' => 'Оплачено',
                                'expired' => 'Истёк срок',
                            ];
                            $statusBadgeClasses = [
                                'pending' => 'badge bg-danger-subtle text-danger-emphasis',
                                'partial' => 'badge bg-warning-subtle text-warning-emphasis',
                                'paid' => 'badge bg-success-subtle text-success-emphasis',
                                'expired' => 'badge bg-secondary-subtle text-secondary-emphasis',
                            ];
                            $status = $enrollment->status ?? 'pending';
                        @endphp
                        <tr>
                            <td>{{ $enrollment->section?->name ?? '—' }}</td>
                            <td>{{ $scheduleText }}</td>
                            <td>{{ $enrollment->package?->name ?? '—' }}</td>
                            <td>{{ $period }}</td>
                            <td>
                                {{ number_format($paid, 2, ',', ' ') }} ₸ из {{ number_format($price, 2, ',', ' ') }} ₸
                                @if($due > 0)
                                    <div class="small text-danger">Долг: {{ number_format($due, 2, ',', ' ') }} ₸</div>
                                @endif
                            </td>
                            <td><span class="{{ $statusBadgeClasses[$status] ?? 'badge bg-secondary' }}">{{ $statusLabels[$status] ?? $status }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-3">Прикреплений пока нет.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Платежи</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Дата</th>
                        <th>Секция</th>
                        <th>Пакет</th>
                        <th>Сумма</th>
                        <th>Способ</th>
                        <th>Оператор</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($child->payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td>{{ $payment->enrollment?->section?->name ?? '—' }}</td>
                            <td>{{ $payment->enrollment?->package?->name ?? '—' }}</td>
                            <td>{{ number_format($payment->amount, 2, ',', ' ') }} ₸</td>
                            <td>{{ $payment->method ?? '—' }}</td>
                            <td>{{ $payment->user?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-3">Платежей пока нет.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($trialAttendances->isNotEmpty())
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Пробные занятия</h2>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Дата</th>
                            <th>Секция</th>
                            <th>Тип</th>
                            <th>Оплата</th>
                            <th>Отметил</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($trialAttendances as $trialAttendance)
                            <tr>
                                <td>{{ $trialAttendance->attended_on->format('d.m.Y') }}</td>
                                <td>{{ $trialAttendance->section->name }}</td>
                                <td>
                                    @if($trialAttendance->is_free)
                                        <span class="badge bg-success-subtle text-success-emphasis">Бесплатное</span>
                                    @else
                                        <span class="badge bg-primary-subtle text-primary-emphasis">Платное</span>
                                    @endif
                                </td>
                                <td>
                                    @if($trialAttendance->is_free)
                                        <span class="text-success">Бесплатно</span>
                                    @else
                                        <div>
                                            <strong>{{ number_format($trialAttendance->price, 2, ',', ' ') }} ₸</strong>
                                            @if($trialAttendance->paid_amount > 0)
                                                <div class="small text-secondary">
                                                    Оплачено: {{ number_format($trialAttendance->paid_amount, 2, ',', ' ') }} ₸
                                                    @if($trialAttendance->payment_method)
                                                        ({{ $trialAttendance->payment_method }})
                                                    @endif
                                                </div>
                                            @else
                                                <div class="small text-warning">Не оплачено</div>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="small text-secondary">
                                        {{ $trialAttendance->marker->name ?? 'Система' }}
                                        <br>
                                        {{ $trialAttendance->attended_at->format('d.m.Y H:i') }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if($systemMarkedAttendances->isNotEmpty())
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Системные отметки отсутствия</h2>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Дата</th>
                            <th>Секция</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($systemMarkedAttendances as $attendance)
                            <tr>
                                <td>{{ $attendance->attended_on->format('d.m.Y') }}</td>
                                <td>{{ $attendance->section->name }}</td>
                                <td>
                                    @if($attendance->restored_at)
                                        <span class="badge bg-success-subtle text-success-emphasis">
                                            Возвращено {{ $attendance->restored_at->format('d.m.Y H:i') }}
                                        </span>
                                        <div class="small text-secondary">
                                            {{ $attendance->restorer->name ?? 'Система' }}: {{ $attendance->restored_reason }}
                                        </div>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger-emphasis">Не пришел. Отмечено системой</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$attendance->restored_at)
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#restoreModal" 
                                                data-attendance-id="{{ $attendance->id }}"
                                                data-attendance-date="{{ $attendance->attended_on->format('d.m.Y') }}"
                                                data-section-name="{{ $attendance->section->name }}">
                                            Вернуть посещение
                                        </button>
                                    @else
                                        <span class="text-secondary small">Уже возвращено</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="card mb-5">
        <div class="card-body">
            <h2 class="h5 mb-3">История действий</h2>
            @if($history->isEmpty())
                <p class="text-secondary mb-0">История пока пуста.</p>
            @else
                <ul class="list-unstyled mb-0">
                    @foreach($history as $entry)
                        @php
                            $actor = $entry->user?->name ?? 'Система';
                            $timestamp = $entry->created_at?->format('d.m.Y H:i');
                            $message = match ($entry->action) {
                                'child.created' => 'Карточка создана',
                                'child.updated' => 'Данные изменены',
                                'child.deleted' => 'Карточка удалена',
                                'child.deactivated' => 'Карточка отключена',
                                'child.activated' => 'Карточка активирована',
                                'child.payment_recorded' => 'Зафиксирован платёж',
                                'child.enrollment_added' => 'Добавлено прикрепление',
                                'child.enrollment_removed' => 'Прикрепление завершено',
                                'child.attendance_marked' => 'Отмечено посещение',
                                'child.absence_marked' => 'Отмечено отсутствие системой',
                                'child.visit_restored' => 'Возвращено посещение',
                                default => $entry->action,
                            };
                        @endphp
                        <li class="mb-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <span class="fw-semibold">{{ $actor }}</span>
                                    <span class="text-secondary"> — {{ $message }}</span>
                                </div>
                                <div class="text-secondary small">{{ $timestamp }}</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="modal fade" id="enrollModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="{{ route('enrollments.store') }}">
                @csrf
                <input type="hidden" name="child_id" value="{{ $child->id }}">
                <div class="modal-header">
                    <h5 class="modal-title">Запись на секцию</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="enroll-section" class="form-label">Секция</label>
                        <select class="form-select" id="enroll-section" name="section_id" required>
                            <option value="">Выберите секцию</option>
                            @foreach($sectionsPayload as $sectionData)
                                <option value="{{ $sectionData['id'] }}">{{ $sectionData['direction'] ? $sectionData['direction'] . ' · ' : '' }}{{ $sectionData['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Пробное занятие -->
                    <div class="mb-3" id="trial-section" style="display: none;">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is-trial" name="is_trial" value="1">
                            <label class="form-check-label" for="is-trial">Это пробное занятие</label>
                        </div>
                        <div id="trial-info" class="mt-2" style="display: none;">
                            <div class="alert alert-info">
                                <div id="trial-details"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Обычная запись (не пробное) -->
                    <div id="regular-enrollment" style="display: none;">
                        <div class="mb-3">
                            <label for="enroll-package" class="form-label">Пакет</label>
                            <select class="form-select" id="enroll-package" name="package_id" required disabled>
                                <option value="">Сначала выберите секцию</option>
                            </select>
                        </div>
                    </div>

                    <!-- Календарь для выбора даты -->
                    <div class="mb-3" id="date-selection" style="display: none;">
                        <label class="form-label">Выберите дату</label>
                        <div id="enrollment-calendar"></div>
                        <input type="hidden" id="enroll-started-at" name="started_at" required>
                    </div>

                    <!-- Выбор времени -->
                    <div class="mb-3" id="time-selection" style="display: none;">
                        <label class="form-label">Выберите время</label>
                        <div id="time-slots" class="row g-2"></div>
                        <input type="hidden" id="enroll-schedule" name="section_schedule_id">
                    </div>

                    <div class="mb-3">
                        <label for="enroll-payment-comment" class="form-label">Комментарий</label>
                        <textarea class="form-control" id="enroll-payment-comment" name="payment_comment" rows="2" placeholder="Дополнительная информация"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно для возврата посещения -->
    <div class="modal fade" id="restoreModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="restoreForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Возврат посещения</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <p class="mb-2">Вы собираетесь вернуть посещение:</p>
                        <div class="alert alert-info">
                            <strong id="restoreAttendanceInfo"></strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="restoreReason" class="form-label">Причина возврата <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="restoreReason" name="reason" rows="3" 
                                  placeholder="Укажите причину возврата посещения (например: предоставлена справка, уважительная причина и т.д.)" 
                                  required maxlength="500"></textarea>
                        <div class="form-text">Максимум 500 символов</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary" id="restoreSubmitBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Вернуть посещение
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно для оплаты -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Оплата</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form id="paymentForm" method="POST" action="{{ route('payments.store') }}">
                    @csrf
                    <input type="hidden" id="payment-enrollment-id" name="enrollment_id">
                    <input type="hidden" id="payment-trial-id" name="trial_id">
                    <input type="hidden" id="payment-type" name="payment_type">
                    <div class="modal-body">
                        <div id="payment-info" class="mb-3"></div>
                        
                        <!-- Информация для пробного занятия -->
                        <div id="trial-payment-info" class="alert alert-info" style="display: none;">
                            <i class="bi bi-info-circle me-1"></i>
                            За пробное занятие требуется полная оплата
                        </div>
                        
                        <div class="mb-3">
                            <label for="payment-amount" class="form-label">Сумма оплаты, ₸</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" class="form-control" id="payment-amount" name="amount" required>
                                <button type="button" class="btn btn-outline-secondary" id="full-payment-btn">Полная оплата</button>
                            </div>
                            <div class="form-text">Остаток к доплате: <span id="remaining-amount">0</span> ₸</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Способ оплаты</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="payment_method" id="payment-cash" value="cash">
                                <label class="btn btn-outline-primary" for="payment-cash">Наличные</label>
                                
                                <input type="radio" class="btn-check" name="payment_method" id="payment-card" value="card" checked>
                                <label class="btn btn-outline-primary" for="payment-card">Безналичные</label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="payment-comment" class="form-label">Комментарий</label>
                            <textarea class="form-control" id="payment-comment" name="comment" rows="2" placeholder="Дополнительная информация об оплате"></textarea>
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
@endsection

@push('styles')
<style>
    #enrollment-calendar {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 1rem;
        background: #fff;
        min-height: 300px;
    }
    
    .time-slot-btn {
        transition: all 0.2s ease;
        border: 2px solid #dee2e6;
        background: #fff;
    }
    
    .time-slot-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-color: #0d6efd;
    }
    
    .time-slot-btn.active {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: white;
    }
    
    .flatpickr-calendar {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-radius: 0.5rem;
    }
    
    .flatpickr-day {
        border-radius: 0.25rem;
    }
    
    .flatpickr-day.selected {
        background: #0d6efd;
        border-color: #0d6efd;
    }
    
    .flatpickr-day:hover {
        background: #e7f3ff;
    }
    
    .flatpickr-day.disabled {
        color: #6c757d;
        background: #f8f9fa;
    }
</style>
@endpush

@push('scripts')
    <script>
        const sectionsCatalog = @json($sectionsPayload->toArray(), JSON_UNESCAPED_UNICODE);
        let currentSection = null;
        let flatpickrInstance = null;

        const sectionSelect = document.getElementById('enroll-section');
        const packageSelect = document.getElementById('enroll-package');
        const trialSection = document.getElementById('trial-section');
        const regularEnrollment = document.getElementById('regular-enrollment');
        const dateSelection = document.getElementById('date-selection');
        const timeSelection = document.getElementById('time-selection');
        const timeSlots = document.getElementById('time-slots');
        const isTrialCheckbox = document.getElementById('is-trial');
        const startedAtInput = document.getElementById('enroll-started-at');
        const scheduleInput = document.getElementById('enroll-schedule');

        // Функции для обработки пробных занятий
        function updateTrialInfo(section) {
            const trialDetails = document.getElementById('trial-details');
            
            if (section.trial_is_free) {
                trialDetails.innerHTML = '<strong>Бесплатное пробное занятие</strong><br>Ребенок может посетить одно пробное занятие бесплатно.';
            } else {
                trialDetails.innerHTML = '<strong>Платное пробное занятие</strong><br>Стоимость: ' + section.trial_price.toLocaleString('ru-RU') + ' ₸<br><small class="text-muted">Оплата будет доступна после записи в разделе "Задолженности"</small>';
            }
        }

        // Инициализация календаря
        function initCalendar() {
            if (flatpickrInstance) {
                flatpickrInstance.destroy();
            }

            const enabledDates = [];
            const weekdays = [];

            if (currentSection) {
                // Получаем дни недели из расписания секции
                currentSection.schedules.forEach(schedule => {
                    if (!weekdays.includes(schedule.weekday)) {
                        weekdays.push(schedule.weekday);
                    }
                });

                // Генерируем доступные даты на 3 месяца вперед
                const today = new Date();
                const endDate = new Date();
                endDate.setMonth(today.getMonth() + 3);

                for (let d = new Date(today); d <= endDate; d.setDate(d.getDate() + 1)) {
                    const dayOfWeek = d.getDay() === 0 ? 7 : d.getDay(); // Воскресенье = 7
                    if (weekdays.includes(dayOfWeek)) {
                        enabledDates.push(d.toISOString().split('T')[0]);
                    }
                }
            }

            flatpickrInstance = flatpickr("#enrollment-calendar", {
                inline: true,
                dateFormat: "Y-m-d",
                enable: enabledDates,
                minDate: "today",
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length > 0) {
                        startedAtInput.value = dateStr;
                        updateTimeSlots();
                    }
                }
            });
        }

        // Обновление слотов времени
        function updateTimeSlots() {
            timeSlots.innerHTML = '';
            
            if (!currentSection || !startedAtInput.value) {
                timeSelection.style.display = 'none';
                return;
            }

            const selectedDate = new Date(startedAtInput.value);
            const dayOfWeek = selectedDate.getDay() === 0 ? 7 : selectedDate.getDay();
            
            const availableSchedules = currentSection.schedules.filter(schedule => schedule.weekday === dayOfWeek);
            
            if (availableSchedules.length === 0) {
                timeSelection.style.display = 'none';
                return;
            }

            timeSelection.style.display = 'block';
            
            availableSchedules.forEach(schedule => {
                const timeSlot = document.createElement('div');
                timeSlot.className = 'col-md-6 col-lg-4';
                
                const startTime = schedule.label.split(' ')[1].split('–')[0];
                const endTime = schedule.label.split(' ')[1].split('–')[1];
                
                timeSlot.innerHTML = `
                    <button type="button" class="btn btn-outline-primary w-100 time-slot-btn" data-schedule-id="${schedule.id}">
                        <i class="bi bi-clock me-1"></i>
                        ${startTime} - ${endTime}
                    </button>
                `;
                
                timeSlots.appendChild(timeSlot);
            });

            // Обработчики для кнопок времени
            document.querySelectorAll('.time-slot-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.time-slot-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    scheduleInput.value = this.dataset.scheduleId;
                });
            });
        }

        const handleSectionChange = () => {
            const id = parseInt(sectionSelect.value, 10);
            currentSection = sectionsCatalog.find((item) => item.id === id);

            // Очищаем все поля
            packageSelect.innerHTML = '';
            timeSlots.innerHTML = '';
            startedAtInput.value = '';
            scheduleInput.value = '';
            
            // Скрываем все секции
            timeSelection.style.display = 'none';
            dateSelection.style.display = 'none';
            regularEnrollment.style.display = 'none';
            trialSection.style.display = 'none';
            
            // Сбрасываем чекбокс пробного занятия
            isTrialCheckbox.checked = false;
            document.getElementById('trial-info').style.display = 'none';

            if (!currentSection) {
                packageSelect.disabled = true;
                packageSelect.insertAdjacentHTML('beforeend', '<option value="">Сначала выберите секцию</option>');
                if (flatpickrInstance) {
                    flatpickrInstance.destroy();
                    flatpickrInstance = null;
                }
                return;
            }

            // Показываем секцию пробного занятия, если у секции есть пробные занятия
            if (currentSection.has_trial && !currentSection.has_child_trial) {
                trialSection.style.display = 'block';
                updateTrialInfo(currentSection);
            }

            // Показываем обычную запись
            regularEnrollment.style.display = 'block';
            packageSelect.disabled = false;

            // Заполняем пакеты
            currentSection.packages.forEach((pkg) => {
                const option = document.createElement('option');
                option.value = pkg.id;
                let details = '—';
                if (pkg.billing_type === 'visits' && pkg.visits_count) {
                    details = `${pkg.visits_count} занятий`;
                } else if (pkg.billing_type === 'period' && pkg.days) {
                    details = `${pkg.days} дней`;
                }
                option.textContent = `${pkg.name} (${details})`;
                packageSelect.appendChild(option);
            });

            // Показываем календарь
            dateSelection.style.display = 'block';
            initCalendar();
        }

        // Обработчик пробного занятия
        isTrialCheckbox.addEventListener('change', function() {
            const trialInfo = document.getElementById('trial-info');
            if (this.checked) {
                trialInfo.style.display = 'block';
                regularEnrollment.style.display = 'none';
                packageSelect.disabled = true;
                packageSelect.required = false;
            } else {
                trialInfo.style.display = 'none';
                regularEnrollment.style.display = 'block';
                packageSelect.disabled = false;
                packageSelect.required = true;
            }
        });

        sectionSelect?.addEventListener('change', handleSectionChange);

        // Обработка модального окна оплаты
        const paymentModal = document.getElementById('paymentModal');
        const paymentForm = document.getElementById('paymentForm');
        const paymentAmount = document.getElementById('payment-amount');
        const fullPaymentBtn = document.getElementById('full-payment-btn');
        const remainingAmount = document.getElementById('remaining-amount');
        const trialPaymentInfo = document.getElementById('trial-payment-info');
        const paymentInfo = document.getElementById('payment-info');

        let currentDebt = 0;
        let isTrialPayment = false;

        // Обработчики для кнопок оплаты
        document.addEventListener('click', function(e) {
            if (e.target.matches('[data-bs-toggle="modal"][data-bs-target="#paymentModal"]')) {
                const type = e.target.dataset.type;
                const debt = parseFloat(e.target.dataset.debt);
                
                currentDebt = debt;
                isTrialPayment = type === 'trial';
                
                // Очищаем форму
                paymentForm.reset();
                document.getElementById('payment-card').checked = true;
                
                // Устанавливаем скрытые поля
                if (type === 'enrollment') {
                    document.getElementById('payment-enrollment-id').value = e.target.dataset.enrollmentId;
                    document.getElementById('payment-trial-id').value = '';
                } else {
                    document.getElementById('payment-enrollment-id').value = '';
                    document.getElementById('payment-trial-id').value = e.target.dataset.trialId;
                }
                document.getElementById('payment-type').value = type;
                
                // Обновляем информацию
                updatePaymentInfo();
            }
        });

        function updatePaymentInfo() {
            remainingAmount.textContent = currentDebt.toLocaleString('ru-RU');
            paymentAmount.max = currentDebt;
            
            if (isTrialPayment) {
                trialPaymentInfo.style.display = 'block';
                paymentAmount.value = currentDebt;
                paymentAmount.readOnly = true;
                fullPaymentBtn.style.display = 'none';
                paymentInfo.innerHTML = '<div class="alert alert-info"><strong>Пробное занятие</strong><br>Требуется полная оплата: ' + currentDebt.toLocaleString('ru-RU') + ' ₸</div>';
            } else {
                trialPaymentInfo.style.display = 'none';
                paymentAmount.readOnly = false;
                fullPaymentBtn.style.display = 'inline-block';
                paymentInfo.innerHTML = '<div class="alert alert-warning"><strong>Обычное прикрепление</strong><br>Можно оплатить частично или полностью</div>';
            }
        }

        // Обработчик кнопки "Полная оплата"
        fullPaymentBtn.addEventListener('click', function() {
            paymentAmount.value = currentDebt;
        });

        // Обработка модального окна возврата посещения
        const restoreModal = document.getElementById('restoreModal');
        const restoreForm = document.getElementById('restoreForm');
        const restoreSubmitBtn = document.getElementById('restoreSubmitBtn');
        const restoreSpinner = restoreSubmitBtn.querySelector('.spinner-border');
        const restoreReason = document.getElementById('restoreReason');
        const restoreAttendanceInfo = document.getElementById('restoreAttendanceInfo');

        let currentAttendanceId = null;

        restoreModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            currentAttendanceId = button.getAttribute('data-attendance-id');
            const attendanceDate = button.getAttribute('data-attendance-date');
            const sectionName = button.getAttribute('data-section-name');
            
            restoreAttendanceInfo.textContent = `${sectionName} - ${attendanceDate}`;
            restoreReason.value = '';
        });

        restoreForm.addEventListener('submit', function (e) {
            e.preventDefault();
            
            if (!currentAttendanceId) {
                alert('Ошибка: не выбран ID посещения');
                return;
            }

            const reason = restoreReason.value.trim();
            if (!reason) {
                alert('Пожалуйста, укажите причину возврата');
                return;
            }

            // Показываем спиннер
            restoreSubmitBtn.disabled = true;
            restoreSpinner.classList.remove('d-none');

            // Отправляем запрос
            fetch(`/attendances/${currentAttendanceId}/restore`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Закрываем модальное окно
                    const modal = bootstrap.Modal.getInstance(restoreModal);
                    modal.hide();
                    
                    // Показываем уведомление
                    alert('Посещение успешно возвращено');
                    
                    // Перезагружаем страницу для обновления данных
                    window.location.reload();
                } else {
                    alert(data.message || 'Ошибка при возврате посещения');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при отправке запроса');
            })
            .finally(() => {
                // Скрываем спиннер
                restoreSubmitBtn.disabled = false;
                restoreSpinner.classList.add('d-none');
            });
        });

        // Обработчик изменения чекбокса пробного занятия
        document.getElementById('is-trial').addEventListener('change', function() {
            const trialInfo = document.getElementById('trial-info');
            if (this.checked) {
                trialInfo.style.display = 'block';
            } else {
                trialInfo.style.display = 'none';
            }
        });
    </script>
@endpush
