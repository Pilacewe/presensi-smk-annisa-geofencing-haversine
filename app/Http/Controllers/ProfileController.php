<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Tampilkan form profil pengguna.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update informasi profil pengguna (nama, email, jabatan, avatar).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Validasi tambahan di luar ProfileUpdateRequest (jabatan & avatar)
        $request->validate([
            'jabatan' => ['nullable', 'string', 'max:100'],
            'avatar'  => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5000'],
        ]);

        // Field standar dari ProfileUpdateRequest (biasanya name & email)
        $user->fill($request->validated());

        // Tambahan: jabatan
        $user->jabatan = $request->input('jabatan');

        // Upload avatar (opsional)
        if ($request->hasFile('avatar')) {
            // Hapus avatar lama jika ada
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $path = $request->file('avatar')->store('avatars', 'public'); // storage/app/public/avatars
            $user->avatar_path = $path;
        }

        // Jika email berubah, wajib verifikasi ulang
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Hapus akun pengguna.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Hapus avatar file jika ada
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
