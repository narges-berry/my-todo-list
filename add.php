<?php
/**
 * Add a task, then return to the list.
 */

require_once __DIR__ . '/db.php';

$db = db();
$errors = [];
$values = form_values([]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf('add.php');

    $result = validate_task($_POST);
    $errors = $result['errors'];
    $values = $result['data'];

    if (!$errors) {
        $title = $values['title'];
        $description = $values['description'];
        $dueDate = $values['due_date'];
        $status = $values['status'];

        // bind_param needs variables. NULLIF stores a blank optional field as NULL.
        $statement = $db->prepare(
            "INSERT INTO tasks (title, description, due_date, status)
             VALUES (?, NULLIF(?, ''), NULLIF(?, ''), ?)"
        );
        $statement->bind_param('ssss', $title, $description, $dueDate, $status);
        $statement->execute();
        $statement->close();

        set_flash('success', 'Task added successfully.');
        redirect('index.php');
    }
}

render_header('Add task');
?>

<div class="form-wrap">
    <h2 class="h4 text-center mb-3">Add a task</h2>
    <?php render_task_form('add.php', $values, $errors, 'Save task'); ?>
</div>

<?php render_footer(); ?>
