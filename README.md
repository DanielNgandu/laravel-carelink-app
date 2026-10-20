# CareLink Follow-Up

CareLink Follow-Up is a demonstration application that helps clinic staff find patients who are due for appointments, have recently missed appointments, or are overdue. Staff can filter the list and record attempts to contact patients.



## What you need

A Windows computer with **XAMPP (PHP 8.2 or later)**, **Composer**, and **Node.js with npm** installed. The demonstration uses a local SQLite database, so no separate database server is needed.

## Download and set up

1. Open the [CareLink GitHub repository](https://github.com/DanielNgandu/laravel-carelink-app.git). Select **Code → Download ZIP**, then extract the folder to `C:\xampp\htdocs\laravel-carelink-app`. Alternatively, open a terminal in `C:\xampp\htdocs` and run:

   ```powershell
   git clone https://github.com/DanielNgandu/laravel-carelink-app.git
   cd laravel-carelink-app
   ```

2. Open the extracted project folder in VS Code. Select **Terminal → New Terminal**, then run these commands one at a time:

   ```powershell
   composer install
   Copy-Item .env.example .env
   php artisan key:generate
   ```

   If `.env` already exists, **do not overwrite it or regenerate the application key**.
3. In `.env`, set `DB_CONNECTION=sqlite` and `SESSION_DRIVER=file`. Create an empty `database/database.sqlite` file if it does not exist.
4. Continue with:

   ```powershell
   php artisan migrate
   php artisan db:seed
   npm.cmd install
   npm.cmd run build
   php artisan serve
   ```

5. Open **http://127.0.0.1:8000/login** in your browser.

The demonstration accounts must be created or seeded in your local database before they can be used.

## Demonstration login

| Account | Email | Password |
|---|---|---|
| Clinician | `clinician@carelink.test` | `DemoPass123!` |
| District Officer | `district@carelink.test` | `DemoPass123!` |

These are **local demonstration credentials only**. Never use them in a real clinical deployment.

## Using CareLink

After signing in, the Follow-Up page opens. Choose an appointment status, facility (where permitted), and sorting option, then select **Search**. Use **Previous** and **Next** to move between pages. Clinicians can select **Record Contact**, enter a note, and save it. District officers have read-only access. Select **Logout** when finished.

## If something goes wrong

- **Page not loading:** Check that `php artisan serve` is still running.
- **No patients shown:** Change the status filter or check that sample data has been seeded.
- **Styles missing:** Run `npm.cmd run build` and refresh the browser.
- **Page expired (419):** Reload the login page and sign in again, using the same address throughout.

To run the automated checks, enter `php artisan test` in the terminal.


