<?php
// views/booking_user.php
session_start();

// Redirect jika user belum login
if (!isset($_SESSION['user_id'])) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: ../login.php?redirect_url=$redirect_url");
    exit();
}

// Memuat semua file model yang dibutuhkan
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/models/schedule.php';
require_once __DIR__ . '/../backend/models/studio.php';
require_once __DIR__ . '/../backend/models/promo.php';
require_once __DIR__ . '/../backend/models/booking.php'; // Penting: Memuat model booking

// Validasi ID Jadwal dari URL
$schedule_id = intval($_GET['schedule_id'] ?? 0);
if (!$schedule_id) { die("ID Jadwal tidak valid."); }

// Ambil detail jadwal dari database
$schedule_details = getScheduleWithDetailsById($conn, $schedule_id);
if (!$schedule_details) { die("Jadwal tidak ditemukan."); }

$user_id = $_SESSION['user_id'];

// =================================================================
// ==> LOGIKA ANTI-CALO BAGIAN 1: PEMBLOKIRAN SAAT MEMBUKA HALAMAN <==
// =================================================================
$max_tickets_per_session = 3;
// Memanggil fungsi baru untuk menghitung tiket yang sudah ada
$existing_tickets = Booking::countUserTicketsForSchedule($conn, $user_id, $schedule_id);

// Jika pengguna sudah mencapai batas, tampilkan halaman error dan hentikan script
if ($existing_tickets >= $max_tickets_per_session) {
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Batas Pembelian Tercapai</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
    <body class="bg-[#181111] text-gray-300 flex items-center justify-center min-h-screen p-4">
        <div class="text-center bg-[#211717] p-8 md:p-10 rounded-lg shadow-lg border border-red-700 max-w-lg w-full">
            <i class="fas fa-exclamation-triangle fa-4x text-red-500 mb-4"></i>
            <h1 class="text-2xl md:text-3xl font-bold text-white">Batas Pembelian Tercapai</h1>
            <p class="text-gray-400 mt-2 mb-6">Anda telah memiliki {$existing_tickets} tiket untuk sesi ini. Batas pembelian adalah {$max_tickets_per_session} tiket per pengguna untuk jadwal yang sama.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="../views/index.php" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition-colors">Kembali ke Beranda</a>
                <a href="my_tickets.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition-colors">Lihat Tiket Saya</a>
            </div>
        </div>
    </body>
    </html>
HTML;
    exit(); // Hentikan eksekusi script
}

// Lanjutkan proses jika pengguna belum mencapai batas
$booked_seat_codes = getBookedSeatsByScheduleId($conn, $schedule_id);
$all_studio_seats = getSeatsByStudioId($conn, $schedule_details['id_studio']);
$booking_message = '';
$booking_status = '';

