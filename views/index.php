<?php
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/models/film.php';
require_once __DIR__ . '/../backend/models/testimonial.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mengambil data film
$films = getAllFilms($conn);
$now_showing_films = array_filter($films, fn($film) => $film['status'] === 'now_showing');
$coming_soon_films = array_filter($films, fn($film) => $film['status'] === 'coming_soon');
$genres = array_filter(array_unique(array_column($now_showing_films, 'genre')));
sort($genres);

// NOTE: Ensure your `getAllTestimonials` function also selects `film.id_film`.
// Example query might be:
// SELECT t.*, u.name as user_name, f.title as film_title, f.id_film
// FROM testimonials t
// JOIN users u ON t.user_id = u.id_user
// JOIN films f ON t.film_id = f.id_film
// WHERE t.is_approved = 1 ORDER BY t.created_at DESC LIMIT ?
$testimonials = Testimonial::getAllTestimonials($conn, 5);

$recommended_films = getRecommendedFilms($conn, 8);
$has_coming_soon = !empty($coming_soon_films);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NusaTix - Nonton Jadi Gampang</title>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin="" />
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'" href="https://fonts.googleapis.com/css2?display=swap&family=Be+Vietnam+Pro%3Awght%4400%3B500%3B700%3B900&family=Noto+Sans%3Awght%4400%3B500%3B700%3B900" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries,line-clamp"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .genre-btn.active {
            background-color: #e92932;
            color: white;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .film-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .film-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        .manual-slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            width: 2rem;
            height: 2rem;
            background-color: rgba(0, 0, 0, 0.6);
            border-radius: 9999px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
            cursor: pointer;
        }

        @media (min-width: 768px) {
            .manual-slider-btn {
                width: 2.5rem;
                height: 2.5rem;
            }
        }

        .manual-slider-btn:hover {
            background-color: rgba(0, 0, 0, 0.8);
        }

        .manual-slider-btn.left-0 {
            left: 0.5rem;
        }

        .manual-slider-btn.right-0 {
            right: 0.5rem;
        }

        .reminder-btn.active {
            background-color: #16a34a;
            color: white;
            border-color: #16a34a;
        }

        .reminder-btn.active .fa-bell {
            display: none;
        }

        .reminder-btn:not(.active) .fa-check {
            display: none;
        }

        details>summary {
            list-style: none;
        }

        details>summary::-webkit-details-marker {
            display: none;
        }

        details[open] summary .fa-chevron-down {
            transform: rotate(180deg);
        }

        .swiper-button-next,
        .swiper-button-prev {
            color: white;
            background-color: rgba(0, 0, 0, 0.3);
            width: 44px;
            height: 44px;
            border-radius: 50%;
        }

        .swiper-button-next:after,
        .swiper-button-prev:after {
            font-size: 1.25rem;
        }

        .swiper-pagination-bullet-active {
            background-color: #e92932 !important;
        }

        .swiper-slide {
            height: auto;
        }

        .coming-soon-card {
            height: 100%;
        }
        
        .testimonial-card-link {
            display: block;
            height: 100%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
        }
        
        .testimonial-card-link:hover .testimonial-card {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(233, 41, 50, 0.15);
            border-color: rgba(233, 41, 50, 0.4);
        }

        .testimonial-card {
             height: 100%;
             transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        }
    </style>
</head>

