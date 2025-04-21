# 🖨️ Printer Tracker Web App

A PHP-based printer discovery and monitoring tool built for use on a local network. It performs SNMP discovery, tracks printer stats like toner levels and page counts, and supports daily automated logging.

---

## 🚀 Getting Started

### 1. Clone the Repository
```bash
git clone https://github.com/plasticus/printers-php-app.git
```

### 2. Create a LAMP Stack in Portainer
Use this `docker-compose.yml`:

```yaml
version: '3.3'
services:
  web:
    image: php:8.2-apache
    container_name: lamp-web
    volumes:
      - ./www:/var/www/html
    ports:
      - "8013:80"
    depends_on:
      - db

  db:
    image: mariadb:10.5
    container_name: printer-lamp-db
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: rootpass
      MYSQL_DATABASE: myapp
      MYSQL_USER: myuser
      MYSQL_PASSWORD: mypass
    volumes:
      - db_data:/var/lib/mysql

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    container_name: printer-lamp-phpmyadmin
    restart: always
    ports:
      - "8014:80"
    environment:
      PMA_HOST: db
      MYSQL_ROOT_PASSWORD: rootpass

volumes:
  db_data:
```

After creating this stack in Portainer, your app will be available at `http://<host-ip>:8013`.

---

## 🧠 SQL Setup
Run these commands in phpMyAdmin or via MySQL CLI:

```sql
CREATE TABLE devices (
    ip_address VARCHAR(45) PRIMARY KEY,
    manufacturer TEXT,
    model TEXT,
    serial_number TEXT,
    page_count INT,
    toner_black INT,
    toner_cyan INT,
    toner_magenta INT,
    toner_yellow INT,
    location VARCHAR(255),
    notes TEXT,
    last_seen DATETIME
);

CREATE TABLE page_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45),
    model TEXT,
    serial_number TEXT,
    page_count INT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---

## 🔁 Automatic Updates

To run the page count update every day at **noon**, set up a cron job:

### 1. Create script `/usr/local/bin/print_update.sh`
```bash
#!/bin/bash
curl -s http://localhost/update.php > /dev/null
```

Make it executable:
```bash
chmod +x /usr/local/bin/print_update.sh
```

### 2. Add to crontab
```bash
crontab -e
```
Add:
```cron
0 12 * * * /usr/local/bin/print_update.sh
```

---

## 🔍 Pages
- `devices.php` — View and edit printers, see usage trends and toner status
- `discovery.php` — Discover new printers and scan the network
- `update.php` — Run-only script for scheduled data capture

---

## ✅ To Do / Ideas
- CSV/JSON export
- SNMP community customization
- Printer type icons or sorting by manufacturer

---

## 📬 Questions
Feel free to open an issue or submit a pull request!
