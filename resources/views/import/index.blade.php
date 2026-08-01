@extends('layouts.app')
@section('title', 'استيراد بيانات Excel')
@section('page-title', 'استيراد بيانات Excel')

@section('content')

<div class="row g-3">

@if(session('process_batch_id'))
{{-- تنبيه: تم الاستيراد بنجاح، معالجة الإقامات متاحة --}}
<div class="col-12">
    <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.4);border-radius:12px;padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;gap:1rem">
        <div style="display:flex;align-items:center;gap:0.75rem">
            <span style="font-size:1.5rem">✅</span>
            <div>
                <div style="font-weight:700;color:#4ade80">تم الاستيراد بنجاح</div>
                <div style="font-size:0.82rem;color:var(--text-muted);margin-top:0.2rem">
                    الخطوة التالية: اضغط "معالجة الآن" لربط بيانات السائقين بأرقام الإقامة
                </div>
            </div>
        </div>
        <form method="POST" action="{{ route('import.process', session('process_batch_id')) }}" style="flex-shrink:0">
            @csrf
            <button type="submit" class="btn-walim d-flex align-items-center gap-2" style="font-size:0.9rem;padding:0.5rem 1.25rem">
                <i class="bi bi-cpu-fill"></i> معالجة الآن
            </button>
        </form>
    </div>
