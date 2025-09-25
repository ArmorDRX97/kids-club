@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-3">Добавить ребёнка</h1>

    <form method="POST" action="{{ route('children.store') }}" class="card card-body">
        @csrf
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Фамилия</label>
                <input name="last_name" class="form-control" value="{{ old('last_name') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Имя</label>
                <input name="first_name" class="form-control" value="{{ old('first_name') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Отчество</label>
                <input name="patronymic" class="form-control" value="{{ old('patronymic') }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Дата рождения</label>
                <input type="date" name="dob" class="form-control" value="{{ old('dob') }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Телефон ребёнка</label>
                <input name="child_phone" class="form-control" value="{{ old('child_phone') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Телефон родителя</label>
                <input name="parent_phone" class="form-control" value="{{ old('parent_phone') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Телефон второго родителя</label>
                <input name="parent2_phone" class="form-control" value="{{ old('parent2_phone') }}">
            </div>

            <div class="col-12">
                <label class="form-label">Заметки</label>
                <textarea name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
            </div>

            <div class="col-12 form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" role="switch" id="is_active"
                       name="is_active" value="1" {{ old('is_active',1) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Активен</label>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary">Сохранить</button>
            <a href="{{ route('children.index') }}" class="btn btn-link">Отмена</a>
        </div>
    </form>
@endsection
