<?php
// events.php - Events Listing Page
// Simulated data structure for events (in real app, fetch from database)
$events = [
    (object)[
        'title' => 'Daet Town Fiesta 2025',
        'description' => 'Annual celebration featuring parades, street dancing, and cultural shows highlighting the rich heritage of Daet.',
        'start_date' => '2025-04-15',
        'venue' => 'Daet Plaza Rizal',
        'capacity' => 500,
        'registered_count' => 312,
        'slug' => 'daet-town-fiesta-2025'
    ],
    (object)[
        'title' => 'Art Exhibit: Bicolano Masters',
        'description' => 'Showcase of local artists featuring traditional and contemporary Bicolano art pieces.',
        'start_date' => '2025-04-20',
        'venue' => 'Daet Museum',
        'capacity' => 200,
        'registered_count' => 145,
        'slug' => 'art-exhibit-bicolano-masters'
    ],
    (object)[
        'title' => 'Coastal Cleanup Drive',
        'description' => 'Join volunteers in cleaning the shores of Daet to promote environmental awareness.',
        'start_date' => '2025-04-25',
        'venue' => 'Bagasbas Beach',
        'capacity' => 300,
        'registered_count' => 278,
        'slug' => 'coastal-cleanup-drive'
    ]
];

$pastEvents = [
    (object)[
        'title' => 'Sinag Street Dance Competition',
        'description' => 'Vibrant street dance competition showcasing local talents.',
        'start_date' => '2025-02-10',
        'venue' => 'Daet Sports Complex',
        'slug' => 'sinag-street-dance'
    ],
    (object)[
        'title' => 'Farmers Trade Fair',
        'description' => 'Local farmers showcased their organic produce and products.',
        'start_date' => '2025-01-25',
        'venue' => 'Daet Agro-Expo Center',
        'slug' => 'farmers-trade-fair'
    ],
    (object)[
        'title' => 'Summer Music Festival',
        'description' => 'Outdoor concert featuring local bands and artists.',
        'start_date' => '2025-03-05',
        'venue' => 'Bagasbas Amphitheater',
        'slug' => 'summer-music-festival'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Daeteño</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .hover-shadow-transition {
            transition: all 0.3s ease;
        }
        .hover-shadow-transition:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
        }
        .date-badge {
            backdrop-filter: blur(8px);
            transition: transform 0.2s ease;
        }
        .date-badge:hover {
            transform: scale(1.05);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .event-card {
            animation: fadeIn 0.5s ease-out forwards;
        }
    </style>
</head>
<body class="bg-gray-50">

<!-- Navigation / Header Bar (simulating Laravel layout) -->
<nav class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <a href="#" class="text-2xl font-bold text-blue-600">Daeteño</a>
                <div class="hidden md:flex ml-10 space-x-8">
                    <a href="#" class="text-gray-900 hover:text-blue-600 px-3 py-2 text-sm font-medium">Home</a>
                    <a href="#" class="text-blue-600 border-b-2 border-blue-600 px-3 py-2 text-sm font-medium">Events</a>
                    <a href="#" class="text-gray-500 hover:text-blue-600 px-3 py-2 text-sm font-medium">Calendar</a>
                    <a href="#" class="text-gray-500 hover:text-blue-600 px-3 py-2 text-sm font-medium">About</a>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="#" class="text-gray-600 hover:text-blue-600">
                    <i class="fas fa-search"></i>
                </a>
                <a href="#" class="text-gray-600 hover:text-blue-600">
                    <i class="far fa-user"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8 text-center md:text-left">
        <h1 class="text-3xl font-bold text-gray-900">Events in Daet</h1>
        <p class="text-gray-600 mt-2">Join exciting local events, festivals, and cultural celebrations</p>
    </div>
    
    <!-- Upcoming Events -->
    <div class="mb-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 border-l-4 border-blue-600 pl-4">Upcoming Events</h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php foreach($events as $event): ?>
            <div class="event-card bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300">
                <div class="md:flex">
                    <div class="md:w-2/5 relative">
                        <img src="https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&q=80" 
                             alt="<?php echo htmlspecialchars($event->title); ?>" class="w-full h-48 md:h-full object-cover">
                        <div class="absolute top-4 left-4 bg-white/90 backdrop-blur-sm px-3 py-2 rounded-lg shadow-md date-badge">
                            <div class="text-center">
                                <div class="text-blue-600 font-bold text-lg"><?php echo date('d', strtotime($event->start_date)); ?></div>
                                <div class="text-gray-600 text-xs"><?php echo date('M', strtotime($event->start_date)); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="md:w-3/5 p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2 hover:text-blue-600 transition-colors"><?php echo htmlspecialchars($event->title); ?></h3>
                        <p class="text-gray-600 mb-4 text-sm"><?php echo htmlspecialchars(substr($event->description, 0, 100)) . (strlen($event->description) > 100 ? '...' : ''); ?></p>
                        
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-gray-600 text-sm">
                                <i class="fas fa-calendar-day text-blue-500 mr-3 w-5"></i>
                                <span><?php echo date('F j, Y', strtotime($event->start_date)); ?></span>
                            </div>
                            <div class="flex items-center text-gray-600 text-sm">
                                <i class="fas fa-map-marker-alt text-blue-500 mr-3 w-5"></i>
                                <span><?php echo htmlspecialchars($event->venue); ?></span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <?php if(isset($event->capacity) && $event->capacity): ?>
                                <div class="text-sm text-gray-500">
                                    <i class="fas fa-users mr-1"></i> <?php echo $event->registered_count; ?>/<?php echo $event->capacity; ?> registered
                                </div>
                                <?php endif; ?>
                            </div>
                            <a href="event-detail.php?slug=<?php echo urlencode($event->slug); ?>" 
                               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-semibold shadow-md hover:shadow-lg">
                                View Details <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Calendar Link / CTA Section -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 mb-12 shadow-md">
        <div class="flex flex-col md:flex-row items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-gray-900 mb-2"><i class="fas fa-calendar-alt text-blue-600 mr-2"></i> View Events Calendar</h3>
                <p class="text-gray-600">See all events in an interactive calendar view</p>
            </div>
            <a href="calendar.php" 
               class="mt-4 md:mt-0 px-6 py-3 bg-white text-blue-600 border-2 border-blue-200 font-semibold rounded-lg hover:bg-blue-100 transition-all duration-300 shadow-sm hover:shadow-md">
                <i class="fas fa-calendar-alt mr-2"></i> Open Calendar
            </a>
        </div>
    </div>
    
    <!-- Past Events -->
    <?php if(count($pastEvents) > 0): ?>
    <div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6 border-l-4 border-gray-400 pl-4">Past Events</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach($pastEvents as $event): ?>
            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="text-xs text-gray-400 mb-1">Past Event</div>
                    <h3 class="font-bold text-gray-900 mb-2 text-lg"><?php echo htmlspecialchars($event->title); ?></h3>
                    <div class="flex items-center text-gray-600 text-sm mb-3">
                        <i class="fas fa-calendar-day text-gray-400 mr-2"></i>
                        <span><?php echo date('M j, Y', strtotime($event->start_date)); ?></span>
                    </div>
                    <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?php echo htmlspecialchars(substr($event->description, 0, 80)) . (strlen($event->description) > 80 ? '...' : ''); ?></p>
                    <a href="event-detail.php?slug=<?php echo urlencode($event->slug); ?>" 
                       class="text-blue-600 hover:text-blue-700 text-sm font-medium inline-flex items-center group">
                        View Details <i class="fas fa-arrow-right ml-1 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Load More / Pagination Placeholder (optional) -->
    <div class="mt-12 text-center">
        <button id="loadMoreBtn" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
            <i class="fas fa-sync-alt mr-2"></i> Load More Events
        </button>
    </div>
</div>

<!-- Footer -->
<footer class="bg-gray-800 text-white mt-16 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <p>&copy; 2025 Daeteño. All rights reserved. Discover the heart of Daet.</p>
        <div class="flex justify-center space-x-6 mt-4">
            <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-twitter"></i></a>
            <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-instagram"></i></a>
        </div>
    </div>
</footer>

<!-- JavaScript for interactive enhancements -->
<script>
    // Simple interactive JavaScript for demo
    document.addEventListener('DOMContentLoaded', function() {
        // Add hover effect on event cards dynamically (already handled by CSS)
        console.log("Events page loaded - Daeteño");
        
        // Load More button simulation
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        if(loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function(e) {
                e.preventDefault();
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Loading...';
                setTimeout(() => {
                    alert("In a full application, this would load more events via AJAX.");
                    this.innerHTML = '<i class="fas fa-sync-alt mr-2"></i> Load More Events';
                }, 1500);
            });
        }
        
        // Add a dynamic greeting based on time of day (just for fun)
        const header = document.querySelector('h1');
        if(header) {
            const hour = new Date().getHours();
            let greeting = "";
            if(hour < 12) greeting = "Good morning! ☀️ ";
            else if(hour < 18) greeting = "Good afternoon! 🌤️ ";
            else greeting = "Good evening! 🌙 ";
            // Prepend greeting to the title text without overwriting
            const originalText = header.innerText;
            if(!originalText.includes(greeting)) {
                // Only add once
                // header.innerText = greeting + originalText; // optional, but might be intrusive
            }
        }
        
        // Add tooltip style for date badges
        const badges = document.querySelectorAll('.date-badge');
        badges.forEach(badge => {
            badge.setAttribute('title', 'Event start date');
        });
        
        // Simulate registration count update (optional visual)
        const regSpans = document.querySelectorAll('.text-gray-500 .fa-users');
        if(regSpans.length > 0) {
            // Just for demo - no actual backend
            console.log(`Found ${regSpans.length} events with registration info`);
        }
        
        // Add a simple search filter demo (client-side)
        const searchIcon = document.querySelector('.fa-search')?.parentElement;
        if(searchIcon) {
            searchIcon.addEventListener('click', function(e) {
                e.preventDefault();
                const searchTerm = prompt("Search events by title or venue:");
                if(searchTerm && searchTerm.trim() !== "") {
                    const eventCards = document.querySelectorAll('.event-card');
                    let found = 0;
                    eventCards.forEach(card => {
                        const title = card.querySelector('h3')?.innerText.toLowerCase() || "";
                        const venue = card.querySelector('.fa-map-marker-alt')?.nextElementSibling?.innerText.toLowerCase() || "";
                        if(title.includes(searchTerm.toLowerCase()) || venue.includes(searchTerm.toLowerCase())) {
                            card.style.display = "";
                            found++;
                            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        } else {
                            card.style.display = "none";
                        }
                    });
                    if(found === 0) {
                        alert("No events found matching: " + searchTerm);
                        // Reset display
                        eventCards.forEach(card => card.style.display = "");
                    } else {
                        alert(`Found ${found} event(s) matching "${searchTerm}"`);
                    }
                }
            });
        }
    });
    
    // Optional: Add a sticky header effect on scroll
    window.addEventListener('scroll', function() {
        const nav = document.querySelector('nav');
        if(window.scrollY > 50) {
            nav.classList.add('shadow-md');
            nav.style.transition = 'all 0.3s';
        } else {
            nav.classList.remove('shadow-md');
        }
    });
    
    // Calendar link - additional console info
    const calendarLink = document.querySelector('a[href="calendar.php"]');
    if(calendarLink) {
        calendarLink.addEventListener('click', function(e) {
            console.log("Redirecting to interactive calendar view (simulated)");
            // In real app, prevent default if needed, but here we let it redirect to calendar.php
        });
    }
</script>
</body>
</html>