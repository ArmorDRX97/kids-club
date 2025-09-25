@extends('layouts.app')
@section('content')
    <h1 class="h4 mb-3">Новый пользователь</h1>
    <form method="POST" action="{{ route('users.store') }}" class="card card-body">
        @csrf
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">ФИО</label><input name="name" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Логин (e‑mail)</label><input type="email" name="email" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Телефон</label><input name="phone" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Пароль</label><input type="password" name="password" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Повтор пароля</label><input type="password" name="password_confirmation" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Роль</label>
                <select name="role" class="form-select" required>
                    <option value="Receptionist">Receptionist</option>
                    <option value="Admin">Admin</option>
                </select>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary">Сохранить</button>
            <a href="{{ route('users.index') }}" class="btn btn-link">Отмена</a>
        </div>
    </form>
@endsection
