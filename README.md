# My To-Do List

A small single-user to-do list built with plain PHP, MySQLi, MySQL, and Bootstrap 5. There is no login page. Anyone who can open the site can manage the same list of tasks.

## Requirements

- PHP 8.1 or newer, with the `mysqli` extension
- MySQL or MariaDB
- A web browser
- [XAMPP](https://www.apachefriends.org/) or [MAMP](https://www.mamp.info/)

Bootstrap and the icons are loaded from a CDN, so the page needs an internet connection to look right.

## Project structure

```text
todo/
  index.php            Task list and filters
  add.php              Add a task
  edit.php             Edit a task
  delete.php           Confirm and delete a task
  update_status.php    Mark a task completed or pending
  db.php               Database connection and shared helpers
  assets/css/style.css Extra styles
  database.sql         Database, table, and sample tasks
  README.md
```

## Run it with XAMPP

1. Start **Apache** and **MySQL** from the XAMPP control panel.
2. This project is already inside XAMPP's web folder at `htdocs/FirstProject/todo`.
3. Open phpMyAdmin at [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
4. Open the **Import** tab, choose `todo/database.sql`, and click **Go**.
5. Visit [http://localhost/FirstProject/todo/](http://localhost/FirstProject/todo/).

You can import the database from the terminal instead:

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root < /Applications/XAMPP/xamppfiles/htdocs/FirstProject/todo/database.sql
```

XAMPP's default login is user `root` with an empty password. Those values are already set at the top of `db.php`.

## Run it with MAMP

1. Copy the `todo` folder into MAMP's web root. On macOS that is usually `/Applications/MAMP/htdocs/todo`.
2. Start the servers in MAMP.
3. Open phpMyAdmin from the MAMP start page and import `database.sql`.
4. In `db.php`, set the password to `root` and the port to `8889`:

```php
define('DB_PASS', 'root');
define('DB_PORT', 8889);
```

5. Visit [http://localhost:8888/todo/](http://localhost:8888/todo/). Use the port shown in MAMP if it is different.

## What you can do

- Add a task with a title, an optional description, an optional due date, and a status.
- View every task, newest first.
- Filter by all, pending, or completed tasks.
- Edit a task.
- Mark a task completed or pending. Completed tasks are crossed out.
- Delete a task after confirming on a separate page.

The sample rows in `database.sql` are there so the list is not empty the first time you open it. Importing that file again removes existing tasks and loads the samples once more.

## Notes

- Changes are saved with POST, then the browser is redirected back to a page that is safe to refresh.
- Forms include a CSRF token stored in the PHP session.
- Database queries use MySQLi prepared statements.
- Text printed on the page is escaped with `htmlspecialchars()`.
