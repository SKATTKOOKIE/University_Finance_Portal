<?php
    session_start();

    // Check if the user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] != TRUE) {
        header('Location: index.php');
        exit;
    }

    require_once('navbar.php');
    require_once('functions.php');
    
    // Handle form submissions
    $transactionMessage = '';
    $transactionSuccess = false;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db = connectdb();
        
        if (isset($_POST['transaction_type']) && isset($_POST['amount']) && !empty($_POST['amount'])) {
            $userId = $_SESSION['userid'];
            $amount = floatval($_POST['amount']);
            $type = $_POST['transaction_type'];
            $description = isset($_POST['description']) ? $_POST['description'] : '';
            
            if ($amount <= 0) {
                $transactionMessage = 'Please enter a valid amount greater than zero.';
            } else {
                // Here you would add code to update the user's balance in the database
                // For now, we'll just show a success message
                if ($type === 'withdraw') {
                    // Add withdrawal logic here
                    $transactionMessage = 'Withdrawal of $' . number_format($amount, 2) . ' was successful.';
                } else {
                    // Add deposit logic here
                    $transactionMessage = 'Deposit of $' . number_format($amount, 2) . ' was successful.';
                }
                $transactionSuccess = true;
            }
        } else {
            $transactionMessage = 'Please fill in all required fields.';
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Transactions - Finance Portal</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <?php renderNavbar('Finance Portal', 'transactions'); ?>
        
        <div class="transaction-container">
            <h1 class="section-title">Transactions</h1>
            
            <?php if ($transactionMessage): ?>
                <div class="message <?php echo $transactionSuccess ? 'success' : 'error'; ?>">
                    <?php echo $transactionMessage; ?>
                </div>
            <?php endif; ?>
            
            <div class="toggle-container">
                <div class="toggle-slider" id="toggleSlider"></div>
                <div class="toggle-option active" id="depositToggle">Deposit</div>
                <div class="toggle-option" id="withdrawToggle">Withdraw</div>
            </div>
            
            <!-- Deposit Form -->
            <form action="transactions.php" method="post" id="deposit-form" class="transaction-form">
                <input type="hidden" name="transaction_type" value="deposit">
                
                <div class="form-group">
                    <label for="deposit-amount">Amount ($)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="deposit-amount" required>
                </div>
                
                <div class="form-group">
                    <label for="deposit-description">Description (Optional)</label>
                    <textarea name="description" id="deposit-description" rows="3"></textarea>
                </div>
                
                <button type="submit" class="submit-button">Deposit Funds</button>
            </form>
            
            <!-- Withdraw Form -->
            <form action="transactions.php" method="post" id="withdraw-form" class="transaction-form">
                <input type="hidden" name="transaction_type" value="withdraw">
                
                <div class="form-group">
                    <label for="withdraw-amount">Amount ($)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="withdraw-amount" required>
                </div>
                
                <div class="form-group">
                    <label for="withdraw-description">Description (Optional)</label>
                    <textarea name="description" id="withdraw-description" rows="3"></textarea>
                </div>
                
                <button type="submit" class="submit-button">Withdraw Funds</button>
            </form>
        </div>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const depositToggle = document.getElementById('depositToggle');
                const withdrawToggle = document.getElementById('withdrawToggle');
                const toggleSlider = document.getElementById('toggleSlider');
                const depositForm = document.getElementById('deposit-form');
                const withdrawForm = document.getElementById('withdraw-form');
                
                // Toggle between deposit and withdraw forms
                depositToggle.addEventListener('click', function() {
                    depositToggle.classList.add('active');
                    withdrawToggle.classList.remove('active');
                    toggleSlider.style.left = '0';
                    depositForm.style.display = 'block';
                    withdrawForm.style.display = 'none';
                });
                
                withdrawToggle.addEventListener('click', function() {
                    withdrawToggle.classList.add('active');
                    depositToggle.classList.remove('active');
                    toggleSlider.style.left = '50%';
                    withdrawForm.style.display = 'block';
                    depositForm.style.display = 'none';
                });
            });
        </script>
    </body>
</html>