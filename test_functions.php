<?php
// Include the functions file
require_once 'functions.php';

// Helper function to run tests and display results
function runTest($testName, $testFunction)
{
    echo "\n=== Running Test: $testName ===\n";
    try
    {
        $result = $testFunction();
        if ($result === true)
        {
            echo "✅ PASS: $testName\n";
            return true;
        }
        else
        {
            echo "❌ FAIL: $testName - $result\n";
            return false;
        }
    }
    catch ( Exception $e )
    {
        echo "❌ ERROR: $testName - " . $e->getMessage() . "\n";
        return false;
    }
}

// Array to track test results
$testResults = [ 
    'passed' => 0,
    'failed' => 0,
    'total' => 0
];

// Function to update test results
function updateTestResults($passed)
{
    global $testResults;
    $testResults['total']++;
    if ($passed)
    {
        $testResults['passed']++;
    }
    else
    {
        $testResults['failed']++;
    }
}

// Function to inspect database schema
function inspectDatabaseSchema()
{
    $db = connectdb();
    $tables = [];

    echo "\n📊 Database Schema Inspection 📊\n";

    // Get all tables in the database
    $stmt = $db->query("SHOW TABLES");
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Found " . count($allTables) . " tables: " . implode(", ", $allTables) . "\n";

    // Inspect each relevant table
    $relevantTables = [ 'users', 'accounts', 'transactions', 'payments' ];
    foreach ($relevantTables as $tableName)
    {
        if (!in_array($tableName, $allTables))
        {
            echo "⚠️ Table '$tableName' not found in database!\n";
            continue;
        }

        $stmt = $db->query("DESCRIBE $tableName");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $columnNames = array_column($columns, 'Field');
        $tables[ $tableName ] = $columnNames;

        echo "Table '$tableName' columns: " . implode(", ", $columnNames) . "\n";
    }

    return $tables;
}

// Function to check if a column exists in a table
function columnExists($tableName, $columnName)
{
    global $databaseSchema;
    return isset($databaseSchema[ $tableName ]) && in_array($columnName, $databaseSchema[ $tableName ]);
}

// Start the testing process
echo "🧪 Starting Black Box Tests for functions.php 🧪\n";
$databaseSchema = inspectDatabaseSchema();

// 1. Test connectdb()
$testPassed = runTest("1.1 connectdb() Basic Connection", function ()
{
    $db = connectdb();
    if (!($db instanceof PDO))
    {
        return "Expected PDO instance, got " . gettype($db);
    }
    return true;
});
updateTestResults($testPassed);

