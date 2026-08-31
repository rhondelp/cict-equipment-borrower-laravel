@extends("components.default")

@section("title", "CICT Equipment Borrower System — College of Information & Communications Technology, UNM")

@push("styles")
<link rel="stylesheet" href="{{ asset('css/landing.css') }}">
@endpush

@section("content")
@php
    try {
        $availableEquipment = \App\Models\Equipment::where('status', 'Available')->sum('available');
    } catch (\Throwable $e) {
        $availableEquipment = 126;
    }

    $catalogItems = [
        ['name' => 'Dell Latitude 5420 Workstation', 'category' => 'laptops', 'category_name' => 'Laptops & PCs', 'icon' => 'fa-laptop', 'specs' => 'Core i7 · 16GB RAM · 512GB SSD', 'total' => 25, 'available' => 18, 'status' => 'Available', 'badge' => 'avail'],
        ['name' => 'Arduino Mega 2560 Pro Kit', 'category' => 'iot', 'category_name' => 'Microcontrollers & IoT', 'icon' => 'fa-microchip', 'specs' => 'ATmega2560 · 54 Digital I/O · Sensors', 'total' => 40, 'available' => 32, 'status' => 'Available', 'badge' => 'avail'],
        ['name' => 'Epson EB-X51 High-Lumens Projector', 'category' => 'av', 'category_name' => 'Projectors & AV', 'icon' => 'fa-video', 'specs' => '3800 Lumens · HDMI/VGA · Wireless', 'total' => 12, 'available' => 4, 'status' => 'Limited Stock', 'badge' => 'limited'],
        ['name' => 'Cisco Catalyst 2960 24-Port Switch', 'category' => 'network', 'category_name' => 'Networking & Tools', 'icon' => 'fa-network-wired', 'specs' => 'Layer 2 · 24x Gigabit · Console Cable', 'total' => 15, 'available' => 11, 'status' => 'Available', 'badge' => 'avail'],
        ['name' => 'Raspberry Pi 4 Model B (8GB Kit)', 'category' => 'iot', 'category_name' => 'Microcontrollers & IoT', 'icon' => 'fa-memory', 'specs' => 'Quad-core 1.5GHz · 64GB MicroSD', 'total' => 20, 'available' => 0, 'status' => 'In Use', 'badge' => 'inuse'],
        ['name' => 'Fluke 117 Digital Multimeter', 'category' => 'network', 'category_name' => 'Networking & Tools', 'icon' => 'fa-bolt', 'specs' => 'True-RMS · Non-Contact Voltage · Probes', 'total' => 18, 'available' => 15, 'status' => 'Available', 'badge' => 'avail'],
    ];
@endphp

