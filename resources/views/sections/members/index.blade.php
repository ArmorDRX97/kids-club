
@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-3">Участники секции: {{ $section->name }}</h1>

    <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
        <a href="{{ route('sections.index') }}" class="btn btn-link">← Назад к списку секций</a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal" {{ ($packages->isEmpty() || $schedulesData->isEmpty()) ? 'disabled' : '' }}>
            Добавить ребёнка
        </button>
        @if($packages->isEmpty())
            <span class="text-danger small">Чтобы записать детей, создайте хотя бы один пакет.</span>
        @endif
        @if($schedulesData->isEmpty())
            <span class="text-danger small">Для секции нет расписания. Добавьте временные интервалы.</span>
        @endif
    </div>

    <form method="POST" action="{{ route('sections.members.store', $section) }}" id="membersForm" onsubmit="return beforeSubmit()">
        @csrf
        <div class="card mb-3 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <div class="fw-semibold">Активные участники</div>
                    <div class="d-flex">
                        <input type="text" id="searchCurrent" class="form-control form-control-sm" placeholder="Фильтр" value="{{ $q }}">
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="filterCurrent()">Поиск</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Имя</th>
                            <th>Контакты</th>
                            <th>Расписание</th>
                            <th>Пакет</th>
                            <th>Действия</th>
                        </tr>
                        </thead>
                        <tbody id="currentRows">
                        @forelse($members as $membership)
                            @php
                                $weekdayNames = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'];
                                $schedule = $membership->schedule;
                                $scheduleLabel = $schedule
                                    ? (($weekdayNames[$schedule->weekday] ?? $schedule->weekday) . ' ' . $schedule->starts_at->format('H:i') . ' – ' . $schedule->ends_at->format('H:i'))
                                    : 'Не выбрано';
                            @endphp
                            <tr data-id="{{ $membership->child->id }}">
                                <td data-name>{{ $membership->child->full_name }}</td>
                                <td data-phone>{{ $membership->child->parent_phone ?? $membership->child->child_phone ?? '—' }}</td>
                                <td>{{ $scheduleLabel }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $membership->package->name }}</div>
                                    <div class="text-secondary small">
                                        {{ $membership->package->billing_type === 'visits' ? 'По занятиям' : 'По периоду' }}
                                        @if($membership->package->billing_type === 'visits' && $membership->package->visits_count)
                                            — {{ $membership->package->visits_count }} занятий
                                        @endif
                                        @if($membership->package->billing_type === 'period' && $membership->package->days)
                                            — {{ $membership->package->days }} дней
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="stageRemove({{ $membership->child->id }}, this)">
                                        Отметить к удалению
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-3">Записей пока нет.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-2">{{ $members->links() }}</div>
            </div>
        </div>

        <div class="card d-none" id="stagedCard">
            <div class="card-body">
                <div class="fw-semibold mb-2">Изменения ожидают подтверждения</div>
                <div class="table-responsive">
                    <table class="table mb-0" id="stagedTable">
                        <thead class="table-light">
                        <tr>
                            <th>Имя</th>
                            <th>Контакты</th>
                            <th>Расписание</th>
                            <th>Пакет</th>
                            <th>Действие</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <input type="hidden" name="add_payload" id="add_payload">
        <input type="hidden" name="remove_ids" id="remove_ids">

        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-success" type="submit">Сохранить изменения</button>
            <a href="{{ route('sections.index') }}" class="btn btn-link">Отмена</a>
        </div>
    </form>

    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Добавление ребёнка</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <div class="input-group mb-3">
                        <input id="searchInput" class="form-control" placeholder="Имя или телефон">
                        <button class="btn btn-outline-secondary" type="button" onclick="doSearch()">Искать</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Имя</th>
                                <th>Контакты</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody id="searchResults"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Готово</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const packages = @json($packagesData);
        const schedules = @json($schedulesData);
        const addMap = new Map();
        const removeMap = new Map();

        const scheduleOptions = schedules.map((option) => `<option value="${option.id}">${option.label}</option>`).join('');

        function renderScheduleSelect(childId) {
            return `<select class="form-select form-select-sm" id="schedule-select-${childId}">${scheduleOptions}</select>`;
        }

        function renderPackageSelect(childId) {
            const options = packages.map((pkg) => {
                let details = '—';
                if (pkg.billing_type === 'visits' && pkg.visits_count) {
                    details = `${pkg.visits_count} занятий`;
                } else if (pkg.billing_type === 'period' && pkg.days) {
                    details = `${pkg.days} дней`;
                }
                return `<option value="${pkg.id}">${pkg.name} (${details})</option>`;
            }).join('');

            return `<select class="form-select form-select-sm" id="pkg-select-${childId}">${options}</select>`;
        }

        function ensureStagedCard() {
            const card = document.getElementById('stagedCard');
            if (!card) {
                return;
            }
            if (addMap.size > 0 || removeMap.size > 0) {
                card.classList.remove('d-none');
            } else {
                card.classList.add('d-none');
            }
        }

        function stageAdd(row) {
            const id = parseInt(row.dataset.id, 10);
            if (Number.isNaN(id) || addMap.has(id)) {
                return;
            }

            if (!packages.length || !schedules.length) {
                alert('Для записи нужна хотя бы одна активная программа и временной слот.');
                return;
            }

            addMap.set(id, true);
            removeMap.delete(id);

            ensureStagedCard();

            const name = row.querySelector('[data-name]')?.textContent?.trim() ?? '';
            const phone = row.querySelector('[data-phone]')?.textContent?.trim() || '—';

            const stagedRow = document.createElement('tr');
            stagedRow.dataset.id = String(id);
            stagedRow.innerHTML = `
                <td>${name}</td>
                <td>${phone}</td>
                <td>${renderScheduleSelect(id)}</td>
                <td>${renderPackageSelect(id)}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="unstage(${id}, this)">Убрать</button>
                </td>
            `;

            document.querySelector('#stagedTable tbody')?.appendChild(stagedRow);

            const addButton = row.querySelector('button');
            if (addButton) {
                addButton.disabled = true;
            }
        }

        function unstage(id, button) {
            addMap.delete(id);
            button.closest('tr')?.remove();

            if (addMap.size === 0 && removeMap.size === 0) {
                ensureStagedCard();
            }

            const searchRowButton = document.querySelector(`#searchResults tr[data-id="${id}"] button`);
            if (searchRowButton) {
                searchRowButton.disabled = false;
            }
        }

        function stageRemove(id, button) {
            const row = button.closest('tr');
            if (!row) {
                return;
            }

            if (removeMap.has(id)) {
                removeMap.delete(id);
                row.classList.remove('table-danger');
                button.textContent = 'Отметить к удалению';
            } else {
                removeMap.set(id, true);
                row.classList.add('table-danger');
                button.textContent = 'Вернуть';
            }

            ensureStagedCard();
        }

        function beforeSubmit() {
            if (addMap.size === 0 && removeMap.size === 0) {
                alert('Нет изменений для сохранения.');
                return false;
            }

            const additions = [];
            document.querySelectorAll('#stagedTable tbody tr').forEach((row) => {
                const id = parseInt(row.dataset.id ?? '0', 10);
                if (Number.isNaN(id)) {
                    return;
                }
                const pkgSelect = row.querySelector(`#pkg-select-${id}`);
                const scheduleSelect = row.querySelector(`#schedule-select-${id}`);
                if (!pkgSelect || !scheduleSelect) {
                    return;
                }
                additions.push({
                    child_id: id,
                    package_id: parseInt(pkgSelect.value, 10),
                    schedule_id: parseInt(scheduleSelect.value, 10),
                });
            });

            document.getElementById('add_payload').value = JSON.stringify(additions);
            document.getElementById('remove_ids').value = JSON.stringify(Array.from(removeMap.keys()));

            return true;
        }

        function filterCurrent() {
            const query = document.getElementById('searchCurrent').value.trim().toLowerCase();
            document.querySelectorAll('#currentRows tr').forEach((tr) => {
                const text = tr.textContent.toLowerCase();
                tr.style.display = text.includes(query) ? '' : 'none';
            });
        }

        async function doSearch() {
            const query = document.getElementById('searchInput').value.trim();
            const tbody = document.getElementById('searchResults');
            tbody.innerHTML = '';

            if (!query) {
                return;
            }

            try {
                const response = await fetch(`{{ route('sections.members.search', $section) }}?q=${encodeURIComponent(query)}`);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                if (!Array.isArray(data) || data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-secondary py-3">Совпадений не найдено</td></tr>';
                    return;
                }

                data.forEach((item) => {
                    const tr = document.createElement('tr');
                    tr.dataset.id = item.id;
                    tr.innerHTML = `
                        <td data-name>${item.last_name} ${item.first_name} ${item.patronymic ?? ''}</td>
                        <td data-phone>${item.child_phone ?? '—'}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-primary" ${addMap.has(item.id) ? 'disabled' : ''} onclick="stageAdd(this.closest('tr'))">Добавить</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } catch (error) {
                console.error(error);
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger py-3">Ошибка загрузки результатов поиска.</td></tr>';
            }
        }

        document.getElementById('searchInput')?.addEventListener('input', function () {
            clearTimeout(this._searchTimer);
            this._searchTimer = setTimeout(doSearch, 300);
        });

        const currentSearch = document.getElementById('searchCurrent');
        if (currentSearch && currentSearch.value.trim() !== '') {
            filterCurrent();
        }
    </script>
@endpush
