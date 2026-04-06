<?php
// edit-profile.php - Edit Profile Page with Database Integration
require_once '../dbconn.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;

// Fetch current user data from database - REMOVED 'phone' column
$userQuery = "SELECT id, email, full_name, role, status, created_at FROM info_profiles WHERE id = $1";
$userResult = query($userQuery, [$user_id]);

if ($userResult === false) {
    $error = "Failed to fetch user data";
    $user = [
        'id' => $user_id,
        'full_name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? ''
    ];
} else {
    $userData = fetchOne($userResult);
    if ($userData) {
        $user = [
            'id' => $userData['id'],
            'full_name' => $userData['full_name'],
            'email' => $userData['email'],
            'role' => $userData['role'],
            'status' => $userData['status'],
            'created_at' => $userData['created_at']
        ];
    } else {
        $user = [
            'id' => $user_id,
            'full_name' => $_SESSION['user_name'] ?? 'User',
            'email' => $_SESSION['user_email'] ?? ''
        ];
    }
}

// CSRF token generation
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if it's a cancel action
    if (isset($_POST['cancel'])) {
        redirect('../dashboard.php');
    }
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors['general'] = 'Invalid security token. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['password'] ?? '';
        $password_confirmation = $_POST['password_confirmation'] ?? '';
        
        // Validation
        if (empty($name)) {
            $errors['name'] = 'Full name is required';
        } elseif (strlen($name) < 2) {
            $errors['name'] = 'Name must be at least 2 characters';
        }
        
        if (empty($email)) {
            $errors['email'] = 'Email address is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        }
        
        // Check if email is taken by another user
        if ($email !== $user['email']) {
            $checkQuery = "SELECT id FROM info_profiles WHERE email = $1 AND id != $2";
            $checkResult = query($checkQuery, [$email, $user_id]);
            if ($checkResult && num_rows($checkResult) > 0) {
                $errors['email'] = 'Email address is already used by another account';
            }
        }
        
        // Handle password change
        $passwordChanged = false;
        if (!empty($new_password)) {
            if (empty($current_password)) {
                $errors['current_password'] = 'Current password is required to set a new password';
            } elseif (strlen($new_password) < 8) {
                $errors['password'] = 'New password must be at least 8 characters';
            } elseif ($new_password !== $password_confirmation) {
                $errors['password_confirmation'] = 'Password confirmation does not match';
            } else {
                // Verify current password
                $passQuery = "SELECT password FROM info_profiles WHERE id = $1";
                $passResult = query($passQuery, [$user_id]);
                if ($passResult) {
                    $userPass = fetchOne($passResult);
                    if ($userPass && password_verify($current_password, $userPass['password'])) {
                        $passwordChanged = true;
                    } else {
                        $errors['current_password'] = 'Current password is incorrect';
                    }
                }
            }
        }
        
        // If no errors, update user data
        if (empty($errors)) {
            // Start building update query
            $updateFields = [];
            $params = [];
            $paramCounter = 1;
            
            // Add basic fields
            $updateFields[] = "full_name = $" . $paramCounter++;
            $params[] = $name;
            
            $updateFields[] = "email = $" . $paramCounter++;
            $params[] = $email;
            
            // Add password if changed
            if ($passwordChanged) {
                $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                $updateFields[] = "password = $" . $paramCounter++;
                $params[] = $hashedPassword;
            }
            
            // Add updated_at
            $updateFields[] = "updated_at = NOW()";
            
            // Add user_id as last parameter
            $params[] = $user_id;
            
            // Execute update
            $updateQuery = "UPDATE info_profiles SET " . implode(", ", $updateFields) . " WHERE id = $" . $paramCounter;
            $updateResult = query($updateQuery, $params);
            
            if ($updateResult !== false) {
                // Update session data
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                
                $success = true;
                
                // Refresh user data
                $user['full_name'] = $name;
                $user['email'] = $email;
            } else {
                $errors['general'] = 'Failed to update profile. Please try again.';
            }
        }
    }
}

