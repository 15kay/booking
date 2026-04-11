<?php
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['student_id'])) {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WSU Hub - Student Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css?v=2">
    <style>
        :root {
            --crimson:    #8B1A1A;
            --crimson-dk: #5C0F0F;
            --crimson-lt: #C0392B;
            --gold:       #D4941A;
            --gold-lt:    #F5C842;
            --ink:        #0F0A0A;
            --surface:    #FAF8F5;
            --card-bg:    #FFFFFF;
            --muted:      #8A7E7E;
            --border:     rgba(139,26,26,0.10);
            --shadow-sm:  0 2px 12px rgba(139,26,26,0.07);
            --shadow-md:  0 8px 32px rgba(139,26,26,0.12);
            --shadow-lg:  0 20px 60px rgba(0,0,0,0.14);
            --radius:     16px;
            --radius-sm:  10px;
        }

        body { background: var(--surface); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        /* ── HERO ─────────────────────────────────────── */
        .hub-hero {
            position: relative;
            border-radius: 24px;
            padding: 52px 48px;
            margin-bottom: 40px;
            overflow: hidden;
            background: linear-gradient(135deg, #3D0A0A 0%, #7A1C1C 60%, #E8A020 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }
        /* animated diagonal grain overlay */
        .hub-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                repeating-linear-gradient(
                    -55deg,
                    transparent,
                    transparent 38px,
                    rgba(255,255,255,0.025) 38px,
                    rgba(255,255,255,0.025) 39px
                );
            pointer-events: none;
        }
        /* gold accent bar */
        .hub-hero::after {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 6px;
            background: linear-gradient(180deg, var(--gold-lt), var(--gold));
            border-radius: 24px 0 0 24px;
        }
        .hub-hero-text { position: relative; z-index: 1; }
        .hub-hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(255,255,255,0.10);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 40px;
            padding: 4px 14px;
            font-size: 11px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--gold-lt);
            font-family: 'DM Sans', sans-serif;
            font-weight: 500;
            margin-bottom: 18px;
        }
        .hub-hero h1 {
            font-size: 32px;
            font-weight: 800;
            color: #fff;
            margin: 0 0 10px;
            line-height: 1.1;
        }
        .hub-hero h1 span { color: var(--gold-lt); }
        .hub-hero p {
            color: rgba(255,255,255,0.65);
            font-size: 14.5px;
            max-width: 440px;
            line-height: 1.65;
            margin: 0 0 26px;
        }
        .hub-search {
            display: flex;
            align-items: center;
            background: rgba(255,255,255,0.08);
            border: 1.5px solid rgba(255,255,255,0.18);
            border-radius: 50px;
            padding: 0 18px;
            gap: 10px;
            max-width: 380px;
            transition: border-color .2s, background .2s;
        }
        .hub-search:focus-within {
            background: rgba(255,255,255,0.14);
            border-color: var(--gold-lt);
        }
        .hub-search i { color: rgba(255,255,255,0.45); font-size: 13px; }
        .hub-search input {
            border: none;
            outline: none;
            background: transparent;
            font-size: 13.5px;
            padding: 13px 0;
            flex: 1;
            color: #fff;
            font-family: 'DM Sans', sans-serif;
        }
        .hub-search input::placeholder { color: rgba(255,255,255,0.40); }
        .hub-hero-deco {
            position: relative; z-index: 1;
            display: flex; flex-direction: column; gap: 10px; align-items: flex-end;
            flex-shrink: 0;
        }
        .deco-ring {
            width: 120px; height: 120px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.08);
            display: flex; align-items: center; justify-content: center;
            position: relative;
        }
        .deco-ring::before {
            content: '';
            position: absolute;
            inset: 10px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(212,148,26,0.25), rgba(139,26,26,0.4));
        }
        .deco-ring i { font-size: 38px; color: rgba(255,255,255,0.18); position: relative; z-index: 1; }
        .deco-stat {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 10px 16px;
            text-align: right;
        }
        .deco-stat .ds-num {
            font-family: 'Syne', sans-serif;
            font-size: 22px; font-weight: 700; color: var(--gold-lt);
        }
        .deco-stat .ds-lbl { font-size: 10px; color: rgba(255,255,255,0.45); letter-spacing: .06em; text-transform: uppercase; }

        /* ── SECTION ─────────────────────────────────── */
        .hub-section { margin-bottom: 40px; }
        .hub-section-title {
            font-size: 14px;
            font-weight: 700;
            color: #3D0A0A;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .hub-section-title::before {
            content: '';
            display: inline-block;
            width: 3px;
            height: 16px;
            background: linear-gradient(180deg, var(--gold), var(--crimson));
            border-radius: 3px;
        }
        .hub-section-title i { color: var(--gold); font-size: 14px; }

        /* ── GRID ────────────────────────────────────── */
        .hub-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 10px;
        }

        /* ── CARD ────────────────────────────────────── */
        .hub-card {
            background: var(--card-bg);
            border-radius: 14px;
            padding: 14px 16px;
            text-align: left;
            text-decoration: none;
            border: 1px solid rgba(0,0,0,0.07);
            transition: transform .18s cubic-bezier(.34,1.56,.64,1),
                        box-shadow .18s ease,
                        border-color .15s;
            cursor: pointer;
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 14px;
            position: relative;
            overflow: hidden;
        }
        /* left accent bar */
        .hub-card::before {
            content: '';
            position: absolute;
            left: 0; top: 16%; bottom: 16%;
            width: 3px;
            border-radius: 0 3px 3px 0;
            background: var(--crimson);
            opacity: 0;
            transition: opacity .18s, top .18s, bottom .18s;
        }
        .hub-card:hover {
            transform: translateX(4px);
            box-shadow: -4px 4px 20px rgba(139,26,26,0.10);
            border-color: rgba(139,26,26,0.20);
        }
        .hub-card:hover::before { opacity: 1; top: 10%; bottom: 10%; }

        .hub-card-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
            transition: transform .18s;
        }
        .hub-card:hover .hub-card-icon { transform: scale(1.08); }

        .hub-card-body { flex: 1; min-width: 0; }
        .hub-card-label {
            font-size: 13px;
            font-weight: 600;
            color: #1a1a1a;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .hub-card-desc {
            font-size: 11px;
            color: #9ca3af;
            line-height: 1.4;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .hub-card .ext-badge {
            font-size: 9px;
            background: rgba(139,26,26,0.07);
            color: var(--crimson);
            padding: 2px 7px;
            border-radius: 20px;
            letter-spacing: .04em;
            flex-shrink: 0;
            white-space: nowrap;
        }
        .hub-card-arrow {
            color: #ccc;
            font-size: 11px;
            flex-shrink: 0;
            transition: color .15s, transform .15s;
        }
        .hub-card:hover .hub-card-arrow { color: var(--crimson); transform: translateX(3px); }

        /* ── ICON COLOR THEMES ───────────────────────── */
        .ic-maroon  { background: rgba(139,26,26,0.09);  color: var(--crimson); }
        .ic-gold    { background: rgba(212,148,26,0.12); color: #A86E00; }
        .ic-blue    { background: rgba(37,99,235,0.08);  color: #2563eb; }
        .ic-green   { background: rgba(16,185,129,0.09); color: #059669; }
        .ic-purple  { background: rgba(124,58,237,0.09); color: #7c3aed; }
        .ic-orange  { background: rgba(234,88,12,0.09);  color: #ea580c; }
        .ic-teal    { background: rgba(13,148,136,0.09); color: #0d9488; }
        .ic-red     { background: rgba(220,38,38,0.09);  color: #dc2626; }
        .ic-indigo  { background: rgba(79,70,229,0.09);  color: #4f46e5; }
        .ic-pink    { background: rgba(219,39,119,0.09); color: #db2777; }

        .hub-card.hidden { display: none; }

        /* ── MINI BROWSER ────────────────────────────── */
        .mini-browser-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(10,5,5,0.72);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .mini-browser-overlay.active { display: flex; }
        .mini-browser {
            background: #fff;
            border-radius: 20px;
            width: 92vw;
            max-width: 1100px;
            height: 85vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0,0,0,0.38);
            border: 1px solid rgba(255,255,255,0.12);
        }
        .mini-browser-toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 16px;
            background: var(--crimson-dk);
            flex-shrink: 0;
        }
        /* traffic-light dots */
        .mb-dots { display: flex; gap: 6px; flex-shrink: 0; }
        .mb-dot {
            width: 12px; height: 12px;
            border-radius: 50%;
            cursor: pointer;
        }
        .mb-dot-close  { background: #ff5f57; }
        .mb-dot-min    { background: #febc2e; }
        .mb-dot-max    { background: #28c840; }
        .mini-browser-toolbar .mb-title {
            font-size: 12px;
            font-weight: 700;
            color: rgba(255,255,255,0.9);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
        }
        .mini-browser-toolbar .mb-url {
            flex: 1;
            font-size: 11.5px;
            color: rgba(255,255,255,0.5);
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 20px;
            padding: 5px 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .mini-browser-toolbar button {
            border: none;
            border-radius: 8px;
            padding: 7px 14px;
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .mb-btn-newtab { background: var(--gold); color: var(--crimson-dk); }
        .mb-btn-newtab:hover { background: var(--gold-lt); }
        .mb-btn-close  { background: rgba(255,255,255,0.12); color: rgba(255,255,255,0.8); }
        .mb-btn-close:hover { background: rgba(255,255,255,0.20); }
        .mini-browser iframe { flex: 1; border: none; width: 100%; }
        .mini-browser-blocked {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            color: var(--muted);
            padding: 40px;
            text-align: center;
        }
        .mini-browser-blocked i { font-size: 48px; color: #d1c8c8; }
        .mini-browser-blocked p { font-size: 13.5px; max-width: 360px; line-height: 1.6; }
        .mini-browser-blocked a {
            background: var(--crimson-dk);
            color: #fff;
            padding: 11px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        /* ── LOADING SPINNER ─────────────────────────── */
        @keyframes mbspin { to { transform: rotate(360deg); } }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .hub-section { animation: fadeUp .45s ease both; }
        .hub-section:nth-child(2) { animation-delay: .05s; }
        .hub-section:nth-child(3) { animation-delay: .10s; }
        .hub-section:nth-child(4) { animation-delay: .15s; }
        .hub-section:nth-child(5) { animation-delay: .20s; }
        .hub-section:nth-child(6) { animation-delay: .25s; }

        /* ── RESPONSIVE ──────────────────────────────── */
        @media (max-width: 680px) {
            .hub-hero { flex-direction: column; padding: 34px 24px; }
            .hub-hero h1 { font-size: 26px; }
            .hub-hero-deco { display: none; }
            .hub-search { max-width: 100%; }
            .hub-grid { grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); }
        }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        <div class="content">

            <!-- Hero -->
            <div class="hub-hero">
                <div class="hub-hero-text">
                    <div class="hub-hero-eyebrow">
                        <i class="fas fa-circle" style="font-size:7px;color:var(--gold-lt)"></i>
                        Walter Sisulu University
                    </div>
                    <h1>Student <span>Hub</span></h1>
                    <p>Your central access point for all university services, portals, and resources — everything in one place.</p>
                    <div class="hub-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="hubSearch" placeholder="Search services, portals, links…">
                    </div>
                </div>
                <div class="hub-hero-deco">
                    <div class="deco-ring"><i class="fas fa-university"></i></div>
                    <div class="deco-stat">
                        <div class="ds-num">40+</div>
                        <div class="ds-lbl">Services</div>
                    </div>
                </div>
            </div>

            <!-- Student Apps -->
            <div class="hub-section">
                <h2 class="hub-section-title"><i class="fas fa-mobile-alt"></i> Student Apps &amp; Tools</h2>
                <div class="hub-grid">
                    <a href="https://wiseup.wsu.ac.za/" target="_blank" class="hub-card" data-name="wiseup moodle elearning lms">
                        <div class="hub-card-icon ic-orange"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">WiSeUp e-Learning</p><p class="hub-card-desc">Moodle LMS platform</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://outlook.office.com/" target="_blank" class="hub-card" data-name="student email outlook office 365 mail">
                        <div class="hub-card-icon ic-blue"><i class="fas fa-envelope"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Student Email</p><p class="hub-card-desc">Outlook web portal</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://www.office.com/" target="_blank" class="hub-card" data-name="microsoft 365 office word excel teams">
                        <div class="hub-card-icon ic-indigo"><i class="fab fa-microsoft"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Microsoft 365</p><p class="hub-card-desc">Office apps &amp; Teams</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://ie.wsu.ac.za/pls/prodi41/w99pkg.mi_login" target="_blank" class="hub-card" data-name="student registration portal results">
                        <div class="hub-card-icon ic-maroon"><i class="fas fa-user-graduate"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Registration Portal</p><p class="hub-card-desc">Register &amp; view results</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="http://status.wsu.ac.za/status/statuscheck.php" target="_blank" class="hub-card" data-name="admission status student number check">
                        <div class="hub-card-icon ic-teal"><i class="fas fa-search"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Admission Status</p><p class="hub-card-desc">Check status &amp; student number</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://status.wsu.ac.za/reset/login.php" target="_blank" class="hub-card" data-name="wsu access pin reset password forgot">
                        <div class="hub-card-icon ic-gold"><i class="fas fa-key"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">WSU Access / PIN</p><p class="hub-card-desc">Reset PIN &amp; get access</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://wsuacza.sharepoint.com/sites/WalterSisuluUniversity2" target="_blank" class="hub-card" data-name="intranet sharepoint wsu network">
                        <div class="hub-card-icon ic-purple"><i class="fas fa-network-wired"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">WSU Intranet</p><p class="hub-card-desc">SharePoint network access</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://wsulib.summon.serialssolutions.com/#!/" target="_blank" class="hub-card" data-name="opac library search books journals">
                        <div class="hub-card-icon ic-green"><i class="fas fa-book-open"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">OPAC Library</p><p class="hub-card-desc">Search books &amp; e-journals</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://print.wsu.ac.za" target="_blank" class="hub-card" data-name="printing student print portal">
                        <div class="hub-card-icon ic-orange"><i class="fas fa-print"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Student Printing</p><p class="hub-card-desc">Printing portal</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://mysafespace.wsu.ac.za/mysafespace/" target="_blank" class="hub-card" data-name="safe space wellness safety report">
                        <div class="hub-card-icon ic-pink"><i class="fas fa-shield-alt"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">My SafeSpace</p><p class="hub-card-desc">Safety &amp; wellness app</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://qualityvoice.wsu.ac.za/" target="_blank" class="hub-card" data-name="quality voice feedback survey student">
                        <div class="hub-card-icon ic-teal"><i class="fas fa-comment-dots"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">QualityVoice</p><p class="hub-card-desc">Student feedback portal</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://aka.ms/mfasetup" target="_blank" class="hub-card" data-name="mfa multi factor authentication security">
                        <div class="hub-card-icon ic-red"><i class="fas fa-lock"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">MFA Setup</p><p class="hub-card-desc">Multi-factor authentication</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://forms.office.com/r/4sUJzEzmyk" target="_blank" class="hub-card" data-name="stars survey academic readiness student tracking">
                        <div class="hub-card-icon ic-gold"><i class="fas fa-clipboard-list"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">STARS Survey</p><p class="hub-card-desc">Academic readiness survey</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                </div>
            </div>

            <!-- Online Portals -->
            <div class="hub-section">
                <h2 class="hub-section-title"><i class="fas fa-globe"></i> Online Portals</h2>
                <div class="hub-grid">
                    <a href="https://wiseup.wsu.ac.za/login/index.php" target="_blank" class="hub-card" data-name="moodle learning management">
                        <div class="hub-card-icon ic-orange"><i class="fas fa-graduation-cap"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Moodle</p><p class="hub-card-desc">Learning Management System</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://www.wsu.ac.za/index.php/en/my-wsu-main" target="_blank" class="hub-card" data-name="student portal wsu">
                        <div class="hub-card-icon ic-maroon"><i class="fas fa-user-circle"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Student Portal</p><p class="hub-card-desc">Academic records &amp; registration</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://students.wsu.ac.za/mobileverify/" target="_blank" class="hub-card" data-name="mobileverify verification documents">
                        <div class="hub-card-icon ic-blue"><i class="fas fa-shield-alt"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">MobileVerify</p><p class="hub-card-desc">Document verification</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://www.nsfas.org.za" target="_blank" class="hub-card" data-name="nsfas funding financial aid bursary">
                        <div class="hub-card-icon ic-green"><i class="fas fa-hand-holding-usd"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">NSFAS</p><p class="hub-card-desc">Funding &amp; financial aid</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://library.wsu.ac.za" target="_blank" class="hub-card" data-name="library books research resources">
                        <div class="hub-card-icon ic-purple"><i class="fas fa-book"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">WSU Library</p><p class="hub-card-desc">Books, journals &amp; research</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://mail.google.com" target="_blank" class="hub-card" data-name="email gmail student mail">
                        <div class="hub-card-icon ic-red"><i class="fas fa-envelope"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Student Email</p><p class="hub-card-desc">@mywsu.ac.za mail</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                </div>
            </div>

            <!-- Booking Services -->
            <div class="hub-section">
                <h2 class="hub-section-title"><i class="fas fa-calendar-check"></i> Book a Service</h2>
                <div class="hub-grid">
                    <a href="book-service.php?category=1" class="hub-card" data-name="academic advising course selection">
                        <div class="hub-card-icon ic-maroon"><i class="fas fa-graduation-cap"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Academic Advising</p><p class="hub-card-desc">Course &amp; program guidance</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="book-service.php?category=2" class="hub-card" data-name="career guidance cv interview">
                        <div class="hub-card-icon ic-gold"><i class="fas fa-briefcase"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Career Guidance</p><p class="hub-card-desc">CV, interviews &amp; career paths</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="book-service.php?category=3" class="hub-card" data-name="healthcare medical health wellness">
                        <div class="hub-card-icon ic-green"><i class="fas fa-heartbeat"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Healthcare</p><p class="hub-card-desc">Medical &amp; wellness services</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="book-service.php?category=4" class="hub-card" data-name="student life activities clubs sports">
                        <div class="hub-card-icon ic-blue"><i class="fas fa-users"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Student Life</p><p class="hub-card-desc">Clubs, sports &amp; activities</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="book-service.php?category=5" class="hub-card" data-name="support counselling mental health nsfas financial">
                        <div class="hub-card-icon ic-purple"><i class="fas fa-hands-helping"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Support Services</p><p class="hub-card-desc">Counselling &amp; financial aid</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="my-bookings.php" class="hub-card" data-name="my bookings appointments">
                        <div class="hub-card-icon ic-teal"><i class="fas fa-calendar-alt"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">My Bookings</p><p class="hub-card-desc">View &amp; manage appointments</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                </div>
            </div>

            <!-- Academic Resources -->
            <div class="hub-section">
                <h2 class="hub-section-title"><i class="fas fa-book-open"></i> Academic Resources</h2>
                <div class="hub-grid">
                    <a href="https://www.wsu.ac.za/index.php/academic-calendar" target="_blank" class="hub-card" data-name="academic calendar dates timetable">
                        <div class="hub-card-icon ic-maroon"><i class="fas fa-calendar"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Academic Calendar</p><p class="hub-card-desc">Key dates &amp; deadlines</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://www.wsu.ac.za/index.php/examinations" target="_blank" class="hub-card" data-name="exams timetable results">
                        <div class="hub-card-icon ic-orange"><i class="fas fa-file-alt"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Examinations</p><p class="hub-card-desc">Exam timetables &amp; results</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://www.wsu.ac.za/index.php/faculties" target="_blank" class="hub-card" data-name="faculties departments programmes">
                        <div class="hub-card-icon ic-indigo"><i class="fas fa-university"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Faculties</p><p class="hub-card-desc">Departments &amp; programmes</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://www.wsu.ac.za/index.php/research" target="_blank" class="hub-card" data-name="research postgraduate">
                        <div class="hub-card-icon ic-teal"><i class="fas fa-flask"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Research</p><p class="hub-card-desc">Research &amp; postgraduate info</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                </div>
            </div>

            <!-- Student Support -->
            <div class="hub-section">
                <h2 class="hub-section-title"><i class="fas fa-life-ring"></i> Student Support</h2>
                <div class="hub-grid">
                    <a href="https://www.wsu.ac.za/index.php/student-wellness" target="_blank" class="hub-card" data-name="wellness mental health counselling">
                        <div class="hub-card-icon ic-pink"><i class="fas fa-heart"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Student Wellness</p><p class="hub-card-desc">Mental health &amp; counselling</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://www.wsu.ac.za/index.php/accommodation" target="_blank" class="hub-card" data-name="accommodation residence housing">
                        <div class="hub-card-icon ic-gold"><i class="fas fa-home"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Accommodation</p><p class="hub-card-desc">Residence &amp; housing</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://www.wsu.ac.za/index.php/sport" target="_blank" class="hub-card" data-name="sport recreation fitness gym">
                        <div class="hub-card-icon ic-green"><i class="fas fa-running"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Sport &amp; Recreation</p><p class="hub-card-desc">Facilities &amp; sports clubs</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://www.wsu.ac.za/index.php/transport" target="_blank" class="hub-card" data-name="transport shuttle bus">
                        <div class="hub-card-icon ic-blue"><i class="fas fa-bus"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Transport</p><p class="hub-card-desc">Shuttle &amp; transport services</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://www.wsu.ac.za/index.php/disability-unit" target="_blank" class="hub-card" data-name="disability support unit">
                        <div class="hub-card-icon ic-purple"><i class="fas fa-wheelchair"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Disability Unit</p><p class="hub-card-desc">Support for disabilities</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="https://www.wsu.ac.za/index.php/international-office" target="_blank" class="hub-card" data-name="international students office visa">
                        <div class="hub-card-icon ic-teal"><i class="fas fa-globe-africa"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">International Office</p><p class="hub-card-desc">International student support</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                </div>
            </div>

            <!-- My Account -->
            <div class="hub-section">
                <h2 class="hub-section-title"><i class="fas fa-user-cog"></i> My Account</h2>
                <div class="hub-grid">
                    <a href="profile.php" class="hub-card" data-name="profile personal information">
                        <div class="hub-card-icon ic-maroon"><i class="fas fa-user-edit"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">My Profile</p><p class="hub-card-desc">Update personal info</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="notifications.php" class="hub-card" data-name="notifications alerts">
                        <div class="hub-card-icon ic-gold"><i class="fas fa-bell"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Notifications</p><p class="hub-card-desc">View all alerts</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="settings.php" class="hub-card" data-name="settings preferences">
                        <div class="hub-card-icon ic-indigo"><i class="fas fa-cog"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Settings</p><p class="hub-card-desc">Manage preferences</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                    <a href="profile.php#password" class="hub-card" data-name="change password security">
                        <div class="hub-card-icon ic-red"><i class="fas fa-lock"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Change Password</p><p class="hub-card-desc">Update your password</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                </div>
            </div>

            <!-- Emergency & Contact -->
            <div class="hub-section">
                <h2 class="hub-section-title"><i class="fas fa-phone-alt"></i> Emergency &amp; Contact</h2>
                <div class="hub-grid">
                    <a href="tel:0437082111" class="hub-card" data-name="emergency security campus protection">
                        <div class="hub-card-icon ic-red"><i class="fas fa-shield-alt"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Campus Security</p><p class="hub-card-desc">043 708 2111</p></div>
                        <i class="fas fa-phone hub-card-arrow" style="color:#dc2626"></i>
                    </a>
                    <a href="tel:0437082000" class="hub-card" data-name="main campus switchboard contact">
                        <div class="hub-card-icon ic-maroon"><i class="fas fa-phone"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Main Switchboard</p><p class="hub-card-desc">043 708 2000</p></div>
                        <i class="fas fa-phone hub-card-arrow" style="color:#8B1A1A"></i>
                    </a>
                    <a href="mailto:info@wsu.ac.za" class="hub-card" data-name="email contact info">
                        <div class="hub-card-icon ic-blue"><i class="fas fa-envelope"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">General Enquiries</p><p class="hub-card-desc">info@wsu.ac.za</p></div>
                        <i class="fas fa-envelope hub-card-arrow" style="color:#2563eb"></i>
                    </a>
                    <a href="https://www.wsu.ac.za/index.php/contact-us" target="_blank" class="hub-card" data-name="contact us campuses locations">
                        <div class="hub-card-icon ic-teal"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="hub-card-body"><p class="hub-card-label">Campus Locations</p><p class="hub-card-desc">Find us on all campuses</p></div>
                        <i class="fas fa-chevron-right hub-card-arrow"></i>
                    </a>
                </div>
            </div>

        </div><!-- /content -->
    </div>
</div>

<!-- Mini Browser Modal -->
<div class="mini-browser-overlay" id="miniBrowserOverlay">
    <div class="mini-browser">
        <div class="mini-browser-toolbar">
            <div class="mb-dots">
                <div class="mb-dot mb-dot-close" id="mbClose" title="Close"></div>
                <div class="mb-dot mb-dot-min"></div>
                <div class="mb-dot mb-dot-max"></div>
            </div>
            <span class="mb-title" id="mbTitle"></span>
            <span class="mb-url" id="mbUrl"></span>
            <button class="mb-btn-newtab" id="mbNewTab"><i class="fas fa-external-link-alt"></i> New Tab</button>
            <button class="mb-btn-close" id="mbCloseBtn"><i class="fas fa-times"></i> Close</button>
        </div>
        <div id="mbLoading" style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;color:#9ca3af;font-size:13px;">
            <div style="width:36px;height:36px;border:3.5px solid #f3f0ee;border-top-color:#8B1A1A;border-radius:50%;animation:mbspin 0.7s linear infinite;"></div>
            <span>Loading…</span>
        </div>
        <style>@keyframes mbspin{to{transform:rotate(360deg);}}</style>
        <iframe id="mbFrame" sandbox="allow-scripts allow-same-origin allow-forms allow-popups" referrerpolicy="no-referrer" style="display:none"></iframe>
        <div class="mini-browser-blocked" id="mbBlocked" style="display:none">
            <i class="fas fa-exclamation-circle"></i>
            <p><strong>This site can't be displayed here.</strong><br>It doesn't allow embedding inside other apps. Click below to open it in a new tab.</p>
            <a id="mbBlockedLink" href="#" target="_blank"><i class="fas fa-external-link-alt"></i> Open in New Tab</a>
        </div>
    </div>
</div>

<script src="js/dashboard.js"></script>
<script>
    var overlay   = document.getElementById('miniBrowserOverlay');
    var mbFrame   = document.getElementById('mbFrame');
    var mbTitle   = document.getElementById('mbTitle');
    var mbUrl     = document.getElementById('mbUrl');
    var mbNewTab  = document.getElementById('mbNewTab');
    var mbCloseBtn = document.getElementById('mbCloseBtn');
    var mbClose   = document.getElementById('mbClose');
    var mbBlocked = document.getElementById('mbBlocked');
    var mbLoading = document.getElementById('mbLoading');
    var blockTimer = null;

    function closeModal() {
        clearTimeout(blockTimer);
        overlay.classList.remove('active');
        mbFrame.src = 'about:blank';
        mbFrame.style.display = 'none';
        mbLoading.style.display = 'none';
        mbBlocked.style.display = 'none';
        document.body.style.overflow = '';
    }

    function showBlocked() {
        clearTimeout(blockTimer);
        mbLoading.style.display = 'none';
        mbFrame.style.display = 'none';
        mbBlocked.style.display = 'flex';
    }

    function openMiniBrowser(url, title) {
        mbTitle.textContent = title;
        mbUrl.textContent = url;
        mbNewTab.onclick = function() { window.open(url, '_blank'); };
        document.getElementById('mbBlockedLink').href = url;
        mbBlocked.style.display = 'none';
        mbFrame.style.display = 'none';
        mbLoading.style.display = 'flex';
        mbFrame.src = url;
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        clearTimeout(blockTimer);
        blockTimer = setTimeout(showBlocked, 5000);
    }

    mbFrame.addEventListener('load', function() {
        if (!mbFrame.src || mbFrame.src === 'about:blank') return;
        clearTimeout(blockTimer);
        mbLoading.style.display = 'none';
        mbFrame.style.display = '';
    });

    mbCloseBtn.addEventListener('click', closeModal);
    mbClose.addEventListener('click', closeModal);
    overlay.addEventListener('click', function(e) { if (e.target === overlay) closeModal(); });

    document.querySelectorAll('.hub-card[target="_blank"]').forEach(function(card) {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            var label = card.querySelector('.hub-card-label');
            openMiniBrowser(card.href, label ? label.textContent.trim() : card.href);
        });
    });

    document.getElementById('hubSearch').addEventListener('input', function() {
        var query = this.value.toLowerCase();
        document.querySelectorAll('.hub-card').forEach(function(card) {
            var name = (card.getAttribute('data-name') || '') + ' ' + (card.querySelector('.hub-card-label') ? card.querySelector('.hub-card-label').textContent : '');
            card.classList.toggle('hidden', query.length > 0 && !name.toLowerCase().includes(query));
        });
        document.querySelectorAll('.hub-section').forEach(function(section) {
            var visible = section.querySelectorAll('.hub-card:not(.hidden)').length;
            section.style.display = visible === 0 && query.length > 0 ? 'none' : '';
        });
    });
</script>
</body>
</html>