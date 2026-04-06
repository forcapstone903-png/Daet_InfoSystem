<?php
// home.php - Homepage
require_once 'dbconn.php';

// Fetch featured spots from database
$featuredSpots = [];
$featuredQuery = "SELECT * FROM info_attractions ORDER BY views_count DESC NULLS LAST LIMIT 3";
$featuredResult = query($featuredQuery);

if ($featuredResult && pg_num_rows($featuredResult) > 0) {
    while ($row = pg_fetch_assoc($featuredResult)) {
        $featuredSpots[] = (object)$row;
    }
}

// If no spots found, fetch any 3 spots as fallback
if (empty($featuredSpots)) {
    $fallbackQuery = "SELECT * FROM info_attractions LIMIT 3";
    $fallbackResult = query($fallbackQuery);
    if ($fallbackResult && pg_num_rows($fallbackResult) > 0) {
        while ($row = pg_fetch_assoc($fallbackResult)) {
            $featuredSpots[] = (object)$row;
        }
    }
}

// Fetch upcoming events - Using parameterized query if your query function supports it
$upcomingEvents = [];
$today = date('Y-m-d H:i:s');

// Try using query with parameters (if your query function supports it)
// If not, we'll use a safer approach without pg_escape_string
$eventsQuery = "SELECT * FROM info_events WHERE start_date >= '$today' AND status IN ('upcoming', 'ongoing') ORDER BY start_date ASC LIMIT 6";
$eventsResult = query($eventsQuery);

if ($eventsResult && pg_num_rows($eventsResult) > 0) {
    while ($event = pg_fetch_assoc($eventsResult)) {
        // Parse additional info from description
        $description = $event['description'] ?? '';
        $additionalInfo = [];
        
        // Try to extract JSON from description
        if (preg_match('/---\nAdditional Information:\n(\{.*\})/s', $description, $matches)) {
            $additionalInfo = json_decode($matches[1], true);
            // Remove the JSON part from description for display
            $description = trim(preg_replace('/---\nAdditional Information:\n\{.*\}/s', '', $description));
        }
        
        // Get image - check database for image
        $imageUrl = 'https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&q=80';
        
        // Check if event has an image in additional info
        if (!empty($additionalInfo['image'])) {
            $imageUrl = $additionalInfo['image'];
        }
        // Check if there's an images column in the events table
        elseif (!empty($event['images'])) {
            $images = trim($event['images'], '{}');
            if (!empty($images)) {
                $firstImage = explode(',', $images)[0];
                $firstImage = trim($firstImage, '"');
                if (!empty($firstImage)) {
                    $imageUrl = $firstImage;
                }
            }
        }
        
        $upcomingEvents[] = (object)[
            'id' => $event['id'],
            'title' => $event['title'],
            'description' => $description,
            'start_date' => $event['start_date'],
            'end_date' => $event['end_date'],
            'status' => $event['status'],
            'location' => $additionalInfo['location'] ?? ($event['location'] ?? 'Daet, Camarines Norte'),
            'event_type' => $additionalInfo['event_type'] ?? 'cultural',
            'image' => $imageUrl,
            'organizer' => $additionalInfo['organizer'] ?? 'Daet LGU',
            'contact' => $additionalInfo['contact'] ?? '',
            'website' => $additionalInfo['website'] ?? ''
        ];
    }
}

// Fetch latest blog posts
$latestPosts = [];
$postsQuery = "SELECT bp.*, p.full_name as author_name 
               FROM info_blog_posts bp 
               LEFT JOIN info_profiles p ON bp.user_id = p.id 
               WHERE bp.status = 'published' 
               ORDER BY bp.created_at DESC 
               LIMIT 3";
$postsResult = query($postsQuery);
if ($postsResult && pg_num_rows($postsResult) > 0) {
    while ($post = pg_fetch_assoc($postsResult)) {
        $post['author'] = (object)['name' => $post['author_name'] ?? 'Admin'];
        $post['published_at'] = isset($post['created_at']) ? new DateTime($post['created_at']) : new DateTime();
        $latestPosts[] = (object)$post;
    }
}

// Fetch stats
$stats = [
    'spots' => 0,
    'events' => 0,
    'visitors' => 0,
    'reviews' => 0
];

