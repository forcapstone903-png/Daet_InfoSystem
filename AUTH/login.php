<?php
// login.php - Simple User Login (No API)
require_once '../dbconn.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('../dashboard.php');
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Query user from info_profiles
        $query = "SELECT id, email, full_name, password, role, status FROM info_profiles WHERE email = $1";
        $result = query($query, [$email]);
        
        if ($result && num_rows($result) > 0) {
            $user = fetchOne($result);
            
            // Check if user is active
            if ($user['status'] !== 'active') {
                $error = 'Your account is ' . $user['status'] . '. Please contact support.';
            } 
            // Verify password
            elseif (password_verify($password, $user['password'])) {
                // Update last_seen and online status
                $updateQuery = "UPDATE info_profiles SET last_seen = NOW(), is_online = TRUE WHERE id = $1";
                query($updateQuery, [$user['id']]);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                // Set remember me cookie if checked
                if ($remember) {
                    setcookie('remember_email', $email, time() + (86400 * 30), '/');
                }
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    redirect('../admin/dashboard.php');
                } else {
                    redirect('../dashboard.php');
                }
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'No account found with this email address.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Daeteño Tourist Information</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .auth-container {
            min-height: calc(100vh - 140px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background: linear-gradient(135deg, #f9f9f9 0%, #f0f9ff 100%);
        }
        .auth-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
            border-top: 4px solid #27ae60;
        }
        .btn-primary {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            cursor: pointer;
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(39, 174, 96, 0.3);
        }
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: #27ae60;
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
        }
        .alert-error {
            background-color: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        .remember-me { display: flex; align-items: center; gap: 0.5rem; }
        .remember-me input { width: auto; }
    </style>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="../index.php" class="text-2xl font-bold bg-gradient-to-r from-green-600 to-yellow-500 bg-clip-text text-transparent">
                    Daeteño
                </a>
                <div class="flex items-center space-x-4">
                    <a href="register.php" class="text-gray-600 hover:text-green-600 transition">Create Account</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="auth-container">
        <div class="auth-card">
            <div class="text-center mb-6">
                <div class="h-14 w-14 rounded-full bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-sign-in-alt text-white text-xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Welcome Back!</h1>
                <p class="text-gray-600 text-sm">Sign in to your Daeteño account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                    <input type="email" name="email" required class="form-input" 
                           value="<?php echo htmlspecialchars($_COOKIE['remember_email'] ?? ''); ?>"
                           placeholder="Enter your email">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                    <input type="password" name="password" required class="form-input" placeholder="Enter your password">
                </div>

                <div class="mb-6 flex justify-between items-center">
                    <label class="remember-me">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <a href="../forgot-password.php" class="text-green-600 text-sm hover:underline">Forgot password?</a>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </button>
            </form>

            <div class="text-center mt-6 pt-4 border-t border-gray-200">
                <p class="text-gray-600 text-sm">
                    Don't have an account? 
                    <a href="register.php" class="text-green-600 font-semibold hover:underline">Create one now</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>