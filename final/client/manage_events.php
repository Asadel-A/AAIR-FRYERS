<?php
include 'includes/auth_check.php';
if ($_SESSION['role'] !== 'admin') { header("Location: index.php"); exit(); }
require 'config/db.php';

$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'insert') {
        $pdo->prepare("INSERT INTO events (event_name, event_date, start_time, end_time, location, event_type) VALUES (:name, :date, :start, :end, :loc, :type)")
            ->execute([':name' => trim($_POST['event_name']), ':date' => $_POST['event_date'], ':start' => $_POST['start_time'] ?: null, ':end' => $_POST['end_time'] ?: null, ':loc' => trim($_POST['location']), ':type' => $_POST['event_type']]);
        $message = 'Event added.'; $msgType = 'success';
    }

    if ($action === 'update') {
        $pdo->prepare("UPDATE events SET event_name=:name, event_date=:date, start_time=:start, end_time=:end, location=:loc, event_type=:type WHERE id=:id")
            ->execute([':name' => trim($_POST['event_name']), ':date' => $_POST['event_date'], ':start' => $_POST['start_time'] ?: null, ':end' => $_POST['end_time'] ?: null, ':loc' => trim($_POST['location']), ':type' => $_POST['event_type'], ':id' => (int) $_POST['event_id']]);
        $message = 'Event updated.'; $msgType = 'success';
    }

    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM attendance WHERE event_id = :id")->execute([':id' => (int) $_POST['event_id']]);
        $pdo->prepare("DELETE FROM events WHERE id = :id")->execute([':id' => (int) $_POST['event_id']]);
        $message = 'Event deleted.'; $msgType = 'success';
    }
}

$search = trim($_GET['search'] ?? '');
$where = ''; $params = [];
if ($search !== '') { $where = "WHERE event_name LIKE :s OR location LIKE :s OR event_type LIKE :s"; $params = [':s' => "%$search%"]; }
$stmt = $pdo->prepare("SELECT * FROM events $where ORDER BY event_date DESC"); $stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events | MEJ Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="page-wrap">
    <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
    <h1 class="page-title">📅 Manage Events</h1>
    <p class="page-sub">Add, edit, or remove events and practices.</p>

    <?php if ($message): ?>
        <div class="alert <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="form-card">
        <h3>➕ Add New Event</h3>
        <form method="POST">
            <input type="hidden" name="action" value="insert">
            <div class="form-row">
                <div class="form-group"><label>Event Name</label><input type="text" name="event_name" required></div>
                <div class="form-group"><label>Date</label><input type="date" name="event_date" required></div>
                <div class="form-group"><label>Start</label><input type="time" name="start_time"></div>
                <div class="form-group"><label>End</label><input type="time" name="end_time"></div>
                <div class="form-group"><label>Location</label><input type="text" name="location"></div>
                <div class="form-group"><label>Type</label><select name="event_type"><option value="Big Band">Big Band</option><option value="Combo">Combo</option></select></div>
                <button type="submit" class="btn-gold">Add</button>
            </div>
        </form>
    </div>

    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search…" value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn-gold">Search</button>
        <?php if ($search): ?><a href="manage_events.php" style="color:#888;font-size:0.8rem;">Clear</a><?php endif; ?>
    </form>

    <?php if (empty($events)): ?>
        <div class="empty-state">No events found.</div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>ID</th><th>Name</th><th>Date</th><th>Start</th><th>End</th><th>Location</th><th>Type</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($events as $ev): ?>
            <tr id="view-<?= $ev['id'] ?>">
                <td><?= $ev['id'] ?></td>
                <td><?= htmlspecialchars($ev['event_name']) ?></td>
                <td><?= $ev['event_date'] ? date('M j, Y', strtotime($ev['event_date'])) : '—' ?></td>
                <td><?= $ev['start_time'] ? date('g:i A', strtotime($ev['start_time'])) : '—' ?></td>
                <td><?= $ev['end_time'] ? date('g:i A', strtotime($ev['end_time'])) : '—' ?></td>
                <td><?= htmlspecialchars($ev['location']) ?></td>
                <td><span class="type-badge <?= strtolower(str_replace(' ','-',$ev['event_type'])) ?>"><?= htmlspecialchars($ev['event_type']) ?></span></td>
                <td class="actions">
                    <button class="btn-edit" onclick="toggleEdit(<?= $ev['id'] ?>)">Edit</button>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this event?')">
                        <input type="hidden" name="action" value="delete"><input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                        <button type="submit" class="btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <tr id="edit-<?= $ev['id'] ?>" style="display:none;background:#141416">
                <form method="POST"><input type="hidden" name="action" value="update"><input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                <td><?= $ev['id'] ?></td>
                <td><input type="text" name="event_name" value="<?= htmlspecialchars($ev['event_name']) ?>" required></td>
                <td><input type="date" name="event_date" value="<?= $ev['event_date'] ?>" required></td>
                <td><input type="time" name="start_time" value="<?= $ev['start_time'] ? date('H:i', strtotime($ev['start_time'])) : '' ?>"></td>
                <td><input type="time" name="end_time" value="<?= $ev['end_time'] ? date('H:i', strtotime($ev['end_time'])) : '' ?>"></td>
                <td><input type="text" name="location" value="<?= htmlspecialchars($ev['location']) ?>"></td>
                <td><select name="event_type"><option value="Big Band" <?= $ev['event_type']==='Big Band'?'selected':'' ?>>Big Band</option><option value="Combo" <?= $ev['event_type']==='Combo'?'selected':'' ?>>Combo</option></select></td>
                <td class="actions">
                    <button type="submit" class="btn-save">Save</button>
                    <button type="button" class="btn-cancel" onclick="toggleEdit(<?= $ev['id'] ?>)">Cancel</button>
                </td></form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<script>
function toggleEdit(id) {
    var v = document.getElementById('view-'+id), e = document.getElementById('edit-'+id);
    v.style.display = e.style.display === 'none' ? 'none' : '';
    e.style.display = e.style.display === 'none' ? '' : 'none';
}
</script>
</body>
</html>