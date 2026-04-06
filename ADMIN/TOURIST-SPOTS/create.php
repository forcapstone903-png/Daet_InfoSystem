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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .transition-all {
            transition: all 0.3s ease;
        }
        .image-preview {
            transition: all 0.3s ease;
        }
        .image-preview:hover {
            transform: scale(1.02);
        }
        .required:after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Admin Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-4">
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="flex items-center text-white/80 hover:text-white transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Spots
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-blue-200">Add Tourist Spot</span>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Add New Tourist Spot</h1>
            <p class="text-gray-600">Create a new tourist destination in Daet</p>
        </div>

        <?php if ($error): ?>
        <div class="mb-6 p-4 rounded-lg bg-red-100 border border-red-200 text-red-700">
            <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="mb-6 p-4 rounded-lg bg-green-100 border border-green-200 text-green-700">
            <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Spot Information</h2>
            </div>

            <div class="p-6">
                <form id="touristSpotForm" method="POST" enctype="multipart/form-data">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2 required">Spot Name</label>
                            <input type="text" id="spotName" name="spotName" required 
                                   value="<?php echo htmlspecialchars($_POST['spotName'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Enter spot name">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2 required">Description</label>
                            <textarea id="description" name="description" rows="4" required
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Describe this tourist spot..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            <p class="text-xs text-gray-500 mt-1">Minimum 50 characters recommended for SEO</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                <select id="category" name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Category</option>
                                    <option value="beach" <?php echo (($_POST['category'] ?? '') == 'beach') ? 'selected' : ''; ?>>Beach</option>
                                    <option value="mountain" <?php echo (($_POST['category'] ?? '') == 'mountain') ? 'selected' : ''; ?>>Mountain</option>
                                    <option value="historical" <?php echo (($_POST['category'] ?? '') == 'historical') ? 'selected' : ''; ?>>Historical</option>
                                    <option value="cultural" <?php echo (($_POST['category'] ?? '') == 'cultural') ? 'selected' : ''; ?>>Cultural</option>
                                    <option value="religious" <?php echo (($_POST['category'] ?? '') == 'religious') ? 'selected' : ''; ?>>Religious</option>
                                    <option value="park" <?php echo (($_POST['category'] ?? '') == 'park') ? 'selected' : ''; ?>>Park</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Location Address</label>
                                <input type="text" id="location" name="location"
                                       value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="e.g., Daet, Camarines Norte">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Latitude</label>
                                <input type="number" step="any" id="latitude" name="latitude"
                                       value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="14.123456">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Longitude</label>
                                <input type="number" step="any" id="longitude" name="longitude"
                                       value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="122.123456">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Google Maps URL</label>
                                <input type="url" id="googleMapsUrl" name="googleMapsUrl"
                                       value="<?php echo htmlspecialchars($_POST['googleMapsUrl'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="https://maps.google.com/...">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Features & Amenities</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <?php
                                $selectedAmenities = $_POST['amenities'] ?? [];
                                $amenitiesList = [
                                    'parking' => 'Parking',
                                    'restrooms' => 'Restrooms',
                                    'food_stalls' => 'Food Stalls',
                                    'wifi' => 'Wi-Fi',
                                    'souvenir_shop' => 'Souvenir Shop',
                                    'first_aid' => 'First Aid',
                                    'cctv' => 'CCTV',
                                    'wheelchair' => 'Wheelchair Access'
                                ];
                                foreach ($amenitiesList as $value => $label):
                                ?>
                                <div class="flex items-center">
                                    <input type="checkbox" name="amenities[]" value="<?php echo $value; ?>" 
                                           <?php echo in_array($value, $selectedAmenities) ? 'checked' : ''; ?>
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-600"><?php echo $label; ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Spot Status</label>
                            <div class="flex flex-wrap gap-6">
                                <div class="flex items-center">
                                    <input type="radio" name="status" value="active" 
                                           <?php echo (($_POST['status'] ?? 'active') == 'active') ? 'checked' : ''; ?>
                                           class="text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-600">Active</span>
                                </div>
                                <div class="flex items-center">
                                    <input type="radio" name="status" value="inactive" 
                                           <?php echo (($_POST['status'] ?? '') == 'inactive') ? 'checked' : ''; ?>
                                           class="text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-600">Inactive</span>
                                </div>
                                <div class="flex items-center ml-4">
                                    <input type="checkbox" name="featured" value="featured" 
                                           <?php echo isset($_POST['featured']) ? 'checked' : ''; ?>
                                           class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500">
                                    <span class="ml-2 text-sm text-gray-600">Featured Spot</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cover Image</label>
                            <div id="dropZone" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-blue-500 transition-all">
                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-300 mb-3"></i>
                                <p class="text-sm text-gray-600">Drag & drop images here or click to browse</p>
                                <p class="text-xs text-gray-400 mt-1">Recommended size: 1200x600px (Max: 5MB)</p>
                                <input type="file" id="imageUpload" name="coverImage" class="hidden" accept="image/jpeg,image/png,image/webp">
                            </div>
                            <div id="imagePreview" class="mt-4 hidden">
                                <img id="previewImg" src="#" alt="Preview" class="image-preview max-w-full h-auto rounded-lg shadow max-h-64 object-cover">
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                            <a href="index.php" 
                               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i> Save Spot
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Image upload handling
        const dropZone = document.getElementById('dropZone');
        const imageUpload = document.getElementById('imageUpload');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');

        dropZone.addEventListener('click', () => {
            imageUpload.click();
        });

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('border-blue-500', 'bg-blue-50');
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-blue-500', 'bg-blue-50');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-blue-500', 'bg-blue-50');
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                validateAndUploadImage(file);
            }
        });

        imageUpload.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                validateAndUploadImage(file);
            }
        });

        function validateAndUploadImage(file) {
            // Validate file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                showMessage('Image size must be less than 5MB', 'error');
                return;
            }
            
            // Validate file type
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
            const messageDiv = document.getElementById('formMessage') || createMessageDiv();
            messageDiv.className = `p-4 rounded-lg mb-4 ${type === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'}`;
            messageDiv.innerHTML = `<i class="fas ${type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'} mr-2"></i> ${message}`;
            messageDiv.classList.remove('hidden');
            
            setTimeout(() => {
                messageDiv.classList.add('hidden');
            }, 5000);
        }
        
        function createMessageDiv() {
            const div = document.createElement('div');
            div.id = 'formMessage';
            const form = document.getElementById('touristSpotForm');
            form.insertBefore(div, form.firstChild);
            return div;
        }

        // Form validation before submit
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
        
        // Get current location button (optional feature)
        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition((position) => {
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                    showMessage('Location coordinates added!', 'success');
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