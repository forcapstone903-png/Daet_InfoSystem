<?php
// File: index.php
// Bookings Management Page

require_once 'C:\Users\Jerwin\Downloads\DAETINFOSYSTEM\dbconn.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('C:\Users\Jerwin\Downloads\DAETINFOSYSTEM\AUTH\login.php');
}

$userName = $_SESSION['full_name'] ?? 'Administrator';
$success_message = '';
$error_message = '';

// Handle AJAX requests for status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $booking_id = $_POST['booking_id'] ?? '';
        $new_status = $_POST['status'] ?? '';
        
        if (empty($booking_id) || empty($new_status)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }
        
        // Update booking status
        $update_query = "UPDATE info_bookings SET status = $1, updated_at = NOW() WHERE id = $2";
        $result = query($update_query, [$new_status, $booking_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Booking status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update booking status']);
        }
        exit;
    }
    
    if ($action === 'get_bookings') {
        // Get all bookings with customer info
        $bookings_query = "SELECT b.*, 
                           u.full_name as customer_name, 
                           u.email as customer_email
                           FROM info_bookings b
                           LEFT JOIN info_profiles u ON b.user_id = u.id
                           ORDER BY b.created_at DESC";
        $bookings_result = query($bookings_query);
        $bookings = $bookings_result ? fetchAll($bookings_result) : [];
        
        echo json_encode(['success' => true, 'bookings' => $bookings]);
        exit;
    }
    
    if ($action === 'export_bookings') {
        // Get all bookings for export
        $export_query = "SELECT b.*, u.full_name as customer_name, u.email as customer_email 
                         FROM info_bookings b
                         LEFT JOIN info_profiles u ON b.user_id = u.id
                         ORDER BY b.created_at DESC";
        $export_result = query($export_query);
        $export_data = $export_result ? fetchAll($export_result) : [];
        
        echo json_encode(['success' => true, 'data' => $export_data]);
        exit;
    }
    
    if ($action === 'get_booking_details') {
        $booking_id = $_POST['booking_id'] ?? '';
        
        $detail_query = "SELECT b.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone
                         FROM info_bookings b
                         LEFT JOIN info_profiles u ON b.user_id = u.id
                         WHERE b.id = $1";
        $detail_result = query($detail_query, [$booking_id]);
        $booking_detail = $detail_result ? fetchOne($detail_result) : null;
        
        if ($booking_detail) {
            echo json_encode(['success' => true, 'booking' => $booking_detail]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Booking not found']);
        }
        exit;
    }
}

// Get real booking statistics from database
$pending_query = query("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM info_bookings WHERE status = 'pending'");
$pending_stats = $pending_query ? fetchOne($pending_query) : ['count' => 0, 'total' => 0];

$confirmed_query = query("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM info_bookings WHERE status = 'confirmed'");
$confirmed_stats = $confirmed_query ? fetchOne($confirmed_query) : ['count' => 0, 'total' => 0];

$cancelled_query = query("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM info_bookings WHERE status = 'cancelled'");
$cancelled_stats = $cancelled_query ? fetchOne($cancelled_query) : ['count' => 0, 'total' => 0];

$completed_query = query("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM info_bookings WHERE status = 'completed'");
$completed_stats = $completed_query ? fetchOne($completed_query) : ['count' => 0, 'total' => 0];

$total_revenue_query = query("SELECT COALESCE(SUM(amount), 0) as total FROM info_bookings WHERE status IN ('confirmed', 'completed')");
$total_revenue = $total_revenue_query ? fetchOne($total_revenue_query) : ['total' => 0];

// Get unique service types for filter - only from info_bookings table
$services = [];
$services_query = query("SELECT DISTINCT service_type FROM info_bookings WHERE service_type IS NOT NULL AND service_type != ''");
if ($services_query) {
    $services = fetchAll($services_query);
}

// Get all bookings with customer info for initial display
$bookings_query = "SELECT b.*, 
                   u.full_name as customer_name, 
                   u.email as customer_email
                   FROM info_bookings b
                   LEFT JOIN info_profiles u ON b.user_id = u.id
                   ORDER BY b.created_at DESC";
$bookings_result = query($bookings_query);
$db_bookings = $bookings_result ? fetchAll($bookings_result) : [];

