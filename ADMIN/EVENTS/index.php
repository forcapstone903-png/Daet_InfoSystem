<?php
/**
 * Daeteño Admin - Events Management (Index Page)
 */

require_once '../../dbconn.php';

if (!isLoggedIn()) redirect('../../login.php');
if (!isAdmin()) redirect('../../index.php');

$user_id = $_SESSION['user_id'];
$userName = $_SESSION['full_name'] ?? 'Administrator';

if (isset($_GET['delete']) && isset($_GET['delete'])) {
    $eventId = $_GET['delete'];
    $checkResult = query("SELECT id FROM info_events WHERE id = $1", [$eventId]);
    if ($checkResult && pg_num_rows($checkResult) > 0) {
        $deleteResult = query("DELETE FROM info_events WHERE id = $1", [$eventId]);
        $_SESSION['success_message'] = $deleteResult ? 'Event has been deleted successfully!' : 'Failed to delete event.';
    } else $_SESSION['error_message'] = 'Event not found.';
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
$errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

$events = [];
$result = query("SELECT id, title, description, start_date, end_date, status, views_count, created_by, created_at, updated_at FROM info_events ORDER BY CASE status WHEN 'ongoing' THEN 1 WHEN 'upcoming' THEN 2 ELSE 3 END, start_date ASC");
if ($result) {
    $eventsData = fetchAll($result);
    foreach ($eventsData as $event) {
        $description = $event['description'] ?? '';
        $additionalInfo = [];
        if (preg_match('/---\nAdditional Information:\n(\{.*\})/s', $description, $matches)) {
            $additionalInfo = json_decode($matches[1], true);
            $description = trim(preg_replace('/---\nAdditional Information:\n\{.*\}/s', '', $description));
        }
        $events[] = [
            'id' => $event['id'], 'title' => $event['title'], 'description' => $description,
            'type' => $additionalInfo['event_type'] ?? 'cultural', 'location' => $additionalInfo['location'] ?? 'Daet, Camarines Norte',
            'start_date' => $event['start_date'], 'end_date' => $event['end_date'], 'status' => $event['status'],
            'organizer' => $additionalInfo['organizer'] ?? 'Daet LGU', 'contact' => $additionalInfo['contact'] ?? '',
            'website' => $additionalInfo['website'] ?? '', 'image' => $additionalInfo['image'] ?? null,
            'views_count' => $event['views_count'] ?? 0
        ];
    }
}

$totalEvents = count($events);
$upcomingEvents = count(array_filter($events, fn($e) => $e['status'] === 'upcoming'));
$ongoingEvents = count(array_filter($events, fn($e) => $e['status'] === 'ongoing'));
$pastEvents = count(array_filter($events, fn($e) => $e['status'] === 'completed'));

function getStatusBadgeClass($status) {
    return match($status) {
        'upcoming' => 'bg-purple-100 text-purple-700',
        'ongoing' => 'bg-emerald-100 text-emerald-700',
        'completed' => 'bg-slate-100 text-slate-600',
        'cancelled' => 'bg-red-100 text-red-700',
        default => 'bg-slate-100 text-slate-600'
    };
}
function getTypeBadgeClass($type) {
    return match($type) {
        'festival' => 'bg-red-100 text-red-700',
        'cultural' => 'bg-blue-100 text-blue-700',
        'sports' => 'bg-orange-100 text-orange-700',
        'music' => 'bg-pink-100 text-pink-700',
        'food' => 'bg-yellow-100 text-yellow-700',
        'community' => 'bg-teal-100 text-teal-700',
        default => 'bg-slate-100 text-slate-600'
    };
}
function formatEventDate($startDate, $endDate) {
    if (!$startDate) return 'Date TBA';
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    if ($start->format('Y-m-d') === $end->format('Y-m-d')) return $start->format('M j, Y') . ' • ' . $start->format('g:i A') . ' - ' . $end->format('g:i A');
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .glass-header { background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); }
        .event-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .event-card:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -12px rgba(0,0,0,0.15); }
        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-2px); }
        .toast { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-purple-50/30">
    <!-- Glass Header -->
    <div class="glass-header text-white sticky top-0 z-50 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-4">
                <div class="flex items-center space-x-4">
                    <a href="../dashboard.php" class="flex items-center text-white/80 hover:text-white transition-all duration-200 hover:scale-105">
                        <i class="fas fa-arrow-left mr-2"></i><span class="text-sm font-medium">Back to Dashboard</span>
                    </a>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="bg-white/20 backdrop-blur-sm px-3 py-1.5 rounded-full flex items-center gap-2">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="text-sm font-medium">Events Management</span>
                    </div>
                    <div class="h-6 w-px bg-white/30"></div>
                    <div class="flex items-center gap-2"><i class="fas fa-user-circle text-lg"></i><span class="text-sm font-medium"><?php echo htmlspecialchars($userName); ?></span></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($successMessage): ?>
    <div class="fixed top-20 right-4 z-50 bg-emerald-500 text-white px-5 py-3 rounded-xl shadow-xl toast flex items-center gap-2"><i class="fas fa-check-circle"></i><span><?php echo htmlspecialchars($successMessage); ?></span></div>
    <script>setTimeout(() => { document.querySelector('.fixed.top-20.right-4')?.remove(); }, 4000);</script>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
    <div class="fixed top-20 right-4 z-50 bg-red-500 text-white px-5 py-3 rounded-xl shadow-xl toast flex items-center gap-2"><i class="fas fa-exclamation-circle"></i><span><?php echo htmlspecialchars($errorMessage); ?></span></div>
    <script>setTimeout(() => { document.querySelector('.fixed.top-20.right-4')?.remove(); }, 4000);</script>
    <?php endif; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div><h1 class="text-3xl font-bold text-slate-800 tracking-tight">Events Management</h1><p class="text-slate-500 mt-2">Manage festivals, activities, and events in Daet</p></div>
            <a href="create.php" class="px-5 py-2.5 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-xl hover:from-purple-700 hover:to-purple-800 transition-all shadow-md flex items-center gap-2 font-medium"><i class="fas fa-calendar-plus"></i> Create Event</a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-5 mb-8">
            <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <div class="flex items-center justify-between"><div><p class="text-slate-500 text-sm font-medium">Total Events</p><p class="text-2xl font-bold text-slate-800"><?php echo $totalEvents; ?></p></div><div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center"><i class="fas fa-calendar-alt text-purple-600"></i></div></div>
            </div>
            <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <div class="flex items-center justify-between"><div><p class="text-slate-500 text-sm font-medium">Upcoming</p><p class="text-2xl font-bold text-purple-600"><?php echo $upcomingEvents; ?></p></div><div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center"><i class="fas fa-clock text-purple-600"></i></div></div>
            </div>
            <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <div class="flex items-center justify-between"><div><p class="text-slate-500 text-sm font-medium">Ongoing</p><p class="text-2xl font-bold text-emerald-600"><?php echo $ongoingEvents; ?></p></div><div class="h-10 w-10 rounded-full bg-emerald-100 flex items-center justify-center"><i class="fas fa-play text-emerald-600"></i></div></div>
            </div>
            <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <div class="flex items-center justify-between"><div><p class="text-slate-500 text-sm font-medium">Past Events</p><p class="text-2xl font-bold text-slate-600"><?php echo $pastEvents; ?></p></div><div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center"><i class="fas fa-check-double text-slate-600"></i></div></div>
            </div>
        </div>

        <!-- Search and Filter Bar -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mb-8">
            <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <h2 class="text-lg font-semibold text-slate-800">All Events</h2>
                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <div class="relative flex-1 sm:flex-initial"><i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-sm"></i><input type="text" id="searchInput" placeholder="Search events..." class="pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 w-full sm:w-64"></div>
                        <select id="typeFilter" class="border border-slate-200 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-purple-500 bg-white"><option value="all">All Categories</option><option value="festival">Festival</option><option value="cultural">Cultural</option><option value="sports">Sports</option><option value="music">Music</option><option value="food">Food Fair</option><option value="community">Community</option></select>
                        <select id="statusFilter" class="border border-slate-200 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-purple-500 bg-white"><option value="all">All Status</option><option value="upcoming">Upcoming</option><option value="ongoing">Ongoing</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div id="eventsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (!empty($events)): foreach ($events as $event): ?>
                    <div class="event-card bg-white border border-slate-200 rounded-2xl overflow-hidden hover:shadow-xl transition-all duration-300" data-title="<?php echo strtolower(htmlspecialchars($event['title'])); ?>" data-type="<?php echo $event['type']; ?>" data-status="<?php echo $event['status']; ?>">
                        <?php if ($event['image'] && file_exists('../../' . $event['image'])): ?>
                        <div class="h-44 overflow-hidden"><img src="../../<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"></div>
                        <?php else: ?>
                        <div class="h-44 bg-gradient-to-br from-purple-100 to-purple-200 flex items-center justify-center"><i class="fas fa-calendar-alt text-5xl text-purple-400"></i></div>
                        <?php endif; ?>
                        <div class="p-5">
                            <div class="flex justify-between items-start mb-3"><span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php echo getTypeBadgeClass($event['type']); ?>"><i class="fas fa-tag mr-1"></i> <?php echo ucfirst($event['type']); ?></span><span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadgeClass($event['status']); ?>"><i class="fas <?php echo getStatusIcon($event['status']); ?> mr-1"></i><?php echo ucfirst($event['status']); ?></span></div>
                            <h3 class="font-bold text-slate-800 mb-2 line-clamp-1"><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p class="text-sm text-slate-500 mb-3 line-clamp-2"><?php echo htmlspecialchars(substr($event['description'], 0, 100)) . (strlen($event['description']) > 100 ? '...' : ''); ?></p>
                            <div class="space-y-2 text-sm"><div class="flex items-center text-slate-500"><i class="fas fa-calendar-alt mr-2 w-4 text-purple-500"></i><span class="text-xs"><?php echo formatEventDate($event['start_date'], $event['end_date']); ?></span></div><div class="flex items-center text-slate-500"><i class="fas fa-map-marker-alt mr-2 w-4 text-purple-500"></i><span class="text-xs"><?php echo htmlspecialchars($event['location']); ?></span></div><div class="flex items-center text-slate-500"><i class="fas fa-eye mr-2 w-4 text-purple-500"></i><span class="text-xs"><?php echo number_format($event['views_count']); ?> views</span></div></div>
                            <div class="flex justify-end items-center mt-4 pt-3 border-t border-slate-100"><div class="flex gap-3"><a href="edit.php?id=<?php echo $event['id']; ?>" class="text-blue-500 hover:text-blue-700 transition-all p-1.5 hover:bg-blue-50 rounded-lg" title="Edit Event"><i class="fas fa-edit"></i></a><button onclick="confirmDelete('<?php echo $event['id']; ?>', '<?php echo htmlspecialchars(addslashes($event['title'])); ?>')" class="text-red-500 hover:text-red-700 transition-all p-1.5 hover:bg-red-50 rounded-lg" title="Delete Event"><i class="fas fa-trash"></i></button></div></div>
                        </div>
                    </div>
                    <?php endforeach; else: ?>
                    <div class="col-span-full text-center py-16"><i class="fas fa-calendar-alt text-5xl text-slate-300 mb-4"></i><p class="text-slate-500 font-medium">No events found</p><p class="text-sm text-slate-400 mt-1">Create your first event to get started</p><a href="create.php" class="inline-block mt-4 px-5 py-2.5 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-all"><i class="fas fa-plus mr-2"></i> Create Event</a></div>
                    <?php endif; ?>
                </div>
                <div id="noResults" class="hidden text-center py-16"><i class="fas fa-search text-5xl text-slate-300 mb-4"></i><p class="text-slate-500 font-medium">No matching events found</p><p class="text-sm text-slate-400">Try adjusting your search or filter criteria</p></div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl fade-in"><div class="text-center"><div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4"><i class="fas fa-exclamation-triangle text-red-600 text-xl"></i></div><h3 class="text-lg font-bold text-slate-800 mb-2">Delete Event</h3><p class="text-slate-500 mb-6" id="deleteMessage">Are you sure you want to delete this event? This action cannot be undone.</p><div class="flex justify-center gap-3"><button onclick="closeDeleteModal()" class="px-5 py-2.5 border border-slate-300 rounded-xl hover:bg-slate-50 transition-all font-medium">Cancel</button><a href="#" id="confirmDeleteBtn" class="px-5 py-2.5 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-all font-medium">Delete Event</a></div></div></div>
    </div>

    <script>
        const searchInput = document.getElementById('searchInput');
        const typeFilter = document.getElementById('typeFilter');
        const statusFilter = document.getElementById('statusFilter');
        const eventsGrid = document.getElementById('eventsGrid');
        const noResults = document.getElementById('noResults');
        
        function filterEvents() {
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            const selectedType = typeFilter ? typeFilter.value : 'all';
            const selectedStatus = statusFilter ? statusFilter.value : 'all';
            const eventCards = document.querySelectorAll('.event-card');
            let visibleCount = 0;
            eventCards.forEach(card => { const title = card.getAttribute('data-title') || ''; const type = card.getAttribute('data-type') || ''; const status = card.getAttribute('data-status') || ''; const matchesSearch = title.includes(searchTerm); const matchesType = selectedType === 'all' || type === selectedType; const matchesStatus = selectedStatus === 'all' || status === selectedStatus; if (matchesSearch && matchesType && matchesStatus) { card.style.display = ''; visibleCount++; } else { card.style.display = 'none'; } });
            if (noResults && eventsGrid) { if (visibleCount === 0 && eventCards.length > 0) { noResults.classList.remove('hidden'); eventsGrid.classList.add('hidden'); } else { noResults.classList.add('hidden'); eventsGrid.classList.remove('hidden'); } }
        }
        
        if (searchInput) searchInput.addEventListener('keyup', filterEvents);
        if (typeFilter) typeFilter.addEventListener('change', filterEvents);
        if (statusFilter) statusFilter.addEventListener('change', filterEvents);

        let deleteModal = document.getElementById('deleteModal');
        let confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

        function confirmDelete(eventId, eventTitle) { const deleteMessage = document.getElementById('deleteMessage'); if (deleteMessage) deleteMessage.innerHTML = `Are you sure you want to delete "<strong>${escapeHtml(eventTitle)}</strong>"? This action cannot be undone.`; if (confirmDeleteBtn) confirmDeleteBtn.href = `?delete=${eventId}`; if (deleteModal) { deleteModal.classList.remove('hidden'); deleteModal.classList.add('flex'); } }
        function closeDeleteModal() { if (deleteModal) { deleteModal.classList.remove('flex'); deleteModal.classList.add('hidden'); } }
        function escapeHtml(text) { const div = document.createElement('div'); div.textContent = text; return div.innerHTML; }
        if (deleteModal) deleteModal.addEventListener('click', function(e) { if (e.target === deleteModal) closeDeleteModal(); });
        document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && deleteModal && !deleteModal.classList.contains('hidden')) closeDeleteModal(); });
    </script>
</body>
</html>