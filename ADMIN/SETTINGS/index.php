<?php
/**
 * Daeteño Admin - System Settings
 * 
 * Professional System Settings page with proper alignment and modern UI
 */

session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_name'] = 'Admin User';
}

// Handle form submissions
$successMessage = null;
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'save_general') {
        $_SESSION['success_message'] = 'General settings saved successfully!';
        header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=general&saved=1');
        exit;
    } elseif ($action === 'save_website') {
        $_SESSION['success_message'] = 'Website settings saved successfully!';
        header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=website&saved=1');
        exit;
    } elseif ($action === 'save_email') {
        $_SESSION['success_message'] = 'Email settings saved successfully!';
        header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=email&saved=1');
        exit;
    } elseif ($action === 'save_security') {
        $_SESSION['success_message'] = 'Security settings saved successfully!';
        header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=security&saved=1');
        exit;
    } elseif ($action === 'save_notifications') {
        $_SESSION['success_message'] = 'Notification settings saved successfully!';
        header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=notifications&saved=1');
        exit;
    } elseif ($action === 'create_backup') {
        $_SESSION['success_message'] = 'Backup created successfully!';
        header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=backup&backup=1');
        exit;
    } elseif ($action === 'restore_backup') {
        $_SESSION['success_message'] = 'Backup restored successfully!';
        header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=backup&restored=1');
        exit;
    }
}

$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
unset($_SESSION['success_message']);

// Sample backup data
$backups = [
    ['id' => 1, 'name' => 'auto_backup_20241201.sql', 'size' => '2.4 MB', 'date' => '2024-12-01 02:00:00', 'type' => 'auto'],
    ['id' => 2, 'name' => 'manual_backup_20241125.sql', 'size' => '2.3 MB', 'date' => '2024-11-25 15:30:00', 'type' => 'manual'],
    ['id' => 3, 'name' => 'auto_backup_20241124.sql', 'size' => '2.3 MB', 'date' => '2024-11-24 02:00:00', 'type' => 'auto'],
];

