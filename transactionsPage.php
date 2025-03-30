<?php
    session_start();

    // Check if the user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] != TRUE) 
    {
        header('Location: index.php');
        exit;
    }

    require_once('navbar.php');
    require_once('functions.php');
    
    // Get user's account information
    $userId = $_SESSION['userid'];
    $account = getUserAccount($userId);
    $accountId = $account['account_id'];
    $currentBalance = $account['balance'];
    
    // Handle form submissions
    $transactionMessage = '';
    $transactionSuccess = false;
    $redirectToHome = false;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') 
    {
        if (isset($_POST['transaction_type']) && isset($_POST['amount']) && !empty($_POST['amount'])) 
        {
            $amount = floatval($_POST['amount']);
            $type = $_POST['transaction_type'];
            $description = isset($_POST['description']) ? $_POST['description'] : '';
            
            if ($amount <= 0) 
            {
                $transactionMessage = 'Please enter a valid amount greater than zero.';
            } 
            else 
            {
                if ($type === 'withdraw') 
                {
                    $result = withdrawFunds($accountId, $amount, $description);
                    
                    if ($result === 'insufficient_funds') 
                    {
                        $transactionMessage = 'Insufficient funds. Your current balance is £' . number_format($currentBalance, 2) . '.';
                    } 
                    else if ($result === true) 
                    {
                        $transactionMessage = 'Withdrawal of £' . number_format($amount, 2) . ' was successful.';
                        $transactionSuccess = true;
                        $redirectToHome = true;
                    } 
                    else 
                    {
                        $transactionMessage = 'There was an error processing your withdrawal. Please try again.';
                    }
                } 
                else 
                {
                    $result = depositFunds($accountId, $amount, $description);
                    
                    if ($result === true) 
                    {
                        $transactionMessage = 'Deposit of £' . number_format($amount, 2) . ' was successful.';
                        $transactionSuccess = true;
                        $redirectToHome = true;
                    } 
                    else 
                    {
                        $transactionMessage = 'There was an error processing your deposit. Please try again.';
                    }
                }
            }
        } 
        else 
        {
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
            
            <div class="balance-display">
                Your current balance: <span class="balance-amount">£<?php echo number_format($currentBalance, 2); ?></span>
            </div>
            
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
            <form action="transactionsPage.php" method="post" id="deposit-form" class="transaction-form">
                <input type="hidden" name="transaction_type" value="deposit">
                
                <div class="form-group">
                    <label for="deposit-amount">Amount (£)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="deposit-amount" required>
                </div>
                
                <div class="form-group">
                    <label for="deposit-description">Description (Optional)</label>
                    <textarea name="description" id="deposit-description" rows="3"></textarea>
                </div>
                
                <button type="submit" class="submit-button">Deposit Funds</button>
            </form>
            
            <!-- Withdraw Form -->
            <form action="transactionsPage.php" method="post" id="withdraw-form" class="transaction-form">
                <input type="hidden" name="transaction_type" value="withdraw">
                
                <div class="form-group">
                    <label for="withdraw-amount">Amount (£)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="withdraw-amount" required>
                </div>
                
                <div class="form-group">
                    <label for="withdraw-description">Description (Optional)</label>
                    <textarea name="description" id="withdraw-description" rows="3"></textarea>
                </div>
                
                <button type="submit" class="submit-button">Withdraw Funds</button>
            </form>
        </div>
        
        <!-- Processing Overlay -->
        <div class="processing-overlay" id="processingOverlay">
            <div class="processing-content">
                <div class="processing-spinner"></div>
                <h3>Processing Your Transaction</h3>
                <p>Please wait while we process your request...</p>
            </div>
        </div>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() 
            {
                const depositToggle = document.getElementById('depositToggle');
                const withdrawToggle = document.getElementById('withdrawToggle');
                const toggleSlider = document.getElementById('toggleSlider');
                const depositForm = document.getElementById('deposit-form');
                const withdrawForm = document.getElementById('withdraw-form');
                
                // Toggle between deposit and withdraw forms
                depositToggle.addEventListener('click', function() 
                {
                    depositToggle.classList.add('active');
                    withdrawToggle.classList.remove('active');
                    toggleSlider.style.left = '0';
                    depositForm.style.display = 'block';
                    withdrawForm.style.display = 'none';
                });
                
                withdrawToggle.addEventListener('click', function() 
                {
                    withdrawToggle.classList.add('active');
                    depositToggle.classList.remove('active');
                    toggleSlider.style.left = '50%';
                    withdrawForm.style.display = 'block';
                    depositForm.style.display = 'none';
                });
                
                // Show processing overlay when forms are submitted
                const forms = document.querySelectorAll('form');
                const processingOverlay = document.getElementById('processingOverlay');
                
                forms.forEach(form => 
                {
                    form.addEventListener('submit', function(e) 
                    {
                        // Prevent immediate form submission
                        e.preventDefault();
                        
                        // Show the processing overlay
                        processingOverlay.classList.add('active');
                        
                        // Submit the form after a delay to show the overlay
                        setTimeout(() => 
                        {
                            form.submit();
                        }, 100);
                    });
                });
                
                <?php if ($redirectToHome && $transactionSuccess): ?>
                // Show processing overlay and redirect to home
                processingOverlay.classList.add('active');
                setTimeout(function() 
                {
                    window.location.href = 'homepage.php';
                }, 5000); // 5 seconds
                <?php endif; ?>
            });
        </script>
    </body>
</html>