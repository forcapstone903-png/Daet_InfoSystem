<?php
// index.php - Users Management Page
require_once 'C:\Users\Jerwin\Downloads\DAETINFOSYSTEM\dbconn.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('C:\Users\Jerwin\Downloads\DAETINFOSYSTEM\AUTH\login.php');
}

// Get real statistics from database
$total_users_query = query("SELECT COUNT(*) as count FROM info_profiles");
$total_users = fetchOne($total_users_query);

$active_users_query = query("SELECT COUNT(*) as count FROM info_profiles WHERE status = 'active'");
$active_users = fetchOne($active_users_query);

$admin_count_query = query("SELECT COUNT(*) as count FROM info_profiles WHERE role = 'admin'");
$admin_count = fetchOne($admin_count_query);

$stats = [
    'total_users' => $total_users['count'],
    'active_users' => $active_users['count'],
    'administrators' => $admin_count['count']
];

// Get all users from database
$users_query = query("SELECT id, full_name, email, role, status, created_at FROM info_profiles ORDER BY created_at DESC");
$users = fetchAll($users_query);

// Process user data for display
$processed_users = [];
foreach ($users as $user) {
    $processed_users[] = [
        'id' => $user['id'],
        'name' => $user['full_name'],
        'email' => $user['email'],
        'role' => ucfirst($user['role']),
        'status' => ucfirst($user['status']),
        'joined' => date('M d, Y', strtotime($user['created_at'])),
        'initial' => strtoupper(substr($user['full_name'], 0, 1))
    ];
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_user') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'regular_user';
        $status = $_POST['status'] ?? 'active';
        
        // Validate
        if (empty($name) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }
        
        // Check if email exists
        $check_query = query("SELECT id FROM info_profiles WHERE email = $1", [$email]);
        if (num_rows($check_query) > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit;
        }
        
        // Create user
        $user_id = generateUUID();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO info_profiles (id, email, full_name, password, role, status, created_at, updated_at) 
                         VALUES ($1, $2, $3, $4, $5, $6, NOW(), NOW())";
        
        $result = query($insert_query, [$user_id, $email, $name, $hashed_password, $role, $status]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'User created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create user']);
        }
        exit;
    }
    
    if ($action === 'edit_user') {
        $user_id = $_POST['user_id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'regular_user';
        $status = $_POST['status'] ?? 'active';
        
        if (empty($user_id) || empty($name) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Required fields missing']);
            exit;
        }
        
        $update_query = "UPDATE info_profiles SET full_name = $1, email = $2, role = $3, status = $4, updated_at = NOW() WHERE id = $5";
        $result = query($update_query, [$name, $email, $role, $status, $user_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update user']);
        }
        exit;
    }
    
    if ($action === 'reset_password') {
        $user_id = $_POST['user_id'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        
        if (empty($user_id) || empty($new_password) || strlen($new_password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
            exit;
        }
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE info_profiles SET password = $1, updated_at = NOW() WHERE id = $2";
        $result = query($update_query, [$hashed_password, $user_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reset password']);
        }
        exit;
    }
    
    if ($action === 'delete_user') {
        $user_id = $_POST['user_id'] ?? '';
        
        // Don't allow deleting yourself
        if ($user_id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
            exit;
        }
        
        $delete_query = "DELETE FROM info_profiles WHERE id = $1";
        $result = query($delete_query, [$user_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
        }
        exit;
    }
    
    if ($action === 'get_user') {
        $user_id = $_POST['user_id'] ?? '';
        $user_query = query("SELECT id, full_name, email, role, status FROM info_profiles WHERE id = $1", [$user_id]);
        $user = fetchOne($user_query);
        
        if ($user) {
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
        exit;
    }
}

$userName = $_SESSION['full_name'] ?? 'Administrator';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Daeteño Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal {
            transition: all 0.3s ease;
        }
        .action-btn {
            transition: all 0.2s ease;
        }
        .action-btn:hover {
            transform: scale(1.1);
        }
        .table-row:hover {
            background-color: #f9fafb;
        }
        .role-badge {
            transition: all 0.2s ease;
        }
        .role-badge:hover {
            transform: scale(1.05);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Admin Header -->
    <div class="bg-gradient-to-r from-green-600 to-green-800 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-4">
                <div class="flex items-center space-x-4">
                    <a href="../dashboard.php" class="flex items-center text-white/80 hover:text-white transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                    <div class="h-6 w-px bg-white/30"></div>
                    <span class="text-white font-medium">Users Management</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm"><i class="fas fa-user-circle mr-1"></i> <?php echo htmlspecialchars($userName); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Users Management</h1>
                <p class="text-gray-600">Manage system users and administrators</p>
            </div>
            <button onclick="openAddUserModal()" 
               class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-user-plus mr-2"></i> Add User
            </button>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow border border-gray-100 p-4">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center mr-3">
                        <i class="fas fa-users text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Total Users</p>
                        <p class="text-xl font-bold text-gray-900" id="totalUsers"><?php echo $stats['total_users']; ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow border border-gray-100 p-4">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-lg bg-green-100 flex items-center justify-center mr-3">
                        <i class="fas fa-user-check text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Active Users</p>
                        <p class="text-xl font-bold text-gray-900" id="activeUsers"><?php echo $stats['active_users']; ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow border border-gray-100 p-4">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-lg bg-purple-100 flex items-center justify-center mr-3">
                        <i class="fas fa-user-shield text-purple-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Administrators</p>
                        <p class="text-xl font-bold text-gray-900" id="adminCount"><?php echo $stats['administrators']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900">All Users</h2>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Search users..." 
                                   class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <select id="roleFilter" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500">
                            <option value="all">All Users</option>
                            <option value="Admin">Administrators</option>
                            <option value="Regular_user">Regular Users</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTable" class="bg-white divide-y divide-gray-200">
                            <?php foreach ($processed_users as $user): ?>
                            <tr class="table-row" data-user-id="<?php echo $user['id']; ?>" data-role="<?php echo strtolower($user['role']); ?>" data-status="<?php echo strtolower($user['status']); ?>">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white font-bold mr-3">
                                            <?php echo htmlspecialchars($user['initial']); ?>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
                                            <div class="text-sm text-gray-500">ID: <?php echo substr($user['id'], 0, 8); ?>...</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="role-badge px-2 py-1 text-xs rounded-full <?php echo $user['role'] == 'Admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <?php echo $user['role']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="status-badge px-2 py-1 text-xs rounded-full <?php echo $user['status'] == 'Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $user['status']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?php echo $user['joined']; ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <button onclick="editUser('<?php echo $user['id']; ?>')" class="action-btn text-blue-600 hover:text-blue-700" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="resetPassword('<?php echo $user['id']; ?>')" class="action-btn text-green-600 hover:text-green-700" title="Reset Password">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <button onclick="deleteUser('<?php echo $user['id']; ?>')" class="action-btn text-red-600 hover:text-red-700" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (empty($processed_users)): ?>
                <div class="text-center py-8">
                    <p class="text-gray-500">
                        <i class="fas fa-users-cog text-3xl mb-3 text-gray-300"></i><br>
                        No users found
                    </p>
                    <p class="text-sm text-gray-400 mt-2">
                        Click "Add User" to create your first user.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- User Roles & Permissions Section -->
        <div class="mt-6 bg-white rounded-xl shadow border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">User Roles & Permissions</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="border-r border-gray-200 pr-6">
                    <h4 class="font-medium text-gray-900 mb-2 flex items-center">
                        <i class="fas fa-user-shield text-purple-600 mr-2"></i>
                        Administrator
                    </h4>
                    <ul class="space-y-1 text-sm text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            Full system access
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            Manage all content
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            User management
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            View analytics & reports
                        </li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900 mb-2 flex items-center">
                        <i class="fas fa-user text-blue-600 mr-2"></i>
                        Regular User
                    </h4>
                    <ul class="space-y-1 text-sm text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            Book tours & activities
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            Write reviews
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            Save favorites
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            View personal dashboard
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 modal fade-in">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Add New User</h3>
                <button onclick="closeUserModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="userForm">
                <input type="hidden" id="userId" name="userId">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                        <input type="text" id="userName" name="name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                        <input type="email" id="userEmail" name="email" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div id="passwordField">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                        <input type="password" id="userPassword" name="password"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select id="userRole" name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                            <option value="regular_user">Regular User</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="userStatus" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeUserModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-save mr-2"></i> Save User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 modal fade-in">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Reset Password</h3>
                <button onclick="closeResetPasswordModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="resetPasswordForm">
                <input type="hidden" id="resetUserId" name="resetUserId">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Password *</label>
                        <input type="password" id="newPassword" name="new_password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
                        <input type="password" id="confirmPassword" name="confirm_password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeResetPasswordModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-key mr-2"></i> Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 modal fade-in">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Delete User</h3>
                <p class="text-sm text-gray-500 mb-4">Are you sure you want to delete this user? This action cannot be undone.</p>
                <div class="flex justify-center space-x-3">
                    <button onclick="closeDeleteModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button onclick="confirmDelete()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentUserId = null;
        let userToDelete = null;
        let isLoading = false;

        // Search and filter functionality
        const searchInput = document.getElementById('searchInput');
        const roleFilter = document.getElementById('roleFilter');
        
        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const roleValue = roleFilter.value;
            const rows = document.querySelectorAll('#usersTable tr');
            let visibleCount = 0;
            let activeCount = 0;
            let adminCount = 0;
            
            rows.forEach(row => {
                const userName = row.querySelector('td:first-child .font-medium')?.innerText.toLowerCase() || '';
                const userRole = row.querySelector('td:nth-child(3) span')?.innerText || '';
                const userStatus = row.querySelector('td:nth-child(4) span')?.innerText || '';
                
                let matchesSearch = userName.includes(searchTerm);
                let matchesRole = roleValue === 'all' || userRole === roleValue;
                
                if (matchesSearch && matchesRole) {
                    row.style.display = '';
                    visibleCount++;
                    if (userStatus === 'Active') activeCount++;
                    if (userRole === 'Admin') adminCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update statistics
            document.getElementById('totalUsers').textContent = visibleCount;
            document.getElementById('activeUsers').textContent = activeCount;
            document.getElementById('adminCount').textContent = adminCount;
        }
        
        searchInput.addEventListener('input', filterTable);
        roleFilter.addEventListener('change', filterTable);
        
        // Show notification
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `toast-notification px-6 py-3 rounded-lg shadow-lg ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
        
        // User Management Functions
        function openAddUserModal() {
            document.getElementById('modalTitle').textContent = 'Add New User';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('passwordField').style.display = 'block';
            document.getElementById('userModal').classList.remove('hidden');
            document.getElementById('userModal').classList.add('flex');
        }
        
        async function editUser(id) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_user');
                formData.append('user_id', id);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const user = data.user;
                    document.getElementById('modalTitle').textContent = 'Edit User';
                    document.getElementById('userId').value = user.id;
                    document.getElementById('userName').value = user.full_name;
                    document.getElementById('userEmail').value = user.email;
                    document.getElementById('userRole').value = user.role;
                    document.getElementById('userStatus').value = user.status;
                    document.getElementById('passwordField').style.display = 'none';
                    document.getElementById('userModal').classList.remove('hidden');
                    document.getElementById('userModal').classList.add('flex');
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                showNotification('Error loading user data', 'error');
            }
        }
        
        function closeUserModal() {
            document.getElementById('userModal').classList.remove('flex');
            document.getElementById('userModal').classList.add('hidden');
        }
        
        function resetPassword(id) {
            currentUserId = id;
            document.getElementById('resetUserId').value = id;
            document.getElementById('resetPasswordModal').classList.remove('hidden');
            document.getElementById('resetPasswordModal').classList.add('flex');
        }
        
        function closeResetPasswordModal() {
            document.getElementById('resetPasswordModal').classList.remove('flex');
            document.getElementById('resetPasswordModal').classList.add('hidden');
            document.getElementById('resetPasswordForm').reset();
        }
        
        function deleteUser(id) {
            userToDelete = id;
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('flex');
            document.getElementById('deleteModal').classList.add('hidden');
            userToDelete = null;
        }
        
        async function confirmDelete() {
            if (userToDelete && !isLoading) {
                isLoading = true;
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'delete_user');
                    formData.append('user_id', userToDelete);
                    
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Remove row from table
                        const row = document.querySelector(`tr[data-user-id="${userToDelete}"]`);
                        if (row) row.remove();
                        
                        showNotification(data.message, 'success');
                        filterTable(); // Update statistics
                        closeDeleteModal();
                        
                        // Reload page after 1 second to refresh data
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification(data.message, 'error');
                    }
                } catch (error) {
                    showNotification('Error deleting user', 'error');
                } finally {
                    isLoading = false;
                }
            }
        }
        
        // Form submissions
        document.getElementById('userForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (isLoading) return;
            isLoading = true;
            
            const userId = document.getElementById('userId').value;
            const formData = new FormData();
            
            if (userId) {
                formData.append('action', 'edit_user');
                formData.append('user_id', userId);
                formData.append('name', document.getElementById('userName').value);
                formData.append('email', document.getElementById('userEmail').value);
                formData.append('role', document.getElementById('userRole').value);
                formData.append('status', document.getElementById('userStatus').value);
            } else {
                const password = document.getElementById('userPassword').value;
                if (!password) {
                    showNotification('Password is required for new users', 'error');
                    isLoading = false;
                    return;
                }
                if (password.length < 6) {
                    showNotification('Password must be at least 6 characters', 'error');
                    isLoading = false;
                    return;
                }
                
                formData.append('action', 'add_user');
                formData.append('name', document.getElementById('userName').value);
                formData.append('email', document.getElementById('userEmail').value);
                formData.append('password', password);
                formData.append('role', document.getElementById('userRole').value);
                formData.append('status', document.getElementById('userStatus').value);
            }
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeUserModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                showNotification('Error saving user', 'error');
            } finally {
                isLoading = false;
            }
        });
        
        document.getElementById('resetPasswordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (isLoading) return;
            
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                showNotification('Passwords do not match!', 'error');
                return;
            }
            
            if (newPassword.length < 6) {
                showNotification('Password must be at least 6 characters', 'error');
                return;
            }
            
            isLoading = true;
            
            try {
                const formData = new FormData();
                formData.append('action', 'reset_password');
                formData.append('user_id', document.getElementById('resetUserId').value);
                formData.append('new_password', newPassword);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeResetPasswordModal();
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                showNotification('Error resetting password', 'error');
            } finally {
                isLoading = false;
            }
        });
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const userModal = document.getElementById('userModal');
            const resetModal = document.getElementById('resetPasswordModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target === userModal) closeUserModal();
            if (event.target === resetModal) closeResetPasswordModal();
            if (event.target === deleteModal) closeDeleteModal();
        }
        
        // Initialize filter on page load
        filterTable();
    </script>
</body>
</html>