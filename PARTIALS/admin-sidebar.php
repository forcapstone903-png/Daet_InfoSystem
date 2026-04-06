<?php
// admin-sidebar.php - Admin Sidebar Component
// This file should be included in admin.php layout

// Simulate data for unread messages count (in real app, fetch from database)
$unreadCount = 3; // This would come from: ContactMessage::where('status', 'unread')->count()

// Determine current route (simplified for PHP)
$currentRoute = $_GET['route'] ?? 'admin.dashboard';
$isActive = function($route) use ($currentRoute) {
    if (strpos($currentRoute, $route) === 0) {
        return 'bg-blue-600';
    }
    return 'hover:bg-gray-800';
};
?>

<aside class="w-64 bg-gray-900 text-white min-h-screen">
    <div class="p-6">
        <div class="flex items-center space-x-3 mb-8">
            <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                <i class="fas fa-shield-alt text-white"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold">Daeteño Admin</h1>
                <p class="text-gray-400 text-sm">Administration Panel</p>
            </div>
        </div>
        
        <nav class="space-y-2">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Main</p>
                <a href="admin.php?route=admin.dashboard" 
                   class="<?php echo $isActive('admin.dashboard'); ?> flex items-center px-4 py-3 rounded-lg transition-colors">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    Dashboard
                </a>
            </div>
            
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Content</p>
                <a href="admin-tourist-spots.php?route=admin.tourist-spots.index" 
                   class="<?php echo $isActive('admin.tourist-spots'); ?> flex items-center px-4 py-3 rounded-lg transition-colors">
                    <i class="fas fa-umbrella-beach mr-3"></i>
                    Tourist Spots
                </a>
                <a href="admin-events.php?route=admin.events.index" 
                   class="<?php echo $isActive('admin.events'); ?> flex items-center px-4 py-3 rounded-lg transition-colors">
                    <i class="fas fa-calendar-alt mr-3"></i>
                    Events
                </a>
                <a href="admin-blog.php?route=admin.blog-posts.index" 
                   class="<?php echo $isActive('admin.blog-posts'); ?> flex items-center px-4 py-3 rounded-lg transition-colors">
                    <i class="fas fa-blog mr-3"></i>
                    Blog Posts
                </a>
            </div>
            
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Bookings & Users</p>
                <a href="admin-bookings.php?route=admin.bookings.index" 
                   class="<?php echo $isActive('admin.bookings'); ?> flex items-center px-4 py-3 rounded-lg transition-colors">
                    <i class="fas fa-calendar-check mr-3"></i>
                    Bookings
                </a>
                <a href="admin-reviews.php?route=admin.reviews.index" 
                   class="<?php echo $isActive('admin.reviews'); ?> flex items-center px-4 py-3 rounded-lg transition-colors">
                    <i class="fas fa-star mr-3"></i>
                    Reviews
                </a>
                <a href="admin-messages.php?route=admin.messages.index" 
                   class="<?php echo $isActive('admin.messages'); ?> flex items-center px-4 py-3 rounded-lg transition-colors">
                    <i class="fas fa-envelope mr-3"></i>
                    Messages
                    <?php if($unreadCount > 0): ?>
                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Settings</p>
                <a href="admin-users.php?route=admin.users.index" 
                   class="<?php echo $isActive('admin.users'); ?> flex items-center px-4 py-3 rounded-lg transition-colors">
                    <i class="fas fa-users mr-3"></i>
                    Users
                </a>
                <a href="admin-settings.php?route=admin.settings.index" 
                   class="<?php echo $isActive('admin.settings'); ?> flex items-center px-4 py-3 rounded-lg transition-colors">
                    <i class="fas fa-cog mr-3"></i>
                    Settings
                </a>
            </div>
            
            <div class="pt-8 border-t border-gray-800">
                <a href="index.php" 
                   class="flex items-center px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-colors">
                    <i class="fas fa-home mr-3"></i>
                    Back to Site
                </a>
                <form method="POST" action="logout.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <button type="submit" 
                            class="w-full flex items-center px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-colors text-left">
                        <i class="fas fa-sign-out-alt mr-3"></i>
                        Logout
                    </button>
                </form>
            </div>
        </nav>
    </div>
</aside>

<style>
    /* Additional styles for sidebar animations */
    .transition-colors {
        transition-property: background-color, color;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 150ms;
    }
    
    .rounded-lg {
        border-radius: 0.5rem;
    }
    
    .hover\:bg-gray-800:hover {
        background-color: #1f2937;
    }
    
    .bg-blue-600 {
        background-color: #2563eb;
    }
    
    .border-t {
        border-top-width: 1px;
    }
    
    .border-gray-800 {
        border-color: #1f2937;
    }
</style>

<script>
    // Optional: Add active state tracking for sidebar links
    document.addEventListener('DOMContentLoaded', function() {
        const currentUrl = window.location.pathname;
        const sidebarLinks = document.querySelectorAll('aside nav a');
        
        sidebarLinks.forEach(link => {
            // Check if link href matches current URL
            if (link.getAttribute('href') === currentUrl.split('/').pop()) {
                link.classList.remove('hover:bg-gray-800');
                link.classList.add('bg-blue-600');
            }
        });
        
        // Add tooltip for sidebar items on hover (optional)
        const navItems = document.querySelectorAll('aside nav a');
        navItems.forEach(item => {
            item.addEventListener('mouseenter', function() {
                const text = this.querySelector('.fas + span')?.innerText || '';
                if (window.innerWidth < 768) {
                    // For mobile, show tooltip
                    let tooltip = document.createElement('span');
                    tooltip.innerText = text;
                    tooltip.className = 'absolute left-16 bg-gray-800 text-white text-xs px-2 py-1 rounded z-50';
                    tooltip.style.whiteSpace = 'nowrap';
                    this.appendChild(tooltip);
                    setTimeout(() => tooltip.remove(), 1000);
                }
            });
        });
    });
</script>