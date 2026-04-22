<!--
Name: Aneek & Asadel
Date: March 27 2026
Description: Admin page for managing event records. Admin users can add, edit, or delete events. Stores records in database as well

-->

<?php

include 'includes/auth_check.php';
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
require 'config/db.php';

$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'insert') {
        $stmt = $pdo->prepare("
            INSERT INTO events (event_name, event_date, start_time, end_time, location, event_type)
            VALUES (:name, :date, :start, :end, :loc, :type)
        ");
        $stmt->execute([
            ':name'  => trim($_POST['event_name']),
            ':date'  => $_POST['event_date'],
            ':start' => $_POST['start_time'] ?: null,
            ':end'   => $_POST['end_time']   ?: null,
            ':loc'   => trim($_POST['location']),
            ':type'  => $_POST['event_type'],
        ]);
        $message = 'Event added successfully.';
        $msgType = 'success';
    }

    if ($action === 'update') {
        $stmt = $pdo->prepare("
            UPDATE events
            SET event_name = :name,
                event_date = :date,
                start_time = :start,
                end_time   = :end,
                location   = :loc,
                event_type = :type
            WHERE id = :id
        ");
        $stmt->execute([
            ':name'  => trim($_POST['event_name']),
            ':date'  => $_POST['event_date'],
            ':start' => $_POST['start_time'] ?: null,
            ':end'   => $_POST['end_time']   ?: null,
            ':loc'   => trim($_POST['location']),
            ':type'  => $_POST['event_type'],
            ':id'    => (int)$_POST['event_id'],
        ]);
        $message = 'Event updated successfully.';
        $msgType = 'success';
    }

    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM attendance WHERE event_id = :id")
            ->execute([':id' => (int)$_POST['event_id']]);
        $pdo->prepare("DELETE FROM events WHERE id = :id")
            ->execute([':id' => (int)$_POST['event_id']]);
        $message = 'Event deleted.';
        $msgType = 'success';
    }
}

$search = trim($_GET['search'] ?? '');
$where  = '';
$params = [];

if ($search !== '') {
    $where  = "WHERE event_name LIKE :s OR location LIKE :s OR event_type LIKE :s";
    $params = [':s' => "%$search%"];
}

$events = $pdo->prepare("SELECT * FROM events $where ORDER BY event_date DESC, start_time DESC");
$events->execute($params);
$events = $events->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events | MEJ Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="page-wrap">
    <a href="admin_dashboard.php" class="back-link">← Back to Admin Dashboard</a>
    <h1 class="page-title"> Manage Events</h1>
    <p class="page-sub">Add, edit, or remove events and practices.</p>

    <?php if ($message): ?>
        <div class="alert <?php echo $msgType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="form-card">
        <h3> Add New Event</h3>
        <form method="POST">
            <input type="hidden" name="action" value="insert">
            <div class="form-row">
                <div class="form-group">
                    <label>Event Name</label>
                    <input type="text" name="event_name" required placeholder="e.g. Rehearsal #5">
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="event_date" required>
                </div>
                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" name="start_time">
                </div>
                <div class="form-group">
                    <label>End Time</label>
                    <input type="time" name="end_time">
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" placeholder="e.g. MUSC 200">
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="event_type">
                        <option value="Big Band">Big Band</option>
                        <option value="Combo">Combo</option>
                    </select>
                </div>
                <button type="submit" class="btn-gold">Add Event</button>
            </div>
        </form>
    </div>

    <form method="GET" class="search-bar">
        <input type="text" name="search"
               placeholder="Search by event name, location, or type…"
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn-gold">Search</button>
        <?php if ($search): ?>
            <a href="manage_events.php" style="color:#888; font-size:0.8rem;">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (empty($events)): ?>
        <div class="empty-state">No events found.</div>
    <?php else: ?>
    <table class="data-table" id="events-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Event Name</th>
                <th>Date</th>
                <th>Start</th>
                <th>End</th>
                <th>Location</th>
                <th>Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($events as $ev): ?>

            <tr id="view-<?php echo $ev['id']; ?>">
                <td><?php echo (int)$ev['id']; ?></td>
                <td><?php echo htmlspecialchars($ev['event_name']); ?></td>
                <td><?php echo $ev['event_date'] ? date('M j, Y', strtotime($ev['event_date'])) : '—'; ?></td>
                <td><?php echo $ev['start_time'] ? date('g:i A', strtotime($ev['start_time'])) : '—'; ?></td>
                <td><?php echo $ev['end_time']   ? date('g:i A', strtotime($ev['end_time']))   : '—'; ?></td>
                <td><?php echo htmlspecialchars($ev['location']); ?></td>
                <td>
                    <?php $cls = strtolower(str_replace(' ', '-', $ev['event_type'])); ?>
                    <span class="type-badge <?php echo $cls; ?>"><?php echo htmlspecialchars($ev['event_type']); ?></span>
                </td>
                <td class="actions">
                    <button class="btn-edit" onclick="toggleEdit(<?php echo $ev['id']; ?>)">Edit</button>
                    <form method="POST" style="display:inline"
                          onsubmit="return confirm('Delete event «<?php echo htmlspecialchars(addslashes($ev['event_name'])); ?>»? This also removes related attendance records.');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="event_id" value="<?php echo $ev['id']; ?>">
                        <button type="submit" class="btn-danger">Delete</button>
                    </form>
                </td>
            </tr>

            <tr id="edit-<?php echo $ev['id']; ?>" style="display:none; background:#141416;">
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="event_id" value="<?php echo $ev['id']; ?>">
                    <td><?php echo (int)$ev['id']; ?></td>
                    <td><input type="text" name="event_name" value="<?php echo htmlspecialchars($ev['event_name']); ?>" required></td>
                    <td><input type="date" name="event_date" value="<?php echo $ev['event_date']; ?>" required></td>
                    <td><input type="time" name="start_time" value="<?php echo $ev['start_time'] ? date('H:i', strtotime($ev['start_time'])) : ''; ?>"></td>
                    <td><input type="time" name="end_time"   value="<?php echo $ev['end_time']   ? date('H:i', strtotime($ev['end_time']))   : ''; ?>"></td>
                    <td><input type="text" name="location"   value="<?php echo htmlspecialchars($ev['location']); ?>"></td>
                    <td>
                        <select name="event_type">
                            <option value="Big Band" <?php echo $ev['event_type'] === 'Big Band' ? 'selected' : ''; ?>>Big Band</option>
                            <option value="Combo"    <?php echo $ev['event_type'] === 'Combo'    ? 'selected' : ''; ?>>Combo</option>
                        </select>
                    </td>
                    <td class="actions">
                        <button type="submit" class="btn-save">Save</button>
                        <button type="button" class="btn-cancel" onclick="toggleEdit(<?php echo $ev['id']; ?>)">Cancel</button>
                    </td>
                </form>
            </tr>

        <?php endforeach; ?>
        </tbody>
    </table>

    <p style="color:#555; font-size:0.75rem; margin-top:20px;">
        Total events: <?php echo count($events); ?>
    </p>
    <?php endif; ?>
</div>

<script src="js/admin-tables.js"></script>
</body>
</html>
