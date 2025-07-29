<?php
session_start();
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header("Location: dashboard.php");
    exit();
}

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth.php';

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['identifier'] ?? '';
    $password = $_POST['password'] ?? '';

    if (login($conn, $identifier, $password)) {
        // Cek apakah rolenya admin
        if ($_SESSION['user_role'] === 'admin') {
            header("Location: dashboard.php");
            exit();
        } else {
            session_unset();
            session_destroy();
            $error_message = "Akses ditolak. Hanya admin yang dapat login.";
        }
    } else {
        $error_message = "Email/Nama atau password salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="w-full max-w-sm">
        <form action="login.php" method="POST" class="bg-white shadow-xl rounded-lg px-8 pt-6 pb-8 mb-4">
            <div class="text-center mb-8">
                <i class="fas fa-film text-5xl text-blue-600"></i>
                <h1 class="text-2xl font-bold text-gray-800 mt-2">Admin Panel Login</h1>
            </div>
            <?php if ($error_message): ?>
                <p class="bg-red-100 text-red-700 text-sm px-4 py-2 rounded-md mb-4"><?php echo $error_message; ?></p>
            <?php endif; ?>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="identifier">Email atau Nama</label>
                <input class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" id="identifier" name="identifier" type="text" placeholder="admin@bioskop.com" required>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                <input class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" id="password" name="password" type="password" placeholder="******************" required>
            </div>
            <div class="flex items-center justify-center">
                <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline w-full" type="submit">
                    Sign In
                </button>
            </div>
        </form>
    </div>
</body>
</html>