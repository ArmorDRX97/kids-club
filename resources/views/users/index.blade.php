@extends('layouts.app')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Пользователи</h1>
        <a href="{{ route('users.create') }}" class="btn btn-primary">Добавить</a>
    </div>
    <form class="row g-2 mb-3">
        <div class="col-auto"><input class="form-control" name="q" value="{{ $q }}" placeholder="Поиск"></div>
        <div class="col-auto"><button class="btn btn-outline-secondary">Найти</button></div>
    </form>
    <div class="card"><div class="table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead class="table-light"><tr><th>ФИО</th><th>Логин (e‑mail)</th><th>Телефон</th><th>Роль</th><th></th></tr></thead>
                <tbody>
                @foreach($users as $u)
                    <tr>
                        <td>{{ $u->name }}</td>
                        <td>{{ $u->email }}</td>
                        <td>{{ $u->phone }}</td>
                        <td>{{ $u->getRoleNames()->implode(', ') }}</td>
                        <td class="text-end">
                            <a href="{{ route('users.edit',$u) }}" class="btn btn-sm btn-outline-secondary">Править</a>
                            @if(auth()->id() !== $u->id)
                                <form class="d-inline" method="POST" action="{{ route('users.destroy',$u) }}" onsubmit="return confirm('Удалить пользователя?')">@csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Удалить</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div></div>
    <div class="mt-3">{{ $users->links() }}</div>
@endsection
