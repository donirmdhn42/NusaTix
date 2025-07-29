<?php
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/models/film.php';
require_once __DIR__ . '/../backend/models/schedule.php';
require_once __DIR__ . '/../backend/auth.php'; 
require_once __DIR__ . '/../backend/helpers/functions.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: auth_user.php?mode=login');
    exit();
}

// Ambil ID film dari URL
$film_id = intval($_GET['id'] ?? 0);
if ($film_id === 0) {
    die("Film tidak valid.");
}

// Ambil detail film dari database
$film = getFilmById($conn, $film_id);
if (!$film) {
    die("Film tidak ditemukan.");
}

// Ambil semua jadwal yang tersedia untuk film ini
$schedules = getSchedulesByFilmId($conn, $film_id);

// Kelompokkan jadwal berdasarkan tanggal
$grouped_schedules = [];
foreach ($schedules as $schedule) {
    $date = date('Y-m-d', strtotime($schedule['show_date']));
    if (!isset($grouped_schedules[$date])) {
        $grouped_schedules[$date] = [];
    }
    $grouped_schedules[$date][] = $schedule;
}

// Fungsi helper untuk format tanggal Indonesia
function formatIndonesianDateParts($date)
{
    $days = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $timestamp = strtotime($date);
    return [
        'dayName' => $days[date('w', $timestamp)],
        'dayNumber' => date('d', $timestamp),
        'monthName' => $months[date('n', $timestamp) - 1]
    ];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Film: <?= htmlspecialchars($film['title']) ?> - NusaTix</title>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin="" />
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'" href="https://fonts.googleapis.com/css2?display=swap&family=Be+Vietnam+Pro%3Awght%4400%3B500%3B700%3B900&family=Noto+Sans%3Awght%4400%3B500%3B700%3B900" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Mengilangkan scrollbar visual pada kontainer tanggal */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>

<body class="bg-[#181111]" style='font-family: "Be Vietnam Pro", "Noto Sans", sans-serif;'>
    <div class="relative flex size-full min-h-screen flex-col bg-[#181111] dark group/design-root overflow-x-hidden">
        <div class="layout-container flex h-full grow flex-col">

            <?php include __DIR__ . '/templates/header.php'; ?>

            <main class="flex flex-1 justify-center py-8 md:py-10">
                <div class="layout-content-container flex flex-col w-full max-w-5xl px-4">

                    <section class="flex flex-row gap-5 md:gap-8 mb-12 items-start">
                        <div class="w-1/3 sm:w-1/4 flex-shrink-0">
                            <div class="w-full bg-center bg-no-repeat aspect-[3/4] bg-cover rounded-xl shadow-lg border border-[#382929]" style='background-image: url("../uploads/posters/<?= htmlspecialchars($film['poster'] ?? 'default.jpg') ?>");'></div>
                        </div>

                        <div class="w-2/3 sm:w-3/4 flex-1 flex flex-col gap-2 md:gap-4 text-white">
                            <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold leading-tight text-white"><?= htmlspecialchars($film['title']) ?></h1>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-gray-400 text-sm">
                                <span class="border border-gray-600 rounded-md px-2 py-1 text-xs font-semibold"><?= htmlspecialchars($film['genre']) ?></span>
                                <span><i class="fas fa-clock mr-1"></i> <?= htmlspecialchars($film['duration']) ?> menit</span>
                            </div>
                            <div class="hidden md:block bg-[#211717] p-5 rounded-lg border border-gray-700/50 mt-2">
                                <h3 class="font-bold text-lg mb-2 text-red-500">Sinopsis</h3>
                                <p class="text-gray-300 text-base leading-relaxed"><?= nl2br(htmlspecialchars($film['description'])) ?></p>
                            </div>
                            <div class="mt-2 text-gray-400 text-sm space-y-2">
                                <p><strong class="font-semibold text-white w-24 inline-block">Sutradara</strong>: <?= htmlspecialchars($film['director']) ?></p>
                                <p><strong class="font-semibold text-white w-24 inline-block">Tanggal Rilis</strong>: <?= date('d F Y', strtotime($film['release_date'])) ?></p>
                            </div>
                        </div>
                    </section>

                    <section class="md:hidden -mt-8 mb-10">
                        <div class="bg-[#211717] p-5 rounded-lg border border-gray-700/50 mt-2">
                            <h3 class="font-bold text-lg mb-2 text-red-500">Sinopsis</h3>
                            <p class="text-gray-300 text-base leading-relaxed"><?= nl2br(htmlspecialchars($film['description'])) ?></p>
                        </div>
                    </section>

                    <section>
                        <h2 class="text-white text-3xl font-bold mb-6">Jadwal Tayang</h2>
                        <?php if (empty($grouped_schedules)): ?>
                            <div class="text-center py-16 bg-[#211717] rounded-lg border border-gray-700">
                                <i class="fas fa-calendar-times fa-4x text-gray-600"></i>
                                <p class="text-gray-400 mt-4">Jadwal untuk film ini belum tersedia.</p>
                            </div>
                        <?php else: ?>
                            <div class="relative group">
                                <button id="scroll-left-btn" class="absolute left-0 top-1/2 -translate-y-1/2 z-10 w-10 h-10 bg-black/50 hover:bg-black/80 rounded-full text-white items-center justify-center transition-opacity duration-300 opacity-0 group-hover:opacity-100 hidden md:flex">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <div id="date-scroller" class="overflow-x-auto no-scrollbar mb-6 pb-2">
                                    <div class="flex flex-nowrap space-x-3">
                                        <?php foreach ($grouped_schedules as $date => $schedules_on_date):
                                            $date_parts = formatIndonesianDateParts($date);
                                        ?>
                                            <button data-date="<?= $date ?>" class="date-tab flex-shrink-0 text-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out border-2 border-transparent w-24 hover:bg-red-600 transition-all duration-200 group transform hover:scale-105">
                                                <span class="text-xs font-bold uppercase"><?= $date_parts['dayName'] ?></span>
                                                <span class="text-2xl font-extrabold block"><?= $date_parts['dayNumber'] ?></span>
                                                <span class="text-xs uppercase"><?= $date_parts['monthName'] ?></span>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <button id="scroll-right-btn" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-10 h-10 bg-black/50 hover:bg-black/80 rounded-full text-white items-center justify-center transition-opacity duration-300 opacity-0 group-hover:opacity-100 hidden md:flex">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>

                            <div id="schedule-content">
                                <?php foreach ($grouped_schedules as $date => $schedules_on_date): ?>
                                    <div id="schedule-<?= $date ?>" class="schedule-pane hidden transition-opacity duration-300">
                                        <?php
                                        $schedules_by_studio = [];
                                        foreach ($schedules_on_date as $schedule) {
                                            $schedules_by_studio[$schedule['studio_name']][] = $schedule;
                                        }
                                        ?>
                                        <?php foreach ($schedules_by_studio as $studio_name => $studio_schedules): ?>
                                            <div class="mb-6">
                                                <h3 class="text-xl font-bold text-red-400 mb-3"><?= htmlspecialchars($studio_name) ?></h3>
                                                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3">
                                                    <?php foreach ($studio_schedules as $schedule): ?>
                                                        <a href="booking_user.php?schedule_id=<?= $schedule['id_schedule'] ?>" class="block text-center bg-[#382929] text-white p-3 rounded-lg shadow-md hover:bg-red-600 transition-all duration-200 group transform hover:scale-105">
                                                            <p class="font-bold text-xl"><?= date('H:i', strtotime($schedule['show_time'])) ?></p>
                                                            <p class="text-xs text-gray-400 group-hover:text-white mt-1">Rp<?= number_format($schedule['price'], 0, ',', '.') ?></p>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </main>
            <?php include __DIR__ . '/templates/footer.php'; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // === Logika Tab Tanggal ===
            const dateTabs = document.querySelectorAll('.date-tab');
            const schedulePanes = document.querySelectorAll('.schedule-pane');
            if (dateTabs.length > 0) {
                function setActiveTab(tab) {
                    dateTabs.forEach(t => {
                        t.classList.remove('bg-red-600', 'text-white', 'border-red-500');
                        t.classList.add('bg-[#211717]', 'text-gray-400');
                    });
                    tab.classList.add('bg-red-600', 'text-white', 'border-red-500');
                    tab.classList.remove('bg-[#211717]', 'text-gray-400');
                }

                function showPane(pane) {
                    schedulePanes.forEach(p => p.classList.add('hidden'));
                    if (pane) pane.classList.remove('hidden');
                }
                setActiveTab(dateTabs[0]);
                showPane(schedulePanes[0]);
                dateTabs.forEach(tab => {
                    tab.addEventListener('click', function() {
                        setActiveTab(this);
                        const targetPane = document.getElementById(`schedule-${this.dataset.date}`);
                        showPane(targetPane);
                    });
                });
            }

            // === Logika Tombol Geser (Slider) ===
            const scroller = document.getElementById('date-scroller');
            const btnLeft = document.getElementById('scroll-left-btn');
            const btnRight = document.getElementById('scroll-right-btn');

            if (scroller) {
                const scrollAmount = 200; // Jarak geser per klik

                function updateScrollButtons() {
                    // Cek apakah bisa scroll ke kiri
                    if (scroller.scrollLeft > 0) {
                        btnLeft.classList.remove('hidden');
                        btnLeft.classList.add('md:flex');
                    } else {
                        btnLeft.classList.add('hidden');
                        btnLeft.classList.remove('md:flex');
                    }
                    // Cek apakah bisa scroll ke kanan
                    const maxScrollLeft = scroller.scrollWidth - scroller.clientWidth;
                    if (scroller.scrollLeft < maxScrollLeft - 1) { // -1 untuk toleransi
                        btnRight.classList.remove('hidden');
                        btnRight.classList.add('md:flex');
                    } else {
                        btnRight.classList.add('hidden');
                        btnRight.classList.remove('md:flex');
                    }
                }

                btnLeft.addEventListener('click', () => {
                    scroller.scrollBy({
                        left: -scrollAmount,
                        behavior: 'smooth'
                    });
                });
                btnRight.addEventListener('click', () => {
                    scroller.scrollBy({
                        left: scrollAmount,
                        behavior: 'smooth'
                    });
                });

                scroller.addEventListener('scroll', updateScrollButtons);

                // Panggil sekali saat halaman dimuat untuk set state awal
                // Timeout kecil untuk memastikan layout sudah selesai di-render
                setTimeout(updateScrollButtons, 100);
            }
        });
    </script>
</body>

</html>