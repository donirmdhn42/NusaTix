<?php
session_start();
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/models/user.php';

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    setcookie('remember_me', '', time() - 3600, "/"); 
    header("Location: ./index.php");
    exit;
}

if (isset($_COOKIE['remember_me']) && !isset($_SESSION['user_id'])) {
    list($user_id, $token) = explode(':', $_COOKIE['remember_me']);
    $user = getUserById($conn, $user_id); 

    if ($user && hash_equals($user['remember_token'], $token)) {
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];

        $new_token = bin2hex(random_bytes(32));
        updateUserRememberToken($conn, $user['id_user'], $new_token); 
        setcookie('remember_me', $user['id_user'] . ':' . $new_token, time() + (86400 * 30), "/"); 

    } else {
        setcookie('remember_me', '', time() - 3600, "/");
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'check_email') {
    header('Content-Type: application/json');
    $email = trim($_POST['email'] ?? '');
    $response = checkEmailAvailability($conn, $email);
    echo json_encode(['status' => $response['status'], 'message' => $response['message']]);
    exit;
}

$mode = $_GET['mode'] ?? 'login';
$error = $_SESSION['login_error'] ?? '';
$success = '';
unset($_SESSION['login_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_form'])) {
    $nama = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $registerResult = registerNewUser($conn, $nama, $email, $password);

    if ($registerResult['status'] === 'success') {
        $_SESSION['success_message'] = $registerResult['message'];
        header("Location: auth_user.php?mode=login");
        exit;
    } else {
        $error = $registerResult['message'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_form'])) {
    $login_identifier = $_POST['login']; 
    $password_input = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);

    $loginResult = loginUser($conn, $login_identifier, $password_input);

    if ($loginResult['status'] === 'success') {
        if ($remember_me) {
            $user_id = $_SESSION['user_id'];
            $token = bin2hex(random_bytes(32)); 
            updateUserRememberToken($conn, $user_id, $token); 
            setcookie('remember_me', $user_id . ':' . $token, time() + (86400 * 30), "/"); 
        } else {
            
            if (isset($_SESSION['user_id'])) {
                updateUserRememberToken($conn, $_SESSION['user_id'], null); 
            }
            setcookie('remember_me', '', time() - 3600, "/"); 
        }

        $redirect_url = $_GET['redirect_url'] ?? './index.php';
        header("Location: " . $redirect_url);
        exit;
    } else {
        $error = $loginResult['message'];
    }
}

if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-[#181111]">
<head>
    <title><?= $mode === 'login' ? 'Login' : 'Daftar' ?> - NusaTix</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg viewBox='0 0 48 48' fill='%23ff0000' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z' fill='currentColor'%3E%3C/path%3E%3C/svg%3E" type="image/svg+xml">
    <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin="" />
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'" href="https://fonts.googleapis.com/css2?display=swap&family=Be+Vietnam+Pro%3Awght%4400%3B500%3B700%3B900&family=Noto+Sans%3Awght%4400%3B500%3B700%3B900" />
    <style>
        body { font-family: "Be Vietnam Pro", "Noto Sans", sans-serif; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .animate-fade-in { animation: fadeIn 0.8s ease-out forwards; }
        [x-cloak] { display: none !important; }
        input[type="text"]:-webkit-autofill,
        input[type="email"]:-webkit-autofill,
        input[type="password"]:-webkit-autofill,
        input[type="text"]:-webkit-autofill:hover,
        input[type="email"]:-webkit-autofill:hover,
        input[type="password"]:-webkit-autofill:hover,
        input[type="text"]:-webkit-autofill:focus,
        input[type="email"]:-webkit-autofill:focus,
        input[type="password"]:-webkit-autofill:focus {
            -webkit-text-fill-color: #E5E7EB !important; 
            -webkit-box-shadow: 0 0 0px 1000px #211717 inset !important; 
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>
</head>
<body class="h-full font-sans bg-[#181111] text-gray-100">
<main class="flex min-h-screen w-full items-center justify-center p-4">
    <div class="w-full max-w-4xl animate-fade-in">
        <div class="grid grid-cols-1 md:grid-cols-2 bg-[#211717] shadow-2xl rounded-2xl overflow-hidden border border-white/10">

            <div class="flex flex-col justify-center p-10 bg-[#140e0e] text-center">
                <a href="./index.php" class="flex flex-col items-center justify-center gap-4 mb-6">
                    <div class="size-20 text-red-600">
                        <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"></path>
                        </svg>
                    </div>
                    <h2 class="text-5xl font-extrabold text-white tracking-tight">NusaTix</h2>
                </a>
                <p class="mt-4 text-gray-300 leading-snug text-lg">
                    Film terbaik, pengalaman terbaik,<br>hanya di NusaTix.
                </p>
                <p class="mt-2 text-gray-500 text-md">
                    Pesan tiketmu sekarang juga!
                </p>
            </div>


            <div class="flex flex-col justify-center p-8 sm:p-12" x-data="authForm()" x-cloak>
                <div class="w-full relative" style="min-height: 400px;">
                    <div x-show="isLoading" x-transition.opacity class="absolute inset-0 bg-[#211717]/80 backdrop-blur-sm flex items-center justify-center z-20 rounded-xl">
                        <svg class="animate-spin h-8 w-8 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </div>

                    <div x-show="mode === 'login'">
                        <h2 class="text-2xl font-bold text-white">Selamat Datang Kembali</h2>
                        <p class="mt-2 text-sm text-gray-400">Belum punya akun? <a href="#" @click.prevent="switchMode('register')" class="font-medium text-red-600 hover:text-red-500">Buat akun baru</a></p>
                        <form method="POST" action="auth_user.php?mode=login" @submit="isLoading = true" class="mt-8 space-y-6">
                           <input type="hidden" name="login_form" value="1">
                            <div>
                                <label for="login" class="block text-sm font-medium text-gray-300">Email Anda</label>
                                <input id="login" name="login" type="email" placeholder="Silakan masukkan email Anda" required class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md shadow-sm placeholder-gray-400 text-white focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500">
                            </div>
                            <div>
                                <label for="password_login" class="block text-sm font-medium text-gray-300">Password</label>
                                <input id="password_login" name="password" type="password" placeholder="Silakan masukkan password Anda" required class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md shadow-sm placeholder-gray-400 text-white focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500">
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <input id="remember_me" name="remember_me" type="checkbox" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-600 rounded bg-gray-800">
                                    <label for="remember_me" class="ml-2 block text-sm text-gray-300">Ingat Saya</label>
                                </div>
                            </div>
                            <div><button type="submit" class="w-full flex justify-center py-2.5 px-4 rounded-md shadow-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Masuk ke Akun</button></div>
                        </form>
                    </div>

                    <div x-show="mode === 'register'">
                        <h2 class="text-2xl font-bold text-white">Buat Akun Baru</h2>
                        <p class="mt-2 text-sm text-gray-400">Sudah punya akun? <a href="#" @click.prevent="switchMode('login')" class="font-medium text-red-600 hover:text-red-500">Login</a></p>
                        <form method="POST" action="auth_user.php?mode=register" @submit.prevent="validateAndSubmitRegister()" class="mt-8 space-y-6">
                            <input type="hidden" name="register_form" value="1">
                            <div>
                                <label for="nama_lengkap" class="block text-sm font-medium text-gray-300">Nama Lengkap</label>
                                <input id="nama_lengkap" type="text" name="nama_lengkap" x-model="form.nama_lengkap" placeholder="Masukkan nama lengkap Anda" required class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md shadow-sm placeholder-gray-400 text-white focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500">
                            </div>
                            <div>
                                <label for="email_reg" class="block text-sm font-medium text-gray-300">Alamat Email</label>
                                <input id="email_reg" type="email" name="email" x-model="form.email" placeholder="contoh@email.com" required class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md shadow-sm placeholder-gray-400 text-white focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500">
                            </div>
                            <div>
                                <label for="password_reg" class="block text-sm font-medium text-gray-300">Buat Password</label>
                                <input id="password_reg" type="password" name="password" x-model="form.password" placeholder="Minimal 6 karakter" required class="mt-1 block w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md shadow-sm placeholder-gray-400 text-white focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500">
                            </div>
                            <div><button type="submit" class="w-full flex justify-center py-2.5 px-4 rounded-md shadow-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Daftar Akun</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
    const swalTheme = {
        confirmButtonColor: '#e92932',
        customClass: {
            popup: 'rounded-xl shadow-lg bg-[#211717]',
            title: 'text-white',
            htmlContainer: 'text-gray-300'
        },
        background: '#211717',
        color: '#E5E7EB'
    };
    function authForm() {
        return {
            mode: '<?= $mode ?>',
            isLoading: false,
            form: { nama_lengkap: '', email: '', password: '' },
            switchMode(newMode) {
                this.isLoading = true;
                setTimeout(() => {
                    this.mode = newMode;
                    this.form.nama_lengkap = '';
                    this.form.email = '';
                    this.form.password = '';
                    this.isLoading = false;
                }, 200);
            },
            async validateAndSubmitRegister() {
                this.isLoading = true;
                if (this.form.nama_lengkap.trim() === '') {
                    Swal.fire({...swalTheme, icon: 'error', title: 'Nama Lengkap Kosong', text: 'Silakan masukkan nama lengkap Anda.'});
                    this.isLoading = false;
                    return;
                }
                if (this.form.email.trim() === '' || !this.form.email.includes('@')) {
                    Swal.fire({...swalTheme, icon: 'error', title: 'Email Tidak Valid', text: 'Silakan masukkan alamat email yang benar.'});
                    this.isLoading = false;
                    return;
                }
                if (this.form.password.trim().length < 6) {
                    Swal.fire({...swalTheme, icon: 'error', title: 'Password Terlalu Pendek', text: 'Password minimal harus 6 karakter.'});
                    this.isLoading = false;
                    return;
                }

                try {
                    const response = await fetch('auth_user.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `action=check_email&email=${encodeURIComponent(this.form.email)}`
                    });
                    const data = await response.json();

                    if (data.status === 'taken') {
                        Swal.fire({...swalTheme, icon: 'error', title: 'Email Sudah Terdaftar', text: 'Silakan gunakan email lain atau login dengan email ini.'});
                        this.isLoading = false;
                    } else if (data.status === 'available') {
                        this.$el.submit(); 
                    } else {
                        Swal.fire({...swalTheme, icon: 'error', title: 'Error', text: data.message || 'Terjadi kesalahan. Silakan coba lagi.'});
                        this.isLoading = false;
                    }
                } catch (error) {
                    this.isLoading = false;
                    Swal.fire({...swalTheme, icon: 'error', title: 'Error', text: 'Gagal menghubungi server.'});
                }
            },
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($error): ?>
            Swal.fire({...swalTheme, icon: 'error', title: 'Gagal!', text: '<?= addslashes(htmlspecialchars($error)) ?>'});
        <?php endif; ?>
        <?php if ($success): ?>
            Swal.fire({...swalTheme, icon: 'success', title: 'Berhasil!', text: '<?= addslashes(htmlspecialchars($success)) ?>'});
        <?php endif; ?>
    });
</script>
</body>
</html>