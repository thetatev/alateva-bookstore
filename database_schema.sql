-- database_schema.sql
-- Նոր հատկությունների աղյուսակները (cart_items, password_resets, stock_notifications) գտնվում են database_upgrade.sql-ում։
-- ԽՆԴՐՈՒՄ ԵՆՔ ՈՒՇԱԴՐՈՒԹՅՈՒՆ. Այս ֆայլը վերականգնված է PHP կոդից (ձեր բազայի իրական սխեման ինձ չէր տրվել)։
-- Օգտագործեք միայն որպես տեղեկանք՝ համեմատելու ձեր alateva_db-ի հետ։ Ամեն ինչ IF NOT EXISTS է, գոյություն ունեցող աղյուսակները չեն փոխվում։

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    member_since DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    name_hy VARCHAR(100) NOT NULL,
    icon VARCHAR(60) DEFAULT 'fa-book',
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    title_hy VARCHAR(200) NOT NULL,
    author VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2) DEFAULT NULL,
    stock INT NOT NULL DEFAULT 0,
    category_id INT DEFAULT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,
    rating DECIMAL(2,1) NOT NULL DEFAULT 0,
    reviews_count INT NOT NULL DEFAULT 0,
    publisher VARCHAR(150) DEFAULT NULL,
    pages INT DEFAULT NULL,
    year INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_books_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    reviewer_name VARCHAR(200) DEFAULT NULL,
    rating TINYINT NOT NULL,
    title VARCHAR(150) DEFAULT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review_user_book (book_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    user_id INT DEFAULT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,
    shipping_method VARCHAR(50) DEFAULT NULL,
    shipping_price DECIMAL(10,2) DEFAULT 0,
    payment_method VARCHAR(50) DEFAULT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    notes TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_orders_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    book_title VARCHAR(200) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    INDEX idx_items_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Եթե ձեր աղյուսակները արդեն կան, խորհուրդ է տրվում ավելացնել (եթե դեռ չկան) հետևյալ ինդեքսները.
--   ALTER TABLE orders ADD UNIQUE KEY uq_order_number (order_number);
--   ALTER TABLE orders ADD INDEX idx_orders_user (user_id);
--   ALTER TABLE reviews ADD UNIQUE KEY uq_review_user_book (book_id, user_id);
-- Նոր ադմինի գաղտնաբառի հեշ ստանալու համար՝  php -r "echo password_hash('ՁԵՐ_ԳԱՂՏՆԱԲԱՌԸ', PASSWORD_DEFAULT);"
-- ապա՝ UPDATE users SET password_hash='<հեշը>' WHERE email='admin@alateva.com';
