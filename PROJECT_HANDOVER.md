# SFFF 2026 — Project Handover & Developer Briefing

> **Project Name:** SFFF 2026 — Street & Fast Food Fest (Global Village Tunisia)
> **Event Dates:** August 29 – September 12, 2026
> **Location:** Tunis Lac II Waterfront (1.8 km Corniche)
> **Official Website Domain:** https://www.globalvillagetunisia.com
> **Primary Language:** French (with full English & Arabic trilingual switching)

---

## 1. Executive Summary & Tech Stack

SFFF 2026 is a 15-night cultural & culinary festival in Tunis Lac II bringing together 42 national pavilions, over 310 exhibitors/food trucks, 8 themed corniche zones, an interactive map, a Future Food Lab, a 100% Cashless RFID system, and a Passport Collector gamification system.

### Tech Stack
* **Frontend:** Single-Page Application HTML5, Vanilla JavaScript (ES6+), CSS3 + Tailwind CSS build system.
* **Libraries:** pdf-lib (Client-side PDF generation & interactive form filling), Google Fonts (Plus Jakarta Sans, Tajawal for Arabic RTL).
* **Backend Database Schema:** MySQL 8.0+ / MariaDB 10.6+ (sfff2026_database.sql).

---

## 2. Codebase Structure & Key Files

* index.html — Main application (5,200+ lines, full UI + reactive state)
* interactive-map.html — Standalone interactive SVG/Canvas festival map (1.8 km)
* sfff2026_database.sql — Production-ready MySQL/MariaDB database schema (23 tables + 3 views)
* PROJECT_HANDOVER.md — Handover briefing for AI assistant / developer takeover
* assets/ — Logos, country banner images (assets/countrys/), PDF documents (assets/documents/)

---

## 3. Implemented Features Breakdown

1. **Trilingual Support (FR / EN / AR):** Dynamic string replacement with full RTL support when switching to Arabic.
2. **Hero & Countdown Clock:** Calculates remaining days, hours, minutes, and seconds until August 29, 2026 at 17:00 (UTC+1).
3. **5 Senses Concept:** Interactive cards for Gout, Ouie, Vue, Odorat, Toucher.
4. **42 National Pavilions:** Flag emojis, regional tags, description, and images.
5. **Programmation (15 Nuits · 15 Voyages):** Night-by-night breakdown with dynamic status badges (Ce Soir, A venir, Termine).
6. **8 Corniche Zones:** Timeline indicator mapping out the 1.8 km path.
7. **Interactive Map:** Embedded iframe displaying interactive-map.html with reset & fullscreen controls.
8. **Espace Professionnels:** Form with profile tabs (Investisseur, Exposant, Sponsor, Ambassade, etc.).
9. **Billetterie & 4 Passeports:** Standard (25 DT), Pro (45 DT), Diplomatique (85 DT), VIP (150 DT).
10. **Ticket Checkout Modal:** Interactive quantity calculation, total price update, and order reference generator.
11. **Documents Utiles (Resources Hub):** Profile filter pills, search bar, PDF modal preview (iframe), download buttons, and online form filling via pdf-lib.

---

## 4. Database Schema Summary (sfff2026_database.sql)

The database script contains **23 tables + 3 SQL views**:
festivals, site_config, festival_senses, festival_zones, country_pavillons, festival_nights, night_performances, future_food_lab_items, awards, ticket_types, ticket_type_features, users, passport_orders, passport_stamps, sponsors, document_categories, documents, doc_form_submissions, press_accreditations, sponsor_reservations, exhibitor_reservations, ambassador_reservations, pro_applications.

---

## 5. Copy-Pasteable Prompt for the Next AI Assistant

`	ext
======================================================================
AI CODER INITIALIZATION PROMPT FOR SFFF 2026
======================================================================

You are taking over development for the SFFF 2026 (Street & Fast Food Fest) project.

Project Overview:
- SFFF 2026 is a 15-night international food and culture festival taking place at Tunis Lac II (1.8 km waterfront) from August 29 to September 12, 2026.
- Main entry point: index.html (Single-page app in Vanilla JS + HTML5 + CSS/Tailwind).
- Interactive map: interactive-map.html.
- Database schema: sfff2026_database.sql (MySQL 8 / MariaDB 10.6).
- Detailed Briefing: PROJECT_HANDOVER.md.

Current State of Development:
1. Full front-end UI built with dark luxury theme (Gold accents #D4AF37, deep dark panels #141416).
2. Live countdown timer set to 2026-08-29T17:00:00+01:00.
3. Trilingual support (French primary, English, Arabic RTL).
4. 42 national pavilions, 15 festival nights, 8 zones, Future Food Lab, and Gamification Passport Simulator.
5. Interactive Ticket Checkout modal with 4 passport tiers (25 DT to 150 DT).
6. Resource hub (Documents Utiles) with instant PDF preview modal, category filtering, search, and online form filling via pdf-lib.
7. SQL Database Schema (sfff2026_database.sql) containing 23 tables and 3 views ready for backend connection.

Next Recommended Tasks to Implement:
- Backend API Integration (Node.js/Express, PHP/Laravel, or Python/FastAPI) to handle form submissions in passport_orders, press_accreditations, sponsor_reservations, and exhibitor_reservations.
- Payment Gateway Integration (Konnect, Flouci, ClickToPay, Stripe) for real passport purchases.
- Admin Panel / Dashboard for managing orders, approving press accreditations, and scanning RFID wristbands.
======================================================================
`
