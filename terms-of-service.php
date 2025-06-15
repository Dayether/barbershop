<?php
$back = isset($_GET['from']) && preg_match('/^[a-zA-Z0-9\-_\.]+\.php$/', $_GET['from']) ? $_GET['from'] : 'index.php';
?>
<link rel="stylesheet" href="css/about.css">
<link rel="stylesheet" href="css/style.css">

<div id="terms-of-service" class="about-container">
    <h1>Terms of Service</h1>
    <div class="about-content">
        <img src="images/logo2.jpg" alt="Terms of Service" class="about-image">
        <div class="about-text">
            <h2>Welcome to Our Barbershop</h2>
            <p>
                By accessing or using our website and services, you agree to comply with and be bound by the following terms and conditions. Please read them carefully.
            </p>
            <h2>Use of Services</h2>
            <ul>
                <li>Our services are intended for personal, non-commercial use only.</li>
                <li>You agree to provide accurate information when booking appointments or contacting us.</li>
                <li>We reserve the right to refuse service to anyone for any reason at any time.</li>
            </ul>
            <h2>Appointments & Cancellations</h2>
            <ul>
                <li>Please arrive on time for your appointment. Late arrivals may result in rescheduling.</li>
                <li>If you need to cancel or reschedule, please notify us as soon as possible.</li>
            </ul>
            <h2>Intellectual Property</h2>
            <ul>
                <li>All content on this website, including text, images, and logos, is the property of our barbershop and may not be used without permission.</li>
            </ul>
            <h2>Limitation of Liability</h2>
            <ul>
                <li>We strive to provide accurate information, but we do not guarantee the completeness or accuracy of content on our website.</li>
                <li>We are not liable for any damages arising from the use of our website or services.</li>
            </ul>
            <h2>Changes to Terms</h2>
            <ul>
                <li>We may update these terms from time to time. Continued use of our website constitutes acceptance of any changes.</li>
            </ul>
            <a href="<?= htmlspecialchars($back) ?>" class="btn btn-secondary">&#8592; Go Back</a>
        </div>
    </div>
</div>