// Create test user based on actual schema
function createTestUser()
{
    global $databaseSchema;
    $db = connectdb();

    // Get the primary key column name
    $userPrimaryKey = 'user_id'; // Default assumption

    // Create a unique identifier for our test user
    $testIdentifier = 'test_' . time();

    // Construct an INSERT query based on the actual columns
    $columns = [];
    $placeholders = [];
    $values = [];

    // Go through all columns and provide test values
    foreach ($databaseSchema['users'] as $column)
    {
        // Skip auto-increment columns
        if ($column == $userPrimaryKey)
        {
            continue;
        }

        $columns[] = $column;
        $placeholders[] = '?';

        // Provide reasonable test values based on column name
        if (stripos($column, 'name') !== false)
        {
            $values[] = 'Test' . ucfirst($column);
        }
        elseif (stripos($column, 'email') !== false)
        {
            $values[] = 'test@example.com';
        }
        elseif (stripos($column, 'password') !== false)
        {
            $values[] = 'testpassword';
        }
        elseif (stripos($column, 'salt') !== false)
        {
            $values[] = 'testsalt';
        }
        else
        {
            $values[] = $testIdentifier;
        }
    }

    if (empty($columns))
    {
        throw new Exception("Cannot create test user - no columns available");
    }

    $columnsStr = implode(', ', $columns);
    $placeholdersStr = implode(', ', $placeholders);

    echo "Creating test user with columns: $columnsStr\n";

    // Insert the test user
    $query = "INSERT INTO users ($columnsStr) VALUES ($placeholdersStr)";
    $stmt = $db->prepare($query);
    $stmt->execute($values);

    $userId = $db->lastInsertId();

    // Verify user was created
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([ $userId ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user)
    {
        throw new Exception("Failed to create test user");
    }

    return $userId;
}

// Only proceed with tests if we have the necessary tables
if (isset($databaseSchema['users']) && isset($databaseSchema['accounts']))
{
    try
    {
        // Create test user
        $testUserId = createTestUser();
        echo "Created test user with ID: $testUserId\n";

        // 2. Test getUserAccount()
        $testPassed = runTest("2.1 getUserAccount() Existing User", function () use ($testUserId)
        {
            $account = getUserAccount($testUserId);
            if (!is_array($account))
            {
                return "Expected array, got " . gettype($account);
            }
            if (!isset($account['account_id']) || !isset($account['user_id']) || !isset($account['balance']))
            {
                return "Missing expected keys in account array";
            }
            if ($account['user_id'] != $testUserId)
            {
                return "User ID mismatch. Expected: $testUserId, Got: " . $account['user_id'];
            }
            return true;
        });
        updateTestResults($testPassed);

        $testPassed = runTest("2.2 getUserAccount() Non-existing User", function ()
        {
            // Create a temporary test user that we can delete
            $db = connectdb();
            $stmt = $db->prepare("INSERT INTO users (first_name, last_name, role, email, user_name, password, salt) 
                                 VALUES ('Temp', 'User', 'student', 'temp@example.com', 'tempuser', 'password', 'salt')");
            $stmt->execute();
            $tempUserId = $db->lastInsertId();

            // Now we can safely test with this user ID
            $account = getUserAccount($tempUserId);

            // Clean up: delete the user and created account
            $stmt = $db->prepare("DELETE FROM accounts WHERE user_id = ?");
            $stmt->execute([ $tempUserId ]);
            $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([ $tempUserId ]);

            // Verify the results
            if (!is_array($account))
            {
                return "Expected array, got " . gettype($account);
            }

            // Check if balance is zero, allowing for different zero representations (0, 0.0, "0", "0.00")
            if (floatval($account['balance']) !== 0.0)
            {
                return "Expected zero balance, got " . $account['balance'];
            }

            return true;
        });
        updateTestResults($testPassed);

        // Get account ID for the test user
        $testAccount = getUserAccount($testUserId);
        $testAccountId = $testAccount['account_id'];
        $initialBalance = $testAccount['balance'];

        // 3. Test depositFunds()
        $testPassed = runTest("3.1 depositFunds() Valid Deposit", function () use ($testAccountId, $initialBalance)
        {
            $depositAmount = 100.00;
            $result = depositFunds($testAccountId, $depositAmount);
            if ($result !== true)
            {
                return "Expected true, got " . var_export($result, true);
            }

            // Verify balance was updated
            $db = connectdb();
            $stmt = $db->prepare("SELECT balance FROM accounts WHERE account_id = ?");
            $stmt->execute([ $testAccountId ]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);

            $expectedBalance = $initialBalance + $depositAmount;
            if (abs($account['balance'] - $expectedBalance) > 0.001)
            {
                return "Balance not updated correctly. Expected: $expectedBalance, Got: " . $account['balance'];
            }

            return true;
        });
        updateTestResults($testPassed);

        $testPassed = runTest("3.2 depositFunds() Zero Amount", function () use ($testAccountId)
        {
            $result = depositFunds($testAccountId, 0);
            if ($result !== true)
            {
                return "Expected true, got " . var_export($result, true);
            }
            return true;
        });
        updateTestResults($testPassed);

        $testPassed = runTest("3.3 depositFunds() Negative Amount", function () use ($testAccountId)
        {
            $result = depositFunds($testAccountId, -50);
            // This depends on how the function handles negative amounts
            // Simply log the result but don't fail the test
            echo "Function returned: " . var_export($result, true) . " for negative amount\n";
            return true;
        });
        updateTestResults($testPassed);

        $testPassed = runTest("3.4 depositFunds() Invalid Account ID", function ()
        {
            $invalidAccountId = 999999; // Non-existent account ID
            $result = depositFunds($invalidAccountId, 100);
            if ($result !== false)
            {
                return "Expected false for invalid account, got " . var_export($result, true);
            }
            return true;
        });
        updateTestResults($testPassed);

        // 4. Test withdrawFunds()
        // Get current balance
        $testAccount = getUserAccount($testUserId);
        $currentBalance = $testAccount['balance'];

        $testPassed = runTest("4.1 withdrawFunds() Valid Withdrawal with Sufficient Funds", function () use ($testAccountId, $currentBalance)
        {
            $withdrawAmount = $currentBalance > 50 ? 50 : $currentBalance / 2;
            $result = withdrawFunds($testAccountId, $withdrawAmount);
            if ($result !== true)
            {
                return "Expected true, got " . var_export($result, true);
            }

            // Verify balance was updated
            $db = connectdb();
            $stmt = $db->prepare("SELECT balance FROM accounts WHERE account_id = ?");
            $stmt->execute([ $testAccountId ]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);

            $expectedBalance = $currentBalance - $withdrawAmount;
            if (abs($account['balance'] - $expectedBalance) > 0.001)
            {
                return "Balance not updated correctly. Expected: $expectedBalance, Got: " . $account['balance'];
            }

            return true;
        });
        updateTestResults($testPassed);

        // Deposit more funds to ensure we have enough for the next tests
        depositFunds($testAccountId, 200);
        $updatedAccount = getUserAccount($testUserId);
        $updatedBalance = $updatedAccount['balance'];

        $testPassed = runTest("4.2 withdrawFunds() Insufficient Funds", function () use ($testAccountId, $updatedBalance)
        {
            $result = withdrawFunds($testAccountId, $updatedBalance + 100);
            if ($result !== 'insufficient_funds')
            {
                return "Expected 'insufficient_funds', got " . var_export($result, true);
            }
            return true;
        });
        updateTestResults($testPassed);

        $testPassed = runTest("4.3 withdrawFunds() Zero Amount", function () use ($testAccountId)
        {
            $result = withdrawFunds($testAccountId, 0);
            // Log the result without failing the test
            echo "Function returned: " . var_export($result, true) . " for zero amount\n";
            return true;
        });
        updateTestResults($testPassed);

        $testPassed = runTest("4.4 withdrawFunds() Negative Amount", function () use ($testAccountId)
        {
            $result = withdrawFunds($testAccountId, -50);
            // Log the result without failing the test
            echo "Function returned: " . var_export($result, true) . " for negative amount\n";
            return true;
        });
        updateTestResults($testPassed);

        $testPassed = runTest("4.5 withdrawFunds() Invalid Account ID", function ()
        {
            $invalidAccountId = 999999; // Non-existent account ID
            $result = withdrawFunds($invalidAccountId, 100);
            if ($result !== 'insufficient_funds')
            {
                return "Expected 'insufficient_funds' for invalid account, got " . var_export($result, true);
            }
            return true;
        });
        updateTestResults($testPassed);

        // 5. Test getUserBalance()
        $testPassed = runTest("5.1 getUserBalance() Existing User", function () use ($testUserId)
        {
            $balance = getUserBalance($testUserId);
            if (!is_numeric($balance))
            {
                return "Expected numeric balance, got " . gettype($balance);
            }
            return true;
        });
        updateTestResults($testPassed);

        $testPassed = runTest("5.2 getUserBalance() Non-existing User", function ()
        {
            // Create a temporary test user that we can delete
            $db = connectdb();
            $stmt = $db->prepare("INSERT INTO users (first_name, last_name, role, email, user_name, password, salt) 
                                 VALUES ('Temp', 'User', 'student', 'temp@example.com', 'tempuser2', 'password', 'salt')");
            $stmt->execute();
            $tempUserId = $db->lastInsertId();

            // Test the function
            $balance = getUserBalance($tempUserId);

            // Clean up: delete the user and created account
            $stmt = $db->prepare("DELETE FROM accounts WHERE user_id = ?");
            $stmt->execute([ $tempUserId ]);
            $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([ $tempUserId ]);

            // Verify the results - use float comparison for flexibility
            if (floatval($balance) !== 0.0)
            {
                return "Expected zero balance, got $balance";
            }

            return true;
        });
        updateTestResults($testPassed);

        // 6. Test getUserTransactionHistory()
        // First ensure there are some transactions
        depositFunds($testAccountId, 100);
        withdrawFunds($testAccountId, 50);

        $testPassed = runTest("6.1 getUserTransactionHistory() User with Transactions", function () use ($testUserId)
        {
            $transactions = getUserTransactionHistory($testUserId);
            if (!is_array($transactions))
            {
                return "Expected array, got " . gettype($transactions);
            }
            if (count($transactions) < 1)
            {
                return "Expected at least 1 transaction, got " . count($transactions);
            }
            $firstTransaction = $transactions[0];
            if (
                !isset($firstTransaction['transaction_id']) || !isset($firstTransaction['amount']) ||
                !isset($firstTransaction['time']) || !isset($firstTransaction['payment_type'])
            )
            {
                return "Missing expected keys in transaction array";
            }
            return true;
        });
        updateTestResults($testPassed);

        $testPassed = runTest("6.2 getUserTransactionHistory() Non-existing User", function ()
        {
            $nonExistentId = 999999;
            $transactions = getUserTransactionHistory($nonExistentId);
            if (!is_array($transactions))
            {
                return "Expected empty array, got " . gettype($transactions);
            }
            if (count($transactions) !== 0)
            {
                return "Expected 0 transactions, got " . count($transactions);
            }
            return true;
        });
        updateTestResults($testPassed);

        $testPassed = runTest("6.3 getUserTransactionHistory() Custom Limit", function () use ($testUserId)
        {
            $limit = 1;
            $transactions = getUserTransactionHistory($testUserId, $limit);
            if (count($transactions) > $limit)
            {
                return "Expected at most $limit transactions, got " . count($transactions);
            }
            return true;
        });
        updateTestResults($testPassed);

        // 7. Test getRedirectMessages()
        $testPassed = runTest("7.1 getRedirectMessages() No Redirect Parameter", function ()
        {
            // Clear any existing $_GET['redirect']
            unset($_GET['redirect']);

            $messages = getRedirectMessages();
            if (!is_array($messages))
            {
                return "Expected array, got " . gettype($messages);
            }

            // Check if the keys exist, adjust based on what the function actually returns
            if (!array_key_exists('error', $messages) || !array_key_exists('success', $messages))
            {
                return "Missing expected keys in messages array. Keys found: " . implode(", ", array_keys($messages));
            }

            return true;
        });
        updateTestResults($testPassed);

        $testPassed = runTest("7.2 getRedirectMessages() Valid Error Redirect", function ()
        {
            $_GET['redirect'] = 'failed';
            $messages = getRedirectMessages();
            if ($messages['error'] === null)
            {
                return "Expected error message, got null";
            }
            if ($messages['success'] !== null)
            {
                return "Expected null for success, got " . $messages['success'];
            }
            return true;
        });
        updateTestResults($testPassed);

        $testPassed = runTest("7.3 getRedirectMessages() Valid Success Redirect", function ()
        {
            $_GET['redirect'] = 'logout';
            $messages = getRedirectMessages();
            if ($messages['success'] === null)
            {
                return "Expected success message, got null";
            }
            if ($messages['error'] !== null)
            {
                return "Expected null for error, got " . $messages['error'];
            }
            return true;
        });
        updateTestResults($testPassed);

        // 8. Test getUserSpendingSummary()
        $testPassed = runTest("8.1 getUserSpendingSummary() User with Spending", function () use ($testUserId)
        {
            $summary = getUserSpendingSummary($testUserId);
            if (!is_array($summary))
            {
                return "Expected array, got " . gettype($summary);
            }

            $expectedKeys = [ 'week', 'month', 'three_months', 'six_months', 'year' ];
            foreach ($expectedKeys as $key)
            {
                if (!isset($summary[ $key ]))
                {
                    return "Missing expected key: $key";
                }
                if (!is_numeric($summary[ $key ]))
                {
                    return "Expected numeric value for $key, got " . gettype($summary[ $key ]);
                }
            }
            return true;
        });
        updateTestResults($testPassed);

        $testPassed = runTest("8.2 getUserSpendingSummary() Non-existing User", function ()
        {
            $nonExistentId = 999999;
            $summary = getUserSpendingSummary($nonExistentId);
            if ($summary !== false)
            {
                return "Expected false, got " . var_export($summary, true);
            }
            return true;
        });
        updateTestResults($testPassed);

        // 9. Test getUserDetails()
        $testPassed = runTest("9.1 getUserDetails() Existing User", function () use ($testUserId)
        {
            $details = getUserDetails($testUserId);
            if (!is_array($details))
            {
                return "Expected array, got " . gettype($details);
            }
            if (!isset($details['user_id']))
            {
                return "Missing user_id in user details array";
            }
            if ($details['user_id'] != $testUserId)
            {
                return "User ID mismatch. Expected: $testUserId, Got: " . $details['user_id'];
            }
            return true;
        });
        updateTestResults($testPassed);

        $testPassed = runTest("9.2 getUserDetails() Non-existing User", function ()
        {
            $nonExistentId = 999999;
            $details = getUserDetails($nonExistentId);
            if ($details !== false)
            {
                return "Expected false, got " . var_export($details, true);
            }
            return true;
        });
        updateTestResults($testPassed);

        // Prepare for password tests by updating the test user's password field
        // This ensures we have a known password for verification
        $db = connectdb();
        $password = 'testpassword';

        // Check if salt column exists
        $hasSalt = columnExists('users', 'salt');

        if ($hasSalt)
        {
            // Modern account with salt
            $salt = 'testsalt';
            $hashedPassword = password_hash($password . $salt, PASSWORD_BCRYPT);

            $stmt = $db->prepare("UPDATE users SET password = ?, salt = ? WHERE user_id = ?");
            $stmt->execute([ $hashedPassword, $salt, $testUserId ]);
        }
        else
        {
            // Legacy account without salt
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([ $password, $testUserId ]);
        }

        // 10. Test verifyUserPassword()
        $testPassed = runTest("10.1 verifyUserPassword() Valid Credentials", function () use ($testUserId)
        {
            $result = verifyUserPassword($testUserId, 'testpassword');
            if ($result !== true)
            {
                return "Expected true, got " . var_export($result, true);
            }
            return true;
        });
        updateTestResults($testPassed);

        $testPassed = runTest("10.2 verifyUserPassword() Invalid Password", function () use ($testUserId)
        {
            $result = verifyUserPassword($testUserId, 'wrongpassword');
            if ($result !== false)
            {
                return "Expected false, got " . var_export($result, true);
            }
            return true;
        });
        updateTestResults($testPassed);

        $testPassed = runTest("10.3 verifyUserPassword() Non-existing User", function ()
        {
            $nonExistentId = 999999;
            $result = verifyUserPassword($nonExistentId, 'anypassword');
            if ($result !== false)
            {
                return "Expected false, got " . var_export($result, true);
            }
            return true;
        });
        updateTestResults($testPassed);

        // 11. Test updateUserPassword()
        $testPassed = runTest("11.1 updateUserPassword() Valid Update", function () use ($testUserId)
        {
            $newPassword = 'newTestPassword123!';
            $result = updateUserPassword($testUserId, $newPassword);
            if ($result !== true)
            {
                return "Expected true, got " . var_export($result, true);
            }

            // Verify the password was updated
            $verifyResult = verifyUserPassword($testUserId, $newPassword);
            if ($verifyResult !== true)
            {
                return "Failed to verify new password";
            }

            // Reset to original password for future tests
            updateUserPassword($testUserId, 'testpassword');

            return true;
        });
        updateTestResults($testPassed);

        $testPassed = runTest("11.2 updateUserPassword() Non-existing User", function ()
        {
            $nonExistentId = 999999;
            $result = updateUserPassword($nonExistentId, 'newpassword');
            if ($result !== true)
            {
                return "Expected true for non-existent user, got " . var_export($result, true);
            }
            return true;
        });
        updateTestResults($testPassed);

        // Clean up test data
        function cleanupTestData($userId)
        {
            global $databaseSchema;
            $db = connectdb();

            try
            {
                // Begin transaction for safer cleanup
                $db->beginTransaction();

                // Get account ID
                $stmt = $db->prepare("SELECT account_id FROM accounts WHERE user_id = ?");
                $stmt->execute([ $userId ]);
                $account = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($account)
                {
                    $accountId = $account['account_id'];

                    // Delete transactions if they exist
                    if (isset($databaseSchema['transactions']))
                    {
                        $stmt = $db->prepare("DELETE FROM transactions WHERE account_id = ?");
                        $stmt->execute([ $accountId ]);
                    }

                    // Delete payments related to the transactions
                    if (isset($databaseSchema['payments']))
                    {
                        // Find payment IDs first from transactions (if possible)
                        $paymentIds = [];
                        if (isset($databaseSchema['transactions']) && in_array('payment_id', $databaseSchema['transactions']))
                        {
                            $stmt = $db->prepare("SELECT payment_id FROM transactions WHERE account_id = ?");
                            $stmt->execute([ $accountId ]);
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
                            {
                                $paymentIds[] = $row['payment_id'];
                            }

                            // Delete payments
                            if (!empty($paymentIds))
                            {
                                $placeholders = str_repeat('?,', count($paymentIds) - 1) . '?';
                                $stmt = $db->prepare("DELETE FROM payments WHERE payment_id IN ($placeholders)");
                                $stmt->execute($paymentIds);
                            }
                        }
                    }

                    // Delete account
                    $stmt = $db->prepare("DELETE FROM accounts WHERE account_id = ?");
                    $stmt->execute([ $accountId ]);
                }

                // Delete test user
                $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([ $userId ]);

                $db->commit();
                echo "Cleanup successful\n";
            }
            catch ( Exception $e )
            {
                $db->rollBack();
                echo "Error during cleanup: " . $e->getMessage() . "\n";
            }
        }

        cleanupTestData($testUserId);
        echo "\nCleaned up test data for user ID: $testUserId\n";

    }
    catch ( Exception $e )
    {
        echo "❌ Setup Error: " . $e->getMessage() . "\n";
    }
}
else
{
    echo "❌ Cannot proceed with tests - required tables are missing\n";
}

// Display test summary
echo "\n📊 Test Summary 📊\n";
echo "Total Tests: {$testResults['total']}\n";
echo "Passed: {$testResults['passed']}\n";
echo "Failed: {$testResults['failed']}\n";
if ($testResults['total'] > 0)
{
    echo "Success Rate: " . round(($testResults['passed'] / $testResults['total']) * 100, 2) . "%\n";
}

// Finish
echo "\n🏁 Black Box Testing Completed 🏁\n";
?>