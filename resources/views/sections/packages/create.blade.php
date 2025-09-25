@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-3">Новый пакет для секции «{{ $section->name }}»</h1>
    <form action="{{ route('sections.packages.store', $section) }}" method="POST" class="card card-body">
        @include('sections.packages._form')
        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Сохранить</button>
            <a href="{{ route('sections.packages.index', $section) }}" class="btn btn-link">Отмена</a>
        </div>
    </form>
@endsection
