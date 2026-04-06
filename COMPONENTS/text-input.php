<?php
// text-input.php - Text Input Component
$disabled = $disabled ?? false;
$class = $class ?? 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm';
?>

<input <?php echo $disabled ? 'disabled' : ''; ?> <?php echo $attributes; ?> class="<?php echo $class; ?>">