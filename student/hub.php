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
        .hub-hero {
            background: linear-gradient(135deg, #3D0A0A 0%, #7A1C1C 60%, #E8A020 100%);
            border-radius: 16px;
            padding: 40px;
            color: #fff;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .hub-hero h1 { font-size: 28px; margin-bottom: 8px; }
        .hub-hero p  { opacity: 0.85; font-size: 15px; max-width: 500px; }
        .hub-hero-icon { font-size: 80px; opacity: 0.2; }

        .hub-search {
            display: flex;
            align-items: center;
            background: #fff;
            border-radius: 10px;
            padding: 4px 16px;
            gap: 10px;
            margin-top: 20px;
            max-width: 420px;
        }
        .hub-search i { color: #9ca3af; }
        .hub-search input {
            border: none;
            outline: none;
            font-size: 14px;
            padding: 10px 0;
            flex: 1;
            color: #1a1a1a;
        }

        .hub-section { margin-bottom: 35px; }
        .hub-section-title {
            font-size: 16px;
            font-weight: 700;
            color: #3D0A0A;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f3f4f6;
        }
        .hub-section-title i { color: #E8A020; }

        .hub-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 14px;
        }

        .hub-card {
            background: #fff;
            border-radius: 14px;
            padding: 22px 16px;
            text-align: center;
            text-decoration: none;
            border: 2px solid #f3f4f6;
            transition: all 0.25s;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        .hub-card:hover {
            border-color: #7A1C1C;
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(122,28,28,0.12);
        }
        .hub-card-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        .hub-card-label {
            font-size: 13px;
            font-weight: 600;
            color: #1a1a1a;
            line-height: 1.3;
        }
        .hub-card-desc {
            font-size: 11px;
            color: #9ca3af;
            line-height: 1.4;
        }
        .hub-card .ext-badge {
            font-size: 10px;
            background: #f3f4f6;
            color: #6b7280;
            padding: 2px 8px;
            border-radius: 20px;
        }

        /* Color themes */
        .ic-maroon  { background: rgba(122,28,28,0.1);  color: #7A1C1C; }
        .ic-gold    { background: rgba(232,160,32,0.12); color: #b87a00; }
        .ic-blue    { background: rgba(37,99,235,0.1);   color: #2563eb; }
        .ic-green   { background: rgba(16,185,129,0.1);  color: #10b981; }
        .ic-purple  { background: rgba(139,92,246,0.1);  color: #8b5cf6; }
        .ic-orange  { background: rgba(249,115,22,0.1);  color: #f97316; }
        .ic-teal    { background: rgba(20,184,166,0.1);  color: #14b8a6; }
        .ic-red     { background: rgba(239,68,68,0.1);   color: #ef4444; }
        .ic-indigo  { background: rgba(99,102,241,0.1);  color: #6366f1; }
        .ic-pink    { background: rgba(236,72,153,0.1);  color: #ec4899; }

        .hub-card.hidden { display: none; }

        /* Mini Browser Modal */
        .mini-browser-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .mini-browser-overlay.active { display: flex; }
        .mini-browser {
            background: #fff;
            border-radius: 14px;
            width: 92vw;
            max-width: 1100px;
            height: 85vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.35);
        }
        .mini-browser-toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            background: #f3f4f6;
            border-bottom: 1px solid #e5e7eb;
            flex-shrink: 0;
        }
        .mini-browser-toolbar .mb-title {
            font-size: 13px;
            font-weight: 600;
            color: #3D0A0A;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 260px;
        }
        .mini-browser-toolbar .mb-url {
            flex: 1;
            font-size: 12px;
            color: #6b7280;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 5px 12px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .mini-browser-toolbar button {
            border: none;
            border-radius: 8px;
            padding: 7px 13px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .mb-btn-newtab { background: #3D0A0A; color: #fff; }
        .mb-btn-newtab:hover { background: #7A1C1C; }
        .mb-btn-close { background: #e5e7eb; color: #374151; }
        .mb-btn-close:hover { background: #d1d5db; }
        .mini-browser iframe {
            flex: 1;
            border: none;
            width: 100%;
        }
        .mini-browser-blocked {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            color: #6b7280;
            padding: 40px;
            text-align: center;
        }
        .mini-browser-blocked i { font-size: 48px; color: #d1d5db; }
        .mini-browser-blocked p { font-size: 14px; max-width: 360px; }
        .mini-browser-blocked a {
            background: #3D0A0A;
            color: #fff;
            padding: 10px 22px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
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
                <div>
                    <h1><i class="fas fa-th-large"></i> WSU Student Hub</h1>
                    <p>Your central access point for all university services, portals, and resources — everything in one place.</p>
                    <div class="hub-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="hubSearch" placeholder="Search services, portals, links...">
                    </div>
                </div>
                <i class="fas fa-university hub-hero-icon"></i>
            </div>

            <!-- Student Apps -->
            <div class="hub-section">
                <h2 class="hub-section-title"><i class="fas fa-mobile-alt"></i> Student Apps & Tools</h2>
                <div class="hub-grid">
                    <a href="https://wiseup.wsu.ac.za/" target="_blank" class="hub-card" data-name="wiseup moodle elearning lms">
                        <div class="hub-card-icon ic-orange"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div><p class="hub-card-label">WiSeUp e-Learning</p><p class="hub-card-desc">Moodle LMS platform</p></div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://outlook.office.com/" target="_blank" class="hub-card" data-name="student email outlook office 365 mail">
                        <div class="hub-card-icon ic-blue"><i class="fas fa-envelope"></i></div>
                        <div><p class="hub-card-label">Student Email</p><p class="hub-card-desc">Outlook web portal</p></div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://www.office.com/" target="_blank" class="hub-card" data-name="microsoft 365 office word excel teams">
                        <div class="hub-card-icon ic-indigo"><i class="fab fa-microsoft"></i></div>
                        <div><p class="hub-card-label">Microsoft 365</p><p class="hub-card-desc">Office apps & Teams</p></div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://ie.wsu.ac.za/pls/prodi41/w99pkg.mi_login" target="_blank" class="hub-card" data-name="student registration portal results">
                        <div class="hub-card-icon ic-maroon"><i class="fas fa-user-graduate"></i></div>
                        <div><p class="hub-card-label">Registration Portal</p><p class="hub-card-desc">Register & view results</p></div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="http://status.wsu.ac.za/status/statuscheck.php" target="_blank" class="hub-card" data-name="admission status student number check">
                        <div class="hub-card-icon ic-teal"><i class="fas fa-search"></i></div>
                        <div><p class="hub-card-label">Admission Status</p><p class="hub-card-desc">Check status & student number</p></div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://status.wsu.ac.za/reset/login.php" target="_blank" class="hub-card" data-name="wsu access pin reset password forgot">
                        <div class="hub-card-icon ic-gold"><i class="fas fa-key"></i></div>
                        <div><p class="hub-card-label">WSU Access / PIN</p><p class="hub-card-desc">Reset PIN & get access</p></div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://wsuacza.sharepoint.com/sites/WalterSisuluUniversity2" target="_blank" class="hub-card" data-name="intranet sharepoint wsu network">
                        <div class="hub-card-icon ic-purple"><i class="fas fa-network-wired"></i></div>
                        <div><p class="hub-card-label">WSU Intranet</p><p class="hub-card-desc">SharePoint network access</p></div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://wsulib.summon.serialssolutions.com/#!/" target="_blank" class="hub-card" data-name="opac library search books journals">
                        <div class="hub-card-icon ic-green"><i class="fas fa-book-open"></i></div>
                        <div><p class="hub-card-label">OPAC Library</p><p class="hub-card-desc">Search books & e-journals</p></div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://print.wsu.ac.za" target="_blank" class="hub-card" data-name="printing student print portal">
                        <div class="hub-card-icon ic-orange"><i class="fas fa-print"></i></div>
                        <div><p class="hub-card-label">Student Printing</p><p class="hub-card-desc">Printing portal</p></div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://mysafespace.wsu.ac.za/mysafespace/" target="_blank" class="hub-card" data-name="safe space wellness safety report">
                        <div class="hub-card-icon ic-pink"><i class="fas fa-shield-alt"></i></div>
                        <div><p class="hub-card-label">My SafeSpace</p><p class="hub-card-desc">Safety & wellness app</p></div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://qualityvoice.wsu.ac.za/" target="_blank" class="hub-card" data-name="quality voice feedback survey student">
                        <div class="hub-card-icon ic-teal"><i class="fas fa-comment-dots"></i></div>
                        <div><p class="hub-card-label">QualityVoice</p><p class="hub-card-desc">Student feedback portal</p></div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://aka.ms/mfasetup" target="_blank" class="hub-card" data-name="mfa multi factor authentication security">
                        <div class="hub-card-icon ic-red"><i class="fas fa-lock"></i></div>
                        <div><p class="hub-card-label">MFA Setup</p><p class="hub-card-desc">Multi-factor authentication</p></div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://forms.office.com/r/4sUJzEzmyk" target="_blank" class="hub-card" data-name="stars survey academic readiness student tracking">
                        <div class="hub-card-icon ic-gold"><i class="fas fa-clipboard-list"></i></div>
                        <div><p class="hub-card-label">STARS Survey</p><p class="hub-card-desc">Academic readiness survey</p></div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                </div>
            </div>

            <!-- Online Portals -->
            <div class="hub-section">
                <h2 class="hub-section-title"><i class="fas fa-globe"></i> Online Portals</h2>
                <div class="hub-grid">
                    <a href="https://wiseup.wsu.ac.za/login/index.php" target="_blank" class="hub-card" data-name="moodle learning management">
                        <div class="hub-card-icon ic-orange"><i class="fas fa-graduation-cap"></i></div>
                        <div>
                            <p class="hub-card-label">Moodle</p>
                            <p class="hub-card-desc">Learning Management System</p>
                        </div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://www.wsu.ac.za/index.php/en/my-wsu-main" target="_blank" class="hub-card" data-name="student portal wsu">
                        <div class="hub-card-icon ic-maroon"><i class="fas fa-user-circle"></i></div>
                        <div>
                            <p class="hub-card-label">Student Portal</p>
                            <p class="hub-card-desc">Academic records & registration</p>
                        </div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://students.wsu.ac.za/mobileverify/" target="_blank" class="hub-card" data-name="mobileverify verification documents">
                        <div class="hub-card-icon ic-blue"><i class="fas fa-shield-alt"></i></div>
                        <div>
                            <p class="hub-card-label">MobileVerify</p>
                            <p class="hub-card-desc">Document verification</p>
                        </div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://www.nsfas.org.za" target="_blank" class="hub-card" data-name="nsfas funding financial aid bursary">
                        <div class="hub-card-icon ic-green"><i class="fas fa-hand-holding-usd"></i></div>
                        <div>
                            <p class="hub-card-label">NSFAS</p>
                            <p class="hub-card-desc">Funding & financial aid</p>
                        </div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://library.wsu.ac.za" target="_blank" class="hub-card" data-name="library books research resources">
                        <div class="hub-card-icon ic-purple"><i class="fas fa-book"></i></div>
                        <div>
                            <p class="hub-card-label">WSU Library</p>
                            <p class="hub-card-desc">Books, journals & research</p>
                        </div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://mail.google.com" target="_blank" class="hub-card" data-name="email gmail student mail">
                        <div class="hub-card-icon ic-red"><i class="fas fa-envelope"></i></div>
                        <div>
                            <p class="hub-card-label">Student Email</p>
                            <p class="hub-card-desc">@mywsu.ac.za mail</p>
                        </div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                </div>
            </div>

            <!-- Booking Services -->
            <div class="hub-section">
                <h2 class="hub-section-title"><i class="fas fa-calendar-check"></i> Book a Service</h2>
                <div class="hub-grid">
                    <a href="book-service.php?category=1" class="hub-card" data-name="academic advising course selection">
                        <div class="hub-card-icon ic-maroon"><i class="fas fa-graduation-cap"></i></div>
                        <div>
                            <p class="hub-card-label">Academic Advising</p>
                            <p class="hub-card-desc">Course & program guidance</p>
                        </div>
                    </a>
                    <a href="book-service.php?category=2" class="hub-card" data-name="career guidance cv interview">
                        <div class="hub-card-icon ic-gold"><i class="fas fa-briefcase"></i></div>
                        <div>
                            <p class="hub-card-label">Career Guidance</p>
                            <p class="hub-card-desc">CV, interviews & career paths</p>
                        </div>
                    </a>
                    <a href="book-service.php?category=3" class="hub-card" data-name="healthcare medical health wellness">
                        <div class="hub-card-icon ic-green"><i class="fas fa-heartbeat"></i></div>
                        <div>
                            <p class="hub-card-label">Healthcare</p>
                            <p class="hub-card-desc">Medical & wellness services</p>
                        </div>
                    </a>
                    <a href="book-service.php?category=4" class="hub-card" data-name="student life activities clubs sports">
                        <div class="hub-card-icon ic-blue"><i class="fas fa-users"></i></div>
                        <div>
                            <p class="hub-card-label">Student Life</p>
                            <p class="hub-card-desc">Clubs, sports & activities</p>
                        </div>
                    </a>
                    <a href="book-service.php?category=5" class="hub-card" data-name="support counselling mental health nsfas financial">
                        <div class="hub-card-icon ic-purple"><i class="fas fa-hands-helping"></i></div>
                        <div>
                            <p class="hub-card-label">Support Services</p>
                            <p class="hub-card-desc">Counselling & financial aid</p>
                        </div>
                    </a>
                    <a href="my-bookings.php" class="hub-card" data-name="my bookings appointments">
                        <div class="hub-card-icon ic-teal"><i class="fas fa-calendar-alt"></i></div>
                        <div>
                            <p class="hub-card-label">My Bookings</p>
                            <p class="hub-card-desc">View & manage appointments</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Academic Resources -->
            <div class="hub-section">
                <h2 class="hub-section-title"><i class="fas fa-book-open"></i> Academic Resources</h2>
                <div class="hub-grid">
                    <a href="https://www.wsu.ac.za/index.php/academic-calendar" target="_blank" class="hub-card" data-name="academic calendar dates timetable">
                        <div class="hub-card-icon ic-maroon"><i class="fas fa-calendar"></i></div>
                        <div>
                            <p class="hub-card-label">Academic Calendar</p>
                            <p class="hub-card-desc">Key dates & deadlines</p>
                        </div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://www.wsu.ac.za/index.php/examinations" target="_blank" class="hub-card" data-name="exams timetable results">
                        <div class="hub-card-icon ic-orange"><i class="fas fa-file-alt"></i></div>
                        <div>
                            <p class="hub-card-label">Examinations</p>
                            <p class="hub-card-desc">Exam timetables & results</p>
                        </div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://www.wsu.ac.za/index.php/faculties" target="_blank" class="hub-card" data-name="faculties departments programmes">
                        <div class="hub-card-icon ic-indigo"><i class="fas fa-university"></i></div>
                        <div>
                            <p class="hub-card-label">Faculties</p>
                            <p class="hub-card-desc">Departments & programmes</p>
                        </div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://www.wsu.ac.za/index.php/research" target="_blank" class="hub-card" data-name="research postgraduate">
                        <div class="hub-card-icon ic-teal"><i class="fas fa-flask"></i></div>
                        <div>
                            <p class="hub-card-label">Research</p>
                            <p class="hub-card-desc">Research & postgraduate info</p>
                        </div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                </div>
            </div>

            <!-- Student Support -->
            <div class="hub-section">
                <h2 class="hub-section-title"><i class="fas fa-life-ring"></i> Student Support</h2>
                <div class="hub-grid">
                    <a href="https://www.wsu.ac.za/index.php/student-wellness" target="_blank" class="hub-card" data-name="wellness mental health counselling">
                        <div class="hub-card-icon ic-pink"><i class="fas fa-heart"></i></div>
                        <div>
                            <p class="hub-card-label">Student Wellness</p>
                            <p class="hub-card-desc">Mental health & counselling</p>
                        </div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://www.wsu.ac.za/index.php/accommodation" target="_blank" class="hub-card" data-name="accommodation residence housing">
                        <div class="hub-card-icon ic-gold"><i class="fas fa-home"></i></div>
                        <div>
                            <p class="hub-card-label">Accommodation</p>
                            <p class="hub-card-desc">Residence & housing</p>
                        </div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://www.wsu.ac.za/index.php/sport" target="_blank" class="hub-card" data-name="sport recreation fitness gym">
                        <div class="hub-card-icon ic-green"><i class="fas fa-running"></i></div>
                        <div>
                            <p class="hub-card-label">Sport & Recreation</p>
                            <p class="hub-card-desc">Facilities & sports clubs</p>
                        </div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://www.wsu.ac.za/index.php/transport" target="_blank" class="hub-card" data-name="transport shuttle bus">
                        <div class="hub-card-icon ic-blue"><i class="fas fa-bus"></i></div>
                        <div>
                            <p class="hub-card-label">Transport</p>
                            <p class="hub-card-desc">Shuttle & transport services</p>
                        </div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://www.wsu.ac.za/index.php/disability-unit" target="_blank" class="hub-card" data-name="disability support unit">
                        <div class="hub-card-icon ic-purple"><i class="fas fa-wheelchair"></i></div>
                        <div>
                            <p class="hub-card-label">Disability Unit</p>
                            <p class="hub-card-desc">Support for students with disabilities</p>
                        </div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                    <a href="https://www.wsu.ac.za/index.php/international-office" target="_blank" class="hub-card" data-name="international students office visa">
                        <div class="hub-card-icon ic-teal"><i class="fas fa-globe-africa"></i></div>
                        <div>
                            <p class="hub-card-label">International Office</p>
                            <p class="hub-card-desc">International student support</p>
                        </div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                </div>
            </div>

            <!-- Quick Account Links -->
            <div class="hub-section">
                <h2 class="hub-section-title"><i class="fas fa-user-cog"></i> My Account</h2>
                <div class="hub-grid">
                    <a href="profile.php" class="hub-card" data-name="profile personal information">
                        <div class="hub-card-icon ic-maroon"><i class="fas fa-user-edit"></i></div>
                        <div>
                            <p class="hub-card-label">My Profile</p>
                            <p class="hub-card-desc">Update personal info</p>
                        </div>
                    </a>
                    <a href="notifications.php" class="hub-card" data-name="notifications alerts">
                        <div class="hub-card-icon ic-gold"><i class="fas fa-bell"></i></div>
                        <div>
                            <p class="hub-card-label">Notifications</p>
                            <p class="hub-card-desc">View all alerts</p>
                        </div>
                    </a>
                    <a href="settings.php" class="hub-card" data-name="settings preferences">
                        <div class="hub-card-icon ic-indigo"><i class="fas fa-cog"></i></div>
                        <div>
                            <p class="hub-card-label">Settings</p>
                            <p class="hub-card-desc">Manage preferences</p>
                        </div>
                    </a>
                    <a href="profile.php#password" class="hub-card" data-name="change password security">
                        <div class="hub-card-icon ic-red"><i class="fas fa-lock"></i></div>
                        <div>
                            <p class="hub-card-label">Change Password</p>
                            <p class="hub-card-desc">Update your password</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Emergency Contacts -->
            <div class="hub-section">
                <h2 class="hub-section-title"><i class="fas fa-phone-alt"></i> Emergency & Contact</h2>
                <div class="hub-grid">
                    <a href="tel:0437082111" class="hub-card" data-name="emergency security campus protection">
                        <div class="hub-card-icon ic-red"><i class="fas fa-shield-alt"></i></div>
                        <div>
                            <p class="hub-card-label">Campus Security</p>
                            <p class="hub-card-desc">043 708 2111</p>
                        </div>
                    </a>
                    <a href="tel:0437082000" class="hub-card" data-name="main campus switchboard contact">
                        <div class="hub-card-icon ic-maroon"><i class="fas fa-phone"></i></div>
                        <div>
                            <p class="hub-card-label">Main Switchboard</p>
                            <p class="hub-card-desc">043 708 2000</p>
                        </div>
                    </a>
                    <a href="mailto:info@wsu.ac.za" class="hub-card" data-name="email contact info">
                        <div class="hub-card-icon ic-blue"><i class="fas fa-envelope"></i></div>
                        <div>
                            <p class="hub-card-label">General Enquiries</p>
                            <p class="hub-card-desc">info@wsu.ac.za</p>
                        </div>
                    </a>
                    <a href="https://www.wsu.ac.za/index.php/contact-us" target="_blank" class="hub-card" data-name="contact us campuses locations">
                        <div class="hub-card-icon ic-teal"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <p class="hub-card-label">Campus Locations</p>
                            <p class="hub-card-desc">Find us on all campuses</p>
                        </div>
                        <span class="ext-badge"><i class="fas fa-external-link-alt"></i> External</span>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Mini Browser Modal -->
<div class="mini-browser-overlay" id="miniBrowserOverlay">
    <div class="mini-browser">
        <div class="mini-browser-toolbar">
            <span class="mb-title" id="mbTitle"></span>
            <span class="mb-url" id="mbUrl"></span>
            <button class="mb-btn-newtab" id="mbNewTab"><i class="fas fa-external-link-alt"></i> Open in New Tab</button>
            <button class="mb-btn-close" id="mbClose"><i class="fas fa-times"></i> Close</button>
        </div>
        <div id="mbLoading" style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;color:#6b7280;font-size:14px;">
            <div style="width:40px;height:40px;border:4px solid #f3f4f6;border-top-color:#7A1C1C;border-radius:50%;animation:mbspin 0.8s linear infinite;"></div>
            <span>Loading, please wait...</span>
        </div>
        <style>@keyframes mbspin{to{transform:rotate(360deg);}}</style>
        <iframe id="mbFrame" sandbox="allow-scripts allow-same-origin allow-forms allow-popups" referrerpolicy="no-referrer" style="display:none"></iframe>
        <div class="mini-browser-blocked" id="mbBlocked" style="display:none">
            <i class="fas fa-exclamation-circle"></i>
            <p><strong>This site can't be displayed here.</strong><br>It doesn't allow embedding inside other apps. Click the button below to open it in a new browser tab.</p>
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
    var mbClose   = document.getElementById('mbClose');
    var mbBlocked = document.getElementById('mbBlocked');
    var mbLoading = document.getElementById('mbLoading');
    var currentUrl = '';
    var blockTimer = null;

    function showBlocked() {
        clearTimeout(blockTimer);
        mbLoading.style.display = 'none';
        mbFrame.style.display = 'none';
        mbBlocked.style.display = 'flex';
    }

    function openMiniBrowser(url, title) {
        currentUrl = url;
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

    mbClose.addEventListener('click', function() {
        clearTimeout(blockTimer);
        overlay.classList.remove('active');
        mbFrame.src = 'about:blank';
        mbFrame.style.display = 'none';
        mbLoading.style.display = 'none';
        mbBlocked.style.display = 'none';
        document.body.style.overflow = '';
    });

    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) mbClose.click();
    });

    // Intercept all external hub-card links
    document.querySelectorAll('.hub-card[target="_blank"]').forEach(function(card) {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            var label = card.querySelector('.hub-card-label');
            openMiniBrowser(card.href, label ? label.textContent.trim() : card.href);
        });
    });

    // Search filter
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
