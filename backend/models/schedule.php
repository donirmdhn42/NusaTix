<?php
// backend/models/schedule.php
date_default_timezone_set('Asia/Jakarta');

function getAllScheduleGroups($conn) {
    $sql = "SELECT sg.id_group, f.title AS film_title, st.name AS studio_name, sg.price, sg.start_date, sg.end_date, sg.show_times
            FROM schedule_groups sg
            JOIN films f ON sg.id_film = f.id_film
            JOIN studios st ON sg.id_studio = st.id_studio
            ORDER BY sg.start_date DESC, f.title";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getScheduleGroupById($conn, $id_group) {
    $stmt = $conn->prepare("SELECT * FROM schedule_groups WHERE id_group = ?");
    $stmt->bind_param("i", $id_group);
    $stmt->execute();
    $result = $stmt->get_result();
    $group = $result->fetch_assoc();
    $stmt->close();
    return $group;
}

/**
 * =================================================================
 * ==> FUNGSI EDIT YANG DIPERBAIKI SECARA PAKSA <==
 * =================================================================
 * Logika ini akan menghapus semua data booking terkait sebelum mengedit.
 */
function saveScheduleGroup($conn, $data, $id_group = 0) {
    $conn->begin_transaction();
    try {
        $id_film = intval($data['id_film']);
        $id_studio = intval($data['id_studio']);
        $price = floatval($data['price']);
        $start_date_str = $data['start_date'];
        $end_date_str = $data['end_date'];
        $show_times_array = array_map('trim', explode(',', $data['show_times']));
        sort($show_times_array);
        $show_times_json = json_encode($show_times_array);

        if ($id_group > 0) { // Proses Edit
            // 1. Ambil semua ID booking yang terkait dengan grup jadwal ini
            $stmt_bookings = $conn->prepare("SELECT b.id_booking FROM bookings b JOIN schedules s ON b.id_schedule = s.id_schedule WHERE s.id_group = ?");
            $stmt_bookings->bind_param("i", $id_group);
            $stmt_bookings->execute();
            $result = $stmt_bookings->get_result();
            $booking_ids = [];
            while ($row = $result->fetch_assoc()) {
                $booking_ids[] = $row['id_booking'];
            }
            $stmt_bookings->close();

            // 2. Jika ada booking, hapus semua data turunannya (payments, tickets, baru booking)
            if (!empty($booking_ids)) {
                $booking_ids_str = implode(',', $booking_ids);
                $conn->query("DELETE FROM payments WHERE id_booking IN ($booking_ids_str)");
                $conn->query("DELETE FROM tickets WHERE id_booking IN ($booking_ids_str)");
                $conn->query("DELETE FROM bookings WHERE id_booking IN ($booking_ids_str)");
            }

            // 3. Sekarang aman untuk menghapus jadwal lama
            $stmt_delete_schedules = $conn->prepare("DELETE FROM schedules WHERE id_group = ?");
            $stmt_delete_schedules->bind_param("i", $id_group);
            $stmt_delete_schedules->execute();
            $stmt_delete_schedules->close();
            
            // 4. Perbarui data grup jadwal
            $stmt_update_group = $conn->prepare("UPDATE schedule_groups SET id_film = ?, id_studio = ?, price = ?, start_date = ?, end_date = ?, show_times = ? WHERE id_group = ?");
            $stmt_update_group->bind_param("iidsssi", $id_film, $id_studio, $price, $start_date_str, $end_date_str, $show_times_json, $id_group);
            $stmt_update_group->execute();
            $stmt_update_group->close();

        } else { // Proses Buat Baru
            $stmt_insert_group = $conn->prepare("INSERT INTO schedule_groups (id_film, id_studio, price, start_date, end_date, show_times) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_insert_group->bind_param("iidsss", $id_film, $id_studio, $price, $start_date_str, $end_date_str, $show_times_json);
            $stmt_insert_group->execute();
            $id_group = $conn->insert_id;
            $stmt_insert_group->close();
        }

        // Generate ulang jadwal individual
        $start_date = new DateTime($start_date_str);
        $end_date = new DateTime($end_date_str);
        $end_date->modify('+1 day');
        $interval = new DateInterval('P1D');
        $date_range = new DatePeriod($start_date, $interval, $end_date);
        
        $stmt_schedule = $conn->prepare("INSERT INTO schedules (id_group, id_film, id_studio, show_date, show_time, price) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($date_range as $date) {
            foreach ($show_times_array as $time) {
                $current_date = $date->format('Y-m-d');
                $stmt_schedule->bind_param("iiisss", $id_group, $id_film, $id_studio, $current_date, $time, $price);
                if (!$stmt_schedule->execute()) {
                    if ($conn->errno === 1062) {
                         throw new Exception("Jadwal bentrok pada " . date('d M Y', strtotime($current_date)) . " jam " . substr($time, 0, 5));
                    }
                    throw new Exception($conn->error);
                }
            }
        }
        $stmt_schedule->close();

        $stmt_film = $conn->prepare("UPDATE films SET status = 'now_showing' WHERE id_film = ? AND status = 'coming_soon'");
        $stmt_film->bind_param("i", $id_film);
        $stmt_film->execute();
        $stmt_film->close();

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return $e->getMessage();
    }
}

/**
 * =================================================================
 * ==> FUNGSI HAPUS YANG DIPERBAIKI SECARA PAKSA <==
 * =================================================================
 * Logika ini akan menghapus semua data booking terkait sebelum menghapus jadwal.
 */
function deleteScheduleGroupById($conn, $id_group) {
    $conn->begin_transaction();
    try {
        // 1. Ambil semua ID booking yang terkait
        $stmt_bookings = $conn->prepare("SELECT b.id_booking FROM bookings b JOIN schedules s ON b.id_schedule = s.id_schedule WHERE s.id_group = ?");
        $stmt_bookings->bind_param("i", $id_group);
        $stmt_bookings->execute();
        $result = $stmt_bookings->get_result();
        $booking_ids = [];
        while ($row = $result->fetch_assoc()) {
            $booking_ids[] = $row['id_booking'];
        }
        $stmt_bookings->close();

        // 2. Jika ada booking, hapus data turunannya
        if (!empty($booking_ids)) {
            $booking_ids_str = implode(',', $booking_ids);
            $conn->query("DELETE FROM payments WHERE id_booking IN ($booking_ids_str)");
            $conn->query("DELETE FROM tickets WHERE id_booking IN ($booking_ids_str)");
            $conn->query("DELETE FROM bookings WHERE id_booking IN ($booking_ids_str)");
        }
        
        // 3. Hapus jadwal individual
        $stmt_schedules = $conn->prepare("DELETE FROM schedules WHERE id_group = ?");
        $stmt_schedules->bind_param("i", $id_group);
        $stmt_schedules->execute();
        $stmt_schedules->close();
        
        // 4. Hapus grup jadwal utama
        $stmt_group = $conn->prepare("DELETE FROM schedule_groups WHERE id_group = ?");
        $stmt_group->bind_param("i", $id_group);
        $stmt_group->execute();
        $stmt_group->close();
        
        $conn->commit();
        return true;

    } catch (Exception $e) {
        $conn->rollback();
        return $e->getMessage();
    }
}

// --- Sisa fungsi di bawah ini tidak perlu diubah ---

function getSchedulesByFilmId($conn, $film_id) {
    $today = date('Y-m-d');
    $now_time = date('H:i:s');
    $stmt = $conn->prepare( "SELECT s.id_schedule, s.price, s.show_date, s.show_time, st.name as studio_name FROM schedules s JOIN studios st ON s.id_studio = st.id_studio WHERE s.id_film = ? AND (s.show_date > ? OR (s.show_date = ? AND s.show_time >= ?)) ORDER BY s.show_date ASC, s.show_time ASC");
    $stmt->bind_param("isss", $film_id, $today, $today, $now_time);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getScheduleWithDetailsById($conn, $schedule_id) {
    $stmt = $conn->prepare("SELECT s.id_schedule, s.show_date, s.show_time, s.price, f.id_film, f.title AS film_title, f.poster AS film_poster, st.id_studio, st.name AS studio_name FROM schedules s JOIN films f ON s.id_film = f.id_film JOIN studios st ON s.id_studio = st.id_studio WHERE s.id_schedule = ?");
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getBookedSeatsByScheduleId($conn, $schedule_id) {
    $booked_seats = [];
    $stmt = $conn->prepare("SELECT t.seat_code FROM tickets t JOIN bookings b ON t.id_booking = b.id_booking WHERE b.id_schedule = ? AND ( b.status IN ('paid', 'pending') OR (b.status = 'booked' AND b.booking_time >= NOW() - INTERVAL 2 MINUTE) )");
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $booked_seats[] = $row['seat_code'];
    }
    $stmt->close();
    return $booked_seats;
}