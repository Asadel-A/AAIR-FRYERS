<?php
include 'includes/auth_check.php';
require 'config/db.php';

$currentUserId = $_SESSION['user_id'];

/*Get all events*/
$events = $pdo->query("
    SELECT id, event_name, event_date 
    FROM events 
    ORDER BY event_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

/*Get all members*/
$members = $pdo->query("
    SELECT id, first_name, last_name, instrument 
    FROM members 
    ORDER BY instrument, last_name, first_name
")->fetchAll(PDO::FETCH_ASSOC);

/*Attendance lookup table*/
$stmt = $pdo->query("
    SELECT member_id, event_id, status, time_note 
    FROM attendance
");

$attendance = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $attendance[$row['member_id']][$row['event_id']] = $row;
}

/*Group members by instrument + anonymize others*/
$grouped = [];
$instrumentCounters = [];

foreach ($members as $m) {
    $instrument = $m['instrument'];

    if (!isset($instrumentCounters[$instrument])) {
        $instrumentCounters[$instrument] = 1;
    }

    // Default anonymous label
    $label = $instrument . ' ' . $instrumentCounters[$instrument];

    // If user is logged in, show real name
    $isSelf = ($m['id'] == $currentUserId);

    if ($isSelf) {
        $label = $m['first_name'] . ' ' . $m['last_name'];
    }

    $grouped[$instrument][] = [
        'id' => $m['id'],
        'label' => $label,
        'is_self' => $isSelf
    ];

    $instrumentCounters[$instrument]++;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance | Mac Eng Jazz</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="attendance.css">
</head>

<body>

<div class="page-wrap">

    <a href="index.php" class="back-link">← Back</a>

    <h1 class="page-title">🎷 Attendance</h1>
    <p class="page-sub">Your attendance overview</p>

    <div class="attendance-grid">
        <table class="data-table">

            <!-- HEADER -->
            <thead>
                <tr>
                    <th>Member</th>
                    <?php foreach ($events as $event): ?>
                        <th>
                            <?= htmlspecialchars($event['event_name']) ?><br>
                            <span class="event-date">
                                <?= $event['event_date'] ? date('M j', strtotime($event['event_date'])) : '' ?>
                            </span>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($grouped as $instrument => $membersList): ?>

                    <!-- Instrument header -->
                    <tr>
                        <td colspan="<?= count($events) + 1 ?>" class="instrument-header">
                            <?= htmlspecialchars($instrument) ?>
                        </td>
                    </tr>

                    <!-- Members -->
                    <?php foreach ($membersList as $m): ?>
                        <tr>

                            <!-- MEMBER LABEL -->
                            <td class="member-name">
                                <?php if ($m['is_self']): ?>
                                    <span style="color:#d4af37; font-weight:bold;">
                                        <?= htmlspecialchars($m['label']) ?> (You)
                                    </span>
                                <?php else: ?>
                                    <?= htmlspecialchars($m['label']) ?>
                                <?php endif; ?>
                            </td>

                            <!-- EVENTS -->
                            <?php foreach ($events as $event): 
                                $cell = $attendance[$m['id']][$event['id']] ?? null;
                            ?>
                                <td>
                                    <?php if ($cell && $cell['status'] === 'Attending'): ?>
                                        ✔
                                    <?php elseif ($cell && $cell['status'] === 'Late'): ?>
                                        ⏰
                                    <?php elseif ($cell): ?>
                                        ✖
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>

                        </tr>
                    <?php endforeach; ?>

                <?php endforeach; ?>

            </tbody>
        </table>
    </div>

</div>

</body>
</html>