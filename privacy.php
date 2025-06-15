<?php
$back = isset($_GET['from']) && preg_match('/^[a-zA-Z0-9\-_\.]+\.php$/', $_GET['from']) ? $_GET['from'] : 'index.php';
?>
<link rel="stylesheet" href="css/about.css">
<link rel="stylesheet" href="css/style.css">

<div class="about-container">
    <h1>Privacy Policy</h1>
    <div class="about-content">
        <img src="images/logo2.jpg" alt="Privacy Policy" class="about-image">
        <div class="about-text">
            <h2>Your Privacy Matters</h2>
            <p>
                We value your privacy and are committed to protecting your personal information. This Privacy Policy explains how we collect, use, and safeguard your data when you visit our website or use our services.
            </p>
            <h2>Information We Collect</h2>
            <ul>
                <li>Personal details you provide (such as name, email, or phone number) when booking appointments or contacting us.</li>
                <li>Non-personal information such as browser type, device, and usage data for analytics.</li>
            </ul>
            <h2>How We Use Your Information</h2>
            <ul>
                <li>To provide and improve our services.</li>
                <li>To communicate with you regarding appointments or inquiries.</li>
                <li>To ensure the security and functionality of our website.</li>
            </ul>
            <h2>Your Rights</h2>
            <ul>
                <li>You can request to view, update, or delete your personal information at any time.</li>
                <li>Your data will never be sold or shared with third parties without your consent, except as required by law.</li>
            </ul>
            <a href="<?= htmlspecialchars($back) ?>" class="btn btn-secondary">&#8592; Go Back</a>
        </div>
    </div>
