@extends('layouts.app')
@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0">Секции и подсекции</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('sections.create') }}" class="btn btn-primary">Добавить секцию</a>
        </div>
    </div>
    @foreach($parents as $p)
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-primary" style="font-weight: 700;">Секция: {{ $p->name }}</div>
                        <div class="small text-secondary">Комната: {{ $p->room? ($p->room->name.' ('.$p->room->number_label.')') : '—' }}</div>
                        <div class="mt-1">
                            <span class="badge text-bg-primary">Детей: {{ $counts[$p->id] ?? 0 }}</span>
                            @if($p->defaultPackage)
                                <span class="badge text-bg-info ms-2">Пакет: {{ $p->defaultPackage->type }}@if($p->defaultPackage->visits_count) ({{ $p->defaultPackage->visits_count }} пос.)@endif @if($p->defaultPackage->days) ({{ $p->defaultPackage->days }} дн.)@endif — {{ number_format($p->defaultPackage->price,0,'',' ') }} ₸</span>
                            @else
                                <span class="badge text-bg-warning ms-2">Пакет не выбран</span>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="mt-2">
                            <a href="{{ route('sections.edit',$p) }}" class="btn btn-sm btn-outline-secondary">Править</a>
                            <a href="{{ route('sections.members.index',$p) }}" class="btn btn-sm btn-primary">Дети</a>
                        </div>
                    </div>
                </div>
                @if($p->children->count())
                    <div class="mt-3">
                        <div class="small text-secondary mb-1">Подсекции:</div>
                        <div class="row g-2">
                            @foreach($p->children as $c)
                                <div class="small text-secondary">Комната: {{ $c->room? ($c->room->name.' ('.$c->room->number_label.')') : '—' }}</div>
                                <div class="mt-1"><span class="badge text-bg-primary">Детей: {{ $counts[$c->id] ?? 0 }}</span></div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
@endsection
