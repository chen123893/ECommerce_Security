# ShopEase user manual

Use this guide to install ShopEase locally and try the customer and management portals.

## 1. Set up the web server

Follow the guide for your computer. Both setups use HTTPS on port `8443`.

| Computer | Setup guide |
| --- | --- |
| **Windows** | [XAMPP setup](windows.md) |
| macOS | [MAMP setup](macos.md) |

## 2. Set up the database

1. Open **phpMyAdmin** from XAMPP or MAMP. Use a database you can replace: the first import deletes and recreates the local `ecommerce` database.
2. On the server’s **Import** tab, import [`database/ecommerce.sql`](../../database/ecommerce.sql). Select `ecommerce` in the sidebar, then import [`database/migration_security_audit.sql`](../../database/migration_security_audit.sql).
3. In the ShopEase folder beside `index.php`, create a plain text file named `.env` (without `.txt`). Enter your local database settings. Use the MySQL port shown in XAMPP or MAMP.

   ```dotenv
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=ecommerce
   DB_USER=your_local_database_user
   DB_PASS=your_local_database_password
   ```

   XAMPP usually uses `3306`; MAMP often uses `8889`. Leave the value after `DB_PASS=` empty only if your local database user has no password. Keep `.env` out of Git.

## 3. Open ShopEase

| Page | Address |
| --- | --- |
| Storefront | [https://localhost:8443/E-Commerce-Management-System/](https://localhost:8443/E-Commerce-Management-System/) |
| Customer sign-in | [Open customer sign-in](https://localhost:8443/E-Commerce-Management-System/pages/auth/login.php) |
| Staff or administrator sign-in | [Open management sign-in](https://localhost:8443/E-Commerce-Management-System/admin/login.php) |

![ShopEase customer sign-in screen](sign-in.png)

## Try the demo

- **Customer:** Create an account, sign in, add an in-stock item such as the Dell Inspiron 15 to the cart, and check out with test delivery details. Look for the order in **My Orders**.
- **Administrator or staff:** Sign in through the management portal. If you need a demo login on your own local database, register a separate account, sign out, then set that account’s `users.role` to `Admin` or `Staff` in phpMyAdmin. Use the administrator’s **Two-Factor Auth** page to set up MFA before demonstrating it.

If the site does not open, check that Apache is running and that you used the full HTTPS address. For a certificate warning, check the trust step in your setup guide. If ShopEase reports a database connection error, check MySQL and the settings in `.env`.
