<?php
class Booking
{

    public static function getTopSellingFilms($conn, $limit = 5)
    {
        $sql = "
            SELECT 
                f.title, 
                COUNT(t.id_ticket) AS tickets_sold,
                SUM(b.total_amount) AS total_revenue
            FROM tickets t
            JOIN bookings b ON t.id_booking = b.id_booking
            JOIN schedules s ON b.id_schedule = s.id_schedule
            JOIN films f ON s.id_film = f.id_film
            WHERE b.status = 'paid' AND b.booking_time >= CURDATE() - INTERVAL 30 DAY
            GROUP BY f.id_film
            ORDER BY tickets_sold DESC
            LIMIT ?
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function getAllForAdmin($conn)
    {
        $sql = "SELECT 
                    b.id_booking, b.status, b.total_amount,
                    u.name AS user_name,
                    f.title AS film_title,
                    s.show_date, s.show_time
                FROM bookings b
                JOIN users u ON b.id_user = u.id_user
                JOIN schedules s ON b.id_schedule = s.id_schedule
                JOIN films f ON s.id_film = f.id_film
                ORDER BY b.booking_time DESC
                LIMIT 20";
        return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public static function getDetailsForUser($conn, $booking_id, $user_id)
    {
        $sql = "SELECT 
                    b.id_booking, b.total_amount, b.status,
                    f.title AS film_title,
                    s.show_date, s.show_time,
                    st.name AS studio_name,
                    (SELECT GROUP_CONCAT(t.seat_code ORDER BY t.seat_code) FROM tickets t WHERE t.id_booking = b.id_booking) AS seat_codes
                FROM bookings b
                JOIN schedules s ON b.id_schedule = s.id_schedule
                JOIN films f ON s.id_film = f.id_film
                JOIN studios st ON s.id_studio = st.id_studio
                WHERE b.id_booking = ? AND b.id_user = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $booking_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public static function processPaymentSubmission($conn, $booking_id, $user_id, $payment_method, $amount, $proof_path)
    {
        $conn->begin_transaction();
        try {
            $stmt1 = $conn->prepare("UPDATE bookings SET status = 'pending' WHERE id_booking = ? AND id_user = ? AND status = 'booked'");
            $stmt1->bind_param("ii", $booking_id, $user_id);
            $stmt1->execute();

            if ($stmt1->affected_rows === 0) {
                throw new Exception("Booking tidak valid atau sudah dalam proses pembayaran.");
            }

            $stmt2 = $conn->prepare("INSERT INTO payments (id_booking, amount, payment_method, proof_of_payment, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt2->bind_param("idss", $booking_id, $amount, $payment_method, $proof_path);
            $stmt2->execute();
            
            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Payment submission failed: " . $e->getMessage());
            return false;
        }
    }
    
    public static function getPendingPayments($conn)
    {
        $sql = "SELECT 
                    p.id_payment, p.id_booking, p.amount, p.payment_method, p.proof_of_payment,
                    b.status AS booking_status,
                    u.name AS user_name,
                    f.title AS film_title
                FROM payments p
                JOIN bookings b ON p.id_booking = b.id_booking
                JOIN users u ON b.id_user = u.id_user
                JOIN schedules s ON b.id_schedule = s.id_schedule
                JOIN films f ON s.id_film = f.id_film
                WHERE p.status = 'pending' AND b.status = 'pending'
                ORDER BY p.created_at ASC";
        return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public static function approvePayment($conn, $payment_id, $booking_id)
    {
        $conn->begin_transaction();
        try {
            $stmt1 = $conn->prepare("UPDATE payments SET status = 'verified', paid_at = NOW() WHERE id_payment = ?");
            $stmt1->bind_param("i", $payment_id);
            $stmt1->execute();

            $stmt2 = $conn->prepare("UPDATE bookings SET status = 'paid' WHERE id_booking = ?");
            $stmt2->bind_param("i", $booking_id);
            $stmt2->execute();

            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollback();
            return false;
        }
    }

    public static function rejectPayment($conn, $payment_id, $booking_id, $notes)
    {
        $conn->begin_transaction();
        try {
            $stmt1 = $conn->prepare("UPDATE payments SET status = 'rejected', notes = ? WHERE id_payment = ?");
            $stmt1->bind_param("si", $notes, $payment_id);
            $stmt1->execute();

            $stmt2 = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id_booking = ?");
            $stmt2->bind_param("i", $booking_id);
            $stmt2->execute();

            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollback();
            return false;
        }
    }

     public static function countUserTicketsForSchedule($conn, $user_id, $schedule_id)
    {
        $stmt = $conn->prepare("
            SELECT COUNT(t.id_ticket) as total_tickets
            FROM tickets t
            JOIN bookings b ON t.id_booking = b.id_booking
            WHERE b.id_user = ? 
              AND b.id_schedule = ? 
              AND b.status IN ('paid', 'pending')
        ");
        $stmt->bind_param("ii", $user_id, $schedule_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result['total_tickets'] ?? 0;
    }

    public static function getActiveTicketsByUserId($conn, $user_id)
    {
        $sql = "SELECT 
                    b.id_booking, b.total_amount, b.status, b.booking_time,
                    f.id_film, f.title AS film_title, f.poster AS film_poster,
                    s.show_date, s.show_time,
                    st.name AS studio_name,
                    (SELECT GROUP_CONCAT(t.seat_code ORDER BY t.seat_code SEPARATOR ', ') FROM tickets t WHERE t.id_booking = b.id_booking) AS seat_codes,
                    tes.id_testimonial
                FROM bookings b
                JOIN schedules s ON b.id_schedule = s.id_schedule
                JOIN films f ON s.id_film = f.id_film
                JOIN studios st ON s.id_studio = st.id_studio
                LEFT JOIN testimonials tes ON b.id_booking = tes.id_booking
                WHERE b.id_user = ? AND b.status = 'paid'
                ORDER BY b.booking_time DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public static function getBookingHistoryByUserId($conn, $user_id)
    {
        $sql = "SELECT 
                    b.id_booking, b.total_amount, b.status, b.booking_time,
                    f.title AS film_title, f.poster AS film_poster,
                    s.show_date, s.show_time,
                    st.name AS studio_name,
                    (SELECT GROUP_CONCAT(t.seat_code ORDER BY t.seat_code SEPARATOR ', ') FROM tickets t WHERE t.id_booking = b.id_booking) AS seat_codes
                FROM bookings b
                JOIN schedules s ON b.id_schedule = s.id_schedule
                JOIN films f ON s.id_film = f.id_film
                JOIN studios st ON s.id_studio = st.id_studio
                WHERE b.id_user = ?
                ORDER BY b.booking_time DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}