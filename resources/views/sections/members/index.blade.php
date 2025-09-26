@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-3">Дети секции: {{ $section->name }}</h1>

    <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
        <a href="{{ route('sections.index') }}" class="btn btn-link">← Назад к списку секций</a>
        <button
            type="button"
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#addModal"
            {{ $packages->isEmpty() ? 'disabled' : '' }}
        >
            Добавить детей
        </button>
        @if($packages->isEmpty())
            <span class="text-danger small">Чтобы прикрепить детей, создайте пакеты для секции.</span>
        @endif
        <a href="{{ route('sections.packages.index', $section) }}" class="btn btn-outline-secondary btn-sm">Управление пакетами</a>
    </div>

    <form
        method="POST"
        action="{{ route('sections.members.store', $section) }}"
        id="membersForm"
        onsubmit="return beforeSubmit()"
    >
        @csrf
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <div class="fw-semibold">Текущие прикрепления</div>
                    <div class="d-flex">
                        <input type="text" id="searchCurrent" class="form-control form-control-sm" placeholder="Поиск" value="{{ $q }}">
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="filterCurrent()">Найти</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ФИО</th>
                                <th>Телефон</th>
                                <th>Пакет</th>
                                <th>Действие</th>
                            </tr>
                        </thead>
                        <tbody id="currentRows">
                        @forelse($members as $membership)
                            <tr data-id="{{ $membership->child->id }}">
                                <td>{{ $membership->child->full_name }}</td>
                                <td>{{ $membership->child->parent_phone ?? $membership->child->child_phone ?? '—' }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $membership->package->name }}</div>
                                    <div class="text-secondary small">
                                        {{ $membership->package->billing_type === 'visits' ? 'По занятиям' : 'По времени' }}
                                        @if($membership->package->billing_type === 'visits' && $membership->package->visits_count)
                                            · {{ $membership->package->visits_count }} занятий
                                        @endif
                                        @if($membership->package->billing_type === 'period' && $membership->package->days)
                                            · {{ $membership->package->days }} дн.
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="stageRemove({{ $membership->child->id }}, this)"
                                    >
                                        Открепить
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-secondary py-3">Нет прикреплённых детей</td>
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
                <div class="fw-semibold mb-2">Новые прикрепляемые дети</div>
                <div class="table-responsive">
                    <table class="table mb-0" id="stagedTable">
                        <thead class="table-light">
                            <tr>
                                <th>ФИО</th>
                                <th>Телефон</th>
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
            <button class="btn btn-success" type="submit">Сохранить</button>
            <a href="{{ route('sections.index') }}" class="btn btn-link">Отмена</a>
        </div>
    </form>

    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить детей</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <div class="input-group mb-3">
                        <input id="searchInput" class="form-control" placeholder="Поиск (ФИО/телефон)">
                        <button class="btn btn-outline-secondary" type="button" onclick="doSearch()">Найти</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ФИО</th>
                                    <th>Телефон</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="searchResults"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const packages = @json($packagesData);
        const addMap = new Map();
        const removeSet = new Set();

        function beforeSubmit() {
            const payload = [];
            let hasError = false;

            addMap.forEach((_, childId) => {
                const select = document.querySelector(`#pkg-select-${childId}`);
                if (!select || !select.value) {
                    hasError = true;
                    select?.classList.add('is-invalid');
                    return;
                }
                select.classList.remove('is-invalid');
                payload.push({ child_id: childId, package_id: parseInt(select.value, 10) });
            });

            if (hasError) {
                alert('Для каждого ребёнка выберите пакет.');
                return false;
            }

            document.getElementById('add_payload').value = JSON.stringify(payload);
            document.getElementById('remove_ids').value = JSON.stringify([...removeSet]);

            return true;
        }

        function stageRemove(id, button) {
            const row = button.closest('tr');
            if (removeSet.has(id)) {
                removeSet.delete(id);
                row?.classList.remove('table-warning');
                button.textContent = 'Открепить';
            } else {
                removeSet.add(id);
                row?.classList.add('table-warning');
                button.textContent = 'Отменить';
            }
        }

        document.getElementById('searchCurrent')?.addEventListener('input', filterCurrent);

        function filterCurrent() {
            const query = document.getElementById('searchCurrent').value.trim().toLowerCase();
            document.querySelectorAll('#currentRows tr').forEach((tr) => {
                const text = tr.textContent.toLowerCase();
                tr.style.display = text.includes(query) ? '' : 'none';
            });
        }

        function stageAdd(row) {
            const id = parseInt(row.dataset.id, 10);
            if (Number.isNaN(id) || addMap.has(id)) {
                return;
            }

            if (!packages.length) {
                alert('Сначала создайте пакеты для секции.');
                return;
            }

            addMap.set(id, true);
            document.getElementById('stagedCard').classList.remove('d-none');

            const name = row.querySelector('[data-name]')?.textContent?.trim() ?? '';
            const phone = row.querySelector('[data-phone]')?.textContent?.trim() || '—';

            const stagedRow = document.createElement('tr');
            stagedRow.dataset.id = String(id);
            stagedRow.innerHTML = `
                <td>${name}</td>
                <td>${phone}</td>
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

            if (addMap.size === 0) {
                document.getElementById('stagedCard').classList.add('d-none');
            }

            const searchRowButton = document.querySelector(`#searchResults tr[data-id="${id}"] button`);
            if (searchRowButton) {
                searchRowButton.disabled = false;
            }
        }

        function renderPackageSelect(childId) {
            const options = packages.map((pkg) => {
                let details = '—';
                if (pkg.billing_type === 'visits' && pkg.visits_count) {
                    details = `${pkg.visits_count} зан.`;
                } else if (pkg.billing_type === 'period' && pkg.days) {
                    details = `${pkg.days} дн.`;
                }
                return `<option value="${pkg.id}">${pkg.name} (${details})</option>`;
            }).join('');

            return `<select class="form-select form-select-sm" id="pkg-select-${childId}">${options}</select>`;
        }

        async function doSearch() {
            const query = document.getElementById('searchInput').value.trim();
            const tbody = document.getElementById('searchResults');

            tbody.innerHTML = '';

            try {
                const response = await fetch(`{{ route('sections.members.search', $section) }}?q=${encodeURIComponent(query)}`);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();

                if (!Array.isArray(data) || data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-secondary py-3">Ничего не найдено</td></tr>';
                    return;
                }

                data.forEach((item) => {
                    const tr = document.createElement('tr');
                    tr.dataset.id = item.id;
                    tr.innerHTML = `
                        <td data-name>${item.last_name} ${item.first_name} ${item.patronymic ?? ''}</td>
                        <td data-phone>${item.child_phone ?? ''}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-primary" onclick="stageAdd(this.closest('tr'))">Добавить</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } catch (error) {
                console.error(error);
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger py-3">Ошибка загрузки данных</td></tr>';
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
@endsection
