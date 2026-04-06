<?php
/**
 * Daeteño Admin - Contact Messages Management
 * 
 * Professional contact messages page with inbox, reply functionality,
 * statistics, and message management features.
 */

session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_name'] = 'Admin User';
}

// Handle mark as read action
if (isset($_POST['action']) && $_POST['action'] === 'mark_read' && isset($_POST['message_id'])) {
    $_SESSION['success_message'] = 'Message marked as read!';
    header('Location: ' . $_SERVER['PHP_SELF'] . '?marked=1');
    exit;
}

// Handle delete action
if (isset($_POST['action']) && $_POST['action'] === 'delete_message' && isset($_POST['message_id'])) {
    $_SESSION['success_message'] = 'Message deleted successfully!';
    header('Location: ' . $_SERVER['PHP_SELF'] . '?deleted=1');
    exit;
}

// Handle reply submission
if (isset($_POST['action']) && $_POST['action'] === 'send_reply' && isset($_POST['message_id'])) {
    $_SESSION['success_message'] = 'Reply sent successfully!';
    header('Location: ' . $_SERVER['PHP_SELF'] . '?replied=1');
    exit;
}

// Handle mark all as read
if (isset($_POST['action']) && $_POST['action'] === 'mark_all_read') {
    $_SESSION['success_message'] = 'All messages marked as read!';
    header('Location: ' . $_SERVER['PHP_SELF'] . '?all_read=1');
    exit;
}

$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
unset($_SESSION['success_message']);

// Sample message data
$messages = [
    [
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'phone' => '+63 912 345 6789',
        'subject' => 'Inquiry about Daet Festival',
        'message' => 'Good day! I would like to ask about the schedule of the upcoming Daet Festival. Will there be any special events for tourists? Thank you!',
        'type' => 'inquiry',
        'status' => 'unread',
        'date' => '2024-12-01 10:30:00',
        'replied' => false
    ],
    [
        'id' => 2,
        'name' => 'Maria Santos',
        'email' => 'maria.santos@email.com',
        'phone' => '+63 998 765 4321',
        'subject' => 'Event Sponsorship',
        'message' => 'Hi! Our company is interested in sponsoring local events in Daet. Can you please provide information about sponsorship opportunities?',
        'type' => 'sponsorship',
        'status' => 'unread',
        'date' => '2024-12-02 14:15:00',
        'replied' => false
    ],
    [
        'id' => 3,
        'name' => 'Robert Chen',
        'email' => 'robert.chen@example.com',
        'phone' => '+63 917 123 4567',
        'subject' => 'Volunteer Application',
        'message' => 'I am interested in volunteering for the upcoming events in Daet. I have experience in event management and would love to contribute.',
        'type' => 'volunteer',
        'status' => 'read',
        'date' => '2024-11-28 09:45:00',
        'replied' => true
    ],
    [
        'id' => 4,
        'name' => 'Anna Reyes',
        'email' => 'anna.reyes@email.com',
        'phone' => '+63 920 555 1234',
        'subject' => 'Feedback on Bagasbas Beach Event',
        'message' => 'I attended the beach cleanup event last week and it was amazing! Just wanted to share my appreciation for organizing such a great initiative.',
        'type' => 'feedback',
        'status' => 'read',
        'date' => '2024-11-25 16:20:00',
        'replied' => false
    ],
    [
        'id' => 5,
        'name' => 'Carlos Mendoza',
        'email' => 'carlos.m@example.com',
        'phone' => '+63 955 789 0123',
        'subject' => 'Tourist Information Request',
        'message' => 'Planning to visit Daet next month. Can you recommend the best time to visit and must-see attractions? Also interested in local food spots!',
        'type' => 'inquiry',
        'status' => 'unread',
        'date' => '2024-12-03 08:00:00',
        'replied' => false
    ]
];

// Calculate statistics
$totalMessages = count($messages);
$readMessages = count(array_filter($messages, fn($m) => $m['status'] === 'read'));
$unreadMessages = count(array_filter($messages, fn($m) => $m['status'] === 'unread'));
$todayMessages = count(array_filter($messages, fn($m) => date('Y-m-d', strtotime($m['date'])) === date('Y-m-d')));
$last7DaysMessages = count(array_filter($messages, fn($m) => strtotime($m['date']) >= strtotime('-7 days')));
$thisMonthMessages = count(array_filter($messages, fn($m) => date('m', strtotime($m['date'])) === date('m')));

