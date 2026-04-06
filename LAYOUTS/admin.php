<?php
// admin.php - Admin Layout
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <title><?php echo $title ?? 'Admin - Daeteño'; ?></title>

    <!-- Tailwind + Fonts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .admin-container { display: flex; min-height: 100vh; }
        .admin-main { flex: 1; background: #f8fafc; }
        .admin-header { background: white; border-bottom: 1px solid #e2e8f0; }
        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
    </style>
    <?php echo $styles ?? ''; ?>
</head>
<body class="font-sans antialiased">
    <div class="admin-container">
        <?php include 'partials/admin-sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <div class="px-8 py-4 flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-semibold"><?php echo $pageTitle ?? 'Dashboard'; ?></h2>
                        <p class="text-sm text-gray-600"><?php echo $pageDescription ?? 'Administration panel'; ?></p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="p-2 rounded-full hover:bg-gray-100">
                                <i class="fas fa-bell"></i>
                                <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                            </button>
                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-80 bg-white shadow-lg rounded-lg z-50">
                                <div class="p-3 border-b">No new notifications</div>
                            </div>
                        </div>
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center">A</div>
                                <span><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white shadow-lg rounded-lg z-50">
                                <a href="profile.php" class="block px-4 py-2 hover:bg-gray-100">Profile</a>
                                <a href="logout.php" class="block px-4 py-2 hover:bg-gray-100">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            <main class="p-8">
                <?php echo $content ?? ''; ?>
            </main>
        </div>
    </div>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <?php echo $scripts ?? ''; ?>
</body>
</html>