<?php
// A simple script to clear the browser cache for CSS and JS files

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cache Cleared</title>
    <link rel="stylesheet" href="refreshStyles.css">
</head>
<body>
    <div class="container">
        <h1>Refreshing Application Cache</h1>
        <div class="spinner"></div>
        <p>Clearing your browser cache for CSS and JavaScript files...</p>
        <p>You will be redirected to the homepage in 3 seconds.</p>
        <a class="button" href="homepage.php">Go to Homepage Now</a>
    </div>
    
    <script>
        // Add timestamp to CSS links to force reload
        const links = document.querySelectorAll('link[rel="stylesheet"]');
        links.forEach(link => 
        {
            link.href = link.href + '?v=' + new Date().getTime();
        });
        
        // Redirect after 3 seconds
        setTimeout(() => 
        {
            window.location.href = 'homepage.php';
        }, 3000);
    </script>
</body>
</html>