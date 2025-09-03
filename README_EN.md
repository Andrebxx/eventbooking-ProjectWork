# EventBooking 🎵

**EventBooking** is a modern web platform for managing and booking musical and cultural events. The system offers an elegant user interface and a comprehensive administration panel for event organizers.

## 🚀 Key Features

### For Users
- **Secure Registration & Login**: Authentication system with email validation
- **Personalized User Profile**: Management of personal data and profile photos
- **Event Catalog**: Display of all available events with advanced filters
- **Online Booking**: Booking system with support for multiple seats
- **Payment Management**: Secure integration for credit card payments
- **Booking History**: View of all completed bookings

### For Administrators
- **Administrative Dashboard**: Complete panel for system management
- **Event Management**: Creation, editing, and deletion of events
- **User Management**: Administration of user accounts
- **Booking Monitoring**: View and management of all bookings
- **Security System**: Protection against brute force attacks

## 🛠️ Technologies Used

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5.3.3
- **Backend**: PHP 8.0
- **Database**: MySQL 8.0
- **Security**: Password hashing, CSRF protection, input validation
- **UI/UX**: Font Awesome, Bootstrap Icons, custom CSS animations

## 📋 System Requirements

- **Web Server**: Apache/Nginx
- **PHP**: Version 8.0 or higher
- **MySQL**: Version 8.0 or higher
- **PHP Extensions**: mysqli, session, hash

## 🔧 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/eventbooking.git
   cd eventbooking
   ```

2. **Configure the Database**
   - Import the `my_projectworkgruppoquattro.sql` file into your MySQL database
   - Create a `password.php` file for database configuration:
   ```php
   <?php
   $servername = "localhost";
   $username = "your_username";
   $password = "your_password";
   $dbname = "my_projectworkgruppoquattro";

   $conn = new mysqli($servername, $username, $password, $dbname);
   $myDB = $conn; // Alias for compatibility
   ?>
   ```

3. **Configure Permissions**
   - Ensure the `Immagini/` folder is writable
   - Set appropriate permissions for profile photo uploads

4. **Access the System**
   - Visit `http://localhost/eventbooking`
   - Register a new account or use administrative credentials

## 📁 Project Structure

```
EventBooking/
├── index.php              # Main page
├── login.php              # Authentication system
├── register.php           # User registration
├── Eventi.php              # Event catalog
├── prenotazione.php        # Booking system
├── profilo.php             # User profile management
├── amministrazione.php     # Admin panel
├── navbar.php              # Navigation
├── footer.php              # Site footer
├── cookie_banner.php       # Cookie policy banner
├── 404.php                 # 404 error page
├── logout.php              # Logout
├── clear_attempts.php      # Login attempts reset
├── informazionilegali.php  # Legal information
├── Immagini/               # Graphic resources
│   ├── logo.png           # Main logo
│   ├── Eventi/            # Event images
│   └── foto_utenti/       # User profile photos
└── stili/                  # CSS stylesheets
    ├── stile_index.css    # Homepage styles
    ├── stile_eventi.css   # Events page styles
    ├── stile_login.css    # Login/register styles
    ├── stile_profilo.css  # Profile styles
    ├── stile_navfoo.css   # Navigation styles
    └── ...                # Other styles
```

## 🎯 Detailed Features

### Booking System
- Multiple bookings with participant name entry
- Automatic total price calculation
- Real-time seat availability management
- Email booking confirmation

### Administrative Panel
- Dashboard statistics with charts
- Complete CRUD for events and users
- Booking management with advanced filters
- Backup and restore system

### Security
- Protection against SQL Injection
- Secure password hashing with `password_hash()`
- Anti-brute force system for login
- Server-side validation of all inputs
- CSRF protection for critical forms

## 🚦 Project Status

- ✅ Complete authentication system
- ✅ Event and booking management
- ✅ Administrative panel
- ✅ Responsive and modern UI/UX
- ✅ Integrated payment system
- ✅ Image management and file upload

## 📝 License

This project is developed for educational purposes as part of the ProjectWork course.

## 👥 Developers

Project developed by **Group Four** for the ProjectWork 2025 course.

## 📧 Contact

For support or questions: **bassiandrea24@gmail.com**

---

⭐ If this project was helpful to you, leave a star on GitHub!