<body class="bg-[#181111]" style='font-family: "Be Vietnam Pro", "Noto Sans", sans-serif;'>
    <div class="relative flex size-full min-h-screen flex-col bg-[#181111] dark group/design-root overflow-x-hidden">
        <div class="layout-container flex h-full grow flex-col">

            <?php include __DIR__ . '/templates/header.php'; ?>

            <main class="flex flex-1 justify-center py-5">
                <div class="layout-content-container flex flex-col w-full gap-6 md:gap-10">

                    <div class="px-4 md:px-10 lg:px-20">
                        <div class="swiper hero-slider rounded-xl">
                            <div class="swiper-wrapper">
                                <div class="swiper-slide">
                                    <div class="flex min-h-[350px] md:min-h-[450px] flex-col gap-6 bg-cover bg-center bg-no-repeat p-6 md:p-8 justify-center" style='background-image: linear-gradient(to top, rgba(24,17,17,0.9) 0%, rgba(24,17,17,0.1) 50%), url("../backend/uploads/posters/Image_fx (12).jpg");'>
                                        <div class="text-center">
                                            <h1 class="text-white text-3xl md:text-5xl font-black max-w-2xl mx-auto">Nonton Jadi Gampang, Tiket Tinggal Klik</h1>
                                            <p class="text-gray-300 mt-4 max-w-xl mx-auto">Pesan tiket film favoritmu tanpa antre, kapan saja dan di mana saja.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="flex min-h-[350px] md:min-h-[450px] flex-col gap-6 bg-cover bg-center bg-no-repeat p-6 md:p-8 justify-center" style="background-image: linear-gradient(to right, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.2) 100%), url('../backend/uploads/posters/Image_fx (9).jpg');">
                                        <div class="text-center">
                                            <h2 class="text-white text-3xl md:text-5xl font-black max-w-2xl mx-auto">Promo Spesial Kemerdekaan</h2>
                                            <p class="text-gray-300 mt-4 max-w-xl mx-auto">Rayakan 17 Agustus bersama NusaTix dan nikmati diskon 17% untuk semua penayangan sepanjang bulan kemerdekaan.</p>
                                            <div class="mt-6 inline-flex items-center gap-4 bg-black/30 backdrop-blur-sm border border-white/20 p-2.5 rounded-lg">
                                                <span class="text-white">Kode: <strong class="tracking-widest text-yellow-500">MERDEKA17</strong></span>
                                                <button class="copy-promo-btn text-white hover:text-yellow-500" title="Salin Kode" data-promo-code="MERDEKA17"><i class="far fa-copy"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="flex min-h-[350px] md:min-h-[450px] flex-col gap-6 bg-cover bg-center bg-no-repeat p-6 md:p-8 justify-center" style="background-image: linear-gradient(to right, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.2) 100%), url('../backend/uploads/posters/Image_fx (14).jpg');">
                                        <div class="text-center">
                                            <h2 class="text-white text-3xl md:text-5xl font-black max-w-2xl mx-auto">Promo Pengguna Baru</h2>
                                            <p class="text-gray-300 mt-4 max-w-xl mx-auto">Dapatkan diskon 30% untuk pembelian tiket pertamamu di NusaTix.</p>
                                            <div class="mt-6 inline-flex items-center gap-4 bg-black/30 backdrop-blur-sm border border-white/20 p-2.5 rounded-lg">
                                                <span class="text-white">Kode: <strong class="tracking-widest text-yellow-500">NUSABARU</strong></span>
                                                <button class="copy-promo-btn text-white hover:text-yellow-500" title="Salin Kode" data-promo-code="NUSABARU"><i class="far fa-copy"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div>
                        </div>
                    </div>

                    <div class="px-4 md:px-10 lg:px-20">
                        <div class="relative max-w-2xl mx-auto">
                            <input type="search" id="film-search-input" placeholder="Cari film yang sedang tayang..." class="w-full bg-[#211717] border border-gray-700 text-white rounded-full py-3 pl-6 pr-12 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4">
                               <i class="fas fa-search text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pt-1">
                        <h2 id="now-showing-section" class="text-white text-[22px] font-bold px-4 md:px-10 lg:px-20 mb-4">Film Sedang Tayang</h2>
                        <div id="genre-filter-container" class="flex gap-3 px-4 md:px-10 lg:px-20 pb-3 flex-wrap">
                            <button class="genre-btn flex h-8 items-center justify-center rounded-full bg-[#382929] px-4 text-white text-sm font-medium active" data-genre="Semua">Semua</button>
                            <?php foreach ($genres as $genre) : ?>
                                <button class="genre-btn flex h-8 items-center justify-center rounded-full bg-[#382929] px-4 text-white text-sm font-medium" data-genre="<?= htmlspecialchars($genre) ?>"><?= htmlspecialchars($genre) ?></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="relative">
                            <button class="manual-slider-btn left-0" data-target="now-showing-list"><i class="fas fa-chevron-left"></i></button>
                            <div id="now-showing-list" class="slider-container overflow-x-auto no-scrollbar">
                                <div class="slider-track flex items-stretch p-4 gap-4 pl-4 md:pl-10 lg:pl-20">
                                    <?php foreach ($now_showing_films as $film) : ?>
                                        <a href="detail_film.php?id=<?= $film['id_film'] ?>" class="film-card flex-shrink-0 w-[calc(50%-0.5rem)] sm:w-[calc(33.33%-1rem)] md:w-[calc(25%-1rem)] lg:w-[calc(20%-1rem)] flex flex-col gap-3 rounded-lg no-underline" data-genre="<?= htmlspecialchars($film['genre']) ?>" data-title="<?= htmlspecialchars(strtolower($film['title'])) ?>">
                                            <div class="w-full bg-center bg-no-repeat aspect-[3/4] bg-cover rounded-xl" style='background-image: url("../uploads/posters/<?= htmlspecialchars($film['poster'] ?? 'default.jpg') ?>");'></div>
                                            <div>
                                                <p class="text-white text-base font-medium truncate"><?= htmlspecialchars($film['title']) ?></p>
                                                <p class="text-[#b89d9f] text-sm font-normal"><?= htmlspecialchars($film['genre']) ?></p>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <button class="manual-slider-btn right-0" data-target="now-showing-list"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>

                    <?php if ($has_coming_soon) : ?>
                        <div class="py-5">
                            <h2 class="text-white text-[22px] font-bold px-4 md:px-10 lg:px-20 mb-4">Segera Hadir Untuk Anda</h2>
                            <div class="px-4 md:px-10 lg:px-20">
                                <div class="swiper coming-soon-slider">
                                    <div class="swiper-wrapper pb-10">
                                        <?php foreach ($coming_soon_films as $film) : ?>
                                            <div class="swiper-slide">
                                                <div class="coming-soon-card bg-[#211717] rounded-xl overflow-hidden flex flex-row items-center">
                                                    <div class="w-1/3 sm:w-1/4 flex-shrink-0">
                                                        <img src="../uploads/posters/<?= htmlspecialchars($film['poster'] ?? 'default.jpg') ?>" alt="Poster <?= htmlspecialchars($film['title']) ?>" class="w-full h-auto object-cover aspect-[3/4]">
                                                    </div>
                                                    <div class="w-2/3 sm:w-3/4 p-3 sm:p-5 flex flex-col justify-center">
                                                        <span class="text-xs font-bold uppercase text-red-400">Akan Datang</span>
                                                        <h3 class="text-white text-base sm:text-lg md:text-xl font-extrabold mt-1 truncate"><?= htmlspecialchars($film['title']) ?></h3>
                                                        <p class="text-gray-300 text-xs sm:text-sm mt-1 md:mt-2 line-clamp-2 sm:line-clamp-3"><?= htmlspecialchars($film['description']) ?></p>
                                                        <p class="text-gray-400 text-xs sm:text-sm mt-2">Rilis: <strong><?= date('d M Y', strtotime($film['release_date'])) ?></strong></p>
                                                        <div class="mt-3 sm:mt-4">
                                                            <button class="reminder-btn inline-flex items-center justify-center gap-2 h-9 px-3 text-xs sm:h-10 sm:px-5 sm:text-sm rounded-full bg-gray-700 hover:bg-gray-600 text-white font-medium transition-colors border border-transparent" data-film-id="<?= $film['id_film'] ?>" data-film-title="<?= htmlspecialchars($film['title']) ?>">
                                                                <i class="fas fa-bell"></i>
                                                                <i class="fas fa-check"></i>
                                                                <span class="reminder-text">Ingatkan Saya</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="swiper-pagination"></div>
                                    <div class="swiper-button-next"></div>
                                    <div class="swiper-button-prev"></div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="py-5">
                        <h2 class="text-white text-[22px] font-bold px-4 md:px-10 lg:px-20 mb-4">Rekomendasi Untukmu</h2>
                        <div class="relative">
                            <button class="manual-slider-btn left-0" data-target="recommendation-list"><i class="fas fa-chevron-left"></i></button>
                            <div id="recommendation-list" class="slider-container overflow-x-auto no-scrollbar">
                                <div class="slider-track flex items-stretch p-4 gap-4 pl-4 md:pl-10 lg:pl-20">
                                    <?php if (!empty($recommended_films)) : ?>
                                        <?php foreach ($recommended_films as $film) : ?>
                                            <a href="detail_film.php?id=<?= $film['id_film'] ?>" class="film-card flex-shrink-0 w-[calc(50%-0.5rem)] sm:w-[calc(33.33%-1rem)] md:w-[calc(25%-1rem)] lg:w-[calc(20%-1rem)] flex flex-col gap-3 rounded-lg no-underline">
                                                <div class="w-full bg-center bg-no-repeat aspect-[3/4] bg-cover rounded-xl" style='background-image: url("../uploads/posters/<?= htmlspecialchars($film['poster'] ?? 'default.jpg') ?>");'></div>
                                                <div>
                                                    <p class="text-white text-base font-medium truncate"><?= htmlspecialchars($film['title']) ?></p>
                                                    <p class="text-[#b89d9f] text-sm font-normal"><?= htmlspecialchars($film['genre']) ?></p>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button class="manual-slider-btn right-0" data-target="recommendation-list"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                    
                    <?php if (!empty($testimonials)) : ?>
                    <div class="py-5">
                        <h2 class="text-white text-[22px] font-bold px-4 md:px-10 lg:px-20 mb-4">Apa Kata Mereka</h2>
                        <div class="px-4 md:px-10 lg:px-20">
                            <div class="swiper testimonial-slider">
                                <div class="swiper-wrapper pb-10">
                                    <?php foreach ($testimonials as $testimonial) : ?>
                                        <?php
                                            $userName = htmlspecialchars($testimonial['user_name']);
                                            $userInitial = !empty($userName) ? strtoupper(substr($userName, 0, 1)) : '?';
                                        ?>
                                        <div class="swiper-slide h-full">
                                            <a href="detail_film.php?id=<?= $testimonial['id_film'] ?>" class="testimonial-card-link">
                                                <figure class="testimonial-card bg-[#211717] p-6 rounded-lg flex flex-col justify-between border border-gray-800">
                                                    <div class="flex-grow">
                                                        <div class="flex items-center text-yellow-400 mb-3">
                                                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                                                <i class="fas fa-star <?= $i <= $testimonial['rating'] ? 'text-yellow-400' : 'text-gray-600' ?>"></i>
                                                            <?php endfor; ?>
                                                        </div>
                                                        <blockquote class="text-gray-300 italic line-clamp-4 md:line-clamp-5 leading-relaxed">
                                                          "<?= htmlspecialchars($testimonial['message']) ?>"
                                                        </blockquote>
                                                    </div>
                                                    <figcaption class="mt-4 pt-4 border-t border-gray-700 flex items-center gap-4">
                                                        <div class="flex-shrink-0 size-10 rounded-full bg-red-900 flex items-center justify-center text-white font-bold text-lg">
                                                            <?= $userInitial ?>
                                                        </div>
                                                        <div class="text-left">
                                                            <p class="font-bold text-white text-sm"><?= $userName ?></p>
                                                            <p class="text-xs text-gray-400">Ulasan untuk: <?= htmlspecialchars($testimonial['film_title']) ?></p>
                                                        </div>
                                                    </figcaption>
                                                </figure>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="swiper-pagination"></div>
                                <div class="swiper-button-next"></div>
                                <div class="swiper-button-prev"></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="py-5">
                        <h2 class="text-white text-[22px] font-bold px-4 md:px-10 lg:px-20 mb-4">
                            Nikmati Pengalaman Menonton Terbaik
                        </h2>
                        <div class="px-4 md:px-10 lg:px-20 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="relative w-full aspect-[3/1] bg-cover bg-center rounded-xl overflow-hidden transition-transform duration-300 transform hover:-translate-y-1" style="background-image: url('../backend/uploads/posters/Image_fx (5).jpg');">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex items-end p-4">
                                    <div class="z-10">
                                        <h3 class="text-white text-base md:text-lg font-bold">Studio Reguler</h3>
                                        <p class="text-gray-300 text-sm">Tempat duduk nyaman, tata suara jernih, dan layar lebar yang ideal untuk pengalaman nonton sehari-hari.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="relative w-full aspect-[3/1] bg-cover bg-center rounded-xl overflow-hidden transition-transform duration-300 transform hover:-translate-y-1" style="background-image: url('../backend/uploads/posters/Image_fx (6).jpg');">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex items-end p-4">
                                    <div class="z-10">
                                        <h3 class="text-white text-base md:text-lg font-bold">Studio Premiere</h3>
                                        <p class="text-gray-300 text-sm">Kursi kulit premium dengan ruang kaki lebih luas serta suasana privat untuk kenyamanan ekstra.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="relative w-full aspect-[3/1] bg-cover bg-center rounded-xl overflow-hidden transition-transform duration-300 transform hover:-translate-y-1" style="background-image: url('../backend/uploads/posters/Image_fx (7).jpg');">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex items-end p-4">
                                    <div class="z-10">
                                        <h3 class="text-white text-base md:text-lg font-bold">Loket & Kasir</h3>
                                        <p class="text-gray-300 text-sm">Pelayanan cepat dan ramah untuk pembelian tiket secara langsung maupun pengambilan tiket online.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="relative w-full aspect-[3/1] bg-cover bg-center rounded-xl overflow-hidden transition-transform duration-300 transform hover:-translate-y-1" style="background-image: url('../backend/uploads/posters/Image_fx (8).jpg');">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex items-end p-4">
                                    <div class="z-10">
                                        <h3 class="text-white text-base md:text-lg font-bold">Tampak Depan NusaTix</h3>
                                        <p class="text-gray-300 text-sm">Desain modern dan ikonik dari luar bioskop yang langsung menarik perhatian pengunjung.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="py-5">
                        <h2 class="text-white text-[22px] font-bold px-4 md:px-10 lg:px-20 mb-6 text-center">(FAQ)</h2>
                        <div class="px-4 md:px-10 lg:px-20 max-w-4xl mx-auto space-y-4">
                            <details class="bg-[#211717] p-4 rounded-lg cursor-pointer">
                                <summary class="flex justify-between items-center font-medium text-white">
                                    Bagaimana cara memesan tiket? 
                                    <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300"></i>
                                </summary>
                                <p class="text-gray-300 mt-3 text-sm">Pilih film yang ingin ditonton, klik "Pesan Tiket", pilih jadwal dan kursi yang tersedia, lalu lanjutkan ke proses pembayaran. Tiket elektronik akan dikirimkan ke email Anda setelah pembayaran berhasil.</p>
                            </details>
                            <details class="bg-[#211717] p-4 rounded-lg cursor-pointer">
                                <summary class="flex justify-between items-center font-medium text-white">
                                    Metode pembayaran apa saja yang diterima? 
                                    <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300"></i>
                                </summary>
                                <p class="text-gray-300 mt-3 text-sm">Kami menerima pembayaran melalui transfer bank, kartu kredit/debit, serta dompet digital populer seperti GoPay, OVO, dan DANA.</p>
                            </details>
                            <details class="bg-[#211717] p-4 rounded-lg cursor-pointer">
                                <summary class="flex justify-between items-center font-medium text-white">
                                    Apakah tiket bisa dibatalkan atau diubah jadwal? 
                                    <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300"></i>
                                </summary>
                                <p class="text-gray-300 mt-3 text-sm">Tiket yang sudah dibeli tidak dapat dibatalkan atau diubah jadwalnya. Mohon pastikan pilihan Anda sudah benar sebelum melakukan pembayaran.</p>
                            </details>
                            <details class="bg-[#211717] p-4 rounded-lg cursor-pointer">
                                <summary class="flex justify-between items-center font-medium text-white">
                                    Bagaimana cara mendapatkan promo atau diskon? 
                                    <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300"></i>
                                </summary>
                                <p class="text-gray-300 mt-3 text-sm">Promo dan diskon dapat ditemukan pada halaman promo kami atau melalui kode promo khusus yang diberikan pada event tertentu. Masukkan kode promo saat melakukan pembayaran untuk mendapatkan potongan harga.</p>
                            </details>
                            <details class="bg-[#211717] p-4 rounded-lg cursor-pointer">
                                <summary class="flex justify-between items-center font-medium text-white">
                                    Apakah saya harus membuat akun untuk membeli tiket? 
                                    <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300"></i>
                                </summary>
                                <p class="text-gray-300 mt-3 text-sm">Untuk kemudahan dan keamanan, kami menyarankan Anda membuat akun terlebih dahulu. Namun, pembelian tiket juga dapat dilakukan sebagai tamu tanpa mendaftar.</p>
                            </details>
                            <details class="bg-[#211717] p-4 rounded-lg cursor-pointer">
                                <summary class="flex justify-between items-center font-medium text-white">
                                    Apakah saya bisa memesan tiket untuk orang lain? 
                                    <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300"></i>
                                </summary>
                                <p class="text-gray-300 mt-3 text-sm">Ya, Anda dapat memesan tiket untuk orang lain dengan menggunakan email mereka untuk pengiriman tiket elektronik.</p>
                            </details>
                            <details class="bg-[#211717] p-4 rounded-lg cursor-pointer">
                                <summary class="flex justify-between items-center font-medium text-white">
                                    Apakah ada batasan jumlah tiket per transaksi? 
                                    <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300"></i>
                                </summary>
                                <p class="text-gray-300 mt-3 text-sm">Ya, setiap transaksi maksimal dapat membeli 3 tiket untuk memastikan ketersediaan tempat duduk bagi semua pengunjung.</p>
                            </details>
                        </div>
                    </div>
                </div>
            </main>
            <?php include __DIR__ . '/templates/footer.php'; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi Hero Slider
            const heroSlider = new Swiper('.hero-slider', {
                loop: true,
                effect: 'fade',
                fadeEffect: {
                    crossFade: true
                },
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev'
                },
            });

            // Inisialisasi Coming Soon Slider
            const comingSoonSlider = new Swiper('.coming-soon-slider', {
                loop: true,
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                },
                spaceBetween: 24,
                slidesPerView: 1,
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                breakpoints: {
                    768: {
                        slidesPerView: 2,
                    }
                }
            });

            // Inisialisasi Testimonial Slider
            const testimonialSlider = new Swiper('.testimonial-slider', {
                loop: true,
                autoplay: {
                    delay: 6000,
                    disableOnInteraction: false,
                },
                spaceBetween: 24,
                slidesPerView: 1,
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                breakpoints: {
                    640: {
                        slidesPerView: 2,
                    },
                    1024: {
                        slidesPerView: 3,
                    }
                }
            });
            
            // --- Combined Film Filter Logic (Search and Genre) ---
            const searchInput = document.getElementById('film-search-input');
            const genreButtons = document.querySelectorAll('.genre-btn');
            const filmCards = document.querySelectorAll('#now-showing-list .film-card');

            function filterAndDisplayFilms() {
                const selectedGenre = document.querySelector('.genre-btn.active').dataset.genre;
                const searchQuery = searchInput.value.toLowerCase().trim();

                filmCards.forEach(card => {
                    const cardGenre = card.dataset.genre;
                    const cardTitle = card.dataset.title;

                    const genreMatch = (selectedGenre === 'Semua' || cardGenre === selectedGenre);
                    const searchMatch = cardTitle.includes(searchQuery);

                    if (genreMatch && searchMatch) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }

            genreButtons.forEach(button => {
                button.addEventListener('click', function() {
                    genreButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    filterAndDisplayFilms();
                });
            });
            
            searchInput.addEventListener('input', filterAndDisplayFilms);

            // Logika tombol geser manual untuk slider film
            const sliderButtons = document.querySelectorAll('.manual-slider-btn');
            sliderButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.dataset.target;
                    const container = document.getElementById(targetId);
                    const scrollAmount = container.clientWidth * 0.8;
                    if (this.classList.contains('left-0')) {
                        container.scrollBy({
                            left: -scrollAmount,
                            behavior: 'smooth'
                        });
                    } else {
                        container.scrollBy({
                            left: scrollAmount,
                            behavior: 'smooth'
                        });
                    }
                });
            });

            // Logika salin kode promo
            const copyButtons = document.querySelectorAll('.copy-promo-btn');
            copyButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const promoCode = this.dataset.promoCode;
                    navigator.clipboard.writeText(promoCode).then(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Kode Promo Disalin!',
                            text: `Kode "${promoCode}" telah disalin.`,
                            timer: 2000,
                            showConfirmButton: false,
                            background: '#211717',
                            color: '#E5E7EB'
                        });
                    });
                });
            });

            // Logika Tombol Pengingat
            let reminders = JSON.parse(localStorage.getItem('filmReminders')) || [];

            function updateButtonStates() {
                const reminderButtons = document.querySelectorAll('.reminder-btn');
                reminderButtons.forEach(button => {
                    const filmId = button.dataset.filmId;
                    const textSpan = button.querySelector('.reminder-text');
                    if (reminders.includes(filmId)) {
                        button.classList.add('active');
                        textSpan.textContent = 'Pengingat Aktif';
                    } else {
                        button.classList.remove('active');
                        textSpan.textContent = 'Ingatkan Saya';
                    }
                });
            }

            document.addEventListener('click', function(e) {
                if (e.target.closest('.reminder-btn')) {
                    const button = e.target.closest('.reminder-btn');
                    e.preventDefault();
                    const filmId = button.dataset.filmId;
                    const filmTitle = button.dataset.filmTitle;
                    const index = reminders.indexOf(filmId);
                    let title, text, icon;
                    if (index > -1) {
                        reminders.splice(index, 1);
                        title = 'Pengingat Dibatalkan';
                        text = `Anda tidak akan diingatkan untuk film ${filmTitle}.`;
                        icon = 'warning';
                    } else {
                        reminders.push(filmId);
                        title = 'Pengingat Diatur!';
                        text = `Kami akan memberitahu Anda saat film ${filmTitle} mulai tayang.`;
                        icon = 'success';
                    }
                    localStorage.setItem('filmReminders', JSON.stringify(reminders));
                    updateButtonStates();
                    Swal.fire({
                        icon: icon,
                        title: title,
                        text: text,
                        timer: 2500,
                        showConfirmButton: false,
                        background: '#211717',
                        color: '#E5E7EB'
                    });
                }
            });
            updateButtonStates();
        });
    </script>
</body>

</html>