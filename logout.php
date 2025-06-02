<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect with JavaScript to clear cart
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out</title>
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            color: #333;
        }
        .logout-container {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .spinner {
            width: 40px;
            height: 40px;
            margin: 20px auto;
            border: 4px solid rgba(0,0,0,0.1);
            border-radius: 50%;
            border-top: 4px solid #c8a656;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h2 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <h2>Logging Out</h2>
        <div class="spinner"></div>
        <p>Please wait while we log you out...</p>
    </div>
    
    <script>
        // Clear cart data completely
        localStorage.removeItem('tipunoCart');
        localStorage.removeItem('tipunoCartOwner');
        
        // Redirect after a short delay
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 1500);
    </script>
</body>
</html>
