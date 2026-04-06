<?php
// File: BLOG/index.php
// Blog Management Dashboard for Admin

require_once '../../../dbconn.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../../../login.php');
}

$userName = $_SESSION['full_name'] ?? 'Administrator';
$success_message = '';
$error_message = '';

if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $post_id = $_GET['delete'];
    $result = query("SELECT featured_image FROM info_blog_posts WHERE id = $1", [$post_id]);
    $post = fetchOne($result);
    if ($post && !empty($post['featured_image'])) {
        $image_path = '../../../' . $post['featured_image'];
        if (file_exists($image_path)) unlink($image_path);
    }
    $result = query("DELETE FROM info_blog_posts WHERE id = $1", [$post_id]);
    $success_message = $result ? 'Blog post deleted successfully!' : 'Failed to delete blog post.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selected_posts = $_POST['selected_posts'] ?? [];
    if (empty($selected_posts)) {
        $error_message = 'Please select at least one post.';
    } else {
        if ($action === 'delete') {
            $result = query("SELECT id, featured_image FROM info_blog_posts WHERE id = ANY($1::int[])", [$selected_posts]);
            $posts = fetchAll($result);
            foreach ($posts as $post) {
                if (!empty($post['featured_image'])) {
                    $image_path = '../../../' . $post['featured_image'];
                    if (file_exists($image_path)) unlink($image_path);
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
        $success_message = $result ? $message : 'Failed to perform bulk action.';
    }
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;
$status_filter = $_GET['status'] ?? '';
$category_filter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

$where_conditions = [];
$params = [];
$param_count = 1;

if (!empty($status_filter)) { $where_conditions[] = "status = $" . $param_count++; $params[] = $status_filter; }
if (!empty($category_filter)) { $where_conditions[] = "category = $" . $param_count++; $params[] = $category_filter; }
if (!empty($search)) { $where_conditions[] = "(title ILIKE $" . $param_count++ . " OR content ILIKE $" . $param_count++ . ")"; $params[] = "%$search%"; $params[] = "%$search%"; }

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
$count_sql = "SELECT COUNT(*) as total FROM info_blog_posts $where_clause";
$count_result = query($count_sql, $params);
$total_row = fetchOne($count_result);
$total_posts = $total_row['total'];
$total_pages = ceil($total_posts / $per_page);

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
$cat_result = query("SELECT DISTINCT category FROM info_blog_posts WHERE category IS NOT NULL AND category != '' ORDER BY category");
$categories = fetchAll($cat_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Manager - Daeteño Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .toast { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .status-published { background: #dcfce7; color: #166534; }
        .status-draft { background: #fef3c7; color: #92400e; }
        .status-scheduled { background: #dbeafe; color: #1e40af; }
        .checkbox-row:hover { background: linear-gradient(90deg, rgba(5,150,105,0.05) 0%, transparent 100%); }
        .glass-header { background: linear-gradient(135deg, #059669 0%, #047857 100%); }
        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px -8px rgba(0,0,0,0.1); }
        .blog-table-row { transition: all 0.2s ease; }
        .blog-table-row:hover { background: #f8fafc; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-emerald-50/30 font-sans antialiased">
    <div class="min-h-screen">
        <!-- Glass Header -->
        <div class="glass-header text-white sticky top-0 z-50 shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between py-4">
                    <div class="flex items-center space-x-4">
                        <a href="../../dashboard.php" class="flex items-center text-white/80 hover:text-white transition-all duration-200 hover:scale-105">
                            <i class="fas fa-tachometer-alt mr-2"></i>
                            <span class="text-sm font-medium">Admin Dashboard</span>
                        </a>
                        <div class="h-6 w-px bg-white/30"></div>
                        <span class="text-white font-semibold">Blog Manager</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="create.php" class="px-4 py-2 bg-white text-emerald-700 rounded-xl hover:bg-slate-50 transition-all shadow-md hover:shadow-lg text-sm font-semibold flex items-center gap-2">
                            <i class="fas fa-plus"></i> New Post
                        </a>
                        <div class="h-6 w-px bg-white/30"></div>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-user-circle text-lg"></i>
                            <span class="text-sm font-medium"><?php echo htmlspecialchars($userName); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <?php if ($success_message): ?>
            <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded-xl shadow-sm toast flex items-center gap-3">
                <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-xl shadow-sm toast flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-500 text-lg"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Blog Manager</h1>
                <p class="text-slate-500 mt-2">Manage your blog posts, create new content, and track performance</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-8">
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
                <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5 border-l-4 border-l-emerald-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-500 text-sm font-medium">Total Posts</p>
                            <p class="text-2xl font-bold text-slate-800"><?php echo $total['count']; ?></p>
                        </div>
                        <i class="fas fa-newspaper text-3xl text-emerald-400 opacity-60"></i>
                    </div>
                </div>
                <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5 border-l-4 border-l-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-500 text-sm font-medium">Published</p>
                            <p class="text-2xl font-bold text-slate-800"><?php echo $published['count']; ?></p>
                        </div>
                        <i class="fas fa-check-circle text-3xl text-blue-400 opacity-60"></i>
                    </div>
                </div>
                <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5 border-l-4 border-l-amber-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-500 text-sm font-medium">Drafts</p>
                            <p class="text-2xl font-bold text-slate-800"><?php echo $draft['count']; ?></p>
                        </div>
                        <i class="fas fa-pencil-alt text-3xl text-amber-400 opacity-60"></i>
                    </div>
                </div>
                <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5 border-l-4 border-l-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-500 text-sm font-medium">Scheduled</p>
                            <p class="text-2xl font-bold text-slate-800"><?php echo $scheduled['count']; ?></p>
                        </div>
                        <i class="fas fa-calendar-alt text-3xl text-purple-400 opacity-60"></i>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 mb-6 overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                    <form method="GET" action="" class="flex flex-wrap gap-4 items-end">
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by title or content..."
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
                            <select name="status" class="px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 bg-white">
                                <option value="">All Status</option>
                                <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="scheduled" <?php echo $status_filter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Category</label>
                            <select name="category" class="px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 bg-white">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                        <?php echo $category_filter === $cat['category'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white rounded-xl hover:from-emerald-700 hover:to-emerald-800 transition-all shadow-sm font-medium">
                                <i class="fas fa-search mr-2"></i>Filter
                            </button>
                            <a href="?reset=1" class="px-5 py-2.5 border border-slate-300 rounded-xl hover:bg-slate-50 transition-all font-medium">
                                <i class="fas fa-times mr-2"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bulk Actions and Table -->
            <form method="POST" action="" id="bulkActionForm">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <button type="button" onclick="toggleAllCheckboxes()" class="text-sm text-slate-600 hover:text-emerald-600 transition-all font-medium">
                                <i class="fas fa-check-double mr-1"></i> Select All
                            </button>
                            <select name="bulk_action" id="bulkAction" class="px-3 py-2 border border-slate-200 rounded-xl text-sm bg-white">
                                <option value="">Bulk Actions</option>
                                <option value="publish">Publish</option>
                                <option value="draft">Move to Draft</option>
                                <option value="delete">Delete</option>
                            </select>
                            <button type="button" onclick="applyBulkAction()" class="px-4 py-2 bg-slate-700 text-white rounded-xl hover:bg-slate-800 transition-all text-sm font-medium">
                                Apply
                            </button>
                        </div>
                        <div class="text-sm text-slate-500 bg-slate-100 px-3 py-1.5 rounded-full">
                            Showing <?php echo count($posts); ?> of <?php echo $total_posts; ?> posts
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left w-12">
                                        <input type="checkbox" id="selectAllCheckbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Post</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Author</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Category</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                <?php if (empty($posts)): ?>
                                <tr>
                                    <td colspan="7" class="px-4 py-16 text-center text-slate-500">
                                        <i class="fas fa-inbox text-5xl mb-4 opacity-30"></i>
                                        <p class="font-medium">No blog posts found.</p>
                                        <a href="create.php" class="inline-block mt-3 text-emerald-600 hover:text-emerald-700 font-medium">
                                            <i class="fas fa-plus mr-1"></i> Create your first post
                                        </a>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($posts as $post): ?>
                                    <tr class="blog-table-row transition-all duration-200 hover:bg-slate-50/80">
                                        <td class="px-4 py-3">
                                            <input type="checkbox" name="selected_posts[]" value="<?php echo $post['id']; ?>" class="post-checkbox rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center">
                                                <?php if (!empty($post['featured_image'])): ?>
                                                <img src="../../../<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                                     alt="" class="w-10 h-10 rounded-xl object-cover mr-3 shadow-sm">
                                                <?php else: ?>
                                                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center mr-3">
                                                    <i class="fas fa-image text-slate-400"></i>
                                                </div>
                                                <?php endif; ?>
                                                <div>
                                                    <a href="edit.php?id=<?php echo $post['id']; ?>" 
                                                       class="font-semibold text-slate-800 hover:text-emerald-600 transition-colors">
                                                        <?php echo htmlspecialchars(substr($post['title'], 0, 60)); ?>
                                                        <?php echo strlen($post['title']) > 60 ? '...' : ''; ?>
                                                    </a>
                                                    <?php if (!empty($post['excerpt'])): ?>
                                                    <p class="text-xs text-slate-400 mt-0.5"><?php echo htmlspecialchars(substr($post['excerpt'], 0, 80)); ?>...</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600 font-medium">
                                            <?php echo htmlspecialchars($post['author_name'] ?? 'Unknown'); ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex px-2.5 py-1 text-xs rounded-full bg-slate-100 text-slate-600 font-medium">
                                                <?php echo htmlspecialchars($post['category'] ?? 'Uncategorized'); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="status-badge px-2.5 py-1 text-xs font-semibold rounded-full 
                                                <?php echo $post['status'] === 'published' ? 'status-published' : ($post['status'] === 'draft' ? 'status-draft' : 'status-scheduled'); ?>">
                                                <?php echo ucfirst($post['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-500">
                                            <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <a href="edit.php?id=<?php echo $post['id']; ?>" class="text-blue-500 hover:text-blue-700 transition-all" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="view.php?id=<?php echo $post['id']; ?>" target="_blank" class="text-emerald-500 hover:text-emerald-700 transition-all" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" onclick="confirmDelete(<?php echo $post['id']; ?>)" class="text-red-500 hover:text-red-700 transition-all" title="Delete">
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

                    <?php if ($total_pages > 1): ?>
                    <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/50">
                        <div class="flex justify-center">
                            <nav class="flex items-center gap-2">
                                <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>&search=<?php echo urlencode($search); ?>" 
                                   class="px-3 py-1.5 border border-slate-300 rounded-xl hover:bg-white transition-all text-sm">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>&search=<?php echo urlencode($search); ?>" 
                                   class="px-3 py-1.5 border border-slate-300 rounded-xl hover:bg-white transition-all text-sm <?php echo $i == $page ? 'bg-emerald-600 text-white border-emerald-600 hover:bg-emerald-700' : 'bg-white'; ?>">
                                    <?php echo $i; ?>
                                </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>&search=<?php echo urlencode($search); ?>" 
                                   class="px-3 py-1.5 border border-slate-300 rounded-xl hover:bg-white transition-all text-sm">
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
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const postCheckboxes = document.querySelectorAll('.post-checkbox');
        
        function toggleAllCheckboxes() {
            const isChecked = selectAllCheckbox.checked;
            postCheckboxes.forEach(checkbox => { checkbox.checked = isChecked; });
        }
        
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', toggleAllCheckboxes);
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
            if (!action) { alert('Please select a bulk action.'); return; }
            if (selected.length === 0) { alert('Please select at least one post.'); return; }
            let confirmMessage = '';
            if (action === 'delete') confirmMessage = `Are you sure you want to delete ${selected.length} post(s)? This action cannot be undone.`;
            else if (action === 'publish') confirmMessage = `Are you sure you want to publish ${selected.length} post(s)?`;
            else if (action === 'draft') confirmMessage = `Are you sure you want to move ${selected.length} post(s) to drafts?`;
            if (confirm(confirmMessage)) document.getElementById('bulkActionForm').submit();
        }
        
        function confirmDelete(postId) {
            if (confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
                window.location.href = '?delete=' + postId;
            }
        }
        
        setTimeout(() => {
            const alerts = document.querySelectorAll('.toast');
            alerts.forEach(alert => { alert.style.opacity = '0'; setTimeout(() => alert.remove(), 300); });
        }, 5000);
    </script>
</body>
</html>