<?php
/**
 * Reusable Navbar Component with Responsive Hamburger Menu
 * 
 * Include this file in any page where you need the navbar.
 * Make sure the session is already started before including this file.
 */

// Check if user is logged in
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === TRUE;
$firstName = $isLoggedIn ? $_SESSION['firstname'] : '';
// Get user role - assuming it's stored in session
$userRole = $isLoggedIn && isset($_SESSION['role']) ? $_SESSION['role'] : '';
$isAdmin = $userRole === 'A'; // 'A' is admin role according to the database

/**
 * Function to generate the navbar
 * 
 * @param string $siteName - The name of the site to display on the left
 * @param string $activePage - The current active page (home, about, transactions, admin)
 * @return void - Outputs the navbar HTML
 */
function renderNavbar($siteName = 'Finance Portal', $activePage = '')
{
    global $isLoggedIn, $firstName, $isAdmin;
    ?>
    <link rel="stylesheet" href="style.css">

    <nav class="navbar">
        <a href="homePage.php" class="navbar-brand"><?php echo htmlspecialchars($siteName); ?></a>

        <?php if ($isLoggedIn): ?>
            <div class="navbar-links">
                <!-- Hamburger menu for mobile -->
                <div class="hamburger-menu" id="hamburgerMenu">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

                <!-- Navigation links that will be hidden on mobile -->
                <div class="nav-links-container" id="navLinksContainer">
                    <a href="homePage.php" class="nav-link <?php echo ($activePage === 'home') ? 'active' : ''; ?>">Home</a>
                    <a href="aboutPage.php" class="nav-link <?php echo ($activePage === 'about') ? 'active' : ''; ?>">About</a>
                    <a href="transactionsPage.php"
                        class="nav-link <?php echo ($activePage === 'transactions') ? 'active' : ''; ?>">Transactions</a>
                    <?php if ($isAdmin): ?>
                        <a href="adminPage.php" class="nav-link <?php echo ($activePage === 'admin') ? 'active' : ''; ?>">Admin</a>
                    <?php endif; ?>
                </div>

                <!-- User profile always visible even on mobile -->
                <div class="user-profile">
                    <div class="profile-icon" id="profileIcon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="user-name">Hello, <?php echo htmlspecialchars($firstName); ?></div>
                        <a href="profile.php" class="dropdown-item">View Profile</a>
                        <?php if ($isAdmin): ?>
                            <a href="adminPage.php" class="dropdown-item">Admin Panel</a>
                        <?php endif; ?>
                        <a href="logout.php" class="dropdown-item logout-item">Logout</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="navbar-links">
                <!-- Hamburger menu for mobile -->
                <div class="hamburger-menu" id="hamburgerMenu">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

                <!-- Navigation links that will be hidden on mobile -->
                <div class="nav-links-container" id="navLinksContainer">
                    <a href="about.php" class="nav-link <?php echo ($activePage === 'about') ? 'active' : ''; ?>">About</a>
                </div>

                <!-- Login link always visible -->
                <a href="index.php" class="nav-link">Login</a>
            </div>
        <?php endif; ?>
    </nav>

    <!-- Javascript for dropdown and hamburger menu toggle -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // User profile dropdown functionality
            const profileIcon = document.getElementById('profileIcon');
            const profileDropdown = document.getElementById('profileDropdown');

            if (profileIcon) {
                profileIcon.addEventListener('click', function (event) {
                    profileDropdown.classList.toggle('show');
                    event.stopPropagation();
                });
            }

            // Hamburger menu functionality
            const hamburgerMenu = document.getElementById('hamburgerMenu');
            const navLinksContainer = document.getElementById('navLinksContainer');

            if (hamburgerMenu) {
                hamburgerMenu.addEventListener('click', function (event) {
                    navLinksContainer.classList.toggle('active');
                    event.stopPropagation();
                });
            }

            // Close both dropdown and menu when clicking outside
            document.addEventListener('click', function (event) {
                // Close profile dropdown if clicking outside
                if (profileDropdown && profileIcon &&
                    !profileIcon.contains(event.target) &&
                    !profileDropdown.contains(event.target)) {
                    profileDropdown.classList.remove('show');
                }

                // Close hamburger menu if clicking outside
                if (navLinksContainer && hamburgerMenu &&
                    !hamburgerMenu.contains(event.target) &&
                    !navLinksContainer.contains(event.target)) {
                    navLinksContainer.classList.remove('active');
                }
            });
        });
    </script>
    <?php
}
?>