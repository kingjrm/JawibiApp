# Jollibee Ordering System

A fully functional PHP-based ordering system for Jollibee with user authentication, menu browsing, cart management, and order placement.

## Features
- User registration and login
- Browse menu items
- Add items to cart
- Update cart quantities
- Place orders
- View order history

## Setup Instructions

1. **Database Setup**:
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `jollibee`
   - Import the `setup.sql` file to create tables and insert sample data

2. **File Structure**:
   - Place all files in your web server's root directory (e.g., `htdocs/jawibi/jawibi/`)
   - Ensure the `assets/` folder contains the required images

3. **Configuration**:
   - Update `config/db.php` with your database credentials if different from default XAMPP settings

4. **Access the Site**:
   - Navigate to `http://localhost/jawibi/jawibi/index.php`

## Technologies Used
- PHP 7+
- MySQL
- Tailwind CSS
- HTML5
- JavaScript (minimal)

## Database Schema
- `users`: User accounts
- `menu_items`: Available food items
- `orders`: Customer orders
- `order_items`: Items in each order

## Security Notes
- Passwords are hashed using `password_hash()`
- Sessions are used for user authentication
- Input validation is basic; consider adding more robust validation for production use