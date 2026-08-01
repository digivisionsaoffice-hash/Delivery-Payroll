@extends('layouts.app')

@section('title', 'أداة تنظيف المعرفات المتكررة')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">أداة تنظيف المعرفات المتكررة</h4>
            <p class="text-muted mb-0">الموظفون المرتبطون بأكثر من Captain ID في نفس الوقت.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card card-custom">
        <div class="card-body p-0">
            @if($grouped->isEmpty())
                <div class="text-center p-5">
                    <div class="empty-state-icon mx-auto mb-3" style="width: 64px; height: 64px; background: var(--bg-hover); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--accent);">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h5>النظام نظيف تماماً!</h5>
                    <p class="text-muted">لا يوجد أي موظف مرتبط بأكثر من Captain ID.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>الموظف</th>
                                <th>رقم الإقامة</th>
                                <th>المعرفات (Captain IDs) المرتبطة</th>
                                <th class="text-end">الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($grouped as $employeeId => $records)
                                @php $employee = $records->first()->employee; @endphp
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $employee->name_en ?? $employee->name_ar }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $employee->iqama_number }}</span>
                                    </td>
                                    <td>
                                        <ul class="list-unstyled mb-0">
                                            @foreach($records as $record)
                                                <li class="mb-2 p-2 border rounded bg-light">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong class="text-primary">{{ $record->captain_id }}</strong> 
                                                            <span class="text-muted" style="font-size: 0.85rem;">({{ $record->id_name ?? 'بدون اسم' }})</span>
                                                            <br>
                                                            <small class="text-muted"><i class="bi bi-calendar"></i> {{ $record->start_date ? $record->start_date->format('Y-m-d') : 'غير محدد' }}</small>
                                                        </div>
                                                        <form action="{{ route('tools.cleanup.remove', $record->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من فك ارتباط هذا المعرف؟ سيتم إرجاع أي طلبات مرتبطة به إلى حالة غير معالجة.')">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="فك الارتباط وحذف المعرف">
                                                                <i class="bi bi-trash"></i> فك الارتباط
                                                            </button>
                                                        </form>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('employees.show', $employee->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="bi bi-person-lines-fill"></i> ملف الموظف
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
