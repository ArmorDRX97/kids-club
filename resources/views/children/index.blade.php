@extends('layouts.app')
@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0">Дети</h1>
        <a href="{{ route('children.create') }}" class="btn btn-primary">Добавить</a>
    </div>
    <form class="row g-2 mb-3">
        <div class="col-auto"><input name="q" value="{{ $q }}" class="form-control" placeholder="Поиск (ФИО/телефон)"></div>
        <div class="col-auto form-check mt-2">
            <input class="form-check-input" type="checkbox" id="showDeleted" name="deleted" value="1" {{ $showDeleted ? 'checked' : '' }}>
            <label class="form-check-label" for="showDeleted">Неактивные дети</label>
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-secondary">Применить</button>
        </div>
    </form>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>ФИО</th>
                    <th>Телефон</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($children as $c)
                    <tr>
                        <td><a href="{{ route('children.show',$c) }}" class="link-body-emphasis">{{ $c->last_name }} {{ $c->first_name }}</a></td>
                        <td>{{ $c->parent_phone }}</td>
                        <td class="text-end">
                            <a href="{{ route('children.edit',$c) }}" class="btn btn-sm btn-outline-secondary">Править</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-secondary py-4">Нет данных</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $children->links() }}</div>
@endsection
