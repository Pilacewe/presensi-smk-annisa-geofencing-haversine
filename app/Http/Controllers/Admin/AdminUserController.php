<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $role = $request->get('role');

        $users = User::query()
            ->when($q, fn($q1) => $q1->where('name','like',"%$q%")->orWhere('email','like',"%$q%"))
            ->when($role, fn($q2) => $q2->where('role',$role))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email'=> 'required|email|unique:users',
            'password'=>'required|min:6',
            'role' => 'required|in:admin,guru,tu,piket,kepsek',
        ]);

        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('admin.users.index')->with('success','User berhasil ditambahkan');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email'=> 'required|email|unique:users,email,'.$user->id,
            'role' => 'required|in:admin,guru,tu,piket,kepsek',
            'password' => 'nullable|min:6',
        ]);

        if ($data['password'] ?? false) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success','User berhasil diperbarui');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success','User berhasil dihapus');
    }
}
