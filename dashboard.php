<?php
// dashboard.php - User Dashboard
require_once 'dbconn.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('home.php');
}

// Get user data from database
$user_id = $_SESSION['user_id'];
$user_query = "SELECT full_name as name, email FROM info_profiles WHERE id = $1";
$user_result = query($user_query, [$user_id]);

if ($user_result === false) {
    $user = [
        'name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? ''
    ];
} else {
    $user = fetchOne($user_result);
    if (!$user) {
        $user = [
            'name' => $_SESSION['user_name'] ?? 'User',
            'email' => $_SESSION['user_email'] ?? ''
        ];
    }
}

// Get user statistics from database - Updated for your schema
$stats_query = "
    SELECT 
        COUNT(DISTINCT f.id) as feedback_count,
        COUNT(DISTINCT fp.id) as forum_posts,
        COUNT(DISTINCT fr.id) as rewards_points
    FROM info_profiles u
    LEFT JOIN info_feedback f ON f.user_id = u.id
    LEFT JOIN info_forum_posts fp ON fp.user_id = u.id
    LEFT JOIN info_rewards fr ON fr.user_id = u.id
    WHERE u.id = $1
";
$stats_result = query($stats_query, [$user_id]);

if ($stats_result === false) {
    $userStats = [
        'feedback_count' => 0,
        'forum_posts' => 0,
        'rewards_points' => 0,
        'saved_spots' => 0
    ];
} else {
    $userStats = fetchOne($stats_result);
    $userStats = [
        'feedback_count' => $userStats['feedback_count'] ?? 0,
        'forum_posts' => $userStats['forum_posts'] ?? 0,
        'rewards_points' => $userStats['rewards_points'] ?? 0,
        'saved_spots' => 0  // You'll need to create a favorites table if you want this
    ];
}

// Get user's feedback/reviews
$feedback_query = "
    SELECT 
        f.id,
        f.target_type,
        f.target_id,
        f.rating,
        f.review,
        f.created_at,
        CASE 
            WHEN f.target_type = 'attraction' THEN a.name
            WHEN f.target_type = 'event' THEN e.title
            WHEN f.target_type = 'amenity' THEN am.name
            ELSE 'Unknown'
        END as title
    FROM info_feedback f
    LEFT JOIN info_attractions a ON f.target_type = 'attraction' AND f.target_id = a.id
    LEFT JOIN info_events e ON f.target_type = 'event' AND f.target_id = e.id
    LEFT JOIN info_amenities am ON f.target_type = 'amenity' AND f.target_id = am.id
    WHERE f.user_id = $1
    ORDER BY f.created_at DESC
    LIMIT 5
";
$feedback_result = query($feedback_query, [$user_id]);

if ($feedback_result === false) {
    $recentFeedback = [];
} else {
    $recentFeedback = fetchAll($feedback_result);
    $recentFeedback = array_map(function($item) {
        return (object)[
            'id' => $item['id'],
            'title' => $item['title'],
            'rating' => $item['rating'],
            'review' => $item['review'],
            'created_at' => $item['created_at']
        ];
    }, $recentFeedback);
}

// Get user's forum posts
$forum_posts_query = "
    SELECT 
        id,
        title,
        content,
        is_answered,
        created_at
    FROM info_forum_posts
    WHERE user_id = $1
    ORDER BY created_at DESC
    LIMIT 5
";
$forum_result = query($forum_posts_query, [$user_id]);

if ($forum_result === false) {
    $forumPosts = [];
} else {
    $forumPosts = fetchAll($forum_result);
    $forumPosts = array_map(function($post) {
        return (object)[
            'id' => $post['id'],
            'title' => $post['title'],
            'is_answered' => $post['is_answered'],
            'created_at' => $post['created_at']
        ];
    }, $forumPosts);
}

// Get user's reward info
$reward_query = "
    SELECT 
        total_points,
        badges,
        level
    FROM info_rewards
    WHERE user_id = $1
";
$reward_result = query($reward_query, [$user_id]);

if ($reward_result === false) {
    $userRewards = (object)[
        'total_points' => 0,
        'badges' => [],
        'level' => 'beginner'
    ];
} else {
    $rewardData = fetchOne($reward_result);
    $userRewards = (object)[
        'total_points' => $rewardData['total_points'] ?? 0,
        'badges' => $rewardData['badges'] ?? [],
        'level' => $rewardData['level'] ?? 'beginner'
    ];
}

