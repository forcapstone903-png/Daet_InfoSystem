<?php
// app.php - Main Frontend Layout
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title ?? 'Daeteño - Tourist Guide'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #27ae60; --secondary: #f1c40f; }
        .hover-lift:hover { transform: translateY(-4px); transition: all 0.2s; }
        .btn-primary { background: #27ae60; color: white; padding: 8px 16px; border-radius: 8px; }
        .btn-primary:hover { background: #2ecc71; }
    </style>
    <?php echo $styles ?? ''; ?>
</head>
<body class="bg-gray-50 font-sans">
    <?php include 'navigation.php'; ?>
    <main class="max-w-7xl mx-auto px-4 py-8">
        <?php echo $content ?? ''; ?>
    </main>
    <?php include 'footer.php'; ?>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <?php echo $scripts ?? ''; ?>
</body>
</html>