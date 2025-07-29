<?php
// backend/models/Testimonial.php

class Testimonial
{
    /**
     * Mengambil semua testimoni untuk ditampilkan di halaman utama.
     *
     * @param object $conn Koneksi database.
     * @param int $limit Batas jumlah testimoni yang akan diambil.
     * @return array Daftar testimoni.
     */
    public static function getAllTestimonials($conn, $limit = null) // Changed limit to be optional
    {
        // Query ini sudah benar, memastikan id_film diambil untuk membuat link
        $sql = "SELECT
                    t.id_testimonial, -- Add id_testimonial for deletion
                    t.message, t.rating,
                    u.name AS user_name,
                    f.id_film, -- Diperlukan untuk membuat link di frontend
                    f.title AS film_title
                FROM testimonials t
                JOIN users u ON t.id_user = u.id_user
                JOIN films f ON t.id_film = f.id_film
                ORDER BY t.created_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT ?";
        }

        $stmt = $conn->prepare($sql);
        if ($limit !== null) {
            $stmt->bind_param("i", $limit);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Membuat testimoni baru di database.
     */
    public static function create($conn, $user_id, $film_id, $booking_id, $rating, $message)
    {
        $sql = "INSERT INTO testimonials (id_user, id_film, id_booking, rating, message) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiis", $user_id, $film_id, $booking_id, $rating, $message);
        return $stmt->execute();
    }

    /**
     * Cek apakah user sudah memberikan ulasan untuk booking tertentu.
     */
    public static function hasUserReviewedBooking($conn, $user_id, $booking_id)
    {
        $sql = "SELECT id_testimonial FROM testimonials WHERE id_user = ? AND id_booking = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    /**
     * Menghapus testimoni dari database.
     *
     * @param object $conn Koneksi database.
     * @param int $testimonial_id ID testimoni yang akan dihapus.
     * @return bool True jika berhasil dihapus, false jika gagal.
     */
    public static function deleteTestimonial($conn, $testimonial_id)
    {
        $sql = "DELETE FROM testimonials WHERE id_testimonial = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $testimonial_id);
        return $stmt->execute();
    }
}