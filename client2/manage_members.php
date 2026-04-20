<?php
/**
 * Manage Members — INSERT / UPDATE / DELETE
 * ------------------------------------------
 * Aneek's contribution: lets the admin manually add, edit, or remove
 * member records in the database.
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
            INSERT INTO members (first_name, last_name, section, instrument, dietary_restrictions, is_adjunct)
            VALUES (:fn, :ln, :sec, :ins, :diet, :adj)
        ");
        $stmt->execute([
            ':fn'   => trim($_POST['first_name']),
            ':ln'   => trim($_POST['last_name']),
            ':sec'  => trim($_POST['section']),
            ':ins'  => trim($_POST['instrument']),
            ':diet' => trim($_POST['dietary_restrictions']) ?: 'n/a',
            ':adj'  => isset($_POST['is_adjunct']) ? 1 : 0,
        ]);
        $message = 'Member added successfully.';
        $msgType = 'success';
    }

    // ── UPDATE ───────────────────────────────────────────────────────────────
    if ($action === 'update') {
        $stmt = $pdo->prepare("
            UPDATE members
            SET first_name = :fn,
                last_name  = :ln,
                section    = :sec,
                instrument = :ins,
                dietary_restrictions = :diet,
                is_adjunct = :adj
            WHERE id = :id
        ");
        $stmt->execute([
            ':fn'   => trim($_POST['first_name']),
            ':ln'   => trim($_POST['last_name']),
            ':sec'  => trim($_POST['section']),
            ':ins'  => trim($_POST['instrument']),
            ':diet' => trim($_POST['dietary_restrictions']) ?: 'n/a',
            ':adj'  => isset($_POST['is_adjunct']) ? 1 : 0,
            ':id'   => (int)$_POST['member_id'],
        ]);
        $message = 'Member updated successfully.';
        $msgType = 'success';
    }

    // ── DELETE ───────────────────────────────────────────────────────────────
    if ($action === 'delete') {
        // Delete related attendance records first to avoid FK issues
        $pdo->prepare("DELETE FROM attendance WHERE member_id = :id")
            ->execute([':id' => (int)$_POST['member_id']]);
        $pdo->prepare("DELETE FROM members WHERE id = :id")
            ->execute([':id' => (int)$_POST['member_id']]);
        $message = 'Member deleted.';
        $msgType = 'success';
    }
}

// ── Search / Filter ──────────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$where  = '';
$params = [];

if ($search !== '') {
    $where  = "WHERE first_name LIKE :s OR last_name LIKE :s OR section LIKE :s OR instrument LIKE :s";
    $params = [':s' => "%$search%"];
}

$members = $pdo->prepare("SELECT * FROM members $where ORDER BY section, last_name, first_name");
$members->execute($params);
$members = $members->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members | MEJ Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── Page-level styles ─────────────────────────────────────────── */
        .page-wrap { max-width: 1100px; margin: 0 auto; padding: 30px 20px 60px; }

        .back-link { display:inline-block; margin-bottom:20px; color:#d4af37; text-decoration:none; font-size:0.9rem; }
        .back-link:hover { text-decoration:underline; }

        .page-title { color:#d4af37; margin-bottom:5px; }
        .page-sub   { color:#888; font-size:0.85rem; margin-bottom:25px; }

        /* Alert */
        .alert { padding:12px 16px; border-radius:4px; margin-bottom:20px; font-size:0.9rem; }
        .alert.success { background:#1e3a2f; border-left:4px solid #2ecc71; color:#a8f0c6; }
        .alert.error   { background:#3a1e1e; border-left:4px solid #e74c3c; color:#f0a8a8; }

        /* Add-member card */
        .form-card {
            background:#1a1a1c; border:1px solid #333; border-radius:8px;
            padding:25px; margin-bottom:30px;
        }
        .form-card h3 { color:#d4af37; margin-bottom:15px; font-size:1rem; }
        .form-row {
            display:flex; flex-wrap:wrap; gap:10px; align-items:end;
        }
        .form-group { display:flex; flex-direction:column; }
        .form-group label { font-size:0.75rem; color:#888; margin-bottom:4px; }
        .form-group input, .form-group select {
            background:#111; border:1px solid #444; color:#e0d9d1;
            padding:8px 10px; border-radius:4px; font-size:0.85rem;
        }
        .form-group input:focus, .form-group select:focus { border-color:#d4af37; outline:none; }
        .checkbox-group { flex-direction:row; align-items:center; gap:6px; }
        .checkbox-group input[type="checkbox"] { width:16px; height:16px; accent-color:#d4af37; }

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

        /* Search bar */
        .search-bar {
            display:flex; gap:10px; margin-bottom:20px; align-items:center;
        }
        .search-bar input {
            flex:1; max-width:320px; background:#111; border:1px solid #444;
            color:#e0d9d1; padding:8px 12px; border-radius:4px; font-size:0.85rem;
        }
        .search-bar input:focus { border-color:#d4af37; outline:none; }

        /* Data table */
        .data-table { width:100%; border-collapse:collapse; font-size:0.85rem; }
        .data-table th {
            text-align:left; padding:10px; color:#d4af37;
            border-bottom:2px solid #333; font-weight:600;
        }
        .data-table td {
            padding:10px; border-bottom:1px solid #222; color:#bbb;
        }
        .data-table tr:hover { background:#1a1a1c; }
        .data-table .actions { white-space:nowrap; display:flex; gap:6px; }

        /* Inline-edit inputs */
        .data-table td input,
        .data-table td select {
            background:#111; border:1px solid #444; color:#e0d9d1;
            padding:4px 6px; border-radius:3px; font-size:0.82rem; width:100%;
        }

        .empty-state { text-align:center; color:#666; padding:40px 0; font-size:0.9rem; }

        /* Responsive */
        @media (max-width:768px) {
            .form-row { flex-direction:column; }
            .data-table { font-size:0.78rem; }
        }
    </style>
</head>
<body>
<div class="page-wrap">
    <a href="admin_dashboard.php" class="back-link">← Back to Admin Dashboard</a>
    <h1 class="page-title">👥 Manage Members</h1>
    <p class="page-sub">Add, edit, or remove band members from the database.</p>

    <?php if ($message): ?>
        <div class="alert <?php echo $msgType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- ── Add New Member ──────────────────────────────────────────────── -->
    <div class="form-card">
        <h3>➕ Add New Member</h3>
        <form method="POST">
            <input type="hidden" name="action" value="insert">
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" required placeholder="e.g. John">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required placeholder="e.g. Smith">
                </div>
                <div class="form-group">
                    <label>Section</label>
                    <input type="text" name="section" required placeholder="e.g. Reeds">
                </div>
                <div class="form-group">
                    <label>Instrument</label>
                    <input type="text" name="instrument" required placeholder="e.g. Alto Sax">
                </div>
                <div class="form-group">
                    <label>Dietary Restrictions</label>
                    <input type="text" name="dietary_restrictions" placeholder="n/a">
                </div>
                <div class="form-group checkbox-group">
                    <input type="checkbox" name="is_adjunct" id="adj_new">
                    <label for="adj_new" style="margin-bottom:0">Adjunct</label>
                </div>
                <button type="submit" class="btn-gold">Add Member</button>
            </div>
        </form>
    </div>

    <!-- ── Search ──────────────────────────────────────────────────────── -->
    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search by name, section, or instrument…"
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn-gold">Search</button>
        <?php if ($search): ?>
            <a href="manage_members.php" style="color:#888; font-size:0.8rem;">Clear</a>
        <?php endif; ?>
    </form>

    <!-- ── Members Table ────────────────────────────────────────────────── -->
    <?php if (empty($members)): ?>
        <div class="empty-state">No members found.</div>
    <?php else: ?>
    <table class="data-table" id="members-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Section</th>
                <th>Instrument</th>
                <th>Dietary</th>
                <th>Adj.</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($members as $m): ?>
            <!-- ── Display row ──────────────────────────────────────────── -->
            <tr id="view-<?php echo $m['id']; ?>">
                <td><?php echo (int)$m['id']; ?></td>
                <td><?php echo htmlspecialchars($m['first_name']); ?></td>
                <td><?php echo htmlspecialchars($m['last_name']); ?></td>
                <td><?php echo htmlspecialchars($m['section']); ?></td>
                <td><?php echo htmlspecialchars($m['instrument']); ?></td>
                <td><?php echo htmlspecialchars($m['dietary_restrictions']); ?></td>
                <td><?php echo $m['is_adjunct'] ? '✓' : '—'; ?></td>
                <td class="actions">
                    <button class="btn-edit" onclick="toggleEdit(<?php echo $m['id']; ?>)">Edit</button>
                    <form method="POST" style="display:inline"
                          onsubmit="return confirm('Delete <?php echo htmlspecialchars(addslashes($m['first_name'] . ' ' . $m['last_name'])); ?>? This also removes their attendance records.');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="member_id" value="<?php echo $m['id']; ?>">
                        <button type="submit" class="btn-danger">Delete</button>
                    </form>
                </td>
            </tr>

            <!-- ── Inline-edit row (hidden by default) ──────────────────── -->
            <tr id="edit-<?php echo $m['id']; ?>" style="display:none; background:#141416;">
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="member_id" value="<?php echo $m['id']; ?>">
                    <td><?php echo (int)$m['id']; ?></td>
                    <td><input type="text" name="first_name" value="<?php echo htmlspecialchars($m['first_name']); ?>" required></td>
                    <td><input type="text" name="last_name" value="<?php echo htmlspecialchars($m['last_name']); ?>" required></td>
                    <td><input type="text" name="section" value="<?php echo htmlspecialchars($m['section']); ?>" required></td>
                    <td><input type="text" name="instrument" value="<?php echo htmlspecialchars($m['instrument']); ?>" required></td>
                    <td><input type="text" name="dietary_restrictions" value="<?php echo htmlspecialchars($m['dietary_restrictions']); ?>"></td>
                    <td><input type="checkbox" name="is_adjunct" <?php echo $m['is_adjunct'] ? 'checked' : ''; ?> style="width:16px;height:16px;accent-color:#d4af37;"></td>
                    <td class="actions">
                        <button type="submit" class="btn-save">Save</button>
                        <button type="button" class="btn-cancel" onclick="toggleEdit(<?php echo $m['id']; ?>)">Cancel</button>
                    </td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <p style="color:#555; font-size:0.75rem; margin-top:20px;">
        Total members: <?php echo count($members); ?>
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
