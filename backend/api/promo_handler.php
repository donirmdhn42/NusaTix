<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../models/promo.php';

function sendResponse($status, $message, $data = null) {
    $isSuccess = ($status === 'success');
    echo json_encode(['status' => $status, 'success' => $isSuccess, 'message' => $message, 'data' => $data]);
    exit;
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get_all':
        $promos = getAllPromos($conn);
        sendResponse('success', 'Data promo berhasil diambil.', $promos);
        break;

    case 'get_one':
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) { sendResponse('error', 'ID promo tidak valid.'); }
        
        $promo = getPromoById($conn, $id);
        if ($promo) {
            sendResponse('success', 'Detail promo ditemukan.', $promo);
        } else {
            sendResponse('error', 'Promo tidak ditemukan.');
        }
        break;

    case 'save':
        if (empty($_POST['code']) || !isset($_POST['discount_value'])) {
            sendResponse('error', 'Kode dan nilai diskon wajib diisi.');
        }
        if (savePromo($conn, $_POST)) {
            $message = !empty($_POST['id_promo']) ? 'Promo berhasil diperbarui.' : 'Promo berhasil ditambahkan.';
            sendResponse('success', $message);
        } else {
            sendResponse('error', 'Gagal menyimpan data promo ke database.');
        }
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) { sendResponse('error', 'ID promo tidak valid untuk dihapus.'); }

        if (deletePromoById($conn, $id)) {
            sendResponse('success', 'Promo berhasil dihapus.');
        } else {
            sendResponse('error', 'Gagal menghapus promo. Kemungkinan promo sedang digunakan.');
        }
        break;

    case 'apply_promo':
        session_start();
        $promo_code = trim($_POST['promo_code'] ?? '');
        $original_price = floatval($_POST['original_price'] ?? 0);
        
        if (!isset($_SESSION['user_id'])) {
            sendResponse('error', 'Anda harus login untuk menggunakan promo.');
        }
        $user_id = $_SESSION['user_id'];

        $result = applyPromo($conn, $promo_code, $original_price, $user_id);
        sendResponse($result['success'] ? 'success' : 'error', $result['message'], $result);
        break;

    default:
        sendResponse('error', 'Aksi tidak diketahui.');
        break;
}