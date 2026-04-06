<?php
// modal.php - Modal Component
$name = $name ?? ($attributes['name'] ?? 'modal');
$show = $show ?? false;
$maxWidth = $maxWidth ?? '2xl';

$maxWidthClass = match($maxWidth) {
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    default => 'sm:max-w-2xl',
};
?>

<div class="modal-container fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" 
     data-modal-name="<?php echo $name; ?>"
     style="display: <?php echo $show ? 'block' : 'none'; ?>;">
    
    <div class="modal-overlay fixed inset-0 transform transition-all">
        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
    </div>

    <div class="modal-content mb-6 bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full <?php echo $maxWidthClass; ?> sm:mx-auto">
        <?php echo $slot ?? ''; ?>
    </div>
</div>

<script>
class Modal {
    constructor(container) {
        this.container = container;
        this.overlay = container.querySelector('.modal-overlay');
        this.content = container.querySelector('.modal-content');
        this.name = container.dataset.modalName;
        
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        // Close on overlay click
        this.overlay.addEventListener('click', () => this.close());
        
        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen()) {
                this.close();
            }
        });
        
        // Trap focus
        this.container.addEventListener('keydown', (e) => {
            if (e.key === 'Tab' && this.isOpen()) {
                this.trapFocus(e);
            }
        });
    }
    
    open() {
        this.container.style.display = 'block';
        document.body.classList.add('overflow-y-hidden');
        this.overlay.classList.add('ease-out', 'duration-300');
        this.content.classList.add('ease-out', 'duration-300', 'opacity-100', 'translate-y-0', 'sm:scale-100');
        this.focusFirstElement();
    }
    
    close() {
        this.container.style.display = 'none';
        document.body.classList.remove('overflow-y-hidden');
    }
    
    isOpen() {
        return this.container.style.display === 'block';
    }
    
    focusFirstElement() {
        const focusable = this.container.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusable.length) focusable[0].focus();
    }
    
    trapFocus(e) {
        const focusable = this.container.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        
        if (e.shiftKey && document.activeElement === first) {
            last.focus();
            e.preventDefault();
        } else if (!e.shiftKey && document.activeElement === last) {
            first.focus();
            e.preventDefault();
        }
    }
}

// Initialize modals
document.querySelectorAll('.modal-container').forEach(container => {
    new Modal(container);
});

// Global modal control functions
window.openModal = function(name) {
    const modal = document.querySelector(`.modal-container[data-modal-name="${name}"]`);
    if (modal && modal.__modal) modal.__modal.open();
};

window.closeModal = function(name) {
    const modal = document.querySelector(`.modal-container[data-modal-name="${name}"]`);
    if (modal && modal.__modal) modal.__modal.close();
};
</script>

<style>
.modal-content {
    transition: all 0.3s ease;
}
.modal-container[style*="display: block"] .modal-overlay {
    animation: modalFadeIn 0.3s ease-out;
}
.modal-container[style*="display: block"] .modal-content {
    animation: modalSlideIn 0.3s ease-out;
}
@keyframes modalFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
</style>