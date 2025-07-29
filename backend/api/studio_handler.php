<?php
// backend/api/studio_handler.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../models/studio.php';

// Fungsi helper untuk mengirim response JSON
function sendResponse($status, $message, $data = null) {
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get_all':
        try {
            $studios = getAllStudios($conn);
            sendResponse('success', 'Data studio berhasil diambil.', $studios);
        } catch (Exception $e) {
            sendResponse('error', 'Gagal mengambil data studio: ' . $e->getMessage());
        }
        break;

    case 'get_one':
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) {
            $studio = getStudioById($conn, $id);
            if ($studio) {
                sendResponse('success', 'Detail studio ditemukan.', $studio);
            } else {
                sendResponse('error', 'Studio tidak ditemukan.');
            }
        } else {
            sendResponse('error', 'ID studio tidak valid.');
        }
        break;

    case 'save':
        // 1. Validasi dulu
        if (empty($_POST['name']) || empty($_POST['capacity'])) {
            sendResponse('error', 'Nama dan kapasitas studio tidak boleh kosong.');
            // Setelah mengirim response, eksekusi harus berhenti di sini jika ada error.
        }

        // 2. Jika validasi lolos, baru simpan
        if (saveStudio($conn, $_POST)) {
            $message = !empty($_POST['id_studio']) ? 'Studio berhasil diperbarui.' : 'Studio berhasil ditambahkan.';
            sendResponse('success', $message);
        } else {
            sendResponse('error', 'Gagal menyimpan data studio ke database.');
        }
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            if (deleteStudioById($conn, $id)) {
                sendResponse('success', 'Studio berhasil dihapus.');
            } else {
                sendResponse('error', 'Gagal menghapus studio.');
            }
        } else {
            sendResponse('error', 'ID studio tidak valid untuk dihapus.');
        }
        break;

    default:
        sendResponse('error', 'Aksi tidak diketahui.');
        break;
}

?>