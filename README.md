# ASC Library Management System

A comprehensive, modern, and scalable web application for managing college libraries, built with PHP 8, MySQL 8, and Bootstrap 5.

## 🎨 Features

### 🔐 User Authentication
- **Role-based Access Control**: Admin, Librarian, and Student roles
- **Secure Login System**: Password hashing and CAPTCHA protection
- **Password Recovery**: Forgot password functionality with email reset

### 📚 Cataloging Module
- **Books Management**: Complete CRUD operations with detailed metadata
- **Academic Coursework**: Research papers, theses, and dissertations
- **Electronic Resources**: Digital materials, databases, and online resources
- **Advanced Search**: Full-text search across all catalog types
- **Export Functionality**: CSV, Excel, and PDF export options

### 👥 Patron Management
- **Comprehensive Patron Records**: Student and staff information
- **Borrowing History**: Track all patron activities
- **Fine Management**: Automated fine calculation and payment tracking
- **Status Management**: Active, inactive, and suspended patron states

### 🔄 Circulation System
- **Borrow-Return Processing**: Streamlined checkout and return workflow
- **Due Date Management**: Automated due date calculation
- **Overdue Tracking**: Real-time overdue item monitoring
- **Fine Calculation**: Configurable fine rates and automatic calculation

### 📊 Reports & Analytics
- **Accession Lists**: Complete catalog reports with filtering
- **Patron Masterlists**: Comprehensive patron directory
- **Export Options**: Multiple format support (CSV, Excel, PDF)
- **Custom Date Ranges**: Flexible reporting periods

### 🛠️ Administration
- **Staff Management**: User account administration
- **System Settings**: Configurable library parameters
- **Audit Logs**: Complete activity tracking
- **Backup & Export**: Data backup and export functionality

## 🚀 Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx)
- Modern web browser

### Quick Installation

1. **Download/Clone the System**
   ```bash
   git clone [repository-url]
   cd asc-library-management
   ```

2. **Set Permissions**
   ```bash
   chmod 755 -R .
   chmod 777 config/
   chmod 777 uploads/
   ```

3. **Run Installation Script**
   - Navigate to `http://your-domain/install.php`
   - Follow the installation wizard:
     - Step 1: Database configuration
     - Step 2: Create database tables
     - Step 3: Create admin account
     - Step 4: Complete installation

4. **Access the System**
   - Go to `http://your-domain/index.php`
   - Login with your admin credentials

### Manual Installation

1. **Database Setup**
   ```sql
   CREATE DATABASE asc_library;
   ```

2. **Import Schema**
   ```bash
   mysql -u username -p asc_library < database/schema.sql
   ```

3. **Configure Database**
   - Edit `config/database.php` with your database credentials

4. **Create Admin User**
   ```sql
   INSERT INTO users (username, password, name, email, role) 
   VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@asclibrary.edu', 'admin');
   ```

## 🎨 Design & UI

### Modern & Futuristic Theme
- **Primary Colors**: Gold (#FFD700) and Royal Blue (#0033A0)
- **Typography**: Poppins font family for modern readability
- **Responsive Design**: Mobile-first approach with Bootstrap 5
- **Interactive Elements**: Smooth animations and hover effects

### Layout Structure
- **Sidebar Navigation**: Collapsible left sidebar with organized menu sections
- **Top Search Bar**: Global search functionality across all modules
- **Dashboard**: Real-time statistics and quick actions
- **Modal Forms**: Clean, focused input interfaces

## 📁 Project Structure

```
asc-library-management/
├── assets/
│   ├── css/
│   │   └── dashboard.css
│   └── js/
│       └── dashboard.js
├── auth/
│   ├── captcha.php
│   ├── forgot-password.php
│   ├── login.php
│   └── logout.php
├── cataloging/
│   ├── api/
│   │   ├── books.php
│   │   ├── academic-coursework.php
│   │   └── electronic-resources.php
│   ├── books.php
│   ├── academic-coursework.php
│   └── electronic-resources.php
├── circulation/
│   ├── api/
│   │   ├── circulation.php
│   │   └── search-patrons.php
│   └── borrow-return.php
├── config/
│   └── database.php
├── database/
│   └── schema.sql
├── includes/
│   ├── auth.php
│   └── navigation.php
├── patrons/
│   ├── api/
│   │   └── patrons.php
│   └── index.php
├── reports/
│   ├── accession-list.php
│   └── patron-masterlist.php
├── dashboard.php
├── index.php
├── install.php
└── README.md
```

## 🔧 Configuration

### System Settings
Access the system settings through the admin panel to configure:
- Maximum borrowing days
- Fine rates per day
- Maximum reservations per patron
- Library contact information
- System preferences

### Database Configuration
Edit `config/database.php` to update database connection settings:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'asc_library');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

