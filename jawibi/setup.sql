-- Create database
CREATE DATABASE IF NOT EXISTS jollibee;
USE jollibee;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Menu items table
CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'delivered') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (item_id) REFERENCES menu_items(id)
);

-- Insert sample menu items
INSERT INTO menu_items (name, description, price, image) VALUES
('Chickenjoy', 'Crispy fried chicken with your choice of sides', 99.00, 'chickenjoy.jpg'),
('Jolly Spaghetti', 'Sweet and savory spaghetti with meat sauce', 49.00, 'spaghetti.jpg'),
('Tuna Pie', 'Flaky pastry filled with tuna', 39.00, 'tuna_pie.jpg'),
('Family Bucket Meal', '6 pcs Chickenjoy + Spaghetti', 599.00, 'deal1.jpg'),
('Spicy Chickenjoy', 'Extra spicy crispy chicken', 109.00, 'deal2.jpg');