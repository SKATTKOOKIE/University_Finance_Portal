<?php
    session_start();

    // Checks the user has logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] != TRUE) 
    {
        header('Location: index.php');
        exit;
    }

    require_once('navbar.php');
    require_once('functions.php');
    
    // Get user information
    $userId = $_SESSION['userid'];
    $firstName = $_SESSION['firstname'];
    
    // Get user balance
    $balance = getUserBalance($userId);
    
    // Get transaction history (limit to 20 most recent transactions)
    $transactions = getUserTransactionHistory($userId, 20);
?>
<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Home - Finance Portal</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <?php renderNavbar('Finance Portal', 'home'); ?>

        <div class="dashboard-container">
            <h1 class="welcome-header">Welcome back, <?php echo htmlspecialchars($firstName); ?>!</h1>
            
            <div class="balance-card">
                <div class="balance-title">Current Balance</div>
                <div class="balance-amount">£<?php echo number_format($balance, 2); ?></div>
                
                <div class="actions-container">
                    <a href="transactionsPage.php" class="action-button">Make a Transaction</a>
                </div>
            </div>
            
            <div class="recent-transactions">
                <h2 class="section-title">Recent Activity</h2>
                
                <div class="transaction-table-container">
                    <?php if (empty($transactions)): ?>
                        <div class="no-transactions">No transaction history available yet.</div>
                    <?php else: ?>
                        <table class="transaction-history-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Transaction</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                    <?php 
                                        // Format the date
                                        $date = new DateTime($transaction['time']);
                                        $formattedDate = $date->format('d M Y, H:i');
                                        
                                        // Determine class for amount (positive/negative)
                                        $amountClass = $transaction['amount'] >= 0 ? 'amount-positive' : 'amount-negative';
                                        
                                        // Format the amount with a + or - sign
                                        $formattedAmount = $transaction['amount'] >= 0 
                                            ? '+£' . number_format(abs($transaction['amount']), 2)
                                            : '-£' . number_format(abs($transaction['amount']), 2);
                                    ?>
                                    <tr>
                                        <td><?php echo $formattedDate; ?></td>
                                        <td class="transaction-type">
                                            <?php echo htmlspecialchars($transaction['payment_type']); ?>
                                        </td>
                                        <td class="transaction-amount <?php echo $amountClass; ?>">
                                            <?php echo $formattedAmount; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </body>
</html>