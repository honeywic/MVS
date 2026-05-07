# Mzumbe Voting System (MVS)

## Overview

Mzumbe Voting System (MVS) is a comprehensive web-based application designed to streamline and secure the election process for the Mzumbe Secondary School Students' Government. Built with modern web technologies, MVS ensures a transparent, efficient, and user-friendly voting experience for students, faculty, and administrators.

## Features

### For Students
- **Secure Registration**: Easy account creation with validation
- **Authenticated Voting**: Login-protected voting system
- **Real-time Updates**: Live vote statistics during elections
- **Responsive Interface**: Accessible on various devices

### For Administrators
- **Candidate Management**: Add and manage election candidates
- **Post Management**: Create and configure election positions
- **Dashboard Analytics**: Comprehensive election monitoring
- **Report Generation**: PDF reports using FPDF library

### Security Features
- **PIN Reset Functionality**: Secure password recovery
- **Session Management**: Proper logout and session handling
- **Input Validation**: Protection against common web vulnerabilities

## Technologies Used

- **Backend**: PHP 7+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **PDF Generation**: FPDF Library
- **Server**: Apache (via XAMPP)

## Installation

### Prerequisites
- XAMPP (or similar Apache/MySQL/PHP stack)
- Git

### Setup Steps

1. **Clone the Repository**
   ```bash
   git clone https://github.com/honeywic/MVS.git
   cd MVS
   ```

2. **Database Setup**
   - Start XAMPP and ensure Apache and MySQL are running
   - Create a new database named `mvs`
   - Import the database schema:
     ```sql
     mysql -u root -p mvs < sql/mvs.sql
     ```

3. **Configuration**
   - Update database credentials in `config/config.php`
   - Ensure the project is placed in `htdocs` directory for XAMPP

4. **Access the Application**
   - Open browser and navigate to `http://localhost/MVS`

## Usage

### Student Workflow
1. Register an account
2. Login with credentials
3. View available positions and candidates
4. Cast votes securely
5. View live voting statistics

### Admin Workflow
1. Login to admin panel
2. Add/manage election posts
3. Add/manage candidates
4. Monitor election progress
5. Generate reports

## Project Structure

```
MVS/
├── index.php                 # Main landing page
├── assets/                   # Static assets
│   ├── css/                 # Stylesheets
│   ├── js/                  # JavaScript files
│   └── images/              # Image resources
├── auth/                    # Authentication modules
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   └── reset_pin.php
├── config/                  # Configuration files
├── manage/                  # Admin management pages
├── vote-handler/            # Voting logic and statistics
├── sql/                     # Database schema
└── fpdf/                    # PDF generation library
```

## Contributing

We welcome contributions to improve MVS! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## Security Considerations

- Regularly update PHP and MySQL versions
- Use prepared statements for database queries
- Implement HTTPS in production
- Regularly audit code for vulnerabilities

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support or questions, please contact the development team or create an issue in the repository.

---

**Mzumbe Secondary School Students' Government Election System**
*Empowering student voices through technology*</content>
<parameter name="filePath">c:\xampp\htdocs\MVS\README.md