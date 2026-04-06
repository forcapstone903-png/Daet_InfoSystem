<?php
// register.php - Simple User Registration (No API)
require_once '../dbconn.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('../dashboard.php');
}

$errors = [];
$old = [];
$success = false;

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirmation = $_POST['password_confirmation'] ?? '';
    $old = ['name' => $name, 'email' => $email];
    
    // Validation
    if (empty($name)) {
        $errors['name'] = 'Full name is required.';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }
    
    if ($password !== $passwordConfirmation) {
        $errors['password_confirmation'] = 'Passwords do not match.';
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $checkQuery = "SELECT id FROM info_profiles WHERE email = $1";
        $checkResult = query($checkQuery, [$email]);
        if ($checkResult && num_rows($checkResult) > 0) {
            $errors['email'] = 'Email address is already registered.';
        }
    }
    
    if (empty($errors)) {
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Generate UUID for new user
        $userId = generateUUID();
        
        // Insert into info_profiles
        $insertQuery = "INSERT INTO info_profiles (id, email, full_name, password, role, status, created_at, updated_at) 
                        VALUES ($1, $2, $3, $4, 'tourist', 'active', NOW(), NOW())";
        
        $result = query($insertQuery, [$userId, $email, $name, $hashedPassword]);
        
        if ($result) {
            // Create rewards entry
            $rewardsQuery = "INSERT INTO info_rewards (user_id, total_points, badges, level, updated_at) 
                            VALUES ($1, 0, '{}', 'beginner', NOW())";
            query($rewardsQuery, [$userId]);
            
            // Set session variables
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['role'] = 'tourist';
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Redirect to dashboard
            redirect('../dashboard.php');
        } else {
            $errors['general'] = 'Registration failed. Please try again.';
            error_log("Registration failed for email: $email");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Daeteño Tourist Information</title>
    
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
            border-top: 4px solid #f1c40f;
        }
        .btn-primary {
            background: linear-gradient(135deg, #f1c40f 0%, #f39c12 100%);
            color: #2c3e50;
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
            box-shadow: 0 8px 20px rgba(241, 196, 15, 0.3);
        }
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
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
            border-color: #f1c40f;
            box-shadow: 0 0 0 3px rgba(241, 196, 15, 0.1);
        }
        .error-message { color: #e53e3e; font-size: 0.875rem; margin-top: 0.25rem; }
        .input-error { border-color: #e53e3e; }
        .success-message { color: #10b981; font-size: 0.875rem; margin-top: 0.25rem; }
        .alert-error {
            background-color: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="../index.php" class="text-2xl font-bold bg-gradient-to-r from-green-600 to-yellow-500 bg-clip-text text-transparent">
                    Daeteño
                </a>
                <div class="flex items-center space-x-4">
                    <a href="login.php" class="text-gray-600 hover:text-green-600 transition">Login</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="auth-container">
        <div class="auth-card">
            <div class="text-center mb-6">
                <div class="h-14 w-14 rounded-full bg-gradient-to-br from-yellow-400 to-yellow-600 flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-user-plus text-white text-xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Create Your Account</h1>
                <p class="text-gray-600 text-sm mt-1">Join Daeteño to explore tourist spots and events</p>
            </div>

            <?php if (isset($errors['general'])): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($errors['general']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-user mr-2"></i>Full Name
                    </label>
                    <input type="text" name="name" required 
                           class="form-input <?php echo isset($errors['name']) ? 'input-error' : ''; ?>"
                           value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>"
                           placeholder="Enter your full name">
                    <?php if (isset($errors['name'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['name']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-2"></i>Email Address
                    </label>
                    <input type="email" name="email" required 
                           class="form-input <?php echo isset($errors['email']) ? 'input-error' : ''; ?>"
                           value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>"
                           placeholder="Enter your email address">
                    <?php if (isset($errors['email'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2"></i>Password
                    </label>
                    <input type="password" name="password" required 
                           class="form-input <?php echo isset($errors['password']) ? 'input-error' : ''; ?>"
                           placeholder="Create a strong password (min. 8 characters)">
                    <div class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-info-circle"></i> Password must be at least 8 characters
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['password']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2"></i>Confirm Password
                    </label>
                    <input type="password" name="password_confirmation" required 
                           class="form-input <?php echo isset($errors['password_confirmation']) ? 'input-error' : ''; ?>"
                           placeholder="Confirm your password">
                    <?php if (isset($errors['password_confirmation'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['password_confirmation']); ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-primary" id="submitBtn">
                    <i class="fas fa-user-plus mr-2"></i>Create Account
                </button>
            </form>

            <div class="text-center mt-6 pt-4 border-t border-gray-200">
                <p class="text-gray-600 text-sm">
                    Already have an account? 
                    <a href="login.php" class="text-yellow-600 font-semibold hover:underline">Sign in here</a>
                </p>
            </div>
        </div>
    </div>

    <footer class="bg-white border-t border-gray-200 py-6">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-gray-500 text-sm">&copy; <?php echo date('Y'); ?> Daeteño Tourist Information System. All rights reserved.</p>
        </div>
    </footer>

    <script>
        document.getElementById('registerForm')?.addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]').value;
            const confirm = document.querySelector('input[name="password_confirmation"]').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating Account...';
        });
    </script>
</body>
</html>