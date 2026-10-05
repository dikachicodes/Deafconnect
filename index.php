<?php
require_once __DIR__ . '/config/bootstrap.php';
$csrf = $_SESSION['csrf'];
$adminLoggedIn = !empty($_SESSION['admin_id']);
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="DeafConnect — an accessibility-first platform for Deaf and Hard-of-Hearing communities.">
  <title>DeafConnect — Accessible Community Platform</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
  <link rel="stylesheet" href="assets/css/app.css">
  <script>
    window.DEAFCONNECT = <?= json_encode(['csrf' => $csrf, 'adminLoggedIn' => $adminLoggedIn], JSON_UNESCAPED_SLASHES) ?>;
  </script>
</head>

<body>
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <div class="app-shell">
    <header class="topbar">
      <a class="brand" href="#" onclick="showPage('home'); return false;" aria-label="DeafConnect home">
        <span class="brand-mark"><i class="ti ti-ear"></i></span>
        <span>Deaf<span>Connect</span></span>
      </a>
      <nav class="nav-links" aria-label="Main navigation">
        <button class="nav-link active" id="nav-home" onclick="showPage('home')"><i class="ti ti-home-2"></i><span>Home</span></button>
        <button class="nav-link" id="nav-video" onclick="showPage('video')"><i class="ti ti-subtitles"></i><span>Media</span></button>
        <button class="nav-link" id="nav-booking" onclick="showPage('booking')"><i class="ti ti-calendar-event"></i><span>Booking</span></button>
        <button class="nav-link" id="nav-messaging" onclick="showPage('messaging')"><i class="ti ti-message-circle"></i><span>Messages</span></button>
        <button class="nav-link" id="nav-about" onclick="showPage('about')"><i class="ti ti-info-circle"></i><span>About</span></button>
        <a class="nav-link admin-link" href="<?= $adminLoggedIn ? 'admin/' : 'login.php' ?>"><i class="ti ti-shield-check"></i><span>Admin</span></a>
      </nav>
    </header>

    <div id="alert-banner" role="alert" aria-live="assertive" aria-atomic="true">
      <span class="alert-icon" id="alert-icon" aria-hidden="true"></span>
      <span id="alert-msg"></span>
      <button class="dismiss-btn" onclick="dismissAlert()">Dismiss</button>
    </div>

    <main id="main-content">
      <section id="page-home" class="page active" aria-labelledby="home-title">
        <section class="hero">
          <div class="hero-copy">
            <span class="eyebrow"><i class="ti ti-accessible"></i> Accessibility-first by design</span>
            <h1 id="home-title">Communication should never depend on <span>hearing.</span></h1>
            <p>DeafConnect brings captioned media, visual notifications, accessible service booking and text-first support into one calm, inclusive experience for Deaf and Hard-of-Hearing communities.</p>
            <div class="hero-actions">
              <button class="btn btn-primary" onclick="showPage('video')"><i class="ti ti-player-play"></i> Explore captioned media</button>
              <button class="btn btn-secondary" onclick="showPage('booking')"><i class="ti ti-calendar-plus"></i> Book a service</button>
            </div>
            <div class="trust-row" aria-label="Platform accessibility highlights">
              <span><i class="ti ti-check"></i> Visual-first</span>
              <span><i class="ti ti-check"></i> Text-based support</span>
              <span><i class="ti ti-check"></i> Keyboard-friendly</span>
            </div>
          </div>
          <div class="hero-panel" aria-label="Accessibility summary">
            <div class="signal-card">
              <div class="signal-icon"><i class="ti ti-eye"></i></div>
              <div><span>Visual notifications</span><strong>Always on</strong></div>
            </div>
            <div class="signal-card">
              <div class="signal-icon teal"><i class="ti ti-subtitles"></i></div>
              <div><span>Captions & transcripts</span><strong>Built in</strong></div>
            </div>
            <div class="signal-card">
              <div class="signal-icon amber"><i class="ti ti-message-2"></i></div>
              <div><span>Support channel</span><strong>Text only</strong></div>
            </div>
          </div>
        </section>

        <section class="section">
          <div class="section-heading">
            <div><span class="eyebrow compact">What DeafConnect provides</span>
              <h2>Tools that remove communication barriers.</h2>
            </div>
          </div>
          <div class="feature-grid">
            <article class="feature-card">
              <div class="feature-icon blue"><i class="ti ti-subtitles"></i></div>
              <h3>Captioned media</h3>
              <p>Media is paired with synchronised caption text and a complete transcript so information remains accessible without audio.</p><button class="text-link" onclick="showPage('video')">View media <i class="ti ti-arrow-right"></i></button>
            </article>
            <article class="feature-card">
              <div class="feature-icon teal"><i class="ti ti-bell-ringing"></i></div>
              <h3>Visual alerts</h3>
              <p>Success, error and warning states are surfaced visually rather than relying on notification sounds.</p>
            </article>
            <article class="feature-card">
              <div class="feature-icon violet"><i class="ti ti-calendar-event"></i></div>
              <h3>Accessible booking</h3>
              <p>Request interpreters, audiology appointments, counselling, community sessions and relay support with simple forms.</p><button class="text-link" onclick="showPage('booking')">Book a service <i class="ti ti-arrow-right"></i></button>
            </article>
            <article class="feature-card">
              <div class="feature-icon amber"><i class="ti ti-message-circle"></i></div>
              <h3>Text-first messaging</h3>
              <p>Conversations are stored in the database and presented with clear delivery states — no call or sound required.</p><button class="text-link" onclick="showPage('messaging')">Open messages <i class="ti ti-arrow-right"></i></button>
            </article>
          </div>
        </section>

        <section class="section narrow">
          <div class="access-banner">
            <div class="access-badge"><i class="ti ti-universal-access"></i><span>WCAG 2.2 AA</span></div>
            <div>
              <h3>Accessibility is part of the product, not an afterthought.</h3>
              <p>The prototype was designed around visual-first interaction, caption quality and keyboard access. The admin area keeps a recorded checklist baseline and can export it for project documentation.</p>
            </div>
            <a class="btn btn-dark" href="<?= $adminLoggedIn ? 'admin/' : 'login.php' ?>">Open admin audit <i class="ti ti-arrow-up-right"></i></a>
          </div>
        </section>
      </section>

      <section id="page-video" class="page" aria-labelledby="video-title">
        <div class="page-header">
          <div><span class="eyebrow compact">AI media studio</span>
            <h2 id="video-title">Automatic captions</h2>
            <p>Upload a video and DeafConnect will generate a real transcript and synchronised captions for the spoken content.</p>
          </div><span class="status-chip"><i class="ti ti-sparkles"></i> DeafConnect AI transcription</span>
        </div>

        <section class="ai-upload-card" aria-labelledby="upload-title">
          <div class="ai-upload-copy">
            <div class="feature-icon blue"><i class="ti ti-wand"></i></div>
            <div>
              <span class="eyebrow compact">Accessible video workflow</span>
              <h3 id="upload-title">Generate captions from your own video.</h3>
              <p>Your video is uploaded securely to the PHP server, processed automatically, and returned with a timestamped transcript and WebVTT caption file. The AI service connection stays on the server.</p>
            </div>
          </div>
          <form id="video-form" class="video-upload-form" enctype="multipart/form-data">
            <label class="file-picker" for="video-file">
              <i class="ti ti-video-plus"></i>
              <span><strong id="video-file-name">Choose a video</strong><small>MP4, WebM, MOV, AVI or MPEG · up to 100 MB</small></span>
              <input id="video-file" name="video" type="file" accept="video/mp4,video/webm,video/quicktime,video/x-msvideo,video/mpeg" required>
            </label>
            <button class="btn btn-primary" type="submit" id="transcribe-video"><i class="ti ti-sparkles"></i> Generate AI captions</button>
          </form>
          <div id="video-status" class="video-status" role="status" aria-live="polite" aria-atomic="true">
            <i class="ti ti-info-circle"></i><span>Select a video to begin.</span>
          </div>
        </section>

        <div class="media-layout" id="video-workspace" hidden>
          <div>
            <div class="media-player real-media-player" role="region" aria-label="Uploaded captioned video">
              <video id="video-player" controls preload="metadata" playsinline aria-label="Uploaded video with generated captions">
                <track id="video-captions" kind="captions" srclang="en" label="AI-generated captions" default>
              </video>
              <div class="caption-box" id="caption-display" aria-live="polite">Captions will appear here when the video plays.</div>
            </div>
            <div class="media-controls">
              <button class="icon-btn" id="cc-btn" type="button" onclick="toggleCC()" aria-label="Toggle captions" aria-pressed="true"><i class="ti ti-subtitles"></i> CC On</button>
              <span class="live-status" id="cc-status"><i class="ti ti-circle-check"></i> AI-generated captions active</span>
              <span class="live-status video-language" id="video-language"></span>
            </div>
          </div>
          <aside class="transcript-card">
            <button class="transcript-toggle" id="ts-toggle" onclick="toggleTranscript()" aria-expanded="true" aria-controls="ts-body"><span><i class="ti ti-align-left"></i> AI transcript</span><i class="ti ti-chevron-down" id="ts-chev"></i></button>
            <div id="ts-body" class="transcript-body">
              <div id="transcript-content">
                <p class="empty-state">Your generated transcript will appear here.</p>
              </div>
            </div>
          </aside>
        </div>
      </section>

      <section id="page-booking" class="page" aria-labelledby="booking-title">
        <div class="page-header">
          <div><span class="eyebrow compact">Accessible services</span>
            <h2 id="booking-title">Book a service</h2>
            <p>Send a request and keep all communication visual and text-based.</p>
          </div><span class="status-chip"><i class="ti ti-shield-check"></i> Secure form</span>
        </div>
        <div class="booking-layout">
          <div class="booking-side">
            <div class="side-icon"><i class="ti ti-calendar-check"></i></div>
            <h3>Request support that works for you.</h3>
            <p>Choose the service you need, your preferred date and time, and any accessibility requirements. Your request is saved to the DeafConnect database for follow-up.</p>
            <div class="side-points"><span><i class="ti ti-check"></i> Visual confirmation</span><span><i class="ti ti-check"></i> No phone call required</span><span><i class="ti ti-check"></i> Accessible form labels</span></div>
          </div>
          <div class="form-card">
            <form id="booking-form" novalidate>
              <div class="form-row">
                <div class="form-group"><label for="b-first">First name <b>*</b></label><input id="b-first" name="firstname" autocomplete="given-name" placeholder="e.g. Amara" required><small class="field-err" id="err-first">First name is required.</small></div>
                <div class="form-group"><label for="b-last">Last name <b>*</b></label><input id="b-last" name="lastname" autocomplete="family-name" placeholder="e.g. Okafor" required><small class="field-err" id="err-last">Last name is required.</small></div>
              </div>
              <div class="form-group"><label for="b-email">Email address <b>*</b></label><input type="email" id="b-email" name="email" autocomplete="email" placeholder="you@example.com" required><small class="field-err" id="err-email">Enter a valid email address.</small></div>
              <div class="form-group"><label for="b-service">Service type <b>*</b></label><select id="b-service" name="service" required>
                  <option value="">Choose a service</option>
                  <option value="interpreter">BSL Interpreter</option>
                  <option value="audiology">Audiology appointment</option>
                  <option value="counselling">Deaf counselling session</option>
                  <option value="community">Community event registration</option>
                  <option value="relay">Relay service setup</option>
                </select><small class="field-err" id="err-service">Choose a service type.</small></div>
              <div class="form-row">
                <div class="form-group"><label for="b-date">Preferred date <b>*</b></label><input type="date" id="b-date" name="date" required><small class="field-err" id="err-date">Choose a preferred date.</small></div>
                <div class="form-group"><label for="b-time">Preferred time <b>*</b></label><select id="b-time" name="time" required>
                    <option value="">Choose a time</option>
                    <option>09:00 AM</option>
                    <option>10:00 AM</option>
                    <option>11:00 AM</option>
                    <option>12:00 PM</option>
                    <option>01:00 PM</option>
                    <option>02:00 PM</option>
                    <option>03:00 PM</option>
                    <option>04:00 PM</option>
                  </select><small class="field-err" id="err-time">Choose a preferred time.</small></div>
              </div>
              <div class="form-group"><label for="b-notes">Additional notes <span>(optional)</span></label><textarea id="b-notes" name="notes" rows="4" placeholder="Tell us about any accessibility needs or special requirements."></textarea></div>
              <button class="btn btn-primary full" type="submit" id="booking-submit"><i class="ti ti-send"></i> Submit booking request</button>
            </form>
          </div>
        </div>
      </section>

      <section id="page-messaging" class="page" aria-labelledby="msg-title">
        <div class="page-header">
          <div><span class="eyebrow compact">Text-first support</span>
            <h2 id="msg-title">Messages</h2>
            <p>Your conversations are saved so you can return to them later in the same browser session.</p>
          </div><span class="status-chip success"><span class="dot"></span> Online</span>
        </div>
        <div class="msg-layout">
          <aside class="contacts-card">
            <div class="contacts-head"><strong>Contacts</strong><button class="small-icon" onclick="loadContacts()" aria-label="Refresh contacts"><i class="ti ti-refresh"></i></button></div>
            <div id="contacts-list" class="contacts-list">
              <div class="loading-line"></div>
              <div class="loading-line"></div>
              <div class="loading-line"></div>
            </div>
          </aside>
          <section class="chat-card" aria-label="Conversation">
            <div class="chat-head">
              <div class="avatar large" id="th-av">DC</div>
              <div><strong id="th-name">DeafConnect Support</strong><span><span class="dot"></span> Text-based support only</span></div>
            </div>
            <div id="msg-thread" class="msg-thread" aria-live="polite" aria-relevant="additions"></div>
            <div class="chat-compose"><input id="msg-name" placeholder="Your name" aria-label="Your name"><input id="msg-input" placeholder="Type a message…" aria-label="Message input"><button class="icon-btn primary" id="send-msg"><i class="ti ti-send"></i> Send</button></div>
          </section>
        </div>
      </section>

      <section id="page-about" class="page" aria-labelledby="about-title">
        <div class="about-hero">
          <div><span class="eyebrow compact">Research prototype</span>
            <h2 id="about-title">About DeafConnect</h2>
            <p>DeafConnect demonstrates how an accessibility-first web product can make communication, content and support easier to access for Deaf and Hard-of-Hearing users.</p>
          </div>
        </div>
        <section class="section">
          <div class="section-heading">
            <div><span class="eyebrow compact">Design principles</span>
              <h2>Built around real accessibility needs.</h2>
            </div>
          </div>
          <div class="principles-grid">
            <article><i class="ti ti-eye"></i>
              <h3>Visual-first communication</h3>
              <p>System states are communicated visually. No core interaction depends on an audio cue.</p>
            </article>
            <article><i class="ti ti-subtitles"></i>
              <h3>Caption quality</h3>
              <p>Captions are treated as primary content: synchronised, readable and supported by a full transcript.</p>
            </article>
            <article><i class="ti ti-keyboard"></i>
              <h3>Keyboard access</h3>
              <p>Navigation, forms, media controls and messaging are designed for keyboard users.</p>
            </article>
            <article><i class="ti ti-chart-dots-3"></i>
              <h3>Task evaluation</h3>
              <p>The admin area provides a recorded accessibility checklist that can be exported for documentation.</p>
            </article>
          </div>
        </section>
        <section class="section">
          <div class="section-heading">
            <div><span class="eyebrow compact">Theoretical grounding</span>
              <h2>Why the product is designed this way.</h2>
            </div>
          </div>
          <div class="theory-grid">
            <article>
              <h3>Social Model of Disability</h3>
              <p>Barriers to participation can be created by environments and design choices, so the platform focuses on removing avoidable communication barriers.</p>
            </article>
            <article>
              <h3>Universal Design</h3>
              <p>Designing for a broad range of users improves usability and avoids making accessibility a separate mode.</p>
            </article>
            <article>
              <h3>Technology Acceptance Model</h3>
              <p>Clear, useful and easy-to-use interfaces reduce friction and make users more likely to adopt a digital service.</p>
            </article>
          </div>
        </section>
      </section>
    </main>

    <footer><strong>DeafConnect</strong><span>Accessible Community &amp; Media Platform</span><span>Visual-first • Text-first • Keyboard-friendly</span></footer>
  </div>
  <script src="assets/js/app.js"></script>
</body>

</html>