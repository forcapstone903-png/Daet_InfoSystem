<?php
// auth-session-status.php - Session Status Component
$status = $status ?? ($attributes['status'] ?? '');
$class = $attributes['class'] ?? 'font-medium text-sm text-green-600';
?>

<?php if ($status): ?>
    <div class="<?php echo $class; ?>">
        <?php echo htmlspecialchars($status); ?>
    </div>
<?php endif; ?>