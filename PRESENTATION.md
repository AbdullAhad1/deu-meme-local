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
| 1 | **Ahad Abdul** | - | **Team Leader** | Project planning, backend conversion, database setup, final integration, testing, GitHub deployment |
| 2 | **Hasan MD Tanvir** | 20244169 | **Frontend Developer** | HTML/CSS/JS structure, responsive layout, Bootstrap/custom UI, form validation, GIF picker integration |
| 3 | **Das Sourav** | 20244163 | **Backend Developer** | MySQLi queries, authentication logic, file upload handlers, admin panel functionality |
| 4 | **KC Surya** | 20244125 | **UI/UX Designer** | Visual design, color scheme, user experience, profile cards, post layouts, mobile navigation |

---

# Slide 3: Project Overview

**DEU Memeverse** is a social meme-sharing platform built exclusively for Dong-Eui University students.

### Core Purpose
- Students register with their name and student ID
- Create posts with text, photos, or GIFs
- Like posts and follow other students
- View profiles and manage accounts
- Admin panel for content moderation

### Live URL (Demo)
- Localhost: `http://localhost/deu.meme.local/auth.php`
- GitHub: `https://github.com/AbdullAhad1/deu-meme-local`

---

# Slide 4: Features Implemented

### User Features
- Registration / Login with name + student ID + password
- Create text/image/GIF posts
- Like / unlike posts
- Follow / unfollow users
- Edit profile bio
- Upload avatar
- View user profiles

### Admin Features
- Secure admin login
- Delete posts
- Delete users and their content
- JavaScript `confirm()` before delete

---

# Slide 5: Web Programming II Requirements Checklist

| Requirement | Implementation |
|-------------|----------------|
| **MySQLi (not PDO)** | `config.php` uses procedural `mysqli_*` functions |
| **password_hash()** | Used during registration |
| **password_verify()** | Used during login |
| **GD Thumbnails** | `upload_helpers.php` generates 300x300/150x150 thumbnails |
| **finfo MIME Validation** | `finfo_file()` validates JPEG/PNG/GIF |
| **uniqid() Filenames** | `uniqid() + rand()` for every upload |
| **2MB Upload Limit** | Hardcoded in upload validator |
| **JS confirm() on Delete** | Admin delete forms use `onsubmit="return confirm('...')"` |

---

# Slide 6: Technology Stack

### Frontend
- HTML5
- CSS3 (custom dark theme with gradients)
- JavaScript (vanilla)
- Bootstrap Icons
- GIPHY API for GIF picker
- QR Code API for shareable link

### Backend
- PHP 8.x
- MySQLi (procedural)
- MySQL / MariaDB
- GD Library for thumbnails

### Tools
- XAMPP (Windows)
- VS Code
- Git & GitHub
- phpMyAdmin

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

# Slide 8: Project Architecture

```
┌─────────────────┐
│   Browser       │
│   (Student)     │
└────────┬────────┘
         │ HTTP
         ▼
┌─────────────────┐
│   Apache        │
│   (XAMPP)       │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   PHP Files     │
│   (Pages)       │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   config.php    │
│   MySQLi        │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   MariaDB       │
│   deu_board     │
└─────────────────┘
```

---

# Slide 9: Pages Overview

| File | Purpose |
|------|---------|
| `auth.php` | Login / Register page |
| `index.php` | Main feed + create post |
| `profile.php` | User profile + avatar/bio |
| `post.php` | Single post view |
| `users.php` | List all DEU students |
| `admin.php` | Admin dashboard |
| `loading.php` | Splash redirect after login |
| `logout.php` | Session destroy |
| `config.php` | Database connection |
| `upload_helpers.php` | Upload + thumbnail helpers |
| `database.sql` | Full database schema |

---

# Slide 10: Individual Tasks

### Ahad Abdul — Team Leader
- Project planning and task distribution
- Converted all PHP files from PDO to MySQLi
- Created `config.php` with reusable DB helpers
- Wrote `upload_helpers.php` with GD thumbnail + finfo validation
- Set up local and XAMPP-compatible database
- Fixed bugs during testing (password null error, missing column)
- Created GitHub repository and pushed final code
- Coordinated frontend/backend integration

