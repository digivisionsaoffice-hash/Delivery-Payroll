@extends('layouts.app')
@section('title','الإعدادات')
@section('page-title','إعدادات النظام')
@section('content')
<div class="row g-3">
    <div class="col-xl-4">
        <div class="chart-card fade-in">
            <div class="chart-title mb-4">📊 إحصائيات النظام</div>
            @foreach([['الموظفون',$stats['employees'],'bi-people-fill'],['المنصات',$stats['platforms'],'bi-shop'],['الفروع',$stats['branches'],'bi-geo-alt-fill']] as [$l,$v,$ic])
            <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid var(--border)">
                <div style="width:32px;height:32px;background:var(--bg-hover);border-radius:8px;display:flex;align-items:center;justify-content:center">
                    <i class="bi {{ $ic }}" style="color:var(--accent-light)"></i>
                </div>
                <div style="flex:1" class="text-muted" style="font-size:0.875rem">{{ $l }}</div>
                <div class="fw-bold" style="font-size:1.1rem">{{ $v }}</div>
            </div>
            @endforeach
        </div>
    </div>
    <div class="col-xl-8">
        <div class="chart-card fade-in">
            <div class="chart-title mb-3">🔗 روابط سريعة</div>
            <div class="row g-2">
                @foreach([
                    ['المنصات','platforms.index','bi-shop'],
                    ['الفروع','branches.index','bi-geo-alt'],
                    ['المستخدمون','users.index','bi-person-lock'],
                    ['استيراد','import.index','bi-cloud-upload'],
                ] as [$l,$r,$ic])
                <div class="col-6">
                    <a href="{{ route($r) }}" class="btn-ghost w-100 justify-content-center">
                        <i class="bi {{ $ic }}"></i> {{ $l }}
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