// Get recent activity from database
$activity_query = "
    (
        SELECT 
            'feedback' as type,
            'You reviewed ' || 
            CASE 
                WHEN f.target_type = 'attraction' THEN a.name
                WHEN f.target_type = 'event' THEN e.title
                WHEN f.target_type = 'amenity' THEN am.name
            END as message,
            f.created_at as activity_date
        FROM info_feedback f
        LEFT JOIN info_attractions a ON f.target_type = 'attraction' AND f.target_id = a.id
        LEFT JOIN info_events e ON f.target_type = 'event' AND f.target_id = e.id
        LEFT JOIN info_amenities am ON f.target_type = 'amenity' AND f.target_id = am.id
        WHERE f.user_id = $1
    )
    UNION ALL
    (
        SELECT 
            'forum_post' as type,
            'You created a forum post: ' || fp.title as message,
            fp.created_at as activity_date
        FROM info_forum_posts fp
        WHERE fp.user_id = $1
    )
    UNION ALL
    (
        SELECT 
            'forum_reply' as type,
            'You replied to a forum post' as message,
            fr.created_at as activity_date
        FROM info_forum_replies fr
        WHERE fr.user_id = $1
    )
    ORDER BY activity_date DESC
    LIMIT 10
";
$activity_result = query($activity_query, [$user_id]);

$activityConfig = [
    'feedback' => ['icon' => 'star', 'color' => 'bg-green-500'],
    'forum_post' => ['icon' => 'comment', 'color' => 'bg-blue-500'],
    'forum_reply' => ['icon' => 'reply', 'color' => 'bg-purple-500']
];

if ($activity_result === false) {
    $recentActivity = [];
} else {
    $recentActivities = fetchAll($activity_result);
    $recentActivity = array_map(function($activity) use ($activityConfig) {
        $type = $activity['type'];
        $config = $activityConfig[$type] ?? ['icon' => 'bell', 'color' => 'bg-gray-500'];
        
        $time = $activity['activity_date'];
        $timeDiff = time() - strtotime($time);
        if ($timeDiff < 3600) {
            $timeAgo = floor($timeDiff / 60) . ' minutes ago';
        } elseif ($timeDiff < 86400) {
            $timeAgo = floor($timeDiff / 3600) . ' hours ago';
        } else {
            $timeAgo = date('M d, Y', strtotime($time));
        }
        
        return [
            'message' => $activity['message'],
            'time' => $timeAgo,
            'color' => $config['color'],
            'icon' => $config['icon']
        ];
    }, $recentActivities);
}

