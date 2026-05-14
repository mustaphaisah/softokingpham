<?php
require_once __DIR__ . '/../config/config.php';

function redirect($path)
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function isLoggedIn()
{
    return !empty($_SESSION['admin_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        redirect('/index.php');
    }
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        redirect('/index.php');
    }
    $_SESSION['last_activity'] = time();
}

function requireAdmin()
{
    requireLogin();
    if ($_SESSION['admin_role'] !== 'admin') {
        flash('success', 'Access denied. Admin only.');
        redirect('/pages/dashboard.php');
    }
}

function flash($key, $message = '')
{
    if ($message === '') {
        if (isset($_SESSION[$key])) {
            $text = $_SESSION[$key];
            unset($_SESSION[$key]);
            return $text;
        }
        return '';
    }
    $_SESSION[$key] = $message;
}

function countRows($sql, $params = [])
{
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function fetchAllRows($sql, $params = [])
{
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetchRow($sql, $params = [])
{
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function getUserRole()
{
    return $_SESSION['admin_role'] ?? 'cashier';
}

function calculateDaysToExpiry($expiryDate)
{
    $today = new DateTime();
    $expiry = new DateTime($expiryDate);
    $interval = $today->diff($expiry);
    return $interval->invert ? -$interval->days : $interval->days;
}

function getRestockSuggestion($medicineId)
{
    // Simple logic: if quantity < min_stock, suggest min_stock - quantity
    $medicine = fetchRow('SELECT quantity, min_stock FROM medicines WHERE id = ?', [$medicineId]);
    if ($medicine && $medicine['quantity'] < $medicine['min_stock']) {
        return $medicine['min_stock'] - $medicine['quantity'];
    }
    return 0;
}

function getDailyRevenue()
{
    return countRows('SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE DATE(created_at) = CURDATE()');
}

function getDailyProfit()
{
    return countRows('SELECT COALESCE(SUM(total_profit), 0) FROM sales WHERE DATE(created_at) = CURDATE()');
}

function getTotalSalesToday()
{
    return countRows('SELECT COUNT(*) FROM sales WHERE DATE(created_at) = CURDATE()');
}
