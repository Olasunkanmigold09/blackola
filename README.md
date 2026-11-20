# BLACKOLA CRUD

A task manager built with PHP, MySQL, Bootstrap 5, and JavaScript.

Features include account registration and login, profile and password management, task creation and completion, task filtering, progress statistics, and an in-app due-date reminder panel for incomplete tasks due within seven days.

## Requirements

- PHP 8.0 or newer with MySQLi and `mbstring`
- MySQL or MariaDB
- A local server such as XAMPP, WAMP, Laragon, or MAMP

## Setup

1. Place the `blackola` folder in your web server's document root (for example, `C:\xampp\htdocs\blackola`).
2. Import `database.sql` in phpMyAdmin. This creates the `blackola` database and its tables.
3. By default, the app connects to local MySQL as `root` with an empty password. To use different credentials, set `TASKFLOW_DB_HOST`, `TASKFLOW_DB_NAME`, `TASKFLOW_DB_USER`, and `TASKFLOW_DB_PASS`.
4. Open `http://localhost/blackola/`, register an account, and log in.

To use PHP's built-in server, run `php -S localhost:8000` from the `blackola` folder and open `http://localhost:8000/`.

## Project structure

- `pages/tasks.php` - JSON endpoint for task operations
- `assets/` - JavaScript and stylesheets
- `config/` - database connection settings and schema
- `docs/` - project learning guide
- `includes/` - authentication helpers and shared header, footer, and logout
- `layouts/` - reusable page fragments
- `pages/` - login, registration, task, and profile pages
- `index.php` - entry point

## Security notes

Passwords are hashed with `password_hash`, database queries use prepared statements, form and API writes are CSRF protected, and output is escaped. Before deploying publicly, use HTTPS and a dedicated MySQL user instead of `root`.