// Proses form saat disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book') {
    $selected_seat_codes = !empty($_POST['selected_seats']) ? explode(',', $_POST['selected_seats']) : [];
    $promo_code = trim($_POST['promo_code'] ?? '');

    if (empty($selected_seat_codes)) {
        $booking_message = "Pilih setidaknya satu kursi.";
        $booking_status = "error";
    } else {
        // =============================================================================
        // ==> LOGIKA ANTI-CALO BAGIAN 2: VALIDASI SAAT PROSES PEMBELIAN (SUBMIT)      <==
        // =============================================================================
        $num_seats_to_buy = count($selected_seat_codes);
        if (($existing_tickets + $num_seats_to_buy) > $max_tickets_per_session) {
            $booking_message = "Gagal: Anda sudah punya {$existing_tickets} tiket. Anda tidak bisa membeli {$num_seats_to_buy} tiket lagi (batas {$max_tickets_per_session} tiket per sesi).";
            $booking_status = "error";
        } else {
            // Lanjutkan proses booking jika validasi lolos
            $conn->begin_transaction();
            try {
                $current_booked_seats = getBookedSeatsByScheduleId($conn, $schedule_id);
                $conflict_seats = array_intersect($selected_seat_codes, $current_booked_seats);
                if (!empty($conflict_seats)) {
                    throw new Exception("Kursi " . implode(', ', $conflict_seats) . " baru saja dipesan.");
                }
                
                $base_price = $num_seats_to_buy * $schedule_details['price'];
                $final_price = $base_price;
                $promo_id_for_db = null;

                if (!empty($promo_code)) {
                    $promo_result = applyPromo($conn, $promo_code, $base_price, $user_id);
                    if ($promo_result['success']) {
                        $final_price = $promo_result['final_price'];
                        $promo_id_for_db = $promo_result['id_promo'];
                    } else {
                        throw new Exception($promo_result['message']);
                    }
                }
                
                $stmt = $conn->prepare("INSERT INTO bookings (id_user, id_schedule, total_amount, status, id_promo) VALUES (?, ?, ?, 'booked', ?)");
                $stmt->bind_param("iidi", $user_id, $schedule_id, $final_price, $promo_id_for_db);
                $stmt->execute();
                $new_booking_id = $conn->insert_id;

                $stmt_ticket = $conn->prepare("INSERT INTO tickets (id_booking, id_seat, seat_code, price) VALUES (?, ?, ?, ?)");
                foreach ($selected_seat_codes as $seat_code) {
                    $seat_id = null;
                    foreach($all_studio_seats as $seat) { if ($seat['seat_code'] === $seat_code) { $seat_id = $seat['id_seat']; break; } }
                    if ($seat_id) {
                        $stmt_ticket->bind_param("iisd", $new_booking_id, $seat_id, $seat_code, $schedule_details['price']);
                        $stmt_ticket->execute();
                    } else { throw new Exception("Kursi $seat_code tidak valid."); }
                }
                
                $conn->commit();
                $booking_status = "success";
                $_SESSION['last_booking_details'] = [ 
                    'booking_id' => $new_booking_id,
                    'final_price' => $final_price,
                    'expires_at' => time() + 60
                ];

            } catch (Exception $e) {
                $conn->rollback();
                $booking_message = "Gagal membuat pesanan: " . $e->getMessage();
                $booking_status = "error";
            }
        }
        $booked_seat_codes = getBookedSeatsByScheduleId($conn, $schedule_id);
    }
}

