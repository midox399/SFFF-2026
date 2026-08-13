<?php
/**
 * Include at the top of an admin page body, after require_admin_auth().
 * Expects $pageTitle to be set before including.
 */
$pageTitle = $pageTitle ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= h($pageTitle) ?> — SFFF 2026 Admin</title>
  <style>
    :root {
      --bg: #0a0a0b;
      --panel: #141416;
      --border: #27272a;
      --gold: #D4AF37;
      --text: #f5f5f5;
      --muted: #9CA3AF;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      background: var(--bg);
      color: var(--text);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    header.topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1rem 1.5rem;
      background: var(--panel);
      border-bottom: 1px solid var(--border);
    }

    header.topbar .brand {
      font-weight: 800;
      color: var(--gold);
      text-decoration: none;
    }

    nav.admin-nav {
      display: flex;
      gap: 1rem;
      align-items: center;
    }

    nav.admin-nav a {
      color: var(--muted);
      text-decoration: none;
      font-size: 0.85rem;
      padding: 0.4rem 0.7rem;
      border-radius: 8px;
    }

    nav.admin-nav a:hover,
    nav.admin-nav a.active {
      color: #fff;
      background: rgba(212, 175, 55, 0.12);
    }

    main {
      max-width: 1100px;
      margin: 0 auto;
      padding: 2rem 1.5rem;
    }

    h1 {
      font-size: 1.5rem;
      margin: 0 0 1.5rem;
    }

    .stat-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 1.25rem;
    }

    .stat-card .val {
      font-size: 1.6rem;
      font-weight: 800;
      color: var(--gold);
    }

    .stat-card .label {
      font-size: 0.75rem;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-top: 0.25rem;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 12px;
      overflow: hidden;
      font-size: 0.85rem;
    }

    th,
    td {
      text-align: left;
      padding: 0.7rem 0.9rem;
      border-bottom: 1px solid var(--border);
    }

    th {
      color: var(--muted);
      text-transform: uppercase;
      font-size: 0.7rem;
      letter-spacing: 0.05em;
    }

    tr:last-child td {
      border-bottom: none;
    }

    .badge {
      display: inline-block;
      padding: 0.2rem 0.55rem;
      border-radius: 999px;
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
    }

    .badge-pending,
    .badge-unpaid {
      background: rgba(212, 175, 55, 0.15);
      color: var(--gold);
    }

    .badge-confirmed,
    .badge-approved,
    .badge-completed,
    .badge-paid {
      background: rgba(74, 222, 128, 0.15);
      color: #4ade80;
    }

    .badge-cancelled,
    .badge-rejected,
    .badge-failed {
      background: rgba(248, 113, 113, 0.15);
      color: #f87171;
    }

    .badge-reviewing,
    .badge-processing {
      background: rgba(96, 165, 250, 0.15);
      color: #60a5fa;
    }

    .badge-refunded {
      background: rgba(167, 139, 250, 0.15);
      color: #a78bfa;
    }

    .toolbar {
      display: flex;
      flex-wrap: wrap;
      gap: 0.6rem;
      margin-bottom: 1rem;
      align-items: center;
    }

    input,
    select,
    textarea,
    button {
      font-family: inherit;
      font-size: 0.85rem;
    }

    input[type="text"],
    input[type="email"],
    input[type="search"],
    select {
      background: #0d0d0f;
      border: 1px solid var(--border);
      color: #fff;
      padding: 0.5rem 0.7rem;
      border-radius: 8px;
    }

    button, .btn {
      cursor: pointer;
      border: none;
      border-radius: 999px;
      padding: 0.5rem 1rem;
      font-weight: 700;
      background: var(--gold);
      color: #0a0a0a;
      text-decoration: none;
      display: inline-block;
    }

    button.secondary, .btn.secondary {
      background: transparent;
      border: 1px solid var(--border);
      color: var(--text);
    }

    button.danger {
      background: #f87171;
      color: #1a0a0a;
    }

    .error-box {
      background: rgba(248, 113, 113, 0.1);
      border: 1px solid rgba(248, 113, 113, 0.4);
      color: #f87171;
      padding: 0.75rem 1rem;
      border-radius: 10px;
      margin-bottom: 1rem;
      font-size: 0.85rem;
    }

    .empty-state {
      color: var(--muted);
      padding: 2rem;
      text-align: center;
    }

    /* Horizontal-scroll wrapper for wide tables on narrow screens — the
       tables themselves keep their natural width, this just makes sure
       overflow scrolls instead of breaking the page layout. */
    .table-scroll {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      border-radius: 12px;
    }

    .table-scroll table {
      border-radius: 0;
    }

    @media (max-width: 720px) {
      header.topbar {
        flex-wrap: wrap;
        gap: 0.6rem;
        padding: 0.9rem 1rem;
      }

      nav.admin-nav {
        width: 100%;
        flex-wrap: wrap;
        gap: 0.4rem;
      }

      nav.admin-nav a {
        flex: 1 1 auto;
        text-align: center;
        padding: 0.55rem 0.5rem;
        font-size: 0.78rem;
      }

      main {
        padding: 1.25rem 0.9rem;
      }

      h1 {
        font-size: 1.25rem;
      }

      .stat-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.6rem;
      }

      .stat-card {
        padding: 0.9rem;
      }

      .stat-card .val {
        font-size: 1.3rem;
      }

      .toolbar {
        flex-direction: column;
        align-items: stretch;
      }

      .toolbar input,
      .toolbar select,
      .toolbar button,
      .toolbar a {
        width: 100%;
      }

      /* Bigger tap targets throughout the admin area on touch screens */
      button, .btn, input, select {
        min-height: 42px;
      }

      th, td {
        padding: 0.55rem 0.6rem;
        font-size: 0.78rem;
      }
    }
  </style>
</head>

<body>
  <header class="topbar">
    <a href="index.php" class="brand">SFFF 2026 — Admin</a>
    <nav class="admin-nav">
      <a href="index.php">Dashboard</a>
      <a href="passports.php">Réservations</a>
      <a href="checkin.php">Scan Entrée</a>
      <a href="applications.php">Candidatures</a>
      <a href="logout.php">Déconnexion</a>
    </nav>
  </header>
  <main>
