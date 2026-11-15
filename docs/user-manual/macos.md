# macOS setup (MAMP)

[Back to the user manual](README.md)

1. **Install MAMP and download ShopEase.** In MAMP, choose **Apache** and **PHP 8**. Download the ZIP from the [ShopEase repository](https://github.com/chen123893/ECommerce_Security) and place its files in `/Applications/MAMP/shopease-local-htdocs/E-Commerce-Management-System`.
2. **Create the localhost certificate.** In Terminal, run:

   ```sh
   certdir=/Applications/MAMP/conf/apache/shopease-local
   mkdir -p "$certdir"
   /Applications/MAMP/bin/apache2/bin/openssl req -x509 -newkey rsa:2048 -nodes -sha256 -days 90 \
     -subj '/CN=localhost' -addext 'subjectAltName=DNS:localhost' \
     -keyout "$certdir/localhost.key" -out "$certdir/localhost.crt"
   chmod 600 "$certdir/localhost.key"
   ```

   In **Keychain Access**, import the generated `localhost.crt` and trust it for SSL. Keep `localhost.key` outside the web root.
3. **Enable HTTPS in Apache.** Back up `/Applications/MAMP/conf/apache/httpd.conf`. Copy [`deploy/mamp-shopease-ssl.conf`](../../deploy/mamp-shopease-ssl.conf) to `/Applications/MAMP/conf/apache/extra/mamp-shopease-ssl.conf`. Confirm SSL and headers are enabled in Apache and add this line to `httpd.conf` once:

   ```apache
   Include "/Applications/MAMP/conf/apache/extra/mamp-shopease-ssl.conf"
   ```

4. **Check the configuration and start MAMP.** In Terminal, run:

   ```sh
   /Applications/MAMP/bin/apache2/bin/httpd -t -f /Applications/MAMP/conf/apache/httpd.conf
   ```

   If it reports `Syntax OK`, restart MAMP, open phpMyAdmin from **WebStart**, and [continue to the database setup](README.md#2-set-up-the-database).
