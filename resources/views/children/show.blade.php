@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-3">Карточка ребёнка</h1>

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

                <dt class="col-sm-3">Телефон второго родителя</dt>
                <dd class="col-sm-9">{{ $child->parent2_phone ?? '—' }}</dd>

                <dt class="col-sm-3">Заметки</dt>
                <dd class="col-sm-9">{{ $child->notes ?? '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('children.edit', $child) }}" class="btn btn-primary">Редактировать</a>
        <form method="POST" action="{{ route('children.destroy', $child) }}" class="d-inline"
              onsubmit="return confirm('Удалить карточку ребёнка? Действие необратимо.');">
            @csrf
            @method('DELETE')
            <button class="btn btn-outline-danger" type="submit">Удалить</button>
        </form>
        <a href="{{ route('children.index') }}" class="btn btn-link">Назад к списку</a>
    </div>

    <div class="mt-4">
        <h2 class="h5 mb-3">Прикрепления</h2>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Секция</th>
                        <th>Пакет</th>
                        <th>Период</th>
                        <th>Остаток</th>
                        <th>Статус</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($child->enrollments as $enrollment)
                        @php
                            $periodStart = $enrollment->started_at?->format('d.m.Y');
                            $periodEnd = $enrollment->expires_at?->format('d.m.Y');
                            $period = $periodStart ?? '—';
                            if ($periodEnd) {
                                $period .= ' — ' . $periodEnd;
                            }
                            $visitsLeft = $enrollment->visits_left !== null ? $enrollment->visits_left : '—';
                            $statusMap = [
                                'pending' => 'Ожидает оплаты',
                                'partial' => 'Частично оплачен',
                                'paid' => 'Оплачен',
                                'expired' => 'Истёк',
                            ];
                        @endphp
                        <tr>
                            <td>{{ $enrollment->section?->name ?? '—' }}</td>
                            <td>
                                {{ $enrollment->package?->name ?? '—' }}
                                @if($enrollment->package?->section && $enrollment->package->section_id !== $enrollment->section_id)
                                    <span class="badge text-bg-warning ms-1">другая секция</span>
                                @endif
                            </td>
                            <td>{{ $period }}</td>
                            <td>{{ $visitsLeft }}</td>
                            <td>{{ $statusMap[$enrollment->status] ?? $enrollment->status }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">Прикреплений пока нет</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <h2 class="h5 mb-3">Платежи</h2>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Дата</th>
                        <th>Секция</th>
                        <th>Пакет</th>
                        <th>Сумма</th>
                        <th>Комментарий</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($child->payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td>{{ $payment->enrollment?->section?->name ?? '—' }}</td>
                            <td>{{ $payment->enrollment?->package?->name ?? '—' }}</td>
                            <td>
                                @if($payment->amount !== null)
                                    {{ number_format((float) $payment->amount, 2, ',', ' ') }} ₽
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $payment->comment ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">Платежей ещё не было</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <h2 class="h5 mb-3">История действий</h2>
        <div class="card">
            <div class="card-body">
                @php
                    $fieldLabels = [
                        'first_name' => 'Имя',
                        'last_name' => 'Фамилия',
                        'patronymic' => 'Отчество',
                        'dob' => 'Дата рождения',
                        'child_phone' => 'Телефон ребёнка',
                        'parent_phone' => 'Телефон родителя',
                        'parent2_phone' => 'Телефон второго родителя',
                        'notes' => 'Заметки',
                    ];
                @endphp
                @if($history->isEmpty())
                    <div class="text-secondary">Пока нет записей.</div>
                @else
                    <ul class="list-unstyled mb-0">
                        @foreach($history as $entry)
                            @php
                                $payload = $entry->payload ?? [];
                                $actor = $entry->user?->name ?? 'Система';
                                $timestamp = $entry->created_at?->format('d.m.Y H:i');
                                $message = '';
                                $details = null;

                                switch ($entry->action) {
                                    case 'child.created':
                                        $message = 'Создана карточка ребёнка';
                                        break;
                                    case 'child.updated':
                                        $message = 'Обновлены данные ребёнка';
                                        if (!empty($payload['changes']) && is_array($payload['changes'])) {
                                            $lines = [];
                                            foreach ($payload['changes'] as $field => $change) {
                                                $label = $fieldLabels[$field] ?? $field;
                                                $old = $change['old'] ?? '—';
                                                $new = $change['new'] ?? '—';
                                                $lines[] = "$label: $old -> $new";
                                            }
                                            $details = implode('; ', $lines);
                                        }
                                        break;
                                    case 'child.deleted':
                                        $message = 'Карточка удалена';
                                        break;
                                    case 'child.deactivated':
                                        $message = 'Ребёнок отмечен как неактивный';
                                        if (array_key_exists('had_active_package', $payload)) {
                                            $details = $payload['had_active_package']
                                                ? 'На момент деактивации были активные оплаченные пакеты'
                                                : 'Активных оплаченных пакетов не было';
                                        }
                                        break;
                                    case 'child.activated':
                                        $message = 'Ребёнок вновь активирован';
                                        break;
                                    case 'child.payment_recorded':
                                        $amount = isset($payload['amount'])
                                            ? number_format((float) $payload['amount'], 2, ',', ' ') . ' ₽'
                                            : 'Сумма не указана';
                                        $sectionName = $payload['section_name'] ?? '—';
                                        $packageName = $payload['package_name'] ?? '—';
                                        $message = 'Зачислен платёж';
                                        $details = "Секция: {$sectionName}, пакет: {$packageName}, сумма: {$amount}";
                                        break;
                                    case 'child.enrollment_added':
                                        $message = 'Ребёнок прикреплён к секции';
                                        $sectionName = $payload['section_name'] ?? '—';
                                        $packageName = $payload['package_name'] ?? '—';
                                        $details = "Секция: {$sectionName}, пакет: {$packageName}";
                                        break;
                                    case 'child.enrollment_removed':
                                        $message = 'Ребёнок откреплён от секции';
                                        $sectionName = $payload['section_name'] ?? '—';
                                        $packageName = $payload['package_name'] ?? '—';
                                        $details = "Секция: {$sectionName}, пакет: {$packageName}";
                                        break;
                                    case 'child.attendance_marked':
                                        $message = 'Отмечено посещение';
                                        $sectionName = $payload['section_name'] ?? '—';
                                        $date = $payload['attended_on'] ?? '—';
                                        $details = "Секция: {$sectionName}, дата: {$date}";
                                        break;
                                    default:
                                        $message = $entry->action;
                                }
                            @endphp
                            <li class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <span class="fw-semibold">{{ $actor }}</span>
                                        <span class="text-secondary">— {{ $message }}</span>
                                        @if($details)
                                            <div class="text-secondary small mt-1">{{ $details }}</div>
                                        @endif
                                    </div>
                                    <div class="text-secondary small">{{ $timestamp }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection
