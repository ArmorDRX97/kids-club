
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
                    @if($outstandingEnrollments->isEmpty())
                        <p class="text-secondary mb-0">Все прикрепления оплачены.</p>
                    @else
                        <div class="fw-semibold">Общая задолженность: {{ number_format($totalDebt, 2, ',', ' ') }} ₸</div>
                        <ul class="list-unstyled mb-0 mt-2 small">
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
                                <li class="mb-2">
                                    <span class="fw-semibold">{{ $sectionName }}</span> — {{ $packageName }}
                                    <span class="badge bg-warning-subtle text-warning-emphasis ms-1">{{ $statusLabel }}</span>
                                    <div>Оплачено: {{ number_format($paid, 2, ',', ' ') }} ₸ · Осталось: {{ number_format($debt, 2, ',', ' ') }} ₸</div>
                                </li>
                            @endforeach
                        </ul>
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
                    <div class="mb-3">
                        <label for="enroll-schedule" class="form-label">Время</label>
                        <select class="form-select" id="enroll-schedule" name="section_schedule_id" required disabled>
                            <option value="">Сначала выберите секцию</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="enroll-package" class="form-label">Пакет</label>
                        <select class="form-select" id="enroll-package" name="package_id" required disabled>
                            <option value="">Сначала выберите секцию</option>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <label for="enroll-started-at" class="form-label">Дата начала</label>
                            <input type="date" class="form-control" id="enroll-started-at" name="started_at" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="enroll-payment-amount" class="form-label">Оплата, ₸ (необязательно)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="enroll-payment-amount" name="payment_amount">
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label for="enroll-payment-method" class="form-label">Способ оплаты</label>
                        <input type="text" class="form-control" id="enroll-payment-method" name="payment_method" maxlength="50" placeholder="Например, Kaspi">
                    </div>
                    <div class="mb-3">
                        <label for="enroll-payment-comment" class="form-label">Комментарий</label>
                        <textarea class="form-control" id="enroll-payment-comment" name="payment_comment" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const sectionsCatalog = @json($sectionsPayload->toArray(), JSON_UNESCAPED_UNICODE);

        const sectionSelect = document.getElementById('enroll-section');
        const scheduleSelect = document.getElementById('enroll-schedule');
        const packageSelect = document.getElementById('enroll-package');

        const handleSectionChange = () => {
            const id = parseInt(sectionSelect.value, 10);
            const section = sectionsCatalog.find((item) => item.id === id);

            scheduleSelect.innerHTML = '';
            packageSelect.innerHTML = '';

            if (!section) {
                scheduleSelect.disabled = true;
                packageSelect.disabled = true;
                scheduleSelect.insertAdjacentHTML('beforeend', '<option value="">Сначала выберите секцию</option>');
                packageSelect.insertAdjacentHTML('beforeend', '<option value="">Сначала выберите секцию</option>');
                return;
            }

            scheduleSelect.disabled = false;
            packageSelect.disabled = false;

            section.schedules.forEach((schedule) => {
                const option = document.createElement('option');
                option.value = schedule.id;
                option.textContent = schedule.label;
                scheduleSelect.appendChild(option);
            });

            section.packages.forEach((pkg) => {
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
        }

        sectionSelect?.addEventListener('change', handleSectionChange);
    </script>
@endpush
