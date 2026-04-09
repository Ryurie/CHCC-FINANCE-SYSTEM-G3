# CHCC-FINANCE-SYSTEM-G3
# 🎓 CHCCI Finance Access Portal (Group 3)

A centralized, web-based financial management system developed for **Concepcion Holy Cross College, Inc. (CHCCI)**. This system is designed to streamline student billing, secure payment tracking, and automate batch scholarship management, eliminating manual computation errors and duplicate billing.

---

## 🚀 Key Features

* **Smart Invoicing Module:** Dynamically filters fees by department and semester with a strict anti-duplicate billing algorithm.
* **Payment & Receipt Management:** Auto-computes remaining student balances, issues printable electronic receipts, and tracks cashier accountability.
* **Batch Scholarship Allocation:** Allows mass tagging of financial grants (e.g., TES) and automatically isolates excess funds for physical cheque claiming.
* **Real-time General Ledger:** Provides a consolidated view of student financial standings and balances.
* **Automated Periodic Reports:** Generates printable summary reports filtered by specific date ranges.
* **Modern UI/UX:** Mobile-responsive design featuring an integrated Light/Dark mode toggle for user comfort.

---

## 💻 Technologies Used

* **Front-End:** HTML5, CSS3 (Custom Variables), Vanilla JavaScript (Real-time DOM manipulation)
* **Back-End:** PHP 7.4+
* **Database:** MySQL (Relational Database Management System)
* **Environment:** XAMPP (Local Apache Server)

---

## ⚙️ Installation & Setup Guide

To run this project locally on your machine, follow these steps:

1.  **Install XAMPP:** Download and install [XAMPP](https://www.apachefriends.org/index.html).
2.  **Clone the Repository:**
    ```bash
    git clone [https://github.com/your-username/CHCC-FINANCE-SYSTEM-G3.git](https://github.com/your-username/CHCC-FINANCE-SYSTEM-G3.git)
    ```
    *Alternatively, download the ZIP file and extract it.*
3.  **Move to HTDOCS:** Place the extracted folder inside the `xampp/htdocs/` directory.
4.  **Database Setup:**
    * Open XAMPP Control Panel and start **Apache** and **MySQL**.
    * Open your browser and go to `http://localhost/phpmyadmin`.
    * Create a new database named `finance_db`.
    * Import the provided SQL file (if available) or let the system auto-patch the initial tables upon running.
5.  **Run the System:**
    * Open your browser and navigate to: `http://localhost/CHCC-FINANCE-SYSTEM-G3/`

---

## 👥 Developers

Developed with 💻 and ☕ by **Group 3**.
* **Benru** - Lead Developer / Project Manager
* *(Add your groupmates' names here)*
* *(Add your groupmates' names here)*