</div>
@endif


    {{-- ===== فورم الاستيراد ===== --}}
    <div class="col-xl-6">
        <div class="chart-card fade-in">
            <div class="chart-title mb-1">📁 رفع ملف Excel</div>
            <div class="chart-subtitle mb-4">النظام يتعرف على أعمدة كل منصة تلقائياً — بأي ترتيب</div>

            <form method="POST" action="{{ route('import.upload') }}" enctype="multipart/form-data" id="importForm">
                @csrf

                {{-- المنصة --}}
                <div class="mb-3" id="platformContainer">
                    <label class="form-label-dark">المنصة / التطبيق *</label>
                    <select name="platform_id" id="platform_id" class="form-select form-select-dark" required>
                        <option value="">🌍 عام (للسلف والخصومات)</option>
                        <option value="" disabled style="background:#1e293b;color:#94a3b8">──────────</option>
                        @foreach($platforms as $p)
                        <option value="{{ $p->id }}" data-format="{{ $p->report_format ?? 'ninja' }}">
                            {{ $p->name }}
                            @if($p->report_format === 'keeta_slabs') — شرائح
                            @elseif($p->report_format === 'keeta_orders') — طلبات
                            @endif
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- الشهر --}}
                <div class="mb-3">
                    <label class="form-label-dark">الشهر *</label>
                    <input type="month" name="month" class="form-control form-control-dark"
                           value="{{ date('Y-m') }}" required>
                </div>

                {{-- نوع الورقة --}}
                <div class="mb-3">
                    <label class="form-label-dark">نوع الورقة *</label>
                    <select name="sheet_type" id="sheet_type" class="form-select form-select-dark" required>
                        <option value="">اختر نوع البيانات</option>
                        {{-- تقرير التطبيق: خيار واحد، المنصة هي التي تحدد القالب --}}
                        <option value="app_report" id="opt_app_report">📊 تقرير التطبيق النهائي</option>
                        @foreach($sheetGroups as $groupLabel => $types)
                        <optgroup label="{{ $groupLabel }}">
                            @foreach($types as $typeKey => $typeName)
                            <option value="{{ $typeKey }}">{{ $typeName }}</option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                </div>

                {{-- بطاقة معلومات ديناميكية --}}
                <div id="sheetInfoCard" class="mb-3" style="display:none">
                    <div style="background:rgba(37,99,235,0.08);border:1px solid rgba(37,99,235,0.25);border-radius:12px;padding:1rem;font-size:0.82rem">

                        {{-- رأس البطاقة --}}
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div style="color:var(--accent-light);font-weight:700;font-size:0.88rem" id="infoLabel"></div>
                                {{-- تحذير captain_id نص --}}
                                <div id="infoIdWarning" style="display:none;background:rgba(234,179,8,0.12);border-radius:6px;padding:0.3rem 0.6rem;color:#facc15;font-size:0.78rem;margin-top:0.3rem">
                                    ⚠️ captain_id رقم طويل — العمود مُعَرَّف كـ "نص" في القالب تلقائياً
                                </div>
                            </div>
                            <a id="downloadTemplateBtn" href="#" target="_blank"
                               style="display:none"
                               class="btn-walim d-flex align-items-center gap-1"
                               style="padding:0.3rem 0.75rem;font-size:0.78rem;white-space:nowrap">
                                <i class="bi bi-file-earmark-arrow-down"></i> قالب Excel
                            </a>
                        </div>

                        {{-- أعمدة المنصة --}}
                        <div id="colsSection" style="display:none">
                            <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:0.4rem">الأعمدة المتوقعة:</div>
                            <div id="infoColumns" class="d-flex flex-wrap gap-1 mb-2"></div>
                            {{-- مفتاح الألوان --}}
                            <div class="d-flex flex-wrap gap-2 mt-1" style="font-size:0.72rem;color:var(--text-muted)">
                                <span><span style="display:inline-block;width:10px;height:10px;background:#2563EB;border-radius:2px;margin-left:3px"></span> مطلوب للحسبة</span>
                                <span><span style="display:inline-block;width:10px;height:10px;background:#0891B2;border-radius:2px;margin-left:3px"></span> يدخل الحسبة</span>
                                <span><span style="display:inline-block;width:10px;height:10px;background:#78716C;border-radius:2px;margin-left:3px"></span> معلوماتي فقط</span>
                            </div>
                        </div>

                        {{-- ملاحظات --}}
                        <div id="infoNotes" style="color:var(--text-muted);margin-top:0.5rem;line-height:1.8"></div>
                    </div>
                </div>

                {{-- منطقة رفع الملف --}}
                <div class="mb-3">
                    <label class="form-label-dark">الملف *</label>
                    <div id="dropZone" onclick="document.getElementById('fileInput').click()"
                         style="border:2px dashed var(--border);border-radius:12px;padding:2rem;text-align:center;cursor:pointer;transition:all 0.25s;background:var(--bg-hover)">
                        <i class="bi bi-cloud-upload" style="font-size:2rem;color:var(--text-muted);display:block;margin-bottom:0.5rem"></i>
                        <div id="fileLabel" style="font-size:0.875rem;color:var(--text-muted)">اضغط لاختيار ملف أو اسحب وأفلت هنا</div>
                        <small style="color:var(--text-muted)">xlsx, xls, csv — حتى 20MB</small>
                    </div>
                    <input type="file" id="fileInput" name="file" accept=".xlsx,.xls,.csv" class="d-none" required>
                </div>

                <div id="idChangesOptions" class="mb-3" style="display: none; background: rgba(245,158,11,0.08); border-radius: 8px; padding: 1rem; border: 1px solid rgba(245,158,11,0.3);">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="ignore_multiple_ids_warning" id="ignoreMultipleIds" value="1" style="background-color: var(--bg-input); border-color: var(--border);">
                        <label class="form-check-label" for="ignoreMultipleIds" style="color: var(--text-muted); font-size: 0.85rem;">
                            تجاهل تحذير ربط إقامة واحدة بعدة IDs عليها تسويات في هذا الملف (أنا متأكد من أن الموظف يتحمل جميع التسويات/المخالفات).
                        </label>
                    </div>
                </div>
                

                <button type="submit" id="uploadBtn" class="btn-walim w-100">
                    <i class="bi bi-cloud-upload"></i> رفع واستيراد
                </button>
            </form>
        </div>

        {{-- القوالب السريعة --}}
        <div class="chart-card fade-in mt-3">
            <div class="chart-title mb-3">⬇️ تحميل قوالب Excel</div>

            {{-- قوالب تقارير التطبيق (حسب المنصة) --}}
            <div style="font-size:0.72rem;color:var(--text-muted);font-weight:600;margin-bottom:0.5rem;text-transform:uppercase;letter-spacing:0.05em">
                تقارير التطبيق النهائي (حسب المنصة)
            </div>
            <div class="d-flex flex-wrap gap-1 mb-3">
                @foreach($platforms as $p)
                <a href="{{ route('template.platform', $p->id) }}"
                   class="btn-ghost"
                   style="padding:0.25rem 0.6rem;font-size:0.72rem;border-radius:6px"
                   title="تحميل قالب تقرير {{ $p->name }}">
                    <i class="bi bi-file-earmark-excel" style="color:#22c55e"></i>
                    {{ $p->name }}
                </a>
                @endforeach
            </div>

            {{-- قوالب الأوراق الأخرى --}}
            @foreach($sheetGroups as $groupLabel => $types)
            <div style="font-size:0.72rem;color:var(--text-muted);font-weight:600;margin-bottom:0.4rem;text-transform:uppercase;letter-spacing:0.05em">
                {{ $groupLabel }}
            </div>
            <div class="d-flex flex-wrap gap-1 mb-2">
                @foreach($types as $typeKey => $typeName)
                <a href="{{ route('template.sheet', $typeKey) }}"
                   class="btn-ghost"
                   style="padding:0.25rem 0.6rem;font-size:0.72rem;border-radius:6px">
                    <i class="bi bi-file-earmark-excel" style="color:#22c55e"></i>
                    {{ $typeName }}
                </a>
                @endforeach
            </div>
            @endforeach
        </div>
    </div>

    {{-- ===== القوالب ودليل الأعمدة ===== --}}
    <div class="col-xl-6">

        {{-- دليل الأعمدة حسب المنصة --}}
        <div class="chart-card fade-in mt-3">
            <div class="chart-title mb-2">📋 دليل أعمدة كل منصة</div>
            <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:1rem">
                انقر على أي منصة لرؤية الأعمدة المتوقعة والأسماء المقبولة
            </p>

            @foreach($platforms as $p)
            @php $def = \App\Support\PlatformColumnMap::get($p->report_format ?? 'ninja'); @endphp
            <div class="mb-2" style="border:1px solid var(--border);border-radius:10px;overflow:hidden">
                <button class="w-100 text-end d-flex justify-content-between align-items-center"
                        style="background:var(--bg-hover);border:none;padding:0.65rem 0.9rem;color:var(--text-primary);cursor:pointer;font-size:0.84rem;font-weight:600"
                        onclick="toggleRef(this)">
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-chevron-down" style="color:var(--text-muted);transition:transform 0.2s"></i>
                        <a href="{{ route('template.platform', $p->id) }}" onclick="event.stopPropagation()" class="btn-ghost" style="padding:0.15rem 0.5rem;font-size:0.7rem">
                            <i class="bi bi-file-earmark-excel" style="color:#22c55e"></i> قالب
                        </a>
                    </span>
                    <span>
                        {{ $p->name }}
                        @if(!empty($def['id_as_text']) && $def['id_as_text'])
                        <span style="font-size:0.68rem;background:rgba(234,179,8,0.15);color:#facc15;border-radius:4px;padding:0.1rem 0.4rem;margin-right:0.3rem">
                            captain_id نص
                        </span>
                        @endif
                    </span>
                </button>
                <div class="ref-body" style="display:none;padding:0.75rem;font-size:0.78rem">
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        @foreach($def['columns'] ?? [] as $col)
                        @php
                            $isReq  = in_array($col, $def['required'] ?? []);
                            $isCalc = in_array($col, $def['used_in_calc'] ?? []);
                            $isInfo = in_array($col, $def['info_only'] ?? []);
                        @endphp
                        <span style="
                            background:{{ $isReq ? 'rgba(37,99,235,0.2)' : ($isCalc ? 'rgba(8,145,178,0.15)' : ($isInfo ? 'rgba(120,113,108,0.15)' : 'var(--bg-hover)')) }};
                            border:1px solid {{ $isReq ? 'rgba(37,99,235,0.5)' : ($isCalc ? 'rgba(8,145,178,0.4)' : 'var(--border)') }};
                            border-radius:5px;padding:0.15rem 0.45rem;font-family:monospace;
                            font-size:0.76rem;color:{{ $isReq ? '#93c5fd' : ($isInfo ? '#a8a29e' : 'var(--text-secondary)') }}">
                            {{ $col }}
                        </span>
                        @endforeach
                    </div>
                    @if(!empty($def['notes']))
                    <div style="color:var(--text-muted);margin-top:0.5rem">
                        @foreach($def['notes'] as $note)
                        <div>• {{ $note }}</div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- Modal: أعمدة غير معروفة --}}
