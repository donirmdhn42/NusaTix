<?php
function findUserByIdentifier($conn, $identifier) {
    $stmt = $conn->prepare("SELECT id_user, name, email, password, role, remember_token FROM users WHERE email = ? OR name = ?");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

function getUserById($conn, $userId) {
    $stmt = $conn->prepare("SELECT id_user, name, email, password, role, remember_token FROM users WHERE id_user = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

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

function registerNewUser($conn, $nama_lengkap, $email, $password) {
    $email_check = checkEmailAvailability($conn, $email);
    if ($email_check['status'] === 'taken') {
        return $email_check; 
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

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

function loginUser($conn, $identifier, $password) {
    $user = findUserByIdentifier($conn, $identifier);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true); 
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email']; 
        return ['status' => 'success', 'message' => 'Login berhasil!'];
    } else {
        return ['status' => 'error', 'message' => 'Email atau password salah.'];
    }
}


function updateUserRememberToken($conn, $userId, $token) {
    $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id_user = ?");
    if ($token === null) {
        $stmt->bind_param("si", $token, $userId); 
    } else {
        $stmt->bind_param("si", $token, $userId);
    }
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function getUserRoleById($conn, $userId) {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id_user = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user ? $user['role'] : null;
}

function getUserEmailById($conn, $userId) {
    $stmt = $conn->prepare("SELECT email FROM users WHERE id_user = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user ? $user['email'] : null;
}