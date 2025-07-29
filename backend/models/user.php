<?php
// backend/user.php

/**
 * Finds a user by email or name (username).
 *
 * @param mysqli $conn The database connection object.
 * @param string $identifier The email or name (username) to search for.
 * @return array|null The user row if found, otherwise null.
 */
function findUserByIdentifier($conn, $identifier) {
    // Menambahkan kolom remember_token dalam SELECT
    $stmt = $conn->prepare("SELECT id_user, name, email, password, role, remember_token FROM users WHERE email = ? OR name = ?");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

/**
 * Finds a user by ID.
 *
 * @param mysqli $conn The database connection object.
 * @param int $userId The ID of the user to search for.
 * @return array|null The user row if found, otherwise null.
 */
function getUserById($conn, $userId) {
    // Menambahkan kolom remember_token dalam SELECT
    $stmt = $conn->prepare("SELECT id_user, name, email, password, role, remember_token FROM users WHERE id_user = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

/**
 * Checks if an email is already registered.
 *
 * @param mysqli $conn The database connection object.
 * @param string $email The email to check.
 * @return array A response array indicating availability.
 */
function checkEmailAvailability($conn, $email) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row['count'] > 0) {
        return ['status' => 'taken', 'message' => 'Email sudah terdaftar.'];
    } else {
        return ['status' => 'available', 'message' => 'Email tersedia.'];
    }
}

/**
 * Registers a new user.
 *
 * @param mysqli $conn The database connection object.
 * @param string $nama_lengkap The full name of the user.
 * @param string $email The user's email.
 * @param string $password The raw password.
 * @return array A response array indicating success or failure.
 */
function registerNewUser($conn, $nama_lengkap, $email, $password) {
    // Check if email already exists first
    $email_check = checkEmailAvailability($conn, $email);
    if ($email_check['status'] === 'taken') {
        return $email_check; // Return the 'taken' status and message
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Default role for new registrations is 'user'
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
    $stmt->bind_param("sss", $nama_lengkap, $email, $hashed_password);

    if ($stmt->execute()) {
        $stmt->close();
        return ['status' => 'success', 'message' => 'Registrasi berhasil! Silakan login.'];
    } else {
        $error_message = $stmt->error;
        $stmt->close();
        return ['status' => 'error', 'message' => 'Registrasi gagal: ' . $error_message];
    }
}

/**
 * Logs in a user.
 *
 * @param mysqli $conn The database connection object.
 * @param string $identifier The email or username.
 * @param string $password The raw password.
 * @return array A response array indicating success or failure.
 */
function loginUser($conn, $identifier, $password) {
    $user = findUserByIdentifier($conn, $identifier);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true); // Mencegah session fixation
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email']; // Menyimpan email di sesi

        return ['status' => 'success', 'message' => 'Login berhasil!'];
    } else {
        return ['status' => 'error', 'message' => 'Email atau password salah.'];
    }
}

/**
 * Updates the remember token for a user.
 *
 * @param mysqli $conn The database connection object.
 * @param int $userId The ID of the user.
 * @param string|null $token The new remember token, or null to clear it.
 * @return bool True on success, false on failure.
 */
function updateUserRememberToken($conn, $userId, $token) {
    $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id_user = ?");
    if ($token === null) {
        $stmt->bind_param("si", $token, $userId); // 's' for NULL to be treated as string for binding
    } else {
        $stmt->bind_param("si", $token, $userId);
    }
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// Tambahkan fungsi getUserRoleById jika diperlukan di tempat lain
function getUserRoleById($conn, $userId) {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id_user = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user ? $user['role'] : null;
}

// Tambahkan fungsi getUserEmailById jika diperlukan di tempat lain
function getUserEmailById($conn, $userId) {
    $stmt = $conn->prepare("SELECT email FROM users WHERE id_user = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user ? $user['email'] : null;
}