<?php
include 'includes/auth_check.php';
if ($_SESSION['role'] !== 'admin') { header("Location: index.php"); exit(); }
require 'config/db.php';

$message = '';
$msgType = '';

$memberList = $pdo->query("SELECT id, first_name, last_name FROM members ORDER BY last_name, first_name")->fetchAll(PDO::FETCH_ASSOC);
$eventList  = $pdo->query("SELECT id, event_name, event_date FROM events ORDER BY event_date DESC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'insert') {
        $pdo->prepare("INSERT INTO attendance (member_id, event_id, status, time_note) VALUES (:mid, :eid, :status, :note)")
            ->execute([':mid' => (int) $_POST['member_id'], ':eid' => (int) $_POST['event_id'], ':status' => $_POST['status'], ':note' => trim($_POST['time_note']) ?: null]);
        $message = 'Attendance record added.'; $msgType = 'success';
    }

    if ($action === 'update') {
        $pdo->prepare("UPDATE attendance SET member_id=:mid, event_id=:eid, status=:status, time_note=:note WHERE id=:id")
            ->execute([':mid' => (int) $_POST['member_id'], ':eid' => (int) $_POST['event_id'], ':status' => $_POST['status'], ':note' => trim($_POST['time_note']) ?: null, ':id' => (int) $_POST['att_id']]);
        $message = 'Attendance record updated.'; $msgType = 'success';
    }

    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM attendance WHERE id = :id")->execute([':id' => (int) $_POST['att_id']]);
        $message = 'Attendance record deleted.'; $msgType = 'success';
    }
}

$search = trim($_GET['search'] ?? '');
$where = ''; $params = [];
if ($search !== '') {
    $where = "WHERE m.first_name LIKE :s OR m.last_name LIKE :s OR e.event_name LIKE :s OR a.status LIKE :s";
    $params = [':s' => "%$search%"];
}
$stmt = $pdo->prepare("
    SELECT a.*, m.first_name, m.last_name, e.event_name, e.event_date
    FROM attendance a
    JOIN members m ON m.id = a.member_id
    JOIN events  e ON e.id = a.event_id
    $where
    ORDER BY e.event_date DESC, m.last_name
");
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Attendance | MEJ Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="page-wrap">
    <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
    <h1 class="page-title">📋 Manage Attendance</h1>
    <p class="page-sub">Add, edit, or remove attendance records.</p>

    <?php if ($message): ?>
        <div class="alert <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="form-card">
        <h3>➕ Add Attendance Record</h3>
        <form method="POST">
            <input type="hidden" name="action" value="insert">
            <div class="form-row">
                <div class="form-group">
                    <label>Member</label>
                    <select name="member_id" required>
                        <option value="">— select —</option>
                        <?php foreach ($memberList as $mb): ?>
                            <option value="<?= $mb['id'] ?>"><?= htmlspecialchars($mb['last_name'] . ', ' . $mb['first_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Event</label>
                    <select name="event_id" required>
                        <option value="">— select —</option>
                        <?php foreach ($eventList as $ev): ?>
                            <option value="<?= $ev['id'] ?>"><?= htmlspecialchars($ev['event_name']) ?> (<?= $ev['event_date'] ? date('M j', strtotime($ev['event_date'])) : '—' ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="Attending">Attending</option>
                        <option value="Away">Away</option>
                        <option value="Late">Late</option>
                        <option value="Leave">Leave</option>
                    </select>
                </div>
                <div class="form-group"><label>Time Note</label><input type="text" name="time_note" placeholder="e.g. 7:30"></div>
                <button type="submit" class="btn-gold">Add</button>
            </div>
        </form>
    </div>

    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search…" value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn-gold">Search</button>
        <?php if ($search): ?><a href="manage_attendance.php" style="color:#888;font-size:0.8rem;">Clear</a><?php endif; ?>
    </form>

    <?php if (empty($records)): ?>
        <div class="empty-state">No attendance records found.</div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>ID</th><th>Member</th><th>Event</th><th>Date</th><th>Status</th><th>Time Note</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($records as $r): ?>
            <tr id="view-<?= $r['id'] ?>">
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?></td>
                <td><?= htmlspecialchars($r['event_name']) ?></td>
                <td><?= $r['event_date'] ? date('M j, Y', strtotime($r['event_date'])) : '—' ?></td>
                <td><span class="type-badge <?= strtolower($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                <td><?= $r['time_note'] ? htmlspecialchars($r['time_note']) : '—' ?></td>
                <td class="actions">
                    <button class="btn-edit" onclick="toggleEdit(<?= $r['id'] ?>)">Edit</button>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this record?')">
                        <input type="hidden" name="action" value="delete"><input type="hidden" name="att_id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <tr id="edit-<?= $r['id'] ?>" style="display:none;background:#141416">
                <form method="POST"><input type="hidden" name="action" value="update"><input type="hidden" name="att_id" value="<?= $r['id'] ?>">
                <td><?= $r['id'] ?></td>
                <td>
                    <select name="member_id" required>
                        <?php foreach ($memberList as $mb): ?>
                            <option value="<?= $mb['id'] ?>" <?= $mb['id'] == $r['member_id'] ? 'selected' : '' ?>><?= htmlspecialchars($mb['last_name'] . ', ' . $mb['first_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <select name="event_id" required>
                        <?php foreach ($eventList as $ev): ?>
                            <option value="<?= $ev['id'] ?>" <?= $ev['id'] == $r['event_id'] ? 'selected' : '' ?>><?= htmlspecialchars($ev['event_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><?= $r['event_date'] ? date('M j, Y', strtotime($r['event_date'])) : '—' ?></td>
                <td>
                    <select name="status" required>
                        <option value="Attending" <?= $r['status']==='Attending'?'selected':'' ?>>Attending</option>
                        <option value="Away" <?= $r['status']==='Away'?'selected':'' ?>>Away</option>
                        <option value="Late" <?= $r['status']==='Late'?'selected':'' ?>>Late</option>
                        <option value="Leave" <?= $r['status']==='Leave'?'selected':'' ?>>Leave</option>
                    </select>
                </td>
                <td><input type="text" name="time_note" value="<?= htmlspecialchars($r['time_note'] ?? '') ?>"></td>
                <td class="actions">
                    <button type="submit" class="btn-save">Save</button>
                    <button type="button" class="btn-cancel" onclick="toggleEdit(<?= $r['id'] ?>)">Cancel</button>
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
