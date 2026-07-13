<?php
$conn = new mysqli("localhost", "root", "", "jazz_band");

// Get all events
$events_result = $conn->query("SELECT * FROM events ORDER BY event_date");

// Store events in array (IMPORTANT fix)
$events = [];
while ($row = $events_result->fetch_assoc()) {
    $events[] = $row;
}

// Get attendance data
$sql = "
SELECT 
    m.instrument,
    e.id AS event_id,
    COUNT(DISTINCT m.id) AS total_members,
    SUM(a.present) AS total_present
FROM members m
JOIN attendance a ON m.id = a.member_id
JOIN events e ON a.event_id = e.id
GROUP BY m.instrument, e.id
ORDER BY m.instrument, e.event_date
";

$result = $conn->query($sql);

// Organize data
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[$row['instrument']][$row['event_id']] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Attendance</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<nav class="navbar">
    <div class="logo">Mac Eng Jazz</div>
    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
    </ul>
</nav>

<section class="leaderboard dark-bg">
    <div class="container">
        <h2>Public Attendance</h2>

        <table>
            <thead>
                <tr>
                    <th>Instrument</th>

                    <?php foreach ($events as $event): ?>
                        <th>
                            <?php echo htmlspecialchars($event['event_name']); ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($data as $instrument => $eventData): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($instrument); ?></td>

                        <?php foreach ($events as $event): 
                            $event_id = $event['id'];

                            if (isset($eventData[$event_id])) {
                                $row = $eventData[$event_id];

                                $present = $row['total_present'] ?? 0;
                                $total = $row['total_members'] ?? 0;

                                $text = $present . " / " . $total;
                            } else {
                                $text = "-";
                            }
                        ?>
                            <td style="text-align:center;">
                                <?php echo $text; ?>
                            </td>
                        <?php endforeach; ?>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</section>

</body>
</html>