<div class="sidebar">
    <div class="logo">
        <h2>Tipuno</h2>
    </div>
    <div class="menu">
        <ul>
            <li class="<?php echo $page == 'dashboard' ? 'active' : ''; ?>">
                <a href="admin_index.php?page=dashboard">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="<?php echo $page == 'appointments' ? 'active' : ''; ?>">
                <a href="admin_index.php?page=appointments">
                    <i class="fas fa-calendar-check"></i> Appointments
                    <?php
                    // Get count of pending appointments
                    $db = new PDO('mysql:host=localhost;dbname=barbershop', 'root', '');
                    $stmt = $db->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'");
                    $pendingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    if ($pendingCount > 0) {
                        echo '<span class="badge">' . $pendingCount . '</span>';
                    }
                    ?>
                </a>
            </li>
            <li class="<?php echo $page == 'orders' ? 'active' : ''; ?>">
                <a href="admin_index.php?page=orders">
                    <i class="fas fa-shopping-cart"></i> Orders
                    <?php
                    // Get count of pending orders
                    $db = new PDO('mysql:host=localhost;dbname=barbershop', 'root', '');
                    $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
                    $pendingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    if ($pendingCount > 0) {
                        echo '<span class="badge">' . $pendingCount . '</span>';
                    }
                    ?>
                </a>
            </li>
            <li class="<?php echo $page == 'products' ? 'active' : ''; ?>">
                <a href="admin_index.php?page=products">
                    <i class="fas fa-shopping-bag"></i> Products
                </a>
            </li>
            <li class="<?php echo $page == 'barbers' ? 'active' : ''; ?>">
                <a href="admin_index.php?page=barbers">
                    <i class="fas fa-cut"></i> Barbers
                </a>
            </li>
            <li class="<?php echo $page == 'messages' ? 'active' : ''; ?>">
                <a href="admin_index.php?page=messages">
                    <i class="fas fa-envelope"></i> Messages
                    <?php
                    // Get count of unread messages
                    $db = new PDO('mysql:host=localhost;dbname=barbershop', 'root', '');
                    $stmt = $db->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'new'");
                    $unreadCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    if ($unreadCount > 0) {
                        echo '<span class="badge">' . $unreadCount . '</span>';
                    }
                    ?>
                </a>
            </li>
            <li>
                <a href="../index.php" target="_blank">
                    <i class="fas fa-external-link-alt"></i> View Website
                </a>
            </li>
            <li>
                <a href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>
