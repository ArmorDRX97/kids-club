@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-3">Правка ребёнка</h1>

    <form method="POST" action="{{ route('children.update', $child) }}" class="card card-body">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Фамилия</label>
                <input name="last_name" class="form-control" value="{{ old('last_name', $child->last_name) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Имя</label>
                <input name="first_name" class="form-control" value="{{ old('first_name', $child->first_name) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Отчество</label>
                <input name="patronymic" class="form-control" value="{{ old('patronymic', $child->patronymic) }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Дата рождения</label>
                <input type="date" name="dob" class="form-control" value="{{ old('dob', optional($child->dob)->format('Y-m-d')) }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Телефон ребёнка</label>
                <input name="child_phone" class="form-control" value="{{ old('child_phone', $child->child_phone) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Телефон родителя</label>
                <input name="parent_phone" class="form-control" value="{{ old('parent_phone', $child->parent_phone) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Телефон второго родителя</label>
                <input name="parent2_phone" class="form-control" value="{{ old('parent2_phone', $child->parent2_phone) }}">
            </div>

            <div class="col-12">
                <label class="form-label">Заметки</label>
                <textarea name="notes" rows="3" class="form-control">{{ old('notes', $child->notes) }}</textarea>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary">Сохранить</button>
            <a href="{{ route('children.index') }}" class="btn btn-link">Отмена</a>
        </div>
    </form>

    <hr>
    <div class="d-flex align-items-center justify-content-between">
        <div>
            @if(!$child->is_active)
                <span class="badge text-bg-warning">Ребёнок неактивен</span>
            @endif
        </div>
        <div>
            @if($child->is_active)
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deactivateModal">Сделать неактивным</button>
            @else
                <form method="POST" action="{{ route('children.activate', $child) }}" class="d-inline">
                    @csrf
                    <button class="btn btn-success">Сделать активным</button>
                </form>
            @endif
        </div>
    </div>

    <div class="modal fade" id="deactivateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('children.deactivate', $child) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Подтверждение</h5>
                        <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
                    </div>
                    <div class="modal-body">
                        <p>Вы уверены, что хотите сделать ребёнка неактивным? Он не будет доступен в поиске и отметках посещений.</p>
                        @php
                            $hasActivePaid = $child->enrollments()->where('status', 'paid')->where(function ($q) {
                                $q->where(function ($w) {
                                    $w->whereNotNull('visits_left')->where('visits_left', '>', 0);
                                })->orWhere(function ($w) {
                                    $w->whereNull('visits_left')->where(function ($w2) {
                                        $w2->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                                    });
                                });
                            })->exists();
                        @endphp
                        @if($hasActivePaid)
                            <div class="alert alert-warning mb-0">Внимание: у ребёнка есть активный оплаченный пакет!</div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Отмена</button>
                        <button class="btn btn-danger">Подтвердить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