<div class="landing-page-root">
    {{-- TOP NAVBAR --}}
    <header class="cict-nav">
        <div class="nav-container">
            <a href="{{ url('/') }}" class="nav-brand">
                <div class="nav-brand-logo">
                    <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT Logo">
                </div>
                <div class="nav-brand-text">
                    <span class="brand-title">CICT <span>EQUIPMENT</span></span>
                    <span class="brand-sub">Northwest Samar State University · UNM</span>
                </div>
            </a>

            <nav class="nav-links" id="navLinks">
                <a href="#features" class="nav-link">Features</a>
                <a href="#workflow" class="nav-link">Workflow</a>
                <a href="#catalog" class="nav-link">Live Catalog</a>
                <a href="#testing-hub" class="nav-link nav-link-highlight">
                    <i class="fa-solid fa-flask-vial"></i> Testing Hub
                </a>
                <a href="#faq" class="nav-link">FAQ</a>
            </nav>

            <div class="nav-actions">
                <button type="button" id="themeToggleBtn" class="theme-toggle-btn" title="Toggle Dark / Light Theme" aria-label="Toggle Theme">
                    <i class="fa-solid fa-moon dark-icon"></i>
                    <i class="fa-solid fa-sun light-icon"></i>
                </button>

                <div class="test-pill" title="Testing Environment Active">
                    <span class="pulse-dot"></span>
                    <span class="test-pill-text">Test Mode</span>
                </div>

                <a href="{{ route('login') }}" class="nav-btn-secondary">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    <span>Log In</span>
                </a>
                <a href="{{ route('register') }}" class="nav-btn-primary">
                    <span>Create Account</span>
                </a>

                <button type="button" class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open navigation menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>

        {{-- Mobile Drawer Menu --}}
        <div class="mobile-menu" id="mobileMenu">
            <a href="#features" class="mobile-nav-link"><i class="fa-solid fa-layer-group"></i> Features</a>
            <a href="#workflow" class="mobile-nav-link"><i class="fa-solid fa-timeline"></i> Workflow</a>
            <a href="#catalog" class="mobile-nav-link"><i class="fa-solid fa-boxes-stacked"></i> Live Catalog</a>
            <a href="#testing-hub" class="mobile-nav-link"><i class="fa-solid fa-flask-vial"></i> Testing Hub &amp; Roles</a>
            <a href="#faq" class="mobile-nav-link"><i class="fa-solid fa-circle-question"></i> FAQ &amp; Policies</a>
            <div class="mobile-menu-actions">
                <a href="{{ route('login') }}" class="nav-btn-secondary w-full justify-center">Log In</a>
                <a href="{{ route('register') }}" class="nav-btn-primary w-full justify-center">Create Account</a>
            </div>
        </div>
    </header>

    {{-- HERO SECTION --}}
    <section class="hero-section">
        <div class="ambient-glow glow-1"></div>
        <div class="ambient-glow glow-2"></div>
        <div class="grid-overlay"></div>

        <div class="hero-content">
            <div class="hero-badge animate-fade-in">
                <span class="hero-badge-dot"></span>
                <span class="hero-badge-tag">CICT Laboratory Hub</span>
                <span class="hero-badge-sep">&middot;</span>
                <span class="hero-badge-msg">Equipment Requisition &amp; Asset Management v2.4</span>
            </div>

            <h1 class="hero-title animate-fade-in">
                Smart Hardware Requisition <br>
                <span class="gradient-text">&amp; Lab Asset Tracking</span>
            </h1>

            <p class="hero-subtitle animate-fade-in">
                The centralized laboratory inventory platform for CICT students, faculty, and custodians.
                Seamlessly request microcontrollers, laptops, networking kits, and audiovisual gear with real-time stock deductions and automated return alerts.
            </p>

            <div class="hero-actions animate-fade-in">
                <a href="{{ route('login') }}" class="btn-hero-primary">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>Access Portal</span>
                    <i class="fa-solid fa-arrow-right arrow-icon"></i>
                </a>
                <a href="{{ route('register') }}" class="btn-hero-secondary">
                    <i class="fa-solid fa-user-plus"></i>
                    <span>Register Account</span>
                </a>
                <a href="#testing-hub" class="btn-hero-tertiary">
                    <i class="fa-solid fa-flask-vial"></i>
                    <span>Testing Hub &amp; Roles</span>
                </a>
            </div>

            <div class="hero-metrics-bar animate-fade-in">
                <div class="metric-item">
                    <div class="metric-icon metric-blue"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <div class="metric-info">
                        <span class="metric-value">{{ $availableEquipment > 0 ? $availableEquipment : '120+' }}</span>
                        <span class="metric-label">Hardware Units Available</span>
                    </div>
                </div>
                <div class="metric-item">
                    <div class="metric-icon metric-emerald"><i class="fa-solid fa-bolt"></i></div>
                    <div class="metric-info">
                        <span class="metric-value">&lt; 5 Mins</span>
                        <span class="metric-label">Rapid Requisition Dispatch</span>
                    </div>
                </div>
                <div class="metric-item">
                    <div class="metric-icon metric-amber"><i class="fa-solid fa-bell"></i></div>
                    <div class="metric-info">
                        <span class="metric-value">Automated</span>
                        <span class="metric-label">Email Return Alerts</span>
                    </div>
                </div>
                <div class="metric-item">
                    <div class="metric-icon metric-purple"><i class="fa-solid fa-shield-halved"></i></div>
                    <div class="metric-info">
                        <span class="metric-value">3 Roles</span>
                        <span class="metric-label">Admin, Instructor &amp; Student</span>
                    </div>
                </div>
            </div>
    {{-- TESTING & DEMO HUB --}}
    <section class="section-wrap testing-hub-section" id="testing-hub">
        <div class="section-container">
            <div class="section-header">
                <div class="section-kicker">
                    <i class="fa-solid fa-flask-vial"></i> Interactive Testing Hub
                </div>
                <h2 class="section-title">Ready for Multi-Role Testing</h2>
                <p class="section-desc">
                    Explore the complete user journey across all three distinct role privilege tiers.
                    Choose a role below to review capabilities or sign in with test accounts.
                </p>
            </div>

            <div class="roles-grid">
                {{-- Student Role Card --}}
                <div class="role-card">
                    <div class="role-card-header">
                        <div class="role-avatar role-student"><i class="fa-solid fa-user-graduate"></i></div>
                        <div class="role-meta">
                            <span class="role-badge badge-student">Student Role</span>
                            <h3 class="role-name">Student Borrower</h3>
                        </div>
                    </div>
                    <p class="role-summary">Borrow hardware for laboratory exercises, capstone projects, and programming courses.</p>
                    <ul class="role-features">
                        <li><i class="fa-solid fa-circle-check text-emerald"></i> Real-time equipment inventory search</li>
                        <li><i class="fa-solid fa-circle-check text-emerald"></i> Requisition submission with class schedules</li>
                        <li><i class="fa-solid fa-circle-check text-emerald"></i> Live borrow status &amp; return countdown</li>
                        <li><i class="fa-solid fa-circle-check text-emerald"></i> Automatic email notices before due date</li>
                    </ul>
                    <div class="role-card-footer">
                        <a href="{{ route('login') }}" class="role-btn btn-role-student">
                            <span>Test Student Flow</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                {{-- Instructor Role Card --}}
                <div class="role-card role-card-featured">
                    <div class="featured-ribbon">Faculty Access</div>
                    <div class="role-card-header">
                        <div class="role-avatar role-instructor"><i class="fa-solid fa-chalkboard-user"></i></div>
                        <div class="role-meta">
                            <span class="role-badge badge-instructor">Instructor Role</span>
                            <h3 class="role-name">Faculty / Instructor</h3>
                        </div>
                    </div>
                    <p class="role-summary">Requisition teaching hardware, manage subject schedules, and supervise student lab items.</p>
                    <ul class="role-features">
                        <li><i class="fa-solid fa-circle-check text-blue"></i> Bulk hardware requests for lecture sessions</li>
                        <li><i class="fa-solid fa-circle-check text-blue"></i> Link class schedule &amp; laboratory room</li>
                        <li><i class="fa-solid fa-circle-check text-blue"></i> Cancel or update pending requisitions</li>
                        <li><i class="fa-solid fa-circle-check text-blue"></i> View active borrowed teaching equipment</li>
                    </ul>
                    <div class="role-card-footer">
                        <a href="{{ route('login') }}" class="role-btn btn-role-instructor">
                            <span>Test Instructor Flow</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                {{-- Admin / Custodian Role Card --}}
                <div class="role-card">
                    <div class="role-card-header">
                        <div class="role-avatar role-admin"><i class="fa-solid fa-shield-halved"></i></div>
                        <div class="role-meta">
                            <span class="role-badge badge-admin">Administrator Role</span>
                            <h3 class="role-name">Lab Custodian / Admin</h3>
                        </div>
                    </div>
                    <p class="role-summary">Full administrative authority over equipment catalog, approvals, inline statuses, and audit trails.</p>
                    <ul class="role-features">
                        <li><i class="fa-solid fa-circle-check text-purple"></i> Equipment CRUD &amp; live quantity stock control</li>
                        <li><i class="fa-solid fa-circle-check text-purple"></i> Approve / Decline requisitions with 1 click</li>
                        <li><i class="fa-solid fa-circle-check text-purple"></i> Inline transaction updates &amp; return logs</li>
                        <li><i class="fa-solid fa-circle-check text-purple"></i> Manual &amp; cron-triggered overdue email alerts</li>
                    </ul>
                    <div class="role-card-footer">
                        <a href="{{ route('login') }}" class="role-btn btn-role-admin">
                            <span>Test Admin Console</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            {{-- Quick Testing Sandbox Accordion --}}
            <div class="testing-sandbox-box">
                <div class="sandbox-header">
                    <div class="sandbox-title-wrap">
                        <div class="sandbox-icon"><i class="fa-solid fa-terminal"></i></div>
                        <div>
                            <h4 class="sandbox-title">Testing Sandbox &amp; Verification Guide</h4>
                            <p class="sandbox-sub">Recommended test scenarios for QA validation</p>
                        </div>
                    </div>
                    <button type="button" class="sandbox-toggle" id="sandboxToggleBtn">
                        <span id="sandboxToggleText">View Test Scenarios</span>
                        <i class="fa-solid fa-chevron-down" id="sandboxChevron"></i>
                    </button>
                </div>

                <div class="sandbox-body" id="sandboxBody">
                    <div class="sandbox-scenarios">
                        <div class="scenario-card">
                            <div class="scenario-num">01</div>
                            <div class="scenario-details">
                                <h5>Student / Instructor Requisition</h5>
                                <p>Register or log in, visit the borrower dashboard, choose an available hardware item, specify purpose &amp; subject schedule, and submit request.</p>
                            </div>
                        </div>
                        <div class="scenario-card">
                            <div class="scenario-num">02</div>
                            <div class="scenario-details">
                                <h5>Admin Approval &amp; Stock Deduction</h5>
                                <p>Log in as Admin, navigate to <em>Requests</em>, click Approve. Confirm that equipment available quantity decreases instantly in real-time.</p>
                            </div>
                        </div>
                        <div class="scenario-card">
                            <div class="scenario-num">03</div>
                            <div class="scenario-details">
                                <h5>Custody Release &amp; Return Logging</h5>
                                <p>In Admin <em>Transactions</em>, transition status to Released, then Returned. Confirm a return log is recorded with remarks and stock restores.</p>
                            </div>
                        </div>
                        <div class="scenario-card">
                            <div class="scenario-num">04</div>
                            <div class="scenario-details">
                                <h5>Automated Return Alert System</h5>
                                <p>Test automated email triggers via the admin action or schedule scripts (<code>send_alert.bat</code>) to verify reminder delivery.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

        </div>
    </section>

    {{-- LIVE EQUIPMENT CATALOG --}}
    <section class="section-wrap catalog-section" id="catalog">
        <div class="section-container">
            <div class="section-header">
                <div class="section-kicker">
                    <i class="fa-solid fa-boxes-stacked"></i> Real-Time Inventory
                </div>
                <h2 class="section-title">Laboratory Equipment Catalog</h2>
                <p class="section-desc">
                    Search and inspect available CICT laboratory hardware ready for immediate requisition.
                </p>
            </div>

            <div class="catalog-controls">
                <div class="catalog-tabs" id="catalogTabs">
                    <button type="button" class="tab-btn active" data-category="all">
                        <i class="fa-solid fa-border-all"></i> All
                    </button>
                    <button type="button" class="tab-btn" data-category="laptops">
                        <i class="fa-solid fa-laptop"></i> Laptops
                    </button>
                    <button type="button" class="tab-btn" data-category="iot">
                        <i class="fa-solid fa-microchip"></i> IoT
                    </button>
                    <button type="button" class="tab-btn" data-category="av">
                        <i class="fa-solid fa-video"></i> AV
                    </button>
                    <button type="button" class="tab-btn" data-category="network">
                        <i class="fa-solid fa-network-wired"></i> Networking
                    </button>
                </div>

                <div class="catalog-search-wrap">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="catalogSearchInput" class="catalog-search-input" placeholder="Search by name or spec..." autocomplete="off">
                    <button type="button" id="catalogSearchClear" class="search-clear-btn hidden" aria-label="Clear Search">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            <div class="catalog-grid" id="catalogGrid">
                @foreach ($catalogItems as $item)
                    <div class="catalog-card" data-category="{{ $item['category'] }}" data-name="{{ strtolower($item['name']) }}" data-specs="{{ strtolower($item['specs']) }}">
                        <div class="card-top">
                            <div class="card-icon-wrap">
                                <i class="fa-solid {{ $item['icon'] }}"></i>
                            </div>
                            <span class="status-pill status-{{ $item['badge'] }}">
                                <span class="status-dot"></span>
                                {{ $item['status'] }}
                            </span>
                        </div>
                        <div class="card-body">
                            <span class="card-category">{{ $item['category_name'] }}</span>
                            <h4 class="card-title">{{ $item['name'] }}</h4>
                            <p class="card-specs">{{ $item['specs'] }}</p>
                        </div>
                        <div class="card-bottom">
                            <div class="stock-progress">
                                <div class="stock-labels">
                                    <span class="stock-label-text">Availability</span>
                                    <span class="stock-count"><strong>{{ $item['available'] }}</strong> / {{ $item['total'] }} units</span>
                                </div>
                                <div class="progress-bar-track">
                                    <div class="progress-bar-fill progress-{{ $item['badge'] }}" style="width: {{ $item['total'] > 0 ? round(($item['available'] / $item['total']) * 100) : 0 }}%"></div>
                                </div>
                            </div>
                            <a href="{{ route('login') }}" class="card-req-btn" title="Sign in to request this equipment">
                                <span>Requisition</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="catalog-empty hidden" id="catalogEmpty">
                <div class="empty-icon"><i class="fa-solid fa-box-open"></i></div>
                <h4 class="empty-title">No Matching Equipment Found</h4>
                <p class="empty-desc">Try clearing your search query or selecting a different category tab.</p>
                <button type="button" class="btn-reset-filters" id="resetFiltersBtn">Reset Filters</button>
            </div>
        </div>
    </section>

    {{-- CORE FEATURES --}}
    <section class="section-wrap features-section" id="features">
        <div class="section-container">
            <div class="section-header">
                <div class="section-kicker">
                    <i class="fa-solid fa-cubes"></i> Core Capabilities
                </div>
                <h2 class="section-title">Engineered for Laboratory Integrity</h2>
                <p class="section-desc">
                    A purpose-built asset control framework eliminating paper logs, missing equipment, and scheduling bottlenecks.
                </p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon feature-blue"><i class="fa-solid fa-bolt-lightning"></i></div>
                    <h3 class="feature-title">Instant Online Requisition</h3>
                    <p class="feature-desc">Submit borrow requests in seconds. Tag relevant subjects, laboratory rooms, and purpose without physical slips.</p>
                    <div class="feature-tag">Real-Time Queue</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon feature-emerald"><i class="fa-solid fa-sliders"></i></div>
                    <h3 class="feature-title">Live Inventory Balancing</h3>
                    <p class="feature-desc">Stock quantities automatically decrement upon approval/release and safely increment back upon verified return inspection.</p>
                    <div class="feature-tag">Zero Overbooking</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon feature-amber"><i class="fa-solid fa-envelope-open-text"></i></div>
                    <h3 class="feature-title">Automated Return Alerts</h3>
                    <p class="feature-desc">Scheduled cron jobs &amp; instant manual email alerts notify borrowers before deadlines to prevent overdue penalties.</p>
                    <div class="feature-tag">SMTP &amp; Batch Ready</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon feature-purple"><i class="fa-solid fa-clipboard-check"></i></div>
                    <h3 class="feature-title">Return Logs &amp; Audit Trail</h3>
                    <p class="feature-desc">Every transaction is permanently logged with condition ratings, remarks, timestamps, and custodian verification notes.</p>
                    <div class="feature-tag">Complete Accountability</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon feature-cyan"><i class="fa-solid fa-table-columns"></i></div>
                    <h3 class="feature-title">Inline Admin Control</h3>
                    <p class="feature-desc">Change statuses on the fly from Pending to Approved, Released, or Returned with quick inline dropdown triggers.</p>
                    <div class="feature-tag">Rapid Administration</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon feature-rose"><i class="fa-solid fa-calendar-days"></i></div>
                    <h3 class="feature-title">Schedule Integration</h3>
                    <p class="feature-desc">Seamlessly link instructor class schedules to prevent conflicting hardware reservations across laboratory sections.</p>
                    <div class="feature-tag">Conflict Prevention</div>
                </div>
            </div>
        </div>
    </section>

    {{-- WORKFLOW --}}
    <section class="section-wrap workflow-section" id="workflow">
        <div class="section-container">
            <div class="section-header">
                <div class="section-kicker">
                    <i class="fa-solid fa-diagram-project"></i> Simple 4-Step Process
                </div>
                <h2 class="section-title">How the System Works</h2>
                <p class="section-desc">
                    From requisition to check-in, each stage is fully trackable and transparent.
                </p>
            </div>

            <div class="workflow-steps">
                <div class="step-card">
                    <div class="step-badge">Step 01</div>
                    <div class="step-icon"><i class="fa-solid fa-arrow-pointer"></i></div>
                    <h4 class="step-title">Browse &amp; Requisition</h4>
                    <p class="step-desc">Student or instructor selects required laboratory equipment, specifies duration, purpose, and associated subject.</p>
                </div>
                <div class="step-card">
                    <div class="step-badge">Step 02</div>
                    <div class="step-icon"><i class="fa-solid fa-user-check"></i></div>
                    <h4 class="step-title">Verification &amp; Approval</h4>
                    <p class="step-desc">Lab custodian or instructor validates the request against available inventory and endorses the requisition.</p>
                </div>
                <div class="step-card">
                    <div class="step-badge">Step 03</div>
                    <div class="step-icon"><i class="fa-solid fa-box-check"></i></div>
                    <h4 class="step-title">Custody Release</h4>
                    <p class="step-desc">The borrower claims the physical equipment at the CICT custodian office with student/faculty identification.</p>
                </div>
                <div class="step-card">
                    <div class="step-badge">Step 04</div>
                    <div class="step-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                    <h4 class="step-title">Return &amp; Condition Audit</h4>
                    <p class="step-desc">Equipment is inspected upon return, automatically logged into return history, and restocked to available balance.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- SYSTEM DIAGNOSTICS & TELEMETRY --}}
    <section class="section-wrap telemetry-section">
        <div class="section-container">
            <div class="telemetry-card">
                <div class="telemetry-header">
                    <div class="telemetry-title-group">
                        <div class="telemetry-pulse"></div>
                        <h3 class="telemetry-heading">System Diagnostics &amp; Environment Telemetry</h3>
                    </div>
                    <span class="telemetry-badge">Testing Mode Active</span>
                </div>

                <div class="telemetry-grid">
                    <div class="telemetry-item">
                        <span class="tele-label">Framework Engine</span>
                        <span class="tele-value text-blue"><i class="fa-brands fa-laravel"></i> Laravel 12.x</span>
                    </div>
                    <div class="telemetry-item">
                        <span class="tele-label">Database Connection</span>
                        <span class="tele-value text-emerald"><i class="fa-solid fa-database"></i> MySQL / MariaDB Active</span>
                    </div>
                    <div class="telemetry-item">
                        <span class="tele-label">Auth &amp; Session Guard</span>
                        <span class="tele-value text-amber"><i class="fa-solid fa-lock"></i> Multi-Role RBAC</span>
                    </div>
                    <div class="telemetry-item">
                        <span class="tele-label">Alert Mailer Engine</span>
                        <span class="tele-value text-purple"><i class="fa-solid fa-paper-plane"></i> SMTP Mail Queue</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="section-wrap faq-section" id="faq">
        <div class="section-container">
            <div class="section-header">
                <div class="section-kicker">
                    <i class="fa-solid fa-circle-question"></i> Help &amp; Policies
                </div>
                <h2 class="section-title">Frequently Asked Questions</h2>
                <p class="section-desc">
                    Essential guidelines for borrowing CICT hardware and laboratory facilities.
                </p>
            </div>

            <div class="faq-list">
                <div class="faq-item">
                    <button type="button" class="faq-question">
                        <span>Who is eligible to borrow CICT equipment?</span>
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        <p>All officially enrolled CICT students and current faculty members of Northwest Samar State University are eligible to requisition equipment for academic, laboratory, or capstone purposes.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <button type="button" class="faq-question">
                        <span>What is the standard borrowing duration?</span>
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Standard laboratory gear is issued on a same-day or per-class-session basis (up to 5:00 PM). Special project extensions for capstone or research hardware can be approved directly by the Lab Custodian.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <button type="button" class="faq-question">
                        <span>How do automated email notifications work?</span>
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        <p>The system automatically sends friendly email reminders to your registered email address when an item is scheduled for return or if a deadline has passed, keeping your borrowing record clear.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <button type="button" class="faq-question">
                        <span>What should I do if an item is damaged during use?</span>
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Immediately report any malfunction or accidental damage upon return. The custodian will inspect the hardware and log the condition in the official Return Log audit trail for evaluation.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA BANNER --}}
    <section class="section-wrap cta-section">
        <div class="section-container">
            <div class="cta-banner">
                <div class="cta-banner-content">
                    <h2 class="cta-title">Ready to Test the Equipment System?</h2>
                    <p class="cta-desc">Sign in with an authorized account or register to experience the complete streamlined workflow.</p>
                    <div class="cta-actions">
                        <a href="{{ route('login') }}" class="btn-hero-primary">
                            <i class="fa-solid fa-right-to-bracket"></i>
                            <span>Sign In to System</span>
                        </a>
                        <a href="{{ route('register') }}" class="btn-hero-secondary">
                            <i class="fa-solid fa-user-plus"></i>
                            <span>Create Account</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FOOTER --}}
    <footer class="cict-footer">
        <div class="footer-container">
            <div class="footer-top">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT Seal">
                    </div>
                    <div>
                        <h4 class="footer-brand-title">College of Information &amp; Communications Technology</h4>
                        <p class="footer-brand-sub">Northwest Samar State University &middot; Main Campus</p>
                    </div>
                </div>

                <div class="footer-info-grid">
                    <div class="footer-col">
                        <h5>Laboratory Custodian Office</h5>
                        <p><i class="fa-solid fa-location-dot"></i> CICT Building, 2nd Floor, Room 204</p>
                        <p><i class="fa-solid fa-clock"></i> Mon &ndash; Fri: 8:00 AM &ndash; 5:00 PM</p>
                    </div>
                    <div class="footer-col">
                        <h5>Quick Navigation</h5>
                        <p><a href="{{ route('login') }}">Portal Sign In</a></p>
                        <p><a href="{{ route('register') }}">Student / Faculty Registration</a></p>
                        <p><a href="#testing-hub">Testing Hub &amp; Scenarios</a></p>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} College of Information &amp; Communications Technology. All rights reserved.</p>
                <div class="footer-meta">
                    <span class="version-badge">Version 2.4-test</span>
                    <span>Designed for High-Reliability Lab Operations</span>
                </div>
            </div>
        </div>
    </footer>

    {{-- Back to Top Floating Button --}}
    <button type="button" class="back-to-top" id="backToTopBtn" title="Back to top" aria-label="Back to top">
        <i class="fa-solid fa-arrow-up"></i>
    </button>

