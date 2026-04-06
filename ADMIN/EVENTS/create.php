<?php
require_once '../../dbconn.php';

if (!isLoggedIn()) redirect('../../login.php');
if (!isAdmin()) redirect('../../index.php');

$user_id = $_SESSION['user_id'];
$userName = $_SESSION['full_name'] ?? 'Administrator';
$formErrors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_event') {
    if (empty($_POST['title'])) $formErrors['title'] = 'Event title is required';
    if (empty($_POST['start_date'])) $formErrors['start_date'] = 'Start date is required';
    if (empty($_POST['end_date'])) $formErrors['end_date'] = 'End date is required';
    if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
        $startDate = new DateTime($_POST['start_date']);
        $endDate = new DateTime($_POST['end_date']);
        if ($endDate < $startDate) $formErrors['date_range'] = 'End date must be after start date';
    }
    $validStatuses = ['upcoming', 'ongoing', 'completed', 'cancelled'];
    $status = $_POST['status'] ?? 'upcoming';
    if (!in_array($status, $validStatuses)) $status = 'upcoming';
    
    if (empty($formErrors)) {
        $imagePath = null;
        if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../uploads/events/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            $fileType = $_FILES['event_image']['type'];
            if (!in_array($fileType, $allowedTypes)) $formErrors['image'] = 'Only JPG, PNG, and WEBP images are allowed';
            elseif ($_FILES['event_image']['size'] > 2 * 1024 * 1024) $formErrors['image'] = 'Image size must be less than 2MB';
            else {
                $fileExtension = pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION);
                $fileName = uniqid() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['event_image']['tmp_name'], $uploadPath)) $imagePath = 'uploads/events/' . $fileName;
            }
        }
        if (empty($formErrors)) {
            $additionalInfo = [];
            if (!empty($_POST['location'])) $additionalInfo['location'] = $_POST['location'];
            if (!empty($_POST['event_type'])) $additionalInfo['event_type'] = $_POST['event_type'];
            if (!empty($_POST['organizer'])) $additionalInfo['organizer'] = $_POST['organizer'];
            if (!empty($_POST['contact'])) $additionalInfo['contact'] = $_POST['contact'];
            if (!empty($_POST['website'])) $additionalInfo['website'] = $_POST['website'];
            if ($imagePath) $additionalInfo['image'] = $imagePath;
            $fullDescription = $_POST['description'] ?? '';
            if (!empty($additionalInfo)) $fullDescription .= "\n\n---\nAdditional Information:\n" . json_encode($additionalInfo, JSON_PRETTY_PRINT);
            $result = query("INSERT INTO info_events (title, description, start_date, end_date, status, created_by, created_at, updated_at) VALUES ($1, $2, $3, $4, $5, $6, NOW(), NOW())", [$_POST['title'], $fullDescription, $_POST['start_date'], $_POST['end_date'], $status, $user_id]);
            if ($result) { $_SESSION['success_message'] = 'Event "' . htmlspecialchars($_POST['title']) . '" has been created successfully!'; header('Location: index.php?created=1'); exit; } 
            else $formErrors['database'] = 'Failed to create event. Please try again.';
        }
    }
}

