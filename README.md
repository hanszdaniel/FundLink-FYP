# FundLink – Shared Account Financial Management System (FYP)

FundLink is a Laravel web application developed as a Final Year Project to help individuals and groups manage personal and shared expenses with better transparency and budget control.

## Key Features
- Personal & shared expense tracking
- Shared accounts (member management / invite flow)
- Custom categories with spending limits
- Monthly budget setup for shared accounts
- Financial summaries & reports
- PDF statement generation
- Email-based invite/verification (SMTP)

## Tech Stack
- Laravel (PHP)
- MySQL
- HTML, CSS, JavaScript
- SMTP Email
- PDF Generation

## How to Run (Local)
1. Clone the repository
2. Install dependencies:
   - `composer install`
3. Create environment file:
   - Copy `.env.example` to `.env`
4. Generate app key:
   - `php artisan key:generate`
5. Configure database in `.env`
6. Run migrations:
   - `php artisan migrate`
7. Start the app:
   - `php artisan serve`

## Screenshots
![Login](<img width="603" height="387" alt="image" src="https://github.com/user-attachments/assets/75d49776-dad3-4746-b447-5ec04b5ca7f2" />)
![Dashboard](screenshots/dashboard.png)
![Transaction](screenshots/transaction.png)
![Shared Account](screenshots/shared-account.png)
![Statement](screenshots/statement.png)
<img width="603" height="387" alt="image" src="https://github.com/user-attachments/assets/75d49776-dad3-4746-b447-5ec04b5ca7f2" />

