<?php
/**
 * Manage Events — INSERT / UPDATE / DELETE
 * -----------------------------------------
 * Aneek's contribution: lets the admin manually add, edit, or remove
 * event records in the database.
 */

include 'includes/auth_check.php';
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
require 'config/db.php';

// ── Handle POST actions ──────────────────────────────────────────────────────

$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── INSERT ───────────────────────────────────────────────────────────────
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

    // ── UPDATE ───────────────────────────────────────────────────────────────
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

    // ── DELETE ───────────────────────────────────────────────────────────────
    if ($action === 'delete') {
        // Delete related attendance records first to avoid FK issues
        $pdo->prepare("DELETE FROM attendance WHERE event_id = :id")
            ->execute([':id' => (int)$_POST['event_id']]);
        $pdo->prepare("DELETE FROM events WHERE id = :id")
            ->execute([':id' => (int)$_POST['event_id']]);
        $message = 'Event deleted.';
        $msgType = 'success';
    }
}

// ── Fetch all events ─────────────────────────────────────────────────────────
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events | MEJ Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .page-wrap { max-width:1100px; margin:0 auto; padding:30px 20px 60px; }

        .back-link { display:inline-block; margin-bottom:20px; color:#d4af37; text-decoration:none; font-size:0.9rem; }
        .back-link:hover { text-decoration:underline; }

        .page-title { color:#d4af37; margin-bottom:5px; }
        .page-sub   { color:#888; font-size:0.85rem; margin-bottom:25px; }

        .alert { padding:12px 16px; border-radius:4px; margin-bottom:20px; font-size:0.9rem; }
        .alert.success { background:#1e3a2f; border-left:4px solid #2ecc71; color:#a8f0c6; }
        .alert.error   { background:#3a1e1e; border-left:4px solid #e74c3c; color:#f0a8a8; }

        .form-card {
            background:#1a1a1c; border:1px solid #333; border-radius:8px;
            padding:25px; margin-bottom:30px;
        }
        .form-card h3 { color:#d4af37; margin-bottom:15px; font-size:1rem; }
        .form-row { display:flex; flex-wrap:wrap; gap:10px; align-items:end; }
        .form-group { display:flex; flex-direction:column; }
        .form-group label { font-size:0.75rem; color:#888; margin-bottom:4px; }
        .form-group input, .form-group select {
            background:#111; border:1px solid #444; color:#e0d9d1;
            padding:8px 10px; border-radius:4px; font-size:0.85rem;
        }
        .form-group input:focus, .form-group select:focus { border-color:#d4af37; outline:none; }

        .btn-gold {
            background:#d4af37; color:#000; border:none; padding:8px 20px;
            font-weight:bold; border-radius:4px; cursor:pointer; font-size:0.85rem;
        }
        .btn-gold:hover { background:#c09a20; }
        .btn-danger {
            background:transparent; color:#e74c3c; border:1px solid #e74c3c;
            padding:5px 12px; border-radius:4px; cursor:pointer; font-size:0.8rem;
        }
        .btn-danger:hover { background:#e74c3c; color:#fff; }
        .btn-edit {
            background:transparent; color:#3498db; border:1px solid #3498db;
            padding:5px 12px; border-radius:4px; cursor:pointer; font-size:0.8rem;
        }
        .btn-edit:hover { background:#3498db; color:#fff; }
        .btn-save {
            background:#2ecc71; color:#000; border:none;
            padding:5px 12px; border-radius:4px; cursor:pointer; font-size:0.8rem; font-weight:bold;
        }
        .btn-save:hover { background:#27ae60; }
        .btn-cancel {
            background:transparent; color:#888; border:1px solid #555;
            padding:5px 12px; border-radius:4px; cursor:pointer; font-size:0.8rem;
        }
        .btn-cancel:hover { background:#333; color:#ccc; }

        .search-bar { display:flex; gap:10px; margin-bottom:20px; align-items:center; }
        .search-bar input {
            flex:1; max-width:320px; background:#111; border:1px solid #444;
            color:#e0d9d1; padding:8px 12px; border-radius:4px; font-size:0.85rem;
        }
        .search-bar input:focus { border-color:#d4af37; outline:none; }

        .data-table { width:100%; border-collapse:collapse; font-size:0.85rem; }
        .data-table th {
            text-align:left; padding:10px; color:#d4af37;
            border-bottom:2px solid #333; font-weight:600;
        }
        .data-table td { padding:10px; border-bottom:1px solid #222; color:#bbb; }
        .data-table tr:hover { background:#1a1a1c; }
        .data-table .actions { white-space:nowrap; display:flex; gap:6px; }

        .data-table td input,
        .data-table td select {
            background:#111; border:1px solid #444; color:#e0d9d1;
            padding:4px 6px; border-radius:3px; font-size:0.82rem; width:100%;
        }

        .empty-state { text-align:center; color:#666; padding:40px 0; font-size:0.9rem; }

        .type-badge {
            display:inline-block; padding:2px 8px; border-radius:10px;
            font-size:0.75rem; font-weight:600;
        }
        .type-badge.big-band  { background:#2c2040; color:#b39ddb; }
        .type-badge.combo     { background:#1a3a2f; color:#81c784; }

        @media (max-width:768px) {
            .form-row { flex-direction:column; }
            .data-table { font-size:0.78rem; }
        }
    </style>
</head>
<body>
<div class="page-wrap">
    <a href="admin_dashboard.php" class="back-link">← Back to Admin Dashboard</a>
    <h1 class="page-title">📅 Manage Events</h1>
    <p class="page-sub">Add, edit, or remove events and practices.</p>

    <?php if ($message): ?>
        <div class="alert <?php echo $msgType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- ── Add New Event ───────────────────────────────────────────────── -->
    <div class="form-card">
        <h3>➕ Add New Event</h3>
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

    <!-- ── Search ──────────────────────────────────────────────────────── -->
    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search by event name, location, or type…"
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn-gold">Search</button>
        <?php if ($search): ?>
            <a href="manage_events.php" style="color:#888; font-size:0.8rem;">Clear</a>
        <?php endif; ?>
    </form>

    <!-- ── Events Table ─────────────────────────────────────────────────── -->
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
            <!-- ── Display row ──────────────────────────────────────────── -->
            <tr id="view-<?php echo $ev['id']; ?>">
                <td><?php echo (int)$ev['id']; ?></td>
                <td><?php echo htmlspecialchars($ev['event_name']); ?></td>
                <td><?php echo $ev['event_date'] ? date('M j, Y', strtotime($ev['event_date'])) : '—'; ?></td>
                <td><?php echo $ev['start_time'] ? date('g:i A', strtotime($ev['start_time'])) : '—'; ?></td>
                <td><?php echo $ev['end_time']   ? date('g:i A', strtotime($ev['end_time']))   : '—'; ?></td>
                <td><?php echo htmlspecialchars($ev['location']); ?></td>
                <td>
                    <?php
                        $cls = strtolower(str_replace(' ', '-', $ev['event_type']));
                    ?>
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

            <!-- ── Inline-edit row ──────────────────────────────────────── -->
            <tr id="edit-<?php echo $ev['id']; ?>" style="display:none; background:#141416;">
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="event_id" value="<?php echo $ev['id']; ?>">
                    <td><?php echo (int)$ev['id']; ?></td>
                    <td><input type="text" name="event_name" value="<?php echo htmlspecialchars($ev['event_name']); ?>" required></td>
                    <td><input type="date" name="event_date" value="<?php echo $ev['event_date']; ?>" required></td>
                    <td><input type="time" name="start_time" value="<?php echo $ev['start_time'] ? date('H:i', strtotime($ev['start_time'])) : ''; ?>"></td>
                    <td><input type="time" name="end_time" value="<?php echo $ev['end_time'] ? date('H:i', strtotime($ev['end_time'])) : ''; ?>"></td>
                    <td><input type="text" name="location" value="<?php echo htmlspecialchars($ev['location']); ?>"></td>
                    <td>
                        <select name="event_type">
                            <option value="Big Band" <?php echo $ev['event_type']==='Big Band' ? 'selected' : ''; ?>>Big Band</option>
                            <option value="Combo" <?php echo $ev['event_type']==='Combo' ? 'selected' : ''; ?>>Combo</option>
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
    <?php endif; ?>

    <p style="color:#555; font-size:0.75rem; margin-top:20px;">
        Total events: <?php echo count($events); ?>
    </p>
</div>

<script>
    function toggleEdit(id) {
        const viewRow = document.getElementById('view-' + id);
        const editRow = document.getElementById('edit-' + id);
        if (editRow.style.display === 'none') {
            viewRow.style.display = 'none';
            editRow.style.display = '';
        } else {
            viewRow.style.display = '';
            editRow.style.display = 'none';
        }
    }
</script>
</body>
</html>
