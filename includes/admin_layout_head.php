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
      --bg-elevated: #0d0d0f;
      --panel: #141416;
      --panel-glass: rgba(20, 20, 22, 0.6);
      --border: #27272a;
      --border-gold: rgba(212, 175, 55, 0.15);
      --border-gold-strong: rgba(212, 175, 55, 0.4);
      --gold: #D4AF37;
      --text: #f5f5f5;
      --muted: #9CA3AF;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      background:
        radial-gradient(circle at 10% 10%, rgba(212, 175, 55, 0.08) 0%, transparent 45%),
        radial-gradient(circle at 90% 0%, rgba(212, 175, 55, 0.05) 0%, transparent 40%),
        #080808;
      background-attachment: fixed;
      color: var(--text);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    header.topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1rem 1.5rem;
      background: rgba(13, 13, 15, 0.75);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border-bottom: 1px solid var(--border-gold);
      position: sticky;
      top: 0;
      z-index: 20;
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
      max-width: 1500px;
      margin: 0 auto;
      padding: 2rem 2rem;
    }

    h1 {
      font-size: 1.6rem;
      font-weight: 800;
      margin: 0 0 1.75rem;
      letter-spacing: -0.01em;
      display: flex;
      align-items: center;
      gap: 0.7rem;
    }

    h1::before {
      content: '';
      display: inline-block;
      width: 4px;
      height: 1.3em;
      border-radius: 999px;
      background: linear-gradient(180deg, var(--gold), rgba(212, 175, 55, 0.2));
      box-shadow: 0 0 10px rgba(212, 175, 55, 0.5);
      flex-shrink: 0;
    }

    h1.section-heading {
      font-size: 1.05rem;
      margin-top: 2.75rem;
    }

    .stat-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.25rem;
      margin-bottom: 3rem;
    }

    .stat-card {
      position: relative;
      background: rgba(15, 15, 15, 0.8);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(212, 175, 55, 0.2);
      border-radius: 16px;
      padding: 1.6rem 1.6rem 1.4rem;
      overflow: hidden;
      box-shadow: 0 10px 30px -14px rgba(0, 0, 0, 0.7);
      transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 2px;
      background: linear-gradient(90deg, transparent, var(--gold), transparent);
      opacity: 0.8;
    }

    .stat-card::after {
      content: '';
      position: absolute;
      top: -40%;
      right: -30%;
      width: 70%;
      height: 90%;
      background: radial-gradient(circle, rgba(212, 175, 55, 0.12), transparent 70%);
      pointer-events: none;
    }

    .stat-card:hover {
      transform: translateY(-4px);
      border-color: rgba(212, 175, 55, 0.5);
      box-shadow: 0 16px 36px -14px rgba(0, 0, 0, 0.75), 0 0 30px -8px rgba(212, 175, 55, 0.25);
    }

    .stat-card .val {
      position: relative;
      font-size: 2.6rem;
      font-weight: 800;
      color: #ffffff;
      line-height: 1.05;
      letter-spacing: -0.02em;
      font-variant-numeric: tabular-nums;
    }

    .stat-card .label {
      position: relative;
      font-size: 0.72rem;
      color: #d4af37;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      margin-top: 0.6rem;
      font-weight: 700;
    }

    /* ======================= CHARTS ======================= */
    .chart-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
      gap: 1.25rem;
      margin-bottom: 1rem;
    }

    .chart-card {
      position: relative;
      background: rgba(15, 15, 15, 0.8);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(212, 175, 55, 0.2);
      border-radius: 16px;
      padding: 1.4rem 1.5rem 1.2rem;
      box-shadow: 0 20px 40px -20px rgba(0, 0, 0, 0.8), 0 8px 20px -12px rgba(0, 0, 0, 0.6);
    }

    .chart-card-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 0.9rem;
    }

    .chart-card-head h2 {
      font-size: 0.8rem;
      font-weight: 700;
      color: var(--text);
      margin: 0;
      text-transform: uppercase;
      letter-spacing: 0.06em;
    }

    .chart-svg {
      width: 100%;
      height: auto;
      display: block;
      overflow: visible;
    }

    .chart-gridline {
      stroke: rgba(255, 255, 255, 0.06);
      stroke-width: 1;
    }

    .chart-area {
      fill: rgba(212, 175, 55, 0.1);
      stroke: none;
    }

    .chart-line {
      fill: none;
      stroke: var(--gold);
      stroke-width: 2;
      stroke-linejoin: round;
      stroke-linecap: round;
    }

    .chart-dot {
      fill: var(--gold);
      stroke: #0f0f0f;
      stroke-width: 2;
    }

    .chart-dot:hover {
      fill: #fff;
    }

    .chart-end-label {
      fill: #fff;
      font-size: 13px;
      font-weight: 800;
      font-variant-numeric: tabular-nums;
    }

    .chart-axis-labels {
      display: flex;
      justify-content: space-between;
      margin-top: 0.5rem;
      font-size: 0.68rem;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.06em;
    }

    .bar-chart {
      display: flex;
      flex-direction: column;
      gap: 0.9rem;
      padding: 0.3rem 0 0.2rem;
    }

    .bar-row {
      display: grid;
      grid-template-columns: 90px 1fr 32px;
      align-items: center;
      gap: 0.75rem;
    }

    .bar-row-label {
      font-size: 0.72rem;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.06em;
      font-weight: 700;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .bar-track {
      position: relative;
      height: 10px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.06);
      overflow: hidden;
    }

    .bar-fill {
      height: 100%;
      border-radius: 999px;
      background: linear-gradient(90deg, #b8922a, var(--gold));
      box-shadow: 0 0 10px -2px rgba(212, 175, 55, 0.6);
      transition: width 0.4s ease;
    }

    .bar-row-value {
      font-size: 0.85rem;
      font-weight: 800;
      color: #fff;
      text-align: right;
      font-variant-numeric: tabular-nums;
    }

    .table-scroll {
      background: rgba(15, 15, 15, 0.8);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(212, 175, 55, 0.2);
      border-radius: 16px;
      box-shadow: 0 20px 40px -20px rgba(0, 0, 0, 0.8), 0 8px 20px -12px rgba(0, 0, 0, 0.6);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: transparent;
      font-size: 0.85rem;
    }

    th,
    td {
      text-align: left;
      padding: 1.1rem 1.3rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    th {
      color: #d4af37;
      text-transform: uppercase;
      font-size: 0.68rem;
      letter-spacing: 0.1em;
      font-weight: 700;
      border-bottom: 1px solid rgba(212, 175, 55, 0.4);
      background: rgba(212, 175, 55, 0.06);
    }

    tbody tr {
      transition: background 0.15s ease;
    }

    tbody tr:hover {
      background: rgba(212, 175, 55, 0.04);
    }

    tr:last-child td {
      border-bottom: none;
    }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.35rem 0.8rem;
      border-radius: 999px;
      font-size: 0.68rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      border: 1px solid transparent;
    }

    .badge::before {
      content: '';
      width: 5px;
      height: 5px;
      border-radius: 999px;
      background: currentColor;
      box-shadow: 0 0 8px 1px currentColor;
      flex-shrink: 0;
    }

    .badge-pending,
    .badge-unpaid {
      background: rgba(212, 175, 55, 0.15);
      border-color: rgba(212, 175, 55, 0.4);
      color: var(--gold);
      box-shadow: 0 0 14px -6px rgba(212, 175, 55, 0.6);
    }

    .badge-confirmed,
    .badge-approved,
    .badge-completed,
    .badge-paid {
      background: rgba(74, 222, 128, 0.15);
      border-color: rgba(74, 222, 128, 0.4);
      color: #4ade80;
      box-shadow: 0 0 14px -6px rgba(74, 222, 128, 0.6);
    }

    .badge-cancelled,
    .badge-rejected,
    .badge-failed {
      background: rgba(248, 113, 113, 0.15);
      border-color: rgba(248, 113, 113, 0.4);
      color: #f87171;
      box-shadow: 0 0 14px -6px rgba(248, 113, 113, 0.6);
    }

    .badge-reviewing,
    .badge-processing {
      background: rgba(96, 165, 250, 0.15);
      border-color: rgba(96, 165, 250, 0.4);
      color: #60a5fa;
      box-shadow: 0 0 14px -6px rgba(96, 165, 250, 0.6);
    }

    .badge-refunded {
      background: rgba(167, 139, 250, 0.15);
      border-color: rgba(167, 139, 250, 0.4);
      color: #a78bfa;
      box-shadow: 0 0 14px -6px rgba(167, 139, 250, 0.6);
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
    input[type="password"],
    input[type="search"],
    input[type="tel"],
    select {
      background: var(--bg-elevated);
      border: 1px solid var(--border);
      color: #fff;
      padding: 0.5rem 0.7rem;
      border-radius: 8px;
      transition: border-color 0.15s ease;
    }

    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="password"]:focus,
    input[type="search"]:focus,
    input[type="tel"]:focus,
    select:focus {
      outline: none;
      border-color: var(--border-gold-strong);
    }

    button, .btn {
      cursor: pointer;
      border: none;
      border-radius: 999px;
      padding: 0.55rem 1.1rem;
      font-weight: 700;
      background: linear-gradient(135deg, #e8c766, var(--gold) 60%, #b8922a);
      color: #0a0a0a;
      text-decoration: none;
      display: inline-block;
      transition: box-shadow 0.15s ease, transform 0.15s ease;
    }

    button:hover, .btn:hover {
      box-shadow: 0 4px 20px -4px rgba(212, 175, 55, 0.6);
      transform: translateY(-1px);
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
       overflow scrolls instead of breaking the page layout. Styling
       (background/border/radius) lives on .table-scroll itself above. */
    .table-scroll {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
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

      .chart-grid {
        grid-template-columns: 1fr;
        gap: 0.9rem;
      }

      .chart-card {
        padding: 1rem 1.1rem;
      }

      .bar-row {
        grid-template-columns: 70px 1fr 28px;
        gap: 0.5rem;
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

    /* ======================= LOGIN PAGE ======================= */
    .login-shell {
      min-height: calc(100vh - 65px);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1.25rem;
    }

    .login-card {
      position: relative;
      width: 100%;
      max-width: 400px;
      background: rgba(13, 13, 13, 0.85);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border: 1px solid rgba(212, 175, 55, 0.25);
      border-radius: 20px;
      padding: 2.5rem 2.25rem;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.8), 0 0 30px rgba(212, 175, 55, 0.05);
    }

    .login-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 10%;
      right: 10%;
      height: 2px;
      background: linear-gradient(90deg, transparent, var(--gold), transparent);
      opacity: 0.7;
    }

    .login-header {
      text-align: center;
      margin-bottom: 2rem;
    }

    .login-header .eyebrow {
      font-size: 0.68rem;
      text-transform: uppercase;
      letter-spacing: 0.2em;
      color: var(--gold);
      font-weight: 700;
      margin-bottom: 0.6rem;
    }

    .login-header h1 {
      font-size: 1.35rem;
      margin: 0;
      justify-content: center;
    }

    .login-header h1::before {
      display: none;
    }

    .login-divider {
      width: 48px;
      height: 3px;
      border-radius: 999px;
      background: linear-gradient(90deg, transparent, var(--gold), transparent);
      margin: 1rem auto 0;
    }

    .login-form {
      display: flex;
      flex-direction: column;
      gap: 1.1rem;
    }

    .login-field label {
      display: block;
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--muted);
      margin-bottom: 0.4rem;
      font-weight: 700;
    }

    .login-field input {
      width: 100%;
      height: 46px;
      background: var(--bg-elevated);
      border: 1px solid var(--border);
      border-radius: 10px;
      color: #fff;
      padding: 0 0.9rem;
      font-size: 0.9rem;
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .login-field input:focus {
      outline: none;
      border-color: var(--gold);
      box-shadow: 0 0 10px rgba(212, 175, 55, 0.2);
    }

    .login-submit {
      width: 100%;
      height: 48px;
      margin-top: 0.4rem;
      border-radius: 999px;
      font-size: 0.9rem;
      font-weight: 800;
      letter-spacing: 0.02em;
      background: linear-gradient(135deg, #e8c766, var(--gold) 55%, #b8922a);
      color: #0a0a0a;
      border: none;
      cursor: pointer;
      transition: box-shadow 0.2s ease, transform 0.2s ease;
    }

    .login-submit:hover {
      box-shadow: 0 6px 24px -4px rgba(212, 175, 55, 0.55);
      transform: translateY(-1px);
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
