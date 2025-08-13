<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Presensi</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 font-sans antialiased">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-md">
            <div class="p-4 text-center font-bold text-lg border-b">
                Admin Panel
            </div>
            <nav class="p-4">
                <ul class="space-y-2">
                    <li><a href="/admin/dashboard" class="block py-2 px-4 rounded hover:bg-gray-100">Dashboard</a></li>
                    <li><a href="#" class="block py-2 px-4 rounded hover:bg-gray-100">Riwayat Presensi</a></li>
                    <li><a href="#" class="block py-2 px-4 rounded hover:bg-gray-100">Manajemen Pegawai</a></li>
                    <li><a href="#" class="block py-2 px-4 rounded hover:bg-gray-100">Laporan</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Content -->
        <main class="flex-1 p-6">
            @yield('content')
        </main>
    </div>

</body>
</html>
