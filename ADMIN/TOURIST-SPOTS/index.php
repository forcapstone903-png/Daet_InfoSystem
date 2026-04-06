<?php
// index.php - Tourist Spots Management Page
require_once '../../dbconn.php';

if (!isLoggedIn()) {
    redirect('../../login.php');
}

if (!isAdmin()) {
    redirect('../../index.php');
}

$stats = [
    'active_spots' => 0,
    'featured_spots' => 0,
    'total_views' => 0
];

$result = query("SELECT COUNT(*) as count FROM info_attractions");
$row = fetchOne($result);
$stats['active_spots'] = $row['count'] ?? 0;

// Fixed: Featured spots count with proper casting
$featuredQuery = "
    SELECT COUNT(DISTINCT a.id) as count 
    FROM info_attractions a
    LEFT JOIN info_feedback f ON f.target_type = 'attraction' AND CAST(f.target_id AS INTEGER) = a.id
    GROUP BY a.id
    HAVING COALESCE(AVG(f.rating), 0) >= 4.5
";
$featuredResult = query($featuredQuery);
if ($featuredResult) {
    $stats['featured_spots'] = pg_num_rows($featuredResult);
}

$result = query("SELECT COALESCE(SUM(views_count), 0) as total FROM info_attractions");
$row = fetchOne($result);
$stats['total_views'] = $row['total'] ?? 0;

// Fixed: Get all tourist spots with proper casting
$touristSpots = [];
$sql = "
    SELECT 
        a.id,
        a.name,
        a.description,
        a.location,
        a.images,
        a.category,
        a.views_count,
        a.created_at,
        COALESCE(AVG(f.rating), 0) as avg_rating,
        COUNT(DISTINCT f.id) as review_count
    FROM info_attractions a
    LEFT JOIN info_feedback f ON f.target_type = 'attraction' AND CAST(f.target_id AS INTEGER) = a.id
    GROUP BY a.id
    ORDER BY a.created_at DESC
";
$result = query($sql, []);
if ($result) {
    $touristSpots = fetchAll($result);
}

