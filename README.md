# A & D Inspections — Forms Project

Plain procedural PHP + MySQL (no framework, no OOP), matching the existing
`inspection.zip` project's style. This update adds a **second form** —
the Site Inspection Report (MEP / Plumbing), built from your uploaded
`Cypress_Site_Report_2026.docx` (blank template) and
`7_5_26__P_Cypress_..._doc_.docx` (filled example) — and turns the
login landing page into a **form picker (dashboard)**.

## What's new vs. the original inspection.zip

| File | Status |
|---|---|
| `index.php` | **Replaced.** Was the Final Energy Inspection form; is now the post-login dashboard listing both forms as hyperlinks. |
| `form_energy_inspection.php` | New name for the old `index.php` form (unchanged content/behavior). |
| `reports_list_energy.php`, `report_energy.php` | Renamed from `reports_list.php` / `report.php` (unchanged content). |
| `form_plumbing_inspection.php` | **New.** The responsive Site Inspection Report (MEP/Plumbing) form. |
| `api_plumbing.php` | **New.** Save / Download PDF / Share Email / List endpoints for the new form. |
| `pdf_generate_plumbing.php` | **New.** FPDF generator for the new form's PDF (reuses the letterhead/border/checkbox helpers already in `pdf_generate.php`). |
| `report_render_plumbing.php` | **New.** HTML renderer used by `report_plumbing.php` and the emailed report body. |
| `reports_list_plumbing.php`, `report_plumbing.php` | **New.** "Previous reports" list and single-report/resend view for the new form. |
| `logout.php` | **New.** The original project only auto-logged-out on idle timeout; the dashboard adds an explicit "Sign out" link. |
| `schema.sql` | **Appended.** Added `plumbing_inspections` table (existing `users`/`inspections` tables untouched). |
| `.htaccess` | **Updated.** Added the two new server-only files to the deny list. |
| `api.php`, `auth.php`, `config.php`, `smtp_mailer.php`, `pdf_generate.php`, `report_render.php`, `report_letterhead.php`, `report.css`, `login.php`, `forgot_password.php`, `reset_password.php`, `setup_user.php`, `heartbeat.php`, `fpdf/`, `image/` | **Unchanged**, reused as-is. |

## How the new form maps to your Word document

`form_plumbing_inspection.php` reproduces every field found in the
template (checked via the doc's raw form-field/checkbox XML, not just
visible text):

- Header: Prepared For (client), Concerning (inspection address),
  Building #, Inspector, Date.
- Inspection Scope checkboxes: Plumbing / Electrical / HVAC / Other (+text).
- Parties Present checkboxes: Superintendent / Subcontractor / Other (+text).
- Weather (single choice): Sunny / Overcast / Raining.
- Time of inspection + Outside air temperature.
- "Additional written information provided" Yes/No.
- The I / NI / NP / D legend and checklist. The one item shown in your
  filled sample (**I. MEP → A. Underground Plumbing**, marked
  Inspected, with the "Passed — PVC plumbing pipe..." finding) loads
  as the first item by default, but the form lets the inspector
  **add/remove additional items** (same repeatable-list pattern the
  original project already uses for "Areas inspected"), since a real
  site visit usually covers more than one MEP item. If you'd rather
  lock this to exactly one fixed item, that's a quick trim.

## The two buttons (as requested)

- **Download as PDF** — saves the form to MySQL first, then streams a
  generated PDF back to the browser as a download. The PDF is also
  kept on the server in `document/` under a unique filename
  (`SiteInspection_<Client>_<YYYYMMDD>_<8-char-hash>.pdf`).
- **Share with Email** — saves to MySQL, generates the same PDF into
  `document/`, and emails it (as an attachment, with a short HTML
  summary body) to the address typed into "Send report to," CC'ing
  your company address. Uses the same raw-socket SMTP mailer already
  in the project (`smtp_mailer.php`) — no PHPMailer/Swiftmailer
  dependency, consistent with "no framework."

Both buttons remember the saved record's ID after the first click, so
clicking either button again **updates** the same database row instead
of creating duplicates.

## Testing performed

There's no PHP runtime available to me in this chat by default, so I
installed PHP 8.3 + MariaDB locally, loaded `schema.sql`, and ran the
actual project through PHP's built-in server end-to-end:

- Logged in, loaded the dashboard, confirmed both form cards link
  correctly and show live saved-report counts.
- Submitted the new plumbing form's "Download as PDF" — confirmed a
  correct, well-formatted 1-page PDF (checked both by extracting its
  text and rendering it to an image) and a correct row in
  `plumbing_inspections`.
- Submitted "Share with Email" — confirmed it saves, generates the
  PDF, and correctly reaches the SMTP-send step before failing (only
  because this sandbox can't reach an SMTP host — your real
  `config.php` SMTP settings will work as they already do for the
  first form).
- Re-tested the renamed energy-inspection form/API the same way to
  make sure the rename didn't break anything (3-page PDF still
  generates correctly).
- Rendered both the dashboard and the new form at a 380px mobile
  width to confirm responsive layout.
- `php -l` (syntax lint) on every `.php` file in the project.

## Setup on your server

1. Upload everything to the same folder as before (this zip already
   contains the full existing project plus the additions above).
2. Run the new portion of `schema.sql` (the `plumbing_inspections`
   table) against your existing database — the `CREATE TABLE IF NOT
   EXISTS` statements won't touch your existing `users`/`inspections`
   data.
3. Nothing in `config.php` needs to change; the new form reuses your
   existing DB and SMTP credentials.

## Update — form name, missing field, and inspector row fix

Three corrections made after the first delivery:

1. **Form renamed** from "Site Inspection Report" to **"Property
   Inspection Report"** everywhere: the form's on-screen heading and
   `<title>`, the generated PDF's heading, the emailed report's
   subject line and body text, the "previous reports" list/detail
   page titles, the downloaded PDF's filename prefix
   (`PropertyInspection_...`), and the dashboard card on `index.php`.
2. **Added the blank "Report Title" field** that sits directly above
   the client-name field in the Word template (it was missing from
   the first version of the form). It's optional, stored in a new
   `report_title` column, and — when filled in — shows up on the PDF,
   the saved-report detail view, and the emailed report summary.
3. **"Building #" renamed to "License No." and reordered.** The
   template's `(Name and License Number of Inspector) ... (Date)`
   row is now reproduced as **Name → License No. → Date**, in that
   order, everywhere (form, PDF, saved-report view, DB column
   `inspector_license`). The old, separate "Building #" field is
   gone — building/unit info was already part of the free-text
   "Concerning (Inspection Address)" field, so no data is lost. The
   "previous reports" list now shows the license number as a
   secondary badge instead of a building number.

**If you already deployed the previous `plumbing_inspections` table**
on your live server, run this migration once (re-running `schema.sql`
alone won't alter an existing table):
```sql
ALTER TABLE plumbing_inspections CHANGE building inspector_license VARCHAR(100) DEFAULT '';
ALTER TABLE plumbing_inspections ADD COLUMN report_title VARCHAR(255) DEFAULT '' AFTER id;
```

All three changes were re-verified the same way as the first delivery
(local PHP 8.3 + MariaDB, full login → save → PDF → DB round trip,
plus a mobile-width screenshot of the updated form) before packaging.

## One thing worth your attention

`config.php` (carried over unchanged from your upload) has real
production values in it — MySQL password, a Gmail address **and app
password**, and personal emails. That's expected for a working
`config.php`, but since this file has now passed through a chat
transcript, it's worth rotating the Gmail app password and MySQL
password once you're done here, purely as good hygiene.
