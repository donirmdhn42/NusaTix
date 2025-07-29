<?php
// admin/dashboard.php
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/models/Booking.php';

// Memuat header admin (light mode)
require_once __DIR__ . '/templates/header.php';

// --- Mengambil Data Statistik ---
$stats = [];
$result_bookings = $conn->query("SELECT COUNT(id_booking) as total FROM bookings WHERE status IN ('paid', 'pending', 'booked')");
$stats['total_bookings'] = $result_bookings->fetch_assoc()['total'] ?? 0;
$result_films = $conn->query("SELECT COUNT(id_film) as total FROM films WHERE status = 'now_showing'");
$stats['total_films'] = $result_films->fetch_assoc()['total'] ?? 0;
$result_users = $conn->query("SELECT COUNT(id_user) as total FROM users WHERE role = 'user'");
$stats['total_users'] = $result_users->fetch_assoc()['total'] ?? 0;
$result_revenue = $conn->query("SELECT SUM(total_amount) as total FROM bookings WHERE status = 'paid'");
$stats['total_revenue'] = $result_revenue->fetch_assoc()['total'] ?? 0;

$top_films = Booking::getTopSellingFilms($conn, 5);
$latest_bookings = Booking::getAllForAdmin($conn);
$top_film_tickets = !empty($top_films) ? $top_films[0]['tickets_sold'] : 1;
?>

<div class="p-6 md:p-8">
    <div class="space-y-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Dashboard</h1>
            <p class="text-slate-500 mt-1">Ringkasan aktivitas di platform NusaTix.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white p-6 rounded-2xl border border-slate-200 flex justify-between items-center">
                <div>
                    <p class="text-sm font-semibold text-slate-500 uppercase">Total Booking</p>
                    <p class="text-3xl font-bold text-slate-900 mt-1"><?= number_format($stats['total_bookings']); ?></p>
                </div>
                <div class="w-12 h-12 flex items-center justify-center rounded-full bg-blue-100 text-blue-600"><i class="fas fa-receipt text-xl"></i></div>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-slate-200 flex justify-between items-center">
                <div>
                    <p class="text-sm font-semibold text-slate-500 uppercase">Film Tayang</p>
                    <p class="text-3xl font-bold text-slate-900 mt-1"><?= number_format($stats['total_films']); ?></p>
                </div>
                <div class="w-12 h-12 flex items-center justify-center rounded-full bg-emerald-100 text-emerald-600"><i class="fas fa-clapperboard text-xl"></i></div>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-slate-200 flex justify-between items-center">
                <div>
                    <p class="text-sm font-semibold text-slate-500 uppercase">Total Pengguna</p>
                    <p class="text-3xl font-bold text-slate-900 mt-1"><?= number_format($stats['total_users']); ?></p>
                </div>
                <div class="w-12 h-12 flex items-center justify-center rounded-full bg-amber-100 text-amber-600"><i class="fas fa-users text-xl"></i></div>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-slate-200 flex justify-between items-center">
                <div>
                    <p class="text-sm font-semibold text-slate-500 uppercase">Pendapatan</p>
                    <p class="text-3xl font-bold text-slate-900 mt-1">Rp <?= number_format($stats['total_revenue'], 0, ',', '.'); ?></p>
                </div>
                <div class="w-12 h-12 flex items-center justify-center rounded-full bg-violet-100 text-violet-600"><i class="fas fa-sack-dollar text-xl"></i></div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 bg-white p-6 rounded-2xl border border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900 mb-5">Film Terlaris Bulan Ini</h2>
                <div class="space-y-5">
                    <?php if (empty($top_films)): ?>
                        <p class="text-center py-4 text-slate-500">Belum ada data penjualan.</p>
                    <?php else: ?>
                        <?php foreach ($top_films as $index => $film): 
                            $progress = ($film['tickets_sold'] / $top_film_tickets) * 100;
                        ?>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <p class="font-semibold text-slate-800 truncate pr-4"><?= $index + 1 ?>. <?= htmlspecialchars($film['title']); ?></p>
                                <p class="text-sm font-medium text-slate-500"><?= htmlspecialchars($film['tickets_sold']); ?> Tiket</p>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-1.5">
                                <div class="bg-primary h-1.5 rounded-full" style="width: <?= $progress ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-slate-900">Booking Terbaru</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="py-3 px-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Pengguna</th>
                                <th class="py-3 px-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Film & Jadwal</th>
                                <th class="py-3 px-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Total</th>
                                <th class="py-3 px-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-700">
                            <?php if (empty($latest_bookings)): ?>
                                <tr><td colspan="4" class="text-center py-8 text-slate-500">Belum ada data booking.</td></tr>
                            <?php else: ?>
                                <?php foreach ($latest_bookings as $booking): ?>
                                <tr class="border-t border-slate-200 hover:bg-slate-50">
                                    <td class="py-3 px-4 align-top">
                                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($booking['user_name']); ?></p>
                                        <p class="text-xs text-slate-500"><?= htmlspecialchars($booking['user_email'] ?? ''); ?></p>
                                    </td>
                                    <td class="py-3 px-4 align-top">
                                        <p class="font-semibold text-slate-800 truncate max-w-xs"><?= htmlspecialchars($booking['film_title']); ?></p>
                                        <p class="text-xs text-slate-500"><?= date('d M Y, H:i', strtotime($booking['show_date'] . ' ' . $booking['show_time'])); ?></p>
                                    </td>
                                    <td class="py-3 px-4 align-top font-medium text-slate-800">
                                        Rp<?= number_format($booking['total_amount'], 0, ',', '.') ?>
                                    </td>
                                    <td class="py-3 px-4 align-top">
                                        <?php
                                            $status = $booking['status'];
                                            $colorClass = 'bg-slate-100 text-slate-600';
                                            if ($status == 'paid') $colorClass = 'bg-emerald-100 text-emerald-700';
                                            if ($status == 'pending') $colorClass = 'bg-amber-100 text-amber-700';
                                            if ($status == 'booked') $colorClass = 'bg-blue-100 text-blue-700';
                                            if ($status == 'cancelled') $colorClass = 'bg-rose-100 text-rose-700';
                                        ?>
                                        <span class="text-xs font-bold py-1 px-3 rounded-full <?= $colorClass ?>"><?= ucfirst($status); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>