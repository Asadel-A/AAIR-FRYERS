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

    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM attendance WHERE member_id = :id")
            ->execute([':id' => (int)$_POST['member_id']]);
        $pdo->prepare("DELETE FROM members WHERE id = :id")
            ->execute([':id' => (int)$_POST['member_id']]);
        $message = 'Member deleted.';
        $msgType = 'success';
    }
}

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
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members | MEJ Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="page-wrap">
    <a href="admin_dashboard.php" class="back-link">← Back to Admin Dashboard</a>
    <h1 class="page-title">👥 Manage Members</h1>
    <p class="page-sub">Add, edit, or remove band members from the database.</p>

    <?php if ($message): ?>
        <div class="alert <?php echo $msgType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

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

    <form method="GET" class="search-bar">
        <input type="text" name="search"
               placeholder="Search by name, section, or instrument…"
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn-gold">Search</button>
        <?php if ($search): ?>
            <a href="manage_members.php" style="color:#888; font-size:0.8rem;">Clear</a>
        <?php endif; ?>
    </form>

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
            <tr id="edit-<?php echo $m['id']; ?>" style="display:none; background:#141416;">
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="member_id" value="<?php echo $m['id']; ?>">
                    <td><?php echo (int)$m['id']; ?></td>
                    <td><input type="text" name="first_name" value="<?php echo htmlspecialchars($m['first_name']); ?>" required></td>
                    <td><input type="text" name="last_name"  value="<?php echo htmlspecialchars($m['last_name']); ?>"  required></td>
                    <td><input type="text" name="section"    value="<?php echo htmlspecialchars($m['section']); ?>"    required></td>
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

    <p style="color:#555; font-size:0.75rem; margin-top:20px;">
        Total members: <?php echo count($members); ?>
    </p>
    <?php endif; ?>
</div>

<script src="js/admin-tables.js"></script>
</body>
</html>
