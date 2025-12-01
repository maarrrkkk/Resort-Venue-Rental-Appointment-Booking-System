# Resort Venue Booking System

A comprehensive web application for booking resort venues built with PHP, MySQL, HTML, CSS, and JavaScript. Features secure payment processing with PayPal integration and manual payment options.

## Features

### User Features
- **User Registration & Authentication**: Secure signup/login with email verification
- **Forgot Password**: Password reset via email with secure token-based links
- **Venue Browsing**: Interactive venue catalog with images, amenities, and pricing
- **Multi-Step Booking Process**: Guided 4-step booking flow with real-time validation
- **Payment Integration**: Secure PayPal payments and manual payment options (GCash receipts)
- **User Dashboard**: View booking history and manage profile
- **Email Notifications**: Automated emails for inquiries and password resets

### Admin Features
- **Admin Dashboard**: Comprehensive overview with statistics and recent bookings
- **Venue Management**: Add, edit, and manage venue listings with images and QR codes
- **Booking Management**: View, update, and manage booking statuses
- **User Management**: Administer user accounts and roles
- **Payment Management**: Review PayPal transactions and uploaded payment receipts

### Technical Features
- **Environment Configuration**: Secure configuration via `.env` file
- **AJAX API**: Real-time interactions without page reloads
- **Responsive Design**: Mobile-friendly interface with Bootstrap
- **File Upload System**: Secure image upload with validation
- **Session Management**: Secure user sessions and authentication
- **Database Auto-Setup**: Automatic database creation and seeding

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

## Booking Process

The application features a comprehensive 4-step booking process:

1. **Step 1: Select Venue & Date**
   - Browse available venues with images and details
   - Select event date
   - Real-time venue preview

2. **Step 2: Event Details**
   - Choose event type (Wedding, Birthday, Conference, etc.)
   - Specify number of guests
   - Add special requests

3. **Step 3: Confirmation**
   - Review all booking details
   - Calculate total cost (venue + extra guests)
   - Confirm booking details

4. **Step 4: Payment**
    - Choose payment method (PayPal or Manual)
    - PayPal: Secure online payment with PayPal buttons
    - Manual: View venue's GCash QR code and upload receipt
    - Submit booking for admin approval

## User Roles

- **Client**: Can browse venues, make bookings, upload payments, view booking history
- **Admin**: Can manage venues, view all bookings, update booking statuses, manage users

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

### PayPal Configuration
```env
PAYPAL_CLIENT_ID=your-paypal-client-id
PAYPAL_SECRET=your-paypal-secret
PAYPAL_MODE=sandbox  # Use 'sandbox' for testing, 'live' for production
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

## PayPal Configuration (Sandbox Setup)

The application supports secure PayPal payments for instant booking confirmation. Follow these steps to set up PayPal sandbox for testing.

### Step 1: Create PayPal Developer Account
1. Go to [PayPal Developer Dashboard](https://developer.paypal.com/developer/applications/)
2. Sign in with your PayPal account (or create one if you don't have it)
3. If prompted, complete the developer account setup

### Step 2: Create a PayPal App
1. In the Developer Dashboard, click **Apps & Credentials**
2. Click **Create App**
3. Choose **Merchant** as the app type
4. Enter an app name (e.g., "Resort Booking System")
5. Click **Create App**

### Step 3: Get API Credentials
1. After creating the app, you'll see your **Client ID** and **Client Secret**
2. Copy the Client ID and Client Secret (keep them secure)
3. For sandbox testing, these are automatically sandbox credentials

### Step 4: Configure PayPal Settings in .env

Add your PayPal credentials to the `.env` file:

```env
# PayPal Configuration
PAYPAL_CLIENT_ID=AYour_Client_ID_Here
PAYPAL_SECRET=Your_Client_Secret_Here
PAYPAL_MODE=sandbox  # Use 'sandbox' for testing, 'live' for production
```

### Step 5: Test the Integration
1. Start your local server
2. Navigate to the booking page
3. Complete steps 1-3 of the booking process
4. On step 4, select PayPal as payment method
5. Click the PayPal button and complete a test payment using sandbox accounts

**Important Notes:**
- Always use `PAYPAL_MODE=sandbox` for development and testing
- Sandbox payments don't involve real money
- Create test buyer/seller accounts in PayPal Developer Dashboard for testing
- Switch to `PAYPAL_MODE=live` and use live credentials only when ready for production
- Keep your PayPal credentials secure and never commit them to version control

### Optional: Webhook Setup (Recommended for Production)
1. In your PayPal app settings, add a webhook URL: `https://yourdomain.com/api/paypalWebhook.php`
2. Subscribe to events: `PAYMENT.CAPTURE.COMPLETED`, `PAYMENT.CAPTURE.REFUNDED`
3. Note the Webhook ID for additional security verification

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

## Database Schema

The application uses MySQL with the following main tables:

- **users**: User accounts with authentication and profile data
- **venues**: Venue listings with images, amenities, and pricing
- **bookings**: Booking records with payment tracking
- **settings**: Application configuration settings

## API Endpoints

### Authentication
- `POST /api/login.php` - User login
- `POST /api/register.php` - User registration
- `POST /api/forgotPassword.php` - Password reset request
- `POST /api/resetPassword.php` - Password reset confirmation
- `POST /api/logout.php` - User logout

### Venues
- `GET /api/venues.php` - List all venues
- `GET /api/venues.php?id={id}` - Get specific venue
- `POST /api/venues.php` - Create/update venue (admin only)

### Bookings
- `GET /api/bookings.php` - Get user bookings
- `PUT /api/bookings.php` - Update booking status (admin only)

### Admin
- `GET /api/dashboard.php` - Dashboard statistics
- `GET /api/users.php` - User management

## Directory Structure

- `index.php`: Main application router
- `admin/`: Admin dashboard and management pages
- `api/`: RESTful API endpoints
- `assets/`: Static files (CSS, JS, images, uploads)
- `config/`: Database and application configuration
- `includes/`: Shared PHP utilities and templates
- `lib/`: Third-party libraries (PHPMailer)
- `pages/`: Frontend pages (home, login, booking, etc.)

## Technologies Used

### Backend
- **PHP 7.4+**: Server-side scripting and API development
- **MySQL**: Database management with PDO
- **PHPMailer**: Email sending functionality
- **PayPal REST API**: Secure payment processing
- **Sessions**: User authentication and session management

### Frontend
- **HTML5/CSS3**: Semantic markup and responsive styling
- **JavaScript (ES6+)**: Dynamic interactions and AJAX calls
- **Bootstrap 5**: Responsive UI components and styling
- **Font Awesome**: Icons and visual elements

### Development Tools
- **Environment Configuration**: `.env` file for secure configuration
- **AJAX API**: RESTful endpoints for real-time interactions
- **File Upload System**: Secure image handling with validation
- **Form Validation**: Client and server-side validation
- **PayPal SDK**: JavaScript SDK for payment integration

### Security Features
- **Password Hashing**: bcrypt for secure password storage
- **CSRF Protection**: Session-based request validation
- **Input Sanitization**: XSS prevention and SQL injection protection
- **File Upload Security**: Type validation and secure file handling
- **Payment Security**: PayPal API verification and webhook validation