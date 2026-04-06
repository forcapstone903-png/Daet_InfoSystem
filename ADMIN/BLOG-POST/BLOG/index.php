<?php
// File: BLOG/index.php
// Blog Management Dashboard for Admin

require_once '../../../dbconn.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../../../login.php');
}

$userName = $_SESSION['full_name'] ?? 'Administrator';
$success_message = '';
$error_message = '';

// Handle delete action
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $post_id = $_GET['delete'];
    
    // First, get the featured image to delete it from server
    $result = query("SELECT featured_image FROM info_blog_posts WHERE id = $1", [$post_id]);
    $post = fetchOne($result);
    
    if ($post && !empty($post['featured_image'])) {
        $image_path = '../../../' . $post['featured_image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Delete the post
    $result = query("DELETE FROM info_blog_posts WHERE id = $1", [$post_id]);
    
    if ($result) {
        $success_message = 'Blog post deleted successfully!';
    } else {
        $error_message = 'Failed to delete blog post.';
    }
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selected_posts = $_POST['selected_posts'] ?? [];
    
    if (empty($selected_posts)) {
        $error_message = 'Please select at least one post.';
    } else {
        $placeholders = implode(',', array_fill(0, count($selected_posts), '$' . (++$i)));
        
        if ($action === 'delete') {
            // Get featured images before deletion
            $result = query("SELECT id, featured_image FROM info_blog_posts WHERE id = ANY($1::int[])", [$selected_posts]);
            $posts = fetchAll($result);
            
            foreach ($posts as $post) {
                if (!empty($post['featured_image'])) {
                    $image_path = '../../../' . $post['featured_image'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
            }
            
            $result = query("DELETE FROM info_blog_posts WHERE id = ANY($1::int[])", [$selected_posts]);
            $message = 'Selected posts deleted successfully!';
        } elseif ($action === 'publish') {
            $result = query("UPDATE info_blog_posts SET status = 'published', updated_at = NOW() WHERE id = ANY($1::int[])", [$selected_posts]);
            $message = 'Selected posts published successfully!';
        } elseif ($action === 'draft') {
            $result = query("UPDATE info_blog_posts SET status = 'draft', updated_at = NOW() WHERE id = ANY($1::int[])", [$selected_posts]);
            $message = 'Selected posts moved to drafts!';
        }
        
        if ($result) {
            $success_message = $message;
        } else {
            $error_message = 'Failed to perform bulk action.';
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Filtering
$status_filter = $_GET['status'] ?? '';
$category_filter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// Build WHERE clause
$where_conditions = [];
$params = [];
$param_count = 1;

if (!empty($status_filter)) {
    $where_conditions[] = "status = $" . $param_count++;
    $params[] = $status_filter;
}

if (!empty($category_filter)) {
    $where_conditions[] = "category = $" . $param_count++;
    $params[] = $category_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(title ILIKE $" . $param_count++ . " OR content ILIKE $" . $param_count++ . ")";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM info_blog_posts $where_clause";
$count_result = query($count_sql, $params);
$total_row = fetchOne($count_result);
$total_posts = $total_row['total'];
$total_pages = ceil($total_posts / $per_page);

// Get posts with pagination - Fixed: Using info_profiles instead of users
$sql = "SELECT bp.*, p.full_name as author_name 
        FROM info_blog_posts bp
        LEFT JOIN info_profiles p ON bp.user_id = p.id
        $where_clause
        ORDER BY bp.created_at DESC 
        LIMIT $" . $param_count++ . " OFFSET $" . $param_count++;
$params[] = $per_page;
$params[] = $offset;

$result = query($sql, $params);
$posts = fetchAll($result);

// Get categories for filter
$cat_result = query("SELECT DISTINCT category FROM info_blog_posts WHERE category IS NOT NULL AND category != '' ORDER BY category");
$categories = fetchAll($cat_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Manager - Daeteño Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .toast { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .status-badge {
            @apply px-2 py-1 text-xs font-semibold rounded-full;
        }
        .status-published { @apply bg-green-100 text-green-800; }
        .status-draft { @apply bg-yellow-100 text-yellow-800; }
        .status-scheduled { @apply bg-blue-100 text-blue-800; }
        .checkbox-row:hover { background-color: #f9fafb; }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <div class="min-h-screen bg-gray-50">
        <!-- Admin Header -->
        <div class="bg-gradient-to-r from-green-600 to-green-800 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between py-4">
                    <div class="flex items-center space-x-4">
                        <a href="../../dashboard.php" class="flex items-center text-white/80 hover:text-white transition-colors">
                            <i class="fas fa-tachometer-alt mr-2"></i>
                            Admin Dashboard
                        </a>
                        <div class="h-6 w-px bg-white/30"></div>
                        <span class="text-white font-medium">Blog Manager</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="create.php" class="px-4 py-2 bg-white text-green-700 rounded-lg hover:bg-gray-100 transition-colors text-sm font-medium">
                            <i class="fas fa-plus mr-2"></i> New Post
                        </a>
                        <div class="h-6 w-px bg-white/30"></div>
                        <span class="text-sm"><i class="fas fa-user-circle mr-1"></i> <?php echo htmlspecialchars($userName); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Alerts -->
            <?php if ($success_message): ?>
            <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm toast">
                <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm toast">
                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Blog Manager</h1>
                <p class="text-gray-600">Manage your blog posts, create new content, and track performance</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <?php
                $total_result = query("SELECT COUNT(*) as count FROM info_blog_posts");
                $total = fetchOne($total_result);
                $published_result = query("SELECT COUNT(*) as count FROM info_blog_posts WHERE status = 'published'");
                $published = fetchOne($published_result);
                $draft_result = query("SELECT COUNT(*) as count FROM info_blog_posts WHERE status = 'draft'");
                $draft = fetchOne($draft_result);
                $scheduled_result = query("SELECT COUNT(*) as count FROM info_blog_posts WHERE status = 'scheduled'");
                $scheduled = fetchOne($scheduled_result);
                ?>
                <div class="bg-white rounded-xl shadow p-4 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Total Posts</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $total['count']; ?></p>
                        </div>
                        <i class="fas fa-newspaper text-3xl text-green-500 opacity-50"></i>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-4 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Published</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $published['count']; ?></p>
                        </div>
                        <i class="fas fa-check-circle text-3xl text-blue-500 opacity-50"></i>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-4 border-l-4 border-yellow-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Drafts</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $draft['count']; ?></p>
                        </div>
                        <i class="fas fa-pencil-alt text-3xl text-yellow-500 opacity-50"></i>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-4 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Scheduled</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $scheduled['count']; ?></p>
                        </div>
                        <i class="fas fa-calendar-alt text-3xl text-purple-500 opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="bg-white rounded-xl shadow mb-6">
                <div class="p-4 border-b border-gray-200">
                    <form method="GET" action="" class="flex flex-wrap gap-4 items-end">
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by title or content..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                <option value="">All Status</option>
                                <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="scheduled" <?php echo $status_filter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select name="category" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                        <?php echo $category_filter === $cat['category'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-search mr-2"></i>Filter
                            </button>
                            <a href="?reset=1" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors ml-2">
                                <i class="fas fa-times mr-2"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bulk Actions -->
            <form method="POST" action="" id="bulkActionForm">
                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <button type="button" onclick="toggleAllCheckboxes()" class="text-sm text-gray-600 hover:text-green-600 transition-colors">
                                <i class="fas fa-check-double mr-1"></i> Select All
                            </button>
                            <select name="bulk_action" id="bulkAction" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
                                <option value="">Bulk Actions</option>
                                <option value="publish">Publish</option>
                                <option value="draft">Move to Draft</option>
                                <option value="delete">Delete</option>
                            </select>
                            <button type="button" onclick="applyBulkAction()" class="px-3 py-1.5 bg-gray-700 text-white rounded-lg hover:bg-gray-800 transition-colors text-sm">
                                Apply
                            </button>
                        </div>
                        <div class="text-sm text-gray-500">
                            Showing <?php echo count($posts); ?> of <?php echo $total_posts; ?> posts
                        </div>
                    </div>

                    <!-- Posts Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left">
                                        <input type="checkbox" id="selectAllCheckbox" class="rounded border-gray-300">
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Post</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($posts)): ?>
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                                        <i class="fas fa-inbox text-4xl mb-3 opacity-50"></i>
                                        <p>No blog posts found.</p>
                                        <a href="create.php" class="inline-block mt-3 text-green-600 hover:text-green-700">
                                            <i class="fas fa-plus mr-1"></i> Create your first post
                                        </a>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($posts as $post): ?>
                                    <tr class="checkbox-row transition-colors hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <input type="checkbox" name="selected_posts[]" value="<?php echo $post['id']; ?>" class="post-checkbox rounded border-gray-300">
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center">
                                                <?php if (!empty($post['featured_image'])): ?>
                                                <img src="../../../<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                                     alt="" class="w-10 h-10 rounded-lg object-cover mr-3">
                                                <?php else: ?>
                                                <div class="w-10 h-10 rounded-lg bg-gray-200 flex items-center justify-center mr-3">
                                                    <i class="fas fa-image text-gray-400"></i>
                                                </div>
                                                <?php endif; ?>
                                                <div>
                                                    <a href="edit.php?id=<?php echo $post['id']; ?>" 
                                                       class="font-medium text-gray-900 hover:text-green-600 transition-colors">
                                                        <?php echo htmlspecialchars(substr($post['title'], 0, 60)); ?>
                                                        <?php echo strlen($post['title']) > 60 ? '...' : ''; ?>
                                                    </a>
                                                    <?php if (!empty($post['excerpt'])): ?>
                                                    <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars(substr($post['excerpt'], 0, 80)); ?>...</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            <?php echo htmlspecialchars($post['author_name'] ?? 'Unknown'); ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700">
                                                <?php echo htmlspecialchars($post['category'] ?? 'Uncategorized'); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?php
                                            $status_class = '';
                                            switch($post['status']) {
                                                case 'published':
                                                    $status_class = 'status-published';
                                                    break;
                                                case 'draft':
                                                    $status_class = 'status-draft';
                                                    break;
                                                case 'scheduled':
                                                    $status_class = 'status-scheduled';
                                                    break;
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst($post['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <a href="edit.php?id=<?php echo $post['id']; ?>" 
                                                   class="text-blue-600 hover:text-blue-800 transition-colors"
                                                   title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="view.php?id=<?php echo $post['id']; ?>" target="_blank"
                                                   class="text-green-600 hover:text-green-800 transition-colors"
                                                   title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" onclick="confirmDelete(<?php echo $post['id']; ?>)" 
                                                        class="text-red-600 hover:text-red-800 transition-colors"
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

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                        <div class="flex justify-center">
                            <nav class="flex items-center gap-1">
                                <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>&search=<?php echo urlencode($search); ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-100 transition-colors">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>&search=<?php echo urlencode($search); ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-100 transition-colors <?php echo $i == $page ? 'bg-green-600 text-white border-green-600' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>&search=<?php echo urlencode($search); ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-100 transition-colors">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Select all checkboxes
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const postCheckboxes = document.querySelectorAll('.post-checkbox');
        
        function toggleAllCheckboxes() {
            const isChecked = selectAllCheckbox.checked;
            postCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
        }
        
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', toggleAllCheckboxes);
            
            // Update select all checkbox when individual checkboxes change
            postCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    const allChecked = Array.from(postCheckboxes).every(cb => cb.checked);
                    selectAllCheckbox.checked = allChecked;
                });
            });
        }
        
        function applyBulkAction() {
            const action = document.getElementById('bulkAction').value;
            const selected = document.querySelectorAll('.post-checkbox:checked');
            
            if (!action) {
                alert('Please select a bulk action.');
                return;
            }
            
            if (selected.length === 0) {
                alert('Please select at least one post.');
                return;
            }
            
            let confirmMessage = '';
            if (action === 'delete') {
                confirmMessage = `Are you sure you want to delete ${selected.length} post(s)? This action cannot be undone.`;
            } else if (action === 'publish') {
                confirmMessage = `Are you sure you want to publish ${selected.length} post(s)?`;
            } else if (action === 'draft') {
                confirmMessage = `Are you sure you want to move ${selected.length} post(s) to drafts?`;
            }
            
            if (confirm(confirmMessage)) {
                document.getElementById('bulkActionForm').submit();
            }
        }
        
        function confirmDelete(postId) {
            if (confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
                window.location.href = '?delete=' + postId;
            }
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.toast');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>