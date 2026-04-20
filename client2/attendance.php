<?php
include 'includes/auth_check.php'; // allows members + admins
require 'config/db.php';

/*Get all events (columns) */
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

/*Build attendance lookup table*/
$stmt = $pdo->query("SELECT member_id, event_id, status, time_note FROM attendance");

$attendance = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $attendance[$row['member_id']][$row['event_id']] = [
        'status' => $row['status'],
        'note' => $row['time_note']
    ];
}

/*Group members by instrument*/
$grouped = [];
foreach ($members as $m) {
    $grouped[$m['instrument']][] = $m;
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

    <style>
        .attendance-grid {
            overflow-x: auto;
            margin-top: 20px;
        }

        .data-table {
            border-collapse: collapse;
            width: 100%;
            min-width: 900px;
            text-align: center;
        }

        .data-table th,
        .data-table td {
            padding: 10px;
            border-right: 1px solid #222;
        }

        .data-table th:last-child,
        .data-table td:last-child {
            border-right: none;
        }

        .member-name {
            text-align: left;
            font-weight: 500;
            white-space: nowrap;
        }

        .instrument-header {
            background: #111;
            color: #fec841;
            font-size: 1.05rem;
            text-align: left;
            padding: 10px;
            border-top: 2px solid #333;
        }

        .check {
            color: #4caf50;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .late {
            color: #ffcc00;
        }

        .miss {
            color: #666;
        }

        .event-header {
            font-size: 0.85rem;
        }

        .event-date {
            font-size: 0.7rem;
            color: #aaa;
        }
    </style>
</head>

<body>

<div class="page-wrap">
    <a href="index.php" class="back-link">← Back</a>

    <h1 class="page-title">🎷 Attendance</h1>
    <p class="page-sub">See who showed up (and who didn’t 👀)</p>

    <div class="attendance-grid">
        <table class="data-table">

            <!-- HEADER -->
            <thead>
                <tr>
                    <th>Member</th>
                    <?php foreach ($events as $event): ?>
                        <th class="event-header">
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

                    <!-- Instrument Divider -->
                    <tr>
                        <td colspan="<?= count($events) + 1 ?>" class="instrument-header">
                            <?= htmlspecialchars($instrument) ?>
                        </td>
                    </tr>

                    <!-- Members -->
                    <?php foreach ($membersList as $m): ?>
                        <tr>
                            <td class="member-name">
                                <?= htmlspecialchars($m['last_name'] . ', ' . $m['first_name']) ?>
                            </td>

                            <?php foreach ($events as $event): 
                                $cell = $attendance[$m['id']][$event['id']] ?? null;
                            ?>
                                <td>
                                    <?php if ($cell): ?>
                                        <?php if ($cell['status'] === 'Attending'): ?>
                                            <span class="check" title="<?= htmlspecialchars($cell['note'] ?? '') ?>">✔</span>

                                        <?php elseif ($cell['status'] === 'Late'): ?>
                                            <span class="late" title="<?= htmlspecialchars($cell['note'] ?? '') ?>">⏰</span>

                                        <?php elseif ($cell['status'] === 'Away' || $cell['status'] === 'Leave'): ?>
                                            <span class="miss" title="<?= htmlspecialchars($cell['note'] ?? '') ?>">✖</span>

                                        <?php else: ?>
                                            <span class="miss">—</span>
                                        <?php endif; ?>

                                    <?php else: ?>
                                        <span class="miss">—</span>
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