// Filter messages
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? strtolower($_GET['search']) : '';

$filteredMessages = $messages;
if ($filter === 'unread') {
    $filteredMessages = array_filter($messages, fn($m) => $m['status'] === 'unread');
} elseif ($filter === 'read') {
    $filteredMessages = array_filter($messages, fn($m) => $m['status'] === 'read');
}

if (!empty($search)) {
    $filteredMessages = array_filter($filteredMessages, function($m) use ($search) {
        return strpos(strtolower($m['name']), $search) !== false ||
               strpos(strtolower($m['email']), $search) !== false ||
               strpos(strtolower($m['subject']), $search) !== false ||
               strpos(strtolower($m['message']), $search) !== false;
    });
}

// Helper functions
function getTypeBadgeClass($type) {
    return match($type) {
        'inquiry' => 'badge-inquiry',
        'sponsorship' => 'badge-sponsorship',
        'volunteer' => 'badge-volunteer',
        'feedback' => 'badge-feedback',
        default => 'badge-default'
    };
}

function getTypeLabel($type) {
    return match($type) {
        'inquiry' => 'Inquiry',
        'sponsorship' => 'Sponsorship',
        'volunteer' => 'Volunteer',
        'feedback' => 'Feedback',
        default => ucfirst($type)
    };
}

