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
2. Clone or download the project to your web server's root directory (e.g., `htdocs/` for XAMPP).
3. Copy `.env.example` to `.env`: `cp .env.example .env`
4. Edit `.env` with your local configuration (database credentials, Gmail settings, etc.).
5. **Important**: Update `BASE_URL` in `.env` to match your local setup:
   - If project is in `htdocs/resort-venue-rental-appointment-booking-system/`: `BASE_URL=http://localhost/resort-venue-rental-appointment-booking-system`
   - If project is directly in `htdocs/`: `BASE_URL=http://localhost`
6. Access the application via your browser (the database will be created automatically).
7. Set up Gmail App Password for email functionality (see Email Configuration section).

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

### SMTP Email Configuration
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-gmail@gmail.com
SMTP_PASSWORD=your-16-character-app-password
SMTP_ENCRYPTION=tls
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

### Step 3: Configure Email Settings in .env

Add your Gmail credentials to the `.env` file:

```env
# PHPMailer SMTP Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-gmail@gmail.com
SMTP_PASSWORD=abcd-efgh-ijkl-mnop
SMTP_ENCRYPTION=tls
```

**Important Notes:**
- Use your actual Gmail address and the 16-character app password (not your regular password)
- The app password has spaces in the format shown, but remove spaces when pasting into the `.env` file
- Keep your app password secure and don't share it
- You can generate separate app passwords for different purposes if needed
- The application automatically uses these settings for both inquiry emails and password reset emails

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