$spotsResult = query("SELECT COUNT(*) as count FROM info_attractions");
if ($spotsResult && pg_num_rows($spotsResult) > 0) {
    $row = pg_fetch_assoc($spotsResult);
    $stats['spots'] = (int)$row['count'];
}

$eventsResult = query("SELECT COUNT(*) as count FROM info_events WHERE start_date >= CURRENT_DATE AND status IN ('upcoming', 'ongoing')");
if ($eventsResult && pg_num_rows($eventsResult) > 0) {
    $row = pg_fetch_assoc($eventsResult);
    $stats['events'] = (int)$row['count'];
}

$reviewsResult = query("SELECT COUNT(*) as count FROM info_feedback");
if ($reviewsResult && pg_num_rows($reviewsResult) > 0) {
    $row = pg_fetch_assoc($reviewsResult);
    $stats['reviews'] = (int)$row['count'];
}

$stats['visitors'] = 5280; // This could also be fetched from a visitors table if available

$title = 'Daeteño - Discover Daet Tourism';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daeteño - Discover Daet Tourism</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .hover-lift { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .hover-lift:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.02); }
        .hero-gradient { background: linear-gradient(135deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.2) 100%); }
        .counter { transition: all 0.3s ease; }
        .fade-in { animation: fadeInUp 0.6s ease-out; }
        .line-clamp-1 {
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-white">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center space-x-3">
                    <a href="home.php" class="flex items-center">
                        <img src="IMAGES/logo.png" 
                             alt="Daeteño Logo" 
                             class="h-10 w-auto object-contain"
                             onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <span class="text-2xl font-bold bg-gradient-to-r from-green-600 to-yellow-500 bg-clip-text text-transparent" style="display: none;">
                            Daeteño
                        </span>
                    </a>
                    <a href="home.php" class="text-2xl font-bold bg-gradient-to-r from-green-600 to-yellow-500 bg-clip-text text-transparent">
                        Daeteño
                    </a>
                </div>

                <div class="hidden md:flex items-center space-x-6">
                    <a href="home.php" class="text-green-600 font-medium">Home</a>
                    <a href="tourist-spots.php" class="text-gray-600 hover:text-green-600">Tourist Spots</a>
                    <a href="events.php" class="text-gray-600 hover:text-green-600">Events</a>
                    <a href="blog.php" class="text-gray-600 hover:text-green-600">Blog</a>
                    <a href="contact.php" class="text-gray-600 hover:text-green-600">Contact</a>
                   <?php if(function_exists('isLoggedIn') && isLoggedIn()): ?>
                        <a href="dashboard.php" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                            Dashboard
                        </a>
                        <a href="logout.php" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                            Logout
                        </a>
                   <?php else: ?>
                        <a href="auth/login.php" class="text-gray-600 hover:text-green-600 transition">Login</a>
                   <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative overflow-hidden h-[600px]">
        <div class="absolute inset-0">
            <img src="images/bagasbasbeach.webp" alt="Daet Tourism" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-black/30"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 h-full flex items-center">
            <div class="max-w-3xl text-center mx-auto fade-in">
                <h1 class="text-5xl md:text-6xl font-bold text-white mb-6">
                    Discover the Beauty of
                    <span class="text-green-400">Daet,</span>
                    <span class="text-yellow-400">Camarines Norte</span>
                </h1>
                <p class="text-xl text-white mb-10 opacity-90">
                    Your ultimate guide to tourist spots, local events, and cultural experiences in Daet.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="tourist-spots.php" class="px-8 py-3 bg-yellow-500 text-gray-900 font-semibold rounded-lg hover:bg-yellow-400 transition shadow-lg">
                        <i class="fas fa-search-location mr-2"></i> Explore Tourist Spots
                    </a>
                    <a href="events.php" class="px-8 py-3 bg-white text-blue-600 font-semibold rounded-lg hover:bg-gray-100 transition shadow-lg">
                        <i class="fas fa-calendar-alt mr-2"></i> View Events
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Tourist Spots -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12 fade-in">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Featured Tourist Spots</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Discover Daet's most beautiful and popular destinations</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php if(!empty($featuredSpots)): ?>
                    <?php foreach($featuredSpots as $spot): ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden hover-lift border border-gray-100 fade-in">
                        <div class="relative h-64 overflow-hidden">
                            <?php 
                            $imageUrl = 'https://via.placeholder.com/400x300?text=No+Image';
                            if (!empty($spot->images)) {
                                // Handle PostgreSQL array format
                                $cleaned = trim($spot->images, '{}');
                                if (!empty($cleaned)) {
                                    $images = explode(',', $cleaned);
                                    $firstImage = trim($images[0], '"');
                                    if (!empty($firstImage)) {
                                        $imageUrl = $firstImage;
                                    }
                                }
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
                                 alt="<?php echo htmlspecialchars($spot->name); ?>" 
                                 class="w-full h-full object-cover transition-transform duration-500 hover:scale-110"
                                 onerror="this.src='https://via.placeholder.com/400x300?text=Image+Not+Found'">
                            <div class="absolute top-4 right-4 bg-yellow-500 text-white px-3 py-1 rounded-full text-xs font-semibold">
                                <i class="fas fa-chart-line mr-1"></i> Popular
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-3">
                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <i class="fas fa-tag mr-1"></i> <?php echo htmlspecialchars($spot->category ?? 'General'); ?>
                                </span>
                                <div class="flex items-center text-amber-500">
                                    <i class="fas fa-star"></i>
                                    <span class="ml-1 text-gray-700 font-medium">
                                        <?php 
                                        // Get average rating - using simple string concatenation since id is integer
                                        $ratingQuery = "SELECT COALESCE(AVG(rating), 0) as avg_rating FROM info_feedback WHERE target_type = 'attraction' AND target_id = '" . $spot->id . "'";
                                        $ratingResult = query($ratingQuery);
                                        $avgRating = 0;
                                        if ($ratingResult && pg_num_rows($ratingResult) > 0) {
                                            $ratingRow = pg_fetch_assoc($ratingResult);
                                            $avgRating = $ratingRow ? round($ratingRow['avg_rating'], 1) : 0;
                                        }
                                        echo number_format($avgRating, 1);
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($spot->name); ?></h3>
                            <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars(substr($spot->description ?? '', 0, 100)) . '...'; ?></p>
                            <div class="flex items-center text-gray-500 text-sm mb-4">
                                <i class="fas fa-eye mr-2"></i>
                                <span><?php echo number_format($spot->views_count ?? 0); ?> views</span>
                            </div>
                            <a href="spot-detail.php?id=<?php echo urlencode($spot->id); ?>" 
                               class="block w-full text-center py-2 border-2 border-blue-500 text-blue-600 font-semibold rounded-lg hover:bg-blue-50 transition">
                                View Details
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-3 text-center py-12">
                        <i class="fas fa-map-marked-alt text-5xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">No tourist spots available at the moment.</p>
                        <p class="text-gray-400 mt-2">Check back soon for amazing destinations!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if(!empty($featuredSpots)): ?>
            <div class="text-center mt-12">
                <a href="tourist-spots.php" class="inline-flex items-center text-blue-600 font-semibold hover:text-blue-700 transition">
                    View All Tourist Spots <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 bg-gradient-to-r from-blue-600 to-blue-800 text-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div class="counter" data-target="<?php echo $stats['spots']; ?>">
                    <div class="text-4xl font-bold mb-2">0</div>
                    <div class="text-blue-200">Tourist Spots</div>
                </div>
                <div class="counter" data-target="<?php echo $stats['events']; ?>">
                    <div class="text-4xl font-bold mb-2">0</div>
                    <div class="text-blue-200">Upcoming Events</div>
                </div>
                <div class="counter" data-target="<?php echo $stats['visitors']; ?>">
                    <div class="text-4xl font-bold mb-2">0</div>
                    <div class="text-blue-200">Monthly Visitors</div>
                </div>
                <div class="counter" data-target="<?php echo $stats['reviews']; ?>">
                    <div class="text-4xl font-bold mb-2">0</div>
                    <div class="text-blue-200">Happy Reviews</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Upcoming Events -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12 fade-in">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Upcoming Events</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Join exciting local events and cultural celebrations in Daet</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if(!empty($upcomingEvents)): ?>
                    <?php foreach($upcomingEvents as $event): ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden hover-lift fade-in">
                        <div class="relative h-48 overflow-hidden">
                            <img src="<?php echo htmlspecialchars($event->image); ?>" 
                                 alt="<?php echo htmlspecialchars($event->title); ?>" 
                                 class="w-full h-full object-cover transition-transform duration-500 hover:scale-110"
                                 onerror="this.src='https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&q=80'">
                            <div class="absolute top-4 left-4 bg-white/90 backdrop-blur-sm px-3 py-2 rounded-lg shadow-md">
                                <div class="text-center">
                                    <div class="text-purple-600 font-bold text-xl"><?php echo date('d', strtotime($event->start_date)); ?></div>
                                    <div class="text-gray-600 text-xs font-medium"><?php echo date('M', strtotime($event->start_date)); ?></div>
                                </div>
                            </div>
                            <?php if($event->status === 'ongoing'): ?>
                            <div class="absolute top-4 right-4 bg-green-500 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-md">
                                <i class="fas fa-play mr-1"></i> Ongoing
                            </div>
                            <?php elseif($event->status === 'upcoming'): ?>
                            <div class="absolute top-4 right-4 bg-purple-500 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-md">
                                <i class="fas fa-clock mr-1"></i> Upcoming
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-5">
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800 font-medium">
                                    <i class="fas fa-tag mr-1"></i> <?php echo ucfirst(htmlspecialchars($event->event_type ?? 'Event')); ?>
                                </span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2 line-clamp-1"><?php echo htmlspecialchars($event->title); ?></h3>
                            <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars(substr($event->description ?? '', 0, 100)) . '...'; ?></p>
                            
                            <div class="space-y-2 mb-4">
                                <div class="flex items-center text-gray-600 text-sm">
                                    <i class="fas fa-calendar-day text-purple-500 mr-3 w-5"></i>
                                    <span><?php echo date('F j, Y', strtotime($event->start_date)); ?></span>
                                    <?php if($event->end_date && date('Y-m-d', strtotime($event->start_date)) !== date('Y-m-d', strtotime($event->end_date))): ?>
                                    <span class="mx-1">-</span>
                                    <span><?php echo date('F j, Y', strtotime($event->end_date)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center text-gray-600 text-sm">
                                    <i class="fas fa-clock text-purple-500 mr-3 w-5"></i>
                                    <span><?php echo date('g:i A', strtotime($event->start_date)); ?></span>
                                    <?php if($event->end_date): ?>
                                    <span class="mx-1">-</span>
                                    <span><?php echo date('g:i A', strtotime($event->end_date)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center text-gray-600 text-sm">
                                    <i class="fas fa-map-marker-alt text-purple-500 mr-3 w-5"></i>
                                    <span class="line-clamp-1"><?php echo htmlspecialchars($event->location); ?></span>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-100">
                                <?php if($event->organizer && $event->organizer !== 'Daet LGU'): ?>
                                <span class="text-xs text-gray-500">
                                    <i class="fas fa-user-circle mr-1"></i> <?php echo htmlspecialchars($event->organizer); ?>
                                </span>
                                <?php else: ?>
                                <span></span>
                                <?php endif; ?>
                                <a href="event-detail.php?id=<?php echo urlencode($event->id); ?>" 
                                   class="px-4 py-2 bg-gradient-to-r from-purple-500 to-purple-700 text-white rounded-lg hover:shadow-lg transition text-sm font-semibold">
                                    View Details <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-3 text-center py-12">
                        <i class="fas fa-calendar-alt text-5xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 text-lg">No upcoming events at the moment.</p>
                        <p class="text-gray-400 mt-2">Check back soon for local events and festivals in Daet!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if(!empty($upcomingEvents)): ?>
            <div class="text-center mt-12">
                <a href="events.php" class="inline-flex items-center text-purple-600 font-semibold hover:text-purple-700 transition">
                    View All Events <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Blog Section -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12 fade-in">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Latest From Our Blog</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Travel tips, local guides, and Daet culture insights</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php if(!empty($latestPosts)): ?>
                    <?php foreach($latestPosts as $post): ?>
                    <article class="bg-white rounded-xl shadow-lg overflow-hidden hover-lift border border-gray-100 fade-in">
                        <div class="relative h-56 overflow-hidden">
                            <?php if(!empty($post->featured_image)): ?>
                                <img src="<?php echo htmlspecialchars($post->featured_image); ?>" 
                                     alt="<?php echo htmlspecialchars($post->title); ?>"
                                     class="w-full h-full object-cover transition-transform duration-500 hover:scale-110"
                                     onerror="this.src='https://via.placeholder.com/400x300?text=Blog+Post'">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/400x300?text=Blog+Post" 
                                     alt="<?php echo htmlspecialchars($post->title); ?>"
                                     class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">
                            <?php endif; ?>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-3">
                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <?php echo htmlspecialchars($post->category ?? 'Travel'); ?>
                                </span>
                                <span class="text-gray-500 text-sm">
                                    <?php 
                                    if(isset($post->created_at)) {
                                        $date = new DateTime($post->created_at);
                                        $now = new DateTime();
                                        $interval = $date->diff($now);
                                        if ($interval->days == 0) {
                                            echo 'Today';
                                        } elseif ($interval->days == 1) {
                                            echo 'Yesterday';
                                        } else {
                                            echo $interval->days . ' days ago';
                                        }
                                    } else {
                                        echo 'Recently';
                                    }
                                    ?>
                                </span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3 line-clamp-1"><?php echo htmlspecialchars($post->title); ?></h3>
                            <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars(substr($post->content ?? '', 0, 80)) . '...'; ?></p>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-sm font-bold">
                                        <?php echo substr($post->author->name ?? 'A', 0, 1); ?>
                                    </div>
                                    <span class="ml-2 text-sm text-gray-700"><?php echo htmlspecialchars($post->author->name ?? 'Admin'); ?></span>
                                </div>
                                <a href="blog-post.php?id=<?php echo urlencode($post->id); ?>" class="text-blue-600 hover:text-blue-700 font-semibold text-sm transition">
                                    Read More →
                                </a>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-3 text-center py-12">
                        <i class="fas fa-blog text-5xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">No blog posts yet.</p>
                        <p class="text-gray-400 mt-2">Check back soon for travel tips and stories!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-gradient-to-br from-blue-50 to-cyan-50">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold text-gray-900 mb-6">Ready to Explore Daet?</h2>
            <p class="text-gray-600 text-lg mb-8">
                Create an account to book tours, save favorite spots, get event reminders, and receive personalized recommendations.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <?php if(function_exists('isLoggedIn') && isLoggedIn()): ?>
                    <a href="tourist-spots.php" class="px-8 py-3 bg-gradient-to-r from-blue-500 to-blue-700 text-white font-semibold rounded-lg hover:shadow-xl transition hover-lift">
                        <i class="fas fa-compass mr-2"></i> Start Exploring
                    </a>
                    <a href="dashboard.php" class="px-8 py-3 bg-white text-blue-600 border-2 border-blue-200 font-semibold rounded-lg hover:bg-blue-50 transition hover-lift">
                        <i class="fas fa-tachometer-alt mr-2"></i> Go to Dashboard
                    </a>
                <?php else: ?>
                    <a href="auth/register.php" class="px-8 py-3 bg-gradient-to-r from-blue-500 to-blue-700 text-white font-semibold rounded-lg hover:shadow-xl transition hover-lift">
                        <i class="fas fa-user-plus mr-2"></i> Sign Up Free
                    </a>
                    <a href="auth/login.php" class="px-8 py-3 bg-white text-blue-600 border-2 border-blue-200 font-semibold rounded-lg hover:bg-blue-50 transition hover-lift">
                        <i class="fas fa-sign-in-alt mr-2"></i> Log In
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer class="bg-gray-800 text-white py-8 text-center">
        <p>&copy; <?php echo date('Y'); ?> Daeteño Tourist Information System. All rights reserved.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const counters = document.querySelectorAll('.counter');
            
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-target'));
                const countElement = counter.querySelector('.text-4xl');
                let current = 0;
                const increment = Math.ceil(target / 50);
                
                const updateCounter = () => {
                    if (current < target) {
                        current += increment;
                        if (current > target) current = target;
                        countElement.textContent = current.toLocaleString();
                        setTimeout(updateCounter, 30);
                    }
                };
                
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            updateCounter();
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.5 });
                
                observer.observe(counter);
            });
        });
    </script>
</body>
</html>