# Project Proposal

## 1. Project Overview
This Document Management System (DMS) provides a secure web application for users to upload, manage, and download files while keeping documents private and organized. It includes user authentication, file metadata management (categories + tags), search/filtering, and access control.

### Key Objectives
- Allow registered users to upload and manage their documents.
- Store documents securely on the server and metadata in a relational database.
- Protect sensitive user information with hashing/encryption.
- Provide search and filter capabilities.
- Prevent unauthorized access to files and application pages.

## 2. Scope
### Core Functional Requirements
- User registration, login, and session management
- Secure file upload and download
- CRUD operations on document metadata
- Search and filter by category, tags, and title
- Role-based access control (per-user document ownership)

### Non-Functional Requirements
- Use HTML/CSS/JavaScript (AJAX/jQuery) on client side
- Use PHP + SQLite on server side
- Input validation and protection against SQL injection
- Maintain a clean, responsive UI layout

### UI Layout (Sketch)
- **Navigation bar** (top): app title, greeting, logout
- **Sidebar** (left): Upload form + filter controls
- **Main content** (right): sortable table of documents with actions (download, edit, delete)
- **Forms** are designed to be mobile-friendly using responsive CSS

## 3. Architecture
- Client-side: plain HTML/CSS for layout, JavaScript (Fetch/AJAX and jQuery) for dynamic operations.
- Server-side: PHP scripts handle routing, authentication, and database interaction.
- Database: SQLite file stored in `/data/documents.db`. Testing initially done in mySQL.

### Pages
- `index.php` — landing page / login prompt
- `register.php` — user registration
- `login.php` — user login
- `dashboard.php` — user file management + search
- `download.php` — secure document download (authorization check)
- `edit_document.php` — edit document metadata

## Sitemap / Page Flow
1. User arrives at `index.php` (public)
2. User registers via `register.php` or logs in via `login.php`
3. Upon login, user is redirected to `dashboard.php`
4. From dashboard, user can upload, search, filter, download, edit, and delete documents
5. `download.php` ensures only the document owner can access the file

## 4. Security Considerations
- Passwords hashed using `password_hash()` (bcrypt).
- Prepared statements used for SQL (PDO with bound parameters).
- File uploads validated for size and allowed extensions.
- Uploads stored outside of public code (`/uploads`), and direct access is controlled.
- Sessions are stored in database with a session token.

## 5. Deployment
1. Ensure PHP 7.4+ and `pdo_sqlite` extension are enabled.
2. Ensure `/data/` and `/uploads/` are writable by the web server.
3. Point web server root to project directory.
4. Visit `http://localhost/` and register a user.

---

*Last updated: March 2026*