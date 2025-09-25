@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('sections.index') }}" class="btn btn-link px-0">← Назад к секциям</a>
            <h1 class="h4 mb-0">Пакеты секции «{{ $section->name }}»</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('sections.edit', $section) }}" class="btn btn-outline-secondary">Настройки секции</a>
            <a href="{{ route('sections.packages.create', $section) }}" class="btn btn-primary">Добавить пакет</a>
        </div>
    </div>

    @if($packages->isEmpty())
        <div class="alert alert-info">Для этой секции ещё не создано пакетов. Нажмите «Добавить пакет», чтобы создать первый.</div>
    @else
        <div class="row g-3">
            @foreach($packages as $package)
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold">{{ $package->name }}</div>
                                    <div class="text-secondary small">{{ $package->billing_type === 'visits' ? 'По занятиям' : 'По времени' }}</div>
                                </div>
                                <span class="badge {{ $package->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $package->is_active ? 'Активен' : 'Скрыт' }}</span>
                            </div>

                            <dl class="row small mt-3 mb-0 flex-grow-1">
                                <dt class="col-6">Стоимость</dt>
                                <dd class="col-6 text-end">{{ number_format($package->price, 0, '', ' ') }} ₸</dd>

                                @if($package->billing_type === 'visits')
                                    <dt class="col-6">Занятий</dt>
                                    <dd class="col-6 text-end">{{ $package->visits_count }}</dd>
                                @else
                                    <dt class="col-6">Длительность</dt>
                                    <dd class="col-6 text-end">{{ $package->days }} дн.</dd>
                                @endif

                                @if($package->description)
                                    <dt class="col-12">Описание</dt>
                                    <dd class="col-12 text-secondary">{{ $package->description }}</dd>
                                @endif
                            </dl>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-between">
                            <a href="{{ route('sections.packages.edit', [$section, $package]) }}" class="btn btn-sm btn-outline-primary">Изменить</a>
                            <form method="POST" action="{{ route('sections.packages.destroy', [$section, $package]) }}" onsubmit="return confirm('Удалить пакет?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Удалить</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
