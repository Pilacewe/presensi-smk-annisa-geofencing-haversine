<?php

namespace App\Http\Controllers\Kepsek;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        return view('kepsek.account', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $u = $request->user();

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email,'.$u->id,
            'jabatan'  => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6',
        ]);

        if (!empty($data['password'])) $data['password'] = Hash::make($data['password']);
        else unset($data['password']);

        $u->update($data);

        return back()->with('ok','Profil berhasil diperbarui.');
    }
}
