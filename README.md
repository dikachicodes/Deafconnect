# DeafConnect — PHP/MySQL Backend Integration

This version converts the original single-file DeafConnect research prototype into a small PHP + MySQL web application while preserving its core ideas: captioned media, visual-first notifications, accessible service booking, text-based messaging and an admin accessibility panel.

## Main changes

- Split the original page into `index.php`, `assets/css/app.css` and `assets/js/app.js`.
- Booking requests are now validated server-side and stored in MySQL.
- Messages are stored per browser session/conversation and can be reviewed/replied to by an authenticated administrator.
- Admin login protects the dashboard.
- Admin can update booking status: pending, confirmed, completed or cancelled.
- Admin can save/export the prototype's recorded accessibility checklist baseline.
- The accessibility audit panel explicitly labels the score as a recorded baseline; use Lighthouse and axe-DevTools on the deployed application for a live measurement.
- UI was redesigned with a modern responsive layout, better spacing, clearer hierarchy and mobile behavior while keeping keyboard-friendly interactions and visual alerts.

## XAMPP setup

1. Install/start Apache and MySQL in XAMPP.
2. Copy the `DeafConnect` folder into `C:\xampp\htdocs\`.
3. Open phpMyAdmin and import `database.sql`.
4. Check `config/database.php`. The default XAMPP values are `localhost`, database `deafconnect`, user `root`, empty password.
5. Visit `http://localhost/deaf-connect/`.
6. Admin: `http://localhost/deaf-connect/login.php`

### Demo admin account

Username: `admin`

Password: `DeafConnect@1234`

Change the account password before using the project outside a local/demo environment.

## Database tables

`admins` — administrator accounts.

`contacts` — available text-support contacts.

`messages` — guest/admin conversation messages.

`bookings` — service requests and their status.

`audit_reports` — saved accessibility checklist baselines.

## Notes for the project report

The supplied prototype says its accessibility status was verified with Lighthouse and axe-DevTools. The PHP backend included here does **not** run those browser auditing engines itself. Instead, it stores the prototype checklist/baseline in MySQL and reminds the administrator to run the live tools on the deployed site before reporting a current score.


## AI video transcription and captions

The Video page now uses the Gemini API for real transcription.

### Configure Gemini

1. Copy `config/gemini.example.php` to `config/gemini.php` if needed.
2. Open `config/gemini.php`.
3. Replace `YOUR_GEMINI_API_KEY_HERE` with your Gemini API key.
4. Leave the key in PHP only. Do not put it in `assets/js/` or HTML.
5. The default model is `gemini-3.8-flash`; change it only if your Gemini account/API documentation specifies another supported model.

The integration uses Google's Files API to upload the video, waits for the uploaded file to become active, then sends it to Gemini for a structured, timestamped transcript. Gemini's current documentation recommends the Files API for larger media and supports video understanding with timestamps.

The application converts the returned segments into WebVTT and attaches the resulting `.vtt` file to the HTML5 video. The transcript panel is generated from the same AI response, and clicking a transcript segment seeks the video to its start time.

### Local upload settings

The application accepts up to 100 MB per video by default. PHP itself must also permit uploads of that size. In XAMPP, check `php.ini` and make sure `upload_max_filesize` and `post_max_size` are large enough (for example, 128M), then restart Apache.

The Gemini Files API temporarily stores uploaded Gemini-side files and automatically deletes them after 48 hours; DeafConnect also attempts to delete the Gemini file after transcription is complete.

### Important

No real Gemini API key is included in this project. The supplied `config/gemini.php` contains only a placeholder.

Gemini API usage may incur quota/charges depending on the Google account/project configuration. Test with short videos first.
