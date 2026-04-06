<?php
// dropdown-link.php - Dropdown Link Component
$class = $attributes['class'] ?? 'block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out';
?>

<a <?php echo $attributes; ?> class="<?php echo $class; ?>">
    <?php echo $slot ?? ''; ?>
</a>