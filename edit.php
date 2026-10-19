<?php
/**
 * Edit an existing task, then return to the list.
 */

require_once __DIR__ . '/db.php';

$db = db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf('index.php');

    $id = task_id($_POST['id'] ?? 0);
    $task = $id ? find_task($db, $id) : null;

    if (!$task) {
        set_flash('danger', 'That task could not be found.');
        redirect('index.php');
    }

    $result = validate_task($_POST);
    $errors = $result['errors'];
    $values = $result['data'];

    if (!$errors) {
        $title = $values['title'];
        $description = $values['description'];
        $dueDate = $values['due_date'];
        $status = $values['status'];

        $statement = $db->prepare(
            "UPDATE tasks
             SET title = ?, description = NULLIF(?, ''), due_date = NULLIF(?, ''), status = ?
             WHERE id = ?"
        );
        $statement->bind_param('ssssi', $title, $description, $dueDate, $status, $id);
        $statement->execute();
        $statement->close();

        set_flash('success', 'Task updated successfully.');
        redirect('index.php');
    }
} else {
    $id = task_id($_GET['id'] ?? 0);
    $task = $id ? find_task($db, $id) : null;

    if (!$task) {
        set_flash('danger', 'That task could not be found.');
        redirect('index.php');
    }

    $values = form_values($task);
}

render_header('Edit task');
?>

<div class="form-wrap">
    <h2 class="h4 text-center mb-3">Edit task</h2>
    <?php render_task_form('edit.php', $values, $errors, 'Save changes', (int) $id, 'bi-pencil'); ?>
</div>

<?php render_footer(); ?>
