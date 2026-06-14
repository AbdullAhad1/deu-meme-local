# DEU Memes - Local Development

Social meme board project for Dong-Eui University.

## Setup

1. Create a MySQL/MariaDB database named `deu_board`.
2. Import `database.sql` using phpMyAdmin or the MySQL CLI.
3. Edit `config.php` with your database credentials if different from defaults:
   - host: `localhost`
   - database: `deu_board`
   - user: `root`
   - password: `` (blank for default XAMPP)
4. Start Apache and MySQL in XAMPP.
5. Place this folder in `htdocs/` and open `http://localhost/deu.meme.local/auth.php`.

## Used in this project

- MySQLi (procedural)
- `password_hash()` / `password_verify()`
- GD library image thumbnails
- `finfo` MIME validation
- `uniqid()` filenames
- 2 MB upload limit
- JavaScript `confirm()` for delete actions


