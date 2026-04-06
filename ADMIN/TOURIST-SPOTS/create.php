<?php
// create.php - Add Tourist Spot Page
require_once '../../dbconn.php';  // Include database connection

// Check if user is logged in and is admin
if (!isLoggedIn()) {
    redirect('../login.php');
}

if (!isAdmin()) {
    redirect('../index.php');
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['spotName'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category = sanitize($_POST['category'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $latitude = sanitize($_POST['latitude'] ?? '');
    $longitude = sanitize($_POST['longitude'] ?? '');
    $googleMapsUrl = sanitize($_POST['googleMapsUrl'] ?? '');
    $status = sanitize($_POST['status'] ?? 'active');
    $featured = isset($_POST['featured']) ? true : false;
    $amenities = $_POST['amenities'] ?? [];
    
    // Validation
    if (empty($name)) {
        $error = "Spot name is required";
    } elseif (empty($description)) {
        $error = "Description is required";
    } else {
        // Build location JSONB object
        $locationData = null;
        if (!empty($latitude) && !empty($longitude)) {
            $locationData = json_encode([
                'address' => $location,
                'coordinates' => [
                    'lat' => (float)$latitude,
                    'lng' => (float)$longitude
                ],
                'google_maps_url' => $googleMapsUrl
            ]);
        }
        
        // Handle image upload
        $images = [];
        if (isset($_FILES['coverImage']) && $_FILES['coverImage']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/attractions/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExtension = pathinfo($_FILES['coverImage']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['coverImage']['tmp_name'], $uploadPath)) {
                $images[] = 'uploads/attractions/' . $fileName;
            }
        }
        
        // Insert into database
        $sql = "INSERT INTO info_attractions (name, description, location, images, category, created_by) 
                VALUES ($1, $2, $3, $4, $5, $6) RETURNING id";
        
        $params = [
            $name,
            $description,
            $locationData,
            '{' . implode(',', array_map(function($img) { return '"' . $img . '"'; }, $images)) . '}',
            $category,
            $_SESSION['user_id']
        ];
        
        $result = query($sql, $params);
        
        if ($result) {
            $row = fetchOne($result);
            $success = "Tourist spot '$name' created successfully!";
            
            // Add reward points for admin action
            $rewardSql = "INSERT INTO info_reward_transactions (user_id, action, points, metadata) 
                          VALUES ($1, $2, $3, $4)";
            query($rewardSql, [$_SESSION['user_id'], 'create_attraction', 10, json_encode(['attraction_name' => $name])]);
            
            // Clear form after successful submission
            $_POST = [];
        } else {
            $error = "Failed to create tourist spot. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Tourist Spot - Daeteño Admin</title>
    <!-- Google Fonts + Tailwind + Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fc 0%, #eef2f8 100%);
        }
        
        /* Glass morphism header */
        .glass-header {
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        /* Modern card design */
        .card-modern {
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(2px);
            border-radius: 2rem;
            box-shadow: 0 25px 45px -12px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.02);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .card-modern:hover {
            box-shadow: 0 30px 55px -15px rgba(0,0,0,0.15);
        }
        
        /* Enhanced input styling */
        .input-elegant {
            transition: all 0.2s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            background-color: #ffffff;
            border: 1.5px solid #e4e7ec;
            border-radius: 1rem;
            padding: 0.75rem 1.125rem;
            font-size: 0.95rem;
            width: 100%;
        }
        
        .input-elegant:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59,130,246,0.12);
            outline: none;
        }
        
        textarea.input-elegant {
            resize: vertical;
        }
        
        /* Button styles */
        .btn-primary-modern {
            background: linear-gradient(105deg, #2563eb, #1e40af);
            border-radius: 2rem;
            padding: 0.7rem 2rem;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 6px 14px -6px rgba(37,99,235,0.35);
        }
        
        .btn-primary-modern:hover {
            background: linear-gradient(105deg, #1d4ed8, #1e3a8a);
            transform: translateY(-1px);
            box-shadow: 0 10px 20px -8px rgba(37,99,235,0.45);
        }
        
        .btn-secondary-modern {
            background: white;
            border: 1.5px solid #e2e8f0;
            border-radius: 2rem;
            padding: 0.7rem 1.8rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-secondary-modern:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }
        
        /* Dropzone redesign */
        .dropzone-elegant {
            background: #fefefe;
            border: 2px dashed #cbd5e1;
            border-radius: 1.5rem;
            transition: all 0.25s ease;
            cursor: pointer;
        }
        
        .dropzone-elegant:hover {
            border-color: #3b82f6;
            background: #f0f9ff;
        }
        
        .dropzone-drag-active {
            border-color: #3b82f6;
            background: #eff6ff;
            transform: scale(0.99);
        }
        
        /* Preview image animation */
        .image-preview-card {
            transition: all 0.3s ease;
            border-radius: 1.25rem;
            overflow: hidden;
            box-shadow: 0 12px 24px -10px rgba(0,0,0,0.12);
        }
        
        /* Required field star */
        .required-star:after {
            content: " *";
            color: #ef4444;
            font-weight: 600;
        }
        
        /* Animated alerts */
        .alert-animated {
            animation: slideDown 0.35s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Custom checkbox & radio styling */
        .checkbox-custom {
            accent-color: #2563eb;
            width: 1rem;
            height: 1rem;
        }
        
        /* Status badge area */
        .status-group label {
            cursor: pointer;
        }
        
        .featured-badge {
            background: #fffbeb;
            border-radius: 2rem;
            transition: all 0.1s;
        }
        
        .featured-badge:has(input:checked) {
            background: #fef3c7;
            border-color: #f59e0b;
        }
        
        /* Animation for form sections */
        .fade-up {
            animation: fadeUp 0.5s ease-out;
        }
        
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="antialiased">

    <!-- Glass Morphism Header -->
    <div class="glass-header sticky top-0 z-20">
        <div class="max-w-6xl mx-auto px-5 sm:px-8 py-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-5">
                <a href="index.php" class="flex items-center gap-2 text-white/85 hover:text-white transition-all duration-200 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full text-sm font-medium">
                    <i class="fas fa-arrow-left text-xs"></i>
                    <span>Back to Spots</span>
                </a>
                <div class="hidden sm:block h-6 w-px bg-white/20"></div>
                <div class="flex items-center gap-2 text-white/90 text-sm font-medium bg-white/10 px-4 py-1.5 rounded-full">
                    <i class="fas fa-map-marker-alt text-blue-200"></i>
                    <span>Daet Heritage Manager</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-blue-100 flex items-center gap-1"><i class="fas fa-plus-circle"></i> New Attraction</span>
            </div>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
        <!-- Hero Section -->
        <div class="mb-8 text-center md:text-left fade-up">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2 text-blue-600 text-sm font-semibold mb-1">
                        <i class="fas fa-crown"></i>
                        <span>Admin Dashboard</span>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-slate-800">Add <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Tourist Spot</span></h1>
                    <p class="text-slate-500 mt-1 text-base">Curate unforgettable destinations in Daet, Camarines Norte</p>
                </div>
                <div class="flex justify-center md:justify-end">
                    <div class="bg-white/70 backdrop-blur-sm rounded-full px-4 py-1.5 text-xs font-semibold text-slate-700 shadow-sm border border-slate-100">
                        <i class="fas fa-gem mr-1 text-blue-500"></i> +10 reward points per spot
                    </div>
                </div>
            </div>
        </div>

        <!-- Error & Success Alerts - redesigned -->
        <?php if ($error): ?>
        <div class="mb-8 p-4 rounded-2xl bg-gradient-to-r from-rose-50 to-rose-100 border-l-8 border-rose-500 text-rose-800 shadow-sm flex items-start gap-3 alert-animated">
            <i class="fas fa-circle-exclamation text-rose-500 mt-0.5 text-lg"></i>
            <div class="flex-1 text-sm font-medium"><?php echo htmlspecialchars($error); ?></div>
            <button onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-700 transition"><i class="fas fa-times"></i></button>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="mb-8 p-4 rounded-2xl bg-gradient-to-r from-emerald-50 to-emerald-100 border-l-8 border-emerald-500 text-emerald-800 shadow-sm flex items-start gap-3 alert-animated">
            <i class="fas fa-check-circle text-emerald-500 mt-0.5 text-lg"></i>
            <div class="flex-1 text-sm font-medium"><?php echo htmlspecialchars($success); ?></div>
            <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-700 transition"><i class="fas fa-times"></i></button>
        </div>
        <?php endif; ?>

        <!-- Main Form Card -->
        <div class="card-modern overflow-hidden fade-up" style="animation-delay: 0.05s;">
            <div class="px-7 pt-7 pb-3 border-b border-slate-100 bg-white/40">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                        <i class="fas fa-pen-fancy text-sm"></i>
                    </div>
                    <h2 class="text-xl font-bold text-slate-800">Spot Information</h2>
                </div>
            </div>

            <div class="p-6 md:p-8">
                <form id="touristSpotForm" method="POST" enctype="multipart/form-data">
                    <div class="space-y-7">
                        <!-- Spot Name -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5 required-star">Spot Name</label>
                            <input type="text" id="spotName" name="spotName" required 
                                   value="<?php echo htmlspecialchars($_POST['spotName'] ?? ''); ?>"
                                   class="input-elegant"
                                   placeholder="e.g., Bagasbas Lighthouse, Daet Mangrove Eco Park">
                            <p class="text-xs text-slate-400 mt-1.5 flex items-center gap-1"><i class="fas fa-tag text-slate-400 text-[10px]"></i> Unique & memorable name</p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5 required-star">Description</label>
                            <textarea id="description" name="description" rows="5" required
                                      class="input-elegant"
                                      placeholder="Describe the history, ambiance, activities, and what makes this spot special..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            <p class="text-xs text-slate-400 mt-1.5 flex items-center gap-1"><i class="fas fa-chart-line text-slate-400 text-[10px]"></i> 50+ characters recommended for SEO & storytelling</p>
                        </div>

                        <!-- Category + Location grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Category</label>
                                <div class="relative">
                                    <select id="category" name="category" class="input-elegant appearance-none bg-white">
                                        <option value="">Select category</option>
                                        <option value="beach" <?php echo (($_POST['category'] ?? '') == 'beach') ? 'selected' : ''; ?>>🏖️ Beach</option>
                                        <option value="mountain" <?php echo (($_POST['category'] ?? '') == 'mountain') ? 'selected' : ''; ?>>⛰️ Mountain</option>
                                        <option value="historical" <?php echo (($_POST['category'] ?? '') == 'historical') ? 'selected' : ''; ?>>🏛️ Historical</option>
                                        <option value="cultural" <?php echo (($_POST['category'] ?? '') == 'cultural') ? 'selected' : ''; ?>>🎭 Cultural</option>
                                        <option value="religious" <?php echo (($_POST['category'] ?? '') == 'religious') ? 'selected' : ''; ?>>⛪ Religious</option>
                                        <option value="park" <?php echo (($_POST['category'] ?? '') == 'park') ? 'selected' : ''; ?>>🌳 Park & Nature</option>
                                    </select>
                                    <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Location Address</label>
                                <input type="text" id="location" name="location"
                                       value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                                       class="input-elegant"
                                       placeholder="Barangay, Daet, Camarines Norte">
                            </div>
                        </div>

                        <!-- Coordinates + Google Maps -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1.5">Latitude</label>
                                <input type="number" step="any" id="latitude" name="latitude"
                                       value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>"
                                       class="input-elegant" placeholder="14.112345">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1.5">Longitude</label>
                                <input type="number" step="any" id="longitude" name="longitude"
                                       value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>"
                                       class="input-elegant" placeholder="122.955678">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1.5">Google Maps URL</label>
                                <input type="url" id="googleMapsUrl" name="googleMapsUrl"
                                       value="<?php echo htmlspecialchars($_POST['googleMapsUrl'] ?? ''); ?>"
                                       class="input-elegant" placeholder="https://maps.app.goo.gl/...">
                            </div>
                        </div>

                        <!-- Amenities Grid (refreshed) -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-3">✨ Features & Amenities</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <?php
                                $selectedAmenities = $_POST['amenities'] ?? [];
                                $amenitiesList = [
                                    'parking' => '🅿️ Parking',
                                    'restrooms' => '🚻 Restrooms',
                                    'food_stalls' => '🍔 Food Stalls',
                                    'wifi' => '📶 Wi-Fi',
                                    'souvenir_shop' => '🎁 Souvenir Shop',
                                    'first_aid' => '🩹 First Aid',
                                    'cctv' => '📹 CCTV',
                                    'wheelchair' => '♿ Wheelchair Access'
                                ];
                                foreach ($amenitiesList as $value => $label):
                                ?>
                                <label class="flex items-center gap-2.5 bg-slate-50 rounded-xl px-3 py-2.5 border border-slate-100 hover:bg-slate-100 transition cursor-pointer">
                                    <input type="checkbox" name="amenities[]" value="<?php echo $value; ?>" 
                                           <?php echo in_array($value, $selectedAmenities) ? 'checked' : ''; ?>
                                           class="checkbox-custom w-4 h-4 rounded border-slate-300 focus:ring-2 focus:ring-blue-300">
                                    <span class="text-sm text-slate-700 font-medium"><?php echo $label; ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Status & Featured Section (modern card-like) -->
                        <div class="bg-gradient-to-br from-slate-50 to-white rounded-2xl p-5 flex flex-wrap items-center justify-between gap-4 shadow-inner-sm border border-slate-100">
                            <div class="flex flex-wrap gap-6 items-center">
                                <span class="text-sm font-bold text-slate-700">Status:</span>
                                <div class="flex gap-6 status-group">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="status" value="active" 
                                               <?php echo (($_POST['status'] ?? 'active') == 'active') ? 'checked' : ''; ?>
                                               class="w-4 h-4 text-blue-600 focus:ring-blue-400">
                                        <span class="text-sm text-slate-700"><i class="fas fa-circle text-emerald-500 text-xs mr-1"></i> Active</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="status" value="inactive" 
                                               <?php echo (($_POST['status'] ?? '') == 'inactive') ? 'checked' : ''; ?>
                                               class="w-4 h-4 text-blue-600">
                                        <span class="text-sm text-slate-700"><i class="fas fa-circle text-rose-400 text-xs mr-1"></i> Inactive</span>
                                    </label>
                                </div>
                            </div>
                            <label class="flex items-center gap-3 bg-white rounded-full px-5 py-2 shadow-sm border border-amber-200 cursor-pointer transition-all hover:shadow-md">
                                <input type="checkbox" name="featured" value="featured" 
                                       <?php echo isset($_POST['featured']) ? 'checked' : ''; ?>
                                       class="w-4 h-4 rounded border-amber-300 text-amber-500 focus:ring-amber-300">
                                <span class="text-sm font-semibold text-amber-700"><i class="fas fa-star"></i> Featured Spot</span>
                            </label>
                        </div>

                        <!-- Cover Image Dropzone -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Cover Image</label>
                            <div id="dropZone" class="dropzone-elegant rounded-2xl p-7 text-center transition-all">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <div class="w-14 h-14 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center text-2xl">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <p class="text-sm font-medium text-slate-600">Drag & drop image or <span class="text-blue-600 underline font-semibold">browse</span></p>
                                    <p class="text-xs text-slate-400">JPEG, PNG, WEBP up to 5MB | 1200x600px recommended</p>
                                </div>
                                <input type="file" id="imageUpload" name="coverImage" class="hidden" accept="image/jpeg,image/png,image/webp">
                            </div>
                            <div id="imagePreview" class="mt-5 hidden">
                                <div class="image-preview-card bg-white p-2 rounded-xl border border-slate-200 inline-block shadow-md">
                                    <img id="previewImg" src="#" alt="Cover preview" class="rounded-lg max-h-56 w-auto object-cover shadow-sm">
                                    <div class="text-center text-xs text-slate-500 mt-2"><i class="fas fa-check-circle text-emerald-500"></i> New cover preview</div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row justify-end gap-4 pt-6 border-t border-slate-200">
                            <a href="index.php" 
                               class="btn-secondary-modern text-center inline-flex justify-center items-center gap-2 text-slate-700">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn-primary-modern text-white inline-flex items-center justify-center gap-2 shadow-md">
                                <i class="fas fa-save"></i> Publish Tourist Spot
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- subtle footer note -->
        <div class="text-center text-xs text-slate-400 mt-8 flex justify-center gap-3">
            <span><i class="fas fa-camera"></i> Add authentic imagery</span>
            <span>•</span>
            <span><i class="fas fa-database"></i> All changes are saved instantly</span>
        </div>
    </div>

    <script>
        // Image upload handling (preserved functionality, enhanced visuals)
        const dropZone = document.getElementById('dropZone');
        const imageUpload = document.getElementById('imageUpload');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');

        dropZone.addEventListener('click', () => imageUpload.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dropzone-drag-active');
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dropzone-drag-active');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dropzone-drag-active');
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                validateAndUploadImage(file);
            }
        });

        imageUpload.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) validateAndUploadImage(file);
        });

        function validateAndUploadImage(file) {
            if (file.size > 5 * 1024 * 1024) {
                showMessage('Image size must be less than 5MB', 'error');
                return;
            }
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                showMessage('Please upload JPEG, PNG, or WEBP images only', 'error');
                return;
            }
            handleImageUpload(file);
        }

        function handleImageUpload(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                imagePreview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }

        function showMessage(message, type) {
            const existingMsg = document.getElementById('formMessage');
            const msgDiv = existingMsg || createMessageDiv();
            const isError = type === 'error';
            msgDiv.className = `mb-6 p-4 rounded-xl flex items-start gap-3 shadow-sm alert-animated ${isError ? 'bg-rose-50 border-l-8 border-rose-500 text-rose-800' : 'bg-emerald-50 border-l-8 border-emerald-500 text-emerald-800'}`;
            msgDiv.innerHTML = `<i class="fas ${isError ? 'fa-circle-exclamation' : 'fa-check-circle'} mt-0.5 text-lg"></i><div class="flex-1 text-sm font-medium">${message}</div><button onclick="this.parentElement.remove()" class="text-current opacity-70 hover:opacity-100"><i class="fas fa-times"></i></button>`;
            if (!existingMsg) {
                const formEl = document.getElementById('touristSpotForm');
                formEl.insertBefore(msgDiv, formEl.firstChild);
            }
            setTimeout(() => {
                if (msgDiv) msgDiv.style.display = 'none';
            }, 5000);
        }

        function createMessageDiv() {
            const div = document.createElement('div');
            div.id = 'formMessage';
            return div;
        }

        // Form validation (exact same logic)
        document.getElementById('touristSpotForm').addEventListener('submit', (e) => {
            const spotName = document.getElementById('spotName').value.trim();
            const description = document.getElementById('description').value.trim();
            
            if (!spotName) {
                e.preventDefault();
                showMessage('Please enter the spot name', 'error');
                document.getElementById('spotName').focus();
                return false;
            }
            if (!description) {
                e.preventDefault();
                showMessage('Please enter the description', 'error');
                document.getElementById('description').focus();
                return false;
            }
            if (description.length < 50) {
                e.preventDefault();
                showMessage('Description should be at least 50 characters for better SEO', 'error');
                document.getElementById('description').focus();
                return false;
            }
        });
        
        // Get current location (optional, same functionality)
        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition((position) => {
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                    showMessage('Location coordinates added successfully!', 'success');
                }, () => {
                    showMessage('Unable to get location', 'error');
                });
            } else {
                showMessage('Geolocation is not supported', 'error');
            }
        }
    </script>
</body>
</html>