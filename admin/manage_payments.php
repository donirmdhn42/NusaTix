<?php
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/models/Booking.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $payment_id = intval($_POST['payment_id'] ?? 0);
    $booking_id = intval($_POST['booking_id'] ?? 0);
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if ($_POST['action'] === 'approve' && $payment_id > 0 && $booking_id > 0) {
        if (Booking::approvePayment($conn, $payment_id, $booking_id)) {
            $_SESSION['flash_message'] = "Pembayaran untuk booking #$booking_id telah disetujui.";
            $_SESSION['flash_status'] = 'success';
        } else {
            $_SESSION['flash_message'] = "Gagal menyetujui pembayaran.";
            $_SESSION['flash_status'] = 'error';
        }
    } elseif ($_POST['action'] === 'reject' && $payment_id > 0 && $booking_id > 0) {
        $notes = !empty($_POST['rejection_notes']) ? $_POST['rejection_notes'] : 'Bukti pembayaran tidak valid.';
        if (Booking::rejectPayment($conn, $payment_id, $booking_id, $notes)) {
             $_SESSION['flash_message'] = "Pembayaran untuk booking #$booking_id telah ditolak.";
             $_SESSION['flash_status'] = 'success';
        } else {
            $_SESSION['flash_message'] = "Gagal menolak pembayaran.";
            $_SESSION['flash_status'] = 'error';
        }
    }
    header('Location: manage_payments.php');
    exit();
}

require_once __DIR__ . '/templates/header.php';

$pending_payments = Booking::getPendingPayments($conn);
?>
<div class="p-6 md:p-8">
    <div class="space-y-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Verifikasi Pembayaran</h1>
            <p class="text-slate-500 mt-1">Daftar pembayaran yang perlu diverifikasi. Setujui jika bukti valid, atau tolak jika tidak.</p>
        </div>

        <?php if (isset($_SESSION['flash_message'])):
            $status_class = $_SESSION['flash_status'] === 'success'
                ? 'bg-emerald-50 border-emerald-500 text-emerald-700'
                : 'bg-rose-50 border-rose-500 text-rose-700';
        ?>
            <div class="<?= $status_class ?> border-l-4 p-4 rounded-r-lg" role="alert">
                <p class="font-bold"><?= $_SESSION['flash_status'] === 'success' ? 'Berhasil' : 'Gagal' ?></p>
                <p><?= htmlspecialchars($_SESSION['flash_message']) ?></p>
            </div>
            <?php unset($_SESSION['flash_message']); unset($_SESSION['flash_status']); ?>
        <?php endif; ?>

        <div class="bg-white rounded-2xl border border-slate-200">
            <div class="p-6 border-b border-slate-200">
                <h2 class="text-xl font-semibold text-slate-800">Pembayaran Pending (<?= count($pending_payments) ?>)</h2>
            </div>
            <div class="overflow-x-auto">
                <?php if (empty($pending_payments)): ?>
                    <p class="text-center py-8 text-slate-500">Tidak ada pembayaran yang perlu diverifikasi saat ini.</p>
                <?php else: ?>
                    <table class="min-w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="py-3 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Detail Booking</th>
                                <th class="py-3 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Total</th>
                                <th class="py-3 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Bukti</th>
                                <th class="py-3 px-6 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-700">
                            <?php foreach ($pending_payments as $p): ?>
                            <tr class="hover:bg-slate-50 border-t border-slate-200">
                                <td class="py-4 px-6">
                                    <p class="font-semibold text-slate-800">Booking #<?= $p['id_booking']; ?></p>
                                    <p class="text-sm text-slate-500"><?= htmlspecialchars($p['user_name']); ?></p>
                                </td>
                                <td class="py-4 px-6 font-semibold text-slate-800">
                                    Rp<?= number_format($p['amount'], 0, ',', '.') ?>
                                </td>
                                <td class="py-4 px-6">
                                    <a href="../uploads/<?= htmlspecialchars($p['proof_of_payment']) ?>" target="_blank" class="font-semibold text-primary hover:opacity-80">
                                        Lihat Bukti <i class="fas fa-external-link-alt fa-xs ml-1"></i>
                                    </a>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex justify-center items-center gap-2">
                                        <form method="POST" action="manage_payments.php" id="form-approve-<?= $p['id_payment'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="payment_id" value="<?= $p['id_payment'] ?>">
                                            <input type="hidden" name="booking_id" value="<?= $p['id_booking'] ?>">
                                            <button type="button" onclick="approvePayment(<?= $p['id_payment'] ?>)" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-lg text-xs">Setujui</button>
                                        </form>
                                        <form method="POST" action="manage_payments.php" id="form-reject-<?= $p['id_payment'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="payment_id" value="<?= $p['id_payment'] ?>">
                                            <input type="hidden" name="booking_id" value="<?= $p['id_booking'] ?>">
                                            <button type="button" onclick="rejectPayment(<?= $p['id_payment'] ?>)" class="bg-primary hover:opacity-90 text-white font-bold py-2 px-4 rounded-lg text-xs">Tolak</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function approvePayment(paymentId) {
        Swal.fire({
            title: 'Setujui Pembayaran?',
            text: "Pastikan bukti pembayaran sudah valid sebelum melanjutkan.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Setujui!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(`form-approve-${paymentId}`).submit();
            }
        });
    }

    function rejectPayment(paymentId) {
        Swal.fire({
            title: 'Tolak Pembayaran?',
            text: "Anda dapat memberikan alasan penolakan di bawah ini (opsional).",
            icon: 'warning',
            input: 'text',
            inputPlaceholder: 'Contoh: Bukti transfer tidak jelas',
            showCancelButton: true,
            confirmButtonColor: '#e92932',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Tolak!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById(`form-reject-${paymentId}`);
                if (result.value) {
                    const notesInput = document.createElement('input');
                    notesInput.type = 'hidden';
                    notesInput.name = 'rejection_notes';
                    notesInput.value = result.value;
                    form.appendChild(notesInput);
                }
                form.submit();
            }
        });
    }
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>