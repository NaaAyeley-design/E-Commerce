# E-Commerce Authentication System

A complete authentication system built with PHP following MVC architecture principles. This system provides user registration, login, password reset, and session management functionality.

## 🏗️ Architecture

This project follows the **Model-View-Controller (MVC)** pattern:

- **Models** (`classes/`): Handle data logic and database interactions
- **Views** (`views/`): Handle presentation and user interface
- **Controllers** (`controllers/`): Handle business logic and coordinate between models and views
- **Actions** (`actions/`): Handle form submissions and API endpoints

## 📁 Project Structure

```
ecommerce-authent/
├── actions/                 # Form submission handlers
│   ├── login_customer_action.php
│   ├── register_customer_action.php
│   ├── logout_action.php
│   └── forgot_password_action.php
├── classes/                 # Model classes
│   └── customer_class.php
├── controllers/             # Controller functions
│   └── customer_controller.php
├── db/                     # Database related files
│   ├── db_connection.php
│   └── schema.sql
├── js/                     # JavaScript files
│   ├── login.js
│   └── register.js
├── middleware/             # Authentication middleware
│   └── auth_middleware.php
├── views/                  # View templates
│   ├── login.php
│   ├── register.php
│   ├── dashboard.php
│   └── forgot_password.php
└── index.php              # Home page
```

## 🚀 Features

### ✅ Completed Features

- **User Registration**
  - Form validation (client-side and server-side)
  - Email uniqueness check
  - Password hashing with bcrypt
  - Progress indicator during registration

- **User Login**
  - Email/password authentication
  - Remember me functionality
  - Session management
  - Automatic redirect to dashboard

- **Password Reset**
  - Forgot password form
  - Token-based password reset
  - Secure token generation and validation

- **Session Management**
  - Secure session handling
  - Remember me cookies
  - Session cleanup on logout
  - Authentication middleware

- **User Dashboard**
  - Profile information display
  - Quick actions
  - Responsive design
  - Modern UI with animations

- **Security Features**
  - Password hashing with bcrypt
  - SQL injection prevention with prepared statements
  - CSRF protection ready
  - Input validation and sanitization

## 🛠️ Installation & Setup

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or XAMPP/WAMP

### Setup Steps

1. **Clone/Download the project**
   ```bash
   git clone <repository-url>
   cd ecommerce-authent
   ```

2. **Database Setup**
   - Create a MySQL database named `shoppn`
   - Import the schema: `mysql -u root -p shoppn < db/schema.sql`
   - Or run the SQL commands from `db/schema.sql` in your MySQL client

3. **Configure Database Connection**
   - Update database credentials in `db/db_connection.php` if needed:
   ```php
   $this->conn = new mysqli("localhost", "username", "password", "shoppn");
   ```

4. **Web Server Configuration**
   - Place the project in your web server's document root
   - Ensure PHP sessions are enabled
   - Set proper file permissions

5. **Access the Application**
   - Open your browser and navigate to `http://localhost/ecommerce-authent`
   - Or `http://localhost/ecommerce-authent/views/login.php` for direct login

## 📋 Usage

### User Registration

1. Navigate to the registration page
2. Fill in all required fields:
   - Full Name
   - Email Address
   - Password (minimum 6 characters)
   - Country
   - City
   - Contact Number
3. Click "Create My Account"
4. You'll be redirected to the login page upon successful registration

### User Login

1. Navigate to the login page
2. Enter your email and password
3. Optionally check "Remember me" for persistent login
4. Click "Sign In"
5. You'll be redirected to the dashboard upon successful login

### Password Reset

1. On the login page, click "Forgot password?"
2. Enter your email address
3. Click "Send Reset Link"
4. Follow the instructions (in a real application, you'd receive an email)

### User Dashboard

After logging in, you'll see:
- Your profile information
- Quick action buttons
- Account statistics (placeholder for future features)
- Logout option

## 🔧 Technical Details

### Database Schema

The system uses the following main tables:

- **customer**: Stores user information
- **password_resets**: Manages password reset tokens
- **user_roles**: Defines user roles and permissions
- **user_sessions**: Optional session storage
- **audit_log**: Optional activity logging

### Security Measures

- **Password Security**: All passwords are hashed using `password_hash()` with bcrypt
- **SQL Injection Prevention**: All database queries use prepared statements
- **Session Security**: Secure session handling with proper cleanup
- **Input Validation**: Both client-side and server-side validation
- **Token Security**: Cryptographically secure random tokens for password reset

### File Structure Best Practices

- **Separation of Concerns**: Clear separation between models, views, and controllers
- **Modular Design**: Each component has a specific responsibility
- **Reusable Code**: Functions and classes are designed for reusability
- **Security First**: Security considerations built into every component

## 🎨 UI/UX Features

- **Responsive Design**: Works on desktop, tablet, and mobile devices
- **Modern UI**: Clean, professional design with gradients and animations
- **User Feedback**: Loading states, success/error messages, and form validation
- **Accessibility**: Proper form labels, keyboard navigation, and focus states
- **Progressive Enhancement**: Works without JavaScript, enhanced with it

## 🔮 Future Enhancements

- Email verification system
- Two-factor authentication
- Social login integration
- User profile editing
- Admin panel
- API endpoints for mobile apps
- Advanced security features (rate limiting, etc.)

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `db/db_connection.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **Session Issues**
   - Check PHP session configuration
   - Ensure proper file permissions
   - Clear browser cookies if needed

3. **Form Submission Errors**
   - Check JavaScript console for errors
   - Verify file paths are correct
   - Ensure all required files are present

### Debug Mode

To enable debug mode, add this to the top of PHP files:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📝 License

This project is created for educational purposes as part of an e-commerce platform development course.

## 👥 Contributing

This is a learning project. Feel free to:
- Report bugs
- Suggest improvements
- Add new features
- Improve documentation

## 📞 Support

For questions or issues, please refer to the course materials or contact your instructor.

---

**Note**: This is a demonstration project. In a production environment, additional security measures, error handling, and testing would be required.

