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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
        .activity-item { transition: background-color 0.2s ease; }
        .activity-item:hover { background-color: #f8fafc; }
        .notification { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Admin Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-4">
                <div class="flex items-center space-x-4">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold">Admin Dashboard</h1>
                        <p class="text-blue-200 text-sm">Welcome back, <?php echo htmlspecialchars($userName); ?></p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../index.php" class="flex items-center text-white/80 hover:text-white transition-colors">
                        <i class="fas fa-home mr-2"></i> View Site
                    </a>
                    <div class="h-6 w-px bg-white/30"></div>
                    <a href="../logout.php" class="flex items-center text-white/80 hover:text-white transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                    <div class="h-6 w-px bg-white/30"></div>
                    <span class="text-sm text-blue-200"><?php echo date('l, F j, Y'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Tourist Spots -->
            <div class="stat-card bg-white rounded-xl shadow border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Tourist Spots</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $stats['totalSpots']; ?></p>
                        <div class="flex items-center mt-2">
                            <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800">
                                <?php echo $stats['activeSpots']; ?> Active
                            </span>
                            <span class="ml-2 text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">
                                <?php echo $stats['featuredSpots']; ?> Featured
                            </span>
                        </div>
                    </div>
                    <div class="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-umbrella-beach text-blue-600 text-xl"></i>
                    </div>
                </div>
                <a href="TOURIST-SPOTS/index.php" class="mt-4 inline-flex items-center text-sm text-blue-600 hover:text-blue-700">
                    Manage Spots <i class="fas fa-arrow-right ml-2 text-xs"></i>
                </a>
            </div>

            <!-- Events -->
            <div class="stat-card bg-white rounded-xl shadow border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Events</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $stats['totalEvents']; ?></p>
                        <div class="flex items-center mt-2">
                            <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-800">
                                <?php echo $stats['upcomingEvents']; ?> Upcoming
                            </span>
                            <span class="ml-2 text-xs px-2 py-1 rounded-full bg-green-100 text-green-800">
                                <?php echo $stats['activeEvents']; ?> Active
                            </span>
                        </div>
                    </div>
                    <div class="h-12 w-12 rounded-lg bg-purple-100 flex items-center justify-center">
                        <i class="fas fa-calendar-alt text-purple-600 text-xl"></i>
                    </div>
                </div>
                <a href="EVENTS/index.php" class="mt-4 inline-flex items-center text-sm text-blue-600 hover:text-blue-700">
                    Manage Events <i class="fas fa-arrow-right ml-2 text-xs"></i>
                </a>
            </div>

            <!-- Bookings -->
            <div class="stat-card bg-white rounded-xl shadow border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Activities</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $stats['totalBookings']; ?></p>
                        <div class="flex items-center mt-2">
                            <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">
                                <?php echo $stats['pendingBookings']; ?> Pending
                            </span>
                            <span class="ml-2 text-xs px-2 py-1 rounded-full bg-green-100 text-green-800">
                                <?php echo $stats['confirmedBookings']; ?> Completed
                            </span>
                        </div>
                    </div>
                    <div class="h-12 w-12 rounded-lg bg-green-100 flex items-center justify-center">
                        <i class="fas fa-calendar-check text-green-600 text-xl"></i>
                    </div>
                </div>
                <a href="BOOKINGS/index.php" class="mt-4 inline-flex items-center text-sm text-blue-600 hover:text-blue-700">
                    View Activities <i class="fas fa-arrow-right ml-2 text-xs"></i>
                </a>
            </div>

            <!-- Users -->
            <div class="stat-card bg-white rounded-xl shadow border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Users</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $stats['totalUsers']; ?></p>
                        <div class="flex items-center mt-2">
                            <span class="text-xs px-2 py-1 rounded-full bg-indigo-100 text-indigo-800">
                                <?php echo $stats['newUsers']; ?> New (7d)
                            </span>
                            <span class="ml-2 text-xs px-2 py-1 rounded-full bg-red-100 text-red-800">
                                <?php echo $stats['adminUsers']; ?> Admins
                            </span>
                        </div>
                    </div>
                    <div class="h-12 w-12 rounded-lg bg-indigo-100 flex items-center justify-center">
                        <i class="fas fa-users text-indigo-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="MESSAGES/index.php" class="text-sm text-blue-600 hover:text-blue-700">
                        <i class="fas fa-envelope mr-1"></i> <?php echo $stats['unreadMessages']; ?> unread messages
                    </a>
                </div>
            </div>
        </div>

        <!-- Charts and Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Activity Chart -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">User Activity Overview</h2>
                        <p class="text-sm text-gray-600">Last 6 months engagement statistics</p>
                    </div>
                    <div class="p-6">
                        <div class="h-64">
                            <canvas id="activityChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div>
                <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
                    </div>
                    <div class="p-4 space-y-3">
                        <a href="TOURIST-SPOTS/create.php" class="flex items-center p-3 rounded-lg border border-gray-200 hover:bg-blue-50 transition-colors">
                            <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center mr-3">
                                <i class="fas fa-plus text-blue-600"></i>
                            </div>
                            <div><p class="font-medium text-gray-900">Add Tourist Spot</p><p class="text-sm text-gray-600">Create new destination</p></div>
                        </a>
                        <a href="EVENTS/create.php" class="flex items-center p-3 rounded-lg border border-gray-200 hover:bg-purple-50 transition-colors">
                            <div class="h-10 w-10 rounded-lg bg-purple-100 flex items-center justify-center mr-3">
                                <i class="fas fa-calendar-plus text-purple-600"></i>
                            </div>
                            <div><p class="font-medium text-gray-900">Create Event</p><p class="text-sm text-gray-600">Add new event</p></div>
                        </a>
                        <a href="BLOG-POST/create.php" class="flex items-center p-3 rounded-lg border border-gray-200 hover:bg-green-50 transition-colors">
                            <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center mr-3">
                                <i class="fas fa-edit text-green-600"></i>
                            </div>
                            <div><p class="font-medium text-gray-900">Write Blog Post</p><p class="text-sm text-gray-600">Create new article</p></div>
                        </a>
                        <a href="SETTINGS/index.php" class="flex items-center p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                            <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center mr-3">
                                <i class="fas fa-cog text-gray-600"></i>
                            </div>
                            <div><p class="font-medium text-gray-900">Settings</p><p class="text-sm text-gray-600">Configure system</p></div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Recent Bookings -->
            <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Recent Activities</h2>
                        <span class="text-sm text-blue-600"><?php echo $stats['totalBookings']; ?> total</span>
                    </div>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php if (!empty($recentBookings)): ?>
                        <?php foreach ($recentBookings as $booking): ?>
                        <div class="activity-item p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-calendar text-blue-600 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($booking['user']); ?></p>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($booking['item']); ?></p>
                                    </div>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    <?php echo $booking['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 
                                        ($booking['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'); ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-2"><?php echo $booking['time']; ?></p>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="p-6 text-center text-gray-500">
                        <i class="fas fa-calendar-times text-3xl mb-3 text-gray-300"></i>
                        <p>No recent activities</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="px-6 py-4 border-t border-gray-200">
                    <a href="BOOKINGS/index.php" class="text-blue-600 hover:text-blue-700 text-sm font-medium">View all activities →</a>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Recent Users</h2>
                        <span class="text-sm text-blue-600"><?php echo $stats['newUsers']; ?> new (7d)</span>
                    </div>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php if (!empty($recentUsers)): ?>
                        <?php foreach ($recentUsers as $user): ?>
                        <div class="activity-item p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br 
                                        <?php echo $user['is_admin'] ? 'from-red-400 to-red-600' : 'from-blue-400 to-blue-600'; ?>
                                        flex items-center justify-center text-white font-bold mr-3">
                                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></p>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                                    </div>
                                </div>
                                <?php if ($user['is_admin']): ?>
                                <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Admin</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Joined <?php echo $user['joined']; ?></p>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="p-6 text-center text-gray-500">
                        <i class="fas fa-users text-3xl mb-3 text-gray-300"></i>
                        <p>No users yet</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="px-6 py-4 border-t border-gray-200">
                    <a href="USERS/index.php" class="text-blue-600 hover:text-blue-700 text-sm font-medium">View all users →</a>
                </div>
            </div>
        </div>

        <!-- Popular Spots -->
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Popular Tourist Spots</h2>
                    <span class="text-sm text-blue-600">Most Viewed</span>
                </div>
            </div>
            <div class="divide-y divide-gray-100">
                <?php if (!empty($popularSpots)): ?>
                    <?php foreach ($popularSpots as $spot): ?>
                    <div class="activity-item p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-eye text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($spot['name']); ?></p>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-star text-amber-500 mr-1"></i>
                                        <span class="mr-3"><?php echo number_format($spot['rating'], 1); ?></span>
                                        <i class="fas fa-eye text-gray-400 mr-1"></i>
                                        <span><?php echo number_format($spot['views']); ?> views</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="p-6 text-center text-gray-500">
                    <i class="fas fa-map-marker-alt text-3xl mb-3 text-gray-300"></i>
                    <p>No spots available</p>
                </div>
                <?php endif; ?>
            </div>
            <div class="px-6 py-4 border-t border-gray-200">
                <a href="TOURIST-SPOTS/index.php" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Manage all spots →</a>
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
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true }, x: { grid: { display: false } } }
            }
        });

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 notification ${
                type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'
            } text-white`;
            notification.innerHTML = `<div class="flex items-center"><i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} mr-2"></i><span>${message}</span></div>`;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }

        window.addEventListener('load', () => setTimeout(() => showNotification('Welcome to Admin Dashboard!', 'success'), 500));
    </script>
</body>
</html>