// Handle AJAX requests for delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
        $id = $_POST['id'];
        
        $checkResult = query("SELECT id FROM info_attractions WHERE id = $1", [$id]);
        if (pg_num_rows($checkResult) > 0) {
            $deleteResult = query("DELETE FROM info_attractions WHERE id = $1", [$id]);
            if ($deleteResult) {
                echo json_encode(['success' => true, 'message' => 'Tourist spot deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete tourist spot']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Tourist spot not found']);
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourist Spots Management - Daeteño Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        body {
            background: linear-gradient(145deg, #f1f5f9 0%, #e9eef5 100%);
            min-height: 100vh;
        }
        
        /* Glassmorphism header */
        .glass-header {
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        /* Modern card styling */
        .stat-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(4px);
            border-radius: 1.5rem;
            transition: all 0.25s ease;
            border: 1px solid rgba(255,255,255,0.5);
            box-shadow: 0 8px 20px -6px rgba(0,0,0,0.08);
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 28px -12px rgba(0,0,0,0.12);
        }
        
        /* Table container refinement */
        .table-container {
            background: rgba(255,255,255,0.97);
            backdrop-filter: blur(2px);
            border-radius: 1.5rem;
            box-shadow: 0 12px 30px -10px rgba(0,0,0,0.08);
            border: 1px solid rgba(226, 232, 240, 0.6);
            overflow: hidden;
        }
        
        .table-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 1px solid #e2e8f0;
        }
        
        /* Modern inputs */
        .search-input {
            border: 1.5px solid #e2e8f0;
            border-radius: 2rem;
            transition: all 0.2s ease;
            padding-left: 2.5rem;
            font-size: 0.9rem;
        }
        
        .search-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59,130,246,0.12);
            outline: none;
        }
        
        .filter-select {
            border: 1.5px solid #e2e8f0;
            border-radius: 2rem;
            padding: 0.5rem 1rem;
            background-color: white;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .filter-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59,130,246,0.12);
            outline: none;
        }
        
        /* Button styles */
        .btn-primary-modern {
            background: linear-gradient(105deg, #2563eb, #1e40af);
            border-radius: 2rem;
            padding: 0.65rem 1.6rem;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 4px 10px -4px rgba(37,99,235,0.3);
        }
        
        .btn-primary-modern:hover {
            background: linear-gradient(105deg, #1d4ed8, #1e3a8a);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px -6px rgba(37,99,235,0.4);
        }
        
        /* Table row hover */
        .table-row {
            transition: all 0.2s ease;
        }
        
        .table-row:hover {
            background: linear-gradient(90deg, #fefefe, #fafcff);
            transform: scale(1.002);
        }
        
        /* Action buttons */
        .action-btn {
            transition: all 0.2s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 12px;
            background: transparent;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
        }
        
        .action-edit:hover { background: #eff6ff; color: #2563eb; }
        .action-view:hover { background: #ecfdf5; color: #059669; }
        .action-delete:hover { background: #fef2f2; color: #dc2626; }
        
        /* Modal redesign */
        .modal-modern {
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(12px);
            border-radius: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        /* Image thumbnail */
        .image-thumb {
            width: 44px;
            height: 44px;
            object-fit: cover;
            border-radius: 14px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            transition: all 0.2s;
        }
        
        .image-thumb:hover {
            transform: scale(1.05);
        }
        
        /* Category badges */
        .category-badge {
            border-radius: 2rem;
            padding: 0.25rem 0.85rem;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.01em;
        }
        
        /* Toast animation */
        .toast-animated {
            animation: slideUpFade 0.4s ease-out, fadeOut 3s ease-in-out forwards;
        }
        
        @keyframes slideUpFade {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeOut {
            0% { opacity: 1; transform: translateY(0); }
            70% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(-10px); visibility: hidden; }
        }
        
        /* Featured badge pulse */
        .featured-badge {
            background: linear-gradient(135deg, #fef3c7, #fffbeb);
            border: 1px solid #fde68a;
        }
        
        /* Scrollbar refinement */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
    </style>
</head>
<body class="antialiased">

    <!-- Glass Morphism Header -->
    <div class="glass-header sticky top-0 z-20">
        <div class="max-w-7xl mx-auto px-5 sm:px-8 py-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-5">
                <a href="../dashboard.php" class="flex items-center gap-2 text-white/85 hover:text-white transition-all duration-200 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full text-sm font-medium">
                    <i class="fas fa-arrow-left text-xs"></i>
                    <span>Back to Dashboard</span>
                </a>
                <div class="hidden sm:block h-6 w-px bg-white/20"></div>
                <div class="flex items-center gap-2 text-white/90 text-sm font-medium bg-white/10 px-4 py-1.5 rounded-full">
                    <i class="fas fa-umbrella-beach text-blue-200"></i>
                    <span>Spot Manager</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-blue-100"><i class="far fa-user-circle mr-1"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <div class="flex items-center gap-2 text-blue-600 text-sm font-semibold mb-1">
                    <i class="fas fa-compass"></i>
                    <span>Destination Management</span>
                </div>
                <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-slate-800">Tourist <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Spots</span></h1>
                <p class="text-slate-500 mt-1 text-base">Manage all tourist destinations in Daet, Camarines Norte</p>
            </div>
            <a href="create.php" class="btn-primary-modern text-white inline-flex items-center gap-2 shadow-md">
                <i class="fas fa-plus-circle"></i> Add New Spot
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Total Spots</p>
                        <p class="text-3xl font-bold text-slate-800 mt-1"><?php echo $stats['active_spots']; ?></p>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                        <i class="fas fa-map-marker-alt text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-2 text-xs text-slate-400">Active destinations</div>
            </div>
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Featured Spots</p>
                        <p class="text-3xl font-bold text-slate-800 mt-1"><?php echo $stats['featured_spots']; ?></p>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-amber-100 to-amber-200 flex items-center justify-center">
                        <i class="fas fa-star text-amber-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-2 text-xs text-slate-400">⭐ 4.5+ rating</div>
            </div>
            <div class="stat-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Total Views</p>
                        <p class="text-3xl font-bold text-slate-800 mt-1"><?php echo number_format($stats['total_views']); ?></p>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-emerald-100 to-emerald-200 flex items-center justify-center">
                        <i class="fas fa-eye text-emerald-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-2 text-xs text-slate-400">Cumulative visits</div>
            </div>
        </div>

        <!-- Main Table Card -->
        <div class="table-container">
            <div class="table-header px-6 py-5 flex flex-col sm:flex-row justify-between items-center gap-4">
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <i class="fas fa-table-list text-blue-500"></i> All Tourist Spots
                </h2>
                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <div class="relative">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="text" id="searchInput" placeholder="Search spots..." 
                               class="search-input pl-10 pr-4 py-2 w-64">
                    </div>
                    <select id="filterSelect" class="filter-select">
                        <option value="all">✨ All Spots</option>
                        <option value="featured">⭐ Featured Only</option>
                        <option value="top_rated">🏆 Top Rated (4.5+)</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Spot</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Views</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Rating</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Featured</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="touristSpotsTable" class="bg-white divide-y divide-slate-100">
                        <?php if (empty($touristSpots)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="h-20 w-20 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                                        <i class="fas fa-database text-3xl text-slate-400"></i>
                                    </div>
                                    <p class="text-slate-500 font-medium">No tourist spots found</p>
                                    <p class="text-sm text-slate-400 mt-1">Click "Add New Spot" to create your first destination.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($touristSpots as $spot): 
                                $isFeatured = ($spot['avg_rating'] ?? 0) >= 4.5;
                                $firstImage = !empty($spot['images']) ? trim($spot['images'], '{}') : null;
                                $firstImage = $firstImage ? explode(',', $firstImage)[0] : null;
                                $firstImage = $firstImage ? trim($firstImage, '"') : null;
                                $locationData = json_decode($spot['location'] ?? '{}', true);
                            ?>
                            <tr class="table-row" 
                                data-id="<?php echo $spot['id']; ?>"
                                data-rating="<?php echo round($spot['avg_rating'] ?? 0, 1); ?>"
                                data-featured="<?php echo $isFeatured ? 'true' : 'false'; ?>">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <?php if ($firstImage && file_exists('../../' . $firstImage)): ?>
                                            <img src="../../<?php echo htmlspecialchars($firstImage); ?>" 
                                                 alt="<?php echo htmlspecialchars($spot['name']); ?>"
                                                 class="image-thumb">
                                        <?php else: ?>
                                            <div class="h-11 w-11 rounded-2xl bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center">
                                                <i class="fas fa-umbrella-beach text-blue-500 text-lg"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="font-semibold text-slate-800"><?php echo htmlspecialchars($spot['name']); ?></div>
                                            <div class="text-xs text-slate-400 mt-0.5">
                                                <?php echo htmlspecialchars($locationData['address'] ?? 'Daet, Camarines Norte'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="category-badge bg-blue-50 text-blue-700">
                                        <?php echo ucfirst(htmlspecialchars($spot['category'] ?? 'Uncategorized')); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-600 text-sm">
                                    <i class="fas fa-eye text-slate-300 mr-1"></i>
                                    <?php echo number_format($spot['views_count'] ?? 0); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1">
                                        <i class="fas fa-star text-amber-400 text-sm"></i>
                                        <span class="font-semibold text-slate-700"><?php echo number_format($spot['avg_rating'] ?? 0, 1); ?></span>
                                        <span class="text-slate-400 text-xs ml-1">(<?php echo $spot['review_count'] ?? 0; ?>)</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($isFeatured): ?>
                                        <span class="featured-badge px-3 py-1 text-xs font-semibold rounded-full text-amber-700">
                                            <i class="fas fa-star text-amber-500 mr-1"></i> Featured
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 text-xs rounded-full bg-slate-100 text-slate-500">
                                            No
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <button onclick="editSpot('<?php echo $spot['id']; ?>')" 
                                                class="action-btn action-edit text-blue-500"
                                                title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="viewSpot('<?php echo $spot['id']; ?>')" 
                                                class="action-btn action-view text-emerald-500"
                                                title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="deleteSpot('<?php echo $spot['id']; ?>', '<?php echo htmlspecialchars($spot['name']); ?>')" 
                                                class="action-btn action-delete text-red-400"
                                                title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 transition-all">
        <div class="modal-modern max-w-md w-full mx-4 p-6">
            <div class="text-center">
                <div class="mx-auto h-14 w-14 rounded-2xl bg-red-100 flex items-center justify-center mb-4">
                    <i class="fas fa-trash-alt text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">Delete Tourist Spot</h3>
                <p class="text-slate-500 text-sm mb-6" id="deleteMessage">Are you sure you want to delete this tourist spot? This action cannot be undone.</p>
                <div class="flex justify-center gap-3">
                    <button onclick="closeDeleteModal()" class="px-5 py-2.5 border border-slate-200 rounded-xl hover:bg-slate-50 transition text-slate-600 font-medium">
                        Cancel
                    </button>
                    <button onclick="confirmDelete()" class="px-5 py-2.5 bg-red-600 text-white rounded-xl hover:bg-red-700 transition shadow-md font-medium">
                        Delete Permanently
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="viewModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="modal-modern max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white/95 backdrop-blur-sm border-b border-slate-100 px-6 py-4 flex justify-between items-center rounded-t-2xl">
                <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                    <i class="fas fa-info-circle text-blue-500"></i> Spot Details
                </h3>
                <button onclick="closeViewModal()" class="text-slate-400 hover:text-slate-600 transition text-xl">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>
            <div class="p-6" id="spotDetails">
                <!-- Dynamic content -->
            </div>
            <div class="border-t border-slate-100 px-6 py-4 flex justify-end">
                <button onclick="closeViewModal()" class="px-5 py-2.5 bg-slate-600 text-white rounded-xl hover:bg-slate-700 transition font-medium">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div id="toast" class="fixed bottom-6 right-6 z-50 hidden"></div>

    <script>
        let spotToDelete = null;
        let spotNameToDelete = '';

        const searchInput = document.getElementById('searchInput');
        const filterSelect = document.getElementById('filterSelect');
        
        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const filterValue = filterSelect.value;
            const rows = document.querySelectorAll('#touristSpotsTable tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                if (row.cells && row.cells.length > 1) {
                    const spotName = row.cells[0].innerText.toLowerCase();
                    const featured = row.getAttribute('data-featured') === 'true';
                    const rating = parseFloat(row.getAttribute('data-rating') || 0);
                    
                    let matchesSearch = spotName.includes(searchTerm);
                    let matchesFilter = true;
                    
                    if (filterValue === 'featured') {
                        matchesFilter = featured;
                    } else if (filterValue === 'top_rated') {
                        matchesFilter = rating >= 4.5;
                    }
                    
                    if (matchesSearch && matchesFilter) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        }
        
        searchInput.addEventListener('input', filterTable);
        filterSelect.addEventListener('change', filterTable);
        
        function editSpot(id) {
            window.location.href = `edit.php?id=${id}`;
        }
        
        async function viewSpot(id) {
            try {
                const response = await fetch(`get_spot.php?id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    const data = result.data;
                    const locationData = data.location ? JSON.parse(data.location) : {};
                    const images = data.images ? data.images.replace(/[{}]/g, '').split(',').map(img => img.trim().replace(/"/g, '')) : [];
                    
                    const detailsHtml = `
                        <div class="space-y-4">
                            <div class="bg-slate-50 rounded-xl p-4">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Spot Name</label>
                                <p class="text-slate-800 font-bold text-lg mt-1">${escapeHtml(data.name)}</p>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-4">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Description</label>
                                <p class="text-slate-700 mt-1 leading-relaxed">${escapeHtml(data.description || 'No description provided')}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-slate-50 rounded-xl p-4">
                                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Category</label>
                                    <p class="text-slate-800 font-medium mt-1">${escapeHtml(data.category || 'Uncategorized')}</p>
                                </div>
                                <div class="bg-slate-50 rounded-xl p-4">
                                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Total Views</label>
                                    <p class="text-slate-800 font-medium mt-1">${Number(data.views_count || 0).toLocaleString()}</p>
                                </div>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-4">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Location</label>
                                <p class="text-slate-800 font-medium mt-1">${escapeHtml(locationData.address || 'Daet, Camarines Norte')}</p>
                                ${locationData.coordinates ? `<p class="text-slate-500 text-sm mt-1"><i class="fas fa-map-pin"></i> Lat: ${locationData.coordinates.lat}, Lng: ${locationData.coordinates.lng}</p>` : ''}
                            </div>
                            ${images.length > 0 && images[0] ? `
                            <div class="bg-slate-50 rounded-xl p-4">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Images</label>
                                <div class="flex gap-3 mt-3 flex-wrap">
                                    ${images.map(img => `<img src="../../${img}" alt="Spot image" class="h-24 w-24 object-cover rounded-xl shadow-md hover:scale-105 transition" onerror="this.style.display='none'">`).join('')}
                                </div>
                            </div>
                            ` : ''}
                            <div class="bg-slate-50 rounded-xl p-4">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Rating</label>
                                <div class="flex items-center mt-2">
                                    ${generateStarRating(data.avg_rating || 0)}
                                    <span class="ml-2 text-slate-600 text-sm">(${data.review_count || 0} reviews)</span>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('spotDetails').innerHTML = detailsHtml;
                    document.getElementById('viewModal').classList.remove('hidden');
                    document.getElementById('viewModal').classList.add('flex');
                } else {
                    showToast(result.message || 'Failed to load spot details', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error loading spot details', 'error');
            }
        }
        
        function generateStarRating(rating) {
            const fullStars = Math.floor(rating);
            const hasHalfStar = rating % 1 >= 0.5;
            let stars = '';
            for (let i = 0; i < fullStars; i++) {
                stars += '<i class="fas fa-star text-amber-400"></i>';
            }
            if (hasHalfStar) {
                stars += '<i class="fas fa-star-half-alt text-amber-400"></i>';
            }
            const emptyStars = 5 - Math.ceil(rating);
            for (let i = 0; i < emptyStars; i++) {
                stars += '<i class="far fa-star text-amber-400"></i>';
            }
            return stars;
        }
        
        function deleteSpot(id, name) {
            spotToDelete = id;
            spotNameToDelete = name;
            document.getElementById('deleteMessage').innerHTML = `Are you sure you want to delete "<strong class="text-slate-800">${escapeHtml(name)}</strong>"? This action cannot be undone.`;
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('flex');
            document.getElementById('deleteModal').classList.add('hidden');
            spotToDelete = null;
            spotNameToDelete = '';
        }
        
        async function confirmDelete() {
            if (spotToDelete) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', spotToDelete);
                    
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showToast(result.message, 'success');
                        const row = document.querySelector(`tr[data-id="${spotToDelete}"]`);
                        if (row) {
                            row.remove();
                        }
                        filterTable();
                    } else {
                        showToast(result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('Error deleting tourist spot', 'error');
                }
                
                closeDeleteModal();
            }
        }
        
        function closeViewModal() {
            document.getElementById('viewModal').classList.remove('flex');
            document.getElementById('viewModal').classList.add('hidden');
        }
        
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const bgGradient = type === 'success' ? 'from-emerald-500 to-emerald-600' : 'from-red-500 to-red-600';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
            
            toast.innerHTML = `
                <div class="bg-gradient-to-r ${bgGradient} text-white px-5 py-3 rounded-2xl shadow-2xl flex items-center gap-3 toast-animated">
                    <i class="fas ${icon} text-lg"></i>
                    <span class="font-medium">${escapeHtml(message)}</span>
                </div>
            `;
            toast.classList.remove('hidden');
            
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 3200);
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        window.onclick = function(event) {
            const deleteModal = document.getElementById('deleteModal');
            const viewModal = document.getElementById('viewModal');
            
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
            if (event.target === viewModal) {
                closeViewModal();
            }
        }
    </script>
</body>
</html>