<?php
require 'config.php';

// Handle login page (accessible without authentication)
if ($page === 'login') {
    include 'pages/login.php';
    exit;
}

// Handle logout
if ($page === 'logout') {
    session_destroy();
    header('Location: index.php?page=login');
    exit;
}

// Check authentication for all other pages
checkLogin();

require 'header.php';

switch($page) {
    case 'dashboard':   include 'pages/dashboard.php';  break;
    case 'pl_report':   include 'pages/pl_report.php';  break;
    case 'sales':       include 'pages/sales.php';       break;
    case 'expenses':    include 'pages/expenses.php';    break;
    case 'loans':       include 'pages/loans.php';       break;
    case 'feed_cost':   include 'pages/feed_cost.php';  break;
    case 'salary':      include 'pages/salary.php';      break;
    default:            include 'pages/dashboard.php';
}

require 'footer.php';