<div id="unknownColsModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--border);color:var(--text-primary)">
            <div class="modal-header" style="border-color:#f59e0b30;background:rgba(245,158,11,0.08)">
                <h5 class="modal-title" style="color:#f59e0b">⚠️ أعمدة لم تُدرَج في الحسبة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:0.75rem">
                    الأعمدة التالية موجودة في الملف لكنها ليست جزءاً من حسبة الرواتب الحالية.
                    إذا كانت تحتوي على بيانات مهمة (كغرامات أو محافظ)، يُنصح بمراجعتها.
                </p>
                <div id="unknownColsList"></div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: أخطاء الاستيراد --}}
<div id="errorsModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--border);color:var(--text-primary)">
            <div class="modal-header" style="border-color:var(--border)">
                <h5 class="modal-title">⚠️ أخطاء وملاحظات الاستيراد</h5>
                <div class="d-flex align-items-center gap-2">
                    <a id="downloadErrorsBtn" href="#" class="btn btn-sm btn-outline-danger" style="display:none; font-size: 0.75rem; padding: 0.2rem 0.5rem;">
                        <i class="bi bi-download"></i> تنزيل السطور
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body" id="errorsBody" style="font-size:0.82rem;font-family:monospace"></div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ===================================================================
// Platform defs (from PHP)
// ===================================================================
const platformDefs = @json($platformDefs);
const otherDefs    = @json($otherDefs);

