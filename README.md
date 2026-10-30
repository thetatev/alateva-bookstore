# Alateva - Online Bookstore Platform

A fully functional full-stack e-commerce web application for an online bookstore. 

## Features
- **User Authentication:** Secure login/registration system with password hashing.
- **Dynamic Cart & Checkout:** Database-driven shopping cart with real-time price calculations and AJAX updates.
- **Admin Dashboard:** Complete inventory management, order tracking, and user administration.
- **Book Assistant Chatbot:** A custom PHP-based intelligent assistant that recommends books, checks stock, and manages orders using session context.
- **Security:** Implemented CSRF protection, prepared SQL statements (preventing SQL Injection), and XSS filtering.

## Technologies Used
- **Backend:** PHP (OOP)
- **Database:** MySQL
- **Frontend:** HTML, CSS, JavaScript (AJAX)
- **Tools:** Git, GitHub

## Setup Instructions
1. Clone the repository.
2. Import the provided SQL files (`database_schema.sql`, `more_books.sql`) into your MySQL database.
3. Configure your database credentials in `config/database.php`.
4. Run the project on a local server (e.g., XAMPP, MAMP).
