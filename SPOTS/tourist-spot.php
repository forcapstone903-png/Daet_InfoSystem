<?php
// tourist-spots.php - Tourist Spots Listing Page
session_start();

// Database simulation (in real app, fetch from database)
$spots = [
    (object)[
        'id' => 1,
        'name' => 'Bagasbas Beach',
        'slug' => 'bagasbas-beach',
        'description' => 'World-famous surfing destination with fine gray sand and perfect waves for both beginners and advanced surfers. Home to the annual Bagasbas Surfing Cup.',
        'category' => 'Beach',
        'location' => 'Bagasbas, Daet, Camarines Norte',
        'entrance_fee' => 0,
        'rating' => 4.8,
        'reviews_count' => 156,
        'is_featured' => true,
        'image' => 'https://images.unsplash.com/photo-1519046904884-53103b34b206'
    ],
    (object)[
        'id' => 2,
        'name' => 'Daet Municipal Hall',
        'slug' => 'daet-municipal-hall',
        'description' => 'Historic municipal hall built during the American colonial period. Features beautiful neo-classical architecture and well-maintained gardens.',
        'category' => 'Government',
        'location' => 'Poblacion, Daet, Camarines Norte',
        'entrance_fee' => 0,
        'rating' => 4.5,
        'reviews_count' => 89,
        'is_featured' => true,
        'image' => 'https://images.unsplash.com/photo-1566908826300-7cb4094b6b34'
    ],
    (object)[
        'id' => 3,
        'name' => 'Museo De Daet',
        'slug' => 'museo-de-daet',
        'description' => 'Cultural museum showcasing the rich history and heritage of Daet and Camarines Norte. Features artifacts, photographs, and historical exhibits.',
        'category' => 'Museum',
        'location' => 'Justo Lukban Street, Daet',
        'entrance_fee' => 50,
        'rating' => 4.6,
        'reviews_count' => 112,
        'is_featured' => false,
        'image' => 'https://images.unsplash.com/photo-1566127992631-137a642a90f4'
    ],
    (object)[
        'id' => 4,
        'name' => 'Mabini Park',
        'slug' => 'mabini-park',
        'description' => 'Central park in Daet perfect for relaxation and family gatherings. Features a monument of Apolinario Mabini, fountains, and landscaped gardens.',
        'category' => 'Park',
        'location' => 'Poblacion, Daet',
        'entrance_fee' => 0,
        'rating' => 4.3,
        'reviews_count' => 67,
        'is_featured' => false,
        'image' => 'https://images.unsplash.com/photo-1584278860047-22db5a7d4f8f'
    ],
    (object)[
        'id' => 5,
        'name' => 'First Rizal Monument',
        'slug' => 'first-rizal-monument',
        'description' => 'Historical landmark where the first monument of Dr. Jose Rizal was built. A significant site for Philippine history enthusiasts.',
        'category' => 'Historical',
        'location' => 'Justo Lukban Street, Daet',
        'entrance_fee' => 0,
        'rating' => 4.7,
        'reviews_count' => 203,
        'is_featured' => true,
        'image' => 'https://images.unsplash.com/photo-1573883430694-35e6edb7f1d9'
    ],
    (object)[
        'id' => 6,
        'name' => 'Mercedes Beach',
        'slug' => 'mercedes-beach',
        'description' => 'Beautiful beach known for its calm waters and stunning sunset views. Popular for swimming and beach camping.',
        'category' => 'Beach',
        'location' => 'Mercedes, Daet',
        'entrance_fee' => 20,
        'rating' => 4.4,
        'reviews_count' => 78,
        'is_featured' => false,
        'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e'
    ]
];

// Get unique categories
$categories = array_unique(array_column($spots, 'category'));

// Search and filter logic
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 6;

// Filter spots
$filteredSpots = array_filter($spots, function($spot) use ($search, $category) {
    $matchesSearch = true;
    $matchesCategory = true;
    
    if (!empty($search)) {
        $searchLower = strtolower($search);
        $matchesSearch = strpos(strtolower($spot->name), $searchLower) !== false ||
                        strpos(strtolower($spot->location), $searchLower) !== false ||
                        strpos(strtolower($spot->description), $searchLower) !== false;
    }
    
    if (!empty($category)) {
        $matchesCategory = $spot->category === $category;
    }
    
    return $matchesSearch && $matchesCategory;
});

// Pagination
$totalSpots = count($filteredSpots);
$totalPages = ceil($totalSpots / $perPage);
$offset = ($page - 1) * $perPage;
$paginatedSpots = array_slice($filteredSpots, $offset, $perPage);