if (isset($_SESSION['success_message'])) { $successMessage = $_SESSION['success_message']; unset($_SESSION['success_message']); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daeteño Admin - Create Event</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .glass-header { background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); }
        .form-card { transition: all 0.3s ease; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
        .form-card:hover { box-shadow: 0 8px 30px rgba(0,0,0,0.06); }
        input:focus, textarea:focus, select:focus { box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1); }
        .toast { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-purple-50/30">
    <!-- Glass Header -->
    <div class="glass-header text-white sticky top-0 z-50 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-4">
                <div class="flex items-center space-x-4">
                    <a href="../dashboard.php" class="flex items-center text-white/80 hover:text-white transition-all duration-200 hover:scale-105">
                        <i class="fas fa-arrow-left mr-2"></i><span class="text-sm font-medium">Back to Dashboard</span>
                    </a>
                    <div class="h-6 w-px bg-white/30"></div>
                    <a href="index.php" class="flex items-center text-white/80 hover:text-white transition-all duration-200 hover:scale-105">
                        <i class="fas fa-calendar-alt mr-2"></i><span class="text-sm font-medium">All Events</span>
                    </a>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="bg-white/20 backdrop-blur-sm px-3 py-1.5 rounded-full flex items-center gap-2">
                        <i class="fas fa-user-circle"></i>
                        <span class="text-sm font-medium"><?php echo htmlspecialchars($userName); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($successMessage): ?>
    <div id="successToast" class="fixed top-20 right-4 bg-emerald-500 text-white px-5 py-3 rounded-xl shadow-xl z-50 toast flex items-center gap-2">
        <i class="fas fa-check-circle"></i><span><?php echo htmlspecialchars($successMessage); ?></span>
    </div>
    <script>setTimeout(() => { const toast = document.getElementById('successToast'); if (toast) toast.style.display = 'none'; }, 3000);</script>
    <?php endif; ?>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Create New Event</h1>
            <p class="text-slate-500 mt-2">Add a new festival, activity, or event in Daet</p>
        </div>

        <?php if (!empty($formErrors)): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-xl p-5">
            <div class="flex items-center mb-2 gap-2"><i class="fas fa-exclamation-circle text-red-600"></i><h3 class="text-red-800 font-semibold">Please fix the following errors:</h3></div>
            <ul class="list-disc list-inside text-sm text-red-700"><?php foreach ($formErrors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>

        <div class="form-card bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-purple-100 flex items-center justify-center"><i class="fas fa-calendar-plus text-purple-600"></i></div>
                    <div><h2 class="text-lg font-semibold text-slate-800">Event Details</h2><p class="text-sm text-slate-500">Fill in the information below</p></div>
                </div>
            </div>

            <div class="p-6 lg:p-8">
                <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="eventForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create_event">
                    <div class="space-y-6">
                        <div><label class="block text-sm font-semibold text-slate-700 mb-2">Event Title <span class="text-red-500">*</span></label>
                            <input type="text" name="title" id="title" required class="w-full px-4 py-3 border <?php echo isset($formErrors['title']) ? 'border-red-500' : 'border-slate-200'; ?> rounded-xl focus:ring-2 focus:ring-purple-500 transition-all" placeholder="Enter event title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"></div>

                        <div><label class="block text-sm font-semibold text-slate-700 mb-2">Description</label>
                            <textarea name="description" id="description" rows="4" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 transition-all" placeholder="Describe the event..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea></div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div><label class="block text-sm font-semibold text-slate-700 mb-2">Event Type</label>
                                <select name="event_type" id="event_type" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 bg-white">
                                    <option value="">Select Type</option>
                                    <option value="festival" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'festival') ? 'selected' : ''; ?>>🎉 Festival</option>
                                    <option value="cultural" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'cultural') ? 'selected' : ''; ?>>🏛️ Cultural</option>
                                    <option value="sports" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'sports') ? 'selected' : ''; ?>>⚽ Sports</option>
                                    <option value="music" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'music') ? 'selected' : ''; ?>>🎵 Music</option>
                                    <option value="food" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'food') ? 'selected' : ''; ?>>🍽️ Food Fair</option>
                                    <option value="community" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'community') ? 'selected' : ''; ?>>🤝 Community</option>
                                </select></div>
                            <div><label class="block text-sm font-semibold text-slate-700 mb-2">Location</label>
                                <input type="text" name="location" id="location" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500" placeholder="Event venue or location" value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>"></div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div><label class="block text-sm font-semibold text-slate-700 mb-2">Start Date & Time <span class="text-red-500">*</span></label>
                                <input type="datetime-local" name="start_date" id="start_date" required class="w-full px-4 py-3 border <?php echo isset($formErrors['start_date']) ? 'border-red-500' : 'border-slate-200'; ?> rounded-xl focus:ring-2 focus:ring-purple-500" value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>"></div>
                            <div><label class="block text-sm font-semibold text-slate-700 mb-2">End Date & Time <span class="text-red-500">*</span></label>
                                <input type="datetime-local" name="end_date" id="end_date" required class="w-full px-4 py-3 border <?php echo isset($formErrors['end_date']) ? 'border-red-500' : 'border-slate-200'; ?> rounded-xl focus:ring-2 focus:ring-purple-500" value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>"></div>
                        </div>
                        <?php if (isset($formErrors['date_range'])): ?><p class="text-red-500 text-sm -mt-4"><?php echo $formErrors['date_range']; ?></p><?php endif; ?>

                        <div><label class="block text-sm font-semibold text-slate-700 mb-2">Event Status</label>
                            <div class="flex flex-wrap gap-6">
                                <label class="flex items-center"><input type="radio" name="status" value="upcoming" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'upcoming') ? 'checked' : ''; ?> class="text-purple-600 focus:ring-purple-500"><span class="ml-2 text-sm text-slate-600">Upcoming</span></label>
                                <label class="flex items-center"><input type="radio" name="status" value="ongoing" <?php echo (isset($_POST['status']) && $_POST['status'] === 'ongoing') ? 'checked' : ''; ?> class="text-purple-600 focus:ring-purple-500"><span class="ml-2 text-sm text-slate-600">Ongoing</span></label>
                                <label class="flex items-center"><input type="radio" name="status" value="completed" <?php echo (isset($_POST['status']) && $_POST['status'] === 'completed') ? 'checked' : ''; ?> class="text-purple-600 focus:ring-purple-500"><span class="ml-2 text-sm text-slate-600">Completed</span></label>
                                <label class="flex items-center"><input type="radio" name="status" value="cancelled" <?php echo (isset($_POST['status']) && $_POST['status'] === 'cancelled') ? 'checked' : ''; ?> class="text-purple-600 focus:ring-purple-500"><span class="ml-2 text-sm text-slate-600">Cancelled</span></label>
                            </div></div>

                        <div><label class="block text-sm font-semibold text-slate-700 mb-2">Event Image</label>
                            <div class="border-2 border-dashed border-slate-200 rounded-xl p-8 text-center hover:border-purple-400 transition-all cursor-pointer bg-slate-50/30" id="imageUploadArea">
                                <i class="fas fa-cloud-upload-alt text-3xl text-purple-400 mb-3"></i>
                                <p class="text-sm text-slate-600 font-medium">Click or drag to upload event banner</p>
                                <p class="text-xs text-slate-400 mt-1">Recommended size: 1200x400px (Max 2MB)</p>
                                <p class="text-xs text-slate-400">Allowed formats: JPG, PNG, WEBP</p>
                                <input type="file" name="event_image" id="eventImageUpload" class="hidden" accept="image/jpeg,image/png,image/webp">
                            </div>
                            <div id="imagePreviewContainer" class="hidden mt-3 relative"><img id="imagePreview" src="" alt="Preview" class="w-full h-48 object-cover rounded-xl shadow-md"><button type="button" id="removeImage" class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 w-7 h-7 flex items-center justify-center text-xs hover:bg-red-600 transition-all"><i class="fas fa-times"></i></button></div></div>

                        <div class="border-t border-slate-200 pt-6"><h3 class="text-md font-semibold text-slate-800 mb-4 flex items-center gap-2"><i class="fas fa-info-circle text-purple-500"></i>Additional Information</h3>
                            <div class="space-y-4">
                                <div><label class="block text-sm text-slate-600 mb-1">Organizer</label><input type="text" name="organizer" id="organizer" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500" placeholder="Event organizer name" value="<?php echo isset($_POST['organizer']) ? htmlspecialchars($_POST['organizer']) : ''; ?>"></div>
                                <div><label class="block text-sm text-slate-600 mb-1">Contact Information</label><input type="text" name="contact" id="contact" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500" placeholder="Phone or email" value="<?php echo isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : ''; ?>"></div>
                                <div><label class="block text-sm text-slate-600 mb-1">Website/Registration Link</label><input type="url" name="website" id="website" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500" placeholder="https://example.com" value="<?php echo isset($_POST['website']) ? htmlspecialchars($_POST['website']) : ''; ?>"></div>
                            </div></div>

                        <div class="flex justify-end gap-4 pt-6 border-t border-slate-200">
                            <a href="index.php" class="px-6 py-2.5 border border-slate-300 rounded-xl hover:bg-slate-50 transition-all font-medium">Cancel</a>
                            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-xl hover:from-purple-700 hover:to-purple-800 transition-all shadow-md font-medium"><i class="fas fa-calendar-plus mr-2"></i> Create Event</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const uploadArea = document.getElementById('imageUploadArea');
        const imageInput = document.getElementById('eventImageUpload');
        const previewContainer = document.getElementById('imagePreviewContainer');
        const imagePreview = document.getElementById('imagePreview');
        const removeImageBtn = document.getElementById('removeImage');

        if (uploadArea) {
            uploadArea.addEventListener('click', () => imageInput.click());
            uploadArea.addEventListener('dragover', (e) => { e.preventDefault(); uploadArea.classList.add('border-purple-500', 'bg-purple-50'); });
            uploadArea.addEventListener('dragleave', (e) => { e.preventDefault(); uploadArea.classList.remove('border-purple-500', 'bg-purple-50'); });
            uploadArea.addEventListener('drop', (e) => { e.preventDefault(); uploadArea.classList.remove('border-purple-500', 'bg-purple-50'); const file = e.dataTransfer.files[0]; if (file && file.type.startsWith('image/')) handleImageFile(file); });
            imageInput.addEventListener('change', (e) => { if (e.target.files && e.target.files[0]) handleImageFile(e.target.files[0]); });
            function handleImageFile(file) {
                if (file.size > 2 * 1024 * 1024) { alert('Image size must be less than 2MB'); return; }
                const reader = new FileReader();
                reader.onload = (e) => { imagePreview.src = e.target.result; previewContainer.classList.remove('hidden'); uploadArea.classList.add('hidden'); };
                reader.readAsDataURL(file);
            }
            if (removeImageBtn) removeImageBtn.addEventListener('click', () => { imagePreview.src = ''; previewContainer.classList.add('hidden'); uploadArea.classList.remove('hidden'); imageInput.value = ''; });
        }

        const eventForm = document.getElementById('eventForm');
        if (eventForm) eventForm.addEventListener('submit', function(e) {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(document.getElementById('end_date').value);
            if (startDate && endDate && endDate < startDate) { e.preventDefault(); alert('End date must be after start date'); return false; }
        });
    </script>
</body>
</html>