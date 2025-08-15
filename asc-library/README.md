# ASC Library Management System (PHP 8, MySQL 8)

Modern, modular, and scalable LMS with Admin, Librarian, and Student roles. Futuristic blue-gold UI with Bootstrap 5.

## Quick Start (XAMPP / Apache)

1. Clone repo into your web root (e.g., `htdocs/asc-library`).
2. Create database and import schema:
   - Create MySQL DB `asc_library`
   - Import `resources/sql/schema.sql`
3. Create admin user:
   - Generate password hash: `php scripts/gen_password_hash.php admin123`
   - Insert:
     `INSERT INTO user_admin (username,password_hash,role,status) VALUES ('admin','<hash>','Admin','Active');`
4. Configure DB:
   - Set env vars or edit `config/config.php`
5. Apache config:
   - Ensure `mod_rewrite` is enabled
   - Set DocumentRoot to `public/`
6. Visit `http://localhost/asc-library/public/` and login with the admin you created

## Structure

- `public/` front controller and assets
- `app/` core, controllers, models
- `resources/views/` views with layout
- `resources/sql/` schema
- `scripts/` utilities

## Security

- Password hashing (PHP password_hash/password_verify)
- CSRF tokens on POST
- Session hardening (httponly, samesite)
- RBAC checks per route

## Theming

- Bootstrap 5 + custom theme in `public/assets/css/theme.css`