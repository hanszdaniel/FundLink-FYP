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

## Demo Video

🎥 **System Montage Video (FundLink – Final Year Project)**  
This video demonstrates the main features of FundLink, including login, dashboard overview, transaction management, shared account functionality, and PDF statement generation.

👉 Watch here:  
https://drive.google.com/drive/folders/1NK9EmuCLHbLTtCSbtHKzsRxsb3SULhIt?usp=drive_link

## Screenshots

### Login Page
<img src="https://github.com/user-attachments/assets/75d49776-dad3-4746-b447-5ec04b5ca7f2" width="700"/>

### Dashboard
<img src="https://github.com/user-attachments/assets/c1609ec8-0a04-4098-9d30-914fa812d4bb" width="700"/>

### Add Transaction
<img src="https://github.com/user-attachments/assets/caf26f56-68aa-449d-a4a1-df8897b4720a" width="700"/>

### Shared Account
<img src="https://github.com/user-attachments/assets/da6075f4-577e-42ff-9851-ed2e0a2bc07b" width="700"/>

### PDF Statement
<img src="https://github.com/user-attachments/assets/8105d885-3dd1-4205-b881-4bf818946105" width="600"/>