// ===================================================================
// عند تغيير المنصة أو نوع الورقة → تحديث البطاقة
// ===================================================================
function updateInfoCard() {
    const platformId = document.getElementById('platform_id').value;
    const sheetType  = document.getElementById('sheet_type').value;
    const card       = document.getElementById('sheetInfoCard');

    if (!sheetType) { card.style.display = 'none'; return; }

    let def = null;
    let templateUrl = null;
    let showCols = false;

    const platformContainer = document.getElementById('platformContainer');
    const platformSelect = document.getElementById('platform_id');

    if (sheetType === 'app_report' || sheetType === 'id_changes') {
        platformContainer.style.display = 'block';
        platformSelect.required = true;
        
        // تقرير التطبيق أو تغيير المعرفات → يعتمد على المنصة
        if (!platformId) {
            card.style.display = 'none'; return;
        }
        if (sheetType === 'app_report' && !platformDefs[platformId]) {
            card.style.display = 'none'; return;
        }
        
        if (sheetType === 'app_report') {
            def         = platformDefs[platformId];
            templateUrl = `/templates/platform/${platformId}`;
            showCols    = true;
        } else {
            def         = otherDefs[sheetType];
            templateUrl = `/templates/sheet/${sheetType}`;
            showCols    = false;
        }
    } else {
        // ورقة أخرى (خصومات وغيرها) عامة
        platformContainer.style.display = 'none';
        platformSelect.required = false;
        platformSelect.value = ''; // Clear value
        
        def         = otherDefs[sheetType];
        templateUrl = `/templates/sheet/${sheetType}`;
        showCols    = false;
    }

    if (!def) { card.style.display = 'none'; return; }

    // العنوان
    document.getElementById('infoLabel').textContent = sheetType === 'app_report'
        ? `تقرير التطبيق النهائي — ${document.querySelector('#platform_id option:checked').text.trim()}`
        : def.label;

    // تحذير captain_id نص
    document.getElementById('infoIdWarning').style.display = def.id_text ? 'block' : 'none';

    // خيارات إضافية لـ id_changes
    document.getElementById('idChangesOptions').style.display = sheetType === 'id_changes' ? 'block' : 'none';

    // زر القالب
    const dlBtn = document.getElementById('downloadTemplateBtn');
    dlBtn.href  = templateUrl;
    dlBtn.style.display = 'flex';

    // الأعمدة (فقط لتقرير التطبيق)
    const colsSection = document.getElementById('colsSection');
    if (showCols && def.columns) {
        colsSection.style.display = 'block';
        const container = document.getElementById('infoColumns');
        container.innerHTML = '';
        def.columns.forEach(col => {
            const isReq  = def.required.includes(col);
            const isCalc = (def.used_in_calc || []).includes(col);
            const isInfo = (def.info_only || []).includes(col);

            const bg    = isReq ? 'rgba(37,99,235,0.2)' : isCalc ? 'rgba(8,145,178,0.15)' : isInfo ? 'rgba(120,113,108,0.15)' : 'var(--bg-hover)';
            const border= isReq ? 'rgba(37,99,235,0.5)' : isCalc ? 'rgba(8,145,178,0.4)' : 'var(--border)';
            const color = isReq ? '#93c5fd' : isInfo ? '#a8a29e' : 'var(--text-secondary)';

            const span = document.createElement('span');
            span.style.cssText = `background:${bg};border:1px solid ${border};border-radius:5px;padding:0.15rem 0.45rem;font-family:monospace;font-size:0.76rem;color:${color};white-space:nowrap`;
            span.textContent = col;
            container.appendChild(span);
        });
    } else {
        colsSection.style.display = 'none';
    }

    // الملاحظات
    const notesEl = document.getElementById('infoNotes');
    const notes = def.notes || [];
    notesEl.innerHTML = notes.map(n => `<div style="font-size:0.78rem">• ${n}</div>`).join('');

    card.style.display = 'block';
}

