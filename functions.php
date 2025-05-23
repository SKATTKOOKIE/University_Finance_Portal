<?php
// Function to establish db connection
function connectdb()
{
    $db = new PDO('mysql:host=localhost; dbname=syncforge;', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
}

// Function to get user's account
function getUserAccount($userId)
{
    $db = connectdb();

    // Check if user has an account
    $stmt = $db->prepare("SELECT * FROM accounts WHERE user_id = ?");
    $stmt->execute([ $userId ]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no account exists, create one with zero balance
    if (!$account)
    {
        $stmt = $db->prepare("INSERT INTO accounts (user_id, balance) VALUES (?, 0.00)");
        $stmt->execute([ $userId ]);

        // Get the newly created account
        $stmt = $db->prepare("SELECT * FROM accounts WHERE user_id = ?");
        $stmt->execute([ $userId ]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    return $account;
}

// Function to deposit funds
function depositFunds($accountId, $amount, $description = '')
{
    $db = connectdb();

    try
    {
        $db->beginTransaction();

        // Update account balance
        $stmt = $db->prepare("UPDATE accounts SET balance = balance + ? WHERE account_id = ?");
        $stmt->execute([ $amount, $accountId ]);

        // Create payment record
        $stmt = $db->prepare("INSERT INTO payments (type) VALUES ('deposit')");
        $stmt->execute();
        $paymentId = $db->lastInsertId();

        // Create transaction record
        $stmt = $db->prepare("INSERT INTO transactions (amount, account_id, payment_id, product_id) VALUES (?, ?, ?, 0)");
        $stmt->execute([ $amount, $accountId, $paymentId ]);

        $db->commit();
        return true;
    }
    catch ( Exception $e )
    {
        $db->rollBack();
        error_log("Deposit error: " . $e->getMessage());
        return false;
    }
}

// Function to withdraw funds
function withdrawFunds($accountId, $amount, $description = '')
{
    $db = connectdb();

    try
    {
        // Check if sufficient funds
        $stmt = $db->prepare("SELECT balance FROM accounts WHERE account_id = ?");
        $stmt->execute([ $accountId ]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($account['balance'] < $amount)
        {
            return 'insufficient_funds';
        }

        $db->beginTransaction();

        // Update account balance
        $stmt = $db->prepare("UPDATE accounts SET balance = balance - ? WHERE account_id = ?");
        $stmt->execute([ $amount, $accountId ]);

        // Create payment record
        $stmt = $db->prepare("INSERT INTO payments (type) VALUES ('withdrawal')");
        $stmt->execute();
        $paymentId = $db->lastInsertId();

        // Create transaction record
        // Use negative amount for withdrawals to make it clear in the transaction history
        $stmt = $db->prepare("INSERT INTO transactions (amount, account_id, payment_id, product_id) VALUES (?, ?, ?, 0)");
        $stmt->execute([ (-1 * $amount), $accountId, $paymentId ]);

        $db->commit();
        return true;
    }
    catch ( Exception $e )
    {
        $db->rollBack();
        error_log("Withdrawal error: " . $e->getMessage());
        return false;
    }
}

// Function to get user's current balance
function getUserBalance($userId)
{
    $account = getUserAccount($userId);
    return $account['balance'];
}

// Function to get transaction history for a user
function getUserTransactionHistory($userId, $limit = 10)
{
    $db = connectdb();

    try
    {
        // Get account ID for the user - simplify this query
        $stmt = $db->prepare("SELECT account_id FROM accounts WHERE user_id = ?");
        $stmt->execute([ $userId ]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account)
        {
            return []; // No account, no transactions
        }

        $accountId = $account['account_id'];

        // Completely revised query that avoids potential issues
        $query = "
                SELECT 
                    t.transaction_id, 
                    t.amount, 
                    t.time, 
                    p.type AS payment_type
                FROM 
                    transactions AS t,
                    payments AS p
                WHERE 
                    t.payment_id = p.payment_id 
                    AND t.account_id = :account_id
                ORDER BY 
                    t.time DESC
                LIMIT :limit
            ";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':account_id', $accountId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $transactions;
    }
    catch ( Exception $e )
    {
        error_log("Transaction history error: " . $e->getMessage());
        return [];
    }
}

// Clean version with better error handling and diagnostics
function getUserTransactionHistory_withDiagnostics($userId, $limit = 10)
{
    $db = connectdb();

    try
    {
        // Check if we have valid input
        if (!is_numeric($userId) || $userId <= 0)
        {
            error_log("Invalid user ID: $userId");
            return [];
        }

        // Get account for this user
        $stmt = $db->prepare("SELECT * FROM accounts WHERE user_id = ?");
        $stmt->execute([ $userId ]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account)
        {
            error_log("No account found for user ID: $userId");
            return [];
        }

        $accountId = $account['account_id'];

        // Now get transactions
        $stmt = $db->prepare("
                SELECT t.transaction_id, t.amount, t.time, p.type AS payment_type
                FROM transactions t
                JOIN payments p ON t.payment_id = p.payment_id
                WHERE t.account_id = ?
                ORDER BY t.time DESC
                LIMIT ?
            ");

        $stmt->execute([ $accountId, $limit ]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        error_log("Found " . count($transactions) . " transactions for user ID: $userId (account ID: $accountId)");

        return $transactions;
    }
    catch ( Exception $e )
    {
        error_log("Database error in getUserTransactionHistory: " . $e->getMessage());
        return [];
    }
}

/**
 * Process redirect messages and return error or success messages
 *
 * @param void
 * @return array Associative array with 'error' and 'success' keys
 */
function getRedirectMessages()
{
    $messages = [ 
        'error' => null,
        'success' => null
    ];

    if (isset($_GET['redirect']))
    {
        switch ($_GET['redirect'])
        {
            // Error messages
            case 'failed':
                $messages['error'] = 'Incorrect username or password. Please try again.';
                break;
            case 'missing_fields':
                $messages['error'] = 'Please fill in all required fields.';
                break;
            case 'username_exists':
                $messages['error'] = 'Username already exists. Please choose a different username.';
                break;
            case 'email_exists':
                $messages['error'] = 'Email already exists. Please use a different email address.';
                break;
            case 'password_mismatch':
                $messages['error'] = 'Passwords do not match. Please try again.';
                break;
            case 'invalid_name':
                $messages['error'] = 'Names can only contain letters (no spaces or special characters).';
                break;
            case 'invalid_email':
                $messages['error'] = 'Please enter a valid email address.';
                break;
            case 'invalid_username':
                $messages['error'] = 'Username must be 3-20 characters and contain only letters, numbers, and underscores.';
                break;
            case 'invalid_password':
                $messages['error'] = 'Password must be 8-20 characters and include at least one uppercase letter, one number, and one symbol.';
                break;
            case 'account_not_found':
                $messages['error'] = 'Account not found. Please check your credentials.';
                break;
            case 'insufficient_funds':
                $messages['error'] = 'Insufficient funds for this transaction.';
                break;
            case 'incorrect_password':
                $messages['error'] = 'The current password you entered is incorrect.';
                break;
            case 'update_error':
                $messages['error'] = 'An error occurred while updating your information. Please try again later.';
                break;
            case 'password_updated':
                $messages['success'] = 'Your password has been updated successfully.';
                break;

            // Success messages
            case 'logout':
                $messages['success'] = 'You have been successfully logged out.';
                break;
            case 'registration_success':
                $messages['success'] = 'Account created successfully! You can now login with your credentials.';
                break;
            case 'deposit_success':
                $messages['success'] = 'Funds deposited successfully.';
                break;
            case 'withdrawal_success':
                $messages['success'] = 'Withdrawal completed successfully.';
                break;
            case 'profile_updated':
                $messages['success'] = 'Profile information updated successfully.';
                break;
            case 'success':
                $messages['success'] = 'Operation completed successfully.';
                break;
        }
    }

    return $messages;
}

/**
 * Get user spending summary for different time periods
 * 
 * @param int $userId User ID
 * @return array|bool Array with spending data or false on error
 */
function getUserSpendingSummary($userId)
{
    try
    {
        $db = connectdb();

        // Get account for this user
        $stmt = $db->prepare("SELECT account_id FROM accounts WHERE user_id = ?");
        $stmt->execute([ $userId ]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account)
        {
            return false; // No account found
        }

        $accountId = $account['account_id'];

        // Calculate dates for different time periods
        $currentDate = new DateTime();

        $oneWeekAgo = (clone $currentDate)->modify('-1 week');
        $oneMonthAgo = (clone $currentDate)->modify('-1 month');
        $threeMonthsAgo = (clone $currentDate)->modify('-3 months');
        $sixMonthsAgo = (clone $currentDate)->modify('-6 months');
        $oneYearAgo = (clone $currentDate)->modify('-1 year');

        // Format dates for SQL
        $oneWeekAgoFormatted = $oneWeekAgo->format('Y-m-d H:i:s');
        $oneMonthAgoFormatted = $oneMonthAgo->format('Y-m-d H:i:s');
        $threeMonthsAgoFormatted = $threeMonthsAgo->format('Y-m-d H:i:s');
        $sixMonthsAgoFormatted = $sixMonthsAgo->format('Y-m-d H:i:s');
        $oneYearAgoFormatted = $oneYearAgo->format('Y-m-d H:i:s');

        // Get spending for each time period (only withdrawals/negative transactions)
        $getSpending = function ($fromDate) use ($db, $accountId)
        {
            $stmt = $db->prepare("
                SELECT SUM(ABS(amount)) as total_spent
                FROM transactions
                INNER JOIN payments ON transactions.payment_id = payments.payment_id
                WHERE transactions.account_id = ? 
                AND transactions.amount < 0
                AND transactions.time >= ?
            ");
            $stmt->execute([ $accountId, $fromDate ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total_spent'] ?: 0;
        };

        // Calculate spending for each time period
        $spending = [ 
            'week' => $getSpending($oneWeekAgoFormatted),
            'month' => $getSpending($oneMonthAgoFormatted),
            'three_months' => $getSpending($threeMonthsAgoFormatted),
            'six_months' => $getSpending($sixMonthsAgoFormatted),
            'year' => $getSpending($oneYearAgoFormatted)
        ];

        return $spending;

    }
    catch ( Exception $e )
    {
        error_log("Error getting spending summary: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user details from database
 * 
 * @param int $userId User ID
 * @return array|bool User details or false on error
 */
function getUserDetails($userId)
{
    try
    {
        $db = connectdb();
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([ $userId ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    catch ( Exception $e )
    {
        error_log("Error getting user details: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify user password
 * 
 * @param int $userId User ID
 * @param string $password Password to verify
 * @return bool True if password is valid, false otherwise
 */
function verifyUserPassword($userId, $password)
{
    try
    {
        $db = connectdb();

        // Get user from database
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([ $userId ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user)
        {
            return false;
        }

        // Check if this is a legacy account (before salt was implemented)
        if (empty($user['salt']))
        {
            // For backward compatibility with old accounts
            return ($password === $user['password']);
        }
        else
        {
            // Modern account with salt
            return password_verify($password . $user['salt'], $user['password']);
        }
    }
    catch ( Exception $e )
    {
        error_log("Error verifying password: " . $e->getMessage());
        return false;
    }
}

/**
 * Update user password
 * 
 * @param int $userId User ID
 * @param string $newPassword New password
 * @return bool True on success, false on failure
 */
function updateUserPassword($userId, $newPassword)
{
    try
    {
        $db = connectdb();

        // Generate a new salt
        $salt = bin2hex(random_bytes(16)); // 32 character salt

        // Hash the new password with the salt
        $hashedPassword = password_hash($newPassword . $salt, PASSWORD_BCRYPT);

        // Update the password in the database
        $stmt = $db->prepare("UPDATE users SET password = ?, salt = ? WHERE user_id = ?");
        $stmt->execute([ $hashedPassword, $salt, $userId ]);

        return true;
    }
    catch ( Exception $e )
    {
        error_log("Error updating password: " . $e->getMessage());
        return false;
    }
}


?>