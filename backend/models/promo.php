<?php
if (!function_exists('applyPromo')) {

    function applyPromo(mysqli $conn, string $promo_code, float $base_price, int $user_id): array
    {
        $stmt = $conn->prepare("SELECT * FROM promos WHERE code = ? AND is_active = 1 AND valid_from <= CURDATE() AND valid_until >= CURDATE()");
        $stmt->bind_param("s", $promo_code);
        $stmt->execute();
        $promo = $stmt->get_result()->fetch_assoc();

        if (!$promo) {
            return ['success' => false, 'message' => 'Kode promo tidak valid atau kedaluwarsa.'];
        }

        if ($base_price < $promo['min_purchase']) {
            return ['success' => false, 'message' => 'Belanja minimal Rp ' . number_format($promo['min_purchase']) . ' untuk promo ini.'];
        }

        $stmt_check = $conn->prepare("SELECT COUNT(*) as total_used FROM bookings WHERE id_user = ? AND id_promo = ? AND status IN ('booked', 'pending', 'paid')");
        $stmt_check->bind_param("ii", $user_id, $promo['id_promo']);
        $stmt_check->execute();
        $usage = $stmt_check->get_result()->fetch_assoc();

        if (isset($promo['usage_limit_per_user']) && $promo['usage_limit_per_user'] > 0 && $usage['total_used'] >= $promo['usage_limit_per_user']) {
            return ['success' => false, 'message' => 'Anda sudah menggunakan promo ini pada booking lain.'];
        }

        $discount_amount = 0;
        if ($promo['discount_type'] === 'percent') {
            $discount_amount = $base_price * ($promo['discount_value'] / 100);
            if (isset($promo['max_discount']) && $promo['max_discount'] > 0 && $discount_amount > $promo['max_discount']) {
                $discount_amount = $promo['max_discount'];
            }
        } elseif ($promo['discount_type'] === 'fixed') {
            $discount_amount = $promo['discount_value'];
        }
        
        $final_price = max(0, $base_price - $discount_amount);

        return [
            'success' => true,
            'message' => 'Promo berhasil digunakan!',
            'final_price' => $final_price,
            'discount_applied' => $discount_amount,
            'id_promo' => $promo['id_promo']
        ];
    }
}

function getAllPromos($conn) {
    $sql = "SELECT * FROM promos ORDER BY valid_until DESC";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getPromoById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM promos WHERE id_promo = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $promo = $result->fetch_assoc();
    $stmt->close();
    return $promo;
}

function savePromo($conn, $data) {
    $id = intval($data['id_promo'] ?? 0);
    $code = $data['code'];
    $description = $data['description'];
    $discount_type = $data['discount_type'];
    $discount_value = $data['discount_value'];
    $min_purchase = !empty($data['min_purchase']) ? $data['min_purchase'] : 0;
    $max_discount = !empty($data['max_discount']) ? $data['max_discount'] : null;
    $valid_from = $data['valid_from'];
    $valid_until = $data['valid_until'];
    $is_active = isset($data['is_active']) ? 1 : 0;
    $usage_limit = !empty($data['usage_limit_per_user']) ? intval($data['usage_limit_per_user']) : 1;

    if ($id > 0) { 
        $stmt = $conn->prepare("UPDATE promos SET code=?, description=?, discount_type=?, discount_value=?, min_purchase=?, max_discount=?, valid_from=?, valid_until=?, is_active=?, usage_limit_per_user=? WHERE id_promo=?");
        $stmt->bind_param("sssddsssiii", $code, $description, $discount_type, $discount_value, $min_purchase, $max_discount, $valid_from, $valid_until, $is_active, $usage_limit, $id);
    } else { 
        $stmt = $conn->prepare("INSERT INTO promos (code, description, discount_type, discount_value, min_purchase, max_discount, valid_from, valid_until, is_active, usage_limit_per_user) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssddsssii", $code, $description, $discount_type, $discount_value, $min_purchase, $max_discount, $valid_from, $valid_until, $is_active, $usage_limit);
    }

    $is_success = $stmt->execute();
    $stmt->close();
    return $is_success;
}

function deletePromoById($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM promos WHERE id_promo = ?");
    $stmt->bind_param("i", $id);
    $is_success = $stmt->execute();
    $stmt->close();
    return $is_success;
}