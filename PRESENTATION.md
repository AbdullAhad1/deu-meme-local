# DEU Memeverse
## Web Programming II Project Presentation

---

# Slide 1: Title

# DEU Memeverse
### Dong-Eui University Student Meme Board

**Course:** Web Programming II  
**Instructor:** Hamdi  
**Team:** CodeCrafters

---

# Slide 2: Team Members & Roles

| # | Name | Student ID | Role | Responsibility |
|---|------|------------|------|----------------|
| 1 | **Ahad Abdul** | - | **Team Leader** | Planning, backend core, database setup, GitHub, documentation |
| 2 | **Hasan MD Tanvir** | 20244169 | **Frontend Developer** | HTML/CSS/JS, responsive layout, GIF picker, QR code |
| 3 | **Das Sourav** | 20244163 | **Backend Developer** | PHP logic, login/register, like/follow, upload handlers |
| 4 | **KC Surya** | 20244125 | **UI/UX Designer** | Visual design, profile/post layouts, mobile navigation |

---

# Slide 3: Project Overview

**DEU Memeverse** is a social meme-sharing platform for Dong-Eui University students.

### Core Purpose
- Register with name, student ID, and password
- Create posts with text, photos, or GIFs
- Like posts and follow other students
- View profiles and manage accounts
- Admin panel for content moderation

### Links
- GitHub: `https://github.com/AbdullAhad1/deu-meme-local`
- Local: `http://localhost/deu.meme.local/auth.php`

---

# Slide 4: Features Implemented

### User Features
- Registration / Login with password
- Create text/image/GIF posts
- Like / unlike posts
- Follow / unfollow users
- Edit profile bio and upload avatar
- View user profiles and student list

### Admin Features
- Secure admin login
- Delete posts with confirm
- Delete users and their content with confirm

---

# Slide 5: Web Programming II Requirements

| Requirement | Implementation |
|-------------|----------------|
| **MySQLi** | `config.php` uses procedural `mysqli_*` |
| **password_hash()** | Used during registration |
| **password_verify()** | Used during login |
| **GD Thumbnails** | `upload_helpers.php` generates thumbnails |
| **finfo MIME Validation** | `finfo_file()` validates JPEG/PNG/GIF |
| **uniqid() Filenames** | `uniqid() + rand()` for every upload |
| **2MB Upload Limit** | Hardcoded in upload validator |
| **JS confirm() Delete** | Admin delete forms use `confirm()` |

---

# Slide 6: Technology Stack

### Frontend
- HTML5, CSS3, JavaScript
- Bootstrap Icons
- GIPHY API for GIF picker
- QR Code API for shareable link

### Backend
- PHP 8.x
- MySQLi procedural
- MySQL / MariaDB
- GD Library for image thumbnails

### Tools
- XAMPP, VS Code, Git, GitHub, phpMyAdmin

---

# Slide 7: Database Schema

### Tables
1. **user** — id, student_id, name, password, avatar, bio, created_at
2. **post** — id, user_id, message, image_file, thumbnail_file, created_at
3. **likes** — id, post_id, user_id
4. **follows** — id, follower_id, following_id

### Relationships
- User → Posts (1:N)
- User → Likes (1:N)
- User → Follows (1:N self-referencing)

---

# Slide 8: Individual Tasks

### Ahad Abdul — Team Leader
- Project planning and task distribution
- Created GitHub repository and version control
- Wrote `config.php` with reusable MySQLi helpers
- Set up `database.sql` and XAMPP-compatible database
- Reviewed backend code and tested features
- Wrote `README.md` and `PRESENTATION.md`
- Final deployment to GitHub

### Hasan MD Tanvir — Frontend Developer (20244169)
- Built all HTML pages
- Created responsive CSS layout and dark theme
- Implemented JavaScript for GIF picker and QR code
- Added Bootstrap Icons and mobile navigation

### Das Sourav — Backend Developer (20244163)
- Wrote PHP logic for login, register, like, follow
- Built post creation and profile update features
- Created `upload_helpers.php` with GD thumbnails and finfo validation
- Implemented `password_hash()` and `password_verify()`
- Built admin delete operations with confirm dialog
- Enforced uniqid filenames and 2MB upload limit

### KC Surya — UI/UX Designer (20244125)
- Designed color scheme, fonts, and visual style
- Created profile cards and post layouts
- Designed admin dashboard
- Improved mobile responsiveness and overall UX

---

# Slide 9: Key Code Highlights

### MySQLi Connection Helper (`config.php`)
```php
function getDB() {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn) die("Connection failed");
    return $conn;
}
```

### Password Security (`auth.php`)
```php
$hash = password_hash($password, PASSWORD_DEFAULT);
password_verify($password, $stored_hash);
```

### Secure Upload (`upload_helpers.php`)
```php
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
// Only JPG/PNG/GIF under 2MB allowed
```

---

# Slide 10: Screenshots / Live Demo

1. **Auth Page** — Login / Register
2. **Home Feed** — Create post + view posts
3. **Profile Page** — Avatar, bio, stats
4. **Users Page** — All DEU students
5. **Admin Panel** — Delete posts/users with confirm

*(Add screenshots here before presentation)*

---

# Slide 11: Challenges & Solutions

| Challenge | Solution |
|-----------|----------|
| PDO not allowed | Rewrote all queries to MySQLi |
| Old DB had no password column | Added `password` column and updated schema |
| Existing users couldn't log in | Auto-save entered password as hash on first login |
| Uploads too large / unsafe | Reduced to 2MB and validated with finfo |
| Admin page session warning | Added `isset()` checks |

---

# Slide 12: Conclusion & Q&A

### What We Learned
- Secure PHP with prepared statements
- GD library image processing
- File upload validation with finfo
- Password hashing best practices
- Team collaboration with Git/GitHub

### Project Status
✅ Completed and ready for demonstration

### GitHub Repository
`https://github.com/AbdullAhad1/deu-meme-local`

## Thank You — Questions?

**Team CodeCrafters**
- Ahad Abdul — Team Leader
- Hasan MD Tanvir — 20244169
- Das Sourav — 20244163
- KC Surya — 20244125

---

# Speaker Notes

### Ahad Abdul
- Open with project purpose and team roles
- Walk through requirements checklist
- Explain database schema and config.php
- Handle GitHub / technical questions

### Hasan MD Tanvir
- Demo auth page, GIF picker, QR code
- Show responsive design

### Das Sourav
- Explain login/register backend
- Show like/follow logic
- Discuss password hashing and upload security

### KC Surya
- Explain color scheme and UX choices
- Show profile design and mobile navigation

### Live Demo Steps
1. Register a new student
2. Create text and image posts
3. Like a post and follow a user
4. Update profile avatar/bio
5. Open admin panel and delete content
