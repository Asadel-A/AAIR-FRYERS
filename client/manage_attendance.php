<?php
include 'includes/auth_check.php';
if ($_SESSION['role'] !== 'admin') { header("Location: index.php"); exit(); }
require 'config/db.php';

$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'insert') {
        $check = $pdo->prepare("SELECT id FROM attendance WHERE member_id = :mid AND event_id = :eid");
        $check->execute([':mid' => (int) $_POST['member_id'], ':eid' => (int) $_POST['event_id']]);
        if ($check->fetchColumn()) {
            $message = 'Duplicate — this member already has a record for that event.'; $msgType = 'error';
        } else {
            $pdo->prepare("INSERT INTO attendance (member_id, event_id, status, time_note) VALUES (:mid, :eid, :status, :note)")
                ->execute([':mid' => (int) $_POST['member_id'], ':eid' => (int) $_POST['event_id'], ':status' => $_POST['status'], ':note' => trim($_POST['time_note']) ?: null]);
            $message = 'Record added.'; $msgType = 'success';
        }
    }

    if ($action === 'update') {
        $pdo->prepare("UPDATE attendance SET status=:status, time_note=:note WHERE id=:id")
            ->execute([':status' => $_POST['status'], ':note' => trim($_POST['time_note']) ?: null, ':id' => (int) $_POST['attendance_id']]);
        $message = 'Record updated.'; $msgType = 'success';
    }

    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM attendance WHERE id = :id")->execute([':id' => (int) $_POST['attendance_id']]);
        $message = 'Record deleted.'; $msgType = 'success';
    }
}

$allMembers = $pdo->query("SELECT id, first_name, last_name, section FROM members ORDER BY section, last_name")->fetchAll(PDO::FETCH_ASSOC);
$allEvents = $pdo->query("SELECT id, event_name, event_date FROM events ORDER BY event_date DESC")->fetchAll(PDO::FETCH_ASSOC);

$filterEvent = $_GET['event'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT a.id, a.status, a.time_note, m.first_name, m.last_name, m.section, e.event_name, e.event_date FROM attendance a JOIN members m ON m.id=a.member_id JOIN events e ON e.id=a.event_id WHERE 1=1";
$params = [];
if ($filterEvent !== '') { $sql .= " AND a.event_id = :eid"; $params[':eid'] = (int) $filterEvent; }
if ($filterStatus !== '') { $sql .= " AND a.status = :status"; $params[':status'] = $filterStatus; }
if ($search !== '') { $sql .= " AND (m.first_name LIKE :s OR m.last_name LIKE :s OR e.event_name LIKE :s)"; $params[':s'] = "%$search%"; }
$sql .= " ORDER BY e.event_date DESC, m.last_name";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
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
        <h3>➕ Add Record</h3>
        <form method="POST">
            <input type="hidden" name="action" value="insert">
            <div class="form-row">
                <div class="form-group"><label>Member</label>
                    <select name="member_id" required><option value="">— Select —</option>
                    <?php foreach ($allMembers as $m): ?><option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['last_name'].', '.$m['first_name'].' ('.$m['section'].')') ?></option><?php endforeach; ?>
                    </select></div>
                <div class="form-group"><label>Event</label>
                    <select name="event_id" required><option value="">— Select —</option>
                    <?php foreach ($allEvents as $ev): ?><option value="<?= $ev['id'] ?>"><?= htmlspecialchars($ev['event_name'].' ('.date('M j', strtotime($ev['event_date'])).')') ?></option><?php endforeach; ?>
                    </select></div>
                <div class="form-group"><label>Status</label>
                    <select name="status" required><option value="Attending">Attending</option><option value="Away">Away</option><option value="Late">Late</option><option value="Leave">Leave</option></select></div>
                <div class="form-group"><label>Time Note</label><input type="text" name="time_note" placeholder="optional"></div>
                <button type="submit" class="btn-gold">Add</button>
            </div>
        </form>
    </div>

    <form method="GET" class="filter-bar">
        <input type="text" name="search" placeholder="Search…" value="<?= htmlspecialchars($search) ?>">
        <select name="event"><option value="">All Events</option>
            <?php foreach ($allEvents as $ev): ?><option value="<?= $ev['id'] ?>" <?= $filterEvent==$ev['id']?'selected':'' ?>><?= htmlspecialchars($ev['event_name']) ?></option><?php endforeach; ?>
        </select>
        <select name="status"><option value="">All</option>
            <option value="Attending" <?= $filterStatus==='Attending'?'selected':'' ?>>Attending</option>
            <option value="Away" <?= $filterStatus==='Away'?'selected':'' ?>>Away</option>
            <option value="Late" <?= $filterStatus==='Late'?'selected':'' ?>>Late</option>
            <option value="Leave" <?= $filterStatus==='Leave'?'selected':'' ?>>Leave</option>
        </select>
        <button type="submit" class="btn-gold">Filter</button>
        <?php if ($search || $filterEvent || $filterStatus): ?><a href="manage_attendance.php" style="color:#888;font-size:0.8rem;">Clear</a><?php endif; ?>
    </form>

    <?php if (empty($records)): ?>
        <div class="empty-state">No records found.</div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>ID</th><th>Member</th><th>Section</th><th>Event</th><th>Date</th><th>Status</th><th>Note</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($records as $r): ?>
            <tr id="view-<?= $r['id'] ?>">
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></td>
                <td><?= htmlspecialchars($r['section']) ?></td>
                <td><?= htmlspecialchars($r['event_name']) ?></td>
                <td><?= date('M j, Y', strtotime($r['event_date'])) ?></td>
                <td><span class="status-pill <?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                <td><?= $r['time_note'] ? htmlspecialchars($r['time_note']) : '—' ?></td>
                <td class="actions">
                    <button class="btn-edit" onclick="toggleEdit(<?= $r['id'] ?>)">Edit</button>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
                        <input type="hidden" name="action" value="delete"><input type="hidden" name="attendance_id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <tr id="edit-<?= $r['id'] ?>" style="display:none;background:#141416">
                <form method="POST"><input type="hidden" name="action" value="update"><input type="hidden" name="attendance_id" value="<?= $r['id'] ?>">
                <td><?= $r['id'] ?></td>
                <td colspan="2"><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?> <span style="color:#555">(<?= htmlspecialchars($r['section']) ?>)</span></td>
                <td colspan="2"><?= htmlspecialchars($r['event_name']) ?> <span style="color:#555">(<?= date('M j', strtotime($r['event_date'])) ?>)</span></td>
                <td><select name="status">
                    <option value="Attending" <?= $r['status']==='Attending'?'selected':'' ?>>Attending</option>
                    <option value="Away" <?= $r['status']==='Away'?'selected':'' ?>>Away</option>
                    <option value="Late" <?= $r['status']==='Late'?'selected':'' ?>>Late</option>
                    <option value="Leave" <?= $r['status']==='Leave'?'selected':'' ?>>Leave</option>
                </select></td>
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