$title = 'Dashboard - Daeteño';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background: #f9fafb; }
        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <a href="home.php" class="text-2xl font-bold bg-gradient-to-r from-green-600 to-yellow-500 bg-clip-text text-transparent">
                        Daeteño
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-6">
                    <a href="home.php" class="text-gray-600 hover:text-green-600">Home</a>
                    <a href="attractions.php" class="text-gray-600 hover:text-green-600">Attractions</a>
                    <a href="events.php" class="text-gray-600 hover:text-green-600">Events</a>
                    <a href="forum.php" class="text-gray-600 hover:text-green-600">Forum</a>
                    <a href="contact.php" class="text-gray-600 hover:text-green-600">Contact</a>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 text-gray-700">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-green-500 to-yellow-500 flex items-center justify-center text-white font-bold">
                                <?php echo substr($user['name'], 0, 1); ?>
                            </div>
                            <span><?php echo htmlspecialchars($user['name']); ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-1 z-50">
                            <a href="dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-green-50">Dashboard</a>
                            <a href="PROFILE/edit-profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-green-50">Profile</a>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Welcome Header -->
        <div class="mb-8 fade-in">
            <h1 class="text-3xl font-bold text-gray-900">Welcome back, <?php echo htmlspecialchars($user['name']); ?>! 👋</h1>
            <p class="text-gray-600 mt-2">Here's what's happening with Daet tourism today.</p>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 fade-in">
            <div class="stat-card bg-white rounded-xl shadow p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Reviews Written</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $userStats['feedback_count']; ?></p>
                    </div>
                    <div class="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-star text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card bg-white rounded-xl shadow p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Forum Posts</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $userStats['forum_posts']; ?></p>
                    </div>
                    <div class="h-12 w-12 rounded-lg bg-green-100 flex items-center justify-center">
                        <i class="fas fa-comments text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card bg-white rounded-xl shadow p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Points Earned</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $userRewards->total_points; ?></p>
                    </div>
                    <div class="h-12 w-12 rounded-lg bg-yellow-100 flex items-center justify-center">
                        <i class="fas fa-trophy text-yellow-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card bg-white rounded-xl shadow p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Badge Level</p>
                        <p class="text-2xl font-bold text-gray-900 capitalize"><?php echo $userRewards->level; ?></p>
                    </div>
                    <div class="h-12 w-12 rounded-lg bg-purple-100 flex items-center justify-center">
                        <i class="fas fa-medal text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Recent Reviews -->
            <div class="lg:col-span-2 fade-in">
                <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Your Recent Reviews</h2>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <?php if(count($recentFeedback) > 0): ?>
                            <?php foreach($recentFeedback as $review): ?>
                            <div class="p-6 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-medium text-gray-900">
                                        <?php echo htmlspecialchars($review->title); ?>
                                    </h3>
                                    <div class="flex items-center">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star text-sm <?php echo $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="text-gray-600 text-sm"><?php echo htmlspecialchars(substr($review->review ?? '', 0, 150)); ?>...</p>
                                <p class="text-xs text-gray-400 mt-2"><?php echo date('M d, Y', strtotime($review->created_at)); ?></p>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-6 text-center text-gray-500">
                                <i class="fas fa-star text-4xl mb-4 text-gray-300"></i>
                                <p>No reviews yet</p>
                                <a href="attractions.php" class="inline-block mt-2 text-blue-600 hover:text-blue-700">
                                    Write your first review →
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if(count($recentFeedback) > 0): ?>
                    <div class="px-6 py-4 border-t border-gray-200">
                        <a href="my-reviews.php" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                            View all reviews →
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Actions & Recent Activity -->
            <div class="fade-in">
                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden mb-8">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
                    </div>
                    <div class="p-4 space-y-3">
                        <a href="attractions.php" class="flex items-center p-3 rounded-lg border border-gray-200 hover:bg-blue-50 hover:border-blue-200 transition-colors group">
                            <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center mr-3">
                                <i class="fas fa-landmark text-blue-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Explore Attractions</p>
                                <p class="text-sm text-gray-600">Discover new places in Daet</p>
                            </div>
                        </a>
                        
                        <a href="events.php" class="flex items-center p-3 rounded-lg border border-gray-200 hover:bg-green-50 hover:border-green-200 transition-colors group">
                            <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center mr-3">
                                <i class="fas fa-calendar-alt text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Browse Events</p>
                                <p class="text-sm text-gray-600">Find local events to join</p>
                            </div>
                        </a>
                        
                        <a href="forum.php" class="flex items-center p-3 rounded-lg border border-gray-200 hover:bg-purple-50 hover:border-purple-200 transition-colors group">
                            <div class="h-10 w-10 rounded-lg bg-purple-100 flex items-center justify-center mr-3">
                                <i class="fas fa-comments text-purple-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Join Forum</p>
                                <p class="text-sm text-gray-600">Share your experiences</p>
                            </div>
                        </a>
                        
                        <a href="edit-profile.php" class="flex items-center p-3 rounded-lg border border-gray-200 hover:bg-orange-50 hover:border-orange-200 transition-colors group">
                            <div class="h-10 w-10 rounded-lg bg-orange-100 flex items-center justify-center mr-3">
                                <i class="fas fa-user-edit text-orange-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Update Profile</p>
                                <p class="text-sm text-gray-600">Edit your personal information</p>
                            </div>
                        </a>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Recent Activity</h2>
                    </div>
                    <div class="p-4">
                        <?php if(count($recentActivity) > 0): ?>
                            <?php foreach($recentActivity as $activity): ?>
                            <div class="flex items-start mb-4 last:mb-0">
                                <div class="h-8 w-8 rounded-full <?php echo $activity['color']; ?> flex items-center justify-center mr-3 mt-1">
                                    <i class="fas fa-<?php echo $activity['icon']; ?> text-white text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-900"><?php echo htmlspecialchars($activity['message']); ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo $activity['time']; ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-500 text-center py-4">No recent activity</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>