<?php
// navigation.php
$isActive = function($route) use ($currentRoute) {
    return $currentRoute === $route ? 'border-green-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-green-300';
};
?>
<nav class="bg-white shadow-md" x-data="{ mobileMenuOpen: false }">
    <div class="max-w-7xl mx-auto px-4 flex justify-between h-16 items-center">
        <div class="flex items-center gap-4">
            <img src="logo.png" class="h-10" alt="Logo">
            <div class="hidden md:flex gap-6">
                <a href="index.php" class="<?php echo $isActive('home'); ?> border-b-2 pb-2">Home</a>
                <a href="spots.php" class="<?php echo $isActive('spots'); ?> border-b-2 pb-2">Tourist Spots</a>
                <a href="events.php" class="<?php echo $isActive('events'); ?> border-b-2 pb-2">Events</a>
                <a href="blog.php" class="<?php echo $isActive('blog'); ?> border-b-2 pb-2">Blog</a>
            </div>
        </div>
        <div class="hidden md:flex gap-4">
            <?php if(isset($_SESSION['user_id'])): ?>
                <span><?php echo $_SESSION['user_name']; ?></span>
                <a href="logout.php" class="text-red-500">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php" class="bg-yellow-400 px-4 py-1 rounded">Sign Up</a>
            <?php endif; ?>
        </div>
        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-2xl">☰</button>
    </div>
    <div x-show="mobileMenuOpen" class="md:hidden bg-white border-t p-4 flex flex-col gap-2">
        <a href="index.php">Home</a>
        <a href="spots.php">Tourist Spots</a>
        <a href="events.php">Events</a>
        <a href="blog.php">Blog</a>
    </div>
</nav>