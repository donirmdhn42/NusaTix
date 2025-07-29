<?php
// admin/register_admin.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// Pesan untuk ditampilkan setelah submit
$error_message = '';
$success_message = '';

// Proses form hanya jika metode request adalah POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Memanggil file koneksi database
    require_once __DIR__ . '/../backend/db.php';

    // Ambil data dari form
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validasi dasar
    if (empty($name) || empty($email) || empty($password)) {
        $error_message = "Semua field wajib diisi.";
    } else {
        // 1. Cek apakah email sudah ada
        $stmt_check = $conn->prepare("SELECT id_user FROM users WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error_message = 'Email sudah terdaftar. Silakan gunakan email lain.';
        } else {
            // 2. Hash password untuk keamanan
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // 3. Simpan admin baru ke database dengan role 'admin'
            $stmt_insert = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt_insert->bind_param("sss", $name, $email, $hashedPassword);
            
            if ($stmt_insert->execute()) {
                $success_message = 'Akun admin berhasil dibuat! Silakan hapus file ini dan login.';
            } else {
                $error_message = 'Terjadi kesalahan pada server. Gagal mendaftar.';
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="w-full max-w-md">
        <form action="register_admin.php" method="POST" class="bg-white shadow-xl rounded-lg px-8 pt-6 pb-8 mb-4">
            <div class="text-center mb-6">
                <i class="fas fa-user-shield text-5xl text-red-600"></i>
                <h1 class="text-2xl font-bold text-gray-800 mt-2">Registrasi Akun Admin</h1>
            </div>

            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p class="font-bold">Peringatan Keamanan!</p>
                <p>Halaman ini sangat sensitif. Setelah berhasil membuat akun, segera hapus file <strong>register_admin.php</strong> dari server Anda.</p>
            </div>

            <?php if ($success_message): ?>
                <p class="bg-green-100 text-green-700 text-sm px-4 py-3 rounded-md mb-4"><?php echo $success_message; ?></p>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <p class="bg-red-100 text-red-700 text-sm px-4 py-3 rounded-md mb-4"><?php echo $error_message; ?></p>
            <?php endif; ?>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Nama Lengkap</label>
                <input class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" id="name" name="name" type="text" placeholder="Nama Admin" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                <input class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" id="email" name="email" type="email" placeholder="admin@bioskop.com" required>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                <input class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" id="password" name="password" type="password" placeholder="******************" required>
            </div>
            <div class="flex flex-col items-center justify-center gap-4">
                <button class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline w-full" type="submit">
                    Daftarkan Admin
                </button>
                <a href="login.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                    Kembali ke Login
                </a>
            </div>
        </form>
    </div>
</body>
</html>