-- database_upgrade.sql
-- Գործարկեք մեկ անգամ phpMyAdmin-ում (alateva_db -> SQL). Ավելացնում է 3 նոր աղյուսակ, գոյություն ունեցողները չի փոխում։

-- Զամբյուղը պահվում է բազայում (սարքերի միջև համաժամանակեցված)
CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cart_user_book (user_id, book_id),
    INDEX idx_cart_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Գաղտնաբառի վերականգնման թոքեններ (պահվում է միայն SHA-256 հեշը)
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reset_token (token_hash),
    INDEX idx_reset_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- «Ծանուցել, երբ հասանելի լինի» բաժանորդագրություններ
CREATE TABLE IF NOT EXISTS stock_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_notify_book_user (book_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
