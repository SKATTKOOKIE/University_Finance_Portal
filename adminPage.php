<?php
// Start the session
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== TRUE || !isset($_SESSION['role']) || $_SESSION['role'] !== 'A')
{
    // Redirect to login page if not logged in or not an admin
    header("Location: index.php");
    exit();
}

// Include functions file with database connection
require_once 'functions.php';

// Function to get user statistics
function getUserStatistics($db)
{
    $stats = [];

    // Get total number of users
    $stmt = $db->prepare("SELECT COUNT(*) as total_users FROM users");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_users'] = $row['total_users'];

    // Get users with account details
    $stmt = $db->prepare("SELECT u.user_id, u.first_name, u.last_name, u.role, u.email, u.user_name, 
            a.account_id, a.balance
            FROM users u
            LEFT JOIN accounts a ON u.user_id = a.user_id
            ORDER BY u.user_id");

    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stats['users'] = $users;

    // Get transaction statistics
    $stmt = $db->prepare("SELECT COUNT(*) as total_transactions, 
            SUM(CASE WHEN p.type = 'deposit' THEN 1 ELSE 0 END) as total_deposits,
            SUM(CASE WHEN p.type = 'withdrawal' THEN 1 ELSE 0 END) as total_withdrawals,
            SUM(CASE WHEN p.type = 'deposit' THEN t.amount ELSE 0 END) as total_deposit_amount,
            SUM(CASE WHEN p.type = 'withdrawal' THEN ABS(t.amount) ELSE 0 END) as total_withdrawal_amount
            FROM transactions t
            JOIN payments p ON t.payment_id = p.payment_id");

    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['transaction_stats'] = $row;

    return $stats;
}

// Get database connection
$db = connectdb();

// Get user statistics
$stats = getUserStatistics($db);

// Include the navbar
include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - User Statistics</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Additional admin-specific styles */
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-box {
            background-color: var(--off-white);
            border-radius: 6px;
            padding: 15px;
            text-align: center;
            border-left: 3px solid var(--pastel-purple);
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
            color: var(--pastel-purple);
        }

        .stat-label {
            color: var(--text-dark);
            font-size: 14px;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .admin-table th {
            background-color: var(--pastel-purple);
            color: white;
            padding: 0.8rem 1rem;
            text-align: left;
        }

        .admin-table td {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid var(--light-grey);
        }

        .admin-table tr:nth-child(even) {
            background-color: var(--off-white);
        }

        .admin-table tr:hover {
            background-color: rgba(93, 74, 138, 0.05);
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-admin {
            background-color: #ffd700;
            color: #333;
        }

        .badge-user {
            background-color: var(--light-grey);
            color: #333;
        }
    </style>
</head>

<body>
    <?php renderNavbar('Finance Portal', 'admin'); ?>

    <div class="admin-container">
        <h1 class="welcome-header">Admin Dashboard</h1>

        <!-- User Statistics Overview -->
        <div class="dashboard-container">
            <h2 class="section-title">User Statistics Overview</h2>

            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                </div>

                <div class="stat-box">
                    <div class="stat-label">Total Transactions</div>
                    <div class="stat-value"><?php echo $stats['transaction_stats']['total_transactions']; ?></div>
                </div>

                <div class="stat-box">
                    <div class="stat-label">Total Deposits</div>
                    <div class="stat-value"><?php echo $stats['transaction_stats']['total_deposits']; ?></div>
                </div>

                <div class="stat-box">
                    <div class="stat-label">Total Withdrawals</div>
                    <div class="stat-value"><?php echo $stats['transaction_stats']['total_withdrawals']; ?></div>
                </div>

                <div class="stat-box">
                    <div class="stat-label">Total Deposit Amount</div>
                    <div class="stat-value">
                        £<?php echo number_format($stats['transaction_stats']['total_deposit_amount'], 2); ?></div>
                </div>

                <div class="stat-box">
                    <div class="stat-label">Total Withdrawal Amount</div>
                    <div class="stat-value">
                        £<?php echo number_format(abs($stats['transaction_stats']['total_withdrawal_amount']), 2); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- User List -->
        <div class="dashboard-container">
            <h2 class="section-title">User List</h2>

            <div class="transaction-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Account ID</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['users'] as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['role'] === 'A'): ?>
                                        <span class="badge badge-admin">Admin</span>
                                    <?php else: ?>
                                        <span class="badge badge-user">User</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $user['account_id'] ? htmlspecialchars($user['account_id']) : 'No Account'; ?>
                                </td>
                                <td>£<?php echo $user['balance'] ? number_format($user['balance'], 2) : '0.00'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add User Transaction History section if needed -->

    </div>
</body>

</html>