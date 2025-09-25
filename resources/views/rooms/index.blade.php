@extends('layouts.app')
@section('content')
    <div class="d-flex justify-content-between mb-3">
        <h1 class="h4">Комнаты</h1>
        <a href="{{ route('rooms.create') }}" class="btn btn-primary">Добавить</a>
    </div>
    <div class="card"><div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead class="table-light"><tr><th>Название</th><th>№</th><th>Вместимость</th><th>Спецификация</th><th></th></tr></thead>
                <tbody>
                @foreach($rooms as $r)
                    <tr>
                        <td>{{ $r->name }}</td>
                        <td>{{ $r->number_label }}</td>
                        <td>{{ $r->capacity }}</td>
                        <td class="text-truncate" style="max-width:300px">{{ $r->spec }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('rooms.edit',$r) }}">Править</a>
                            <form class="d-inline" method="POST" action="{{ route('rooms.destroy',$r) }}" onsubmit="return confirm('Удалить комнату?')">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Удалить</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div></div>
    <div class="mt-3">{{ $rooms->links() }}</div>
@endsection
