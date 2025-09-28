@php
    $initialSchedule = collect($initialSchedule ?? [])->mapWithKeys(function ($slots, $weekday) {
        $weekday = (int) $weekday;
        $normalized = collect($slots)->map(function ($slot) {
            return [
                'id' => $slot['id'] ?? null,
                'starts_at' => $slot['starts_at'] ?? '',
                'ends_at' => $slot['ends_at'] ?? '',
                'locked' => (bool) ($slot['locked'] ?? false),
            ];
        })->values()->all();

        return [$weekday => $normalized];
    })->toArray();
@endphp

<div class="card shadow-sm mb-4" data-schedule-builder data-config='@json(['initial' => $initialSchedule], JSON_UNESCAPED_UNICODE)'>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h2 class="h5 mb-0">Расписание</h2>
                <p class="text-secondary small mb-0">Укажите дни недели и временные интервалы, в которые проходит секция.</p>
            </div>
        </div>

        @php
            $weekdays = [
                1 => 'Понедельник',
                2 => 'Вторник',
                3 => 'Среда',
                4 => 'Четверг',
                5 => 'Пятница',
                6 => 'Суббота',
                7 => 'Воскресенье',
            ];
        @endphp

        <ul class="nav nav-pills mb-3" id="schedule-tabs" role="tablist">
            @foreach($weekdays as $weekdayNumber => $weekdayLabel)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                            id="weekday-tab-{{ $weekdayNumber }}"
                            data-bs-toggle="pill"
                            data-bs-target="#weekday-panel-{{ $weekdayNumber }}"
                            type="button"
                            role="tab"
                            aria-controls="weekday-panel-{{ $weekdayNumber }}"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                            data-weekday="{{ $weekdayNumber }}">
                        <span class="d-inline-flex align-items-center gap-1">
                            <span>{{ $weekdayLabel }}</span>
                            <span class="badge bg-primary rounded-pill" data-slot-count-badge data-weekday="{{ $weekdayNumber }}" hidden>0</span>
                        </span>
                    </button>
                </li>
            @endforeach
        </ul>

        <div class="tab-content" id="schedule-tabContent">
            @foreach($weekdays as $weekdayNumber => $weekdayLabel)
                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                     id="weekday-panel-{{ $weekdayNumber }}"
                     role="tabpanel"
                     aria-labelledby="weekday-tab-{{ $weekdayNumber }}"
                     data-weekday-panel="{{ $weekdayNumber }}">
                    <div class="d-flex justify-content-between align-items-center border rounded px-3 py-2">
                        <div>
                            <div class="fw-semibold">{{ $weekdayLabel }}</div>
                            <div class="text-secondary small">Добавляйте один или несколько временных промежутков.</div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="weekday-toggle-{{ $weekdayNumber }}" data-day-toggle data-weekday="{{ $weekdayNumber }}">
                            <label class="form-check-label" for="weekday-toggle-{{ $weekdayNumber }}">Активно</label>
                        </div>
                    </div>

                    <div class="mt-3" data-slot-list data-weekday="{{ $weekdayNumber }}"></div>

                    <button type="button" class="btn btn-sm btn-outline-primary mt-3" data-add-slot data-weekday="{{ $weekdayNumber }}" disabled>
                        Добавить интервал
                    </button>
                </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-schedule-builder]').forEach((builder) => {
                    const config = JSON.parse(builder.getAttribute('data-config') ?? '{}');
                    const initial = config.initial ?? {};
                    const defaultSlot = { starts_at: '09:00', ends_at: '11:00', locked: false };
                    const dayCounters = {};

                    const getList = (weekday) => builder.querySelector(`[data-slot-list][data-weekday="${weekday}"]`);
                    const getButton = (weekday) => builder.querySelector(`[data-add-slot][data-weekday="${weekday}"]`);
                    const getToggle = (weekday) => builder.querySelector(`[data-day-toggle][data-weekday="${weekday}"]`);

                    const getBadge = (weekday) => builder.querySelector(`[data-slot-count-badge][data-weekday="${weekday}"]`);
                    const updateBadge = (weekday) => {
                        const badge = getBadge(weekday);
                        if (!badge) {
                            return;
                        }
                        const list = getList(weekday);
                        const count = list ? list.querySelectorAll('[data-index]').length : 0;
                        if (count > 0) {
                            badge.hidden = false;
                            badge.textContent = String(count);
                        } else {
                            badge.hidden = true;
                            badge.textContent = '';
                        }
                    };


                    const createSlotRow = (weekday, slot) => {
                        const list = getList(weekday);
                        const index = dayCounters[weekday] = (dayCounters[weekday] ?? 0) + 1;
                        const safeStarts = slot.starts_at || defaultSlot.starts_at;
                        const safeEnds = slot.ends_at || defaultSlot.ends_at;
                        const row = document.createElement('div');
                        row.className = 'row g-2 align-items-end slot-row mb-2';
                        row.dataset.index = String(index);
                        row.innerHTML = `
                            <div class="col-6 col-md-5">
                                <label class="form-label form-label-sm">С</label>
                                <input type="time" class="form-control" name="schedule[${weekday}][${index}][starts_at]" value="${safeStarts}" required>
                            </div>
                            <div class="col-6 col-md-5">
                                <label class="form-label form-label-sm">По</label>
                                <input type="time" class="form-control" name="schedule[${weekday}][${index}][ends_at]" value="${safeEnds}" required>
                            </div>
                            <div class="col-12 col-md-2 text-md-end">
                                <button type="button" class="btn btn-outline-danger btn-sm w-100" data-remove-slot data-weekday="${weekday}" data-index="${index}">Удалить</button>
                            </div>
                        `;

                        if (slot.locked) {
                            row.querySelectorAll('input').forEach((input) => {
                                input.readOnly = true;
                                input.classList.add('bg-light');
                            });
                            const removeBtn = row.querySelector('[data-remove-slot]');
                            removeBtn.disabled = true;
                            removeBtn.classList.add('d-none');
                            const badge = document.createElement('div');
                            badge.className = 'col-12 text-secondary small';
                            badge.textContent = 'Интервал используется в активных записях и не может быть удалён.';
                            row.appendChild(badge);
                        }

                        list?.appendChild(row);
                        updateBadge(weekday);
                    };

                    const setDayActive = (weekday, active, options = {}) => {
                        const { skipDefault = false } = options;
                        const addButton = getButton(weekday);
                        const list = getList(weekday);
                        if (!addButton || !list) {
                            return;
                        }
                        addButton.disabled = !active;
                        if (!active) {
                            list.innerHTML = '';
                            dayCounters[weekday] = 0;
                        } else if (!list.children.length && !skipDefault) {
                            createSlotRow(weekday, defaultSlot);
                        }
                        updateBadge(weekday);
                    };

                    builder.querySelectorAll('[data-add-slot]').forEach((button) => {
                        button.addEventListener('click', () => {
                            const weekday = button.getAttribute('data-weekday');
                            createSlotRow(weekday, defaultSlot);
                        });
                    });

                    builder.addEventListener('click', (event) => {
                        const removeBtn = event.target.closest('[data-remove-slot]');
                        if (!removeBtn) {
                            return;
                        }
                        const weekday = removeBtn.getAttribute('data-weekday');
                        const index = removeBtn.getAttribute('data-index');
                        const list = getList(weekday);
                        const row = list?.querySelector(`[data-index="${index}"]`);
                        if (row) {
                            row.remove();
                            if (!list.children.length) {
                                setDayActive(weekday, false);
                                const toggle = getToggle(weekday);
                                if (toggle) {
                                    toggle.checked = false;
                                }
                            } else {
                                updateBadge(weekday);
                            }
                        }
                    });

                    builder.querySelectorAll('[data-day-toggle]').forEach((toggle) => {
                        toggle.addEventListener('change', () => {
                            const weekday = toggle.getAttribute('data-weekday');
                            setDayActive(weekday, toggle.checked);
                        });
                    });

                    Object.keys(initial).forEach((weekday) => {
                        const toggle = getToggle(weekday);
                        if (!toggle) {
                            return;
                        }
                        const slots = initial[weekday] ?? [];
                        if (slots.length) {
                            toggle.checked = true;
                            setDayActive(weekday, true, { skipDefault: slots.length > 0 });
                            slots.forEach((slot) => createSlotRow(weekday, slot));
                        }
                    });
                });
            });
        </script>
    @endonce
@endpush









