<?php
// delete-user-form.php - Account Deletion Component
session_start();

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

$errors = [];
$showModal = false;

// Check if modal should be shown (when there are validation errors from deletion attempt)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_account') {
    $password = $_POST['password'] ?? '';
    
    if (empty($password)) {
        $errors['password'] = 'Password is required to delete your account';
        $showModal = true;
    } elseif ($password !== 'password123') { // Simulated check
        $errors['password'] = 'Password is incorrect';
        $showModal = true;
    } else {
        // In real app, delete user account from database
        session_destroy();
        header('Location: account-deleted.php');
        exit;
    }
}

// Also check for errors from previous submission via session (for Blade compatibility)
if (!empty($_SESSION['userDeletionErrors'])) {
    $errors = $_SESSION['userDeletionErrors'];
    $showModal = true;
    unset($_SESSION['userDeletionErrors']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f9fafb; }
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }
        .modal-enter {
            animation: modalFadeIn 0.2s ease-out;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .shake {
            animation: shake 0.5s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-2xl mx-auto py-8 px-4">
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-red-50 to-white">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-trash-alt text-red-600 mr-2"></i> Delete Account
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.
                </p>
            </div>
            
            <div class="p-6">
                <button type="button" id="deleteAccountBtn"
                        class="px-5 py-2.5 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors shadow-sm">
                    <i class="fas fa-trash-alt mr-2"></i> Delete Account
                </button>
            </div>
        </section>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="modal-backdrop fixed inset-0" id="modalBackdrop"></div>
            
            <div class="modal-enter relative bg-white rounded-xl shadow-xl max-w-md w-full mx-auto z-10">
                <form method="POST" action="" id="deleteForm">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="delete_account">
                    <input type="hidden" name="_method" value="delete">
                    
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-red-600 text-lg"></i>
                            </div>
                            <h2 class="text-lg font-semibold text-gray-900" id="modal-title">
                                Are you sure you want to delete your account?
                            </h2>
                        </div>
                        
                        <p class="text-sm text-gray-600 mb-4">
                            Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.
                        </p>
                        
                        <div class="mb-4">
                            <label for="delete_password" class="sr-only">Password</label>
                            <div class="relative">
                                <input type="password" id="delete_password" name="password" 
                                       placeholder="Password"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 pr-10">
                                <button type="button" onclick="togglePassword('delete_password')" 
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <?php if(isset($errors['password'])): ?>
                            <p class="mt-2 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i> <?php echo $errors['password']; ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex justify-end gap-3">
                            <button type="button" id="cancelDeleteBtn"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" id="confirmDeleteBtn"
                                    class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors">
                                <i class="fas fa-trash-alt mr-2"></i> Delete Account
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
            field.setAttribute('type', type);
        }
        
        // Modal elements
        const modal = document.getElementById('deleteModal');
        const deleteBtn = document.getElementById('deleteAccountBtn');
        const cancelBtn = document.getElementById('cancelDeleteBtn');
        const modalBackdrop = document.getElementById('modalBackdrop');
        
        function openModal() {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            // Focus on password input
            setTimeout(() => document.getElementById('delete_password')?.focus(), 100);
        }
        
        function closeModal() {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            // Clear password field and errors
            const passwordField = document.getElementById('delete_password');
            if (passwordField) passwordField.value = '';
            const errorMsg = document.querySelector('#deleteForm .text-red-600');
            if (errorMsg) errorMsg.remove();
        }
        
        deleteBtn?.addEventListener('click', openModal);
        cancelBtn?.addEventListener('click', closeModal);
        modalBackdrop?.addEventListener('click', closeModal);
        
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
        
        // Form validation
        document.getElementById('deleteForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('delete_password').value;
            if (!password.trim()) {
                e.preventDefault();
                const passwordField = document.getElementById('delete_password');
                passwordField.classList.add('shake');
                setTimeout(() => passwordField.classList.remove('shake'), 500);
                
                // Show error if not exists
                if (!document.querySelector('#deleteForm .text-red-600')) {
                    const errorDiv = document.createElement('p');
                    errorDiv.className = 'mt-2 text-sm text-red-600';
                    errorDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> Password is required to delete your account';
                    passwordField.parentElement.parentElement.appendChild(errorDiv);
                }
            } else {
                const btn = document.getElementById('confirmDeleteBtn');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Deleting...';
            }
        });
        
        // Show modal automatically if there were validation errors
        <?php if($showModal && !empty($errors)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openModal();
            const passwordField = document.getElementById('delete_password');
            if (passwordField) {
                passwordField.classList.add('shake');
                setTimeout(() => passwordField.classList.remove('shake'), 500);
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>