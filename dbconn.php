<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID periodically for security
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Supabase PostgreSQL connection
$host = "aws-1-ap-northeast-2.pooler.supabase.com";
$port = "6543";
$dbname = "postgres";
$user = "postgres.kfprrnygbiuurdydnjnl";
$password = "1nfoSystem_LJM";

// Establish database connection
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

// Check connection
if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

// Set UTF-8 encoding
pg_set_client_encoding($conn, "UTF8");

function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if logged-in user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Redirect helper
function redirect($url) {
    header("Location: $url");
    exit();
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Execute queries safely
function query($sql, $params = []) {
    global $conn;
    $result = pg_query_params($conn, $sql, $params);
    if (!$result) {
        error_log("Query failed: " . pg_last_error($conn));
        return false;
    }
    return $result;
}

// Fetch all rows
function fetchAll($result) {
    return pg_fetch_all($result) ?: [];
}

// Fetch single row
function fetchOne($result) {
    if ($result === false || $result === null) {
        return null;
    }
    return pg_fetch_assoc($result) ?: null;
}

// Get number of rows
function num_rows($result) {
    return pg_num_rows($result);
}

// Generate UUID
function generateUUID() {
    $result = query("SELECT gen_random_uuid() as uuid");
    $row = fetchOne($result);
    return $row['uuid'];
}

// Helper function to get attraction with rating
function getAttractionWithRating($attractionId) {
    $query = "SELECT 
                a.*,
                COALESCE(AVG(f.rating), 0) as avg_rating,
                COUNT(f.id) as review_count
              FROM info_attractions a
              LEFT JOIN info_feedback f ON f.target_type = 'attraction' AND f.target_id = CAST(a.id AS TEXT)
              WHERE a.id = $1
              GROUP BY a.id";
    $result = query($query, [$attractionId]);
    return fetchOne($result);
}

// Helper function to get all attractions with ratings
function getAllAttractionsWithRatings() {
    $query = "SELECT 
                a.*,
                COALESCE(AVG(f.rating), 0) as avg_rating,
                COUNT(f.id) as review_count
              FROM info_attractions a
              LEFT JOIN info_feedback f ON f.target_type = 'attraction' AND f.target_id = CAST(a.id AS TEXT)
              GROUP BY a.id
              ORDER BY a.created_at DESC";
    $result = query($query, []);
    return fetchAll($result);
}

// Helper function to get user's feedback with item names
function getUserFeedback($userId) {
    $query = "SELECT 
                f.*,
                CASE 
                    WHEN f.target_type = 'attraction' THEN a.name
                    WHEN f.target_type = 'event' THEN e.title
                    ELSE 'Item'
                END as item_name
              FROM info_feedback f
              LEFT JOIN info_attractions a ON f.target_type = 'attraction' AND f.target_id = CAST(a.id AS TEXT)
              LEFT JOIN info_events e ON f.target_type = 'event' AND f.target_id = CAST(e.id AS TEXT)
              WHERE f.user_id = $1
              ORDER BY f.created_at DESC";
    $result = query($query, [$userId]);
    return fetchAll($result);
}
?>