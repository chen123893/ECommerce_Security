# Windows setup (XAMPP)

[Back to the user manual](README.md)

The paths below use `C:\xampp`. If you installed XAMPP elsewhere, update the paths in both the commands and the Apache configuration.

1. **Install XAMPP and download ShopEase.** Use [XAMPP for Windows with PHP 8.2](https://www.apachefriends.org/download.html). On the [ShopEase repository](https://github.com/chen123893/ECommerce_Security), choose **Code → Download ZIP**, then extract and rename the folder so `index.php` is at:

   ```text
   C:\xampp\shopease-https\E-Commerce-Management-System\index.php
   ```

   Create `shopease-https` if it does not exist. Keep the project outside the default `htdocs` folder.

2. **Create the localhost certificate.** Paste these commands into PowerShell. Accept the trust prompt only for the certificate you just generated.

   ```powershell
   $certDir = 'C:\xampp\apache\conf\shopease-local'
   New-Item -ItemType Directory -Force -Path $certDir | Out-Null
   @'
   [req]
   distinguished_name = dn
   [dn]
   CN = localhost
   '@ | Set-Content -Encoding ascii "$certDir\openssl.cnf"

   & 'C:\xampp\apache\bin\openssl.exe' req -x509 -newkey rsa:2048 -nodes -sha256 -days 90 `
     -config "$certDir\openssl.cnf" -subj '/CN=localhost' -addext 'subjectAltName=DNS:localhost' `
     -keyout "$certDir\localhost.key" -out "$certDir\localhost.crt"
   if ($LASTEXITCODE -ne 0) { throw 'Certificate generation failed.' }
   certutil.exe -user -addstore Root "$certDir\localhost.crt"
   ```

   Keep the private key in the Apache configuration folder.

3. **Enable HTTPS in Apache.** Copy [`xampp-shopease-ssl.conf`](xampp-shopease-ssl.conf) from the project’s `docs\user-manual` folder to `C:\xampp\apache\conf\extra`. Back up `C:\xampp\apache\conf\httpd.conf`. Open it and uncomment the existing `LoadModule ssl_module` and `LoadModule headers_module` lines if needed. Add this line once at the end:

   ```apache
   Include conf/extra/httpd-shopease-ssl.conf
   ```

4. **Check the configuration and start XAMPP.** In PowerShell, run:

   ```powershell
   & 'C:\xampp\apache\bin\httpd.exe' -t -d 'C:/xampp/apache' -f 'conf/httpd.conf'
   ```

   If it reports `Syntax OK`, start or restart **Apache** and **MySQL** in the XAMPP Control Panel. Open [phpMyAdmin](http://127.0.0.1/phpmyadmin/) and [continue to the database setup](README.md#2-set-up-the-database).

If Apache does not start, check its error log and make sure port `8443` is free. If the browser still warns about the certificate, confirm that you trust the generated certificate, restart the browser, and open the site using `localhost`.