### Hasan MD Tanvir — Frontend Developer (20244169)
- Built responsive layout structure
- Implemented post feed cards and create-post form
- Added GIF picker using GIPHY API
- Implemented QR code share popup
- Added mobile navigation bar
- Ensured Bootstrap Icons and dark theme consistency
- Tested frontend across screen sizes

### Das Sourav — Backend Developer (20244163)
- Implemented login/registration logic
- Built like/unlike functionality
- Built follow/unfollow functionality
- Implemented post creation with image uploads
- Implemented profile bio update and avatar upload
- Built admin delete operations for posts and users
- Helped test backend flow end-to-end

### KC Surya — UI/UX Designer (20244125)
- Designed dark meme-themed color palette
- Created avatar placeholders and profile layouts
- Designed post action buttons (like, follow)
- Designed admin dashboard tables and buttons
- Improved mobile responsiveness
- Ensured visual consistency across all pages

---

# Slide 11: Task Distribution Summary

| Area | Member |
|------|--------|
| Planning & Integration | Ahad Abdul |
| HTML/CSS/JS Frontend | Hasan MD Tanvir |
| PHP Backend Logic | Das Sourav |
| Visual Design & UX | KC Surya |
| Testing & Bug Fixes | All members |
| Documentation / GitHub | Ahad Abdul |

---

# Slide 12: Key Code Highlights

### MySQLi Connection Helper (`config.php`)
```php
function getDB() {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn) die("Connection failed");
    return $conn;
}
function db_prepare($sql) {
    return mysqli_prepare(getDB(), $sql);
}
```

### Password Hashing & Verification (`auth.php`)
```php
$hash = password_hash($password, PASSWORD_DEFAULT);
// Login
password_verify($password, $stored_hash);
```

### Secure Upload Helper (`upload_helpers.php`)
```php
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
// Only JPG/PNG/GIF under 2MB allowed
```

---

# Slide 13: Screenshots / Demo

1. **Auth Page** — Login / Register
2. **Home Feed** — Create post + view posts
3. **Profile Page** — Avatar, bio, stats, own posts
4. **Users Page** — All registered DEU students
5. **Admin Panel** — Delete posts/users with confirm

*(Add screenshots here before presentation)*

---

# Slide 14: Challenges & Solutions

| Challenge | Solution |
|-----------|----------|
| PDO not allowed | Rewrote all queries to MySQLi |
| Old DB had no password column | Added `password` column to schema and code |
| Existing users couldn't log in | Auto-save entered password as hash on first login |
| Uploads over 5MB | Reduced limit to 2MB and validated with finfo |
| Admin page warning | Added `isset()` checks for session variables |

---

# Slide 15: Future Improvements

- Comment system on posts
- Real-time notifications
- Direct messaging between students
- Post categories / hashtags
- Email verification
- Report inappropriate content
- Dark/light theme toggle

---

# Slide 16: Conclusion

### What We Learned
- Writing secure PHP with prepared statements
- Using GD library for image processing
- Proper file upload validation with finfo
- Password hashing best practices
- Team collaboration with Git/GitHub

### Project Status
✅ Completed and ready for demonstration

### GitHub Repository
`https://github.com/AbdullAhad1/deu-meme-local`

---

# Slide 17: Q&A

## Thank You!
### Questions?

**Team CodeCrafters**
- Ahad Abdul — Team Leader
- Hasan MD Tanvir — 20244169
- Das Sourav — 20244163
- KC Surya — 20244125

Dong-Eui University
Web Programming II

---

# Speaker Notes

### Ahad Abdul
- Open with project purpose
- Explain why we chose this project
- Walk through requirements checklist
- Handle GitHub / technical questions

### Hasan MD Tanvir
- Demo auth page
- Show GIF picker
- Show QR code share feature
- Explain responsive design

### Das Sourav
- Explain login/registration backend
- Show like/follow logic
- Explain admin delete flow
- Discuss security (password_hash, prepared statements)

### KC Surya
- Explain color scheme and UX choices
- Show profile design
- Show mobile navigation
- Discuss design consistency

### Live Demo Steps
1. Register a new student
2. Create a text post
3. Upload an image post
4. Like a post
5. Follow another student
6. Update profile avatar/bio
7. Open admin panel and delete a post
