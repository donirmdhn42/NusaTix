<?php
require_once '../config/midtrans.php'; 
require_once '../backend/db.php';

header('Content-Type: text/plain');

try {
    $notif = new \Midtrans\Notification();

    $order_id = $notif->order_id;
    $payment_type = $notif->payment_type;
    $status_code = $notif->status_code;
    $transaction_status = $notif->transaction_status;
    $transaction_time = $notif->transaction_time;
    $gross_amount = $notif->gross_amount;

    $status_bayar = 'pending'; 
    if ($transaction_status == 'capture' || $transaction_status == 'settlement') {
        $status_bayar = 'berhasil';
    } else if ($transaction_status == 'deny' || $transaction_status == 'cancel' || $transaction_status == 'expire') {
        $status_bayar = 'gagal';
    }

    $conn->begin_transaction();

    try {
        $stmt_pembayaran = $conn->prepare("UPDATE pembayaran SET status_bayar = ?, payment_type = ?, paid_at = ? WHERE order_id = ?");
        $stmt_pembayaran->bind_param("ssss", $status_bayar, $payment_type, $transaction_time, $order_id);
        $stmt_pembayaran->execute();
        
        if ($stmt_pembayaran->affected_rows == 0) {
            throw new Exception("Order ID '{$order_id}' tidak ditemukan di tabel pembayaran.");
        }

        $stmt_get_user = $conn->prepare("
            SELECT d.user_id 
            FROM pembayaran p
            JOIN donasi d ON p.donasi_id = d.donasi_id
            WHERE p.order_id = ?
        ");
        $stmt_get_user->bind_param("s", $order_id);
        $stmt_get_user->execute();
        $donasi_data = $stmt_get_user->get_result()->fetch_assoc();

        if ($donasi_data && !empty($donasi_data['user_id'])) {
            $user_id = $donasi_data['user_id'];
            $nominal_rp = 'Rp ' . number_format($gross_amount, 0, ',', '.');

            $pesan = match($status_bayar) {
                'berhasil' => "Pembayaran untuk donasi senilai $nominal_rp telah berhasil! Terima kasih.",
                'pending'  => "Donasi Anda senilai $nominal_rp masih menunggu pembayaran.",
                'gagal'    => "Pembayaran untuk donasi senilai $nominal_rp telah gagal atau dibatalkan.",
                default    => 'Status donasi diperbarui.'
            };

            $stmt_notif = $conn->prepare("
                INSERT INTO notifikasi (user_id, pesan, is_seen, order_id) 
                VALUES (?, ?, 0, ?)
                ON DUPLICATE KEY UPDATE pesan = VALUES(pesan), is_seen = 0, created_at = NOW()
            ");
            $stmt_notif->bind_param("iss", $user_id, $pesan, $order_id);
            $stmt_notif->execute();
        }
        
        $conn->commit();

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

    http_response_code(200);
    echo "OK";

} catch (Exception $e) {
    http_response_code(400); 
    error_log("Gagal memproses notifikasi Midtrans: " . $e->getMessage()); 
    echo "Gagal memproses notifikasi: " . $e->getMessage();
}
?>