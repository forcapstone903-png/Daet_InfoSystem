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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal { transition: all 0.3s ease; }
        .delete-btn, .edit-btn, .view-btn { transition: all 0.2s ease; }
        .delete-btn:hover { transform: scale(1.1); color: #dc2626; }
        .edit-btn:hover { transform: scale(1.1); color: #2563eb; }
        .view-btn:hover { transform: scale(1.1); color: #16a34a; }
        .table-row:hover { background-color: #f9fafb; }
        .image-thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 8px; }
        @keyframes fadeOut { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(100%); } }
        .toast { animation: fadeOut 3s ease-in-out forwards; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-4">
                <div class="flex items-center space-x-4">
                    <a href="../dashboard.php" class="flex items-center text-white/80 hover:text-white transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-blue-200">Tourist Spots Management</span>
                    <div class="h-6 w-px bg-white/30"></div>
                    <span class="text-sm text-blue-200">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Tourist Spots Management</h1>
                <p class="text-gray-600">Manage all tourist destinations in Daet</p>
            </div>
            <a href="create.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-sm hover:shadow">
                <i class="fas fa-plus mr-2"></i> Add New Spot
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 shadow-sm">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-lg bg-blue-200 flex items-center justify-center mr-3">
                        <i class="fas fa-map-marker-alt text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Total Spots</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['active_spots']; ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg p-4 shadow-sm">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-lg bg-yellow-200 flex items-center justify-center mr-3">
                        <i class="fas fa-star text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Featured Spots</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['featured_spots']; ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 shadow-sm">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-lg bg-green-200 flex items-center justify-center mr-3">
                        <i class="fas fa-eye text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Total Views</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_views']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <h2 class="text-lg font-semibold text-gray-900">All Tourist Spots</h2>
                    <div class="flex flex-col sm:flex-row items-center gap-3">
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Search spots..." 
                                   class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-64">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <select id="filterSelect" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                            <option value="all">All Spots</option>
                            <option value="featured">Featured Only</option>
                            <option value="top_rated">Top Rated (4.5+)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Spot</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Views</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Featured</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="touristSpotsTable" class="bg-white divide-y divide-gray-200">
                        <?php if (empty($touristSpots)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-center">
                                    <i class="fas fa-database text-4xl mb-3 text-gray-300"></i>
                                    <p class="text-gray-500">No tourist spots found</p>
                                    <p class="text-sm text-gray-400 mt-2">Click "Add New Spot" to create your first tourist spot.</p>
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
                            <tr class="table-row hover:bg-gray-50 transition-colors" 
                                data-id="<?php echo $spot['id']; ?>"
                                data-rating="<?php echo round($spot['avg_rating'] ?? 0, 1); ?>"
                                data-featured="<?php echo $isFeatured ? 'true' : 'false'; ?>">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <?php if ($firstImage && file_exists('../../' . $firstImage)): ?>
                                            <img src="../../<?php echo htmlspecialchars($firstImage); ?>" 
                                                 alt="<?php echo htmlspecialchars($spot['name']); ?>"
                                                 class="h-10 w-10 rounded-lg object-cover mr-3">
                                        <?php else: ?>
                                            <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center mr-3">
                                                <i class="fas fa-umbrella-beach text-blue-600"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($spot['name']); ?></div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($locationData['address'] ?? 'Daet, Camarines Norte'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                        <?php echo ucfirst(htmlspecialchars($spot['category'] ?? 'Uncategorized')); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    <i class="fas fa-eye text-gray-400 mr-1"></i>
                                    <?php echo number_format($spot['views_count'] ?? 0); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <i class="fas fa-star text-amber-400 mr-1"></i>
                                        <span class="font-medium"><?php echo number_format($spot['avg_rating'] ?? 0, 1); ?></span>
                                        <span class="text-gray-400 ml-1">(<?php echo $spot['review_count'] ?? 0; ?>)</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($isFeatured): ?>
                                        <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-star mr-1"></i>Featured
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-500">
                                            No
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-3">
                                        <button onclick="editSpot('<?php echo $spot['id']; ?>')" 
                                                class="edit-btn text-blue-600 hover:text-blue-800 transition-colors"
                                                title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="viewSpot('<?php echo $spot['id']; ?>')" 
                                                class="view-btn text-green-600 hover:text-green-800 transition-colors"
                                                title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="deleteSpot('<?php echo $spot['id']; ?>', '<?php echo htmlspecialchars($spot['name']); ?>')" 
                                                class="delete-btn text-red-600 hover:text-red-800 transition-colors"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
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
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 modal shadow-xl">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Delete Tourist Spot</h3>
                <p class="text-sm text-gray-500 mb-4" id="deleteMessage">Are you sure you want to delete this tourist spot? This action cannot be undone.</p>
                <div class="flex justify-center space-x-3">
                    <button onclick="closeDeleteModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button onclick="confirmDelete()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 modal shadow-xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4 sticky top-0 bg-white pb-3 border-b">
                <h3 class="text-lg font-medium text-gray-900">Tourist Spot Details</h3>
                <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="spotDetails" class="space-y-3"></div>
            <div class="mt-6 flex justify-end">
                <button onclick="closeViewModal()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <div id="toast" class="fixed bottom-4 right-4 hidden z-50"></div>

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
                        <div class="border-b pb-3">
                            <label class="text-sm font-medium text-gray-500 block">Spot Name</label>
                            <p class="text-gray-900 font-medium">${escapeHtml(data.name)}</p>
                        </div>
                        <div class="border-b pb-3">
                            <label class="text-sm font-medium text-gray-500 block">Description</label>
                            <p class="text-gray-700">${escapeHtml(data.description || 'No description provided')}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4 border-b pb-3">
                            <div>
                                <label class="text-sm font-medium text-gray-500 block">Category</label>
                                <p class="text-gray-900">${escapeHtml(data.category || 'Uncategorized')}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500 block">Views</label>
                                <p class="text-gray-900">${Number(data.views_count || 0).toLocaleString()}</p>
                            </div>
                        </div>
                        <div class="border-b pb-3">
                            <label class="text-sm font-medium text-gray-500 block">Location</label>
                            <p class="text-gray-900">${escapeHtml(locationData.address || 'Daet, Camarines Norte')}</p>
                            ${locationData.coordinates ? `<p class="text-sm text-gray-500 mt-1">Lat: ${locationData.coordinates.lat}, Lng: ${locationData.coordinates.lng}</p>` : ''}
                        </div>
                        ${images.length > 0 && images[0] ? `
                        <div class="border-b pb-3">
                            <label class="text-sm font-medium text-gray-500 block">Images</label>
                            <div class="flex gap-2 mt-2 flex-wrap">
                                ${images.map(img => `<img src="../../${img}" alt="Spot image" class="h-20 w-20 object-cover rounded-lg shadow" onerror="this.style.display='none'">`).join('')}
                            </div>
                        </div>
                        ` : ''}
                        <div>
                            <label class="text-sm font-medium text-gray-500 block">Rating</label>
                            <div class="flex items-center mt-1">
                                ${generateStarRating(data.avg_rating || 0)}
                                <span class="ml-2 text-gray-600">(${data.review_count || 0} reviews)</span>
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
            document.getElementById('deleteMessage').innerHTML = `Are you sure you want to delete "<strong>${escapeHtml(name)}</strong>"? This action cannot be undone.`;
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
            const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            toast.innerHTML = `
                <div class="${bgColor} text-white px-6 py-3 rounded-lg shadow-lg flex items-center toast">
                    <i class="fas ${icon} mr-2"></i>
                    <span>${escapeHtml(message)}</span>
                </div>
            `;
            toast.classList.remove('hidden');
            
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 3000);
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