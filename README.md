<div align="center">
  <img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white" />
  <img src="https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white" />
  <img src="https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white" />
  <img src="https://img.shields.io/badge/Bootstrap_Icons-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white" />

<br><br>

  <h1 align="center">🛍️ E-Commerce Management System</h1>
  
  <p align="center">
    <strong>A professional, modern, and fully responsive E-Commerce platform featuring a stunning Mint-Green UI.</strong>
  </p>

</div>

<hr>

## Features

### 🛒 Client storefront

- 🧾 Browse products, categories, brands, and product details
- 🛒 Add products to a session-based shopping cart
- 🔐 Register and sign in as a client
- 📍 Manage profile information and delivery details
- 📦 Place orders, review order history, and cancel eligible orders

### 🧰 Staff portal

Staff members use the management portal to support daily store operations. Depending on the page, staff can view the dashboard, products, clients, and orders; review order items; manage inventory, categories, brands, partners, and team information.

Staff accounts cannot access administrator-only user management and other restricted actions.

### 🛡️ Administrator portal

Administrators have full management access, including everything available to staff plus:

- 👤 Create, edit, and delete users
- 🏷️ Assign the `Admin`, `Staff`, or `Client` role
- 🗂️ Manage all catalog, order, client, team, and partner records
- 📊 Monitor inventory, revenue, orders, and registered users

## 👥 User roles

| Role        | Main access                                                     | Login location          |
| ----------- | --------------------------------------------------------------- | ----------------------- |
| 🛒 `Client` | Storefront, cart, checkout, profile, and personal orders        | `/pages/auth/login.php` |
| 🧰 `Staff`  | Store management and operational dashboard pages                | `/admin/login.php`      |
| 🛡️ `Admin`  | Full dashboard access, including user and permission management | `/admin/login.php`      |

The role is stored in the `users.role` column. New public registrations are created as `Client` accounts. Staff and administrator accounts should be created by an administrator or inserted into the database by a trusted developer.

## 💻 Technology

- 🐘 PHP 8 or newer
- 🗄️ MySQL or MariaDB
- 🌐 HTML5, CSS3, and Vanilla JavaScript
- 🎨 Bootstrap Icons
- 🖥️ XAMPP, WAMP, MAMP, or another Apache/PHP environment

## 📂 Project Structure

```text
E-Commerce-Management-System/
├── actions/          # Form handlers for authentication, cart, checkout, orders, and profile
├── admin/            # Staff and administrator portal
│   ├── includes/     # Dashboard authentication and shared layout files
│   ├── pages/        # Catalog, client, order, team, partner, and user management
│   └── assets/       # Admin styles, scripts, and uploaded images
├── assets/           # Storefront styles and JavaScript
├── config/           # Database connection
├── database/         # Database schema and seed data
├── includes/         # Shared storefront header, navigation, and footer
├── pages/            # Storefront, authentication, cart, checkout, and account pages
└── index.php         # Storefront entry point
```

## ⚙️ Installation

1. 🚀 Install and start Apache and MySQL in XAMPP.
2. 📁 Copy the project into the web root, for example:

```text
C:\xampp\htdocs\E-Commerce-Management-System
```

3. 🗃️ Open `http://localhost/phpmyadmin` and create a database named `ecommerce`.
4. 📥 Import `database/ecommerce.sql`.
5. 🌱 Optionally import `database/seed.sql` to add sample users, products, categories, brands, and other data.
6. 🔧 Check `config/database.php` and update the host, database name, username, and password for your local MySQL installation.
7. 🔗 Open the application:

- 🛍️ Storefront: `http://localhost/E-Commerce-Management-System/`
- 🛒 Client login: `http://localhost/E-Commerce-Management-System/pages/auth/login.php`
- 🔐 Staff and admin login: `http://localhost/E-Commerce-Management-System/admin/login.php`

## 🔑 Demo accounts

The accounts in `database/seed.sql` use the development password `password`:

| Role   | Email                  |
|--------|------------------------|
| Admin  | `admin@shopease.com`   |
| Staff  | `staff@shopease.com`   |
| Client | `ahmed@example.com`    |
| Client | `mohamed@example.com`  |
| Client | `omar@example.com`     |
| Client | `youssef@example.com`  |
| Client | `ali@example.com`      |

