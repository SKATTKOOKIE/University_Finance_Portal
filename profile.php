<?php
session_start();
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] != TRUE)
{
    header('Location: index.php');
    exit;
}

// Get any redirect messages
$messages = getRedirectMessages();
$errorMessage = $messages['error'];
$successMessage = $messages['success'];

// Get user information
$userId = $_SESSION['userid'];
$userName = $_SESSION['username'];
$firstName = $_SESSION['firstname'];

// Get user details from database
try
{
    $db = connectdb();
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([ $userId ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // If user not found (shouldn't happen but just in case)
    if (!$user)
    {
        header('Location: logout.php');
        exit;
    }

    // Get spending summary using the function from functions.php
    $spending = getUserSpendingSummary($userId);

    if ($spending)
    {
        $weekSpending = $spending['week'];
        $monthSpending = $spending['month'];
        $threeMonthSpending = $spending['three_months'];
        $sixMonthSpending = $spending['six_months'];
        $yearSpending = $spending['year'];
    }
    else
    {
        // Default values if function fails
        $weekSpending = $monthSpending = $threeMonthSpending = $sixMonthSpending = $yearSpending = 0;
    }

}
catch ( PDOException $e )
{
    // Log the error
    error_log("Profile page error: " . $e->getMessage());
    $errorMessage = "An error occurred while loading your profile. Please try again later.";
}

require_once('navbar.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Finance Portal</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .profile-header {
            text-align: center;
            color: var(--pastel-purple);
            margin-bottom: 2rem;
            font-size: 1.8rem;
            font-weight: bold;
        }

        .profile-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--light-grey);
        }

        .profile-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .section-title {
            color: var(--pastel-purple);
            font-size: 1.3rem;
            margin-bottom: 1rem;
        }

        .profile-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .info-item {
            margin-bottom: 1rem;
        }

        .info-label {
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 0.3rem;
            display: block;
        }

        .info-value {
            padding: 0.8rem;
            background-color: var(--off-white);
            border-radius: 4px;
            border: 1px solid var(--light-grey);
            width: 100%;
            box-sizing: border-box;
        }

        .info-text {
            font-size: 1rem;
            color: var(--text-dark);
        }

        .spending-summary {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .spending-card {
            background-color: var(--off-white);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            border-left: 4px solid var(--pastel-purple);
        }

        .spending-period {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        .spending-amount {
            font-size: 1.5rem;
            color: #dd0000;
            font-weight: bold;
        }

        .tabs {
            display: flex;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--light-grey);
        }

        .tab {
            padding: 0.8rem 1.5rem;
            cursor: pointer;
            color: var(--text-dark);
            transition: all 0.3s ease;
            border-bottom: 2px solid transparent;
            margin-right: 0.5rem;
        }

        .tab.active {
            color: var(--pastel-purple);
            border-bottom: 2px solid var(--pastel-purple);
            font-weight: bold;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .password-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-input-container input {
            flex: 1;
            padding-right: 40px;
            /* Space for the eye button */
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-dark);
            opacity: 0.7;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            opacity: 1;
        }

        .password-toggle svg {
            width: 18px;
            height: 18px;
        }

        @media (max-width: 768px) {
            .profile-info {
                grid-template-columns: 1fr;
            }

            .spending-summary {
                grid-template-columns: 1fr 1fr;
            }

            .tabs {
                flex-wrap: wrap;
            }

            .tab {
                padding: 0.6rem 1rem;
            }
        }

        @media (max-width: 480px) {
            .spending-summary {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php renderNavbar('Finance Portal', 'profile'); ?>

    <div class="profile-container">
        <h1 class="profile-header">My Profile</h1>

        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-error"
                style="background-color: #ffeeee; color: #dd0000; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success"
                style="background-color: #eeffee; color: #00aa00; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <div class="tab active" data-tab="account-info">Account Information</div>
            <div class="tab" data-tab="change-password">Change Password</div>
            <div class="tab" data-tab="spending-summary">Spending Summary</div>
        </div>

        <!-- Account Information Tab -->
        <div class="tab-content active" id="account-info">
            <div class="profile-section">
                <h2 class="section-title">Personal Information</h2>
                <div class="profile-info">
                    <div class="info-item">
                        <label class="info-label">User ID</label>
                        <div class="info-value">
                            <span class="info-text"><?php echo htmlspecialchars($user['user_id']); ?></span>
                        </div>
                    </div>

                    <div class="info-item">
                        <label class="info-label">Username</label>
                        <div class="info-value">
                            <span class="info-text"><?php echo htmlspecialchars($user['user_name']); ?></span>
                        </div>
                    </div>

                    <div class="info-item">
                        <label class="info-label">First Name</label>
                        <div class="info-value">
                            <span class="info-text"><?php echo htmlspecialchars($user['first_name']); ?></span>
                        </div>
                    </div>

                    <div class="info-item">
                        <label class="info-label">Last Name</label>
                        <div class="info-value">
                            <span class="info-text"><?php echo htmlspecialchars($user['last_name']); ?></span>
                        </div>
                    </div>

                    <div class="info-item">
                        <label class="info-label">Email</label>
                        <div class="info-value">
                            <span class="info-text"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                    </div>

                    <div class="info-item">
                        <label class="info-label">Account Type</label>
                        <div class="info-value">
                            <span class="info-text">
                                <?php
                                if ($user['role'] == 'A')
                                {
                                    echo 'Administrator';
                                }
                                else
                                {
                                    echo 'Standard User';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Change Password Tab -->
        <div class="tab-content" id="change-password">
            <div class="profile-section">
                <h2 class="section-title">Change Password</h2>
                <form action="updatePassword.php" method="post" id="passwordForm" class="login-form">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <div class="password-input-container">
                            <input type="password" name="current_password" id="current_password" required>
                            <button type="button" class="password-toggle" data-target="current_password">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="eye-icon">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="eye-slash-icon" style="display:none">
                                    <path
                                        d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24">
                                    </path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="password-input-container">
                            <input type="password" name="new_password" id="new_password" minlength="8" maxlength="20"
                                required>
                            <button type="button" class="password-toggle" data-target="new_password">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="eye-icon">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="eye-slash-icon" style="display:none">
                                    <path
                                        d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24">
                                    </path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                        <small class="form-hint">8-20 characters, must include a number, a symbol, and a capital
                            letter</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="password-input-container">
                            <input type="password" name="confirm_password" id="confirm_password" minlength="8"
                                maxlength="20" required>
                            <button type="button" class="password-toggle" data-target="confirm_password">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="eye-icon">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="eye-slash-icon" style="display:none">
                                    <path
                                        d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24">
                                    </path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div id="password-requirements"
                        style="margin-bottom: 15px; padding: 10px; background-color: #f8f8f8; border-radius: 4px; display: none;">
                        <p style="margin-top: 0; font-weight: bold;">Password must contain:</p>
                        <ul style="margin-bottom: 0; padding-left: 20px;">
                            <li id="length-check">8-20 characters</li>
                            <li id="uppercase-check">At least one uppercase letter</li>
                            <li id="number-check">At least one number</li>
                            <li id="symbol-check">At least one symbol</li>
                        </ul>
                    </div>

                    <button type="submit" class="login-button">Update Password</button>
                </form>
            </div>
        </div>

        <!-- Spending Summary Tab -->
        <div class="tab-content" id="spending-summary">
            <div class="profile-section">
                <h2 class="section-title">Spending Summary</h2>
                <p>View your spending over different time periods:</p>

                <div class="spending-summary">
                    <div class="spending-card">
                        <div class="spending-period">Past Week</div>
                        <div class="spending-amount">£<?php echo number_format($weekSpending, 2); ?></div>
                    </div>

                    <div class="spending-card">
                        <div class="spending-period">Past Month</div>
                        <div class="spending-amount">£<?php echo number_format($monthSpending, 2); ?></div>
                    </div>

                    <div class="spending-card">
                        <div class="spending-period">Past 3 Months</div>
                        <div class="spending-amount">£<?php echo number_format($threeMonthSpending, 2); ?></div>
                    </div>

                    <div class="spending-card">
                        <div class="spending-period">Past 6 Months</div>
                        <div class="spending-amount">£<?php echo number_format($sixMonthSpending, 2); ?></div>
                    </div>

                    <div class="spending-card">
                        <div class="spending-period">Past Year</div>
                        <div class="spending-amount">£<?php echo number_format($yearSpending, 2); ?></div>
                    </div>
                </div>

                <div style="margin-top: 2rem; text-align: center;">
                    <a href="transactionsPage.php" class="action-button">View All Transactions</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Load the external validation script -->
    <script src="form-validation.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Tab switching functionality
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', function () {
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));

                    // Add active class to clicked tab
                    this.classList.add('active');

                    // Show corresponding content
                    const tabContentId = this.getAttribute('data-tab');
                    document.getElementById(tabContentId).classList.add('active');
                });
            });

            // Initialize password validation
            if (document.getElementById('passwordForm')) {
                initPasswordValidation('passwordForm',
                    {
                        passwordId: 'new_password',
                        confirmPasswordId: 'confirm_password',
                        showRequirements: true
                    });
            }

            // Password visibility toggle functionality
            const passwordToggles = document.querySelectorAll('.password-toggle');
            let currentlyVisible = null;

            passwordToggles.forEach(toggle => {
                toggle.addEventListener('click', function () {
                    const targetId = this.getAttribute('data-target');
                    const passwordInput = document.getElementById(targetId);
                    const eyeIcon = this.querySelector('.eye-icon');
                    const eyeSlashIcon = this.querySelector('.eye-slash-icon');

                    // If another password field is currently visible, hide it first
                    if (currentlyVisible && currentlyVisible !== passwordInput) {
                        const otherToggle = document.querySelector(`.password-toggle[data-target="${currentlyVisible.id}"]`);
                        const otherEyeIcon = otherToggle.querySelector('.eye-icon');
                        const otherEyeSlashIcon = otherToggle.querySelector('.eye-slash-icon');

                        currentlyVisible.type = 'password';
                        otherEyeIcon.style.display = 'block';
                        otherEyeSlashIcon.style.display = 'none';
                    }

                    // Toggle current password visibility
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        eyeIcon.style.display = 'none';
                        eyeSlashIcon.style.display = 'block';
                        currentlyVisible = passwordInput;
                    }
                    else {
                        passwordInput.type = 'password';
                        eyeIcon.style.display = 'block';
                        eyeSlashIcon.style.display = 'none';
                        currentlyVisible = null;
                    }
                });
            });
        });
    </script>
</body>

</html>