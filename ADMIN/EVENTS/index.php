<?php
/**
 * Daeteño Admin - Events Management (Index Page)
 */

require_once '../../dbconn.php';

// Check if user is logged in and is admin
if (!isLoggedIn()) {
    redirect('../../login.php');
}

if (!isAdmin()) {
    redirect('../../index.php');
}

$user_id = $_SESSION['user_id'];
$userName = $_SESSION['full_name'] ?? 'Administrator';

// Handle delete action
if (isset($_GET['delete']) && isset($_GET['delete'])) {
    $eventId = $_GET['delete'];
    
    // Check if event exists
    $checkResult = query("SELECT id FROM info_events WHERE id = $1", [$eventId]);
    if ($checkResult && pg_num_rows($checkResult) > 0) {
        // Delete the event
        $deleteResult = query("DELETE FROM info_events WHERE id = $1", [$eventId]);
        if ($deleteResult) {
            $_SESSION['success_message'] = 'Event has been deleted successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to delete event.';
        }
    } else {
        $_SESSION['error_message'] = 'Event not found.';
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
$errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Fetch events from database
$events = [];
$result = query("
    SELECT 
        id,
        title,
        description,
        start_date,
        end_date,
        status,
        views_count,
        created_by,
        created_at,
        updated_at
    FROM info_events 
    ORDER BY 
        CASE status 
            WHEN 'ongoing' THEN 1 
            WHEN 'upcoming' THEN 2 
            ELSE 3 
        END,
        start_date ASC
");

if ($result) {
    $eventsData = fetchAll($result);
    foreach ($eventsData as $event) {
        // Extract additional info from description
        $description = $event['description'] ?? '';
        $additionalInfo = [];
        
        // Try to extract JSON from description
        if (preg_match('/---\nAdditional Information:\n(\{.*\})/s', $description, $matches)) {
            $additionalInfo = json_decode($matches[1], true);
            // Remove the JSON part from description for display
            $description = trim(preg_replace('/---\nAdditional Information:\n\{.*\}/s', '', $description));
        }
        
        $events[] = [
            'id' => $event['id'],
            'title' => $event['title'],
            'description' => $description,
            'type' => $additionalInfo['event_type'] ?? 'cultural',
            'location' => $additionalInfo['location'] ?? 'Daet, Camarines Norte',
            'start_date' => $event['start_date'],
            'end_date' => $event['end_date'],
            'status' => $event['status'],
            'organizer' => $additionalInfo['organizer'] ?? 'Daet LGU',
            'contact' => $additionalInfo['contact'] ?? '',
            'website' => $additionalInfo['website'] ?? '',
            'image' => $additionalInfo['image'] ?? null,
            'views_count' => $event['views_count'] ?? 0
        ];
    }
}

// Calculate statistics
$totalEvents = count($events);
$upcomingEvents = count(array_filter($events, fn($e) => $e['status'] === 'upcoming'));
$ongoingEvents = count(array_filter($events, fn($e) => $e['status'] === 'ongoing'));
$pastEvents = count(array_filter($events, fn($e) => $e['status'] === 'completed'));

// Helper functions
function getStatusBadgeClass($status) {
    return match($status) {
        'upcoming' => 'bg-purple-100 text-purple-800',
        'ongoing' => 'bg-green-100 text-green-800',
        'completed' => 'bg-gray-100 text-gray-800',
        'cancelled' => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-800'
    };
}

function getTypeBadgeClass($type) {
    return match($type) {
        'festival' => 'bg-red-100 text-red-800',
        'cultural' => 'bg-blue-100 text-blue-800',
        'sports' => 'bg-orange-100 text-orange-800',
        'music' => 'bg-pink-100 text-pink-800',
        'food' => 'bg-yellow-100 text-yellow-800',
        'community' => 'bg-teal-100 text-teal-800',
        default => 'bg-gray-100 text-gray-800'
    };
}

function formatEventDate($startDate, $endDate) {
    if (!$startDate) return 'Date TBA';
    
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    
    if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
        return $start->format('M j, Y') . ' • ' . $start->format('g:i A') . ' - ' . $end->format('g:i A');
    }
    return $start->format('M j, g:i A') . ' - ' . $end->format('M j, g:i A');
}

function getStatusIcon($status) {
    return match($status) {
        'upcoming' => 'fa-clock',
        'ongoing' => 'fa-play',
        'completed' => 'fa-check-circle',
        'cancelled' => 'fa-ban',
        default => 'fa-calendar'
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daeteño Admin - Events Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Admin Header -->
    <div class="bg-gradient-to-r from-purple-600 to-purple-800 text-white sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-4">
                <div class="flex items-center space-x-4">
                    <a href="../dashboard.php" class="flex items-center text-white/80 hover:text-white transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-purple-200">Events Management</span>
                    <div class="h-6 w-px bg-white/30"></div>
                    <span class="text-sm text-purple-200">Welcome, <?php echo htmlspecialchars($userName); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <?php if ($successMessage): ?>
    <div class="fixed top-20 right-4 z-50 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span><?php echo htmlspecialchars($successMessage); ?></span>
        </div>
    </div>
    <script>setTimeout(() => { document.querySelector('.fixed.top-20.right-4')?.remove(); }, 4000);</script>
    <?php endif; ?>

    <!-- Error Toast -->
    <?php if ($errorMessage): ?>
    <div class="fixed top-20 right-4 z-50 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span><?php echo htmlspecialchars($errorMessage); ?></span>
        </div>
    </div>
    <script>setTimeout(() => { document.querySelector('.fixed.top-20.right-4')?.remove(); }, 4000);</script>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Events Management</h1>
                <p class="text-gray-600">Manage festivals, activities, and events in Daet</p>
            </div>
            <a href="create.php" 
               class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-2 shadow-sm hover:shadow">
                <i class="fas fa-calendar-plus"></i> Create Event
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Events</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $totalEvents; ?></p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                        <i class="fas fa-calendar-alt text-purple-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Upcoming</p>
                        <p class="text-2xl font-bold text-purple-600"><?php echo $upcomingEvents; ?></p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                        <i class="fas fa-clock text-purple-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Ongoing</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo $ongoingEvents; ?></p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                        <i class="fas fa-play text-green-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Past Events</p>
                        <p class="text-2xl font-bold text-gray-600"><?php echo $pastEvents; ?></p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                        <i class="fas fa-check-double text-gray-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Bar -->
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <h2 class="text-lg font-semibold text-gray-900">All Events</h2>
                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <div class="relative flex-1 sm:flex-initial">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="text" id="searchInput" placeholder="Search events..." 
                                   class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 w-full sm:w-64">
                        </div>
                        <select id="typeFilter" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500">
                            <option value="all">All Categories</option>
                            <option value="festival">Festival</option>
                            <option value="cultural">Cultural</option>
                            <option value="sports">Sports</option>
                            <option value="music">Music</option>
                            <option value="food">Food Fair</option>
                            <option value="community">Community</option>
                        </select>
                        <select id="statusFilter" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500">
                            <option value="all">All Status</option>
                            <option value="upcoming">Upcoming</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div id="eventsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (!empty($events)): ?>
                        <?php foreach ($events as $event): ?>
                        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-all duration-200"
                             data-title="<?php echo strtolower(htmlspecialchars($event['title'])); ?>"
                             data-type="<?php echo $event['type']; ?>"
                             data-status="<?php echo $event['status']; ?>">
                            
                            <?php if ($event['image'] && file_exists('../../' . $event['image'])): ?>
                            <div class="h-40 overflow-hidden">
                                <img src="../../<?php echo htmlspecialchars($event['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($event['title']); ?>"
                                     class="w-full h-full object-cover">
                            </div>
                            <?php else: ?>
                            <div class="h-40 bg-gradient-to-br from-purple-100 to-purple-200 flex items-center justify-center">
                                <i class="fas fa-calendar-alt text-5xl text-purple-400"></i>
                            </div>
                            <?php endif; ?>
                            
                            <div class="p-4">
                                <div class="flex justify-between items-start mb-3">
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo getTypeBadgeClass($event['type']); ?>">
                                        <i class="fas fa-tag mr-1"></i> <?php echo ucfirst($event['type']); ?>
                                    </span>
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo getStatusBadgeClass($event['status']); ?>">
                                        <i class="fas <?php echo getStatusIcon($event['status']); ?> mr-1"></i>
                                        <?php echo ucfirst($event['status']); ?>
                                    </span>
                                </div>
                                <h3 class="font-semibold text-gray-900 mb-2 line-clamp-1"><?php echo htmlspecialchars($event['title']); ?></h3>
                                <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?php echo htmlspecialchars(substr($event['description'], 0, 100)) . (strlen($event['description']) > 100 ? '...' : ''); ?></p>
                                <div class="space-y-2 text-sm">
                                    <div class="flex items-center text-gray-500">
                                        <i class="fas fa-calendar-alt mr-2 w-4 text-purple-500"></i>
                                        <span class="text-xs"><?php echo formatEventDate($event['start_date'], $event['end_date']); ?></span>
                                    </div>
                                    <div class="flex items-center text-gray-500">
                                        <i class="fas fa-map-marker-alt mr-2 w-4 text-purple-500"></i>
                                        <span class="text-xs"><?php echo htmlspecialchars($event['location']); ?></span>
                                    </div>
                                    <div class="flex items-center text-gray-500">
                                        <i class="fas fa-eye mr-2 w-4 text-purple-500"></i>
                                        <span class="text-xs"><?php echo number_format($event['views_count']); ?> views</span>
                                    </div>
                                </div>
                                <div class="flex justify-end items-center mt-4 pt-3 border-t border-gray-100">
                                    <div class="flex space-x-2">
                                        <a href="edit.php?id=<?php echo $event['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-700 transition-colors p-1 hover:bg-blue-50 rounded"
                                           title="Edit Event">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="confirmDelete('<?php echo $event['id']; ?>', '<?php echo htmlspecialchars(addslashes($event['title'])); ?>')" 
                                                class="text-red-600 hover:text-red-700 transition-colors p-1 hover:bg-red-50 rounded"
                                                title="Delete Event">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-span-full text-center py-12">
                            <i class="fas fa-calendar-alt text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">No events found</p>
                            <p class="text-sm text-gray-400 mt-1">Create your first event to get started</p>
                            <a href="create.php" class="inline-block mt-4 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                                <i class="fas fa-plus mr-2"></i> Create Event
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div id="noResults" class="hidden text-center py-12">
                    <i class="fas fa-search text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No matching events found</p>
                    <p class="text-sm text-gray-400">Try adjusting your search or filter criteria</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Delete Event</h3>
                <p class="text-gray-600 mb-6" id="deleteMessage">Are you sure you want to delete this event? This action cannot be undone.</p>
                <div class="flex justify-center space-x-3">
                    <button onclick="closeDeleteModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <a href="#" id="confirmDeleteBtn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Delete Event
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
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
    </style>

    <script>
        // Search and Filter Functionality
        const searchInput = document.getElementById('searchInput');
        const typeFilter = document.getElementById('typeFilter');
        const statusFilter = document.getElementById('statusFilter');
        const eventsGrid = document.getElementById('eventsGrid');
        const noResults = document.getElementById('noResults');
        
        function filterEvents() {
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            const selectedType = typeFilter ? typeFilter.value : 'all';
            const selectedStatus = statusFilter ? statusFilter.value : 'all';
            
            const eventCards = document.querySelectorAll('.bg-white.border.border-gray-200.rounded-lg');
            let visibleCount = 0;
            
            eventCards.forEach(card => {
                const title = card.getAttribute('data-title') || '';
                const type = card.getAttribute('data-type') || '';
                const status = card.getAttribute('data-status') || '';
                
                const matchesSearch = title.includes(searchTerm);
                const matchesType = selectedType === 'all' || type === selectedType;
                const matchesStatus = selectedStatus === 'all' || status === selectedStatus;
                
                if (matchesSearch && matchesType && matchesStatus) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            if (noResults && eventsGrid) {
                if (visibleCount === 0 && eventCards.length > 0) {
                    noResults.classList.remove('hidden');
                    eventsGrid.classList.add('hidden');
                } else {
                    noResults.classList.add('hidden');
                    eventsGrid.classList.remove('hidden');
                }
            }
        }
        
        if (searchInput) searchInput.addEventListener('keyup', filterEvents);
        if (typeFilter) typeFilter.addEventListener('change', filterEvents);
        if (statusFilter) statusFilter.addEventListener('change', filterEvents);

        // Delete Modal Functions
        let deleteModal = document.getElementById('deleteModal');
        let confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

        function confirmDelete(eventId, eventTitle) {
            const deleteMessage = document.getElementById('deleteMessage');
            if (deleteMessage) {
                deleteMessage.innerHTML = `Are you sure you want to delete "<strong>${escapeHtml(eventTitle)}</strong>"? This action cannot be undone.`;
            }
            if (confirmDeleteBtn) {
                confirmDeleteBtn.href = `?delete=${eventId}`;
            }
            if (deleteModal) {
                deleteModal.classList.remove('hidden');
                deleteModal.classList.add('flex');
            }
        }

        function closeDeleteModal() {
            if (deleteModal) {
                deleteModal.classList.remove('flex');
                deleteModal.classList.add('hidden');
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        if (deleteModal) {
            deleteModal.addEventListener('click', function(e) {
                if (e.target === deleteModal) {
                    closeDeleteModal();
                }
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && deleteModal && !deleteModal.classList.contains('hidden')) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>