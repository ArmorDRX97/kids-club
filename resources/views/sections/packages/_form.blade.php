@csrf
@if(isset($package))
    @method('PUT')
@endif
@php($isActiveValue = (string) old('is_active', isset($package) ? ($package->is_active ? '1' : '0') : '1'))
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Название</label>
        <input type="text" name="name" value="{{ old('name', $package->name ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Тип пакета</label>
        <select name="billing_type" id="billingType" class="form-select" required>
            <option value="visits" @selected(old('billing_type', $package->billing_type ?? 'visits') === 'visits')>По занятиям</option>
            <option value="period" @selected(old('billing_type', $package->billing_type ?? '') === 'period')>По времени</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Статус</label>
        <select name="is_active" class="form-select">
            <option value="1" @selected($isActiveValue === '1')>Активен</option>
            <option value="0" @selected($isActiveValue === '0')>Скрыт</option>
        </select>
    </div>
    <div class="col-md-4" id="visitsWrap">
        <label class="form-label">Количество занятий</label>
        <input type="number" name="visits_count" min="1" value="{{ old('visits_count', $package->visits_count ?? '') }}" class="form-control">
    </div>
    <div class="col-md-4 d-none" id="daysWrap">
        <label class="form-label">Длительность (дни)</label>
        <input type="number" name="days" min="1" value="{{ old('days', $package->days ?? '') }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">Стоимость (₸)</label>
        <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $package->price ?? 0) }}" class="form-control" required>
    </div>
    <div class="col-12">
        <label class="form-label">Описание</label>
        <textarea name="description" rows="3" class="form-control" placeholder="Например: в стоимость входит форма">{{ old('description', $package->description ?? '') }}</textarea>
        <small class="text-secondary">Необязательное поле для деталей.</small>
    </div>
</div>
<script>
    (function(){
        const typeSelect = document.getElementById('billingType');
        const visitsWrap = document.getElementById('visitsWrap');
        const daysWrap = document.getElementById('daysWrap');
        function toggleFields(){
            const isVisits = typeSelect.value === 'visits';
            visitsWrap.classList.toggle('d-none', !isVisits);
            daysWrap.classList.toggle('d-none', isVisits);
        }
        toggleFields();
        typeSelect.addEventListener('change', toggleFields);
    })();
</script>