$title = 'Edit Profile - Daeteño';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <title><?php echo $title; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .password-strength {
            transition: all 0.3s ease;
        }
        
        .strength-bar {
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        
        .input-error {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }
        
        .input-success {
            border-color: #10b981 !important;
        }
        
        .animate-shake {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .back-button {
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            transform: translateX(-5px);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <a href="../index.php" class="text-2xl font-bold bg-gradient-to-r from-green-600 to-yellow-500 bg-clip-text text-transparent">
                        Daeteño
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-6">
                    <a href="../index.php" class="text-gray-600 hover:text-green-600">Home</a>
                    <a href="../attractions.php" class="text-gray-600 hover:text-green-600">Attractions</a>
                    <a href="../events.php" class="text-gray-600 hover:text-green-600">Events</a>
                    <a href="../forum.php" class="text-gray-600 hover:text-green-600">Forum</a>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 text-gray-700">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-green-500 to-yellow-500 flex items-center justify-center text-white font-bold">
                                <?php echo substr($user['full_name'], 0, 1); ?>
                            </div>
                            <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-1 z-50">
                            <a href="../dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-green-50">Dashboard</a>
                            <a href="edit-profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-green-50">Profile</a>
                            <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Back to Dashboard Button -->
        <div class="mb-6">
            <a href="../dashboard.php" class="inline-flex items-center text-gray-600 hover:text-green-600 back-button">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Dashboard
            </a>
        </div>
        
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Edit Profile</h1>
            <p class="text-gray-600 mt-2">Update your personal information</p>
        </div>
        
        <!-- Success Message -->
        <?php if($success): ?>
        <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-lg border border-green-200 flex items-center justify-between" id="successMessage">
            <div>
                <i class="fas fa-check-circle mr-2"></i> Profile updated successfully!
            </div>
            <div class="flex space-x-2">
                <a href="../dashboard.php" class="text-green-700 hover:text-green-900 font-semibold">
                    Go to Dashboard →
                </a>
                <button onclick="this.parentElement.parentElement.remove()" class="text-green-700 hover:text-green-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Error Messages -->
        <?php if(isset($errors['general'])): ?>
        <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-lg border border-red-200 flex items-center justify-between">
            <div>
                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $errors['general']; ?>
            </div>
            <button onclick="this.parentElement.remove()" class="text-red-700 hover:text-red-900">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Profile Header -->
            <div class="bg-gradient-to-r from-green-500 to-yellow-500 px-8 py-6">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center text-2xl font-bold text-green-600">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <div class="text-white">
                        <h2 class="text-xl font-bold"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                        <p class="text-white/90">Member since <?php echo date('F Y', strtotime($user['created_at'] ?? 'now')); ?></p>
                        <p class="text-white/80 text-sm mt-1">
                            <i class="fas fa-tag mr-1"></i> <?php echo ucfirst($user['role'] ?? 'Tourist'); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="p-8">
                <form method="POST" action="edit-profile.php" id="profileForm">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                            <input type="text" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                   required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <?php if(isset($errors['name'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['name']; ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <?php if(isset($errors['email'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['email']; ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Role (Read-only) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Account Type</label>
                            <div class="w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-lg text-gray-600">
                                <i class="fas fa-<?php echo $user['role'] === 'admin' ? 'crown' : 'user'; ?> mr-2"></i>
                                <?php echo ucfirst($user['role'] ?? 'Tourist'); ?>
                            </div>
                        </div>
                        
                        <!-- Status (Read-only) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Account Status</label>
                            <div class="w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-lg text-gray-600">
                                <i class="fas fa-<?php echo $user['status'] === 'active' ? 'check-circle text-green-600' : 'ban text-red-600'; ?> mr-2"></i>
                                <?php echo ucfirst($user['status'] ?? 'Active'); ?>
                            </div>
                        </div>
                        
                        <!-- Current Password -->
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                            <div class="relative">
                                <input type="password" id="current_password" name="current_password"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                <button type="button" onclick="togglePassword('current_password')" 
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Required to change email or password</p>
                            <?php if(isset($errors['current_password'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['current_password']; ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- New Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                            <div class="relative">
                                <input type="password" id="password" name="password"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                <button type="button" onclick="togglePassword('password')" 
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="passwordStrength" class="mt-2 password-strength">
                                <div class="flex gap-1 h-1">
                                    <div class="strength-bar flex-1 rounded-full bg-gray-200"></div>
                                    <div class="strength-bar flex-1 rounded-full bg-gray-200"></div>
                                    <div class="strength-bar flex-1 rounded-full bg-gray-200"></div>
                                    <div class="strength-bar flex-1 rounded-full bg-gray-200"></div>
                                </div>
                                <p id="strengthText" class="text-xs mt-1 text-gray-500"></p>
                            </div>
                            <?php if(isset($errors['password'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['password']; ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                            <div class="relative">
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                <button type="button" onclick="togglePassword('password_confirmation')" 
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <?php if(isset($errors['password_confirmation'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['password_confirmation']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <a href="../dashboard.php" 
                               class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors">
                                <i class="fas fa-tachometer-alt mr-2"></i>
                                Back to Dashboard
                            </a>
                            <div class="flex space-x-4">
                                <a href="../dashboard.php" 
                                   class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors">
                                    Cancel
                                </a>
                                <button type="submit" 
                                        id="submitBtn"
                                        class="px-6 py-3 bg-gradient-to-r from-green-600 to-yellow-500 text-white font-semibold rounded-lg hover:opacity-90 transition-opacity">
                                    <i class="fas fa-save mr-2"></i>
                                    Save Changes
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
            field.setAttribute('type', type);
        }
        
        // Password strength meter
        const passwordInput = document.getElementById('password');
        const strengthBars = document.querySelectorAll('.strength-bar');
        const strengthText = document.getElementById('strengthText');
        
        function checkPasswordStrength(password) {
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[$@#&!]+/)) strength++;
            
            return strength;
        }
        
        function updateStrengthMeter(password) {
            const strength = checkPasswordStrength(password);
            
            strengthBars.forEach((bar, index) => {
                if (index < strength) {
                    bar.classList.remove('bg-gray-200');
                    if (strength <= 2) {
                        bar.classList.add('bg-red-500');
                    } else if (strength <= 3) {
                        bar.classList.add('bg-yellow-500');
                    } else {
                        bar.classList.add('bg-green-500');
                    }
                } else {
                    bar.classList.remove('bg-red-500', 'bg-yellow-500', 'bg-green-500');
                    bar.classList.add('bg-gray-200');
                }
            });
            
            if (password.length === 0) {
                strengthText.textContent = '';
            } else if (strength <= 2) {
                strengthText.textContent = 'Weak password';
                strengthText.className = 'text-xs mt-1 text-red-500';
            } else if (strength <= 3) {
                strengthText.textContent = 'Medium password';
                strengthText.className = 'text-xs mt-1 text-yellow-500';
            } else {
                strengthText.textContent = 'Strong password';
                strengthText.className = 'text-xs mt-1 text-green-500';
            }
        }
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                updateStrengthMeter(this.value);
                
                const confirmInput = document.getElementById('password_confirmation');
                if (confirmInput && confirmInput.value) {
                    validatePasswordMatch();
                }
            });
        }
        
        // Password confirmation validation
        function validatePasswordMatch() {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirmation').value;
            const confirmField = document.getElementById('password_confirmation');
            
            if (password !== confirm && confirm !== '') {
                confirmField.classList.add('input-error');
                confirmField.classList.remove('input-success');
                return false;
            } else if (confirm !== '') {
                confirmField.classList.remove('input-error');
                confirmField.classList.add('input-success');
            }
            return true;
        }
        
        const confirmInput = document.getElementById('password_confirmation');
        if (confirmInput) {
            confirmInput.addEventListener('input', validatePasswordMatch);
        }
        
        // Real-time validation for other fields
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        
        function validateName() {
            const name = nameInput.value.trim();
            if (name.length < 2 && name.length > 0) {
                nameInput.classList.add('input-error');
                return false;
            } else if (name.length >= 2) {
                nameInput.classList.remove('input-error');
                nameInput.classList.add('input-success');
                return true;
            }
            return true;
        }
        
        function validateEmail() {
            const email = emailInput.value.trim();
            const emailRegex = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
            if (!emailRegex.test(email) && email !== '') {
                emailInput.classList.add('input-error');
                return false;
            } else if (email !== '') {
                emailInput.classList.remove('input-error');
                emailInput.classList.add('input-success');
                return true;
            }
            return true;
        }
        
        if (nameInput) nameInput.addEventListener('input', validateName);
        if (emailInput) emailInput.addEventListener('input', validateEmail);
        
        // Form submission validation
        document.getElementById('profileForm')?.addEventListener('submit', function(e) {
            let isValid = true;
            
            if (!validateName()) {
                isValid = false;
                nameInput.classList.add('animate-shake');
                setTimeout(() => nameInput.classList.remove('animate-shake'), 500);
            }
            
            if (!validateEmail()) {
                isValid = false;
                emailInput.classList.add('animate-shake');
                setTimeout(() => emailInput.classList.remove('animate-shake'), 500);
            }
            
            const password = document.getElementById('password').value;
            const currentPassword = document.getElementById('current_password').value;
            
            if (password) {
                if (!currentPassword) {
                    alert('Current password is required to change your password');
                    isValid = false;
                } else if (!validatePasswordMatch()) {
                    isValid = false;
                    confirmInput.classList.add('animate-shake');
                    setTimeout(() => confirmInput.classList.remove('animate-shake'), 500);
                }
                
                const strength = checkPasswordStrength(password);
                if (strength < 3) {
                    alert('Please use a stronger password (at least 8 characters with mix of letters, numbers, and symbols)');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        // Auto-hide success message after 5 seconds
        setTimeout(function() {
            const successMsg = document.getElementById('successMessage');
            if (successMsg) successMsg.style.display = 'none';
        }, 5000);
    </script>
</body>
</html>