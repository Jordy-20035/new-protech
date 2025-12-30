# ProTech Setup Guide - Windows

Complete step-by-step guide to install PHP, PostgreSQL, and run the ProTech application.

---

## Table of Contents
1. [Install PHP](#1-install-php)
2. [Install PostgreSQL](#2-install-postgresql)
3. [Configure the Database](#3-configure-the-database)
4. [Configure config.php](#4-configure-configphp)
5. [Run the Application](#5-run-the-application)
6. [Test Login Credentials](#6-test-login-credentials)

---

## 1. Install PHP

### Option A: Download PHP Directly (Recommended)

1. **Download PHP for Windows:**
   - Go to: https://windows.php.net/download/
   - Download **PHP 8.3 VS16 x64 Thread Safe** (zip file)
   - Example: `php-8.3.x-Win32-vs16-x64.zip`

2. **Extract PHP:**
   ```
   Extract to: C:\php
   ```

3. **Configure PHP:**
   - In `C:\php`, copy `php.ini-development` and rename to `php.ini`
   - Open `php.ini` in a text editor
   - Find and uncomment these lines (remove the `;` at the start):
   ```ini
   extension_dir = "ext"
   extension=pdo_pgsql
   extension=pgsql
   extension=mbstring
   extension=openssl
   ```

4. **Add PHP to System PATH:**
   - Press `Win + R`, type `sysdm.cpl`, press Enter
   - Go to **Advanced** tab → **Environment Variables**
   - Under "System variables", find `Path` and click **Edit**
   - Click **New** and add: `C:\php`
   - Click **OK** on all dialogs

5. **Verify Installation:**
   - Open a **new** Command Prompt (important!)
   - Run: `php -v`
   - You should see PHP version info

### Option B: Using Chocolatey (Package Manager)

```powershell
# Run PowerShell as Administrator
# Install Chocolatey first (if not installed):
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))

# Install PHP
choco install php -y

# Restart terminal, then verify
php -v
```

---

## 2. Install PostgreSQL

### Download and Install:

1. **Download PostgreSQL:**
   - Go to: https://www.postgresql.org/download/windows/
   - Click "Download the installer"
   - Download the latest version (e.g., PostgreSQL 16)

2. **Run the Installer:**
   - Run the downloaded `.exe` file
   - Click **Next** through the wizard
   - **IMPORTANT:** When prompted for password, enter a password you'll remember!
     - Example: `postgres123`
     - Write this down - you'll need it for `config.php`
   - Keep the default port: `5432`
   - Complete the installation

3. **Verify Installation:**
   - PostgreSQL installs pgAdmin (a GUI tool)
   - You can also use the command line:
   ```cmd
   # Open Command Prompt
   "C:\Program Files\PostgreSQL\16\bin\psql" -U postgres
   # Enter your password when prompted
   ```

### Using Chocolatey (Alternative):

```powershell
choco install postgresql -y
```

---

## 3. Configure the Database

### Option A: Using pgAdmin (GUI)

1. **Open pgAdmin:**
   - Search for "pgAdmin" in Start Menu and open it
   - Set a master password if prompted (can be anything)

2. **Connect to Server:**
   - In the left panel, expand "Servers"
   - Click on "PostgreSQL 16" (or your version)
   - Enter the password you set during installation

3. **Create Database:**
   - Right-click on "Databases" → "Create" → "Database"
   - Name: `protech_db`
   - Click "Save"

4. **Import Schema:**
   - Right-click on `protech_db` → "Query Tool"
   - Click the folder icon (Open File) and select `database.sql`
   - Click the Play button (▶) or press F5 to execute

### Option B: Using Command Line

```cmd
# Open Command Prompt

# 1. Create the database
"C:\Program Files\PostgreSQL\16\bin\psql" -U postgres -c "CREATE DATABASE protech_db;"
# Enter your password

# 2. Import the schema
"C:\Program Files\PostgreSQL\16\bin\psql" -U postgres -d protech_db -f "C:\Users\johnd\Downloads\protech\database.sql"
# Enter your password
```

---

## 4. Configure config.php

Open `config.php` in a text editor and update these values:

```php
// Database connection settings
define('DB_HOST', 'localhost');      // Keep as localhost
define('DB_PORT', '5432');           // Keep default PostgreSQL port
define('DB_NAME', 'protech_db');     // Keep as protech_db
define('DB_USER', 'postgres');       // Your PostgreSQL username
define('DB_PASS', 'your_password');  // ← CHANGE THIS to your PostgreSQL password!
```

### Example with password:
```php
define('DB_PASS', 'postgres123');    // Use the password you set during PostgreSQL installation
```

---

## 5. Run the Application

### Start PHP Development Server:

```cmd
# Open Command Prompt
cd C:\Users\johnd\Downloads\protech

# Start the server
php -S localhost:8000
```

### Access the Application:

Open your browser and go to:
- **Home Page:** http://localhost:8000
- **Login/Register:** http://localhost:8000/auth.html

Keep the Command Prompt window open while using the application!

---

## 6. Test Login Credentials

The `database.sql` includes sample users with the password: `password123`

| Email | Password | Type |
|-------|----------|------|
| john@example.com | password123 | Client |
| mike@example.com | password123 | Worker (Plumber) |
| sarah@example.com | password123 | Worker (Electrician) |
| david@example.com | password123 | Worker (Cleaner) |

---

## Troubleshooting

### "PHP is not recognized as a command"
- Make sure you added `C:\php` to your System PATH
- Open a **new** Command Prompt after changing PATH

### "pdo_pgsql extension not loaded"
- Make sure you uncommented `extension=pdo_pgsql` in `php.ini`
- Make sure you're editing the correct `php.ini` (run `php --ini` to find it)

### "Connection refused" to PostgreSQL
- Make sure PostgreSQL service is running:
  - Press `Win + R`, type `services.msc`
  - Find "postgresql-x64-16" and make sure it's "Running"
  - If not, right-click and select "Start"

### "Authentication failed"
- Double-check the password in `config.php`
- Make sure you're using the password you set during PostgreSQL installation

### Can't find psql command
- PostgreSQL bin folder needs to be in PATH, or use the full path:
  ```
  "C:\Program Files\PostgreSQL\16\bin\psql"
  ```

---

## Quick Start Summary

```cmd
# After installing PHP and PostgreSQL:

# 1. Create database
"C:\Program Files\PostgreSQL\16\bin\psql" -U postgres -c "CREATE DATABASE protech_db;"

# 2. Import schema  
"C:\Program Files\PostgreSQL\16\bin\psql" -U postgres -d protech_db -f database.sql

# 3. Update config.php with your PostgreSQL password

# 4. Start server
cd C:\Users\johnd\Downloads\protech
php -S localhost:8000

# 5. Open browser to http://localhost:8000
```

That's it! You should now be able to use ProTech. 🎉

