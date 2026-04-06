<?php
// contact.php - Contact Us Page
session_start();

// Simulate CSRF token (in real app, generate and store in session)
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid security token. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        $errors = [];
        if (empty($name)) $errors[] = 'Name is required';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
        if (empty($subject)) $errors[] = 'Subject is required';
        if (empty($message)) $errors[] = 'Message is required';
        
        if (empty($errors)) {
            // In a real application, send email or save to database here
            // For demo, we'll just set a success message
            $_SESSION['success'] = 'Thank you for contacting us! We will get back to you soon.';
            
            // Clear form data after successful submission
            $_POST = [];
        } else {
            $_SESSION['error'] = implode(', ', $errors);
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: contact.php');
    exit;
}

// Include main layout
$title = 'Contact Us - Daeteño';
$content = ob_get_clean();
ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Contact Us</h1>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Have questions, suggestions, or need assistance? We're here to help you explore Daet better.
        </p>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Contact Form -->
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Send us a Message</h2>
            
            <?php if(isset($_SESSION['success'])): ?>
            <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-lg" id="successMessage">
                <i class="fas fa-check-circle mr-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
            <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-lg" id="errorMessage">
                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
            <?php endif; ?>
            
            <form action="contact.php" method="POST" id="contactForm">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <div class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Your Name</label>
                        <input type="text" id="name" name="name" required
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <div class="text-red-500 text-sm mt-1 hidden" id="nameError">Please enter your name</div>
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <div class="text-red-500 text-sm mt-1 hidden" id="emailError">Please enter a valid email address</div>
                    </div>
                    
                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                        <input type="text" id="subject" name="subject" required
                               value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <div class="text-red-500 text-sm mt-1 hidden" id="subjectError">Please enter a subject</div>
                    </div>
                    
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                        <textarea id="message" name="message" rows="6" required
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        <div class="text-red-500 text-sm mt-1 hidden" id="messageError">Please enter your message</div>
                    </div>
                    
                    <div>
                        <button type="submit" 
                                class="w-full py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-paper-plane mr-2"></i> Send Message
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Contact Information -->
        <div>
            <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Get in Touch</h2>
                <div class="space-y-6">
                    <div class="flex items-start">
                        <div class="h-12 w-12 rounded-lg bg-white flex items-center justify-center mr-4 shadow-sm">
                            <i class="fas fa-map-marker-alt text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-1">Visit Us</h3>
                            <p class="text-gray-600">Daet, Camarines Norte<br>Philippines 4600</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="h-12 w-12 rounded-lg bg-white flex items-center justify-center mr-4 shadow-sm">
                            <i class="fas fa-phone text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-1">Call Us</h3>
                            <p class="text-gray-600">+63 (54) 123-4567<br>Mon-Fri, 8:00 AM - 5:00 PM</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="h-12 w-12 rounded-lg bg-white flex items-center justify-center mr-4 shadow-sm">
                            <i class="fas fa-envelope text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-1">Email Us</h3>
                            <p class="text-gray-600">info@daetenio.ph<br>support@daetenio.ph</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- FAQ -->
            <div class="bg-white rounded-xl shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Frequently Asked Questions</h2>
                <div class="space-y-4">
                    <div class="faq-item">
                        <button class="faq-question w-full text-left font-semibold text-gray-900 py-2 flex justify-between items-center hover:text-blue-600 transition-colors">
                            How do I book a tourist spot?
                            <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                        </button>
                        <div class="faq-answer hidden mt-2 text-gray-600 text-sm pl-4 border-l-2 border-blue-200">
                            Simply create an account, browse tourist spots, and click "Book Now" on the spot's detail page.
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <button class="faq-question w-full text-left font-semibold text-gray-900 py-2 flex justify-between items-center hover:text-blue-600 transition-colors">
                            Are events free to join?
                            <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                        </button>
                        <div class="faq-answer hidden mt-2 text-gray-600 text-sm pl-4 border-l-2 border-blue-200">
                            Some events are free while others may have ticket fees. Check the event details for pricing information.
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <button class="faq-question w-full text-left font-semibold text-gray-900 py-2 flex justify-between items-center hover:text-blue-600 transition-colors">
                            Can I cancel my booking?
                            <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                        </button>
                        <div class="faq-answer hidden mt-2 text-gray-600 text-sm pl-4 border-l-2 border-blue-200">
                            Yes, you can cancel bookings from your dashboard up to 24 hours before the scheduled date.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-hide flash messages after 5 seconds
    setTimeout(function() {
        const successMsg = document.getElementById('successMessage');
        const errorMsg = document.getElementById('errorMessage');
        if (successMsg) successMsg.style.display = 'none';
        if (errorMsg) errorMsg.style.display = 'none';
    }, 5000);
    
    // FAQ Accordion functionality
    document.querySelectorAll('.faq-question').forEach(button => {
        button.addEventListener('click', () => {
            const answer = button.nextElementSibling;
            const icon = button.querySelector('i');
            
            // Close other FAQs
            document.querySelectorAll('.faq-answer').forEach(otherAnswer => {
                if (otherAnswer !== answer && !otherAnswer.classList.contains('hidden')) {
                    otherAnswer.classList.add('hidden');
                    const otherIcon = otherAnswer.previousElementSibling.querySelector('i');
                    if (otherIcon) otherIcon.style.transform = 'rotate(0deg)';
                }
            });
            
            // Toggle current FAQ
            answer.classList.toggle('hidden');
            if (icon) {
                icon.style.transform = answer.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
            }
        });
    });
    
    // Form validation
    document.getElementById('contactForm')?.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Name validation
        const name = document.getElementById('name');
        const nameError = document.getElementById('nameError');
        if (!name.value.trim()) {
            nameError.classList.remove('hidden');
            name.classList.add('border-red-500');
            isValid = false;
        } else {
            nameError.classList.add('hidden');
            name.classList.remove('border-red-500');
        }
        
        // Email validation
        const email = document.getElementById('email');
        const emailError = document.getElementById('emailError');
        const emailRegex = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
        if (!email.value.trim() || !emailRegex.test(email.value)) {
            emailError.classList.remove('hidden');
            email.classList.add('border-red-500');
            isValid = false;
        } else {
            emailError.classList.add('hidden');
            email.classList.remove('border-red-500');
        }
        
        // Subject validation
        const subject = document.getElementById('subject');
        const subjectError = document.getElementById('subjectError');
        if (!subject.value.trim()) {
            subjectError.classList.remove('hidden');
            subject.classList.add('border-red-500');
            isValid = false;
        } else {
            subjectError.classList.add('hidden');
            subject.classList.remove('border-red-500');
        }
        
        // Message validation
        const message = document.getElementById('message');
        const messageError = document.getElementById('messageError');
        if (!message.value.trim()) {
            messageError.classList.remove('hidden');
            message.classList.add('border-red-500');
            isValid = false;
        } else {
            messageError.classList.add('hidden');
            message.classList.remove('border-red-500');
        }
        
        if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            const firstError = document.querySelector('.border-red-500');
            if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
    
    // Real-time validation on input
    const inputs = ['name', 'email', 'subject', 'message'];
    inputs.forEach(field => {
        const input = document.getElementById(field);
        if (input) {
            input.addEventListener('input', function() {
                const errorEl = document.getElementById(field + 'Error');
                if (errorEl && this.value.trim()) {
                    errorEl.classList.add('hidden');
                    this.classList.remove('border-red-500');
                }
            });
        }
    });
</script>

<?php
// Capture content and include layout
$content = ob_get_clean();

// If you have a separate layout file, include it here
// For standalone page, we'll output directly with a basic layout
if (file_exists('layouts/app.php')) {
    include 'layouts/app.php';
} else {
    // Fallback minimal layout
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title; ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Inter', sans-serif; }
        </style>
    </head>
    <body class="bg-gray-50">
        <?php echo $content; ?>
    </body>
    </html>
    <?php
}
?>