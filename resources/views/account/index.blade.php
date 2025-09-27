@extends('layouts.app')
@section('content')
    <h1 class="h4 mb-3">Личный кабинет</h1>
    <div class="card"><div class="card-body">
            <h5 class="card-title">Смена пароля</h5>
            <form method="POST" action="{{ route('account.password') }}" class="row g-3">
                @csrf
                <div class="col-md-4"><label class="form-label">Текущий пароль</label><input type="password" name="current_password" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Новый пароль</label><input type="password" name="password" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Повтор пароля</label><input type="password" name="password_confirmation" class="form-control" required></div>
                <div class="col-12"><button class="btn btn-primary">Обновить пароль</button></div>
            </form>
        </div></div>

        <hr>
        <form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="btn btn-danger">
        Выйти
    </button>
</form>
@endsection