document.getElementById('platform_id').addEventListener('change', updateInfoCard);
document.getElementById('sheet_type').addEventListener('change', updateInfoCard);

// ===================================================================
// Drag & Drop
// ===================================================================
const dropZone  = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const fileLabel = document.getElementById('fileLabel');

fileInput.addEventListener('change', function() {
    if (this.files[0]) {
        fileLabel.innerHTML = `<strong style="color:var(--success)">✓ ${this.files[0].name}</strong><br>
            <small style="color:var(--text-muted)">${(this.files[0].size/1024/1024).toFixed(2)} MB</small>`;
        dropZone.style.borderColor = 'var(--success)';
    }
});
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor = 'var(--accent)'; dropZone.style.background = 'rgba(37,99,235,0.05)'; });
dropZone.addEventListener('dragleave', () => { dropZone.style.borderColor = 'var(--border)'; dropZone.style.background = 'var(--bg-hover)'; });
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.style.borderColor = 'var(--border)'; dropZone.style.background = 'var(--bg-hover)';
    if (e.dataTransfer.files[0]) { fileInput.files = e.dataTransfer.files; fileInput.dispatchEvent(new Event('change')); }
});

// ===================================================================
// Submit
// ===================================================================
document.getElementById('importForm').addEventListener('submit', function(e) {
    const sheetType  = document.getElementById('sheet_type').value;
    const platformId = document.getElementById('platform_id').value;

    if (!sheetType) { e.preventDefault(); alert('الرجاء اختيار نوع الورقة'); return; }
    if ((sheetType === 'app_report' || sheetType === 'id_changes') && !platformId) { 
        e.preventDefault(); 
        alert('الرجاء اختيار المنصة أولاً'); 
        return; 
    }

    const btn = document.getElementById('uploadBtn');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> جاري الرفع...';
    btn.disabled  = true;
});

// ===================================================================
// Accordion
// ===================================================================
function toggleRef(btn) {
    const body = btn.nextElementSibling;
    const icon = btn.querySelector('.bi');
    const isOpen = body.style.display !== 'none';
    body.style.display = isOpen ? 'none' : 'block';
    if (icon) { icon.className = isOpen ? 'bi bi-chevron-down' : 'bi bi-chevron-up'; }
}

// ===================================================================
// Modals
// ===================================================================
function showUnknownCols(cols) {
    const list = document.getElementById('unknownColsList');
    list.innerHTML = cols.map(c =>
        `<div style="background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);border-radius:6px;padding:0.4rem 0.75rem;margin-bottom:0.4rem;font-family:monospace;color:#fbbf24">
            📌 ${c}
        </div>`
    ).join('');
    new bootstrap.Modal(document.getElementById('unknownColsModal')).show();
}

function showErrors(batchId, errors) {
    const body = document.getElementById('errorsBody');
    body.innerHTML = errors.map((e, i) => {
        let isWarning = e.message && (e.message.includes('تنبيه') || e.message.includes('تحذير'));
        let bg = isWarning ? 'rgba(245,158,11,0.08)' : 'rgba(239,68,68,0.08)';
        let title = isWarning ? 'ملاحظة' : 'خطأ';
        let icon = isWarning ? 'bi-exclamation-triangle text-warning' : 'bi-x-circle text-danger';
        return `<div style="margin-bottom:0.4rem;padding:0.4rem;background:${bg};border-radius:6px">
            <i class="bi ${icon} me-1"></i> <strong>${title} ${i+1}:</strong> ${e.message || JSON.stringify(e)}
        </div>`;
    }).join('');
    
    const dlBtn = document.getElementById('downloadErrorsBtn');
    if (batchId && errors.some(e => e.row)) {
        dlBtn.href = `/import/${batchId}/export-errors`;
        dlBtn.style.display = 'inline-block';
    } else {
        dlBtn.style.display = 'none';
    }
    
    new bootstrap.Modal(document.getElementById('errorsModal')).show();
}
</script>
@endpush
