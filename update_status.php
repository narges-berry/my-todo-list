<?php
/**
 * Mark a task completed or pending, then return to the list.
 * This page only accepts POST. It has no form of its own.
 */

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$filter = selected_filter($_POST['filter'] ?? 'all');
require_csrf(filter_url($filter));

$id = task_id($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

if (!$id || !in_array($status, ['pending', 'completed'], true)) {
    set_flash('danger', 'Could not update that task.');
    redirect(filter_url($filter));
}

$db = db();
$task = find_task($db, $id);

if (!$task) {
    set_flash('danger', 'That task could not be found.');
    redirect(filter_url($filter));
}

$statement = $db->prepare('UPDATE tasks SET status = ? WHERE id = ?');
$statement->bind_param('si', $status, $id);
$statement->execute();
$statement->close();

if ($status === 'completed') {
    set_flash('success', 'Task marked as completed.');
} else {
    set_flash('success', 'Task marked as pending.');
}

redirect(filter_url($filter));