**Password for all accounts:** `password`

The seed file does not currently include a `Staff` account. To test the staff portal, create a user with `role = 'Staff'` through the administrator user page or directly in the database, using a securely generated password hash.

Change all development passwords before using the application outside a local test environment.

## 🗄️ Database overview

- 👥 `users`: login identities, role assignments, and 2FA secrets
- 📇 `clients`: client contact and delivery information
- 🛡️ `login_attempts`: IP and account login attempt tracking for rate limiting
- 🔑 `password_resets`: cryptographically secure SHA-256 password reset tokens
- 🛍️ `products`, `categories`, and `brands`: product catalog
- 📦 `orders` and `order_items`: purchases and their products
- 🤝 `team` and `partners`: storefront content managed from the portal

## 🛡️ Security Architecture & Implementations

### 1. Strong Password Policy & Modern Hashing (Argon2id / Bcrypt)
- **Password Policy Enforced**:
  - Minimum 8 characters in length.
  - Requires at least 1 uppercase letter (`A-Z`).
  - Requires at least 1 lowercase letter (`a-z`).
  - Requires at least 1 numeric digit (`0-9`).
  - Requires at least 1 special symbol (`!@#$%^&*` etc.).
  - Real-time client-side visual feedback checklist and strict server-side validation.
- **Hashing Algorithm**:
  - Automatically selects `Argon2id` (`PASSWORD_ARGON2ID`) when supported by PHP, with fallback to high-cost `Bcrypt` (`cost = 12`).
  - **Transparent Auto-Rehashing**: Upon every successful login, legacy hashes are automatically rehashed in the database without any user friction.

### 2. Login Rate Limiting (Brute-Force & Credential Stuffing Defense)
- Tracked via dedicated database table `login_attempts` with IP and email indexes.
- **Per-Account Limit**: 5 consecutive failed attempts per email within a 15-minute sliding window.
- **Per-IP Limit**: 10 failed attempts across all accounts per IP within 15 minutes (mitigates distributed spraying).
- Protects both Customer login (`/pages/auth/login.php`) and Admin login (`/admin/login.php`).
- Automatically clears failed counters upon successful authentication.
- Rate-limiting protection is also applied to TOTP verification attempts on 2FA screens.

### 3. Secure Password Reset Flow
- **Anti-Enumeration Design**: Submitting an email returns a uniform, timing-safe confirmation message regardless of whether the email exists.
- **Cryptographic High-Entropy Tokens**:
  - Generates 256-bit cryptographically secure random tokens (`random_bytes(32)`).
  - Only stores **SHA-256 hashes** of tokens in the database, preventing token leakage in case of database dumps.
- **Single-Use & 30-Minute Expiry**: Tokens expire in 30 minutes and are immediately consumed upon reset.
- **Local Dev Simulation Banner**: In local testing environments without an active SMTP service, a simulated reset link is made available for instant testing.

### 4. Admin Multi-Factor Authentication (MFA / TOTP)
- **RFC 6238 Compliant TOTP Engine**: Works with Google Authenticator, Microsoft Authenticator, 1Password, Authy, etc.
- **Self-Contained Pure PHP Implementation**: Zero third-party backend server dependencies; does not send secrets to external chart APIs.
- **QR Code & Secret Key Display**: Interactive setup wizard with instant QR code scanning and manual Base32 key entry.
- **Emergency Single-Use Backup Codes**: Generates 8 one-time alphanumeric recovery codes (stored as SHA-256 hashes) to prevent account lockout if the mobile device is lost.
- **Admin Management Portal**: Administrative users can enable/disable 2FA and regenerate backup recovery codes from `/admin/pages/security/mfa.php`. Administrators can also reset 2FA for staff or locked users directly from `/admin/pages/users/edit.php`.

## 🔒 Security Best Practices

- 🔐 Always enforce HTTPS in production environments.
- 🔑 Set strong, unique passwords for all staff and admin accounts.
- 🛡️ Enable Admin Two-Factor Authentication immediately after initial setup.
- 🚫 Never commit production database credentials to version control.

