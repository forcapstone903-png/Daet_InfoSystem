<?php
// File: index.php
// Bookings Management Page

require_once 'C:\Users\oleng\Downloads\Daet_InfoSystem-main\dbconn.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('C:\Users\oleng\Downloads\Daet_InfoSystem-main\AUTH\login.php');
}

$userName = $_SESSION['full_name'] ?? 'Administrator';
$success_message = '';
$error_message = '';

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
        $update_query = "UPDATE info_bookings SET status = $1, updated_at = NOW() WHERE id = $2";
        $result = query($update_query, [$new_status, $booking_id]);
        echo json_encode(['success' => (bool)$result, 'message' => $result ? 'Booking status updated successfully' : 'Failed to update booking status']);
        exit;
    }
    
    if ($action === 'get_bookings') {
        $bookings_query = "SELECT b.*, u.full_name as customer_name, u.email as customer_email FROM info_bookings b LEFT JOIN info_profiles u ON b.user_id = u.id ORDER BY b.created_at DESC";
        $bookings_result = query($bookings_query);
        $bookings = $bookings_result ? fetchAll($bookings_result) : [];
        echo json_encode(['success' => true, 'bookings' => $bookings]);
        exit;
    }
    
    if ($action === 'export_bookings') {
        $export_query = "SELECT b.*, u.full_name as customer_name, u.email as customer_email FROM info_bookings b LEFT JOIN info_profiles u ON b.user_id = u.id ORDER BY b.created_at DESC";
        $export_result = query($export_query);
        $export_data = $export_result ? fetchAll($export_result) : [];
        echo json_encode(['success' => true, 'data' => $export_data]);
        exit;
    }
    
    if ($action === 'get_booking_details') {
        $booking_id = $_POST['booking_id'] ?? '';
        $detail_query = "SELECT b.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone FROM info_bookings b LEFT JOIN info_profiles u ON b.user_id = u.id WHERE b.id = $1";
        $detail_result = query($detail_query, [$booking_id]);
        $booking_detail = $detail_result ? fetchOne($detail_result) : null;
        echo json_encode(['success' => (bool)$booking_detail, 'booking' => $booking_detail, 'message' => $booking_detail ? '' : 'Booking not found']);
        exit;
    }
}

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

$services = [];
$services_query = query("SELECT DISTINCT service_type FROM info_bookings WHERE service_type IS NOT NULL AND service_type != ''");
if ($services_query) $services = fetchAll($services_query);

