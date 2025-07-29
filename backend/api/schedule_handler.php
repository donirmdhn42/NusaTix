<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../models/schedule.php';

function sendResponse($status, $message, $data = null) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get_groups':
        $groups = getAllScheduleGroups($conn);
        sendResponse('success', 'Data grup jadwal berhasil diambil.', $groups);
        break;

    case 'get_group_details': 
        $id_group = intval($_GET['id_group'] ?? 0);
        if ($id_group <= 0) {
            sendResponse('error', 'ID grup tidak valid.');
        }
        $group = getScheduleGroupById($conn, $id_group);
        if ($group) {
            sendResponse('success', 'Detail grup jadwal berhasil diambil.', $group);
        } else {
            sendResponse('error', 'Grup jadwal tidak ditemukan.');
        }
        break;

    case 'save_group':
        if (empty($_POST['id_film']) || empty($_POST['id_studio']) || empty($_POST['price']) || empty($_POST['start_date']) || empty($_POST['end_date']) || empty($_POST['show_times'])) {
            sendResponse('error', 'Semua field wajib diisi.');
        }

        $id_group = intval($_POST['id_group'] ?? 0);
        
        $result = saveScheduleGroup($conn, $_POST, $id_group); 
        if ($result === true) {
            $message = ($id_group > 0) ? 'Grup jadwal berhasil diperbarui.' : 'Grup jadwal baru berhasil dibuat.';
            sendResponse('success', $message);
        } else {
            sendResponse('error', 'Gagal: ' . $result);
        }
        break;

    case 'delete_group':
        $id_group = intval($_POST['id_group'] ?? 0);
        if ($id_group <= 0) {
            sendResponse('error', 'ID grup tidak valid.');
        }
        
        if (deleteScheduleGroupById($conn, $id_group)) {
            sendResponse('success', 'Grup jadwal dan semua isinya berhasil dihapus.');
        } else {
            sendResponse('error', 'Gagal menghapus grup jadwal.');
        }
        break;

    default:
        sendResponse('error', 'Aksi tidak diketahui.');
        break;
}
?>