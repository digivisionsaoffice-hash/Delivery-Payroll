<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->latest()->paginate(20);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role'     => 'required|exists:roles,name',
        ]);
        $user = User::create(['name'=>$data['name'],'email'=>$data['email'],'password'=>bcrypt($data['password'])]);
        $user->assignRole($data['role']);
        return redirect()->route('users.index')->with('success', 'تم إضافة المستخدم');
    }

    public function show(User $user) { return redirect()->route('users.index'); }
    public function edit(User $user) { $roles = Role::all(); return view('users.edit', compact('user', 'roles')); }

    public function update(Request $request, User $user)
    {
        $data = $request->validate(['name'=>'required','email'=>'required|email|unique:users,email,'.$user->id,'role'=>'required|exists:roles,name']);
        $user->update(['name'=>$data['name'],'email'=>$data['email']]);
        $user->syncRoles([$data['role']]);
        if ($request->password) $user->update(['password'=>bcrypt($request->password)]);
        return redirect()->route('users.index')->with('success', 'تم التحديث');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) return back()->with('error', 'لا يمكن حذف حسابك');
        $user->delete();
        return redirect()->route('users.index')->with('success', 'تم الحذف');
    }
}