## 👥 User Roles & Permissions

### Administrator
- Full system access
- User management
- System configuration
- All CRUD operations
- Reports and exports

### Librarian
- Catalog management
- Patron management
- Circulation operations
- Reports generation
- Limited administrative functions

### Student
- View catalog
- Check borrowing history
- Reserve resources
- Limited access to personal information

## 🔒 Security Features

- **Password Hashing**: Secure password storage using PHP's password_hash()
- **SQL Injection Protection**: Prepared statements throughout
- **XSS Prevention**: Input sanitization and output escaping
- **CSRF Protection**: Token-based form protection
- **Session Management**: Secure session handling
- **CAPTCHA Integration**: Bot protection on login forms
- **Audit Logging**: Complete activity tracking

## 📊 Database Schema

### Core Tables
- **users**: System user accounts and authentication
- **books**: Book catalog with comprehensive metadata
- **academic_coursework**: Research papers and academic materials
- **electronic_resources**: Digital resources and online materials
- **patrons**: Library member information
- **circulation**: Borrowing and return transactions
- **reservations**: Resource reservation system
- **inventory**: Stock management and acquisition
- **audit_logs**: System activity tracking
- **system_settings**: Configurable system parameters

## 🚀 Performance Optimization

- **Database Indexing**: Optimized queries with proper indexing
- **Pagination**: Efficient data loading for large datasets
- **Caching**: Session-based caching for frequently accessed data
- **Responsive Images**: Optimized image handling
- **Minified Assets**: Compressed CSS and JavaScript

## 🔄 Backup & Maintenance

### Automated Backups
- Database backup functionality
- Export options for all data types
- Scheduled backup recommendations

### Maintenance Tasks
- Regular database optimization
- Log file cleanup
- System updates and patches

## 📱 Mobile Responsiveness

The system is fully responsive and optimized for:
- **Desktop**: Full feature access with sidebar navigation
- **Tablet**: Adapted layout with touch-friendly controls
- **Mobile**: Streamlined interface with essential functions

## 🆘 Support & Troubleshooting

### Common Issues

1. **Database Connection Errors**
   - Verify database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Check database permissions

2. **Permission Errors**
   - Set proper file permissions (755 for directories, 644 for files)
   - Ensure web server has write access to config and uploads directories

3. **Login Issues**
   - Clear browser cache and cookies
   - Verify CAPTCHA is working
   - Check user account status

### Getting Help
- Check the system logs in `logs/` directory
- Review audit logs in the admin panel
- Contact system administrator for technical support

## 🔮 Future Enhancements

- **Mobile App**: Native mobile application
- **Advanced Analytics**: Detailed usage statistics and insights
- **Integration APIs**: Third-party system integration
- **Multi-language Support**: Internationalization
- **Advanced Search**: Elasticsearch integration
- **Automated Notifications**: Email and SMS alerts

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📞 Contact

For support and inquiries:
- Email: support@asclibrary.edu
- Phone: +1-234-567-8900
- Website: https://asclibrary.edu

---

**ASC Library Management System** - Modernizing library operations with cutting-edge technology.