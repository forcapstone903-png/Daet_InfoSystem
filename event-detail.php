<?php
// event-detail.php - Event Detail Page
require_once 'dbconn.php';

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$eventId) {
    redirect('home.php');
}

// Increment view count
query("UPDATE info_events SET views_count = COALESCE(views_count, 0) + 1 WHERE id = $1", [$eventId]);

// Fetch event details
$event = null;
$result = query("SELECT * FROM info_events WHERE id = $1", [$eventId]);
if ($result && pg_num_rows($result) > 0) {
    $eventData = fetchOne($result);
    
    // Parse additional info from description
    $description = $eventData['description'] ?? '';
    $additionalInfo = [];
    
    if (preg_match('/---\nAdditional Information:\n(\{.*\})/s', $description, $matches)) {
        $additionalInfo = json_decode($matches[1], true);
        $description = trim(preg_replace('/---\nAdditional Information:\n\{.*\}/s', '', $description));
    }
    
    $event = (object)[
        'id' => $eventData['id'],
        'title' => $eventData['title'],
        'description' => $description,
        'start_date' => $eventData['start_date'],
        'end_date' => $eventData['end_date'],
        'status' => $eventData['status'],
        'views_count' => $eventData['views_count'] ?? 0,
        'location' => $additionalInfo['location'] ?? 'Daet, Camarines Norte',
        'event_type' => $additionalInfo['event_type'] ?? 'cultural',
        'image' => $additionalInfo['image'] ?? null,
        'organizer' => $additionalInfo['organizer'] ?? 'Daet LGU',
        'contact' => $additionalInfo['contact'] ?? '',
        'website' => $additionalInfo['website'] ?? ''
    ];
}

if (!$event) {
    redirect('home.php');
}

$title = $event->title . ' - Daeteño Events';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 font-['Inter']">
    <!-- Navigation (same as home.php) -->
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <!-- Copy navigation from home.php -->
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <a href="home.php" class="inline-flex items-center text-purple-600 hover:text-purple-700 mb-6">
            <i class="fas fa-arrow-left mr-2"></i> Back to Home
        </a>
        
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <?php if($event->image && file_exists($event->image)): ?>
            <div class="h-64 md:h-80 overflow-hidden">
                <img src="<?php echo htmlspecialchars($event->image); ?>" 
                     alt="<?php echo htmlspecialchars($event->title); ?>"
                     class="w-full h-full object-cover">
            </div>
            <?php else: ?>
            <div class="h-64 md:h-80 bg-gradient-to-r from-purple-400 to-purple-600 flex items-center justify-center">
                <i class="fas fa-calendar-alt text-6xl text-white/50"></i>
            </div>
            <?php endif; ?>
            
            <div class="p-6 md:p-8">
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="px-3 py-1 text-sm rounded-full bg-purple-100 text-purple-800">
                        <i class="fas fa-tag mr-1"></i> <?php echo ucfirst($event->event_type); ?>
                    </span>
                    <span class="px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-800">
                        <i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($event->location); ?>
                    </span>
                    <?php if($event->status === 'ongoing'): ?>
                    <span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-800">
                        <i class="fas fa-play mr-1"></i> Ongoing
                    </span>
                    <?php elseif($event->status === 'upcoming'): ?>
                    <span class="px-3 py-1 text-sm rounded-full bg-purple-100 text-purple-800">
                        <i class="fas fa-clock mr-1"></i> Upcoming
                    </span>
                    <?php endif; ?>
                </div>
                
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($event->title); ?></h1>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-calendar-day text-purple-500 text-xl mr-3"></i>
                        <div>
                            <p class="text-sm text-gray-500">Date</p>
                            <p class="font-medium"><?php echo date('F j, Y', strtotime($event->start_date)); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-clock text-purple-500 text-xl mr-3"></i>
                        <div>
                            <p class="text-sm text-gray-500">Time</p>
                            <p class="font-medium">
                                <?php echo date('g:i A', strtotime($event->start_date)); ?>
                                <?php if($event->end_date): ?>
                                - <?php echo date('g:i A', strtotime($event->end_date)); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-map-marker-alt text-purple-500 text-xl mr-3"></i>
                        <div>
                            <p class="text-sm text-gray-500">Venue</p>
                            <p class="font-medium"><?php echo htmlspecialchars($event->location); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-eye text-purple-500 text-xl mr-3"></i>
                        <div>
                            <p class="text-sm text-gray-500">Views</p>
                            <p class="font-medium"><?php echo number_format($event->views_count); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="prose max-w-none mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">About this Event</h2>
                    <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($event->description)); ?></p>
                </div>
                
                <?php if($event->organizer && $event->organizer !== 'Daet LGU'): ?>
                <div class="mb-6 p-4 bg-purple-50 rounded-lg">
                    <h3 class="font-semibold text-gray-900 mb-2">Organizer</h3>
                    <p class="text-gray-700"><?php echo htmlspecialchars($event->organizer); ?></p>
                    <?php if($event->contact): ?>
                    <p class="text-sm text-gray-500 mt-1">
                        <i class="fas fa-phone mr-1"></i> <?php echo htmlspecialchars($event->contact); ?>
                    </p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="flex flex-wrap gap-4">
                    <?php if($event->website): ?>
                    <a href="<?php echo htmlspecialchars($event->website); ?>" target="_blank" 
                       class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition font-semibold">
                        <i class="fas fa-external-link-alt mr-2"></i> Register / Learn More
                    </a>
                    <?php endif; ?>
                    <a href="home.php" class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition font-semibold">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="bg-gray-800 text-white py-8 text-center mt-8">
        <p>&copy; <?php echo date('Y'); ?> Daeteño Tourist Information System. All rights reserved.</p>
    </footer>
</body>
</html>