@extends('layouts.app')
@section('title','المستخدمون')
@section('page-title','إدارة المستخدمين')
@section('content')
<div class="d-flex justify-content-between mb-4 fade-in">
    <div class="chart-title">مستخدمو النظام</div>
    <a href="{{ route('users.create') }}" class="btn-walim"><i class="bi bi-plus-lg"></i> مستخدم جديد</a>
</div>
<div class="chart-card fade-in">
    <div class="table-responsive">
        <table class="table walim-table">
            <thead><tr><th>الاسم</th><th>البريد</th><th>الدور</th><th>تاريخ الانضمام</th><th></th></tr></thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td class="fw-bold">{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td><span class="status-badge badge-done">{{ $user->getRoleNames()->first() ?? '—' }}</span></td>
                    <td>{{ $user->created_at->format('Y/m/d') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('users.edit', $user) }}" class="icon-btn" style="width:28px;height:28px"><i class="bi bi-pencil" style="font-size:0.78rem"></i></a>
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('حذف المستخدم؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="icon-btn" style="width:28px;height:28px;color:var(--danger)"><i class="bi bi-trash" style="font-size:0.78rem"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">لا يوجد مستخدمون</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $users->links() }}</div>
</div>
@endsection
