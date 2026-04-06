<?php
// index.php - Users Management Page
require_once 'C:\Users\oleng\Downloads\Daet_InfoSystem-main\dbconn.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('C:\Users\oleng\Downloads\Daet_InfoSystem-main\AUTH\login.php');
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        body {
            background: linear-gradient(145deg, #f1f5f9 0%, #e9eef5 100%);
            min-height: 100vh;
        }
        
        /* Glassmorphism header */
        .glass-header {
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        /* Modern stat cards */
        .stat-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(4px);
            border-radius: 1.5rem;
            transition: all 0.25s ease;
            border: 1px solid rgba(255,255,255,0.5);
            box-shadow: 0 8px 20px -6px rgba(0,0,0,0.06);
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 28px -12px rgba(0,0,0,0.12);
        }
        
        /* Main card container */
        .main-card {
            background: rgba(255,255,255,0.97);
            backdrop-filter: blur(2px);
            border-radius: 1.5rem;
            box-shadow: 0 12px 30px -10px rgba(0,0,0,0.08);
            border: 1px solid rgba(226, 232, 240, 0.6);
            overflow: hidden;
        }
        
        /* Table header */
        .table-header-bg {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 1px solid #e2e8f0;
        }
        
        /* Modern inputs */
        .search-input {
            border: 1.5px solid #e2e8f0;
            border-radius: 2rem;
            transition: all 0.2s ease;
            padding-left: 2.5rem;
            font-size: 0.9rem;
        }
        
        .search-input:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16,185,129,0.12);
            outline: none;
        }
        
        .filter-select {
            border: 1.5px solid #e2e8f0;
            border-radius: 2rem;
            padding: 0.5rem 1rem;
            background-color: white;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .filter-select:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16,185,129,0.12);
            outline: none;
        }
        
        /* Primary button */
        .btn-primary-modern {
            background: linear-gradient(105deg, #10b981, #059669);
            border-radius: 2rem;
            padding: 0.65rem 1.6rem;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 4px 10px -4px rgba(16,185,129,0.3);
        }
        
        .btn-primary-modern:hover {
            background: linear-gradient(105deg, #059669, #047857);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px -6px rgba(16,185,129,0.4);
        }
        
        /* Table row hover */
        .table-row {
            transition: all 0.2s ease;
        }
        
        .table-row:hover {
            background: linear-gradient(90deg, #fefefe, #f0fdf4);
            transform: scale(1.002);
        }
        
        /* Action buttons */
        .action-btn {
            transition: all 0.2s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 12px;
            background: transparent;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
        }
        
        .action-edit:hover { background: #eff6ff; color: #2563eb; }
        .action-key:hover { background: #ecfdf5; color: #059669; }
        .action-delete:hover { background: #fef2f2; color: #dc2626; }
        
        /* User avatar */
        .user-avatar {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 4px 8px -2px rgba(16,185,129,0.3);
        }
        
        /* Role badges */
        .role-badge-admin {
            background: linear-gradient(135deg, #f3e8ff, #e9d5ff);
            color: #6b21a5;
            border: 1px solid #d8b4fe;
        }
        
        .role-badge-user {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
            border: 1px solid #93c5fd;
        }
        
        /* Status badges */
        .status-active {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        
        .status-inactive {
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            color: #374151;
            border: 1px solid #d1d5db;
        }
        
        /* Modal redesign */
        .modal-modern {
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(12px);
            border-radius: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        /* Permissions card */
        .permissions-card {
            background: linear-gradient(135deg, #fefefe, #fafcff);
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
        }
        
        /* Toast animation */
        .toast-notification {
            animation: slideInRight 0.4s ease-out, fadeOut 3s ease-in-out forwards;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes fadeOut {
            0% { opacity: 1; transform: translateX(0); }
            70% { opacity: 1; transform: translateX(0); }
            100% { opacity: 0; transform: translateX(20px); visibility: hidden; }
        }
        
        /* Modal fade in */
        .fade-in {
            animation: modalFadeIn 0.3s ease-out;
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.96);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
    </style>
</head>
<body class="antialiased">

    <!-- Glass Header -->
    <div class="glass-header sticky top-0 z-20">
        <div class="max-w-7xl mx-auto px-5 sm:px-8 py-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-5">
                <a href="../dashboard.php" class="flex items-center gap-2 text-white/85 hover:text-white transition-all duration-200 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full text-sm font-medium">
                    <i class="fas fa-arrow-left text-xs"></i>
                    <span>Back to Dashboard</span>
                </a>
                <div class="hidden sm:block h-6 w-px bg-white/20"></div>
                <div class="flex items-center gap-2 text-white/90 text-sm font-medium bg-white/10 px-4 py-1.5 rounded-full">
                    <i class="fas fa-users text-emerald-200"></i>
                    <span>User Manager</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-emerald-100"><i class="far fa-user-circle mr-1"></i> <?php echo htmlspecialchars($userName); ?></span>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <div class="flex items-center gap-2 text-emerald-600 text-sm font-semibold mb-1">
                    <i class="fas fa-user-cog"></i>
                    <span>Access Management</span>
                </div>
                <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-slate-800">User <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">Management</span></h1>
                <p class="text-slate-500 mt-1 text-base">Manage system users, roles, and permissions</p>
            </div>
            <button onclick="openAddUserModal()" 
               class="btn-primary-modern text-white inline-flex items-center gap-2 shadow-md">
                <i class="fas fa-user-plus"></i> Add User
            </button>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Total Users</p>
                        <p class="text-3xl font-bold text-slate-800 mt-1" id="totalUsers"><?php echo $stats['total_users']; ?></p>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-2 text-xs text-slate-400">Registered accounts</div>
            </div>
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Active Users</p>
                        <p class="text-3xl font-bold text-slate-800 mt-1" id="activeUsers"><?php echo $stats['active_users']; ?></p>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-emerald-100 to-emerald-200 flex items-center justify-center">
                        <i class="fas fa-user-check text-emerald-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-2 text-xs text-slate-400">Active status</div>
            </div>
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Administrators</p>
                        <p class="text-3xl font-bold text-slate-800 mt-1" id="adminCount"><?php echo $stats['administrators']; ?></p>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-purple-100 to-purple-200 flex items-center justify-center">
                        <i class="fas fa-user-shield text-purple-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-2 text-xs text-slate-400">Full access role</div>
            </div>
        </div>

        <!-- Main Table Card -->
        <div class="main-card">
            <div class="table-header-bg px-6 py-5 flex flex-col sm:flex-row justify-between items-center gap-4">
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <i class="fas fa-table-list text-emerald-500"></i> Registered Users
                </h2>
                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <div class="relative">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="text" id="searchInput" placeholder="Search users..." 
                               class="search-input pl-10 pr-4 py-2 w-64">
                    </div>
                    <select id="roleFilter" class="filter-select">
                        <option value="all">👥 All Users</option>
                        <option value="Admin">🛡️ Administrators</option>
                        <option value="Regular_user">👤 Regular Users</option>
                    </select>
                </div>
            </div>

            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead>
                            <tr class="text-left">
                                <th class="pb-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">User</th>
                                <th class="pb-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Email</th>
                                <th class="pb-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Role</th>
                                <th class="pb-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="pb-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Joined</th>
                                <th class="pb-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTable" class="divide-y divide-slate-100">
                            <?php foreach ($processed_users as $user): ?>
                            <tr class="table-row" data-user-id="<?php echo $user['id']; ?>" data-role="<?php echo strtolower($user['role']); ?>" data-status="<?php echo strtolower($user['status']); ?>">
                                <td class="py-4 pr-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full user-avatar flex items-center justify-center text-white font-bold text-sm shadow-md">
                                            <?php echo htmlspecialchars($user['initial']); ?>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-slate-800"><?php echo htmlspecialchars($user['name']); ?></div>
                                            <div class="text-xs text-slate-400 font-mono">ID: <?php echo substr($user['id'], 0, 8); ?>...</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 text-sm text-slate-600"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="py-4">
                                    <span class="role-badge-<?php echo $user['role'] == 'Admin' ? 'admin' : 'user'; ?> px-3 py-1 text-xs font-semibold rounded-full">
                                        <?php echo $user['role']; ?>
                                    </span>
                                </td>
                                <td class="py-4">
                                    <span class="status-<?php echo strtolower($user['status']); ?> px-3 py-1 text-xs font-semibold rounded-full">
                                        <?php echo $user['status']; ?>
                                    </span>
                                </td>
                                <td class="py-4 text-sm text-slate-500"><?php echo $user['joined']; ?></td>
                                <td class="py-4">
                                    <div class="flex gap-1">
                                        <button onclick="editUser('<?php echo $user['id']; ?>')" class="action-btn action-edit text-blue-500" title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button onclick="resetPassword('<?php echo $user['id']; ?>')" class="action-btn action-key text-emerald-500" title="Reset Password">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <button onclick="deleteUser('<?php echo $user['id']; ?>')" class="action-btn action-delete text-red-400" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (empty($processed_users)): ?>
                <div class="text-center py-12">
                    <div class="h-20 w-20 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users-slash text-3xl text-slate-400"></i>
                    </div>
                    <p class="text-slate-500 font-medium">No users found</p>
                    <p class="text-sm text-slate-400 mt-1">Click "Add User" to create your first user.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- User Roles & Permissions Section -->
        <div class="mt-8 permissions-card p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                <i class="fas fa-shield-alt text-emerald-500"></i> User Roles & Permissions
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="border-r border-slate-100 pr-6">
                    <h4 class="font-semibold text-slate-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-user-shield text-purple-500"></i> Administrator
                    </h4>
                    <ul class="space-y-2 text-sm text-slate-600">
                        <li class="flex items-center"><i class="fas fa-check-circle text-emerald-500 mr-2 w-4"></i> Full system access</li>
                        <li class="flex items-center"><i class="fas fa-check-circle text-emerald-500 mr-2 w-4"></i> Manage all content</li>
                        <li class="flex items-center"><i class="fas fa-check-circle text-emerald-500 mr-2 w-4"></i> User management</li>
                        <li class="flex items-center"><i class="fas fa-check-circle text-emerald-500 mr-2 w-4"></i> View analytics & reports</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-slate-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-user text-blue-500"></i> Regular User
                    </h4>
                    <ul class="space-y-2 text-sm text-slate-600">
                        <li class="flex items-center"><i class="fas fa-check-circle text-emerald-500 mr-2 w-4"></i> Book tours & activities</li>
                        <li class="flex items-center"><i class="fas fa-check-circle text-emerald-500 mr-2 w-4"></i> Write reviews</li>
                        <li class="flex items-center"><i class="fas fa-check-circle text-emerald-500 mr-2 w-4"></i> Save favorites</li>
                        <li class="flex items-center"><i class="fas fa-check-circle text-emerald-500 mr-2 w-4"></i> View personal dashboard</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="modal-modern max-w-md w-full mx-4 p-6 fade-in">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-xl font-bold text-slate-800" id="modalTitle">Add New User</h3>
                <button onclick="closeUserModal()" class="text-slate-400 hover:text-slate-600 transition text-xl">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>
            <form id="userForm">
                <input type="hidden" id="userId" name="userId">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Full Name *</label>
                        <input type="text" id="userName" name="name" required
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email Address *</label>
                        <input type="email" id="userEmail" name="email" required
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                    </div>
                    <div id="passwordField">
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Password *</label>
                        <input type="password" id="userPassword" name="password"
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                        <p class="text-xs text-slate-400 mt-1">Minimum 6 characters</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Role</label>
                        <select id="userRole" name="role" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500">
                            <option value="regular_user">👤 Regular User</option>
                            <option value="admin">🛡️ Administrator</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Status</label>
                        <select id="userStatus" name="status" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500">
                            <option value="active">✅ Active</option>
                            <option value="inactive">⭕ Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeUserModal()" class="px-5 py-2.5 border border-slate-200 rounded-xl hover:bg-slate-50 transition font-medium text-slate-600">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white rounded-xl hover:from-emerald-700 hover:to-emerald-800 transition shadow-md font-medium">
                        <i class="fas fa-save mr-2"></i> Save User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="modal-modern max-w-md w-full mx-4 p-6 fade-in">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-xl font-bold text-slate-800">Reset Password</h3>
                <button onclick="closeResetPasswordModal()" class="text-slate-400 hover:text-slate-600 transition text-xl">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>
            <form id="resetPasswordForm">
                <input type="hidden" id="resetUserId" name="resetUserId">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">New Password *</label>
                        <input type="password" id="newPassword" name="new_password" required
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                        <p class="text-xs text-slate-400 mt-1">Minimum 6 characters</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Confirm Password *</label>
                        <input type="password" id="confirmPassword" name="confirm_password" required
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeResetPasswordModal()" class="px-5 py-2.5 border border-slate-200 rounded-xl hover:bg-slate-50 transition font-medium text-slate-600">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white rounded-xl hover:from-emerald-700 hover:to-emerald-800 transition shadow-md font-medium">
                        <i class="fas fa-key mr-2"></i> Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="modal-modern max-w-md w-full mx-4 p-6 fade-in">
            <div class="text-center">
                <div class="mx-auto h-14 w-14 rounded-2xl bg-red-100 flex items-center justify-center mb-4">
                    <i class="fas fa-trash-alt text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">Delete User</h3>
                <p class="text-slate-500 text-sm mb-6">Are you sure you want to delete this user? This action cannot be undone.</p>
                <div class="flex justify-center gap-3">
                    <button onclick="closeDeleteModal()" class="px-5 py-2.5 border border-slate-200 rounded-xl hover:bg-slate-50 transition font-medium text-slate-600">
                        Cancel
                    </button>
                    <button onclick="confirmDelete()" class="px-5 py-2.5 bg-red-600 text-white rounded-xl hover:bg-red-700 transition shadow-md font-medium">
                        Delete Permanently
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
                const userName = row.querySelector('td:first-child .font-semibold')?.innerText.toLowerCase() || '';
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
            
            document.getElementById('totalUsers').textContent = visibleCount;
            document.getElementById('activeUsers').textContent = activeCount;
            document.getElementById('adminCount').textContent = adminCount;
        }
        
        searchInput.addEventListener('input', filterTable);
        roleFilter.addEventListener('change', filterTable);
        
        // Show notification
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `toast-notification fixed top-6 right-6 z-50 px-5 py-3 rounded-xl shadow-2xl flex items-center gap-3 ${type === 'success' ? 'bg-gradient-to-r from-emerald-500 to-emerald-600' : 'bg-gradient-to-r from-red-500 to-red-600'} text-white`;
            notification.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'} text-lg"></i>
                <span class="font-medium">${message}</span>
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
                        const row = document.querySelector(`tr[data-user-id="${userToDelete}"]`);
                        if (row) row.remove();
                        
                        showNotification(data.message, 'success');
                        filterTable();
                        closeDeleteModal();
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