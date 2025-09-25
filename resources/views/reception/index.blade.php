@extends('layouts.app')


@foreach($sections as $s)
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between flex-wrap gap-2 align-items-start">
                <div>
                    <div class="h5 mb-1">{{ $s->name }} @if($s->parent) <span class="text-secondary">→ {{ $s->parent->name }}</span> @endif</div>
                    <div class="small text-secondary">
                        Комната: {{ $s->room? ($s->room->name.' ('.$s->room->number_label.')') : '—' }}
                        · Тип расписания: {{ $s->schedule_type==='weekly'?'по дням недели':'по дням месяца' }}
                    </div>
                </div>
            </div>


            @php
                // Для разработки: соберём список детей по прикреплениям
                $enrs = \App\Models\Enrollment::with('child')
                ->where('section_id',$s->id)
                ->whereHas('child', fn($q)=>$q->where('is_active',true))
                ->orderByDesc('started_at');
                $page = request()->integer('p_'.$s->id, 1);
                $per = 10;
                $total = (clone $enrs)->count();
                $items = (clone $enrs)->skip(($page-1)*$per)->take($per)->get();
            @endphp


            <div class="mt-3">
                @foreach($items as $e)
                    @php
                        $child = $e->child; if(!$child) continue;
                        $today = now()->toDateString();
                        $already = \App\Models\Attendance::where('child_id',$child->id)->where('section_id',$s->id)->where('attended_on',$today)->exists();
                    @endphp
                    <form class="d-flex justify-content-between align-items-center border rounded p-2 mb-2" method="POST" action="{{ route('reception.mark') }}">
                        @csrf
                        <div>
                            <div class="fw-semibold">{{ $child->full_name }}</div>
                            <div class="small text-secondary">
                                Пакет: {{ $e->package->name }}
                                @if($e->package->billing_type === 'visits' && $e->package->visits_count)
                                    · {{ $e->package->visits_count }} занятий
                                @elseif($e->package->billing_type === 'period' && $e->package->days)
                                    · {{ $e->package->days }} дн.
                                @endif
                                @if($e->visits_left!==null)
                                    — осталось {{ $e->visits_left }}
                                @else
                                    — до {{ optional($e->expires_at)->format('d.m.Y') }}
                                @endif
                            </div>
                        </div>
                        <input type="hidden" name="child_id" value="{{ $child->id }}">
                        <input type="hidden" name="section_id" value="{{ $s->id }}">
                        <button class="btn btn-sm {{ $already ? 'btn-outline-secondary' : 'btn-primary' }}" {{ $already ? 'disabled' : '' }}>
                            {{ $already ? 'Уже отмечен' : 'Пришёл' }}
                        </button>
                    </form>
                @endforeach


                {{-- пагинация по 10 строк на секцию (простейшая) --}}
                @if($total>$per)
                    <div class="d-flex gap-2">
                        @for($i=1;$i<=ceil($total/$per);$i++)
                            <a class="btn btn-sm {{ $i==$page? 'btn-primary' : 'btn-outline-primary' }}" href="?p_{{ $s->id }}={{ $i }}#sec{{ $s->id }}">{{ $i }}</a>
                        @endfor
                    </div>
                @endif
            </div>
        </div>
    </div>
@endforeach
@endsection