function formatMessageDate($date) {
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 86400) {
        return 'Today, ' . date('g:i A', $timestamp);
    } elseif ($diff < 172800) {
        return 'Yesterday, ' . date('g:i A', $timestamp);
    } else {
        return date('M j, Y \a\t g:i A', $timestamp);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daeteño Admin - Contact Messages</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        
        /* Glass Header */
        .glass-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            backdrop-filter: blur(10px);
        }
        
        /* Stats Cards */
        .stat-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.15);
        }
        
        /* Message Items */
        .message-item {
            transition: all 0.2s ease;
            border-bottom: 1px solid #e2e8f0;
        }
        .message-item.unread {
            background: linear-gradient(90deg, #fffbeb 0%, #ffffff 100%);
            border-left: 3px solid #f59e0b;
        }
        .message-item:hover {
            background: linear-gradient(90deg, rgba(245, 158, 11, 0.05) 0%, transparent 100%);
        }
        
        /* Badges */
        .badge-unread {
            background: #fee2e2;
            color: #dc2626;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-replied {
            background: #dcfce7;
            color: #16a34a;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-inquiry {
            background: #dbeafe;
            color: #2563eb;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-sponsorship {
            background: #dcfce7;
            color: #16a34a;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-volunteer {
            background: #f3e8ff;
            color: #9333ea;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-feedback {
            background: #fef3c7;
            color: #d97706;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-default {
            background: #f1f5f9;
            color: #475569;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        /* Message Bubble */
        .message-bubble {
            background: #f8fafc;
            border-radius: 16px;
            padding: 16px;
            margin-top: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        .message-bubble:hover {
            background: #ffffff;
            border-color: #eab308;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }
        
        /* Action Buttons */
        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            background: transparent;
        }
        .action-btn:hover { transform: translateY(-2px) scale(1.05); }
        .action-btn-reply { color: #16a34a; }
        .action-btn-reply:hover { background: #dcfce7; box-shadow: 0 2px 8px rgba(22, 163, 74, 0.2); }
        .action-btn-read { color: #3b82f6; }
        .action-btn-read:hover { background: #dbeafe; box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2); }
        .action-btn-delete { color: #ef4444; }
        .action-btn-delete:hover { background: #fee2e2; box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2); }
        
        /* Reply Form */
        .reply-form-container {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(135deg, #fefce8 0%, #ffffff 100%);
            border-top: 1px solid #e2e8f0;
        }
        .reply-form-container.open {
            max-height: 520px;
            transition: max-height 0.45s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Form Controls */
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.875rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: #eab308;
            box-shadow: 0 0 0 3px rgba(234, 179, 8, 0.1);
        }
        .form-control[readonly] {
            background: #f1f5f9;
            cursor: not-allowed;
        }
        
        /* Buttons */
        .btn {
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            font-family: 'Inter', sans-serif;
        }
        .btn-primary {
            background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(234, 179, 8, 0.3);
        }
        .btn-secondary {
            background: white;
            border: 1px solid #e2e8f0;
            color: #475569;
        }
        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }
        .btn-outline {
            background: transparent;
            border: 1px solid #e2e8f0;
            color: #475569;
        }
        .btn-outline:hover {
            background: #f8fafc;
            transform: translateY(-1px);
        }
        
        /* Toast Notification */
        .toast-success {
            position: fixed;
            top: 24px;
            right: 24px;
            background: #10b981;
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 1100;
            transform: translateX(400px);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            font-weight: 500;
            font-size: 0.875rem;
        }
        .toast-success.show { transform: translateX(0); }
        
        /* Quick Action Cards */
        .quick-action-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }
        .quick-action-card:hover {
            transform: translateY(-3px);
            border-color: #eab308;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
        }
        
        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.25s ease;
        }
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .modal-container {
            background: white;
            border-radius: 24px;
            max-width: 420px;
            width: 90%;
            transform: scale(0.95);
            transition: transform 0.25s ease;
            text-align: center;
            padding: 24px;
        }
        .modal-overlay.active .modal-container { transform: scale(1); }
        .modal-icon {
            width: 48px;
            height: 48px;
            background: #fef3c7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        .modal-icon i { font-size: 1.5rem; color: #f59e0b; }
        
        /* Filter Bar */
        .filter-bar {
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            padding: 16px 24px;
            margin-bottom: 24px;
            transition: all 0.2s ease;
        }
        .filter-bar:hover { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03); }
        
        /* Avatar */
        .admin-avatar {
            background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%);
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #e2e8f0; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #eab308; }
        
        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in { animation: fadeInUp 0.3s ease-out; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-amber-50/20">
    
    <!-- Glass Header -->
    <div class="glass-header text-white sticky top-0 z-50 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-4">
                <a href="../dashboard.php" class="flex items-center text-white/80 hover:text-white transition-all duration-200 hover:scale-105 group">
                    <i class="fas fa-arrow-left text-sm group-hover:-translate-x-1 transition-transform"></i>
                    <span class="text-sm font-medium ml-2">Back to Dashboard</span>
                </a>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-xs text-slate-300">Logged in as</p>
                        <p class="text-sm font-semibold text-white">Admin User</p>
                    </div>
                    <div class="h-9 w-9 rounded-full admin-avatar flex items-center justify-center shadow-md">
                        <i class="fas fa-user text-sm text-white"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <div id="successToast" class="toast-success">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage"><?php echo $successMessage ? htmlspecialchars($successMessage) : ''; ?></span>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header -->
        <div class="flex flex-wrap justify-between items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Contact Messages</h1>
                <p class="text-slate-500 mt-2">Manage inquiries and messages from visitors</p>
            </div>
            <form method="POST" onsubmit="return confirm('Mark all messages as read?')">
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="px-5 py-2.5 border border-slate-200 rounded-xl hover:bg-white hover:border-amber-300 transition-all font-medium flex items-center gap-2 shadow-sm hover:shadow">
                    <i class="fas fa-envelope-open-text text-amber-500"></i> Mark All Read
                </button>
            </form>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-8">
            <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-xl bg-amber-100 flex items-center justify-center">
                        <i class="fas fa-envelope text-amber-600 text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold">Total Messages</p>
                        <p class="text-2xl font-bold text-slate-800"><?php echo $totalMessages; ?></p>
                    </div>
                </div>
            </div>
            <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-xl bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-envelope-open text-blue-600 text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold">Read Messages</p>
                        <p class="text-2xl font-bold text-slate-800"><?php echo $readMessages; ?></p>
                    </div>
                </div>
            </div>
            <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-xl bg-red-100 flex items-center justify-center">
                        <i class="fas fa-clock text-red-600 text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold">Unread Messages</p>
                        <p class="text-2xl font-bold text-red-600"><?php echo $unreadMessages; ?></p>
                    </div>
                </div>
            </div>
            <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-xl bg-emerald-100 flex items-center justify-center">
                        <i class="fas fa-calendar-week text-emerald-600 text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold">This Month</p>
                        <p class="text-2xl font-bold text-slate-800"><?php echo $thisMonthMessages; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="flex flex-wrap justify-between items-center gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <form method="GET" class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="text" name="search" placeholder="Search messages..." 
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                               class="pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500 w-64 bg-white">
                        <?php if (isset($_GET['filter']) && $_GET['filter'] !== 'all'): ?>
                            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($_GET['filter']); ?>">
                        <?php endif; ?>
                    </form>
                    <form method="GET">
                        <select name="filter" onchange="this.form.submit()" class="px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500 bg-white">
                            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Messages</option>
                            <option value="unread" <?php echo $filter === 'unread' ? 'selected' : ''; ?>>Unread Only</option>
                            <option value="read" <?php echo $filter === 'read' ? 'selected' : ''; ?>>Read Only</option>
                        </select>
                    </form>
                </div>
                <div class="text-sm text-slate-500 bg-slate-100 px-3 py-1.5 rounded-full">
                    Showing <span class="font-semibold text-slate-700"><?php echo count($filteredMessages); ?></span> of <span class="font-semibold text-slate-700"><?php echo $totalMessages; ?></span> messages
                </div>
            </div>
        </div>

        <!-- Messages List -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <?php if (count($filteredMessages) === 0): ?>
                <div class="text-center py-16">
                    <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-inbox text-2xl text-slate-400"></i>
                    </div>
                    <p class="text-slate-500 font-medium">No messages found</p>
                    <p class="text-sm text-slate-400 mt-1">Messages will appear here when visitors contact you</p>
                </div>
            <?php else: ?>
                <?php foreach ($filteredMessages as $message): ?>
                    <div class="message-item <?php echo $message['status'] === 'unread' ? 'unread' : ''; ?>" id="message-<?php echo $message['id']; ?>">
                        <div class="p-6">
                            <div class="flex flex-wrap justify-between gap-4">
                                <div class="flex-1">
                                    <!-- Header Row -->
                                    <div class="flex flex-wrap items-center gap-2 mb-3">
                                        <h3 class="font-bold text-slate-800"><?php echo htmlspecialchars($message['name']); ?></h3>
                                        <?php if ($message['status'] === 'unread'): ?>
                                            <span class="badge-unread">Unread</span>
                                        <?php endif; ?>
                                        <span class="<?php echo getTypeBadgeClass($message['type']); ?> px-2.5 py-1 text-xs font-semibold rounded-full">
                                            <?php echo getTypeLabel($message['type']); ?>
                                        </span>
                                        <?php if ($message['replied']): ?>
                                            <span class="badge-replied px-2.5 py-1 text-xs font-semibold rounded-full flex items-center gap-1">
                                                <i class="fas fa-reply text-xs"></i> Replied
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Contact Info -->
                                    <div class="flex flex-wrap gap-4 mb-3">
                                        <span class="text-sm text-slate-500 flex items-center gap-1">
                                            <i class="fas fa-envelope text-slate-400 text-xs"></i> <?php echo htmlspecialchars($message['email']); ?>
                                        </span>
                                        <span class="text-sm text-slate-500 flex items-center gap-1">
                                            <i class="fas fa-phone text-slate-400 text-xs"></i> <?php echo htmlspecialchars($message['phone']); ?>
                                        </span>
                                        <span class="text-sm text-slate-500 flex items-center gap-1">
                                            <i class="far fa-clock text-slate-400 text-xs"></i> <?php echo formatMessageDate($message['date']); ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Subject -->
                                    <p class="text-sm text-slate-600 mb-2">
                                        <span class="font-semibold text-slate-700">Subject:</span> <?php echo htmlspecialchars($message['subject']); ?>
                                    </p>
                                    
                                    <!-- Message Bubble -->
                                    <div class="message-bubble">
                                        <p class="text-slate-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="flex items-start gap-1">
                                    <button onclick="toggleReplyForm(<?php echo $message['id']; ?>)" 
                                            class="action-btn action-btn-reply" title="Reply">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                    <?php if ($message['status'] === 'unread'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                            <button type="submit" class="action-btn action-btn-read" title="Mark as Read">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirmDelete()">
                                        <input type="hidden" name="action" value="delete_message">
                                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                        <button type="submit" class="action-btn action-btn-delete" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Reply Form -->
                        <div id="replyForm-<?php echo $message['id']; ?>" class="reply-form-container">
                            <div class="p-6">
                                <h4 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                                    <i class="fas fa-reply-all text-amber-500"></i> Reply to <?php echo htmlspecialchars($message['name']); ?>
                                </h4>
                                <form method="POST">
                                    <input type="hidden" name="action" value="send_reply">
                                    <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                    
                                    <div class="mb-4">
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">To</label>
                                        <input type="email" name="to_email" value="<?php echo htmlspecialchars($message['email']); ?>" readonly
                                               class="w-full px-4 py-2.5 border border-slate-200 rounded-xl bg-slate-50 text-slate-600">
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">Subject</label>
                                        <input type="text" name="subject" value="Re: <?php echo htmlspecialchars($message['subject']); ?>" required
                                               class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                                    </div>
                                    
                                    <div class="mb-5">
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">Message</label>
                                        <textarea name="reply_message" rows="4" required
                                                  class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all"
                                                  placeholder="Type your reply here..."></textarea>
                                    </div>
                                    
                                    <div class="flex justify-end gap-3">
                                        <button type="button" onclick="toggleReplyForm(<?php echo $message['id']; ?>)" 
                                                class="px-5 py-2.5 border border-slate-300 rounded-xl hover:bg-slate-50 transition-all font-medium">Cancel</button>
                                        <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-xl hover:from-amber-600 hover:to-amber-700 transition-all shadow-sm font-medium">
                                            <i class="fas fa-paper-plane mr-2"></i> Send Reply
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Statistics and Quick Actions Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
            <!-- Message Statistics Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-chart-pie text-amber-500"></i> Message Statistics
                </h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2 border-b border-slate-100">
                        <span class="text-slate-600">Today</span>
                        <span class="font-bold text-slate-800 bg-slate-100 px-3 py-1 rounded-full"><?php echo $todayMessages; ?></span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-slate-100">
                        <span class="text-slate-600">Last 7 Days</span>
                        <span class="font-bold text-slate-800 bg-slate-100 px-3 py-1 rounded-full"><?php echo $last7DaysMessages; ?></span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-slate-100">
                        <span class="text-slate-600">This Month</span>
                        <span class="font-bold text-slate-800 bg-slate-100 px-3 py-1 rounded-full"><?php echo $thisMonthMessages; ?></span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-slate-600">Average Response Time</span>
                        <span class="font-medium text-slate-400 flex items-center gap-1"><i class="fas fa-hourglass-half"></i> Coming Soon</span>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-bolt text-amber-500"></i> Quick Actions
                </h3>
                <div class="space-y-3">
                    <div onclick="alert('Email settings coming soon!')" class="quick-action-card cursor-pointer">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                                <i class="fas fa-cog text-amber-600"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800">Email Settings</p>
                                <p class="text-sm text-slate-500">Configure auto-replies</p>
                            </div>
                            <i class="fas fa-chevron-right text-slate-300 ml-auto text-sm"></i>
                        </div>
                    </div>
                    <div onclick="alert('Export feature coming soon!')" class="quick-action-card cursor-pointer">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                                <i class="fas fa-download text-blue-600"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800">Export Messages</p>
                                <p class="text-sm text-slate-500">Download as CSV</p>
                            </div>
                            <i class="fas fa-chevron-right text-slate-300 ml-auto text-sm"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="font-bold text-slate-800 mb-2">Confirm Action</h3>
            <p class="text-sm text-slate-500 mb-6" id="modalMessage">Are you sure you want to perform this action?</p>
            <div class="flex justify-center gap-3">
                <button onclick="closeModal()" class="px-5 py-2.5 border border-slate-300 rounded-xl hover:bg-slate-50 transition-all font-medium">Cancel</button>
                <button id="modalConfirmBtn" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-xl hover:from-amber-600 hover:to-amber-700 transition-all font-medium">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        // Success Toast
        <?php if ($successMessage): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.getElementById('successToast');
            if (toast) {
                toast.classList.add('show');
                setTimeout(function() {
                    toast.classList.remove('show');
                }, 4000);
            }
        });
        <?php endif; ?>

        // Toggle Reply Form
        function toggleReplyForm(messageId) {
            const form = document.getElementById('replyForm-' + messageId);
            if (form) {
                form.classList.toggle('open');
                if (form.classList.contains('open')) {
                    setTimeout(() => {
                        form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }, 100);
                }
            }
        }

        // Confirm Delete
        function confirmDelete() {
            return confirm('Are you sure you want to delete this message? This action cannot be undone.');
        }

        // Close Modal
        function closeModal() {
            document.getElementById('confirmationModal').classList.remove('active');
        }

        // Clean URL params on load
        if (window.location.search && !window.location.search.includes('search')) {
            const url = new URL(window.location.href);
            if (url.searchParams.has('marked') || url.searchParams.has('deleted') || 
                url.searchParams.has('replied') || url.searchParams.has('all_read')) {
                url.search = '';
                window.history.replaceState({}, document.title, url.pathname);
            }
        }
    </script>
</body>
</html>