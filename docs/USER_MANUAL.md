# ShopEase user manual

ShopEase runs locally on Apache, PHP and MySQL or MariaDB. Use **XAMPP on Windows** or **MAMP on macOS**. Install a bundle with PHP 8 and MySQL support. Keep the setup on your own computer for the assessment.

- [Windows with XAMPP](#1-windows-with-xampp)
- [macOS with MAMP](#2-macos-with-mamp)
- [Database and first run](#3-database-and-first-run-both-platforms)
- [Demo and troubleshooting](#4-using-shopease)

## 1. Windows with XAMPP

1. Install [XAMPP for Windows](https://www.apachefriends.org/faq_windows.html). The paths below assume `C:\xampp`; adjust them if you installed it elsewhere.
2. In PowerShell, create the HTTPS site and certificate folders, then download the project:

   ```powershell
   New-Item -ItemType Directory -Force -Path 'C:\xampp\shopease-https', 'C:\xampp\apache\conf\shopease-local' | Out-Null
   git clone https://github.com/chen123893/ECommerce_Security.git 'C:\xampp\shopease-https\E-Commerce-Management-System'
   ```

   Without Git, use **Code → Download ZIP** on the repository, extract it and place the project files in `C:\xampp\shopease-https\E-Commerce-Management-System`. Confirm that `index.php` and `.htaccess` are in that folder. Keep ShopEase outside XAMPP's default `htdocs` so it is available only through the HTTPS site configured below.

3. Create and trust a certificate for this local installation. Run the following in PowerShell:

   ```powershell
   $certDir = 'C:\xampp\apache\conf\shopease-local'
   @'
   [req]
   distinguished_name = dn
   [dn]
   CN = localhost
   '@ | Set-Content -Encoding ascii "$certDir\openssl.cnf"

   & 'C:\xampp\apache\bin\openssl.exe' req -x509 -newkey rsa:2048 -nodes -sha256 -days 90 `
     -config "$certDir\openssl.cnf" -subj '/CN=localhost' `
     -addext 'subjectAltName=DNS:localhost' `
     -keyout "$certDir\localhost.key" -out "$certDir\localhost.crt"
   if ($LASTEXITCODE -ne 0) { throw 'Could not generate the localhost certificate.' }
   certutil.exe -user -addstore Root "$certDir\localhost.crt"
   ```

   Trust only the `localhost.crt` you generated. Keep `localhost.key` private and outside the web folders. If the browser still shows a trust warning, restart it and recheck the certificate rather than bypassing the warning.

4. Back up `C:\xampp\apache\conf\httpd.conf`. In that file, uncomment the existing SSL and headers module lines if necessary. Add the Include line once:

   ```apache
   LoadModule ssl_module modules/mod_ssl.so
   LoadModule headers_module modules/mod_headers.so
   Include conf/extra/httpd-shopease-ssl.conf
   ```

   Save the following as `C:\xampp\apache\conf\extra\httpd-shopease-ssl.conf`. In Notepad, choose **All files** when saving to avoid adding `.txt`.

   ```apache
   Listen 127.0.0.1:8443 https
   <VirtualHost 127.0.0.1:8443>
       ServerName localhost:8443
       DocumentRoot "C:/xampp/shopease-https"

       SSLEngine on
       SSLProtocol -all +TLSv1.2 +TLSv1.3
       SSLCertificateFile "C:/xampp/apache/conf/shopease-local/localhost.crt"
       SSLCertificateKeyFile "C:/xampp/apache/conf/shopease-local/localhost.key"

       Header always set X-Frame-Options "DENY"
       Header always set X-Content-Type-Options "nosniff"
       Header always set Referrer-Policy "strict-origin-when-cross-origin"
       Header always set Strict-Transport-Security "max-age=300"

       <Directory "C:/xampp/shopease-https">
           Require all denied
       </Directory>
       <Directory "C:/xampp/shopease-https/E-Commerce-Management-System">
           Options -Indexes
           AllowOverride FileInfo AuthConfig
           DirectoryIndex index.php
           Require local
       </Directory>
       <LocationMatch "^/E-Commerce-Management-System/(?:database|config|deploy|docs|[.]git)(?:/|$)">
           Require all denied
       </LocationMatch>
   </VirtualHost>
   ```

5. Check Apache's configuration in PowerShell. If it reports `Syntax OK`, start or restart **Apache** and **MySQL** from the XAMPP Control Panel.

   ```powershell
   & 'C:\xampp\apache\bin\httpd.exe' -t -d 'C:/xampp/apache' -f 'conf/httpd.conf'
   ```

   Open [local phpMyAdmin](http://127.0.0.1/phpmyadmin/) for the next section. If XAMPP uses a different HTTP port, use its **MySQL → Admin** button or add the HTTP port to the URL.

## 2. macOS with MAMP

1. In MAMP, choose **Apache** and **PHP 8**. Place the downloaded project at `/Applications/MAMP/shopease-local-htdocs/E-Commerce-Management-System`. Keep the project folder name and the included `.htaccess`.
2. In Terminal, generate a certificate with `localhost` in its Subject Alternative Name (SAN). The example uses MAMP's OpenSSL.

   ```sh
   certdir=/Applications/MAMP/conf/apache/shopease-local
   mkdir -p "$certdir"
   /Applications/MAMP/bin/apache2/bin/openssl req -x509 -newkey rsa:2048 -nodes -sha256 -days 90 \
     -subj '/CN=localhost' -addext 'subjectAltName=DNS:localhost' \
     -keyout "$certdir/localhost.key" -out "$certdir/localhost.crt"
   chmod 600 "$certdir/localhost.key"
   ```

   Import only the generated `localhost.crt` into **Keychain Access** and mark it trusted for SSL on this computer. If MAMP runs Apache under a different user, grant that user read access to the private key without making the key public.
3. Back up `/Applications/MAMP/conf/apache/httpd.conf`. Copy [the supplied MAMP HTTPS configuration](../deploy/mamp-shopease-ssl.conf) to `/Applications/MAMP/conf/apache/extra/mamp-shopease-ssl.conf`. Edit MAMP's `httpd.conf`: ensure the SSL and headers modules are enabled and add this line once:

   ```apache
   Include "/Applications/MAMP/conf/apache/extra/mamp-shopease-ssl.conf"
   ```

4. Run the check below, start or restart MAMP and open phpMyAdmin from **WebStart**. Check MAMP's settings for the MySQL port (often `8889`).

   ```sh
   /Applications/MAMP/bin/apache2/bin/httpd -t -f /Applications/MAMP/conf/apache/httpd.conf
   ```

## 3. Database and first run (both platforms)

1. Use a **disposable local database**. In phpMyAdmin, click the server name, then **Import**. Import [`database/ecommerce.sql`](../database/ecommerce.sql) first. It **replaces the existing `ecommerce` database**. Then import [`database/migration_security_audit.sql`](../database/migration_security_audit.sql). The current dump already has the authentication tables, so skip `migration_security.sql` for this fresh install.
2. In the project folder beside `index.php`, create a plain text file named `.env` (no `.txt` suffix). Use this template with the MySQL details for your own computer:

   ```dotenv
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=ecommerce
   DB_USER=your_local_database_user
   DB_PASS=your_local_database_password
   ```

   XAMPP commonly uses MySQL port `3306`; use the actual port, and update it for MAMP. Use `DB_PASS=` only if your local database account has no password. Keep the file out of Git.

3. Open these URLs in your browser:

   | Page | URL |
   | --- | --- |
   | Storefront | [https://localhost:8443/E-Commerce-Management-System/](https://localhost:8443/E-Commerce-Management-System/) |
   | Customer login | [Customer sign-in](https://localhost:8443/E-Commerce-Management-System/pages/auth/login.php) |
   | Staff and administrator login | [Management sign-in](https://localhost:8443/E-Commerce-Management-System/admin/login.php) |

## 4. Using ShopEase

1. **Customer:** Register a demo account, sign in, choose an in-stock product, add it to the cart and submit checkout with test delivery details. Confirm the order appears in **My Orders**.
2. **Administrator and staff:** If you are setting up your own disposable database and don't already have demo logins, register separate accounts in the storefront. Sign out, then use phpMyAdmin to set the role in `ecommerce.users` for only those accounts to `Admin` and `Staff`, respectively. Sign in through the management portal. The administrator can use **Two-Factor Auth** to enrol and verify an authenticator app before demonstrating MFA at the next sign-in. Use a separate browser profile to demo the customer and management sessions independently.
3. **Finish:** Sign out and stop Apache and MySQL in XAMPP or MAMP. Remove the test certificate from your personal trust store when you no longer use this setup.

| If you see… | Check… |
| --- | --- |
| Connection refused on port 8443 | Apache is running, the Include is loaded, and port `8443` is free. Check Apache's error log if it cannot start. |
| Certificate warning | The browser uses `https://localhost:8443`, the local certificate is trusted, its SAN is `localhost`, and the certificate has not expired. |
| 403 or 404 opening the storefront | Use the HTTPS URL with the full project folder name and make sure the files are in the correct HTTPS document root. |
| Database connection failed | Start MySQL, verify the imported `ecommerce` database, and check the name, port and local credentials in `.env`. |

Apache reference: [SSL/TLS how-to](https://httpd.apache.org/docs/2.4/ssl/ssl_howto.html). Windows certificate command: [Microsoft certutil documentation](https://learn.microsoft.com/en-us/windows-server/administration/windows-commands/certutil).
