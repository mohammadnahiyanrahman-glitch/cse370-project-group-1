<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PolitiTrack</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/polititrack/assets/css/style.css">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-navy">
  <div class="container">
    <a class="navbar-brand font-playfair text-gold" href="/polititrack/index.php">
        <i class="fa-solid fa-landmark me-2"></i>PolitiTrack
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" href="/polititrack/index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/polititrack/politicians.php">Politicians</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/polititrack/submit_promise.php">Post Promise</a>
        </li>
      </ul>
      
      <form class="d-flex me-3" action="/polititrack/search.php" method="GET">
        <input class="form-control me-2 rounded-pill" type="search" name="q" placeholder="Search..." aria-label="Search">
        <button class="btn btn-outline-gold rounded-pill px-3" type="submit"><i class="fa-solid fa-search"></i></button>
      </form>

      <ul class="navbar-nav align-items-center">
        <?php if (isLoggedIn()): ?>
            <?php if (isModerator()): ?>
                <li class="nav-item">
                    <a class="nav-link text-warning fw-bold" href="/polititrack/moderator/index.php"><i class="fa-solid fa-shield-halved me-1"></i>Mod Panel</a>
                </li>
            <?php endif; ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                    <?php 
                        $nav_avatar = isset($_SESSION['profile_picture']) && $_SESSION['profile_picture'] 
                            ? '/polititrack/uploads/avatars/' . htmlspecialchars($_SESSION['profile_picture']) 
                            : 'https://ui-avatars.com/api/?name='.urlencode($_SESSION['username']).'&background=0B192C&color=fff';
                    ?>
                    <img src="<?= $nav_avatar ?>" id="navAvatarImg" class="rounded-circle me-2" style="width: 28px; height: 28px; object-fit: cover; border: 1px solid var(--border);" alt="User Avatar">
                    <?= htmlspecialchars($_SESSION['username']) ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="/polititrack/profile.php?id=<?= $_SESSION['user_id'] ?>">My Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="/polititrack/logout.php">Logout</a></li>
                </ul>
            </li>
        <?php else: ?>
            <li class="nav-item">
                <a class="nav-link fw-bold" href="/polititrack/login.php">Login</a>
            </li>
            <li class="nav-item">
                <a class="btn btn-gold ms-2 rounded-pill px-4 fw-bold shadow-sm" href="/polititrack/signup.php">Sign Up</a>
            </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container my-4 min-vh-100">
    <!-- Toast notifications container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <?php if (isset($_SESSION['toast'])): ?>
            <div class="toast align-items-center text-bg-<?= htmlspecialchars($_SESSION['toast']['type']) ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
              <div class="d-flex">
                <div class="toast-body">
                  <?= htmlspecialchars($_SESSION['toast']['message']) ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
              </div>
            </div>
            <?php unset($_SESSION['toast']); ?>
        <?php endif; ?>
    </div>
