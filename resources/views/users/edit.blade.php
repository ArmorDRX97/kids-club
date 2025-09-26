@extends('layouts.app')
@section('content')
    <h1 class="h4 mb-3">Правка пользователя</h1>
    <form method="POST" action="{{ route('users.update',$user) }}" class="card card-body">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">ФИО</label><input name="name" class="form-control" value="{{ old('name',$user->name) }}" required></div>
            <div class="col-md-6"><label class="form-label">Логин (e‑mail)</label><input type="email" name="email" class="form-control" value="{{ old('email',$user->email) }}" required></div>
            <div class="col-md-4"><label class="form-label">Телефон</label><input name="phone" class="form-control" value="{{ old('phone',$user->phone) }}"></div>
            <div class="col-md-4"><label class="form-label">Новый пароль (если менять)</label><input type="password" name="password" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Повтор пароля</label><input type="password" name="password_confirmation" class="form-control"></div>
            @php
                $role = $user->getRoleNames()->first();
            @endphp
            <div class="col-md-4"><label class="form-label">Роль</label>
                @if($role === \App\Models\User::ROLE_ADMIN)
                    <input type="hidden" name="role" value="Admin">
                    <div class="form-control-plaintext">Admin</div>
                    <div class="form-text">Роль администратора нельзя изменить.</div>
                @else
                    <select name="role" class="form-select" required>
                        <option value="Receptionist" @selected($role==='Receptionist')>Receptionist</option>
                    </select>
                @endif
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary">Сохранить</button>
            <a href="{{ route('users.index') }}" class="btn btn-link">Отмена</a>
        </div>
    </form>
@endsection
