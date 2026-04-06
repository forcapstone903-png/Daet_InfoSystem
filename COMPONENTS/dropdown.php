<?php
// dropdown.php - Dropdown Component
$align = $align ?? 'right';
$width = $width ?? '48';
$contentClasses = $contentClasses ?? 'py-1 bg-white';

$alignmentClasses = match($align) {
    'left' => 'ltr:origin-top-left rtl:origin-top-right start-0',
    'top' => 'origin-top',
    default => 'ltr:origin-top-right rtl:origin-top-left end-0',
};

$widthClass = match($width) {
    '48' => 'w-48',
    default => $width,
};
?>

<div class="relative dropdown-container" x-data="{ open: false }">
    <div class="dropdown-trigger" onclick="toggleDropdown(this)">
        <?php echo $trigger ?? ''; ?>
    </div>

    <div class="dropdown-menu absolute z-50 mt-2 <?php echo $widthClass; ?> rounded-md shadow-lg <?php echo $alignmentClasses; ?>" style="display: none;">
        <div class="rounded-md ring-1 ring-black ring-opacity-5 <?php echo $contentClasses; ?>">
            <?php echo $content ?? ''; ?>
        </div>
    </div>
</div>

<script>
function toggleDropdown(trigger) {
    const container = trigger.closest('.dropdown-container');
    const menu = container.querySelector('.dropdown-menu');
    const isOpen = menu.style.display === 'block';
    
    // Close all other dropdowns
    document.querySelectorAll('.dropdown-menu').forEach(m => {
        if (m !== menu) m.style.display = 'none';
    });
    
    menu.style.display = isOpen ? 'none' : 'block';
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown-container')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.style.display = 'none';
        });
    }
});
</script>

<style>
.dropdown-menu {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.dropdown-menu[style*="display: block"] {
    animation: dropdownFadeIn 0.2s ease-out;
}
@keyframes dropdownFadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>