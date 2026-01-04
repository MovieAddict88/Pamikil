<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karaoke App</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div id="app">
        <header class="app-header">
            <div class="container">
                <div class="header-content">
                    <div class="logo">
                        <i class="fas fa-microphone-alt"></i>
                        <h1>Karaoke</h1>
                    </div>
                    <nav class="main-nav">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="/" class="nav-link<?php echo $currentPage === 'home' ? ' active' : ''; ?>">
                                <i class="fas fa-home"></i> Home
                            </a>
                            <a href="/solo" class="nav-link<?php echo $currentPage === 'solo' ? ' active' : ''; ?>">
                                <i class="fas fa-microphone"></i> Solo
                            </a>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <a href="/admin" class="nav-link<?php echo $currentPage === 'admin' ? ' active' : ''; ?>">
                                    <i class="fas fa-cog"></i> Admin
                                </a>
                            <?php endif; ?>
                            <div class="user-menu">
                                <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                <a href="/logout" class="nav-link">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        <?php else: ?>
                            <a href="/login" class="nav-link<?php echo $currentPage === 'login' ? ' active' : ''; ?>">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                            <a href="/register" class="nav-link<?php echo $currentPage === 'register' ? ' active' : ''; ?>">
                                <i class="fas fa-user-plus"></i> Register
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </header>
        
        <main class="app-main">
            <div class="container">