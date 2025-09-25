@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-3">Редактировать комнату</h1>

    <form method="POST" action="{{ route('rooms.update', $room) }}" class="card card-body">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Название<span class="text-danger">*</span></label>
                <input name="name" class="form-control" value="{{ old('name', $room->name) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Номер</label>
                <input name="number_label" class="form-control" value="{{ old('number_label', $room->number_label) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Вместимость</label>
                <input type="number" min="1" name="capacity" class="form-control"
                       value="{{ old('capacity', $room->capacity) }}">
            </div>
            <div class="col-12">
                <label class="form-label">Спецификация</label>
                <textarea name="spec" rows="4" class="form-control">{{ old('spec', $room->spec) }}</textarea>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary">Сохранить</button>
            <a href="{{ route('rooms.index') }}" class="btn btn-link">Отмена</a>
        </div>
    </form>
@endsection
