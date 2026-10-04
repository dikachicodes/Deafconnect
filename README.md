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
