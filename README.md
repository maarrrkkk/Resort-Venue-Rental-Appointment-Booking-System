# Resort Venue Booking System

A web application for booking resort venues built with PHP, MySQL, HTML, CSS, and JavaScript.

## Features

- User registration and authentication
- Forgot password functionality with email reset links
- Venue browsing and booking
- Admin panel for managing venues and bookings
- AJAX API for real-time interactions
- Email notifications for inquiries and password resets

## Installation

1. Set up a PHP environment with MySQL (e.g., XAMPP).
2. Copy `.env.example` to `.env` and configure your environment variables (database, email, etc.).
3. Place the project in your web server's root directory.
4. Access the application via your browser (the database will be created automatically).
5. Configure Gmail App Password for email functionality (see Email Configuration section).

## Environment Configuration

Copy `.env.example` to `.env` and update the values according to your setup:

```bash
cp .env.example .env
```

### Database Configuration
```env
DB_HOST=localhost
DB_USER=root
DB_PASS=your_mysql_password
DB_NAME=resort_booking
DB_PORT=3306
```

### Default Users
The application will create these default users on first run:
```env
ADMIN_NAME=Resort Manager
ADMIN_EMAIL=admin@resort.com
ADMIN_PASSWORD=admin123

USER1_NAME=John Smith
USER1_EMAIL=john@example.com
USER1_PASSWORD=admin123
```

### Application Settings
```env
BASE_URL=http://localhost
SITE_NAME=Paradise Resort
CONTACT_EMAIL=events@paradiseresort.com
CONTACT_PHONE=+1 (555) 123-4567
```

**Important:** Never commit your `.env` file to version control as it contains sensitive information. The `.gitignore` file is configured to exclude it.

## Email Configuration (Gmail Setup)

The application uses Gmail SMTP to send emails for inquiries and password reset functionality. You need to set up a Gmail App Password for secure email sending.

### Step 1: Enable 2-Factor Authentication
1. Go to your Google Account settings
2. Navigate to **Security** → **2-Step Verification**
3. Enable 2-factor authentication if not already enabled

### Step 2: Generate App Password
1. In your Google Account, go to **Security** → **2-Step Verification**
2. Scroll down to **App passwords**
3. Click **Select app** → Choose **Mail**
4. Click **Select device** → Choose **Other (custom name)**
5. Enter a name like "Resort Booking System"
6. Click **Generate**
7. Copy the 16-character password (it will look like: `abcd-efgh-ijkl-mnop`)

### Step 3: Configure Email Settings

#### For Inquiry Emails (`api/sendInquiry.php`):
```php
$mail->Username = 'your-gmail@gmail.com'; // Replace with your Gmail address
$mail->Password = 'abcd-efgh-ijkl-mnop'; // Replace with your 16-character app password
$mail->setFrom('your-gmail@gmail.com', 'Resort Booking System');
```

#### For Password Reset Emails (`api/forgotPassword.php`):
```php
$mail->Username = 'your-gmail@gmail.com'; // Replace with your Gmail address
$mail->Password = 'abcd-efgh-ijkl-mnop'; // Replace with your 16-character app password
$mail->setFrom('your-gmail@gmail.com', 'Resort Booking System');
```

**Important Notes:**
- Use your actual Gmail address and the 16-character app password (not your regular password)
- The app password has spaces in the format shown, but remove spaces when pasting into the code
- Keep your app password secure and don't share it
- You can generate separate app passwords for different purposes if needed

## Directory Structure

- `index.php`: Homepage
- `admin/`: Admin dashboard
- `client/`: Client dashboard
- `includes/`: Shared PHP files
- `api/`: AJAX endpoints
- `assets/`: CSS, JS, images
- `database/`: SQL schema

## Technologies Used

- PHP
- MySQL
- HTML/CSS/JavaScript
- Bootstrap (placeholder)