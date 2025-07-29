<?php
class Testimonial
{
    public static function getAllTestimonials($conn, $limit = null) 
    {
        $sql = "SELECT
                    t.id_testimonial, 
                    t.message, t.rating,
                    u.name AS user_name,
                    f.id_film, 
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

    public static function create($conn, $user_id, $film_id, $booking_id, $rating, $message)
    {
        $sql = "INSERT INTO testimonials (id_user, id_film, id_booking, rating, message) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiis", $user_id, $film_id, $booking_id, $rating, $message);
        return $stmt->execute();
    }

    public static function hasUserReviewedBooking($conn, $user_id, $booking_id)
    {
        $sql = "SELECT id_testimonial FROM testimonials WHERE id_user = ? AND id_booking = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    public static function deleteTestimonial($conn, $testimonial_id)
    {
        $sql = "DELETE FROM testimonials WHERE id_testimonial = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $testimonial_id);
        return $stmt->execute();
    }
}