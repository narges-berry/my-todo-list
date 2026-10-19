-- My To-Do List
-- Creates the database, the tasks table, and a few sample rows.
-- Re-running this file drops the tasks table and loads the sample data again.

CREATE DATABASE IF NOT EXISTS todo_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE todo_db;

DROP TABLE IF EXISTS tasks;

CREATE TABLE tasks (
  id INT NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  due_date DATE NULL,
  status ENUM('pending', 'completed') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newest created_at values appear first on the task list.
INSERT INTO tasks (title, description, due_date, status, created_at) VALUES
  (
    'Read a chapter',
    'Continue the novel before bed.',
    NULL,
    'completed',
    '2026-10-19 11:20:00'
  ),
  (
    'Call the dentist',
    'Reschedule next week''s appointment to Thursday afternoon.',
    '2026-10-21',
    'pending',
    '2026-10-18 16:45:00'
  ),
  (
    'Finish the project report',
    'Write the summary and proofread it before sending.',
    '2026-10-22',
    'pending',
    '2026-10-18 14:30:00'
  ),
  (
    'Buy groceries',
    'Milk, eggs, bread, and fresh vegetables for the week.',
    '2026-10-20',
    'pending',
    '2026-10-17 09:15:00'
  ),
  (
    'Pay the electricity bill',
    'The bill is on the kitchen counter.',
    '2026-10-15',
    'pending',
    '2026-10-15 18:05:00'
  ),
  (
    'Water the plants',
    NULL,
    '2026-10-16',
    'completed',
    '2026-10-16 08:00:00'
  );
