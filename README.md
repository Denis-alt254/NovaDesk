# NovaDesk 🚀

NovaDesk is a modern, lightweight, database-driven PHP web application designed to showcase digital service packages, facilitate client consultation requests, and provide real-time user session management. Built using pure PHP, MySQL (PDO), and vanilla JavaScript, NovaDesk delivers enterprise-level security and performance without the bloat of heavy frameworks.

---

## 🌟 Features Overview

### 👤 User Management & Authentication
* **User Registration & Login:** Secure password hashing (`PASSWORD_ARGON2ID` / `PASSWORD_DEFAULT`) with client & server-side validation.
* **Role-Based Access Control (RBAC):** Distinct roles for **Clients** (request submission & tracking) and **Admins** (package creation & consultation processing).
* **Database-Backed Sessions:** Custom session handler storing session payloads, IP addresses, and user-agent strings directly in MySQL for cross-device tracking and security auditing.

### 💼 Service Packages Hub
* **Dynamic Catalog:** Publicly browse service packages categorized by industry, project scope, estimated delivery timeline, and pricing tier.
* **Package Details & Filtering:** Instant filtering by category (e.g., *Engineering*, *UI/UX Design*, *Security Audit*) using asynchronous JavaScript (AJAX).

### 📩 Consultation Request Engine
* **Interactive Request Form:** Prefill client account information automatically when logged in.
* **Package Binding:** Link consultation inquiries directly to specific service packages.
* **Request Tracking Dashboard:** Real-time status lifecycle tracking (`Pending` → `In Review` → `Quoted` → `Completed` → `Cancelled`).

### ⚙️ Architecture & Security
* **Singleton PDO Connection Wrapper:** Connection pooling pattern guaranteeing a single DB connection handle per request.
* **Prepared Statements:** Strict parameterized SQL execution (`PDO::ATTR_EMULATE_PREPARES => false`) to eliminate SQL Injection (SQLi) vulnerabilities.
* **XSS Defense:** Context-aware output escaping via `htmlspecialchars()` across all dynamic frontend views.

---

## 📂 Complete Project Directory Structure

```text
NovaDesk/
├── config/
│   └── db.php                     # Singleton PDO Database Connection Wrapper
├── database/
│   └── schema.sql                 # Database Schema, Indexes, FKs & Seed Data
├── public/
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css          # Core Design Tokens & UI Stylesheet
│   │   └── js/
│   │       ├── auth.js            # Client-side form validations
│   │       └── catalog.js         # Dynamic search & category filtering
│   ├── index.php                  # Landing page & featured service packages
│   ├── packages.php               # Complete Service Package Catalog
│   ├── request-consultation.php   # Consultation Request Form
│   └── dashboard.php              # User Dashboard (Status tracking)
├── src/
│   ├── Auth.php                   # Registration, Login & Session Controllers
│   ├── Package.php                # Service Package Queries & Handlers
│   └── Consultation.php           # Consultation Processing Engine
├── tests/
│   └── test_db.php                # Automated Singleton & Schema Verification Test
├── .gitignore                     # Git exclusion rules
└── README.md                      # Complete Project Documentation