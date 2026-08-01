@extends('layouts.app')
@section('title','الخصومات والسلف')
@section('page-title','الخصومات والسلف')
@section('content')
<div class="row g-3">
    @foreach([
        ['السلف النقدية','deductions.advances','bi-cash','kpi-blue'],
        ['المخالفات','deductions.violations','bi-exclamation-triangle-fill','kpi-red'],
    ] as [$l,$r,$ic,$c])
    <div class="col-md-4 fade-in">
        <a href="{{ route($r) }}" style="text-decoration:none">
            <div class="kpi-card {{ $c }}" style="cursor:pointer;text-align:center;padding:2rem">
                <div class="kpi-icon" style="margin:0 auto 1rem;width:52px;height:52px;font-size:1.4rem"><i class="bi {{ $ic }}"></i></div>
                <div class="kpi-value" style="font-size:1.1rem">{{ $l }}</div>
            </div>
        </a>
    </div>
    @endforeach
</div>
@endsection
