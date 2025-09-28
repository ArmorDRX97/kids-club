@php
    $weekdayNames = [
        1 => 'Пн',
        2 => 'Вт',
        3 => 'Ср',
        4 => 'Чт',
        5 => 'Пт',
        6 => 'Сб',
        7 => 'Вс',
    ];
@endphp

@if($sections->isEmpty())
    <p class="text-secondary mb-0">Секции не найдены.</p>
@else
    <div class="row row-cols-1 row-cols-lg-2 g-3">
        @foreach($sections as $section)
            @php
                $scheduleSummary = $section->schedules
                    ->sortBy(fn($slot) => $slot->weekday * 10000 + (int) $slot->starts_at->format('Hi'))
                    ->groupBy('weekday')
                    ->map(function ($items) use ($weekdayNames) {
                        $label = $weekdayNames[$items->first()->weekday] ?? $items->first()->weekday;
                        $slots = $items->map(function ($slot) {
                            return $slot->starts_at->format('H:i') . '–' . $slot->ends_at->format('H:i');
                        })->implode(', ');
                        return "$label: $slots";
                    })
                    ->implode('; ');
            @endphp
            <div class="col">
                <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h3 class="h5 mb-1">{{ $section->name }}</h3>
                            <div class="text-secondary small">Помещение: {{ $section->room?->name ?? 'Не назначено' }}</div>
                            <div class="text-secondary small mt-2">Расписание: {{ $scheduleSummary ?: 'Не задано' }}</div>
                        </div>
                        <div class="text-end">
                            <span class="badge text-bg-primary">Записей: {{ $section->enrollments_count }}</span>
                            <span class="badge text-bg-info">Пакетов: {{ $section->packages_count }}</span>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('sections.members.index', $section) }}" class="btn btn-sm btn-outline-success">Участники</a>
                        @role('Admin')
                            <a href="{{ route('sections.packages.index', $section) }}" class="btn btn-sm btn-outline-primary">Пакеты</a>
                            <a href="{{ route('sections.edit', $section) }}" class="btn btn-sm btn-outline-secondary">Редактировать</a>
                        @endrole
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
