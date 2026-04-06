<?php
/**
 * Daeteño Admin - Create Event Page
 */

require_once '../../dbconn.php';

// Check if user is logged in and is admin
if (!isLoggedIn()) {
    redirect('../../login.php');
}

if (!isAdmin()) {
    redirect('../../index.php');
}

$user_id = $_SESSION['user_id'];
$userName = $_SESSION['full_name'] ?? 'Administrator';

$formErrors = [];
$successMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_event') {
    // Validate required fields
    if (empty($_POST['title'])) {
        $formErrors['title'] = 'Event title is required';
    }
    if (empty($_POST['start_date'])) {
        $formErrors['start_date'] = 'Start date is required';
    }
    if (empty($_POST['end_date'])) {
        $formErrors['end_date'] = 'End date is required';
    }
    
    // Validate date range
    if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
        $startDate = new DateTime($_POST['start_date']);
        $endDate = new DateTime($_POST['end_date']);
        if ($endDate < $startDate) {
            $formErrors['date_range'] = 'End date must be after start date';
        }
    }
    
    // Validate status
    $validStatuses = ['upcoming', 'ongoing', 'completed', 'cancelled'];
    $status = $_POST['status'] ?? 'upcoming';
    if (!in_array($status, $validStatuses)) {
        $status = 'upcoming';
    }
    
    if (empty($formErrors)) {
        // Handle image upload if present - store in a separate uploads folder
        $imagePath = null;
        if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../uploads/events/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            $fileType = $_FILES['event_image']['type'];
            
            if (!in_array($fileType, $allowedTypes)) {
                $formErrors['image'] = 'Only JPG, PNG, and WEBP images are allowed';
            } elseif ($_FILES['event_image']['size'] > 2 * 1024 * 1024) {
                $formErrors['image'] = 'Image size must be less than 2MB';
            } else {
                $fileExtension = pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION);
                $fileName = uniqid() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['event_image']['tmp_name'], $uploadPath)) {
                    $imagePath = 'uploads/events/' . $fileName;
                }
            }
        }
        
        if (empty($formErrors)) {
            // Store additional info in description field (append if needed)
            $additionalInfo = [];
            if (!empty($_POST['location'])) {
                $additionalInfo['location'] = $_POST['location'];
            }
            if (!empty($_POST['event_type'])) {
                $additionalInfo['event_type'] = $_POST['event_type'];
            }
            if (!empty($_POST['organizer'])) {
                $additionalInfo['organizer'] = $_POST['organizer'];
            }
            if (!empty($_POST['contact'])) {
                $additionalInfo['contact'] = $_POST['contact'];
            }
            if (!empty($_POST['website'])) {
                $additionalInfo['website'] = $_POST['website'];
            }
            if ($imagePath) {
                $additionalInfo['image'] = $imagePath;
            }
            
            $fullDescription = $_POST['description'] ?? '';
            if (!empty($additionalInfo)) {
                $fullDescription .= "\n\n---\nAdditional Information:\n" . json_encode($additionalInfo, JSON_PRETTY_PRINT);
            }
            
            // Insert into database
            $result = query(
                "INSERT INTO info_events (title, description, start_date, end_date, status, created_by, created_at, updated_at) 
                 VALUES ($1, $2, $3, $4, $5, $6, NOW(), NOW())",
                [
                    $_POST['title'],
                    $fullDescription,
                    $_POST['start_date'],
                    $_POST['end_date'],
                    $status,
                    $user_id
                ]
            );
            
            if ($result) {
                $_SESSION['success_message'] = 'Event "' . htmlspecialchars($_POST['title']) . '" has been created successfully!';
                header('Location: index.php?created=1');
                exit;
            } else {
                $formErrors['database'] = 'Failed to create event. Please try again.';
            }
        }
    }
}

