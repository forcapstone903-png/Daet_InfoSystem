<?php
// update-profile-information-form.php - Profile Information Update Component
session_start();

// Simulate user data (in real app, fetch from database)
$user = [
    'id' => $_SESSION['user_id'] ?? 1,
    'name' => $_SESSION['user_name'] ?? 'John Doe',
    'email' => $_SESSION['user_email'] ?? 'john@example.com',
    'email_verified' => $_SESSION['email_verified'] ?? false
];

// CSRF token
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Handle form submission
$errors = [];
$status = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    // Verify CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors['general'] = 'Invalid security token';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($name)) {
            $errors['name'] = 'Name is required';
        } elseif (strlen($name) < 2) {
            $errors['name'] = 'Name must be at least 2 characters';
        }
        
        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        }
        
        if (empty($errors)) {
            // Update user data
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            
            // If email changed, mark as unverified
            if ($email !== $user['email']) {
                $_SESSION['email_verified'] = false;
                $user['email_verified'] = false;
            }
            
            $user['name'] = $name;
            $user['email'] = $email;
            
            $status = 'profile-updated';
        }
    }
}

// Handle verification email resend
$verificationStatus = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_verification') {
    // In real app, send verification email here
    $verificationStatus = 'verification-link-sent';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Information</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f9fafb; }
        .fade-out {
            animation: fadeOut 2s ease-out forwards;
        }
        @keyframes fadeOut {
            0% { opacity: 1; transform: translateY(0); }
            70% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(-10px); visibility: hidden; }
        }
        .input-error {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }
        .input-success {
            border-color: #10b981 !important;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-2xl mx-auto py-8 px-4">
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-white">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-user-circle text-blue-600 mr-2"></i> Profile Information
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Update your account's profile information and email address.
                </p>
            </div>
            
            <div class="p-6">
                <!-- Verification Form (hidden, triggered by button) -->
                <form id="send-verification" method="POST" action="" style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="send_verification">
                </form>
                
                <!-- Main Profile Form -->
                <form method="POST" action="" id="profileForm" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="_method" value="patch">
                    
                    <!-- Name Field -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" id="name" name="name" 
                               value="<?php echo htmlspecialchars($user['name']); ?>"
                               required autofocus
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        <?php if(isset($errors['name'])): ?>
                        <p class="mt-2 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i> <?php echo $errors['name']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>"
                               required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        <?php if(isset($errors['email'])): ?>
                        <p class="mt-2 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i> <?php echo $errors['email']; ?></p>
                        <?php endif; ?>
                        
                        <!-- Email Verification Notice -->
                        <?php if(!$user['email_verified']): ?>
                        <div class="mt-3 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                            <p class="text-sm text-yellow-800">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Your email address is unverified.
                                <button type="button" id="resendVerificationBtn" 
                                        class="ml-2 text-blue-600 hover:text-blue-800 underline font-medium">
                                    Click here to re-send the verification email.
                                </button>
                            </p>
                            <?php if($verificationStatus === 'verification-link-sent'): ?>
                            <p class="mt-2 text-sm text-green-600">
                                <i class="fas fa-check-circle mr-1"></i>
                                A new verification link has been sent to your email address.
                            </p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Submit Section -->
                    <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
                        <button type="submit" id="saveBtn"
                                class="px-5 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                            <i class="fas fa-save mr-2"></i> Save
                        </button>
                        
                        <?php if($status === 'profile-updated'): ?>
                        <p id="savedMessage" class="text-sm text-green-600">
                            <i class="fas fa-check-circle mr-1"></i> Saved.
                        </p>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </section>
    </div>
    
    <script>
        // Auto-hide saved message after 2 seconds
        const savedMsg = document.getElementById('savedMessage');
        if (savedMsg) {
            setTimeout(() => {
                savedMsg.classList.add('fade-out');
            }, 2000);
        }
        
        // Resend verification email
        const resendBtn = document.getElementById('resendVerificationBtn');
        if (resendBtn) {
            resendBtn.addEventListener('click', function() {
                document.getElementById('send-verification').submit();
            });
        }
        
        // Real-time validation
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        
        function validateName() {
            const name = nameInput.value.trim();
            if (name.length < 2 && name.length > 0) {
                nameInput.classList.add('input-error');
                nameInput.classList.remove('input-success');
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
                emailInput.classList.remove('input-success');
                return false;
            } else if (email !== '') {
                emailInput.classList.remove('input-error');
                emailInput.classList.add('input-success');
                return true;
            }
            return true;
        }
        
        nameInput?.addEventListener('input', validateName);
        emailInput?.addEventListener('input', validateEmail);
        
        // Form validation on submit
        document.getElementById('profileForm')?.addEventListener('submit', function(e) {
            let isValid = true;
            if (!validateName()) isValid = false;
            if (!validateEmail()) isValid = false;
            
            if (!isValid) {
                e.preventDefault();
            } else {
                const btn = document.getElementById('saveBtn');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';
            }
        });
    </script>
</body>
</html>