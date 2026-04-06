<?php
// update-password-form.php - Password Update Component
session_start();

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

$errors = [];
$status = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors['general'] = 'Invalid security token';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $password = $_POST['password'] ?? '';
        $password_confirmation = $_POST['password_confirmation'] ?? '';
        
        if (empty($current_password)) {
            $errors['current_password'] = 'Current password is required';
        } elseif ($current_password !== 'password123') { // Simulated check
            $errors['current_password'] = 'Current password is incorrect';
        }
        
        if (empty($password)) {
            $errors['password'] = 'New password is required';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors['password'] = 'Password must contain at least one uppercase letter';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Password must contain at least one number';
        }
        
        if ($password !== $password_confirmation) {
            $errors['password_confirmation'] = 'Password confirmation does not match';
        }
        
        if (empty($errors)) {
            // In real app, update password in database
            $status = 'password-updated';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f9fafb; }
        .fade-out {
            animation: fadeOut 2s ease-out forwards;
        }
        @keyframes fadeOut {
            0% { opacity: 1; }
            70% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; }
        }
        .input-error { border-color: #ef4444 !important; background-color: #fef2f2 !important; }
        .input-success { border-color: #10b981 !important; }
        .strength-bar { transition: all 0.3s ease; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-2xl mx-auto py-8 px-4">
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-white">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-lock text-purple-600 mr-2"></i> Update Password
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Ensure your account is using a long, random password to stay secure.
                </p>
            </div>
            
            <div class="p-6">
                <form method="POST" action="" id="passwordForm" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="_method" value="put">
                    
                    <!-- Current Password -->
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                        <div class="relative">
                            <input type="password" id="current_password" name="current_password" 
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 pr-10">
                            <button type="button" onclick="togglePassword('current_password')" 
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <?php if(isset($errors['current_password'])): ?>
                        <p class="mt-2 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i> <?php echo $errors['current_password']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- New Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" 
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 pr-10">
                            <button type="button" onclick="togglePassword('password')" 
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        
                        <!-- Password Strength Meter -->
                        <div id="passwordStrength" class="mt-2">
                            <div class="flex gap-1 h-1.5">
                                <div class="strength-bar flex-1 rounded-full bg-gray-200"></div>
                                <div class="strength-bar flex-1 rounded-full bg-gray-200"></div>
                                <div class="strength-bar flex-1 rounded-full bg-gray-200"></div>
                                <div class="strength-bar flex-1 rounded-full bg-gray-200"></div>
                            </div>
                            <p id="strengthText" class="text-xs mt-1 text-gray-500"></p>
                        </div>
                        
                        <!-- Password Requirements -->
                        <ul id="passwordRequirements" class="mt-2 text-xs space-y-1">
                            <li id="req-length" class="text-gray-500"><i class="fas fa-circle mr-1 text-[8px]"></i> At least 8 characters</li>
                            <li id="req-upper" class="text-gray-500"><i class="fas fa-circle mr-1 text-[8px]"></i> At least 1 uppercase letter</li>
                            <li id="req-number" class="text-gray-500"><i class="fas fa-circle mr-1 text-[8px]"></i> At least 1 number</li>
                        </ul>
                        
                        <?php if(isset($errors['password'])): ?>
                        <p class="mt-2 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i> <?php echo $errors['password']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <div class="relative">
                            <input type="password" id="password_confirmation" name="password_confirmation" 
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 pr-10">
                            <button type="button" onclick="togglePassword('password_confirmation')" 
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="confirmMatch" class="mt-1 text-xs"></div>
                        <?php if(isset($errors['password_confirmation'])): ?>
                        <p class="mt-2 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i> <?php echo $errors['password_confirmation']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Submit Section -->
                    <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
                        <button type="submit" id="saveBtn"
                                class="px-5 py-2.5 bg-purple-600 text-white font-medium rounded-lg hover:bg-purple-700 transition-colors shadow-sm">
                            <i class="fas fa-key mr-2"></i> Update Password
                        </button>
                        
                        <?php if($status === 'password-updated'): ?>
                        <p id="savedMessage" class="text-sm text-green-600">
                            <i class="fas fa-check-circle mr-1"></i> Password updated successfully!
                        </p>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </section>
    </div>
    
    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
            field.setAttribute('type', type);
        }
        
        // Password strength and requirements checker
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirmation');
        const strengthBars = document.querySelectorAll('.strength-bar');
        const strengthText = document.getElementById('strengthText');
        const reqLength = document.getElementById('req-length');
        const reqUpper = document.getElementById('req-upper');
        const reqNumber = document.getElementById('req-number');
        const confirmMatchDiv = document.getElementById('confirmMatch');
        
        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[$@#&!]/.test(password)) strength++;
            return Math.min(strength, 4);
        }
        
        function updateRequirements(password) {
            // Length check
            if (password.length >= 8) {
                reqLength.innerHTML = '<i class="fas fa-check-circle text-green-500 mr-1 text-xs"></i> At least 8 characters';
                reqLength.classList.remove('text-gray-500');
                reqLength.classList.add('text-green-600');
            } else {
                reqLength.innerHTML = '<i class="fas fa-circle mr-1 text-[8px]"></i> At least 8 characters';
                reqLength.classList.remove('text-green-600');
                reqLength.classList.add('text-gray-500');
            }
            
            // Uppercase check
            if (/[A-Z]/.test(password)) {
                reqUpper.innerHTML = '<i class="fas fa-check-circle text-green-500 mr-1 text-xs"></i> At least 1 uppercase letter';
                reqUpper.classList.remove('text-gray-500');
                reqUpper.classList.add('text-green-600');
            } else {
                reqUpper.innerHTML = '<i class="fas fa-circle mr-1 text-[8px]"></i> At least 1 uppercase letter';
                reqUpper.classList.remove('text-green-600');
                reqUpper.classList.add('text-gray-500');
            }
            
            // Number check
            if (/[0-9]/.test(password)) {
                reqNumber.innerHTML = '<i class="fas fa-check-circle text-green-500 mr-1 text-xs"></i> At least 1 number';
                reqNumber.classList.remove('text-gray-500');
                reqNumber.classList.add('text-green-600');
            } else {
                reqNumber.innerHTML = '<i class="fas fa-circle mr-1 text-[8px]"></i> At least 1 number';
                reqNumber.classList.remove('text-green-600');
                reqNumber.classList.add('text-gray-500');
            }
        }
        
        function updateStrengthMeter(password) {
            const strength = checkPasswordStrength(password);
            
            strengthBars.forEach((bar, index) => {
                bar.classList.remove('bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500', 'bg-gray-200');
                if (index < strength) {
                    if (strength <= 1) bar.classList.add('bg-red-500');
                    else if (strength === 2) bar.classList.add('bg-orange-500');
                    else if (strength === 3) bar.classList.add('bg-yellow-500');
                    else bar.classList.add('bg-green-500');
                } else {
                    bar.classList.add('bg-gray-200');
                }
            });
            
            if (password.length === 0) {
                strengthText.textContent = '';
            } else if (strength <= 1) {
                strengthText.textContent = 'Weak password';
                strengthText.className = 'text-xs mt-1 text-red-500';
            } else if (strength === 2) {
                strengthText.textContent = 'Fair password';
                strengthText.className = 'text-xs mt-1 text-orange-500';
            } else if (strength === 3) {
                strengthText.textContent = 'Good password';
                strengthText.className = 'text-xs mt-1 text-yellow-600';
            } else {
                strengthText.textContent = 'Strong password!';
                strengthText.className = 'text-xs mt-1 text-green-600 font-medium';
            }
        }
        
        function validatePasswordMatch() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (confirm === '') {
                confirmMatchDiv.innerHTML = '';
                confirmInput.classList.remove('input-error', 'input-success');
                return true;
            }
            
            if (password === confirm) {
                confirmMatchDiv.innerHTML = '<i class="fas fa-check-circle text-green-500 mr-1"></i> Passwords match';
                confirmMatchDiv.className = 'mt-1 text-xs text-green-600';
                confirmInput.classList.remove('input-error');
                confirmInput.classList.add('input-success');
                return true;
            } else {
                confirmMatchDiv.innerHTML = '<i class="fas fa-exclamation-circle text-red-500 mr-1"></i> Passwords do not match';
                confirmMatchDiv.className = 'mt-1 text-xs text-red-600';
                confirmInput.classList.remove('input-success');
                confirmInput.classList.add('input-error');
                return false;
            }
        }
        
        passwordInput?.addEventListener('input', function() {
            updateStrengthMeter(this.value);
            updateRequirements(this.value);
            validatePasswordMatch();
        });
        
        confirmInput?.addEventListener('input', validatePasswordMatch);
        
        // Auto-hide success message
        const savedMsg = document.getElementById('savedMessage');
        if (savedMsg) {
            setTimeout(() => savedMsg.classList.add('fade-out'), 2000);
        }
        
        // Form validation
        document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
            let isValid = true;
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (password.length < 8) isValid = false;
            if (!/[A-Z]/.test(password)) isValid = false;
            if (!/[0-9]/.test(password)) isValid = false;
            if (password !== confirm) isValid = false;
            
            if (!isValid) {
                e.preventDefault();
                alert('Please meet all password requirements and ensure passwords match.');
            } else {
                const btn = document.getElementById('saveBtn');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Updating...';
            }
        });
    </script>
</body>
</html>