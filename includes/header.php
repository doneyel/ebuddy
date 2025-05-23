<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dancel Tire Supply - <?php echo $pageTitle ?? 'Dashboard'; ?></title>
    <a href="/dancel_tire_system/dashboard.php"></a>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/styles.css" rel="stylesheet">
    <!-- Add Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <!-- Navbar Brand & Sidebar Toggle -->
        <div class="navbar-logo-container">
            <img src="../images/dtslogo.png" alt="Dancel Tire Supply Logo" class="navbar-logo">
            <a class="navbar-brand ps-2" href="../index.php">Dancel Tire Supply</a>
        </div>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" 
                id="sidebarToggle" 
                onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Add logout button on the right side of navbar -->
        <div class="ms-auto">
            <a href="#" onclick="confirmLogout()" class="btn btn-outline-light btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
        </div>
        
        <script>
            function confirmLogout() {
                if (confirm("Are you sure you want to log out?")) {
                    window.location.href = "logout.php";
                }
            }
        </script>

        <script>
            function toggleSidebar() {
                document.body.classList.toggle('sb-sidenav-toggled');
                localStorage.setItem('sb|sidebar-toggle', 
                document.body.classList.contains('sb-sidenav-toggled'));
            }
        </script>
    </nav>
    
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <a class="nav-link active" href="dashboard.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Dashboard
                        </a>
                        
                        <a class="nav-link" href="sales/index.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-shopping-cart"></i></div>
                            Sales
                        </a>
                        <a class="nav-link" href="inventory/index.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-boxes"></i></div>
                            Inventory
                        </a>
                        <a class="nav-link" href="products/index.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tags"></i></div>
                            Products
                        </a>
                        <a class="nav-link" href="customers/index.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                            Customers
                        </a>
                        
                        <a class="nav-link" href="reports/sales.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                            Sales Reports
                        </a>
                        <a class="nav-link" href="reports/inventory.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-clipboard-list"></i></div>
                            Inventory Reports
                        </a>
                    </div>
                </div>
            </nav>
        </div>
        
        <div id="layoutSidenav_content">
            <main></main>