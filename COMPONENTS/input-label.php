<?php
// input-label.php - Input Label Component
$value = $value ?? ($attributes['value'] ?? '');
$class = $attributes['class'] ?? 'block font-medium text-sm text-gray-700';
?>

<label <?php echo $attributes; ?> class="<?php echo $class; ?>">
    <?php echo !empty($value) ? htmlspecialchars($value) : ($slot ?? ''); ?>
</label>