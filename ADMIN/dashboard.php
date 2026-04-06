<?php
// ADMIN/admin_dashboard.php - Admin Dashboard
require_once '../dbconn.php';  // Go up one level to find dbconn.php

// Check if user is logged in and is admin
if (!isLoggedIn()) {
    redirect('../login.php');
}

if (!isAdmin()) {
    redirect('../index.php');
}

// Get user info
$user_id = $_SESSION['user_id'];
$userName = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Administrator';

// Fetch real statistics from database
$stats = [];

// Tourist Spots
$result = query("SELECT COUNT(*) as count FROM info_attractions");
$row = fetchOne($result);
$stats['totalSpots'] = $row['count'] ?? 0;

// Active spots (with views > 0)
$result = query("SELECT COUNT(*) as count FROM info_attractions WHERE views_count > 0");
$row = fetchOne($result);
$stats['activeSpots'] = $row['count'] ?? 0;

// Featured spots (rating >= 4.5 from feedback)
$result = query("
    SELECT COUNT(DISTINCT a.id) as count 
    FROM info_attractions a
    JOIN info_feedback f ON f.target_id = a.id AND f.target_type = 'attraction'
    WHERE f.rating >= 4.5
");
$row = fetchOne($result);
$stats['featuredSpots'] = $row['count'] ?? 0;

// Events
$result = query("SELECT COUNT(*) as count FROM info_events");
$row = fetchOne($result);
$stats['totalEvents'] = $row['count'] ?? 0;

$result = query("SELECT COUNT(*) as count FROM info_events WHERE status = 'upcoming'");
$row = fetchOne($result);
$stats['upcomingEvents'] = $row['count'] ?? 0;

$result = query("SELECT COUNT(*) as count FROM info_events WHERE status = 'ongoing'");
$row = fetchOne($result);
$stats['activeEvents'] = $row['count'] ?? 0;

// Bookings (using reward transactions as proxy)
$result = query("SELECT COUNT(*) as count FROM info_reward_transactions");
$row = fetchOne($result);
$stats['totalBookings'] = $row['count'] ?? 0;

$result = query("SELECT COUNT(*) as count FROM info_reward_transactions WHERE metadata->>'status' = 'pending'");
$row = fetchOne($result);
$stats['pendingBookings'] = $row['count'] ?? 0;

$result = query("SELECT COUNT(*) as count FROM info_reward_transactions WHERE metadata->>'status' = 'confirmed'");
$row = fetchOne($result);
$stats['confirmedBookings'] = $row['count'] ?? 0;

// Users
$result = query("SELECT COUNT(*) as count FROM info_profiles");
$row = fetchOne($result);
$stats['totalUsers'] = $row['count'] ?? 0;

$result = query("SELECT COUNT(*) as count FROM info_profiles WHERE created_at > NOW() - INTERVAL '7 days'");
$row = fetchOne($result);
$stats['newUsers'] = $row['count'] ?? 0;

$result = query("SELECT COUNT(*) as count FROM info_profiles WHERE role = 'admin'");
$row = fetchOne($result);
$stats['adminUsers'] = $row['count'] ?? 0;

// Unread notifications
$result = query("SELECT COUNT(*) as count FROM info_notifications WHERE user_id = $1 AND is_read = false", [$user_id]);
$row = fetchOne($result);
$stats['unreadMessages'] = $row['count'] ?? 0;

// Monthly activity data
$bookingData = [];
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
for ($i = 5; $i >= 0; $i--) {
    $result = query("
        SELECT COUNT(*) as count 
        FROM info_reward_transactions 
        WHERE DATE_TRUNC('month', created_at) = DATE_TRUNC('month', NOW() - ($1 || ' months')::INTERVAL)
    ", [$i]);
    $row = fetchOne($result);
    $bookingData[] = [
        'month' => $months[5 - $i],
        'count' => (int)($row['count'] ?? 0)
    ];
}

// Recent users
$recentUsers = [];
$result = query("
    SELECT full_name, email, role, created_at 
    FROM info_profiles 
    ORDER BY created_at DESC 
    LIMIT 4
");
if ($result) {
    $users = fetchAll($result);
    foreach ($users as $user) {
        $recentUsers[] = [
            'name' => $user['full_name'] ?? 'Unknown User',
            'email' => $user['email'],
            'is_admin' => ($user['role'] ?? '') === 'admin',
            'joined' => time_ago(strtotime($user['created_at']))
        ];
    }
}

// Popular spots
$popularSpots = [];
$result = query("
    SELECT name, views_count,
        COALESCE((SELECT AVG(rating)::DECIMAL(3,2) FROM info_feedback WHERE target_id = id AND target_type = 'attraction'), 0) as rating
    FROM info_attractions 
    ORDER BY views_count DESC 
    LIMIT 4
");
if ($result) {
    $spots = fetchAll($result);
    foreach ($spots as $spot) {
        $popularSpots[] = [
            'name' => $spot['name'],
            'rating' => round((float)($spot['rating'] ?? 0), 1),
            'views' => (int)($spot['views_count'] ?? 0)
        ];
    }
}

// Recent bookings/reward transactions
$recentBookings = [];
$result = query("
    SELECT rt.action, rt.created_at, rt.metadata, p.full_name as user_name
    FROM info_reward_transactions rt
    JOIN info_profiles p ON p.id = rt.user_id
    ORDER BY rt.created_at DESC
    LIMIT 4
");
if ($result) {
    $bookings = fetchAll($result);
    foreach ($bookings as $booking) {
        $recentBookings[] = [
            'user' => $booking['user_name'] ?? 'Unknown',
            'item' => $booking['action'] ?? 'Activity',
            'status' => json_decode($booking['metadata'] ?? '{}', true)['status'] ?? 'completed',
            'time' => time_ago(strtotime($booking['created_at']))
        ];
    }
}

// Helper function
function time_ago($timestamp) {
    $diff = time() - $timestamp;
    if ($diff < 60) return $diff . ' seconds ago';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M j, Y', $timestamp);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Daeteño</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        
        .stat-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.15);
        }
        
        .activity-item {
            transition: all 0.2s ease;
        }
        .activity-item:hover {
            background: linear-gradient(90deg, rgba(59,130,246,0.05) 0%, rgba(59,130,246,0) 100%);
            padding-left: 0.5rem;
        }
        
        .notification {
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .glass-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #0f2b45 100%);
            backdrop-filter: blur(10px);
        }
        
        .sidebar-link {
            transition: all 0.2s ease;
        }
        .sidebar-link:hover {
            background: rgba(255,255,255,0.1);
            transform: translateX(4px);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #3b82f6, #06b6d4);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .chart-container {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            transition: all 0.3s ease;
        }
        .chart-container:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.05);
        }
        
        .avatar-circle {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
        
        .admin-badge {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        @keyframes pulse-ring {
            0% { transform: scale(0.95); opacity: 0.7; }
            100% { transform: scale(1.05); opacity: 0; }
        }
        
        .notification-dot {
            animation: pulse-ring 2s infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-slate-100">

    <!-- Modern Admin Header -->
    <div class="glass-header text-white sticky top-0 z-50 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-4">
                <div class="flex items-center space-x-4">
                    <div class="h-10 w-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg">
                        <i class="fas fa-shield-alt text-white text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight">Admin Dashboard</h1>
                        <p class="text-blue-200 text-xs opacity-90">Welcome back, <?php echo htmlspecialchars($userName); ?></p>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="../index.php" class="hidden sm:flex items-center text-white/80 hover:text-white transition-all duration-200 text-sm font-medium hover:scale-105">
                        <i class="fas fa-globe-asia mr-2 text-sm"></i> View Site
                    </a>
                    <div class="h-6 w-px bg-white/20 hidden sm:block"></div>
                    <a href="../logout.php" class="flex items-center text-white/80 hover:text-white transition-all duration-200 text-sm font-medium hover:scale-105">
                        <i class="fas fa-sign-out-alt mr-2 text-sm"></i> Logout
                    </a>
                    <div class="h-6 w-px bg-white/20 hidden sm:block"></div>
                    <div class="flex items-center space-x-2 bg-white/10 px-3 py-1.5 rounded-full backdrop-blur-sm">
                        <i class="fas fa-calendar-alt text-blue-200 text-xs"></i>
                        <span class="text-sm font-medium"><?php echo date('M d, Y'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Welcome Banner -->
        <div class="mb-8 bg-gradient-to-r from-blue-600/10 via-cyan-600/10 to-transparent rounded-2xl p-6 border border-blue-100/50">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Dashboard Overview</h2>
                    <p class="text-slate-500 mt-1">Monitor your platform's performance and manage content</p>
                </div>
                <div class="flex items-center space-x-2 bg-white/60 backdrop-blur-sm px-4 py-2 rounded-full shadow-sm">
                    <i class="fas fa-chart-line text-blue-500 text-sm"></i>
                    <span class="text-sm text-slate-600">Last updated: <?php echo date('g:i A'); ?></span>
                </div>
            </div>
        </div>

        <!-- Quick Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Tourist Spots Card -->
            <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <div class="h-12 w-12 rounded-xl bg-blue-100 flex items-center justify-center shadow-sm">
                            <i class="fas fa-umbrella-beach text-blue-600 text-xl"></i>
                        </div>
                        <div class="flex gap-1">
                            <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-medium"><?php echo $stats['activeSpots']; ?> Active</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 font-medium"><?php echo $stats['featuredSpots']; ?> Featured</span>
                        </div>
                    </div>
                    <p class="text-slate-500 text-sm font-medium mb-1">Tourist Spots</p>
                    <p class="text-3xl font-bold text-slate-800"><?php echo $stats['totalSpots']; ?></p>
                    <a href="TOURIST-SPOTS/index.php" class="mt-4 inline-flex items-center text-sm text-blue-600 hover:text-blue-700 font-medium gap-1 group-hover:gap-2 transition-all">
                        Manage Spots <i class="fas fa-arrow-right text-xs transition-transform group-hover:translate-x-1"></i>
                    </a>
                </div>
            </div>

            <!-- Events Card -->
            <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-purple-50 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <div class="h-12 w-12 rounded-xl bg-purple-100 flex items-center justify-center shadow-sm">
                            <i class="fas fa-calendar-alt text-purple-600 text-xl"></i>
                        </div>
                        <div class="flex gap-1">
                            <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-medium"><?php echo $stats['upcomingEvents']; ?> Upcoming</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-medium"><?php echo $stats['activeEvents']; ?> Active</span>
                        </div>
                    </div>
                    <p class="text-slate-500 text-sm font-medium mb-1">Events</p>
                    <p class="text-3xl font-bold text-slate-800"><?php echo $stats['totalEvents']; ?></p>
                    <a href="EVENTS/index.php" class="mt-4 inline-flex items-center text-sm text-blue-600 hover:text-blue-700 font-medium gap-1 group-hover:gap-2 transition-all">
                        Manage Events <i class="fas fa-arrow-right text-xs transition-transform group-hover:translate-x-1"></i>
                    </a>
                </div>
            </div>

            <!-- Activities Card -->
            <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-green-50 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <div class="h-12 w-12 rounded-xl bg-green-100 flex items-center justify-center shadow-sm">
                            <i class="fas fa-calendar-check text-green-600 text-xl"></i>
                        </div>
                        <div class="flex gap-1">
                            <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 font-medium"><?php echo $stats['pendingBookings']; ?> Pending</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-medium"><?php echo $stats['confirmedBookings']; ?> Completed</span>
                        </div>
                    </div>
                    <p class="text-slate-500 text-sm font-medium mb-1">Activities</p>
                    <p class="text-3xl font-bold text-slate-800"><?php echo $stats['totalBookings']; ?></p>
                    <a href="BOOKINGS/index.php" class="mt-4 inline-flex items-center text-sm text-blue-600 hover:text-blue-700 font-medium gap-1 group-hover:gap-2 transition-all">
                        View Activities <i class="fas fa-arrow-right text-xs transition-transform group-hover:translate-x-1"></i>
                    </a>
                </div>
            </div>

            <!-- Users Card -->
            <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-50 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <div class="h-12 w-12 rounded-xl bg-indigo-100 flex items-center justify-center shadow-sm">
                            <i class="fas fa-users text-indigo-600 text-xl"></i>
                        </div>
                        <div class="flex gap-1">
                            <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 font-medium"><?php echo $stats['newUsers']; ?> New (7d)</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-medium"><?php echo $stats['adminUsers']; ?> Admins</span>
                        </div>
                    </div>
                    <p class="text-slate-500 text-sm font-medium mb-1">Users</p>
                    <p class="text-3xl font-bold text-slate-800"><?php echo $stats['totalUsers']; ?></p>
                    <div class="mt-4">
                        <a href="MESSAGES/index.php" class="inline-flex items-center text-sm text-blue-600 hover:text-blue-700 font-medium gap-1">
                            <i class="fas fa-envelope mr-1 text-xs"></i> <?php echo $stats['unreadMessages']; ?> unread messages
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Quick Actions Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Activity Chart -->
            <div class="lg:col-span-2 chart-container bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm">
                <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">User Activity Overview</h2>
                            <p class="text-sm text-slate-500 mt-0.5">Last 6 months engagement statistics</p>
                        </div>
                        <div class="h-8 w-8 rounded-full bg-blue-50 flex items-center justify-center">
                            <i class="fas fa-chart-line text-blue-500 text-sm"></i>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="h-72">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm">
                <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                    <h2 class="text-lg font-semibold text-slate-800">Quick Actions</h2>
                    <p class="text-sm text-slate-500 mt-0.5">Frequently used admin tools</p>
                </div>
                <div class="p-5 space-y-3">
                    <a href="TOURIST-SPOTS/create.php" class="flex items-center p-3 rounded-xl border border-slate-100 hover:border-blue-200 hover:bg-blue-50/30 transition-all duration-200 group">
                        <div class="h-10 w-10 rounded-xl bg-blue-100 flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                            <i class="fas fa-plus text-blue-600 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-slate-800">Add Tourist Spot</p>
                            <p class="text-xs text-slate-500">Create new destination</p>
                        </div>
                        <i class="fas fa-chevron-right text-slate-300 text-xs group-hover:translate-x-1 transition-transform"></i>
                    </a>
                    <a href="EVENTS/create.php" class="flex items-center p-3 rounded-xl border border-slate-100 hover:border-purple-200 hover:bg-purple-50/30 transition-all duration-200 group">
                        <div class="h-10 w-10 rounded-xl bg-purple-100 flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                            <i class="fas fa-calendar-plus text-purple-600 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-slate-800">Create Event</p>
                            <p class="text-xs text-slate-500">Add new event</p>
                        </div>
                        <i class="fas fa-chevron-right text-slate-300 text-xs group-hover:translate-x-1 transition-transform"></i>
                    </a>
                    <a href="BLOG-POST/create.php" class="flex items-center p-3 rounded-xl border border-slate-100 hover:border-green-200 hover:bg-green-50/30 transition-all duration-200 group">
                        <div class="h-10 w-10 rounded-xl bg-green-100 flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                            <i class="fas fa-edit text-green-600 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-slate-800">Write Blog Post</p>
                            <p class="text-xs text-slate-500">Create new article</p>
                        </div>
                        <i class="fas fa-chevron-right text-slate-300 text-xs group-hover:translate-x-1 transition-transform"></i>
                    </a>
                    <a href="SETTINGS/index.php" class="flex items-center p-3 rounded-xl border border-slate-100 hover:border-slate-300 hover:bg-slate-50 transition-all duration-200 group">
                        <div class="h-10 w-10 rounded-xl bg-slate-100 flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                            <i class="fas fa-cog text-slate-600 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-slate-800">Settings</p>
                            <p class="text-xs text-slate-500">Configure system</p>
                        </div>
                        <i class="fas fa-chevron-right text-slate-300 text-xs group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activities Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Recent Bookings -->
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm">
                <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">Recent Activities</h2>
                            <p class="text-sm text-slate-500 mt-0.5">Latest user interactions</p>
                        </div>
                        <span class="text-sm text-blue-600 font-medium bg-blue-50 px-3 py-1 rounded-full"><?php echo $stats['totalBookings']; ?> total</span>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    <?php if (!empty($recentBookings)): ?>
                        <?php foreach ($recentBookings as $booking): ?>
                        <div class="activity-item p-5 transition-all duration-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mr-3 shadow-sm">
                                        <i class="fas fa-calendar text-blue-600 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($booking['user']); ?></p>
                                        <p class="text-sm text-slate-500"><?php echo htmlspecialchars($booking['item']); ?></p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full 
                                    <?php echo $booking['status'] === 'confirmed' ? 'bg-green-100 text-green-700' : 
                                        ($booking['status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-slate-100 text-slate-600'); ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-3 flex items-center gap-1">
                                <i class="far fa-clock"></i> <?php echo $booking['time']; ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-calendar-times text-slate-400 text-2xl"></i>
                        </div>
                        <p class="text-slate-500 font-medium">No recent activities</p>
                        <p class="text-sm text-slate-400 mt-1">Activities will appear here when users interact</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                    <a href="BOOKINGS/index.php" class="text-blue-600 hover:text-blue-700 text-sm font-medium inline-flex items-center gap-1 group">
                        View all activities <i class="fas fa-arrow-right text-xs transition-transform group-hover:translate-x-1"></i>
                    </a>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm">
                <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">Recent Users</h2>
                            <p class="text-sm text-slate-500 mt-0.5">Newest platform members</p>
                        </div>
                        <span class="text-sm text-blue-600 font-medium bg-blue-50 px-3 py-1 rounded-full"><?php echo $stats['newUsers']; ?> new (7d)</span>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    <?php if (!empty($recentUsers)): ?>
                        <?php foreach ($recentUsers as $user): ?>
                        <div class="activity-item p-5 transition-all duration-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full flex items-center justify-center text-white font-bold mr-3 shadow-sm
                                        <?php echo $user['is_admin'] ? 'admin-badge' : 'avatar-circle'; ?>">
                                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($user['name']); ?></p>
                                        <p class="text-sm text-slate-500"><?php echo htmlspecialchars($user['email']); ?></p>
                                    </div>
                                </div>
                                <?php if ($user['is_admin']): ?>
                                <span class="px-2 py-1 text-xs font-semibold bg-red-100 text-red-700 rounded-full flex items-center gap-1">
                                    <i class="fas fa-shield-alt text-xs"></i> Admin
                                </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-slate-400 mt-3 flex items-center gap-1">
                                <i class="far fa-clock"></i> Joined <?php echo $user['joined']; ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-users text-slate-400 text-2xl"></i>
                        </div>
                        <p class="text-slate-500 font-medium">No users yet</p>
                        <p class="text-sm text-slate-400 mt-1">Users will appear here when they register</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                    <a href="USERS/index.php" class="text-blue-600 hover:text-blue-700 text-sm font-medium inline-flex items-center gap-1 group">
                        View all users <i class="fas fa-arrow-right text-xs transition-transform group-hover:translate-x-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Popular Spots Section -->
        <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm">
            <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">Popular Tourist Spots</h2>
                        <p class="text-sm text-slate-500 mt-0.5">Most viewed destinations</p>
                    </div>
                    <span class="text-sm text-blue-600 font-medium bg-blue-50 px-3 py-1 rounded-full flex items-center gap-1">
                        <i class="fas fa-fire text-xs"></i> Most Viewed
                    </span>
                </div>
            </div>
            <div class="divide-y divide-slate-100">
                <?php if (!empty($popularSpots)): ?>
                    <?php foreach ($popularSpots as $index => $spot): ?>
                    <div class="activity-item p-5 transition-all duration-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center flex-1">
                                <div class="w-8 text-center mr-3">
                                    <span class="text-sm font-bold <?php echo $index === 0 ? 'text-yellow-500' : ($index === 1 ? 'text-slate-400' : ($index === 2 ? 'text-amber-600' : 'text-slate-500')); ?>">
                                        #<?php echo $index + 1; ?>
                                    </span>
                                </div>
                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-map-marker-alt text-blue-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($spot['name']); ?></p>
                                    <div class="flex items-center gap-3 text-sm">
                                        <div class="flex items-center gap-1">
                                            <i class="fas fa-star text-amber-400 text-xs"></i>
                                            <span class="text-slate-600"><?php echo number_format($spot['rating'], 1); ?></span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <i class="fas fa-eye text-slate-400 text-xs"></i>
                                            <span class="text-slate-500"><?php echo number_format($spot['views']); ?> views</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="w-20 h-1 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full" style="width: <?php echo min(100, ($spot['views'] / max(array_column($popularSpots, 'views')) * 100)); ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-map-marker-alt text-slate-400 text-2xl"></i>
                    </div>
                    <p class="text-slate-500 font-medium">No spots available</p>
                    <p class="text-sm text-slate-400 mt-1">Add tourist spots to see them here</p>
                </div>
                <?php endif; ?>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                <a href="TOURIST-SPOTS/index.php" class="text-blue-600 hover:text-blue-700 text-sm font-medium inline-flex items-center gap-1 group">
                    Manage all spots <i class="fas fa-arrow-right text-xs transition-transform group-hover:translate-x-1"></i>
                </a>
            </div>
        </div>
    </div>

    <script>
        const activityData = <?php echo json_encode($bookingData); ?>;
        
        const ctx = document.getElementById('activityChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: activityData.map(item => item.month),
                datasets: [{
                    label: 'User Actions',
                    data: activityData.map(item => item.count),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.05)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#ffffff',
                        bodyColor: '#94a3b8',
                        padding: 10,
                        cornerRadius: 8,
                    }
                },
                scales: { 
                    y: { 
                        beginAtZero: true,
                        grid: { color: '#e2e8f0', drawBorder: false },
                        ticks: { stepSize: 1 }
                    }, 
                    x: { 
                        grid: { display: false },
                        ticks: { font: { weight: '500' } }
                    } 
                }
            }
        });

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-20 right-4 px-5 py-3 rounded-xl shadow-xl z-50 notification ${
                type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'
            } text-white`;
            notification.innerHTML = `<div class="flex items-center gap-2"><i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i><span class="font-medium">${message}</span></div>`;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }

        window.addEventListener('load', () => setTimeout(() => showNotification('Welcome to Admin Dashboard!', 'success'), 500));
    </script>
</body>
</html>