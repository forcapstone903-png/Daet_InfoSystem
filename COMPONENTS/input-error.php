<?php
// input-error.php - Input Error Component
$messages = $messages ?? ($attributes['messages'] ?? []);
$class = $attributes['class'] ?? 'text-sm text-red-600 space-y-1';
?>

<?php if (!empty($messages)): ?>
    <ul class="<?php echo $class; ?>">
        <?php foreach ((array) $messages as $message): ?>
            <li><?php echo htmlspecialchars($message); ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>