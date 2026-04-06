<?php
// File: BLOG/create.php
// Blog Post Creation/Editor Page

require_once '../../dbconn.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../../login.php');
}

$user_id = $_SESSION['user_id']; // This should be UUID from your session
$userName = $_SESSION['full_name'] ?? 'Administrator';
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $title = trim($_POST['post_title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $category = $_POST['category'] ?? '';
    $tags = trim($_POST['tags'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $publish_date = !empty($_POST['publish_date']) ? $_POST['publish_date'] : null;
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    
    // Validate required fields
    if (empty($title) || empty($content)) {
        $error_message = 'Title and content are required fields.';
    } else {
        // Handle image upload
        $featured_image = null;
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/blog/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $filename = time() . '_' . uniqid() . '.' . $file_extension;
                $target_path = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $target_path)) {
                    $featured_image = 'uploads/blog/' . $filename;
                } else {
                    $error_message = 'Failed to upload image. Please check directory permissions.';
                }
            } else {
                $error_message = 'Invalid file type. Allowed: JPG, PNG, WebP, GIF';
            }
        }
        
        if (empty($error_message)) {
            // Insert into database with UUID
            $result = query(
                "INSERT INTO info_blog_posts (user_id, title, content, excerpt, category, tags, featured_image, 
                 status, publish_date, meta_title, meta_description, created_at, updated_at) 
                 VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, NOW(), NOW())",
                [
                    $user_id, $title, $content, $excerpt, $category, $tags, 
                    $featured_image, $status, $publish_date, $meta_title, $meta_description
                ]
            );
            
            if ($result) {
                if ($action === 'save_draft') {
                    $success_message = 'Blog post saved as draft successfully!';
                } else {
                    $success_message = 'Blog post published successfully!';
                }
                // Clear form on success
                $_POST = [];
                // Reset file input
                $_FILES = [];
            } else {
                $error_message = 'Failed to save blog post. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write Blog Post - Daeteño Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .editor-toolbar button:hover { background-color: #e5e7eb; }
        textarea { resize: vertical; }
        .image-drop-zone { transition: all 0.2s ease; }
        .image-drop-zone:hover { background-color: #f9fafb; border-color: #10b981; }
        .toast { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <div class="min-h-screen bg-gray-50">
        <!-- Admin Header -->
        <div class="bg-gradient-to-r from-green-600 to-green-800 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between py-4">
                    <div class="flex items-center space-x-4">
                        <a href="BLOG/index.php" class="flex items-center text-white/80 hover:text-white transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Blog Manager
                        </a>
                        <div class="h-6 w-px bg-white/30"></div>
                        <a href="../dashboard.php" class="flex items-center text-white/80 hover:text-white transition-colors">
                            <i class="fas fa-tachometer-alt mr-2"></i>
                            Admin Dashboard
                        </a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-green-200">Write Blog Post</span>
                        <div class="h-6 w-px bg-white/30"></div>
                        <span class="text-sm"><i class="fas fa-user-circle mr-1"></i> <?php echo htmlspecialchars($userName); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
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

            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Write New Blog Post</h1>
                <p class="text-gray-600">Share travel stories, tips, and news about Daet</p>
            </div>

            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">Post Editor</h2>
                    <p class="text-sm text-gray-500 mt-1">Fill in the details below to create your blog post</p>
                </div>

                <div class="p-6">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" id="formAction" value="publish_post">
                        
                        <div class="space-y-6">
                            <!-- Title -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Post Title <span class="text-red-500">*</span></label>
                                <input type="text" name="post_title" required 
                                       value="<?php echo htmlspecialchars($_POST['post_title'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                       placeholder="Enter compelling title...">
                                <p class="text-xs text-gray-500 mt-1">60 characters recommended for SEO</p>
                            </div>

                            <!-- Category & Author -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                    <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                        <option value="">Select Category</option>
                                        <option value="travel" <?php echo (($_POST['category'] ?? '') === 'travel') ? 'selected' : ''; ?>>✈️ Travel Tips</option>
                                        <option value="food" <?php echo (($_POST['category'] ?? '') === 'food') ? 'selected' : ''; ?>>🍽️ Food & Dining</option>
                                        <option value="culture" <?php echo (($_POST['category'] ?? '') === 'culture') ? 'selected' : ''; ?>>🏛️ Culture & History</option>
                                        <option value="events" <?php echo (($_POST['category'] ?? '') === 'events') ? 'selected' : ''; ?>>🎉 Events & Festivals</option>
                                        <option value="accommodation" <?php echo (($_POST['category'] ?? '') === 'accommodation') ? 'selected' : ''; ?>>🏨 Accommodation</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Author</label>
                                    <input type="text" value="<?php echo htmlspecialchars($userName); ?>" readonly
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                                </div>
                            </div>

                            <!-- Featured Image -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Featured Image</label>
                                <div class="image-drop-zone border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:bg-gray-50 transition cursor-pointer" id="imageDropZone" onclick="document.getElementById('featuredImageUpload').click()">
                                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                    <p class="text-sm text-gray-600">Click to upload or drag and drop</p>
                                    <p class="text-xs text-gray-400 mt-1">Recommended size: 1200x600px (Max 5MB)</p>
                                    <p class="text-xs text-gray-400">Allowed formats: JPG, PNG, WebP, GIF</p>
                                    <input type="file" name="featured_image" id="featuredImageUpload" class="hidden" accept="image/jpeg,image/png,image/webp,image/gif">
                                </div>
                                <div id="imagePreview" class="mt-3 hidden"></div>
                            </div>

                            <!-- Excerpt -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Excerpt</label>
                                <textarea name="excerpt" rows="3"
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                                          placeholder="Brief summary of the post (appears in listings)"><?php echo htmlspecialchars($_POST['excerpt'] ?? ''); ?></textarea>
                                <p class="text-xs text-gray-500 mt-1">A short summary (150-160 characters recommended for SEO)</p>
                            </div>

                            <!-- Content with Rich Text Editor -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Content <span class="text-red-500">*</span></label>
                                <div class="border border-gray-300 rounded-lg overflow-hidden">
                                    <div class="editor-toolbar flex flex-wrap items-center gap-1 p-2 border-b border-gray-200 bg-gray-50">
                                        <button type="button" class="p-2 rounded hover:bg-gray-200 transition" data-command="bold" title="Bold (Ctrl+B)"><i class="fas fa-bold"></i></button>
                                        <button type="button" class="p-2 rounded hover:bg-gray-200 transition" data-command="italic" title="Italic (Ctrl+I)"><i class="fas fa-italic"></i></button>
                                        <button type="button" class="p-2 rounded hover:bg-gray-200 transition" data-command="underline" title="Underline (Ctrl+U)"><i class="fas fa-underline"></i></button>
                                        <div class="w-px h-6 bg-gray-300 mx-1"></div>
                                        <button type="button" class="p-2 rounded hover:bg-gray-200 transition" data-command="insertUnorderedList" title="Bullet List"><i class="fas fa-list-ul"></i></button>
                                        <button type="button" class="p-2 rounded hover:bg-gray-200 transition" data-command="insertOrderedList" title="Numbered List"><i class="fas fa-list-ol"></i></button>
                                        <div class="w-px h-6 bg-gray-300 mx-1"></div>
                                        <button type="button" class="p-2 rounded hover:bg-gray-200 transition" data-command="createLink" title="Insert Link"><i class="fas fa-link"></i></button>
                                        <button type="button" class="p-2 rounded hover:bg-gray-200 transition" onclick="insertImage()" title="Insert Image"><i class="fas fa-image"></i></button>
                                        <div class="w-px h-6 bg-gray-300 mx-1"></div>
                                        <button type="button" class="p-2 rounded hover:bg-gray-200 transition" onclick="insertHeading()" title="Heading"><i class="fas fa-heading"></i></button>
                                    </div>
                                    <textarea name="content" id="editorTextarea" rows="15" required
                                              class="w-full px-4 py-3 focus:outline-none resize-none font-mono text-sm"
                                              placeholder="Start writing your blog post here... Use markdown formatting:&#10;&#10;**bold text**&#10;*italic text*&#10;- bullet points&#10;1. numbered lists&#10;[link text](url)&#10;![alt text](image-url)"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">💡 Tip: You can use markdown formatting for rich text content</p>
                            </div>

                            <!-- Tags -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                                <input type="text" name="tags" value="<?php echo htmlspecialchars($_POST['tags'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                                       placeholder="Separate tags with commas (e.g., travel, daet, beach)">
                                <p class="text-xs text-gray-500 mt-1">Helps users find related content</p>
                            </div>

                            <!-- Publish Settings -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Publish Status</label>
                                    <select name="status" id="publishStatus" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                        <option value="draft" <?php echo (($_POST['status'] ?? '') === 'draft') ? 'selected' : ''; ?>>📝 Draft</option>
                                        <option value="published" <?php echo (($_POST['status'] ?? '') === 'published') ? 'selected' : ''; ?>>🚀 Published</option>
                                        <option value="scheduled" <?php echo (($_POST['status'] ?? '') === 'scheduled') ? 'selected' : ''; ?>>📅 Scheduled</option>
                                    </select>
                                </div>
                                <div id="publishDateDiv" style="<?php echo (($_POST['status'] ?? '') === 'scheduled') ? '' : 'display: none;'; ?>">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Publish Date (for scheduled posts)</label>
                                    <input type="datetime-local" name="publish_date" value="<?php echo htmlspecialchars($_POST['publish_date'] ?? ''); ?>"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                </div>
                            </div>

                            <!-- SEO Settings (Collapsible) -->
                            <div>
                                <button type="button" onclick="toggleSEO()" class="flex items-center justify-between w-full px-4 py-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                    <span class="font-medium text-gray-700"><i class="fas fa-chart-line mr-2"></i> SEO Settings (Optional)</span>
                                    <i class="fas fa-chevron-down" id="seoIcon"></i>
                                </button>
                                <div id="seoSettings" class="hidden mt-4 space-y-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Meta Title</label>
                                        <input type="text" name="meta_title" value="<?php echo htmlspecialchars($_POST['meta_title'] ?? ''); ?>"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                                               placeholder="SEO title (optional, defaults to post title)">
                                        <p class="text-xs text-gray-500 mt-1">60 characters recommended</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Meta Description</label>
                                        <textarea name="meta_description" rows="2"
                                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                                                  placeholder="Brief description for search engines"><?php echo htmlspecialchars($_POST['meta_description'] ?? ''); ?></textarea>
                                        <p class="text-xs text-gray-500 mt-1">150-160 characters recommended</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                                <button type="button" onclick="submitForm('save_draft')" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    <i class="fas fa-save mr-2"></i> Save as Draft
                                </button>
                                <button type="button" onclick="submitForm('publish_post')" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                                    <i class="fas fa-paper-plane mr-2"></i> Publish Post
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Image preview
        const imageUpload = document.getElementById('featuredImageUpload');
        const imagePreviewDiv = document.getElementById('imagePreview');
        
        if (imageUpload) {
            imageUpload.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validate file size (max 5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('File is too large. Maximum size is 5MB.');
                        this.value = '';
                        return;
                    }
                    
                    // Validate file type
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                    if (!allowedTypes.includes(file.type)) {
                        alert('Please select an image file (JPEG, PNG, WebP, or GIF).');
                        this.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        imagePreviewDiv.innerHTML = `
                            <div class="relative inline-block">
                                <img src="${event.target.result}" class="max-h-48 rounded-lg border shadow-sm" alt="Preview">
                                <button type="button" onclick="clearImagePreview()" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                        `;
                        imagePreviewDiv.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
        
        function clearImagePreview() {
            imagePreviewDiv.innerHTML = '';
            imagePreviewDiv.classList.add('hidden');
            if(imageUpload) imageUpload.value = '';
        }
        
        // Show/hide publish date based on status
        const publishStatus = document.getElementById('publishStatus');
        const publishDateDiv = document.getElementById('publishDateDiv');
        
        if (publishStatus) {
            publishStatus.addEventListener('change', function() {
                if (this.value === 'scheduled') {
                    publishDateDiv.style.display = 'block';
                } else {
                    publishDateDiv.style.display = 'none';
                }
            });
        }
        
        // Rich text editor commands (using markdown)
        const editorTextarea = document.getElementById('editorTextarea');
        
        function wrapText(before, after = '') {
            const start = editorTextarea.selectionStart;
            const end = editorTextarea.selectionEnd;
            const selectedText = editorTextarea.value.substring(start, end);
            const wrappedText = before + selectedText + after;
            editorTextarea.value = editorTextarea.value.substring(0, start) + wrappedText + editorTextarea.value.substring(end);
            editorTextarea.focus();
            editorTextarea.selectionStart = start + before.length;
            editorTextarea.selectionEnd = start + before.length + selectedText.length;
        }
        
        document.querySelectorAll('.editor-toolbar button[data-command]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const command = this.getAttribute('data-command');
                
                switch(command) {
                    case 'bold':
                        wrapText('**', '**');
                        break;
                    case 'italic':
                        wrapText('*', '*');
                        break;
                    case 'underline':
                        wrapText('<u>', '</u>');
                        break;
                    case 'insertUnorderedList':
                        wrapText('\n- ');
                        break;
                    case 'insertOrderedList':
                        wrapText('\n1. ');
                        break;
                    case 'createLink':
                        const url = prompt('Enter link URL:', 'https://');
                        if (url) {
                            const selectedText = editorTextarea.value.substring(editorTextarea.selectionStart, editorTextarea.selectionEnd);
                            wrapText(`[${selectedText || 'link text'}](${url})`, '');
                        }
                        break;
                    default:
                        break;
                }
            });
        });
        
        function insertImage() {
            const url = prompt('Enter image URL:', 'https://');
            if (url) {
                const altText = prompt('Enter alt text for the image:', 'image description');
                wrapText(`![${altText || 'image'}](${url})`, '');
            }
        }
        
        function insertHeading() {
            const level = prompt('Heading level (1-6):', '2');
            if (level && level >= 1 && level <= 6) {
                wrapText(`\n${'#'.repeat(level)} `, '\n');
            }
        }
        
        // Toggle SEO settings
        function toggleSEO() {
            const seoSettings = document.getElementById('seoSettings');
            const icon = document.getElementById('seoIcon');
            if (seoSettings.classList.contains('hidden')) {
                seoSettings.classList.remove('hidden');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                seoSettings.classList.add('hidden');
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }
        
        // Form submission
        function submitForm(action) {
            const title = document.querySelector('input[name="post_title"]').value.trim();
            const content = editorTextarea.value.trim();
            
            if (!title) {
                alert('Please enter a post title.');
                document.querySelector('input[name="post_title"]').focus();
                return;
            }
            
            if (!content) {
                alert('Please enter post content.');
                editorTextarea.focus();
                return;
            }
            
            if (confirm('Are you sure you want to ' + (action === 'save_draft' ? 'save this as a draft?' : 'publish this post?'))) {
                document.getElementById('formAction').value = action;
                document.querySelector('form').submit();
            }
        }
        
        // Keyboard shortcuts
        editorTextarea.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'b':
                        e.preventDefault();
                        wrapText('**', '**');
                        break;
                    case 'i':
                        e.preventDefault();
                        wrapText('*', '*');
                        break;
                    case 'u':
                        e.preventDefault();
                        wrapText('<u>', '</u>');
                        break;
                }
            }
        });
        
        // Auto-resize textarea
        editorTextarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 500) + 'px';
        });
        
        // Drag and drop for image upload
        const dropZone = document.getElementById('imageDropZone');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropZone.classList.add('border-green-500', 'bg-green-50');
        }
        
        function unhighlight() {
            dropZone.classList.remove('border-green-500', 'bg-green-50');
        }
        
        dropZone.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                imageUpload.files = files;
                const event = new Event('change');
                imageUpload.dispatchEvent(event);
            }
        });
    </script>
</body>
</html>