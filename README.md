# 🏪 NexInvent - Smart Inventory Management System

Welcome to NexInvent, your next-generation inventory and sales management solution! 🚀

## 🌟 Features

- 📊 Real-time inventory tracking
- 💰 Sales order management
- 🛍️ Purchase order processing
- 👥 Customer relationship management
- 👷 Employee management
- 📈 Analytics and reporting
- 🔐 Role-based access control
- 📱 Responsive design

## 🛠️ System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP/WAMP/MAMP (for local development)
- Modern web browser (Chrome, Firefox, Safari, Edge)

## 🚀 Quick Start Guide

1. **Clone the Repository**
   ```bash
   git clone https://github.com/Tokise/stock.git
   ```

2. **Database Setup**
   - Create a MySQL database named 'nexinvent'
   - Import the database schema from `src/modules/database.sql`
   ```bash
   mysql -u your_username -p nexinvent < src/modules/database.sql
   ```

3. **Configuration**
   - Navigate to `src/config/`
   - Copy `db.example.php` to `db.php`
   - Update database credentials in `db.php`

4. **Default Login Credentials**
   ```
   Admin Account:
   Username: admin
   Password: password123

   Test Customer Account:
   Username: customer
   Password: customer123
   ```

## 👥 User Roles & Permissions

### 🔑 Admin
- Full system access
- Manage users and permissions
- Access all reports and settings

### 👨‍💼 Manager
- Manage inventory
- Process sales and purchases
- View reports
- Manage suppliers
- View employee information

### 👷 Employee
- View inventory
- Create sales orders
- View basic reports
- Manage assigned tasks

### 🛍️ Customer
- View product catalog
- Place orders
- Track order status
- View order history
- Update profile

## 🎯 Key Features by Module

### 📦 Inventory Management
- Real-time stock tracking
- Low stock alerts
- Stock movement history
- Barcode support
- Category management

### 💰 Sales Management
- Create/manage sales orders
- Customer management
- Invoice generation
- Payment tracking
- Sales analytics

### 🛒 Purchase Management
- Create purchase orders
- Supplier management
- Stock receiving
- Purchase analytics
- Cost tracking

### 👥 Customer Portal
- Self-service ordering
- Order history
- Profile management
- Wishlist functionality
- Order tracking

## 📊 Reports & Analytics

- Sales performance
- Inventory status
- Purchase analysis
- Customer insights
- Employee performance

## 🔒 Security Features

- Role-based access control
- Secure password hashing
- Session management
- Activity logging
- Data encryption

## 🛠️ Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials
   - Verify MySQL service is running
   - Confirm database exists

2. **Permission Issues**
   - Check file/folder permissions
   - Verify user role permissions
   - Clear browser cache

3. **Session Errors**
   - Check PHP session configuration
   - Clear session data
   - Verify tmp folder permissions

## 🤝 Support & Contribution

- Report issues on GitHub
- Submit pull requests
- Join our community forum
- Contact support team

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🙏 Acknowledgments

- Bootstrap team
- DataTables library
- Chart.js
- Select2
- SweetAlert2

---
Made with ❤️ by BSIT Students