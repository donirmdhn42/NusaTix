<?php
// backend/film_handler.php

// Header untuk mengizinkan permintaan dari domain manapun dan untuk response JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../models/film.php';

// Fungsi untuk mengirim response JSON
function sendResponse($status, $message, $data = null) {
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Ambil 'action' dari request. Bisa dari GET atau POST.
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get_all':
        try {
            $films = getAllFilms($conn);
            sendResponse('success', 'Data film berhasil diambil.', $films);
        } catch (Exception $e) {
            sendResponse('error', 'Gagal mengambil data film: ' . $e->getMessage());
        }
        break;

    case 'get_one':
        $id = $_GET['id'] ?? 0;
        if ($id > 0) {
            $film = getFilmById($conn, $id);
            if ($film) {
                sendResponse('success', 'Detail film ditemukan.', $film);
            } else {
                sendResponse('error', 'Film tidak ditemukan.');
            }
        } else {
            sendResponse('error', 'ID film tidak valid.');
        }
        break;

    case 'save':
        // Logika untuk menyimpan film (baik buat baru maupun update)
        $id = intval($_POST['id_film'] ?? 0);
        $data = $_POST;
        
        // Handle file upload untuk poster
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] == 0) {
            $target_dir = __DIR__ . "/../../uploads/posters/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $filename = time() . '_' . basename($_FILES["poster"]["name"]);
            $target_file = $target_dir . $filename;

            if (move_uploaded_file($_FILES["poster"]["tmp_name"], $target_file)) {
                $data['poster'] = $filename;
            }
        } else {
            $data['poster'] = $_POST['current_poster'] ?? null;
        }

        if (saveFilm($conn, $data)) {
            sendResponse('success', 'Film berhasil disimpan.');
        } else {
            sendResponse('error', 'Gagal menyimpan data film.');
        }
        break;

    case 'delete':
        $id = $_POST['id'] ?? 0;
        if ($id > 0) {
            if (deleteFilmById($conn, $id)) {
                sendResponse('success', 'Film berhasil dihapus.');
            } else {
                sendResponse('error', 'Gagal menghapus film.');
            }
        } else {
            sendResponse('error', 'ID film tidak valid untuk dihapus.');
        }
        break;

    default:
        sendResponse('error', 'Aksi tidak diketahui.');
        break;
}
?>