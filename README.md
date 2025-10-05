# Resort Venue Booking System

A comprehensive web application for booking resort venues built with PHP, MySQL, HTML, CSS, and JavaScript.

## Features

### User Features
- **User Registration & Authentication**: Secure signup/login with email verification
- **Forgot Password**: Password reset via email with secure token-based links
- **Venue Browsing**: Interactive venue catalog with images, amenities, and pricing
- **Multi-Step Booking Process**: Guided 4-step booking flow with real-time validation
- **GCash Payment Integration**: Upload payment receipts for booking confirmation
- **User Dashboard**: View booking history and manage profile
- **Email Notifications**: Automated emails for inquiries and password resets

### Admin Features
- **Admin Dashboard**: Comprehensive overview with statistics and recent bookings
- **Venue Management**: Add, edit, and manage venue listings with images and QR codes
- **Booking Management**: View, update, and manage booking statuses
- **User Management**: Administer user accounts and roles
- **GCash Receipt Review**: View uploaded payment receipts for verification

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
   - View venue's GCash QR code
   - Upload payment receipt screenshot
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

### Security Features
- **Password Hashing**: bcrypt for secure password storage
- **CSRF Protection**: Session-based request validation
- **Input Sanitization**: XSS prevention and SQL injection protection
- **File Upload Security**: Type validation and secure file handling