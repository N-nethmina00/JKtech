<?php
// index.php - JDM Japanese Reconditioned Auto Parts Homepage
require_once __DIR__ . '/includes/functions.php';

$flash = getFlash();
$brands = fetchBrands();
$categories = fetchCategories();
$allProducts = fetchAllProducts();

// Get featured stock (top 6 items)
$featuredProducts = array_filter($allProducts, fn($p) => !empty($p['is_featured']));
if (empty($featuredProducts)) $featuredProducts = array_slice($allProducts, 0, 6);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>JKtech.LK | Genuine Japanese Reconditioned Auto Parts Sri Lanka</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/home.css">
</head>
<body>

  <!-- Navigation Header -->
  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <!-- Flash Notification (if any) -->
  <?php if ($flash): ?>
    <div class="container" style="margin-top: 20px;">
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
        <span><?= htmlspecialchars($flash['message']) ?></span>
      </div>
    </div>
  <?php endif; ?>

  <!-- Hero Section with Interactive Particle Canvas & Dual Ambient Lighting -->
  <section class="hero-section">
    <!-- Interactive Particle Background Canvas -->
    <canvas id="heroParticleCanvas"></canvas>

    <div class="container hero-grid">
      <!-- Left Hero Text -->
      <div class="hero-content">
        <div class="hero-badge">
          <span class="dot"></span>
          <span>JK TECH MOTORS • JAPAN DIRECT IMPORT HUB</span>
        </div>
        <h1 class="hero-title">
          GENUINE RECONDITIONED <br>
          <span class="highlight">SPARES DIRECTLY IMPORTED</span> <br>
          FROM JAPAN
        </h1>
        <p class="hero-desc">
          Low-mileage engines, gearboxes, suspension kits, and braking assemblies sourced from Yokohama, Nagoya &amp; Osaka auctions. Fully dyno-inspected, compression-verified, and guaranteed with a 90-day replacement warranty.
        </p>
        <div class="hero-actions">
          <a href="products.php" class="btn btn-primary btn-lg">
            <span>Explore Parts Directory</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/>
            </svg>
          </a>
          <a href="#assemblies" class="btn btn-secondary btn-lg">View Assemblies</a>
        </div>

        <!-- Sleek Quick Chassis & Engine Search Bar -->
        <div class="hero-quick-search-box">
          <form action="products.php" method="GET" class="quick-search-form">
            <div class="search-input-wrap">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input type="text" name="search" placeholder="Quick search engine code or chassis (e.g. 1NZ-FE, K20A, FD2, Brembo)..." autocomplete="off">
              <button type="submit" class="btn btn-primary btn-sm">Find Part</button>
            </div>
          </form>
          <div class="quick-pills">
            <span class="quick-label">Trending:</span>
            <a href="products.php?search=2JZ">2JZ-GTE</a>
            <a href="products.php?search=K20A">K20A Type-R</a>
            <a href="products.php?search=1NZ-FE">1NZ-FE</a>
            <a href="products.php?search=Brembo">Brembo</a>
            <a href="products.php?search=Gearbox">Gearbox</a>
          </div>
        </div>
      </div>

      <!-- Right Column: Animated Japan -> Sri Lanka High-Tech Logistics Route Map -->
      <div class="border-runner-wrap tilt-card" data-tilt>
        <div class="border-runner-content route-map-card hud-bracket">
          <!-- Card Header & Live Telemetry Ping -->
          <div class="map-card-header">
            <div class="live-radar-tag">
              <span class="radar-beacon-dot"></span>
              <span class="live-text">DIRECT IMPORT RADAR</span>
              <span class="flight-no">LIVE AIRWAY #JDM-782</span>
            </div>
            <div class="route-title-wrap">
              <h3 class="route-main-title">
                JAPAN <span class="route-arrow">➔</span> SRI LANKA
              </h3>
              <span class="route-sub-title">Yokohama / Nagoya Auto Hub ➔ Colombo Port &amp; BIA Cargo Terminal</span>
            </div>
          </div>

          <!-- Interactive High-Tech Radar Map Viewport -->
          <div class="radar-map-viewport">
            <svg class="radar-map-svg" viewBox="0 0 540 330" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
              <defs>
                <!-- Neon Cyan Glow Filter -->
                <filter id="mapCyanGlow" x="-25%" y="-25%" width="150%" height="150%">
                  <feGaussianBlur stdDeviation="3.5" result="blur"/>
                  <feMerge>
                    <feMergeNode in="blur"/>
                    <feMergeNode in="SourceGraphic"/>
                  </feMerge>
                </filter>

                <!-- Hyper Orange Glow Filter -->
                <filter id="mapOrangeGlow" x="-25%" y="-25%" width="150%" height="150%">
                  <feGaussianBlur stdDeviation="3.5" result="blur"/>
                  <feMerge>
                    <feMergeNode in="blur"/>
                    <feMergeNode in="SourceGraphic"/>
                  </feMerge>
                </filter>

                <!-- Flight Route Gradient (Orange Origin -> Cyan Destination) -->
                <linearGradient id="flightArcGrad" x1="100%" y1="0%" x2="0%" y2="100%">
                  <stop offset="0%" stop-color="#FF4D2D"/>
                  <stop offset="45%" stop-color="#FFA800"/>
                  <stop offset="75%" stop-color="#06B6D4"/>
                  <stop offset="100%" stop-color="#00F0FF"/>
                </linearGradient>

                <!-- Jet Stream Trail Gradient -->
                <linearGradient id="jetTrailGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                  <stop offset="0%" stop-color="transparent"/>
                  <stop offset="60%" stop-color="rgba(6, 182, 212, 0.4)"/>
                  <stop offset="100%" stop-color="#FF4D2D"/>
                </linearGradient>
              </defs>

              <!-- Radar Map Deep Dark Ocean Background -->
              <rect width="540" height="330" rx="8" fill="#080e1a"/>

              <!-- Concentric Radar Range Circles -->
              <circle cx="300" cy="180" r="75" fill="none" stroke="rgba(6, 182, 212, 0.08)" stroke-width="1" stroke-dasharray="3 3"/>
              <circle cx="300" cy="180" r="150" fill="none" stroke="rgba(6, 182, 212, 0.06)" stroke-width="1" stroke-dasharray="3 3"/>
              <circle cx="300" cy="180" r="225" fill="none" stroke="rgba(6, 182, 212, 0.05)" stroke-width="1" stroke-dasharray="3 3"/>

              <!-- Coordinate Grids (Latitude & Longitude) -->
              <line x1="20" y1="65" x2="520" y2="65" stroke="rgba(255,255,255,0.035)" stroke-width="1"/>
              <line x1="20" y1="130" x2="520" y2="130" stroke="rgba(255,255,255,0.035)" stroke-width="1"/>
              <line x1="20" y1="195" x2="520" y2="195" stroke="rgba(255,255,255,0.035)" stroke-width="1"/>
              <line x1="20" y1="260" x2="520" y2="260" stroke="rgba(255,255,255,0.035)" stroke-width="1"/>

              <line x1="100" y1="20" x2="100" y2="310" stroke="rgba(255,255,255,0.035)" stroke-width="1"/>
              <line x1="190" y1="20" x2="190" y2="310" stroke="rgba(255,255,255,0.035)" stroke-width="1"/>
              <line x1="280" y1="20" x2="280" y2="310" stroke="rgba(255,255,255,0.035)" stroke-width="1"/>
              <line x1="370" y1="20" x2="370" y2="310" stroke="rgba(255,255,255,0.035)" stroke-width="1"/>
              <line x1="460" y1="20" x2="460" y2="310" stroke="rgba(255,255,255,0.035)" stroke-width="1"/>

              <!-- Coordinate Degree Marks -->
              <text x="24" y="68" fill="rgba(6,182,212,0.3)" font-size="7" font-family="monospace">40°N</text>
              <text x="24" y="133" fill="rgba(6,182,212,0.3)" font-size="7" font-family="monospace">25°N</text>
              <text x="24" y="198" fill="rgba(6,182,212,0.3)" font-size="7" font-family="monospace">15°N</text>
              <text x="24" y="263" fill="rgba(6,182,212,0.3)" font-size="7" font-family="monospace">05°N</text>

              <text x="96" y="322" fill="rgba(6,182,212,0.3)" font-size="7" font-family="monospace">70°E</text>
              <text x="186" y="322" fill="rgba(6,182,212,0.3)" font-size="7" font-family="monospace">85°E</text>
              <text x="276" y="322" fill="rgba(6,182,212,0.3)" font-size="7" font-family="monospace">105°E</text>
              <text x="366" y="322" fill="rgba(6,182,212,0.3)" font-size="7" font-family="monospace">125°E</text>
              <text x="456" y="322" fill="rgba(6,182,212,0.3)" font-size="7" font-family="monospace">140°E</text>

              <!-- Stylized Cyber Continents & Landmasses -->
              <g class="map-continents" fill="rgba(18, 28, 48, 0.78)" stroke="rgba(6, 182, 212, 0.3)" stroke-width="1.1">
                <!-- Mainland East Asia & Indochina -->
                <path d="M 120,70 L 170,40 L 250,45 L 310,35 L 365,42 L 372,78 L 350,88 L 330,84 L 335,114 L 352,128 L 338,160 L 320,166 L 310,192 L 318,220 L 302,232 L 288,222 L 282,194 L 268,182 L 256,155 L 226,155 L 206,128 L 138,128 Z"/>
                
                <!-- Korea -->
                <path d="M 368,85 L 380,88 L 376,110 L 364,105 Z"/>

                <!-- Japan Archipelago (Highlighted in JDM Accent) -->
                <!-- Honshu -->
                <path d="M 412,104 C 426,94 440,84 456,74 C 462,77 458,86 446,96 C 436,105 422,112 412,104 Z" fill="rgba(255, 77, 45, 0.18)" stroke="#FF4D2D" stroke-width="1.5"/>
                <!-- Hokkaido -->
                <path d="M 454,54 C 466,46 476,52 472,64 C 460,66 452,59 454,54 Z" fill="rgba(255, 77, 45, 0.18)" stroke="#FF4D2D" stroke-width="1.2"/>
                <!-- Kyushu & Shikoku -->
                <path d="M 396,114 C 408,111 408,121 398,124 C 391,122 393,116 396,114 Z" fill="rgba(255, 77, 45, 0.18)" stroke="#FF4D2D" stroke-width="1.2"/>

                <!-- Taiwan -->
                <path d="M 352,160 L 360,163 L 356,176 L 348,173 Z"/>

                <!-- Philippines -->
                <path d="M 378,175 L 390,178 L 385,202 L 374,198 Z M 382,214 L 396,218 L 392,238 L 378,234 Z"/>

                <!-- Malay Peninsula & Singapore -->
                <path d="M 280,195 L 288,220 L 280,245 L 272,236 Z"/>

                <!-- Indonesia (Sumatra & Java) -->
                <path d="M 230,235 L 265,258 L 275,282 L 248,274 Z"/>
                <path d="M 280,286 L 334,295 L 330,302 L 278,295 Z"/>

                <!-- Indian Subcontinent -->
                <path d="M 120,128 L 206,128 C 194,162 180,200 162,238 L 148,252 L 136,238 C 122,204 112,168 120,128 Z"/>

                <!-- Sri Lanka (Teardrop Island Highlighted in Neon Cyan) -->
                <path d="M 158,252 C 166,254 170,263 166,272 C 162,278 155,276 153,269 C 152,261 154,254 158,252 Z" fill="rgba(6, 182, 212, 0.35)" stroke="#00F0FF" stroke-width="1.8" filter="url(#mapCyanGlow)"/>
              </g>

              <!-- Secondary Ocean Shipping Route (Yokohama Port ➔ Malacca Strait ➔ Colombo Port) -->
              <path d="M 440,105 C 410,150 365,200 278,245 C 230,260 190,264 158,266" fill="none" stroke="rgba(6, 182, 212, 0.25)" stroke-width="1.5" stroke-dasharray="3 4"/>
              
              <!-- Container Cargo Vessel in Maritime Corridor -->
              <g transform="translate(242, 253) scale(0.9)">
                <title>Direct Sea Vessel Route (40ft High-Cube Containers)</title>
                <path d="M-8,2 L-6,5 L6,5 L8,2 L5,2 L5,0 L2,0 L2,2 L-1,2 L-1,-1 L-3,-1 L-3,2 Z" fill="#06B6D4"/>
                <rect x="-0.5" y="-0.5" width="3" height="1.5" fill="#FFA800"/>
                <circle cx="0" cy="7" r="2" fill="rgba(6, 182, 212, 0.4)"/>
              </g>

              <!-- PRIMARY EXPRESS AIR ROUTE TRAJECTORY -->
              <!-- Underlying Wide Glow Beam -->
              <path d="M 440,95 C 365,125 250,175 158,260" fill="none" stroke="rgba(6, 182, 212, 0.18)" stroke-width="8" filter="url(#mapCyanGlow)"/>

              <!-- Base Gradient Trajectory Arc -->
              <path id="flightArc" d="M 440,95 C 365,125 250,175 158,260" fill="none" stroke="url(#flightArcGrad)" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="5 4"/>

              <!-- High-Speed Continuous Laser Runner along the Route -->
              <path d="M 440,95 C 365,125 250,175 158,260" fill="none" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" class="laser-runner-flight"/>

              <!-- Mid-Flight Telemetry Callout Box -->
              <g transform="translate(292, 136)">
                <line x1="0" y1="0" x2="-20" y2="-18" stroke="rgba(6, 182, 212, 0.4)" stroke-width="1" stroke-dasharray="2 2"/>
                <g transform="translate(-142, -32)">
                  <rect width="140" height="20" rx="3" fill="rgba(8, 14, 26, 0.9)" stroke="rgba(6, 182, 212, 0.35)" stroke-width="1"/>
                  <text x="8" y="13" font-family="'Rajdhani', monospace" font-size="8.5" font-weight="700" fill="#00F0FF">
                    AIRWAY #JDM-782 • ALT 38,000FT
                  </text>
                </g>
              </g>

              <!-- ORIGIN WAYPOINT: Yokohama / Tokyo (Japan) -->
              <g transform="translate(440, 95)">
                <!-- Pulsing Orange Sonar Wave Rings -->
                <circle r="5" stroke="#FF4D2D" stroke-width="1.5" fill="none" class="pulse-ring-orange-1"/>
                <circle r="5" stroke="#FF4D2D" stroke-width="1.5" fill="none" class="pulse-ring-orange-2"/>
                <circle r="5" fill="#FF4D2D" filter="url(#mapOrangeGlow)"/>
                <circle r="2" fill="#FFFFFF"/>
                <!-- Origin Node Label -->
                <g transform="translate(-8, -26)">
                  <rect width="94" height="19" rx="3" fill="rgba(12, 19, 32, 0.94)" stroke="#FF4D2D" stroke-width="1"/>
                  <text x="6" y="13" font-family="'Rajdhani', sans-serif" font-size="9" font-weight="800" fill="#FFFFFF">
                    🇯🇵 YOKOHAMA (JPN)
                  </text>
                </g>
              </g>

              <!-- DESTINATION WAYPOINT: Colombo (Sri Lanka) -->
              <g transform="translate(158, 260)">
                <!-- Pulsing Cyan Radar Rings & Crosshair Reticle -->
                <circle r="6" stroke="#06B6D4" stroke-width="1.5" fill="none" class="pulse-ring-cyan-1"/>
                <circle r="6" stroke="#06B6D4" stroke-width="1.5" fill="none" class="pulse-ring-cyan-2"/>
                <circle r="12" stroke="#00F0FF" stroke-width="1" fill="none" stroke-dasharray="3 3" class="spin-reticle"/>
                <circle r="5" fill="#00F0FF" filter="url(#mapCyanGlow)"/>
                <circle r="2" fill="#FFFFFF"/>
                <!-- Destination Node Label -->
                <g transform="translate(-98, 12)">
                  <rect width="92" height="19" rx="3" fill="rgba(12, 19, 32, 0.94)" stroke="#06B6D4" stroke-width="1"/>
                  <text x="6" y="13" font-family="'Rajdhani', sans-serif" font-size="9" font-weight="800" fill="#00F0FF">
                    🇱🇰 COLOMBO (LKA)
                  </text>
                </g>
              </g>

              <!-- ANIMATED SMALL CARGO AIRPLANE TRAVELING FROM JAPAN TO SRI LANKA -->
              <g class="cargo-plane-flight">
                <g transform="scale(0.85)">
                  <!-- Jet Afterburner Flame (Trailing behind plane) -->
                  <ellipse cx="-16" cy="0" rx="6" ry="2.5" fill="#FF4D2D" opacity="0.9">
                    <animate attributeName="rx" values="4;9;4" dur="0.22s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="0.75;1;0.75" dur="0.22s" repeatCount="indefinite"/>
                  </ellipse>
                  <ellipse cx="-12" cy="0" rx="3.5" ry="1.5" fill="#FFA800"/>

                  <!-- Outer Cyan Glow Hull -->
                  <path d="M 17,0 L 4,-3 L -4,-15 L -7,-15 L -4,-3 L -12,-3 L -15,-7 L -17,-7 L -15,0 L -17,7 L -15,7 L -12,3 L -4,3 L -7,15 L -4,15 L 4,3 Z" fill="rgba(6, 182, 212, 0.4)" filter="url(#mapCyanGlow)"/>

                  <!-- Aircraft Fuselage & Swept Wings (Centered facing +X) -->
                  <path d="M 17,0 L 4,-2.5 L -4,-14 L -7,-14 L -4,-2.5 L -12,-2.5 L -15,-6 L -17,-6 L -15,0 L -17,6 L -15,6 L -12,2.5 L -4,2.5 L -7,14 L -4,14 L 4,2.5 Z" fill="#0c1728" stroke="#00F0FF" stroke-width="1.2"/>

                  <!-- Wingtip Navigation Strobes (Green Starboard, Red Port) -->
                  <circle cx="-6" cy="-14" r="1.5" fill="#00FF66"/>
                  <circle cx="-6" cy="14" r="1.5" fill="#FF0055"/>
                  <circle cx="16" cy="0" r="1.5" fill="#FFFFFF"/>

                  <!-- Cockpit Windshield -->
                  <path d="M 10,-1 L 14,0 L 10,1 Z" fill="#FFFFFF"/>
                </g>

                <!-- Hardware-Accelerated Path Traversal -->
                <animateMotion dur="7s" repeatCount="indefinite" rotate="auto" path="M 440,95 C 365,125 250,175 158,260">
                  <mpath href="#flightArc" xlink:href="#flightArc"/>
                </animateMotion>

                <!-- Seamless Flight Loop Opacity Fade -->
                <animate attributeName="opacity" values="0; 1; 1; 1; 0" keyTimes="0; 0.05; 0.90; 0.96; 1" dur="7s" repeatCount="indefinite"/>
              </g>
            </svg>

            <!-- Radar Scanner Sweep Cone Overlay -->
            <div class="radar-scanner-sweep"></div>
          </div>

          <!-- Bottom Telemetry HUD Matrix -->
          <div class="map-telemetry-grid">
            <div class="telemetry-item">
              <span class="tele-label">ORIGIN DISPATCH</span>
              <span class="tele-val"><span class="flag-icon">🇯🇵</span> YOKOHAMA</span>
              <span class="tele-sub">JEVIC Certified Auctions</span>
            </div>
            <div class="telemetry-item">
              <span class="tele-label">TRANSIT DISTANCE</span>
              <span class="tele-val highlight-cyan">6,840 KM</span>
              <span class="tele-sub">Direct Air &amp; Ocean Corridor</span>
            </div>
            <div class="telemetry-item">
              <span class="tele-label">DESTINATION PORT</span>
              <span class="tele-val"><span class="flag-icon">🇱🇰</span> COLOMBO</span>
              <span class="tele-sub">Bonded Yard &amp; Express Delivery</span>
            </div>
          </div>

          <!-- Live Shipping Progress Bar & Dispatch Status -->
          <div class="route-status-footer">
            <div class="progress-track">
              <div class="progress-bar-animated"></div>
            </div>
            <div class="status-info-row">
              <span class="status-badge"><span class="badge-dot"></span> AIR CARGO: 48-72 HRS</span>
              <span class="status-badge"><span class="badge-dot ocean"></span> SEA FREIGHT: 12-14 DAYS</span>
              <a href="products.php" class="btn-track-parts">Explore In-Stock Spares ➔</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Animated Metrics / Stats Counter Bar -->
  <section class="stats-bar">
    <div class="container stats-grid">
      <div class="stat-item reveal-on-scroll">
        <h3 id="statCounter1">12,500<span class="accent">+</span></h3>
        <p>Spares In Stock</p>
      </div>
      <div class="stat-item reveal-on-scroll">
        <h3 id="statCounter2">3,200<span class="accent">+</span></h3>
        <p>Engines Sold in SL</p>
      </div>
      <div class="stat-item reveal-on-scroll">
        <h3 id="statCounter3">4.9<span class="accent">/5</span></h3>
        <p>Garages Trust Rating</p>
      </div>
      <div class="stat-item reveal-on-scroll">
        <h3 id="statCounter4">48<span class="accent">h</span></h3>
        <p>Islandwide Dispatch</p>
      </div>
    </div>
  </section>

  <!-- Select Critical Assemblies & Component Groups -->
  <section class="section-pad" id="assemblies">
    <div class="container">
      <div class="section-header reveal-on-scroll">
        <span class="section-tag">SELECT CRITICAL ASSEMBLIES &amp; COMPONENT GROUPS</span>
        <h2 class="section-title">ENGINE, TRANSMISSION &amp; CHASSIS CATEGORIES</h2>
        <p class="section-subtitle">Grade-A authenticated Japanese mechanical assemblies tested and ready for immediate drop-in installation.</p>
      </div>

      <div class="assembly-grid">
        <?php foreach ($categories as $cat): ?>
          <a href="products.php?category_id=<?= $cat['id'] ?>" class="assembly-card tilt-card hud-bracket reveal-on-scroll">
            <div class="icon-box">
              <?php if ($cat['slug'] === 'engine-assemblies'): ?>
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="4" y="6" width="16" height="12" rx="2"/><path d="M2 10h2M20 10h2M10 2v4M14 2v4M7 18v3M17 18v3"/></svg>
              <?php elseif ($cat['slug'] === 'transmissions'): ?>
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
              <?php elseif ($cat['slug'] === 'suspension'): ?>
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2v4M8 6h8M9 6v12M15 6v12M8 18h8M12 18v4"/></svg>
              <?php elseif ($cat['slug'] === 'braking'): ?>
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/></svg>
              <?php elseif ($cat['slug'] === 'electrical'): ?>
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="4" y="4" width="16" height="16" rx="2"/><circle cx="9" cy="9" r="2"/><path d="M15 9h.01M9 15h6"/></svg>
              <?php elseif ($cat['slug'] === 'turbo-intake'): ?>
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="8"/><path d="M12 12l4-4M12 12l-4 4M12 12l4 4M12 12l-4-4"/></svg>
              <?php else: ?>
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
              <?php endif; ?>
            </div>
            <h4><?= htmlspecialchars($cat['name']) ?></h4>
            <p><?= htmlspecialchars($cat['description']) ?></p>
            <span class="card-link">Explore Group &rarr;</span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Featured Grade-A Reconditioned JDM Stock with Filter Tabs -->
  <section class="section-pad" style="background: #090e1a; border-top: 1px solid var(--border-subtle); border-bottom: 1px solid var(--border-subtle);">
    <div class="container">
      <div class="section-header reveal-on-scroll">
        <span class="section-tag">DIRECT YARD RECONDITIONED ARRIVALS</span>
        <h2 class="section-title">FEATURED GRADE-A RECONDITIONED JDM STOCK</h2>
        <p class="section-subtitle">Verified cylinder compression logs, zero sludge guarantee, and comprehensive bench testing reports.</p>
      </div>

      <!-- Interactive Category Filter Tabs -->
      <div class="featured-tabs-wrapper reveal-on-scroll">
        <button type="button" class="featured-tab-btn active" data-filter="all">All Parts</button>
        <button type="button" class="featured-tab-btn" data-filter="1">Engines</button>
        <button type="button" class="featured-tab-btn" data-filter="2">Gearboxes</button>
        <button type="button" class="featured-tab-btn" data-filter="3">Suspension</button>
        <button type="button" class="featured-tab-btn" data-filter="4">Brakes</button>
      </div>

      <div class="products-grid">
        <?php foreach ($featuredProducts as $item): ?>
          <div class="product-card featured-product-card tilt-card hud-bracket reveal-on-scroll" data-category="<?= $item['category_id'] ?>">
            <div class="card-thumb">
              <img src="<?= htmlspecialchars(getProductImageUrl($item['image_url'])) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
              <span class="card-badge-top badge badge-grade"><?= htmlspecialchars($item['grade'] ?? 'Grade A') ?></span>
            </div>
            <div class="card-body">
              <span class="card-category"><?= htmlspecialchars($item['brand_name'] ?? 'JDM OEM') ?> • <?= htmlspecialchars($item['category_name'] ?? 'Assembly') ?></span>
              <h3 class="card-title">
                <a href="product-details.php?id=<?= $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></a>
              </h3>
              <div class="card-meta">
                <p><strong>Fits:</strong> <?= htmlspecialchars($item['model_compatibility']) ?></p>
                <p style="color:var(--neon-emerald); margin-top:6px; font-weight:700; display:flex; align-items:center;">
                  <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:#10b981; box-shadow:0 0 8px #10b981; margin-right:8px; animation:pulseGlow 1.5s infinite;"></span>
                  <span><?= htmlspecialchars($item['compression']) ?></span>
                </p>
              </div>
              <div class="card-footer">
                <span class="card-price"><?= formatCurrency($item['price']) ?></span>
                <div class="flex gap-2">
                  <button class="btn btn-primary btn-sm btn-quick-add" data-product-id="<?= $item['id'] ?>">Add to Cart</button>
                  <a href="product-details.php?id=<?= $item['id'] ?>" class="btn btn-outline btn-sm">Details</a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Why Sri Lanka Trusts JKtech Reconditioned Parts (About Section) -->
  <section class="section-pad" id="about">
    <div class="container">
      <div class="section-header reveal-on-scroll">
        <span class="section-tag">UNCOMPROMISING QUALITY</span>
        <h2 class="section-title">WHY SRI LANKA TRUSTS JKTECH RECONDITIONED PARTS</h2>
        <p class="section-subtitle">Bridging premier Japanese auto dismantling yards with Sri Lankan garages and car enthusiasts.</p>
      </div>

      <div class="trust-grid">
        <div class="trust-card tilt-card reveal-on-scroll">
          <div class="step-num">01</div>
          <h4>Direct Auction Sourcing</h4>
          <p>Hand-selected at USS Tokyo, JAA, and CAA auto auctions. Sourced only from low-mileage, impeccably maintained donor chassis.</p>
        </div>
        <div class="trust-card tilt-card reveal-on-scroll">
          <div class="step-num">02</div>
          <h4>Compression &amp; Dyno Bench Tested</h4>
          <p>Dry and wet cylinder compression tested on our Yokohama test benches. Oil pressure logs and leak-down inspections recorded.</p>
        </div>
        <div class="trust-card tilt-card reveal-on-scroll">
          <div class="step-num">03</div>
          <h4>JEVIC Certified Mileage</h4>
          <p>Genuine Japanese documentation, engine serial traceability, and official export certificates provided with each component.</p>
        </div>
        <div class="trust-card tilt-card reveal-on-scroll">
          <div class="step-num">04</div>
          <h4>90-Day Full Replacement Warranty</h4>
          <p>Complete confidence for garages and vehicle owners. Any internal defect within 90 days is replaced or fully refunded.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Yokohama Yard to Sri Lankan Roads Timeline with Traveling Laser Rail & Sonar Radar -->
  <section class="section-pad journey-section" id="journey">
    <div class="container">
      <div class="section-header reveal-on-scroll">
        <span class="section-tag">DIRECT IMPORT LOGISTICS</span>
        <h2 class="section-title">YOKOHAMA YARD TO SRI LANKAN ROADS</h2>
        <p class="section-subtitle">Our seamless maritime supply chain from Japanese dismantling bays to your garage hoist.</p>
      </div>

      <div class="timeline-track">
        <div class="timeline-node reveal-on-scroll">
          <div class="timeline-dot-wrap">
            <span class="sonar-wave"></span>
            <div class="timeline-dot">01</div>
          </div>
          <h5>Yokohama Yard Sourcing</h5>
          <p>Vehicles purchased and dismantled in Kanagawa, Chiba &amp; Nagoya yards with verified low KM.</p>
        </div>
        <div class="timeline-node reveal-on-scroll">
          <div class="timeline-dot-wrap">
            <span class="sonar-wave"></span>
            <div class="timeline-dot">02</div>
          </div>
          <h5>Bench &amp; Dyno Testing</h5>
          <p>Compression, gearbox hydraulic pressure, and electronics bench-tested before palletizing.</p>
        </div>
        <div class="timeline-node reveal-on-scroll">
          <div class="timeline-dot-wrap">
            <span class="sonar-wave"></span>
            <div class="timeline-dot">03</div>
          </div>
          <h5>Container Ocean Freight</h5>
          <p>Moisture-sealed shipping containers from Yokohama Port directly to Colombo Harbour.</p>
        </div>
        <div class="timeline-node reveal-on-scroll">
          <div class="timeline-dot-wrap">
            <span class="sonar-wave orange"></span>
            <div class="timeline-dot orange">04</div>
          </div>
          <h5>Direct Garage Delivery</h5>
          <p>Rapid customs clearance and 48-hour delivery directly to workshops across Sri Lanka.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Garage & Owner Reviews -->
  <section class="section-pad" id="testimonials">
    <div class="container">
      <div class="section-header reveal-on-scroll">
        <span class="section-tag">GARAGE &amp; WORKSHOP REVIEWS</span>
        <h2 class="section-title">READ AS TOLD BY GARAGES &amp; OWNERS</h2>
        <p class="section-subtitle">Real experiences from professional mechanics, master tuners, and car owners.</p>
      </div>

      <div class="reviews-grid">
        <div class="review-card tilt-card reveal-on-scroll">
          <div class="review-stars">★★★★★</div>
          <p class="review-text">
            "Ordered a Toyota 1NZ-FE engine for a customer's Premio. Compression was exact at 12.8 bar as advertised, completely clean under the rocker cover with zero sludge. Fired up on first crank!"
          </p>
          <div class="reviewer-info">
            <div class="reviewer-avatar">SP</div>
            <div>
              <div class="reviewer-name">Sampath Auto Engineering</div>
              <div class="reviewer-role">Master Mechanic • Kandy</div>
            </div>
          </div>
        </div>

        <div class="review-card tilt-card reveal-on-scroll">
          <div class="review-stars">★★★★★</div>
          <p class="review-text">
            "Finding a genuine Honda K20A Type-R engine in Sri Lanka that hasn't been abused locally is tough. JK Tech Motors delivered a pristine Osaka long block with original documentation. Incredible VTEC response!"
          </p>
          <div class="reviewer-info">
            <div class="reviewer-avatar">NR</div>
            <div>
              <div class="reviewer-name">Niroshan Ranasinghe</div>
              <div class="reviewer-role">Civic EK4 Owner • Colombo</div>
            </div>
          </div>
        </div>

        <div class="review-card tilt-card reveal-on-scroll">
          <div class="review-stars">★★★★★</div>
          <p class="review-text">
            "Replaced the Tiida CVT gearbox with one from JK Tech Motors. Smooth shifts, no whining noise, torque converter included. Customer is thrilled with the result and warranty coverage."
          </p>
          <div class="reviewer-info">
            <div class="reviewer-avatar">DM</div>
            <div>
              <div class="reviewer-name">Dammika Motors</div>
              <div class="reviewer-role">Workshop Manager • Gampaha</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Part Inquiry CTA Banner (Contact Section) -->
      <div class="cta-banner reveal-on-scroll" id="contact">
        <div class="cta-content">
          <h3>NEED A SPECIFIC ENGINE OR CHASSIS PART?</h3>
          <p>Looking for a rare JDM chassis code, turbo manifold, or differential? Our Yokohama team can source it on special order.</p>
        </div>
        <a href="https://wa.me/94778376481?text=Hi%20AUTO%20PARTS" target="_blank" class="btn btn-primary btn-lg">
          <span>Request Custom Import</span>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/>
          </svg>
        </a>
      </div>
    </div>
  </section>

  <!-- Global Footer -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
