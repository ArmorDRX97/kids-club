@extends('layouts.app')
@section('content')
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card card-kpi"><div class="card-body">
                    <div class="text-secondary">Всего детей</div>
                    <div class="kpi">{{ \App\Models\Child::count() }}</div>
                </div></div>
        </div>
        <div class="col-md-4">
            <div class="card card-kpi"><div class="card-body">
                    <div class="text-secondary">Секции/подсекции</div>
                    <div class="kpi">{{ \App\Models\Section::count() }}</div>
                </div></div>
        </div>
        <div class="col-md-4">
            <div class="card card-kpi"><div class="card-body">
                    <div class="text-secondary">Активные пакеты</div>
                    <div class="kpi">{{ \App\Models\Enrollment::where('status','!=','expired')->count() }}</div>
                </div></div>
        </div>
    </div>
@endsection