// If no bookings table exists, show error message
if (empty($db_bookings) && !$bookings_result) {
    $error_message = "Bookings table not found. Please ensure the database is properly set up.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings Management - Daeteño Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .booking-table tbody tr:hover {
            background-color: #f9fafb;
        }
        .status-badge {
            transition: all 0.2s ease;
        }
        .modal {
            transition: all 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">

    <div class="min-h-screen bg-gray-50">
        <!-- Admin Header -->
        <div class="bg-gradient-to-r from-green-600 to-green-800 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between py-4">
                    <div class="flex items-center space-x-4">
                        <a href="../../dashboard.php" class="flex items-center text-white/80 hover:text-white transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Dashboard
                        </a>
                        <div class="h-6 w-px bg-white/30"></div>
                        <span class="text-white font-medium">Bookings Management</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm"><i class="fas fa-user-circle mr-1"></i> <?php echo htmlspecialchars($userName); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <?php if ($success_message): ?>
            <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm">
                <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm">
                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <div class="flex justify-between items-center mb-6 flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Bookings Management</h1>
                    <p class="text-gray-600">Manage all tour and activity bookings</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button onclick="exportBookings()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-download mr-2"></i> Export
                    </button>
                    <button class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors" onclick="toggleFilterPanel()">
                        <i class="fas fa-filter mr-2"></i> Filter
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow border border-gray-100 p-4">
                    <div class="flex items-center">
                        <div class="h-12 w-12 rounded-lg bg-yellow-100 flex items-center justify-center mr-3">
                            <i class="fas fa-clock text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Pending</p>
                            <p class="text-xl font-bold text-gray-900"><?php echo $pending_stats['count']; ?></p>
                            <p class="text-xs text-gray-500">₱<?php echo number_format($pending_stats['total'], 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow border border-gray-100 p-4">
                    <div class="flex items-center">
                        <div class="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center mr-3">
                            <i class="fas fa-check-circle text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Confirmed</p>
                            <p class="text-xl font-bold text-gray-900"><?php echo $confirmed_stats['count']; ?></p>
                            <p class="text-xs text-gray-500">₱<?php echo number_format($confirmed_stats['total'], 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow border border-gray-100 p-4">
                    <div class="flex items-center">
                        <div class="h-12 w-12 rounded-lg bg-green-100 flex items-center justify-center mr-3">
                            <i class="fas fa-star text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Completed</p>
                            <p class="text-xl font-bold text-gray-900"><?php echo $completed_stats['count']; ?></p>
                            <p class="text-xs text-gray-500">₱<?php echo number_format($completed_stats['total'], 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow border border-gray-100 p-4">
                    <div class="flex items-center">
                        <div class="h-12 w-12 rounded-lg bg-red-100 flex items-center justify-center mr-3">
                            <i class="fas fa-times-circle text-red-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Cancelled</p>
                            <p class="text-xl font-bold text-gray-900"><?php echo $cancelled_stats['count']; ?></p>
                            <p class="text-xs text-gray-500">₱<?php echo number_format($cancelled_stats['total'], 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow border border-gray-100 p-4">
                    <div class="flex items-center">
                        <div class="h-12 w-12 rounded-lg bg-purple-100 flex items-center justify-center mr-3">
                            <i class="fas fa-money-bill-wave text-purple-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Revenue</p>
                            <p class="text-xl font-bold text-gray-900">₱<?php echo number_format($total_revenue['total'], 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Panel (Hidden by default) -->
            <div id="filterPanel" class="hidden mb-6 bg-white rounded-lg shadow border border-gray-100 p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                        <input type="date" id="dateFrom" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                        <input type="date" id="dateTo" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Service Type</label>
                        <select id="serviceFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                            <option value="">All Services</option>
                            <?php foreach ($services as $service): ?>
                                <?php 
                                $service_name = $service['service_type'] ?? $service['service_name'] ?? '';
                                if (!empty($service_name)): 
                                ?>
                                <option value="<?php echo htmlspecialchars($service_name); ?>">
                                    <?php echo htmlspecialchars($service_name); ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end mt-4 space-x-2">
                    <button onclick="resetFilters()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Reset
                    </button>
                    <button onclick="applyFilters()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        Apply Filters
                    </button>
                </div>
            </div>

            <!-- Bookings Table -->
            <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <h2 class="text-lg font-semibold text-gray-900">All Bookings</h2>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Search bookings..." 
                                   class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 booking-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="bookingsTableBody" class="divide-y divide-gray-200">
                            <!-- Dynamic content via JavaScript -->
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center">
                    <p class="text-sm text-gray-500">Showing <span id="showingCount">0</span> of <span id="totalCount">0</span> bookings</p>
                    <div class="flex space-x-2">
                        <button class="px-3 py-1 border rounded hover:bg-gray-50 disabled:opacity-50" id="prevPage" disabled>Previous</button>
                        <button class="px-3 py-1 border rounded hover:bg-gray-50 disabled:opacity-50" id="nextPage" disabled>Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div id="bookingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 modal fade-in">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Booking Details</h3>
                <button onclick="closeBookingModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="bookingDetailsContent">
                <!-- Dynamic content -->
            </div>
            <div class="flex justify-end mt-6">
                <button onclick="closeBookingModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        let allBookings = [];
        let currentPage = 1;
        const rowsPerPage = 10;
        let currentFilters = {
            search: '',
            status: '',
            service: '',
            dateFrom: '',
            dateTo: ''
        };

        // Load initial bookings from PHP
        allBookings = <?php echo json_encode($db_bookings); ?>;

        function updateStatsFromData() {
            const pending = allBookings.filter(b => b.status === 'pending').length;
            const confirmed = allBookings.filter(b => b.status === 'confirmed').length;
            const completed = allBookings.filter(b => b.status === 'completed').length;
            const cancelled = allBookings.filter(b => b.status === 'cancelled').length;
            const totalRevenue = allBookings
                .filter(b => b.status === 'confirmed' || b.status === 'completed')
                .reduce((sum, b) => sum + parseFloat(b.amount || 0), 0);
            
            // Update stats display
            const statsCards = document.querySelectorAll('.bg-white.rounded-lg.shadow');
            if (statsCards[0]) statsCards[0].querySelector('.text-xl').textContent = pending;
            if (statsCards[1]) statsCards[1].querySelector('.text-xl').textContent = confirmed;
            if (statsCards[2]) statsCards[2].querySelector('.text-xl').textContent = completed;
            if (statsCards[3]) statsCards[3].querySelector('.text-xl').textContent = cancelled;
            if (statsCards[4]) statsCards[4].querySelector('.text-xl').textContent = '₱' + totalRevenue.toLocaleString('en-PH', {minimumFractionDigits: 2});
        }

        async function updateBookingStatus(bookingId, newStatus) {
            if (!confirm(`Are you sure you want to mark this booking as ${newStatus}?`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('booking_id', bookingId);
                formData.append('status', newStatus);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message, 'success');
                    // Reload page to refresh data
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                showNotification('Error updating booking status', 'error');
            }
        }

        async function viewBookingDetails(bookingId) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_booking_details');
                formData.append('booking_id', bookingId);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const booking = data.booking;
                    const modalContent = document.getElementById('bookingDetailsContent');
                    modalContent.innerHTML = `
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Booking Reference</label>
                                    <p class="text-gray-900 font-medium">${escapeHtml(booking.booking_reference || ('#BK-' + booking.id))}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Status</label>
                                    <p class="text-gray-900">
                                        <span class="px-2 py-1 text-xs rounded-full ${getStatusClass(booking.status)}">
                                            ${escapeHtml(booking.status || 'N/A')}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Customer Name</label>
                                    <p class="text-gray-900">${escapeHtml(booking.customer_name || 'N/A')}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Customer Email</label>
                                    <p class="text-gray-900">${escapeHtml(booking.customer_email || 'N/A')}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Customer Phone</label>
                                    <p class="text-gray-900">${escapeHtml(booking.customer_phone || 'N/A')}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Service</label>
                                    <p class="text-gray-900">${escapeHtml(booking.service_type || booking.service_name || 'N/A')}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Booking Date</label>
                                    <p class="text-gray-900">${formatDate(booking.booking_date || booking.created_at)}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Amount</label>
                                    <p class="text-gray-900 font-bold">₱${parseFloat(booking.amount || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</p>
                                </div>
                                <div class="col-span-2">
                                    <label class="text-sm font-medium text-gray-500">Notes</label>
                                    <p class="text-gray-900">${escapeHtml(booking.notes || 'No notes')}</p>
                                </div>
                            </div>
                        </div>
                    `;
                    document.getElementById('bookingModal').classList.remove('hidden');
                    document.getElementById('bookingModal').classList.add('flex');
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                showNotification('Error loading booking details', 'error');
            }
        }

        function getStatusClass(status) {
            switch(status?.toLowerCase()) {
                case 'pending': return 'bg-yellow-100 text-yellow-800';
                case 'confirmed': return 'bg-blue-100 text-blue-800';
                case 'completed': return 'bg-green-100 text-green-800';
                case 'cancelled': return 'bg-red-100 text-red-800';
                default: return 'bg-gray-100 text-gray-800';
            }
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        function getFilteredBookings() {
            return allBookings.filter(booking => {
                const matchesSearch = !currentFilters.search || 
                    (booking.customer_name && booking.customer_name.toLowerCase().includes(currentFilters.search)) ||
                    (booking.booking_reference && booking.booking_reference.toLowerCase().includes(currentFilters.search)) ||
                    ((booking.service_type || booking.service_name) && (booking.service_type || booking.service_name).toLowerCase().includes(currentFilters.search));
                
                const matchesStatus = !currentFilters.status || booking.status === currentFilters.status;
                const matchesService = !currentFilters.service || (booking.service_type === currentFilters.service || booking.service_name === currentFilters.service);
                
                let matchesDate = true;
                if (currentFilters.dateFrom) {
                    const bookingDate = new Date(booking.booking_date || booking.created_at);
                    const fromDate = new Date(currentFilters.dateFrom);
                    matchesDate = bookingDate >= fromDate;
                }
                if (currentFilters.dateTo && matchesDate) {
                    const bookingDate = new Date(booking.booking_date || booking.created_at);
                    const toDate = new Date(currentFilters.dateTo);
                    toDate.setHours(23, 59, 59);
                    matchesDate = bookingDate <= toDate;
                }
                
                return matchesSearch && matchesStatus && matchesService && matchesDate;
            });
        }

        function renderBookings() {
            const filtered = getFilteredBookings();
            const totalFiltered = filtered.length;
            const totalPages = Math.ceil(totalFiltered / rowsPerPage);
            
            if (currentPage > totalPages) currentPage = Math.max(1, totalPages);
            if (currentPage < 1) currentPage = 1;
            
            const start = (currentPage - 1) * rowsPerPage;
            const paginated = filtered.slice(start, start + rowsPerPage);
            
            const tbody = document.getElementById('bookingsTableBody');
            if (!tbody) return;
            
            tbody.innerHTML = '';
            
            if (paginated.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-gray-500">No bookings found.</td></tr>';
                document.getElementById('showingCount').innerText = '0';
                document.getElementById('totalCount').innerText = totalFiltered;
                document.getElementById('prevPage').disabled = true;
                document.getElementById('nextPage').disabled = true;
                return;
            }
            
            paginated.forEach(booking => {
                const row = document.createElement('tr');
                const statusClass = getStatusClass(booking.status);
                const customerName = booking.customer_name || 'Guest User';
                const initial = customerName.charAt(0).toUpperCase();
                const colors = ['bg-blue-100', 'bg-green-100', 'bg-purple-100', 'bg-pink-100', 'bg-orange-100', 'bg-teal-100'];
                const colorIndex = Math.abs(customerName.length) % colors.length;
                const colorClass = colors[colorIndex];
                const textColorClass = colorClass.replace('bg-', 'text-').replace('100', '600');
                
                row.innerHTML = `
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">${escapeHtml(booking.booking_reference || ('#BK-' + booking.id))}</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="h-8 w-8 rounded-full ${colorClass} flex items-center justify-center mr-2">
                                <span class="${textColorClass} font-bold text-sm">${escapeHtml(initial)}</span>
                            </div>
                            <span class="text-sm text-gray-900">${escapeHtml(customerName)}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">${escapeHtml(booking.service_type || booking.service_name || 'N/A')}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">${formatDate(booking.booking_date || booking.created_at)}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full ${statusClass} status-badge">${escapeHtml(booking.status || 'pending')}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">₱${parseFloat(booking.amount || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td class="px-6 py-4">
                        <div class="flex space-x-2">
                            ${booking.status !== 'confirmed' && booking.status !== 'completed' && booking.status !== 'cancelled' ? 
                                `<button onclick="updateBookingStatus('${booking.id}', 'confirmed')" class="text-blue-600 hover:text-blue-800 transition" title="Confirm">
                                    <i class="fas fa-check-circle"></i>
                                </button>` : ''
                            }
                            ${booking.status !== 'completed' && booking.status !== 'cancelled' ? 
                                `<button onclick="updateBookingStatus('${booking.id}', 'completed')" class="text-green-600 hover:text-green-800 transition" title="Complete">
                                    <i class="fas fa-star"></i>
                                </button>` : ''
                            }
                            ${booking.status !== 'cancelled' ? 
                                `<button onclick="updateBookingStatus('${booking.id}', 'cancelled')" class="text-red-600 hover:text-red-800 transition" title="Cancel">
                                    <i class="fas fa-times-circle"></i>
                                </button>` : ''
                            }
                            <button onclick="viewBookingDetails('${booking.id}')" class="text-gray-600 hover:text-gray-800 transition" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
            
            document.getElementById('showingCount').innerText = paginated.length;
            document.getElementById('totalCount').innerText = totalFiltered;
            document.getElementById('prevPage').disabled = currentPage === 1;
            document.getElementById('nextPage').disabled = currentPage === totalPages || totalPages === 0;
        }
        
        function applyFilters() {
            currentFilters = {
                search: document.getElementById('searchInput')?.value.toLowerCase() || '',
                status: document.getElementById('statusFilter')?.value || '',
                service: document.getElementById('serviceFilter')?.value || '',
                dateFrom: document.getElementById('dateFrom')?.value || '',
                dateTo: document.getElementById('dateTo')?.value || ''
            };
            currentPage = 1;
            renderBookings();
        }
        
        function resetFilters() {
            if (document.getElementById('searchInput')) document.getElementById('searchInput').value = '';
            if (document.getElementById('statusFilter')) document.getElementById('statusFilter').value = '';
            if (document.getElementById('serviceFilter')) document.getElementById('serviceFilter').value = '';
            if (document.getElementById('dateFrom')) document.getElementById('dateFrom').value = '';
            if (document.getElementById('dateTo')) document.getElementById('dateTo').value = '';
            
            currentFilters = {
                search: '',
                status: '',
                service: '',
                dateFrom: '',
                dateTo: ''
            };
            currentPage = 1;
            renderBookings();
        }
        
        async function exportBookings() {
            try {
                const formData = new FormData();
                formData.append('action', 'export_bookings');
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success && data.data) {
                    // Create CSV content
                    const headers = ['ID', 'Booking Reference', 'Customer Name', 'Customer Email', 'Service', 'Booking Date', 'Status', 'Amount', 'Notes'];
                    const csvRows = [headers];
                    
                    data.data.forEach(booking => {
                        csvRows.push([
                            booking.id,
                            booking.booking_reference || ('#BK-' + booking.id),
                            booking.customer_name || '',
                            booking.customer_email || '',
                            booking.service_type || booking.service_name || '',
                            booking.booking_date || booking.created_at,
                            booking.status || '',
                            booking.amount || 0,
                            booking.notes || ''
                        ]);
                    });
                    
                    const csvContent = csvRows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n');
                    const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `bookings_export_${new Date().toISOString().split('T')[0]}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                    
                    showNotification('Bookings exported successfully!', 'success');
                } else {
                    showNotification('Failed to export bookings', 'error');
                }
            } catch (error) {
                showNotification('Error exporting bookings', 'error');
            }
        }
        
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `toast-notification px-6 py-3 rounded-lg shadow-lg ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
                    <span>${escapeHtml(message)}</span>
                </div>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }
        
        function toggleFilterPanel() {
            const panel = document.getElementById('filterPanel');
            if (panel) {
                panel.classList.toggle('hidden');
            }
        }
        
        function closeBookingModal() {
            document.getElementById('bookingModal').classList.remove('flex');
            document.getElementById('bookingModal').classList.add('hidden');
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            renderBookings();
            
            const searchInput = document.getElementById('searchInput');
            const prevBtn = document.getElementById('prevPage');
            const nextBtn = document.getElementById('nextPage');
            
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    applyFilters();
                });
            }
            
            if (prevBtn) {
                prevBtn.addEventListener('click', function() {
                    if (currentPage > 1) {
                        currentPage--;
                        renderBookings();
                    }
                });
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', function() {
                    const filtered = getFilteredBookings();
                    const totalPages = Math.ceil(filtered.length / rowsPerPage);
                    if (currentPage < totalPages) {
                        currentPage++;
                        renderBookings();
                    }
                });
            }
            
            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('bookingModal');
                if (event.target === modal) {
                    closeBookingModal();
                }
            }
        });
    </script>
</body>
</html>