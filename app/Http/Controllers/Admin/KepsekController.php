<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class KepsekController extends Controller
{
    // GET /admin/kepsek
    public function index()
    {
        // hanya satu kepsek
        $kepsek = User::where('role','kepsek')->orderBy('id')->first();
        return view('admin.kepsek.index', compact('kepsek'));
    }

    // GET /admin/kepsek/create
    public function create()
    {
        // cegah lebih dari satu akun (opsional tapi recommended)
        if (User::where('role','kepsek')->exists()) {
            return redirect()->route('admin.kepsek.index')
                ->with('ok','Akun Kepsek sudah ada. Silakan edit akun yang ada.');
        }

        return view('admin.kepsek.form', [
            'mode'   => 'create',
            'user'   => new User(['jabatan' => 'Kepala Sekolah', 'is_active' => 1]),
            'action' => route('admin.kepsek.store'),
            'method' => 'POST',
        ]);
    }

    // POST /admin/kepsek
    public function store(Request $request)
    {
        $request->validate([
            'name'     => ['required','string','max:120'],
            'email'    => ['required','email','max:190','unique:users,email'],
            'jabatan'  => ['nullable','string','max:120'],
            'password' => ['required','string','min:6','confirmed'],
        ]);

        User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'jabatan'   => $request->jabatan ?: 'Kepala Sekolah',
            'role'      => 'kepsek',
            'is_active' => 1,
            'password'  => Hash::make($request->password),
        ]);

        return redirect()->route('admin.kepsek.index')->with('ok','Akun Kepsek berhasil dibuat.');
    }

    // GET /admin/kepsek/{kepsek}/edit
    public function edit(User $kepsek)
    {
        abort_if($kepsek->role !== 'kepsek', 404);

        return view('admin.kepsek.form', [
            'mode'   => 'edit',
            'user'   => $kepsek,
            'action' => route('admin.kepsek.update', $kepsek),
            'method' => 'PATCH',
        ]);
    }

    // PATCH /admin/kepsek/{kepsek}
    public function update(Request $request, User $kepsek)
    {
        abort_if($kepsek->role !== 'kepsek', 404);

        $data = $request->validate([
            'name'      => ['required','string','max:120'],
            'email'     => ['required','email','max:190', Rule::unique('users','email')->ignore($kepsek->id)],
            'jabatan'   => ['nullable','string','max:120'],
            'password'  => ['nullable','string','min:6','confirmed'],
            'is_active' => ['nullable','in:0,1'],
        ]);

        $kepsek->name      = $data['name'];
        $kepsek->email     = $data['email'];
        $kepsek->jabatan   = $data['jabatan'] ?? $kepsek->jabatan;

        if (array_key_exists('is_active',$data)) {
            $kepsek->is_active = (int)$data['is_active'];
        }
        if (!empty($data['password'])) {
            $kepsek->password = Hash::make($data['password']);
        }

        $kepsek->save();

        return redirect()->route('admin.kepsek.index')->with('ok','Akun Kepsek diperbarui.');
    }

    // DELETE /admin/kepsek/{kepsek}
    public function destroy(User $kepsek)
    {
        abort_if($kepsek->role !== 'kepsek', 404);
        $kepsek->delete();
        return back()->with('ok','Akun Kepsek dihapus.');
    }

    // POST /admin/kepsek/{user}/reset-password
    public function resetPassword(Request $request, User $user)
    {
        abort_if($user->role !== 'kepsek', 404);

        $request->validate([
            'new_password' => ['required','string','min:6','confirmed'],
        ]);

        $user->password = Hash::make($request->new_password);
        $user->save();

        return back()->with('ok','Password Kepsek telah direset.');
    }
}
