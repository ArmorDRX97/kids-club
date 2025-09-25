@extends('layouts.app')
@section('content')
    <h1 class="h4 mb-3">{{ isset($package)?'Правка пакета':'Новый пакет' }}</h1>
    <form method="POST" action="{{ isset($package)?route('packages.update',$package):route('packages.store') }}" class="card card-body">
        @csrf @if(isset($package)) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Секция</label>
                <select name="section_id" class="form-select" required>
                    <option value="">— выберите —</option>
                    @foreach($sections as $s)
                        <option value="{{ $s->id }}" @selected(old('section_id',$package->section_id ?? '')==$s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Тип</label>
                <select id="pkg-type" name="type" class="form-select" required>
                    <option value="visits" @selected(old('type',$package->type ?? '')==='visits')>По занятиям</option>
                    <option value="period" @selected(old('type',$package->type ?? '')==='period')>На период</option>
                </select>
            </div>
            <div class="col-md-6" id="wrap-visits">
                <label class="form-label">Кол-во посещений</label>
                <input type="number" name="visits_count" class="form-control" value="{{ old('visits_count',$package->visits_count ?? '') }}">
            </div>
            <div class="col-md-6 d-none" id="wrap-days">
                <label class="form-label">Дней</label>
                <input type="number" name="days" class="form-control" value="{{ old('days',$package->days ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Цена (₸)</label>
                <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price',$package->price ?? '') }}" required>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary">Сохранить</button>
            <a href="{{ route('packages.index') }}" class="btn btn-link">Отмена</a>
        </div>
    </form>
    <script>
        function togglePkg(){
            const t = document.getElementById('pkg-type').value;
            document.getElementById('wrap-visits').classList.toggle('d-none', t!=='visits');
            document.getElementById('wrap-days').classList.toggle('d-none', t!=='period');
        }
        document.getElementById('pkg-type').addEventListener('change', togglePkg);
        togglePkg();
    </script>
@endsection
