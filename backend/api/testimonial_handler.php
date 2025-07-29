<?php
// backend/api/testimonial_handler.php
session_start();

/**
 * Fungsi untuk mengirim respons JSON yang terstandarisasi dan menghentikan skrip.
 * Ini memastikan tidak ada output lain yang mengganggu format JSON.
 *
 * @param string $status 'success' atau 'error'.
 * @param string $message Pesan yang akan dikirim.
 * @param array $data Data tambahan yang akan disertakan dalam respons (opsional).
 */
function send_json_response($status, $message, $data = []) {
    // Membersihkan output buffer jika ada
    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../models/Testimonial.php';

// Check if an action is provided
$action = $_REQUEST['action'] ?? ''; // Use $_REQUEST to handle both GET and POST for action

// Admin specific actions should ideally have a more robust admin check
// For simplicity, I'm maintaining the existing session check, but for a real admin panel,
// you'd want something like: if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) { ... }

if (!isset($_SESSION['user_id'])) { // This check applies to all actions for now
    send_json_response('error', 'Akses ditolak. Silakan login terlebih dahulu.');
}


switch ($action) {
    case 'get_all':
        // This is for the admin view, so fetch all testimonials without a limit
        try {
            $testimonials = Testimonial::getAllTestimonials($conn);
            send_json_response('success', 'Testimoni berhasil diambil.', $testimonials);
        } catch (Exception $e) {
            error_log("Error getting all testimonials: " . $e->getMessage());
            send_json_response('error', 'Gagal mengambil testimoni.', []);
        }
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            send_json_response('error', 'Metode request tidak valid untuk aksi hapus.');
        }

        $testimonial_id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

        if (empty($testimonial_id)) {
            send_json_response('error', 'ID testimoni tidak valid.');
        }

        try {
            $success = Testimonial::deleteTestimonial($conn, $testimonial_id);
            if ($success) {
                send_json_response('success', 'Testimoni berhasil dihapus.');
            } else {
                throw new Exception("Gagal menghapus testimoni dari database.");
            }
        } catch (Exception $e) {
            error_log("Error deleting testimonial: " . $e->getMessage());
            send_json_response('error', 'Terjadi kesalahan saat menghapus testimoni.');
        }
        break;

    // Existing user testimonial creation logic
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            send_json_response('error', 'Metode request tidak valid untuk aksi buat testimoni.');
        }

        $user_id = $_SESSION['user_id'];
        $booking_id = filter_input(INPUT_POST, 'id_booking', FILTER_SANITIZE_NUMBER_INT);
        $film_id = filter_input(INPUT_POST, 'id_film', FILTER_SANITIZE_NUMBER_INT);
        $rating = filter_input(INPUT_POST, 'rating', FILTER_SANITIZE_NUMBER_INT);
        $message = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS));

        // Validasi data
        if (empty($booking_id) || empty($film_id) || empty($rating) || empty($message)) {
            send_json_response('error', 'Semua field wajib diisi.');
        }
        if ($rating < 1 || $rating > 5) {
            send_json_response('error', 'Rating tidak valid.');
        }

        try {
            // Cek apakah user sudah pernah memberikan ulasan untuk booking ini
            if (Testimonial::hasUserReviewedBooking($conn, $user_id, $booking_id)) {
                send_json_response('error', 'Anda sudah pernah memberikan ulasan untuk tiket ini.');
            }

            // Simpan testimoni baru
            $success = Testimonial::create($conn, $user_id, $film_id, $booking_id, $rating, $message);

            if ($success) {
                send_json_response('success', 'Terima kasih! Ulasan Anda berhasil dikirim.');
            } else {
                throw new Exception("Gagal menyimpan ulasan ke database.");
            }
        } catch (Exception $e) {
            // Catat error di sisi server untuk debugging
            error_log("Error in testimonial_handler.php (create): " . $e->getMessage());
            // Kirim pesan error generik ke klien
            send_json_response('error', 'Terjadi kesalahan pada server. Silakan coba lagi nanti.');
        }
        break;

    default:
        send_json_response('error', 'Aksi tidak valid.');
        break;
}

$conn->close();