$bookings_query = "SELECT b.*, u.full_name as customer_name, u.email as customer_email FROM info_bookings b LEFT JOIN info_profiles u ON b.user_id = u.id ORDER BY b.created_at DESC";
$bookings_result = query($bookings_query);
$db_bookings = $bookings_result ? fetchAll($bookings_result) : [];
if (empty($db_bookings) && !$bookings_result) $error_message = "Bookings table not found.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings Management - Daeteño Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .booking-table tbody tr:hover { background: linear-gradient(90deg, rgba(5,150,105,0.05) 0%, transparent 100%); }
        .status-badge { transition: all 0.2s ease; }
        .modal { transition: all 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.3s ease-out; }
        .toast-notification { position: fixed; bottom: 24px; right: 24px; z-index: 1000; animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .glass-header { background: linear-gradient(135deg, #059669 0%, #047857 100%); }
        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -8px rgba(0,0,0,0.1); }
        .filter-panel { transition: all 0.3s ease; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-emerald-50/30 font-sans antialiased">
    <div class="min-h-screen">
        <!-- Glass Header -->
        <div class="glass-header text-white sticky top-0 z-50 shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between py-4">
                    <div class="flex items-center space-x-4">
                        <a href="../dashboard.php" class="flex items-center text-white/80 hover:text-white transition-all duration-200 hover:scale-105">
                            <i class="fas fa-arrow-left mr-2"></i>
                            <span class="text-sm font-medium">Back to Dashboard</span>
                        </a>
                        <div class="h-6 w-px bg-white/30"></div>
                        <span class="text-white font-semibold">Bookings Management</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="bg-white/20 backdrop-blur-sm px-3 py-1.5 rounded-full">
                            <i class="fas fa-user-circle mr-1"></i>
                            <span class="text-sm font-medium"><?php echo htmlspecialchars($userName); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <?php if ($success_message): ?>
            <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded-xl shadow-sm flex items-center gap-3">
                <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-xl shadow-sm flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-500 text-lg"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="flex justify-between items-center mb-8 flex-wrap gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Bookings Management</h1>
                    <p class="text-slate-500 mt-2">Manage all tour and activity bookings</p>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="exportBookings()" class="px-5 py-2.5 border border-slate-200 rounded-xl hover:bg-white transition-all font-medium shadow-sm flex items-center gap-2">
                        <i class="fas fa-download text-emerald-600"></i> Export
                    </button>
                    <button onclick="toggleFilterPanel()" class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white rounded-xl hover:from-emerald-700 hover:to-emerald-800 transition-all shadow-md flex items-center gap-2">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5 mb-8">
                <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                    <div class="flex items-center">
                        <div class="h-12 w-12 rounded-xl bg-amber-100 flex items-center justify-center mr-3">
                            <i class="fas fa-clock text-amber-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Pending</p>
                            <p class="text-2xl font-bold text-slate-800"><?php echo $pending_stats['count']; ?></p>
                            <p class="text-xs text-slate-400">₱<?php echo number_format($pending_stats['total'], 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                    <div class="flex items-center">
                        <div class="h-12 w-12 rounded-xl bg-blue-100 flex items-center justify-center mr-3">
                            <i class="fas fa-check-circle text-blue-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Confirmed</p>
                            <p class="text-2xl font-bold text-slate-800"><?php echo $confirmed_stats['count']; ?></p>
                            <p class="text-xs text-slate-400">₱<?php echo number_format($confirmed_stats['total'], 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                    <div class="flex items-center">
                        <div class="h-12 w-12 rounded-xl bg-emerald-100 flex items-center justify-center mr-3">
                            <i class="fas fa-star text-emerald-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Completed</p>
                            <p class="text-2xl font-bold text-slate-800"><?php echo $completed_stats['count']; ?></p>
                            <p class="text-xs text-slate-400">₱<?php echo number_format($completed_stats['total'], 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                    <div class="flex items-center">
                        <div class="h-12 w-12 rounded-xl bg-red-100 flex items-center justify-center mr-3">
                            <i class="fas fa-times-circle text-red-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Cancelled</p>
                            <p class="text-2xl font-bold text-slate-800"><?php echo $cancelled_stats['count']; ?></p>
                            <p class="text-xs text-slate-400">₱<?php echo number_format($cancelled_stats['total'], 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5 bg-gradient-to-br from-emerald-50 to-white">
                    <div class="flex items-center">
                        <div class="h-12 w-12 rounded-xl bg-emerald-200 flex items-center justify-center mr-3">
                            <i class="fas fa-money-bill-wave text-emerald-700 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Total Revenue</p>
                            <p class="text-2xl font-bold text-emerald-700">₱<?php echo number_format($total_revenue['total'], 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Panel -->
            <div id="filterPanel" class="hidden mb-8 bg-white rounded-2xl shadow-sm border border-slate-100 p-6 filter-panel">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Date From</label>
                        <input type="date" id="dateFrom" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Date To</label>
                        <input type="date" id="dateTo" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Service Type</label>
                        <select id="serviceFilter" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 bg-white">
                            <option value="">All Services</option>
                            <?php foreach ($services as $service): $service_name = $service['service_type'] ?? $service['service_name'] ?? ''; if (!empty($service_name)): ?>
                                <option value="<?php echo htmlspecialchars($service_name); ?>"><?php echo htmlspecialchars($service_name); ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
                        <select id="statusFilter" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 bg-white">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end mt-5 gap-3">
                    <button onclick="resetFilters()" class="px-5 py-2.5 border border-slate-300 rounded-xl hover:bg-slate-50 transition-all font-medium">Reset</button>
                    <button onclick="applyFilters()" class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white rounded-xl hover:from-emerald-700 hover:to-emerald-800 transition-all shadow-sm font-medium">Apply Filters</button>
                </div>
            </div>

            <!-- Bookings Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">All Bookings</h2>
                        <p class="text-sm text-slate-500 mt-0.5">Manage and track customer bookings</p>
                    </div>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="text" id="searchInput" placeholder="Search bookings..." class="pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 w-64">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 booking-table">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Booking ID</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Service</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="bookingsTableBody" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <p class="text-sm text-slate-500">Showing <span id="showingCount" class="font-semibold text-slate-700">0</span> of <span id="totalCount" class="font-semibold text-slate-700">0</span> bookings</p>
                    <div class="flex gap-2">
                        <button class="px-3 py-1.5 border border-slate-300 rounded-xl hover:bg-white transition-all disabled:opacity-50 text-sm font-medium" id="prevPage" disabled>Previous</button>
                        <button class="px-3 py-1.5 border border-slate-300 rounded-xl hover:bg-white transition-all disabled:opacity-50 text-sm font-medium" id="nextPage" disabled>Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div id="bookingModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 max-w-2xl w-full mx-4 modal fade-in shadow-2xl">
            <div class="flex justify-between items-center mb-5 pb-3 border-b border-slate-100">
                <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                    <i class="fas fa-receipt text-emerald-500"></i> Booking Details
                </h3>
                <button onclick="closeBookingModal()" class="text-slate-400 hover:text-slate-600 transition-all w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="bookingDetailsContent"></div>
            <div class="flex justify-end mt-6 pt-3 border-t border-slate-100">
                <button onclick="closeBookingModal()" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all font-medium">Close</button>
            </div>
        </div>
    </div>

    <script>
        let allBookings = <?php echo json_encode($db_bookings); ?>;
        let currentPage = 1;
        const rowsPerPage = 10;
        let currentFilters = { search: '', status: '', service: '', dateFrom: '', dateTo: '' };

        function getStatusClass(status) {
            switch(status?.toLowerCase()) {
                case 'pending': return 'bg-amber-100 text-amber-700';
                case 'confirmed': return 'bg-blue-100 text-blue-700';
                case 'completed': return 'bg-emerald-100 text-emerald-700';
                case 'cancelled': return 'bg-red-100 text-red-700';
                default: return 'bg-slate-100 text-slate-600';
            }
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        function escapeHtml(str) { if (!str) return ''; return String(str).replace(/[&<>]/g, function(m) { if (m === '&') return '&amp;'; if (m === '<') return '&lt;'; if (m === '>') return '&gt;'; return m; }); }

        function getFilteredBookings() {
            return allBookings.filter(booking => {
                const matchesSearch = !currentFilters.search || (booking.customer_name && booking.customer_name.toLowerCase().includes(currentFilters.search)) || (booking.booking_reference && booking.booking_reference.toLowerCase().includes(currentFilters.search)) || ((booking.service_type || booking.service_name) && (booking.service_type || booking.service_name).toLowerCase().includes(currentFilters.search));
                const matchesStatus = !currentFilters.status || booking.status === currentFilters.status;
                const matchesService = !currentFilters.service || (booking.service_type === currentFilters.service || booking.service_name === currentFilters.service);
                let matchesDate = true;
                if (currentFilters.dateFrom) { const bookingDate = new Date(booking.booking_date || booking.created_at); const fromDate = new Date(currentFilters.dateFrom); matchesDate = bookingDate >= fromDate; }
                if (currentFilters.dateTo && matchesDate) { const bookingDate = new Date(booking.booking_date || booking.created_at); const toDate = new Date(currentFilters.dateTo); toDate.setHours(23,59,59); matchesDate = bookingDate <= toDate; }
                return matchesSearch && matchesStatus && matchesService && matchesDate;
            });
        }

        function renderBookings() {
            const filtered = getFilteredBookings();
            const totalFiltered = filtered.length;
            const totalPages = Math.ceil(totalFiltered / rowsPerPage);
            if (currentPage > totalPages) currentPage = Math.max(1, totalPages);
            const start = (currentPage - 1) * rowsPerPage;
            const paginated = filtered.slice(start, start + rowsPerPage);
            const tbody = document.getElementById('bookingsTableBody');
            if (!tbody) return;
            tbody.innerHTML = '';
            if (paginated.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-12 text-slate-500"><i class="fas fa-calendar-times text-4xl mb-3 opacity-40"></i><p class="font-medium">No bookings found</p></td></tr>';
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
                row.className = 'hover:bg-slate-50/80 transition-all duration-200';
                row.innerHTML = `
                    <td class="px-6 py-4 text-sm font-semibold text-slate-800">${escapeHtml(booking.booking_reference || ('#BK-' + booking.id))}</td>
                    <td class="px-6 py-4"><div class="flex items-center"><div class="h-8 w-8 rounded-full bg-emerald-100 flex items-center justify-center mr-2"><span class="text-emerald-700 font-bold text-sm">${escapeHtml(initial)}</span></div><span class="text-sm text-slate-700 font-medium">${escapeHtml(customerName)}</span></div></td>
                    <td class="px-6 py-4 text-sm text-slate-600">${escapeHtml(booking.service_type || booking.service_name || 'N/A')}</td>
                    <td class="px-6 py-4 text-sm text-slate-500">${formatDate(booking.booking_date || booking.created_at)}</td>
                    <td class="px-6 py-4"><span class="px-2.5 py-1 text-xs font-semibold rounded-full ${statusClass}">${escapeHtml(booking.status || 'pending')}</span></td>
                    <td class="px-6 py-4 text-sm font-semibold text-slate-800">₱${parseFloat(booking.amount || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td class="px-6 py-4"><div class="flex gap-2">
                        ${booking.status !== 'confirmed' && booking.status !== 'completed' && booking.status !== 'cancelled' ? `<button onclick="updateBookingStatus('${booking.id}', 'confirmed')" class="text-blue-500 hover:text-blue-700 transition-all p-1" title="Confirm"><i class="fas fa-check-circle"></i></button>` : ''}
                        ${booking.status !== 'completed' && booking.status !== 'cancelled' ? `<button onclick="updateBookingStatus('${booking.id}', 'completed')" class="text-emerald-500 hover:text-emerald-700 transition-all p-1" title="Complete"><i class="fas fa-star"></i></button>` : ''}
                        ${booking.status !== 'cancelled' ? `<button onclick="updateBookingStatus('${booking.id}', 'cancelled')" class="text-red-500 hover:text-red-700 transition-all p-1" title="Cancel"><i class="fas fa-times-circle"></i></button>` : ''}
                        <button onclick="viewBookingDetails('${booking.id}')" class="text-slate-500 hover:text-slate-700 transition-all p-1" title="View Details"><i class="fas fa-eye"></i></button>
                    </div></td>
                `;
                tbody.appendChild(row);
            });
            document.getElementById('showingCount').innerText = paginated.length;
            document.getElementById('totalCount').innerText = totalFiltered;
            document.getElementById('prevPage').disabled = currentPage === 1;
            document.getElementById('nextPage').disabled = currentPage === totalPages || totalPages === 0;
        }

        async function updateBookingStatus(bookingId, newStatus) {
            if (!confirm(`Are you sure you want to mark this booking as ${newStatus}?`)) return;
            try {
                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('booking_id', bookingId);
                formData.append('status', newStatus);
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) { showNotification(data.message, 'success'); setTimeout(() => location.reload(), 1000); } 
                else showNotification(data.message, 'error');
            } catch (error) { showNotification('Error updating booking status', 'error'); }
        }

        async function viewBookingDetails(bookingId) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_booking_details');
                formData.append('booking_id', bookingId);
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    const booking = data.booking;
                    document.getElementById('bookingDetailsContent').innerHTML = `
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Booking Reference</label><p class="text-slate-800 font-semibold">${escapeHtml(booking.booking_reference || ('#BK-' + booking.id))}</p></div>
                                <div><label class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Status</label><p><span class="px-2.5 py-1 text-xs font-semibold rounded-full ${getStatusClass(booking.status)}">${escapeHtml(booking.status || 'N/A')}</span></p></div>
                                <div><label class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Customer Name</label><p class="text-slate-800">${escapeHtml(booking.customer_name || 'N/A')}</p></div>
                                <div><label class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Customer Email</label><p class="text-slate-800">${escapeHtml(booking.customer_email || 'N/A')}</p></div>
                                <div><label class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Customer Phone</label><p class="text-slate-800">${escapeHtml(booking.customer_phone || 'N/A')}</p></div>
                                <div><label class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Service</label><p class="text-slate-800">${escapeHtml(booking.service_type || booking.service_name || 'N/A')}</p></div>
                                <div><label class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Booking Date</label><p class="text-slate-800">${formatDate(booking.booking_date || booking.created_at)}</p></div>
                                <div><label class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Amount</label><p class="text-slate-800 font-bold text-lg">₱${parseFloat(booking.amount || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</p></div>
                                <div class="col-span-2"><label class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Notes</label><p class="text-slate-600 bg-slate-50 p-3 rounded-xl">${escapeHtml(booking.notes || 'No notes')}</p></div>
                            </div>
                        </div>
                    `;
                    document.getElementById('bookingModal').classList.remove('hidden');
                    document.getElementById('bookingModal').classList.add('flex');
                } else showNotification(data.message, 'error');
            } catch (error) { showNotification('Error loading booking details', 'error'); }
        }

        function applyFilters() {
            currentFilters = { search: document.getElementById('searchInput')?.value.toLowerCase() || '', status: document.getElementById('statusFilter')?.value || '', service: document.getElementById('serviceFilter')?.value || '', dateFrom: document.getElementById('dateFrom')?.value || '', dateTo: document.getElementById('dateTo')?.value || '' };
            currentPage = 1; renderBookings();
        }

        function resetFilters() {
            if (document.getElementById('searchInput')) document.getElementById('searchInput').value = '';
            if (document.getElementById('statusFilter')) document.getElementById('statusFilter').value = '';
            if (document.getElementById('serviceFilter')) document.getElementById('serviceFilter').value = '';
            if (document.getElementById('dateFrom')) document.getElementById('dateFrom').value = '';
            if (document.getElementById('dateTo')) document.getElementById('dateTo').value = '';
            currentFilters = { search: '', status: '', service: '', dateFrom: '', dateTo: '' };
            currentPage = 1; renderBookings();
        }

        async function exportBookings() {
            try {
                const formData = new FormData();
                formData.append('action', 'export_bookings');
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success && data.data) {
                    const headers = ['ID', 'Booking Reference', 'Customer Name', 'Customer Email', 'Service', 'Booking Date', 'Status', 'Amount', 'Notes'];
                    const csvRows = [headers];
                    data.data.forEach(booking => { csvRows.push([booking.id, booking.booking_reference || ('#BK-' + booking.id), booking.customer_name || '', booking.customer_email || '', booking.service_type || booking.service_name || '', booking.booking_date || booking.created_at, booking.status || '', booking.amount || 0, booking.notes || '']); });
                    const csvContent = csvRows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n');
                    const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a'); a.href = url; a.download = `bookings_export_${new Date().toISOString().split('T')[0]}.csv`;
                    document.body.appendChild(a); a.click(); document.body.removeChild(a); window.URL.revokeObjectURL(url);
                    showNotification('Bookings exported successfully!', 'success');
                } else showNotification('Failed to export bookings', 'error');
            } catch (error) { showNotification('Error exporting bookings', 'error'); }
        }

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `toast-notification px-5 py-3 rounded-xl shadow-xl ${type === 'success' ? 'bg-emerald-500' : 'bg-red-500'} text-white`;
            notification.innerHTML = `<div class="flex items-center gap-2"><i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i><span class="font-medium">${escapeHtml(message)}</span></div>`;
            document.body.appendChild(notification);
            setTimeout(() => { notification.style.opacity = '0'; setTimeout(() => notification.remove(), 300); }, 3000);
        }

        function toggleFilterPanel() { document.getElementById('filterPanel')?.classList.toggle('hidden'); }
        function closeBookingModal() { document.getElementById('bookingModal').classList.remove('flex'); document.getElementById('bookingModal').classList.add('hidden'); }

        document.addEventListener('DOMContentLoaded', function() {
            renderBookings();
            const searchInput = document.getElementById('searchInput');
            const prevBtn = document.getElementById('prevPage');
            const nextBtn = document.getElementById('nextPage');
            if (searchInput) searchInput.addEventListener('keyup', () => applyFilters());
            if (prevBtn) prevBtn.addEventListener('click', function() { if (currentPage > 1) { currentPage--; renderBookings(); } });
            if (nextBtn) nextBtn.addEventListener('click', function() { const filtered = getFilteredBookings(); const totalPages = Math.ceil(filtered.length / rowsPerPage); if (currentPage < totalPages) { currentPage++; renderBookings(); } });
            window.onclick = function(event) { const modal = document.getElementById('bookingModal'); if (event.target === modal) closeBookingModal(); };
        });
    </script>
</body>
</html>