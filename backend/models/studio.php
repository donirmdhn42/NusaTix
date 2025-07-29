<?php
// backend/models/studio.php

function getAllStudios($conn) {
    $sql = "SELECT id_studio, name, capacity FROM studios ORDER BY name ASC";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getStudioById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM studios WHERE id_studio = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $studio = $result->fetch_assoc();
    $stmt->close();
    return $studio;
}

function saveStudio($conn, $data) {
    $conn->begin_transaction();
    try {
        $id = intval($data['id_studio'] ?? 0);
        $name = $data['name'];
        $capacity = intval($data['capacity']);

        if ($id > 0) { // Proses Update
            $stmt = $conn->prepare("UPDATE studios SET name = ?, capacity = ? WHERE id_studio = ?");
            $stmt->bind_param("sii", $name, $capacity, $id);
        } else { // Proses Create
            $stmt = $conn->prepare("INSERT INTO studios (name, capacity) VALUES (?, ?)");
            $stmt->bind_param("si", $name, $capacity);
        }
        $stmt->execute();
        $new_id = ($id > 0) ? $id : $conn->insert_id;
        $stmt->close();
        
        // Panggil fungsi yang sudah diperbaiki dengan membawa nilai kapasitas
        ensureSeatsMatchCapacity($conn, $new_id, $capacity);
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Gagal simpan studio: " . $e->getMessage());
        return false;
    }
}


function deleteStudioById($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM studios WHERE id_studio = ?");
    $stmt->bind_param("i", $id);
    $is_success = $stmt->execute();
    $stmt->close();
    return $is_success;
}

function getSeatsByStudioId($conn, $studio_id) {
    $stmt = $conn->prepare("SELECT id_seat, seat_code FROM seats WHERE id_studio = ? ORDER BY seat_code ASC");
    $stmt->bind_param("i", $studio_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * FUNGSI YANG DIPERBAIKI: Memastikan jumlah kursi di database
 * sama persis dengan kapasitas studio.
 */
function ensureSeatsMatchCapacity($conn, $studio_id, $capacity) {
    // 1. Hapus semua kursi lama dari studio ini untuk memastikan kebersihan data.
    $stmt_delete = $conn->prepare("DELETE FROM seats WHERE id_studio = ?");
    $stmt_delete->bind_param("i", $studio_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    // 2. Buat kursi baru sejumlah kapasitas yang ditentukan.
    if ($capacity <= 0) {
        return; // Jangan buat kursi jika kapasitasnya 0 atau kurang.
    }

    $seats_to_create = [];
    $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L']; // Cukup untuk banyak baris
    $cols_per_row = 10; // Asumsi 10 kursi per baris, bisa disesuaikan
    
    $created_count = 0;
    foreach ($rows as $row) {
        for ($i = 1; $i <= $cols_per_row; $i++) {
            if ($created_count >= $capacity) {
                break 2; // Hentikan kedua loop jika sudah mencapai kapasitas
            }
            $seats_to_create[] = $row . $i;
            $created_count++;
        }
    }
    
    // 3. Masukkan data kursi baru ke database.
    $stmt_insert = $conn->prepare("INSERT INTO seats (id_studio, seat_code) VALUES (?, ?)");
    foreach ($seats_to_create as $seat_code) {
        $stmt_insert->bind_param("is", $studio_id, $seat_code);
        $stmt_insert->execute();
    }
    $stmt_insert->close();
}
?>