</div>

{{-- =========================================================
     LANDING PAGE INTERACTIVE JAVASCRIPT
     ========================================================= --}}
<script>
document.addEventListener('DOMContentLoaded', function() {

    // 1. THEME SWITCHER
    const themeBtn = document.getElementById('themeToggleBtn');
    if (themeBtn) {
        themeBtn.addEventListener('click', function() {
            const isDark = document.documentElement.classList.contains('dark');
            if (isDark) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('cict-theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('cict-theme', 'dark');
            }
        });
    }

    // 2. MOBILE MENU TOGGLE
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    if (mobileBtn && mobileMenu) {
        mobileBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('active');
        });
        document.querySelectorAll('.mobile-nav-link').forEach(function(link) {
            link.addEventListener('click', function() {
                mobileMenu.classList.remove('active');
            });
        });
    }

    // 3. TESTING SANDBOX ACCORDION
    const sandboxToggle = document.getElementById('sandboxToggleBtn');
    const sandboxBody = document.getElementById('sandboxBody');
    const sandboxToggleText = document.getElementById('sandboxToggleText');
    if (sandboxToggle && sandboxBody) {
        sandboxToggle.addEventListener('click', function() {
            const isOpen = sandboxBody.classList.contains('active');
            if (isOpen) {
                sandboxBody.classList.remove('active');
                sandboxToggle.classList.remove('active');
                if (sandboxToggleText) sandboxToggleText.textContent = 'View Test Scenarios';
            } else {
                sandboxBody.classList.add('active');
                sandboxToggle.classList.add('active');
                if (sandboxToggleText) sandboxToggleText.textContent = 'Hide Test Scenarios';
            }
        });
    }

    // 4. CATALOG FILTER & LIVE SEARCH
    const tabBtns = document.querySelectorAll('.tab-btn');
    const searchInput = document.getElementById('catalogSearchInput');
    const searchClear = document.getElementById('catalogSearchClear');
    const catalogCards = document.querySelectorAll('.catalog-card');
    const catalogEmpty = document.getElementById('catalogEmpty');
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');

    let currentCategory = 'all';
    let searchQuery = '';

    function filterCatalog() {
        let visibleCount = 0;
        catalogCards.forEach(function(card) {
            const cardCat = card.getAttribute('data-category');
            const cardName = card.getAttribute('data-name') || '';
            const cardSpecs = card.getAttribute('data-specs') || '';
            const matchesCategory = (currentCategory === 'all' || cardCat === currentCategory);
            const matchesSearch = (searchQuery === '' || cardName.includes(searchQuery) || cardSpecs.includes(searchQuery));
            if (matchesCategory && matchesSearch) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        if (catalogEmpty) {
            if (visibleCount === 0) catalogEmpty.classList.remove('hidden');
            else catalogEmpty.classList.add('hidden');
        }
    }

    tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            tabBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentCategory = btn.getAttribute('data-category') || 'all';
            filterCatalog();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            searchQuery = searchInput.value.trim().toLowerCase();
            if (searchClear) {
                if (searchQuery.length > 0) searchClear.classList.remove('hidden');
                else searchClear.classList.add('hidden');
            }
            filterCatalog();
        });
    }
    if (searchClear && searchInput) {
        searchClear.addEventListener('click', function() {
            searchInput.value = '';
            searchQuery = '';
            searchClear.classList.add('hidden');
            filterCatalog();
            searchInput.focus();
        });
    }
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', function() {
            currentCategory = 'all';
            searchQuery = '';
            if (searchInput) searchInput.value = '';
            if (searchClear) searchClear.classList.add('hidden');
            tabBtns.forEach(function(b) {
                if (b.getAttribute('data-category') === 'all') b.classList.add('active');
                else b.classList.remove('active');
            });
            filterCatalog();
        });
    }

    // 5. FAQ ACCORDION
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(function(item) {
        const questionBtn = item.querySelector('.faq-question');
        if (questionBtn) {
            questionBtn.addEventListener('click', function() {
                const isActive = item.classList.contains('active');
                faqItems.forEach(i => i.classList.remove('active'));
                if (!isActive) item.classList.add('active');
            });
        }
    });

    // 6. BACK TO TOP BUTTON
    const backToTopBtn = document.getElementById('backToTopBtn');
    if (backToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 400) backToTopBtn.classList.add('visible');
            else backToTopBtn.classList.remove('visible');
        });
        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
});
</script>
@endsection