// Include main layout
$title = 'Tourist Spots - Daeteño';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <title><?php echo $title; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .spot-card {
            transition: all 0.3s ease;
        }
        
        .spot-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
        }
        
        .image-container {
            position: relative;
            overflow: hidden;
        }
        
        .image-container img {
            transition: transform 0.5s ease;
        }
        
        .spot-card:hover .image-container img {
            transform: scale(1.05);
        }
        
        .featured-badge {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pagination a {
            transition: all 0.2s ease;
        }
        
        .pagination a:hover {
            background-color: #e5e7eb;
            transform: translateY(-1px);
        }
        
        .category-btn {
            transition: all 0.2s ease;
        }
        
        .category-btn:hover {
            transform: translateY(-2px);
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Simple Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <a href="index.php" class="text-2xl font-bold bg-gradient-to-r from-green-600 to-yellow-500 bg-clip-text text-transparent">
                        Daeteño
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-600 hover:text-green-600">Home</a>
                    <a href="tourist-spots.php" class="text-green-600 font-medium">Tourist Spots</a>
                    <a href="events.php" class="text-gray-600 hover:text-green-600">Events</a>
                    <a href="contact.php" class="text-gray-600 hover:text-green-600">Contact</a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8 text-center md:text-left fade-in">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900">
                Tourist Spots in Daet
            </h1>
            <p class="text-gray-600 mt-2 text-lg">
                Discover beautiful destinations and attractions in Daet, Camarines Norte
            </p>
        </div>
        
        <!-- Search and Filter -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 fade-in">
            <form action="tourist-spots.php" method="GET" id="searchForm" class="space-y-4">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1 relative">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="search" id="searchInput" 
                               placeholder="Search spots by name, location, or description..."
                               value="<?php echo htmlspecialchars($search); ?>"
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <select name="category" id="categorySelect" 
                                class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                            <option value="">All Categories</option>
                            <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                <?php echo $cat; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" 
                                class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-md hover:shadow-lg">
                            <i class="fas fa-search mr-2"></i> Search
                        </button>
                        <?php if(!empty($search) || !empty($category)): ?>
                        <a href="tourist-spots.php" 
                           class="inline-block ml-2 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-times mr-1"></i> Clear
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Results Count -->
        <div class="mb-4 text-sm text-gray-500">
            <i class="fas fa-map-marker-alt mr-1"></i> 
            Found <?php echo $totalSpots; ?> <?php echo $totalSpots === 1 ? 'spot' : 'spots'; ?>
        </div>
        
        <!-- Spots Grid -->
        <?php if(count($paginatedSpots) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach($paginatedSpots as $index => $spot): ?>
            <div class="spot-card bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 fade-in" 
                 style="animation-delay: <?php echo $index * 0.1; ?>s">
                <div class="image-container relative h-56 overflow-hidden">
                    <img src="<?php echo $spot->image; ?>?auto=format&fit=crop&q=80&w=400&h=300" 
                         alt="<?php echo htmlspecialchars($spot->name); ?>" 
                         class="w-full h-full object-cover">
                    <?php if($spot->is_featured): ?>
                    <div class="featured-badge absolute top-4 right-4 bg-gradient-to-r from-yellow-500 to-amber-500 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-lg">
                        <i class="fas fa-star mr-1"></i> Featured
                    </div>
                    <?php endif; ?>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 hover:opacity-100 transition-opacity duration-300 flex items-end justify-start p-4">
                        <a href="spot-detail.php?slug=<?php echo $spot->slug; ?>" 
                           class="bg-white text-gray-900 px-4 py-2 rounded-lg font-medium hover:bg-gray-100 transition-colors">
                            <i class="fas fa-info-circle mr-2"></i> View Details
                        </a>
                    </div>
                </div>
                <div class="p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-tag mr-1 text-xs"></i> <?php echo htmlspecialchars($spot->category); ?>
                        </span>
                        <div class="flex items-center">
                            <i class="fas fa-star text-amber-400"></i>
                            <span class="ml-1 text-gray-700 font-semibold"><?php echo number_format($spot->rating, 1); ?></span>
                            <span class="mx-1 text-gray-400">•</span>
                            <span class="text-gray-500 text-sm"><?php echo $spot->reviews_count; ?> reviews</span>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2 hover:text-blue-600 transition-colors">
                        <a href="spot-detail.php?slug=<?php echo $spot->slug; ?>"><?php echo htmlspecialchars($spot->name); ?></a>
                    </h3>
                    <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                        <?php echo htmlspecialchars(substr($spot->description, 0, 100)) . (strlen($spot->description) > 100 ? '...' : ''); ?>
                    </p>
                    <div class="flex items-center text-gray-500 text-sm mb-4">
                        <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                        <span><?php echo htmlspecialchars($spot->location); ?></span>
                    </div>
                    <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                        <?php if($spot->entrance_fee > 0): ?>
                        <div>
                            <span class="text-xs text-gray-500">Entrance Fee</span>
                            <span class="text-xl font-bold text-gray-900 block">₱<?php echo number_format($spot->entrance_fee, 2); ?></span>
                        </div>
                        <?php else: ?>
                        <span class="text-green-600 font-semibold">
                            <i class="fas fa-check-circle mr-1"></i> Free Entrance
                        </span>
                        <?php endif; ?>
                        <a href="spot-detail.php?slug=<?php echo $spot->slug; ?>" 
                           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-semibold shadow-md hover:shadow-lg">
                            View Details <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <!-- No Results -->
        <div class="bg-white rounded-xl shadow-lg p-12 text-center">
            <i class="fas fa-search text-gray-300 text-6xl mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No spots found</h3>
            <p class="text-gray-500 mb-4">We couldn't find any tourist spots matching your criteria.</p>
            <a href="tourist-spots.php" class="inline-block px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-redo mr-2"></i> Clear Filters
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Pagination -->
        <?php if($totalPages > 1): ?>
        <div class="mt-8 flex justify-center">
            <div class="pagination flex space-x-2">
                <?php if($page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
                <?php endif; ?>
                
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                if ($startPage > 1) {
                    echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">1</a>';
                    if ($startPage > 2) echo '<span class="px-2 py-2 text-gray-500">...</span>';
                }
                
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                   class="px-4 py-2 border rounded-lg transition-colors <?php echo $i === $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50'; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if($endPage < $totalPages): ?>
                    <?php if($endPage < $totalPages - 1): ?>
                    <span class="px-2 py-2 text-gray-500">...</span>
                    <?php endif; ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        <?php echo $totalPages; ?>
                    </a>
                <?php endif; ?>
                
                <?php if($page < $totalPages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Categories Section -->
        <div class="mt-12 pt-8 border-t border-gray-200">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-tags text-blue-500 mr-3"></i>
                Browse by Category
            </h2>
            <div class="flex flex-wrap gap-3">
                <?php foreach($categories as $cat): ?>
                <a href="?category=<?php echo urlencode($cat); ?>" 
                   class="category-btn px-5 py-2.5 bg-gray-100 text-gray-700 rounded-full hover:bg-blue-100 hover:text-blue-700 transition-all shadow-sm">
                    <i class="fas fa-tag mr-1 text-xs"></i> <?php echo htmlspecialchars($cat); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Call to Action -->
        <div class="mt-12 bg-gradient-to-r from-green-50 to-blue-50 rounded-xl p-8 text-center">
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Plan Your Visit to Daet!</h3>
            <p class="text-gray-600 mb-4">Need help planning your itinerary? Contact our tourist information center.</p>
            <a href="contact.php" class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors shadow-md">
                <i class="fas fa-envelope mr-2"></i> Contact Us
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-16 py-8">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> Daeteño Tourist Information System. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        // Auto-submit form on category change
        const categorySelect = document.getElementById('categorySelect');
        if (categorySelect) {
            categorySelect.addEventListener('change', function() {
                document.getElementById('searchForm').submit();
            });
        }
        
        // Debounced search (optional - submits after typing stops)
        let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    document.getElementById('searchForm').submit();
                }, 500);
            });
        }
        
        // Add loading state to pagination links
        document.querySelectorAll('.pagination a').forEach(link => {
            link.addEventListener('click', function(e) {
                const spinner = document.createElement('div');
                spinner.className = 'fixed inset-0 bg-black/20 flex items-center justify-center z-50';
                spinner.innerHTML = '<div class="bg-white rounded-lg p-4 shadow-xl"><i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i><p class="mt-2 text-gray-600">Loading...</p></div>';
                document.body.appendChild(spinner);
            });
        });
        
        // Add image lazy loading
        const images = document.querySelectorAll('.image-container img');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.style.opacity = '0';
                    img.style.transition = 'opacity 0.3s';
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                    }
                    img.onload = () => {
                        img.style.opacity = '1';
                    };
                    observer.unobserve(img);
                }
            });
        });
        
        images.forEach(img => {
            if (img.src && !img.complete) {
                img.dataset.src = img.src;
                img.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300"%3E%3Crect width="400" height="300" fill="%23f3f4f6"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" fill="%239ca3af"%3ELoading...%3C/text%3E%3C/svg%3E';
                imageObserver.observe(img);
            }
        });
        
        // Smooth scroll to top when clicking pagination
        document.querySelectorAll('.pagination a').forEach(link => {
            link.addEventListener('click', function(e) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
        
        // Animate cards on scroll
        const cards = document.querySelectorAll('.spot-card');
        const cardObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    cardObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        cards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.5s ease';
            cardObserver.observe(card);
        });
    </script>
</body>
</html>