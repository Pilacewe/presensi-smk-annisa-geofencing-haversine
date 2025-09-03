<?php
// app/Http/Controllers/Admin/Account/AdminAccountController.php

namespace App\Http\Controllers\Admin\Account;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class AdminAccountController extends Controller
{
    public function index()
{
    $u = auth()->user();

    $settings = [
        'jam_target_masuk' => Setting::get('presensi.jam_target_masuk','07:00'),
        'jam_masuk_start'  => Setting::get('presensi.jam_masuk_start','05:00'),
        'jam_keluar_start' => Setting::get('presensi.jam_keluar_start','16:00'),
        'radius'           => Setting::get('presensi.radius','150'),
        'office_lat'       => Setting::get('presensi.office_lat','-6.200000'),
        'office_lng'       => Setting::get('presensi.office_lng','106.816666'),
        'timezone'         => Setting::get('app.timezone', config('app.timezone','Asia/Jakarta')),
        'notif_email_on_izin' => (bool) Setting::get('notif.email_on_izin', true),
        'digest_hour'      => (int)  Setting::get('notif.digest_hour', 17),
    ];

    // ===== Deteksi link storage: support Windows junction & Unix symlink
    $pub = public_path('storage');
    $target = storage_path('app/public');

    $exists   = file_exists($pub);
    $isSyml   = is_link($pub);                              // symlink (Unix/macOS)
    $samePath = $exists && realpath($pub) === realpath($target); // junction/symlink pointing correctly

    $storageOk  = ($isSyml || $samePath) && is_dir($pub);
    $storageMsg = $storageOk
        ? 'Link publik aktif. File bisa diakses melalui /storage/...'
        : ($exists
            ? 'Folder "public/storage" ada namun tidak menunjuk ke storage/app/public. Hapus folder tersebut lalu jalankan: php artisan storage:link'
            : 'Link belum dibuat. Jalankan: php artisan storage:link');

    $sessionDriver   = config('session.driver');
    $canListSessions = $sessionDriver === 'database';

    return view('admin.account.index', compact(
        'u','settings','storageOk','storageMsg','canListSessions'
    ));
}


    public function updateProfile(Request $r)
    {
        $u = auth()->user();
        $data = $r->validate([
            'name'  => ['required','string','max:100'],
            'email' => ['required','email','max:150','unique:users,email,'.$u->id],
        ]);
        $u->update($data);
        return back()->with('success','Profil berhasil diperbarui.');
    }

    public function updateAvatar(Request $r)
    {
        $u = auth()->user();
        $r->validate(['avatar' => ['required','image','mimes:jpg,jpeg,png,webp','max:2048']]);
        $path = $r->file('avatar')->store('avatars','public');

        if ($u->avatar_path && Storage::disk('public')->exists($u->avatar_path)) {
            Storage::disk('public')->delete($u->avatar_path);
        }
        $u->avatar_path = $path;
        $u->save();
        return back()->with('success','Foto profil diperbarui.');
    }

    public function deleteAvatar()
    {
        $u = auth()->user();
        if ($u->avatar_path && Storage::disk('public')->exists($u->avatar_path)) {
            Storage::disk('public')->delete($u->avatar_path);
        }
        $u->avatar_path = null;
        $u->save();
        return back()->with('success','Foto profil dihapus.');
    }

    public function updatePassword(Request $r)
    {
        $u = auth()->user();
        $r->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);
        if (! Hash::check($r->current_password, $u->password)) {
            return back()->withErrors(['current_password'=>'Password saat ini tidak cocok.'])->withInput();
        }
        $u->password = Hash::make($r->password);
        $u->save();
        return back()->with('success','Password berhasil diubah.');
    }

    public function updateSettings(Request $r)
    {
        $data = $r->validate([
            // Presensi
            'jam_target_masuk' => ['required','date_format:H:i'],
            'jam_masuk_start'  => ['required','date_format:H:i'],
            'jam_keluar_start' => ['required','date_format:H:i'],
            'radius'           => ['required','numeric','min:50','max:1000'],
            'office_lat'       => ['required','numeric','between:-90,90'],
            'office_lng'       => ['required','numeric','between:-180,180'],
            // Preferensi
            'timezone'         => ['required','string','max:64'],
            'notif_email_on_izin' => ['nullable','boolean'],
            'digest_hour'      => ['required','integer','min:0','max:23'],
        ]);

        // Simpan presensi
        Setting::set('presensi.jam_target_masuk', $data['jam_target_masuk']);
        Setting::set('presensi.jam_masuk_start',  $data['jam_masuk_start']);
        Setting::set('presensi.jam_keluar_start', $data['jam_keluar_start']);
        Setting::set('presensi.radius',           $data['radius']);
        Setting::set('presensi.office_lat',       $data['office_lat']);
        Setting::set('presensi.office_lng',       $data['office_lng']);

        // Simpan preferensi
        Setting::set('app.timezone',              $data['timezone']);
        Setting::set('notif.email_on_izin',       (int)($data['notif_email_on_izin'] ?? 0));
        Setting::set('notif.digest_hour',         (int)$data['digest_hour']);

        return back()->with('success','Pengaturan aplikasi & preferensi tersimpan.');
    }

    public function endOtherSessions(Request $r)
    {
        $r->validate(['password' => ['required']]);
        $u = auth()->user();

        if (! Hash::check($r->password, $u->password)) {
            return back()->withErrors(['password'=>'Password tidak cocok.'])->withInput();
        }

        // Logout device lain (jaga-jaga kalau pakai file/redis, tetap aman dipanggil)
        Auth::logoutOtherDevices($r->password);

        return back()->with('success','Sesi di perangkat lain diakhiri.');
    }
}