// Check for success message from session
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daeteño Admin - Create Event</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Admin Header -->
    <div class="bg-gradient-to-r from-purple-600 to-purple-800 text-white sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-4">
                <div class="flex items-center space-x-4">
                    <a href="../dashboard.php" class="flex items-center text-white/80 hover:text-white transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                    <div class="h-6 w-px bg-white/30"></div>
                    <a href="index.php" class="flex items-center text-white/80 hover:text-white transition-colors">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        All Events
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-purple-200">Welcome, <?php echo htmlspecialchars($userName); ?></span>
                    <div class="h-8 w-8 rounded-full bg-purple-500 flex items-center justify-center">
                        <i class="fas fa-user text-sm"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <?php if ($successMessage): ?>
    <div id="successToast" class="fixed top-20 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span><?php echo htmlspecialchars($successMessage); ?></span>
        </div>
    </div>
    <script>
        setTimeout(() => {
            const toast = document.getElementById('successToast');
            if (toast) toast.style.display = 'none';
        }, 3000);
    </script>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Create New Event</h1>
            <p class="text-gray-600">Add a new festival, activity, or event in Daet</p>
        </div>

        <?php if (!empty($formErrors)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center mb-2">
                <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
                <h3 class="text-red-800 font-medium">Please fix the following errors:</h3>
            </div>
            <ul class="list-disc list-inside text-sm text-red-700">
                <?php foreach ($formErrors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Event Details</h2>
            </div>

            <div class="p-6">
                <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="eventForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create_event">
                    <div class="space-y-6">
                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Event Title *</label>
                            <input type="text" name="title" id="title" required 
                                   class="w-full px-4 py-2 border <?php echo isset($formErrors['title']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                   placeholder="Enter event title"
                                   value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" id="description" rows="4"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                      placeholder="Describe the event..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>

                        <!-- Event Type & Location -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Event Type</label>
                                <select name="event_type" id="event_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                    <option value="">Select Type</option>
                                    <option value="festival" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'festival') ? 'selected' : ''; ?>>Festival</option>
                                    <option value="cultural" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'cultural') ? 'selected' : ''; ?>>Cultural</option>
                                    <option value="sports" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'sports') ? 'selected' : ''; ?>>Sports</option>
                                    <option value="music" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'music') ? 'selected' : ''; ?>>Music</option>
                                    <option value="food" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'food') ? 'selected' : ''; ?>>Food Fair</option>
                                    <option value="community" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'community') ? 'selected' : ''; ?>>Community</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                                <input type="text" name="location" id="location"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                       placeholder="Event venue or location"
                                       value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                            </div>
                        </div>

                        <!-- Start & End Dates -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Start Date & Time *</label>
                                <input type="datetime-local" name="start_date" id="start_date" required
                                       class="w-full px-4 py-2 border <?php echo isset($formErrors['start_date']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                       value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">End Date & Time *</label>
                                <input type="datetime-local" name="end_date" id="end_date" required
                                       class="w-full px-4 py-2 border <?php echo isset($formErrors['end_date']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                       value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>">
                            </div>
                        </div>
                        
                        <?php if (isset($formErrors['date_range'])): ?>
                            <p class="text-red-500 text-xs -mt-4"><?php echo $formErrors['date_range']; ?></p>
                        <?php endif; ?>

                        <!-- Event Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Event Status</label>
                            <div class="flex flex-wrap gap-6">
                                <label class="flex items-center">
                                    <input type="radio" name="status" value="upcoming" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'upcoming') ? 'checked' : ''; ?> class="text-purple-600 focus:ring-purple-500">
                                    <span class="ml-2 text-sm text-gray-600">Upcoming</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="status" value="ongoing" <?php echo (isset($_POST['status']) && $_POST['status'] === 'ongoing') ? 'checked' : ''; ?> class="text-purple-600 focus:ring-purple-500">
                                    <span class="ml-2 text-sm text-gray-600">Ongoing</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="status" value="completed" <?php echo (isset($_POST['status']) && $_POST['status'] === 'completed') ? 'checked' : ''; ?> class="text-purple-600 focus:ring-purple-500">
                                    <span class="ml-2 text-sm text-gray-600">Completed</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="status" value="cancelled" <?php echo (isset($_POST['status']) && $_POST['status'] === 'cancelled') ? 'checked' : ''; ?> class="text-purple-600 focus:ring-purple-500">
                                    <span class="ml-2 text-sm text-gray-600">Cancelled</span>
                                </label>
                            </div>
                        </div>

                        <!-- Event Image Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Event Image</label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-purple-400 transition-colors cursor-pointer" id="imageUploadArea">
                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-3"></i>
                                <p class="text-sm text-gray-600">Click or drag to upload event banner</p>
                                <p class="text-xs text-gray-400 mt-1">Recommended size: 1200x400px (Max 2MB)</p>
                                <p class="text-xs text-gray-400">Allowed formats: JPG, PNG, WEBP</p>
                                <input type="file" name="event_image" id="eventImageUpload" class="hidden" accept="image/jpeg,image/png,image/webp">
                            </div>
                            <div id="imagePreviewContainer" class="hidden mt-3 relative">
                                <img id="imagePreview" src="" alt="Preview" class="w-full h-48 object-cover rounded-lg">
                                <button type="button" id="removeImage" class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-md font-semibold text-gray-900 mb-4">Additional Information</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Organizer</label>
                                    <input type="text" name="organizer" id="organizer"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                           placeholder="Event organizer name"
                                           value="<?php echo isset($_POST['organizer']) ? htmlspecialchars($_POST['organizer']) : ''; ?>">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Contact Information</label>
                                    <input type="text" name="contact" id="contact"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                           placeholder="Phone or email"
                                           value="<?php echo isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : ''; ?>">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Website/Registration Link</label>
                                    <input type="url" name="website" id="website"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                           placeholder="https://example.com"
                                           value="<?php echo isset($_POST['website']) ? htmlspecialchars($_POST['website']) : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                            <a href="index.php" 
                               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                <i class="fas fa-calendar-plus mr-2"></i> Create Event
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Image Upload Preview
        const uploadArea = document.getElementById('imageUploadArea');
        const imageInput = document.getElementById('eventImageUpload');
        const previewContainer = document.getElementById('imagePreviewContainer');
        const imagePreview = document.getElementById('imagePreview');
        const removeImageBtn = document.getElementById('removeImage');

        if (uploadArea) {
            uploadArea.addEventListener('click', function() {
                imageInput.click();
            });

            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.classList.add('border-purple-500', 'bg-purple-50');
            });

            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('border-purple-500', 'bg-purple-50');
            });

            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('border-purple-500', 'bg-purple-50');
                const file = e.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    handleImageFile(file);
                }
            });

            imageInput.addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    handleImageFile(e.target.files[0]);
                }
            });

            function handleImageFile(file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('Image size must be less than 2MB');
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                    uploadArea.classList.add('hidden');
                };
                reader.readAsDataURL(file);
            }

            if (removeImageBtn) {
                removeImageBtn.addEventListener('click', function() {
                    imagePreview.src = '';
                    previewContainer.classList.add('hidden');
                    uploadArea.classList.remove('hidden');
                    imageInput.value = '';
                });
            }
        }

        // Form validation before submit
        const eventForm = document.getElementById('eventForm');
        if (eventForm) {
            eventForm.addEventListener('submit', function(e) {
                const startDate = new Date(document.getElementById('start_date').value);
                const endDate = new Date(document.getElementById('end_date').value);
                
                if (startDate && endDate && endDate < startDate) {
                    e.preventDefault();
                    alert('End date must be after start date');
                    return false;
                }
            });
        }
    </script>
</body>
</html>