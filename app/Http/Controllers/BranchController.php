<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount('employees')->get();
        return view('branches.index', compact('branches'));
    }

    public function create() { return view('branches.create'); }

    public function store(Request $request)
    {
        $request->validate(['name'=>'required|max:100', 'city'=>'nullable|max:50', 'region'=>'nullable|max:100']);
        Branch::create($request->all() + ['is_active' => true]);
        return redirect()->route('branches.index')->with('success', 'تم إضافة الفرع');
    }

    public function show(Branch $branch) { return redirect()->route('branches.index'); }

    public function edit(Branch $branch) { return view('branches.edit', compact('branch')); }

    public function update(Request $request, Branch $branch)
    {
        $request->validate(['name'=>'required|max:100', 'city'=>'nullable|max:50', 'region'=>'nullable|max:100']);
        $branch->update($request->all());
        return redirect()->route('branches.index')->with('success', 'تم التحديث');
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();
        return redirect()->route('branches.index')->with('success', 'تم الحذف بنجاح');
    }

    public function destroyAll()
    {
        Branch::truncate();
        return redirect()->route('branches.index')->with('success', 'تم حذف جميع الفروع بنجاح');
    }
}
