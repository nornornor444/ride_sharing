<?php
session_start();

// If the user is not logged in, kick them back to the login page
if(!isset($_SESSION['passenger_id'])){
    header("Location: login.php");
    exit();
}

// Grab the user's first name from the session to personalize the page
$first_name = $_SESSION['p_first_name'];
?>

<!doctype html>
<html lang="en" data-theme="light">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Routly — Dashboard</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <!-- daisyUI -->
    <link
      href="https://cdn.jsdelivr.net/npm/daisyui@5"
      rel="stylesheet"
      type="text/css"
    />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap"
      rel="stylesheet"
    />

    <style>
      :root {
        --ink: #0d0d12;
        --paper: #f5f4ef;
        --accent: #e85d26;
        --accent-light: #ff8a5c;
        --muted: #9b9b8a;
        --card-bg: #ffffff;
        --grid: rgba(13, 13, 18, 0.06);
      }

      * {
        box-sizing: border-box;
      }

      html {
        scroll-behavior: smooth;
      }

      body {
        font-family: "DM Sans", sans-serif;
        background-color: var(--paper);
        color: var(--ink);
        overflow-x: hidden;
      }

      h1,
      h2,
      h3,
      h4,
      .brand {
        font-family: "Syne", sans-serif;
      }

      /* ─── NAVBAR ─────────────────────────────────── */
      .nav1 {
        background: var(--paper) !important;
        border-bottom: 1.5px solid rgba(13, 13, 18, 0.1);
        position: sticky;
        top: 0;
        z-index: 100;
        backdrop-filter: blur(12px);
      }

      .nav1 .btn-ghost.text-xl {
        font-family: "Syne", sans-serif;
        font-weight: 800;
        font-size: 1.3rem;
        letter-spacing: -0.03em;
        color: var(--ink);
      }

      .nav1 .btn-ghost.text-xl::before {
        content: "";
        display: inline-block;
        width: 10px;
        height: 10px;
        background: var(--accent);
        border-radius: 50%;
        margin-right: 8px;
        vertical-align: middle;
      }

      .nav1 .menu a {
        font-family: "DM Sans", sans-serif;
        font-size: 0.9rem;
        font-weight: 500;
        color: var(--ink);
        opacity: 0.75;
        transition: opacity 0.2s;
      }

      .nav1 .menu a:hover {
        opacity: 1;
      }

      .nav1 .navbar-end .btn {
        background: var(--ink);
        color: var(--paper);
        border: none;
        font-family: "Syne", sans-serif;
        font-weight: 600;
        font-size: 0.85rem;
        letter-spacing: 0.02em;
        border-radius: 100px;
        padding: 0 1.5rem;
        transition:
          background 0.2s,
          transform 0.15s;
      }

      .nav1 .navbar-end .btn:hover {
        background: var(--accent);
        transform: translateY(-1px);
      }

      /* Logout Button Specific Style */
      .nav1 .navbar-end .btn-logout {
          background: #ef4444; /* Tailwind Red 500 */
      }
      .nav1 .navbar-end .btn-logout:hover {
          background: #dc2626; /* Tailwind Red 600 */
      }

      /* ─── HERO ───────────────────────────────────── */
      .hero-section {
        min-height: 80vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 4rem 8vw;
        position: relative;
        overflow: hidden;
      }

      .hero-section::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image: radial-gradient(
          circle,
          var(--grid) 1.5px,
          transparent 1.5px
        );
        background-size: 28px 28px;
        z-index: -1;
      }

      .section-sub {
        color: var(--muted);
        font-size: 1.1rem;
        line-height: 1.7;
        max-width: 600px;
        margin-top: 1rem;
        margin-bottom: 2.5rem;
      }
    </style>
  </head>
  <body>
    <div class="navbar bg-base-100 shadow-sm nav1">
      <div class="navbar-start">
        <div class="dropdown">
          <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h8m-8 6h16"
              />
            </svg>
          </div>
          <ul
            tabindex="-1"
            class="menu menu-sm dropdown-content bg-base-100 rounded-box z-1 mt-3 w-52 p-2 shadow"
          >
            <li><a href="ride_booking.php">Book a ride</a></li>
            <li><a href="#">My Rides</a></li>
            <li><a href="subscriptions.php">My Subscriptions</a></li>
            <li><a href="#">Settings</a></li>
          </ul>
        </div>
        <a href="home.php" class="btn btn-ghost text-xl">Routly</a>
      </div>
      <div class="navbar-center hidden lg:flex">
        <ul class="menu menu-horizontal px-1">
          <li><a href="ride_booking.php">Book a ride</a></li>
          <li><a href="#">My Rides</a></li>
          <li><a href="subscriptions.php">My Subscriptions</a></li>
          <li><a href="edit_profile.php">Settings</a></li>
        </ul>
      </div>
      <div class="navbar-end gap-4">
        <!-- Display User's Name -->
        <span class="hidden md:inline font-bold text-gray-700" style="font-family: 'DM Sans', sans-serif;">
            Hi, <?php echo htmlspecialchars($first_name); ?>!
        </span>
        
        <!-- NEW: Log Out Button pointing to logout.php -->
        <a class="btn btn-logout" href="logout.php">Log Out</a>
      </div>
    </div>

    <!-- ─── Personalized Hero Section ─── -->
    <div class="hero-section">
        <h1 class="text-6xl font-extrabold" style="color: var(--ink);">Welcome back, <?php echo htmlspecialchars($first_name); ?>.</h1>
        <p class="section-sub">Where are we heading today? Book a ride, split the fare, and get to your destination safely with Routly.</p>
        
        <a href="ride_booking.php" style="background: var(--accent); color: var(--paper); border: none; font-family: 'Syne', sans-serif; font-weight: 700; padding: 1rem 2.5rem; border-radius: 100px; font-size: 1.1rem; transition: transform 0.2s;" class="hover:-translate-y-1 shadow-lg">
            Book a Ride Now
        </a>
    </div>

    <!-- ─── Scroll reveal ─── -->
    <script>
      const observer = new IntersectionObserver(
        (entries) =>
          entries.forEach((e) => {
            if (e.isIntersecting) e.target.classList.add("visible");
          }),
        { threshold: 0.12 },
      );
      document
        .querySelectorAll(".reveal")
        .forEach((el) => observer.observe(el));
    </script>
  </body>
</html>