// System information
$systemInfo = [
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'server_protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daeteño Admin - System Settings</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        
        /* Glass Header */
        .glass-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            backdrop-filter: blur(10px);
        }
        
        /* Sidebar Navigation */
        .settings-sidebar {
            background: white;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            position: sticky;
            top: 24px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        }
        .settings-sidebar:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            margin: 4px 8px;
            border-radius: 14px;
            font-size: 0.875rem;
            font-weight: 500;
            color: #475569;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }
        .nav-item i {
            width: 20px;
            font-size: 1rem;
            color: #94a3b8;
            transition: all 0.2s ease;
        }
        .nav-item:hover {
            background: #f8fafc;
            color: #0f172a;
            transform: translateX(4px);
        }
        .nav-item:hover i {
            color: #3b82f6;
        }
        .nav-item.active {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            color: #2563eb;
        }
        .nav-item.active i {
            color: #2563eb;
        }
        
        /* Settings Cards */
        .settings-card {
            background: white;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        }
        .settings-card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
        }
        
        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        }
        .card-header h3 {
            font-size: 1.125rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .card-header p {
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .card-body {
            padding: 24px;
        }
        
        /* Form Groups */
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            font-size: 0.875rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            background: white;
        }
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-hint {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 6px;
        }
        
        /* Grid Layouts */
        .row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .row-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        /* Switch Toggle */
        .switch-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }
        .switch-wrapper:hover {
            background: #f8fafc;
            margin: 0 -12px;
            padding: 12px 12px;
            border-radius: 14px;
        }
        .switch-wrapper:last-child {
            border-bottom: none;
        }
        
        .switch-label {
            flex: 1;
        }
        .switch-label h4 {
            font-size: 0.875rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 2px;
        }
        .switch-label p {
            font-size: 0.75rem;
            color: #64748b;
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 24px;
            flex-shrink: 0;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: 0.25s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.25s;
            border-radius: 50%;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        input:checked + .slider {
            background-color: #3b82f6;
        }
        input:checked + .slider:before {
            transform: translateX(24px);
        }
        
        /* Info Cards */
        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .info-card:hover {
            border-color: #3b82f6;
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .info-card .info-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }
        .info-card .info-icon i {
            font-size: 1rem;
            color: #3b82f6;
        }
        .info-card .info-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 4px;
        }
        .info-card .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
        }
        
        /* Backup Items */
        .backup-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            margin-bottom: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .backup-item:hover {
            background: white;
            border-color: #3b82f6;
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .backup-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .backup-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .backup-icon i {
            font-size: 1.125rem;
            color: #3b82f6;
        }
        .backup-details h4 {
            font-size: 0.875rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .backup-meta {
            display: flex;
            gap: 12px;
            font-size: 0.7rem;
            color: #64748b;
        }
        .backup-actions {
            display: flex;
            gap: 4px;
        }
        .backup-actions button {
            background: transparent;
            border: none;
            padding: 8px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #64748b;
        }
        .backup-actions button:hover {
            background: #f1f5f9;
            transform: scale(1.05);
        }
        .backup-actions button:first-child:hover { color: #3b82f6; }
        .backup-actions button:nth-child(2):hover { color: #f59e0b; }
        .backup-actions button:last-child:hover { color: #ef4444; }
        
        /* Buttons */
        .btn {
            padding: 10px 20px;
            border-radius: 14px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            font-family: 'Inter', sans-serif;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        .btn-secondary {
            background: white;
            border: 1px solid #e2e8f0;
            color: #475569;
        }
        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        .btn-sm {
            padding: 6px 14px;
            font-size: 0.75rem;
        }
        
        /* Toast Notification */
        .toast-success {
            position: fixed;
            top: 24px;
            right: 24px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 1100;
            transform: translateX(400px);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            font-weight: 500;
            font-size: 0.875rem;
        }
        .toast-success.show {
            transform: translateX(0);
        }
        
        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.25s ease;
        }
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .modal-container {
            background: white;
            border-radius: 28px;
            max-width: 420px;
            width: 90%;
            transform: scale(0.95);
            transition: transform 0.25s ease;
            text-align: center;
            padding: 28px;
        }
        .modal-overlay.active .modal-container {
            transform: scale(1);
        }
        .modal-icon {
            width: 56px;
            height: 56px;
            background: #fef3c7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        .modal-icon i {
            font-size: 1.75rem;
            color: #f59e0b;
        }
        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .modal-message {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 24px;
        }
        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 12px;
        }
        
        /* Danger Zone */
        .danger-zone {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 1px solid #fecaca;
            border-radius: 20px;
            padding: 20px;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        .danger-zone:hover {
            border-color: #ef4444;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.1);
        }
        
        /* Settings Sections */
        .settings-section {
            display: none;
        }
        .settings-section.active {
            display: block;
            animation: fadeInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Blue info box */
        .info-box {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-radius: 20px;
            padding: 16px;
            margin: 16px 0;
            transition: all 0.3s ease;
        }
        .info-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
        }
        
        /* Warning box */
        .warning-box {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-radius: 20px;
            padding: 16px;
            margin-top: 24px;
            transition: all 0.3s ease;
        }
        .warning-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.1);
        }
        
        /* Avatar */
        .admin-avatar {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #e2e8f0; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #3b82f6; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .row, .row-3 {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .card-header, .card-body {
                padding: 16px;
            }
            .settings-sidebar {
                position: relative;
                top: 0;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-blue-50/20">
    
    <!-- Glass Header -->
    <div class="glass-header text-white sticky top-0 z-50 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-4">
                <a href="../dashboard.php" class="flex items-center text-white/80 hover:text-white transition-all duration-200 hover:scale-105 group">
                    <i class="fas fa-arrow-left text-sm group-hover:-translate-x-1 transition-transform"></i>
                    <span class="text-sm font-medium ml-2">Back to Dashboard</span>
                </a>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-xs text-slate-300">Logged in as</p>
                        <p class="text-sm font-semibold text-white">Admin User</p>
                    </div>
                    <div class="h-9 w-9 rounded-full admin-avatar flex items-center justify-center shadow-md">
                        <i class="fas fa-user text-sm text-white"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <div id="successToast" class="toast-success">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage"><?php echo $successMessage ? htmlspecialchars($successMessage) : ''; ?></span>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">System Settings</h1>
            <p class="text-slate-500 mt-2">Configure and manage your system preferences</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Sidebar Navigation -->
            <div class="lg:col-span-1">
                <div class="settings-sidebar">
                    <div class="p-3">
                        <div class="nav-item <?php echo $activeTab === 'general' ? 'active' : ''; ?>" data-tab="general">
                            <i class="fas fa-sliders-h"></i>
                            <span>General Settings</span>
                        </div>
                        <div class="nav-item <?php echo $activeTab === 'website' ? 'active' : ''; ?>" data-tab="website">
                            <i class="fas fa-globe"></i>
                            <span>Website Settings</span>
                        </div>
                        <div class="nav-item <?php echo $activeTab === 'email' ? 'active' : ''; ?>" data-tab="email">
                            <i class="fas fa-envelope"></i>
                            <span>Email Settings</span>
                        </div>
                        <div class="nav-item <?php echo $activeTab === 'security' ? 'active' : ''; ?>" data-tab="security">
                            <i class="fas fa-shield-alt"></i>
                            <span>Security</span>
                        </div>
                        <div class="nav-item <?php echo $activeTab === 'notifications' ? 'active' : ''; ?>" data-tab="notifications">
                            <i class="fas fa-bell"></i>
                            <span>Notifications</span>
                        </div>
                        <div class="nav-item <?php echo $activeTab === 'backup' ? 'active' : ''; ?>" data-tab="backup">
                            <i class="fas fa-database"></i>
                            <span>Backup & Restore</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Content -->
            <div class="lg:col-span-3">
                
                <!-- General Settings -->
                <div id="section-general" class="settings-section <?php echo $activeTab === 'general' ? 'active' : ''; ?>">
                    <div class="settings-card">
                        <div class="card-header">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-sliders-h text-blue-500"></i>
                                <h3>General Settings</h3>
                            </div>
                            <p>Configure basic system preferences</p>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="save_general">
                                
                                <div class="form-group">
                                    <label>System Name</label>
                                    <input type="text" name="system_name" value="Daeteño Tourism System" class="form-control">
                                    <div class="form-hint">The name displayed throughout the system</div>
                                </div>
                                
                                <div class="row">
                                    <div class="form-group">
                                        <label>Timezone</label>
                                        <select name="timezone" class="form-control">
                                            <option value="Asia/Manila" selected>Asia/Manila (GMT+8)</option>
                                            <option value="UTC">UTC</option>
                                            <option value="Asia/Tokyo">Asia/Tokyo (GMT+9)</option>
                                            <option value="America/New_York">America/New York (GMT-5)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Date Format</label>
                                        <select name="date_format" class="form-control">
                                            <option value="m/d/Y">MM/DD/YYYY</option>
                                            <option value="d/m/Y" selected>DD/MM/YYYY</option>
                                            <option value="Y-m-d">YYYY-MM-DD</option>
                                            <option value="F j, Y">Month Day, Year</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="form-group">
                                        <label>Time Format</label>
                                        <select name="time_format" class="form-control">
                                            <option value="12">12-hour format</option>
                                            <option value="24" selected>24-hour format</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Items Per Page</label>
                                        <select name="items_per_page" class="form-control">
                                            <option value="10">10 items</option>
                                            <option value="25" selected>25 items</option>
                                            <option value="50">50 items</option>
                                            <option value="100">100 items</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="switch-wrapper">
                                    <div class="switch-label">
                                        <h4>Maintenance Mode</h4>
                                        <p>Only administrators can access the site</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="maintenance_mode">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                
                                <div class="switch-wrapper">
                                    <div class="switch-label">
                                        <h4>User Registration</h4>
                                        <p>Allow new user registrations</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="allow_registration" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                
                                <div class="mt-6 pt-4 border-top">
                                    <h4 class="font-semibold text-slate-700 mb-4 flex items-center gap-2">
                                        <i class="fas fa-microchip text-blue-500"></i> System Information
                                    </h4>
                                    <div class="row-3">
                                        <div class="info-card">
                                            <div class="info-icon"><i class="fab fa-php"></i></div>
                                            <div class="info-label">PHP Version</div>
                                            <div class="info-value"><?php echo $systemInfo['php_version']; ?></div>
                                        </div>
                                        <div class="info-card">
                                            <div class="info-icon"><i class="fas fa-server"></i></div>
                                            <div class="info-label">Server Software</div>
                                            <div class="info-value" style="font-size: 0.75rem;"><?php echo htmlspecialchars(substr($systemInfo['server_software'], 0, 30)); ?></div>
                                        </div>
                                        <div class="info-card">
                                            <div class="info-icon"><i class="fas fa-tachometer-alt"></i></div>
                                            <div class="info-label">Max Execution</div>
                                            <div class="info-value"><?php echo $systemInfo['max_execution_time']; ?>s</div>
                                        </div>
                                        <div class="info-card">
                                            <div class="info-icon"><i class="fas fa-memory"></i></div>
                                            <div class="info-label">Memory Limit</div>
                                            <div class="info-value"><?php echo $systemInfo['memory_limit']; ?></div>
                                        </div>
                                        <div class="info-card">
                                            <div class="info-icon"><i class="fas fa-upload"></i></div>
                                            <div class="info-label">Upload Max</div>
                                            <div class="info-value"><?php echo $systemInfo['upload_max_filesize']; ?></div>
                                        </div>
                                        <div class="info-card">
                                            <div class="info-icon"><i class="fas fa-arrow-down"></i></div>
                                            <div class="info-label">POST Max</div>
                                            <div class="info-value"><?php echo $systemInfo['post_max_size']; ?></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-200">
                                    <button type="button" onclick="resetForm(this.form)" class="btn btn-secondary">Cancel</button>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i> Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Website Settings -->
                <div id="section-website" class="settings-section <?php echo $activeTab === 'website' ? 'active' : ''; ?>">
                    <div class="settings-card">
                        <div class="card-header">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-globe text-blue-500"></i>
                                <h3>Website Settings</h3>
                            </div>
                            <p>Configure website appearance and content</p>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="save_website">
                                
                                <div class="form-group">
                                    <label>Site Title</label>
                                    <input type="text" name="site_title" value="Daeteño - Discover Daet" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label>Site Description</label>
                                    <textarea name="site_description" rows="3" class="form-control">Experience the beauty and culture of Daet, Camarines Norte</textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Footer Text</label>
                                    <input type="text" name="footer_text" value="© 2024 Daeteño Tourism System. All rights reserved." class="form-control">
                                </div>
                                
                                <div class="row">
                                    <div class="form-group">
                                        <label>Default Language</label>
                                        <select name="default_language" class="form-control">
                                            <option value="en" selected>English</option>
                                            <option value="fil">Filipino</option>
                                            <option value="es">Spanish</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Google Analytics ID</label>
                                        <input type="text" name="ga_id" placeholder="UA-XXXXXXXXX-X" class="form-control">
                                        <div class="form-hint">Leave empty to disable tracking</div>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-200">
                                    <button type="button" onclick="resetForm(this.form)" class="btn btn-secondary">Cancel</button>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i> Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Email Settings -->
                <div id="section-email" class="settings-section <?php echo $activeTab === 'email' ? 'active' : ''; ?>">
                    <div class="settings-card">
                        <div class="card-header">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-envelope text-blue-500"></i>
                                <h3>Email Settings</h3>
                            </div>
                            <p>Configure email sending preferences</p>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="save_email">
                                
                                <div class="row">
                                    <div class="form-group">
                                        <label>Mail Driver</label>
                                        <select name="mail_driver" class="form-control">
                                            <option value="smtp" selected>SMTP</option>
                                            <option value="sendmail">Sendmail</option>
                                            <option value="mail">PHP Mail</option>
                                            <option value="log">Log (Development)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>SMTP Host</label>
                                        <input type="text" name="smtp_host" value="smtp.gmail.com" class="form-control">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="form-group">
                                        <label>SMTP Port</label>
                                        <input type="number" name="smtp_port" value="587" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Encryption</label>
                                        <select name="encryption" class="form-control">
                                            <option value="tls" selected>TLS</option>
                                            <option value="ssl">SSL</option>
                                            <option value="none">None</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>SMTP Username</label>
                                    <input type="email" name="smtp_username" placeholder="your-email@example.com" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label>SMTP Password</label>
                                    <input type="password" name="smtp_password" class="form-control">
                                </div>
                                
                                <div class="row">
                                    <div class="form-group">
                                        <label>From Address</label>
                                        <input type="email" name="from_address" value="noreply@daeteno.gov.ph" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>From Name</label>
                                        <input type="text" name="from_name" value="Daeteño Tourism System" class="form-control">
                                    </div>
                                </div>
                                
                                <div class="info-box">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <h4 class="font-semibold text-blue-700 flex items-center gap-2">
                                                <i class="fas fa-paper-plane"></i> Test Email Configuration
                                            </h4>
                                            <p class="text-xs text-blue-600 mt-1">Send a test email to verify your settings</p>
                                        </div>
                                        <button type="button" onclick="sendTestEmail()" class="btn btn-primary btn-sm">Send Test</button>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-200">
                                    <button type="button" onclick="resetForm(this.form)" class="btn btn-secondary">Cancel</button>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i> Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Security Settings -->
                <div id="section-security" class="settings-section <?php echo $activeTab === 'security' ? 'active' : ''; ?>">
                    <div class="settings-card">
                        <div class="card-header">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-shield-alt text-blue-500"></i>
                                <h3>Security Settings</h3>
                            </div>
                            <p>Configure security and authentication preferences</p>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="save_security">
                                
                                <div class="form-group">
                                    <label>Session Lifetime (minutes)</label>
                                    <input type="number" name="session_lifetime" value="120" min="5" max="1440" class="form-control">
                                </div>
                                
                                <div class="switch-wrapper">
                                    <div class="switch-label">
                                        <h4>Two-Factor Authentication</h4>
                                        <p>Require 2FA for admin accounts</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="two_factor_auth">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                
                                <div class="switch-wrapper">
                                    <div class="switch-label">
                                        <h4>Force HTTPS</h4>
                                        <p>Redirect all HTTP traffic to HTTPS</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="force_https" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                
                                <div class="switch-wrapper">
                                    <div class="switch-label">
                                        <h4>Login Attempts Limit</h4>
                                        <p>Lock account after failed attempts</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="login_attempts_limit" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                
                                <div class="form-group">
                                    <label>Maximum Login Attempts</label>
                                    <input type="number" name="max_attempts" value="5" min="3" max="10" class="form-control">
                                </div>
                                
                                <div class="switch-wrapper">
                                    <div class="switch-label">
                                        <h4>Password Expiration</h4>
                                        <p>Force password change every 90 days</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="password_expiration">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                
                                <div class="form-group">
                                    <label>Allowed IP Addresses</label>
                                    <textarea name="allowed_ips" rows="3" placeholder="192.168.1.1&#10;10.0.0.0/24" class="form-control"></textarea>
                                    <div class="form-hint">One IP per line. Leave empty to allow all IPs</div>
                                </div>
                                
                                <div class="danger-zone">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <h4 class="font-semibold text-red-600 flex items-center gap-2">
                                                <i class="fas fa-exclamation-triangle"></i> Danger Zone
                                            </h4>
                                            <p class="text-xs text-red-500 mt-1">Clear all system cache and temporary data</p>
                                        </div>
                                        <button type="button" onclick="confirmClearCache()" class="btn btn-danger btn-sm">Clear Cache</button>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-200">
                                    <button type="button" onclick="resetForm(this.form)" class="btn btn-secondary">Cancel</button>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i> Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Notifications Settings -->
                <div id="section-notifications" class="settings-section <?php echo $activeTab === 'notifications' ? 'active' : ''; ?>">
                    <div class="settings-card">
                        <div class="card-header">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-bell text-blue-500"></i>
                                <h3>Notification Settings</h3>
                            </div>
                            <p>Configure email and system notifications</p>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="save_notifications">
                                
                                <div class="switch-wrapper">
                                    <div class="switch-label">
                                        <h4>New User Registration Alerts</h4>
                                        <p>Send email when new user registers</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="new_user_alerts" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                
                                <div class="switch-wrapper">
                                    <div class="switch-label">
                                        <h4>New Contact Message Alerts</h4>
                                        <p>Send email when new contact message is received</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="contact_alerts" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                
                                <div class="switch-wrapper">
                                    <div class="switch-label">
                                        <h4>System Error Notifications</h4>
                                        <p>Receive alerts for system errors</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="error_alerts" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                
                                <div class="switch-wrapper">
                                    <div class="switch-label">
                                        <h4>Daily Summary Email</h4>
                                        <p>Receive daily activity summary</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="daily_summary">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                
                                <div class="form-group">
                                    <label>Admin Email Address</label>
                                    <input type="email" name="admin_email" value="admin@daeteno.gov.ph" class="form-control">
                                    <div class="form-hint">Primary email for notifications</div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Additional Notification Emails</label>
                                    <textarea name="additional_emails" rows="2" placeholder="admin2@example.com&#10;manager@example.com" class="form-control"></textarea>
                                    <div class="form-hint">One email per line</div>
                                </div>
                                
                                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-200">
                                    <button type="button" onclick="resetForm(this.form)" class="btn btn-secondary">Cancel</button>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i> Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Backup & Restore Settings -->
                <div id="section-backup" class="settings-section <?php echo $activeTab === 'backup' ? 'active' : ''; ?>">
                    <div class="settings-card">
                        <div class="card-header">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-database text-blue-500"></i>
                                <h3>Backup & Restore</h3>
                            </div>
                            <p>Manage database backups and restoration</p>
                        </div>
                        <div class="card-body">
                            <div class="info-box">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h4 class="font-semibold text-blue-700 flex items-center gap-2">
                                            <i class="fas fa-plus-circle"></i> Create New Backup
                                        </h4>
                                        <p class="text-xs text-blue-600 mt-1">Generate a full database backup</p>
                                    </div>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="create_backup">
                                        <button type="submit" class="btn btn-primary btn-sm">Create Backup</button>
                                    </form>
                                </div>
                            </div>
                            
                            <h4 class="font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <i class="fas fa-archive text-blue-500"></i> Available Backups
                            </h4>
                            <div class="mb-6">
                                <?php if (empty($backups)): ?>
                                    <div class="text-center py-12 bg-slate-50 rounded-2xl">
                                        <i class="fas fa-database text-4xl text-slate-300 mb-3"></i>
                                        <p class="text-slate-500 font-medium">No backups available</p>
                                        <p class="text-xs text-slate-400 mt-1">Create your first backup using the button above</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($backups as $backup): ?>
                                        <div class="backup-item">
                                            <div class="backup-info">
                                                <div class="backup-icon">
                                                    <i class="fas <?php echo $backup['type'] === 'auto' ? 'fa-clock' : 'fa-user'; ?>"></i>
                                                </div>
                                                <div class="backup-details">
                                                    <h4><?php echo htmlspecialchars($backup['name']); ?></h4>
                                                    <div class="backup-meta">
                                                        <span><i class="fas fa-hdd"></i> <?php echo $backup['size']; ?></span>
                                                        <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y g:i A', strtotime($backup['date'])); ?></span>
                                                        <span class="capitalize"><?php echo $backup['type']; ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="backup-actions">
                                                <button onclick="downloadBackup('<?php echo $backup['name']; ?>')" title="Download"><i class="fas fa-download"></i></button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirmRestore()">
                                                    <input type="hidden" name="action" value="restore_backup">
                                                    <button type="submit" title="Restore"><i class="fas fa-undo-alt"></i></button>
                                                </form>
                                                <button onclick="confirmDeleteBackup('<?php echo $backup['name']; ?>')" title="Delete"><i class="fas fa-trash-alt"></i></button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex justify-between items-center pt-4 border-t border-slate-200">
                                <div>
                                    <h4 class="font-semibold text-slate-700">Auto Backup Schedule</h4>
                                    <p class="text-xs text-slate-500 mt-1">Automatically create backups on a schedule</p>
                                </div>
                                <select class="form-control w-auto">
                                    <option value="disabled" selected>Disabled</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                            
                            <div class="warning-box">
                                <div class="flex gap-3">
                                    <i class="fas fa-exclamation-triangle text-amber-600 text-lg"></i>
                                    <div>
                                        <h4 class="text-xs font-bold text-amber-800">Important Note</h4>
                                        <p class="text-xs text-amber-700 mt-1">Restoring a backup will overwrite current data. This action cannot be undone. We recommend creating a backup before restoring.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="modal-title" id="modalTitle">Confirm Action</h3>
            <p class="modal-message" id="modalMessage">Are you sure you want to perform this action?</p>
            <div class="modal-buttons">
                <button onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button id="modalConfirmBtn" class="btn btn-danger">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        // Show success toast if message exists
        <?php if ($successMessage): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.getElementById('successToast');
            if (toast) {
                toast.classList.add('show');
                setTimeout(function() {
                    toast.classList.remove('show');
                }, 4000);
            }
        });
        <?php endif; ?>
        
        // Tab navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function() {
                const tab = this.getAttribute('data-tab');
                
                // Update active states
                document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
                this.classList.add('active');
                
                // Show active section
                document.querySelectorAll('.settings-section').forEach(section => section.classList.remove('active'));
                document.getElementById('section-' + tab).classList.add('active');
                
                // Update URL
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                window.history.pushState({}, '', url);
            });
        });
        
        // Reset form
        function resetForm(form) {
            if (confirm('Reset all unsaved changes?')) {
                form.reset();
            }
        }
        
        // Send test email
        function sendTestEmail() {
            alert('Test email sent! Check your inbox (if configured correctly).');
        }
        
        // Confirm clear cache
        function confirmClearCache() {
            const modal = document.getElementById('confirmationModal');
            document.getElementById('modalTitle').textContent = 'Clear System Cache';
            document.getElementById('modalMessage').textContent = 'Are you sure you want to clear all system cache? This may temporarily slow down the system.';
            const confirmBtn = document.getElementById('modalConfirmBtn');
            confirmBtn.onclick = function() {
                alert('Cache cleared successfully!');
                closeModal();
            };
            modal.classList.add('active');
        }
        
        // Confirm delete backup
        function confirmDeleteBackup(backupName) {
            const modal = document.getElementById('confirmationModal');
            document.getElementById('modalTitle').textContent = 'Delete Backup';
            document.getElementById('modalMessage').textContent = `Are you sure you want to delete "${backupName}"? This action cannot be undone.`;
            const confirmBtn = document.getElementById('modalConfirmBtn');
            confirmBtn.onclick = function() {
                alert('Backup deleted successfully!');
                closeModal();
            };
            modal.classList.add('active');
        }
        
        // Confirm restore
        function confirmRestore() {
            return confirm('WARNING: Restoring a backup will overwrite all current data. This action cannot be undone. Are you sure you want to proceed?');
        }
        
        // Download backup
        function downloadBackup(backupName) {
            alert(`Downloading ${backupName}...`);
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('confirmationModal').classList.remove('active');
        }
        
        // Close modal on escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
        
        // Close modal on outside click
        document.getElementById('confirmationModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        
        // Clean URL params on load
        if (window.location.search && (window.location.search.includes('saved') || 
            window.location.search.includes('backup') || window.location.search.includes('restored'))) {
            const url = new URL(window.location.href);
            const tab = url.searchParams.get('tab');
            url.search = tab ? '?tab=' + tab : '';
            window.history.replaceState({}, document.title, url.pathname + (tab ? '?tab=' + tab : ''));
        }
    </script>
</body>
</html>