// Data untuk ditampilkan di View
$film_title = $schedule_details['film_title'];
$film_poster = "../uploads/posters/" . $schedule_details['film_poster'];
$studio_name = $schedule_details['studio_name'];
$show_date = date('d M Y', strtotime($schedule_details['show_date']));
$show_time = date('H:i', strtotime($schedule_details['show_time']));
$ticket_price = $schedule_details['price'];
$js_data = ['all_seats' => $all_studio_seats, 'booked_seats' => $booked_seat_codes, 'ticket_price' => $ticket_price];
$last_booking_id = $_SESSION['last_booking_details']['booking_id'] ?? null;
$last_booking_price = $_SESSION['last_booking_details']['final_price'] ?? 0;
if ($booking_status !== 'success') { unset($_SESSION['last_booking_details']); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Tiket: <?php echo htmlspecialchars($film_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .seat { transition: all 0.2s ease-in-out; }
        .seat.selected { background-color: #e92932; color: white; border-color: #e92932; transform: scale(1.1); }
        .seat.booked { background-color: #4b5563; color: #9ca3af; border-color: #374151; cursor: not-allowed; }
        .seat.available:hover { border-color: #e92932; }
        .seat-row { display: grid; grid-template-columns: 2rem 1fr; align-items: center; gap: 1rem; }
        .seat-grid { display: grid; gap: 0.75rem; }
        .modal { display: none; }
        .modal.flex { display: flex; }
    </style>
</head>
<body class="bg-[#181111] font-sans text-gray-300">
    <?php include __DIR__ . '/templates/header.php'; ?>
    <div class="container mx-auto my-8 p-4 sm:p-6">
        <div class="flex flex-col lg:flex-row gap-8">
            <div class="w-full lg:w-2/3">
                <div class="bg-[#211717] p-6 rounded-2xl shadow-2xl shadow-black/20 border border-gray-700">
                    <div class="flex items-center gap-4 mb-6">
                        <img src="<?php echo htmlspecialchars($film_poster); ?>" class="w-24 h-auto object-cover rounded-md shadow-lg" alt="Poster Film">
                        <div>
                            <h2 class="text-2xl font-bold text-white"><?php echo htmlspecialchars($film_title); ?></h2>
                            <p class="text-md text-gray-400"><?php echo htmlspecialchars($studio_name); ?> &bull; <?php echo htmlspecialchars($show_date . ', ' . $show_time); ?></p>
                        </div>
                    </div>
                    <div class="p-4 bg-[#181111] rounded-lg border border-gray-700">
                        <div class="text-white p-3 text-center rounded-md text-lg font-bold mb-6 tracking-widest shadow-lg bg-gradient-to-t from-gray-900 to-gray-700">L A Y A R</div>
                        <div id="seat-layout" class="space-y-4"></div>
                    </div>
                    <div class="flex justify-center flex-wrap gap-4 mt-6 text-sm text-gray-400">
                        <div class="flex items-center"><div class="w-5 h-5 bg-[#382929] border border-gray-600 rounded-sm mr-2"></div> Tersedia</div>
                        <div class="flex items-center"><div class="w-5 h-5 bg-[#e92932] rounded-sm mr-2"></div> Terpilih</div>
                        <div class="flex items-center"><div class="w-5 h-5 bg-gray-600 rounded-sm mr-2"></div> Terpesan</div>
                    </div>
                </div>
            </div>

            <div class="w-full lg:w-1/3">
                <form id="bookingForm" method="POST" action="">
                    <input type="hidden" name="action" value="book">
                    <div class="bg-[#211717] p-6 rounded-2xl shadow-2xl shadow-black/20 border border-gray-700">
                        <h3 class="text-xl font-semibold mb-4 border-b border-gray-600 pb-2 text-white">Ringkasan Pesanan</h3>
                        <div class="bg-blue-900/50 text-blue-300 border border-blue-700 rounded-lg p-3 text-sm text-center mb-4">
                            Anda telah memiliki <strong><?= $existing_tickets ?> tiket</strong>. Anda dapat membeli <strong><?= $max_tickets_per_session - $existing_tickets ?> tiket</strong> lagi untuk sesi ini.
                        </div>
                        <div class="space-y-3 text-gray-300 mb-6">
                            <div class="flex justify-between"><span>Kursi Terpilih:</span><span id="selectedSeatsDisplay" class="font-semibold text-right text-white">Tidak ada</span></div>
                            <div class="flex justify-between"><span>Jumlah Tiket:</span><span id="ticketCount" class="font-semibold text-white">0</span></div>
                            <div class="flex justify-between"><span>Subtotal:</span><span class="font-semibold">Rp <span id="subtotalPriceDisplay">0</span></span></div>
                        </div>
                        <div class="promo-section mb-6">
                            <label for="promoCodeInput" class="font-medium block mb-1 text-gray-400">Kode Promo:</label>
                            <div class="flex space-x-2">
                                <input type="text" class="flex-1 p-2 bg-[#382929] border border-gray-600 rounded-md shadow-sm text-white focus:ring-red-500 focus:border-red-500" id="promoCodeInput" name="promo_code" autocomplete="off">
                                <button type="button" class="px-5 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition-colors" id="applyPromoBtn">Pakai</button>
                            </div>
                            <div id="promoMessage" class="mt-2 text-sm h-5"></div>
                        </div>
                        <div class="border-t border-gray-600 pt-4 space-y-2">
                            <div class="flex justify-between text-green-400"><span>Diskon:</span><span class="font-semibold">- Rp <span id="discountAmountDisplay">0</span></span></div>
                            <div class="flex justify-between text-2xl font-bold text-white"><span>Total:</span><span>Rp <span id="totalPriceDisplay">0</span></span></div>
                        </div>
                        <input type="hidden" name="selected_seats" id="selectedSeatsInput">
                        <button type="submit" class="w-full mt-6 py-3 bg-red-600 text-white text-lg font-bold rounded-lg hover:bg-red-700 disabled:bg-gray-500" id="bookNowBtn" disabled>Konfirmasi Pesanan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="paymentMethodModal" class="modal fixed inset-0 bg-black bg-opacity-80 justify-center items-center p-4">
        <div class="modal-content bg-[#211717] border border-gray-700 p-8 rounded-lg w-full max-w-lg text-left transform scale-95 opacity-0 transition-all">
            <h2 class="text-3xl font-bold text-white">Pesanan Disimpan!</h2>
            <p class="text-gray-400 mt-2">Pesanan <strong class="text-white">#<?php echo htmlspecialchars($last_booking_id); ?></strong> berhasil dibuat. Segera selesaikan pembayaran.</p>
            <div class="bg-[#382929]/50 p-4 rounded-lg my-6 border border-gray-600">
                <div class="flex justify-between items-center text-xl font-bold text-white">
                    <span>Total Bayar:</span>
                    <span>Rp <?php echo number_format($last_booking_price, 0, ',', '.'); ?></span>
                </div>
            </div>
            <div class="flex gap-4">
                <a href="payment.php?booking_id=<?php echo $last_booking_id; ?>" class="flex-1 w-full text-center py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700">
                    Lanjut Bayar
                </a>
            </div>
        </div>
    </div>
    
      <script>
        document.addEventListener('DOMContentLoaded', function() {
            const MAX_TICKETS = 3;
            let selectedSeats = [];
            const seatData = <?php echo json_encode($js_data); ?>;
            const ticketPrice = parseFloat(seatData.ticket_price) || 0;
            let promoData = null;

            const layoutContainer = document.getElementById('seat-layout');
            const selectedSeatsDisplay = document.getElementById('selectedSeatsDisplay');
            const selectedSeatsInput = document.getElementById('selectedSeatsInput');
            const ticketCountDisplay = document.getElementById('ticketCount');
            const subtotalPriceDisplay = document.getElementById('subtotalPriceDisplay');
            const discountAmountDisplay = document.getElementById('discountAmountDisplay');
            const totalPriceDisplay = document.getElementById('totalPriceDisplay');
            const bookNowBtn = document.getElementById('bookNowBtn');
            const promoCodeInput = document.getElementById('promoCodeInput');
            const promoMessageEl = document.getElementById('promoMessage');
            const applyPromoBtn = document.getElementById('applyPromoBtn');

            function buildSeatLayout() {
                if (!layoutContainer) return;
                layoutContainer.innerHTML = '';
                
                if (!seatData.all_seats || seatData.all_seats.length === 0) {
                    layoutContainer.innerHTML = '<p class="text-center text-gray-500">Denah kursi belum tersedia.</p>';
                    return;
                }

                const seatMap = seatData.all_seats.reduce((acc, seat) => {
                    const row = seat.seat_code.charAt(0);
                    if (!acc[row]) acc[row] = [];
                    acc[row].push(seat.seat_code);
                    return acc;
                }, {});

                const sortedRows = Object.keys(seatMap).sort();
                sortedRows.forEach(row => {
                    const rowDiv = document.createElement('div');
                    rowDiv.className = 'seat-row';
                    
                    const rowLabel = document.createElement('div');
                    rowLabel.className = 'text-center font-bold text-gray-500';
                    rowLabel.textContent = row;
                    rowDiv.appendChild(rowLabel);

                    const gridDiv = document.createElement('div');
                    gridDiv.className = 'seat-grid';
                    const seatsInRow = seatMap[row].length;
                    gridDiv.style.gridTemplateColumns = `repeat(${seatsInRow}, minmax(0, 1fr))`;
                    
                    seatMap[row].sort((a, b) => parseInt(a.substring(1)) - parseInt(b.substring(1))).forEach(seatCode => {
                        const seatDiv = document.createElement('div');
                        const isBooked = seatData.booked_seats.includes(seatCode);
                        seatDiv.dataset.seatCode = seatCode;
                        seatDiv.textContent = seatCode.substring(1);
                        seatDiv.className = `seat w-full h-12 flex items-center justify-center font-bold cursor-pointer border-2 rounded-lg ${isBooked ? 'booked' : 'available bg-[#382929] border-gray-600'}`;
                        
                        if (!isBooked) {
                            seatDiv.addEventListener('click', handleSeatClick);
                        }
                        gridDiv.appendChild(seatDiv);
                    });
                    rowDiv.appendChild(gridDiv);
                    layoutContainer.appendChild(rowDiv);
                });
            }

            function handleSeatClick(event) {
                const seat = event.currentTarget;
                const seatCode = seat.dataset.seatCode;
                const index = selectedSeats.indexOf(seatCode);

                if (index > -1) {
                    seat.classList.remove('selected');
                    selectedSeats.splice(index, 1);
                } else {
                    if (selectedSeats.length < MAX_TICKETS) {
                        seat.classList.add('selected');
                        selectedSeats.push(seatCode);
                    } else {
                        alert(`Anda hanya bisa memilih maksimal ${MAX_TICKETS} tiket.`);
                    }
                }
                
                promoData = null;
                promoCodeInput.value = '';
                promoMessageEl.innerHTML = '';
                updateSummary();
            }

            function updateSummary() {
                selectedSeats.sort();
                const subtotal = selectedSeats.length * ticketPrice;
                let discountAmount = 0;
                let finalPrice = subtotal;

                if (promoData && promoData.success) {
                    discountAmount = promoData.data.discount_applied;
                    finalPrice = promoData.data.final_price;
                }
                
                ticketCountDisplay.innerText = selectedSeats.length;
                subtotalPriceDisplay.innerText = subtotal.toLocaleString('id-ID');
                discountAmountDisplay.innerText = parseFloat(discountAmount).toLocaleString('id-ID');
                totalPriceDisplay.innerText = parseFloat(finalPrice).toLocaleString('id-ID');
                selectedSeatsDisplay.innerText = selectedSeats.length > 0 ? selectedSeats.join(', ') : 'Tidak ada';
                selectedSeatsInput.value = selectedSeats.join(',');
                bookNowBtn.disabled = selectedSeats.length === 0;
            }

            applyPromoBtn.addEventListener('click', () => {
                const promoCode = promoCodeInput.value;
                if (!promoCode || selectedSeats.length === 0) {
                    promoMessageEl.className = 'text-sm text-red-400';
                    promoMessageEl.innerText = 'Pilih kursi dan masukkan kode promo.';
                    return;
                }
                
                promoMessageEl.className = 'text-sm text-yellow-400';
                promoMessageEl.innerText = 'Menerapkan...';
                
                const formData = new FormData();
                formData.append('action', 'apply_promo');
                formData.append('promo_code', promoCode);
                formData.append('original_price', selectedSeats.length * ticketPrice);

                fetch('../backend/api/promo_handler.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    promoMessageEl.className = `text-sm ${res.success ? 'text-green-400' : 'text-red-400'}`;
                    promoMessageEl.innerText = res.message;
                    
                    promoData = res; // Simpan seluruh respons
                    
                    updateSummary();
                });
            });

            <?php if ($booking_status === 'success'): ?>
                const modal = document.getElementById('paymentMethodModal');
                if (modal) {
                    modal.classList.add('flex');
                    setTimeout(() => {
                        const modalContent = modal.querySelector('.modal-content');
                        if(modalContent) {
                            modalContent.classList.remove('scale-95', 'opacity-0');
                            modalContent.classList.add('scale-100', 'opacity-100');
                        }
                    }, 10);
                }
            <?php endif; ?>

            <?php if ($booking_status === 'error' && !empty($booking_message)): ?>
                alert("<?php echo addslashes($booking_message); ?>");
            <?php endif; ?>
            
            buildSeatLayout();
            updateSummary();
        });
    </script>
</body>
</html>