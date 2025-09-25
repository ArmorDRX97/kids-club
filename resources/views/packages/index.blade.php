@extends('layouts.app')
@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0">Пакеты</h1>
        <a href="{{ route('packages.create') }}" class="btn btn-primary">Добавить пакет</a>
    </div>
    <div class="card"><div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead class="table-light"><tr><th>Секция</th><th>Тип</th><th>Параметры</th><th>Цена</th><th></th></tr></thead>
                <tbody>
                @foreach($packages as $p)
                    <tr>
                        <td>{{ $p->section->name }}</td>
                        <td><span class="badge text-bg-info">{{ $p->type }}</span></td>
                        <td>@if($p->type==='visits') {{ $p->visits_count }} посещ.@else {{ $p->days }} дн.@endif</td>
                        <td>{{ number_format($p->price,0,'',' ') }} ₸</td>
                        <td>
                            {{ $p->section->name }}
                            @if($p->section->default_package_id === $p->id)
                                <span class="badge text-bg-success ms-1">по умолчанию</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('packages.edit',$p) }}" class="btn btn-sm btn-outline-secondary">Править</a>
                            <form class="d-inline" method="POST" action="{{ route('packages.destroy',$p) }}" onsubmit="return confirm('Удалить пакет?')">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Удалить</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div></div>
    <div class="mt-3">{{ $packages->links() }}</div>
@endsection
