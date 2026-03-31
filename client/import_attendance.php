<?php
/**
 * Excel → MySQL Importer for MEJ Attendance
 * ------------------------------------------
 * Place this file at: /import_attendance.php
 *
 * NO Composer needed. Just drop SimpleXLSX.php into a /libs/ folder:
 *   Download from: https://github.com/shuchkin/simplexlsx
 *   Save as: libs/SimpleXLSX.php
 *
 * Excel file format expected (MEJ attendance sheet):
 *   Row 1:  Event names       (col E onward)
 *   Row 2:  Dates             (col E onward)
 *   Row 3:  Times             (col E onward)
 *   Row 4:  Locations         (col E onward)
 *   Col A:  Section
 *   Col B:  Instrument
 *   Col C:  First Name
 *   Col D:  Last Name
 *   Last col: Dietary Restrictions
 *   Member rows start at row 5.
 */

include 'includes/auth_check.php';
require 'config/db.php';
require 'libs/SimpleXLSX.php'; // ← only dependency, no Composer needed

// Only admins can access this
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// ── Column/Row constants (0-indexed, since SimpleXLSX returns plain arrays) ──
define('COL_SECTION',     0); // A
define('COL_INSTRUMENT',  1); // B
define('COL_FIRST_NAME',  2); // C
define('COL_LAST_NAME',   3); // D
define('COL_EVENTS_START',4); // E onward

define('ROW_EVENT_NAMES',  0); // Row 1
define('ROW_DATES',        1); // Row 2
define('ROW_TIMES',        2); // Row 3
define('ROW_LOCATIONS',    3); // Row 4
define('ROW_MEMBERS_START',4); // Row 5 onward

// ── Helper functions ──────────────────────────────────────────────────────────

/**
 * Parse attendance cell value into [status, time_note].
 * Handles: "Attending", "Away", "Late 8:45 PM", "Leave 7:00 PM"
 */
function parseAttendance(string $raw): array {
    $raw = trim($raw);
    if (stripos($raw, 'Attending') !== false) return ['Attending', null];
    if (stripos($raw, 'Away')      !== false) return ['Away',      null];
    if (preg_match('/^Late\s+(\d{1,2}:\d{2})/i',  $raw, $m)) return ['Late',  $m[1]];
    if (preg_match('/^Leave\s+(\d{1,2}:\d{2})/i', $raw, $m)) return ['Leave', $m[1]];
    return ['Away', null]; // unknown → Away
}

/**
 * Parse "6:30-8:30 PM" or "9:00 AM-12:00 noon" into [start, end] as "HH:MM:SS".
 */
function parseTimeRange(string $raw): array {
    $raw = trim($raw);
    if (preg_match('/(\d{1,2}:\d{2})\s*(AM|PM|noon)?\s*[-–]\s*(\d{1,2}:\d{2})\s*(AM|PM|noon)?/i', $raw, $m)) {
        $start = to24h($m[1], $m[2]);
        $end   = to24h($m[3], $m[4] ?: $m[2]);
        return [$start, $end];
    }
    return [null, null];
}

function to24h(string $time, string $ampm): ?string {
    [$h, $min] = explode(':', $time);
    $h    = (int)$h;
    $ampm = strtolower(trim($ampm));
    if ($ampm === 'pm'   && $h !== 12) $h += 12;
    if ($ampm === 'am'   && $h === 12) $h  = 0;
    if ($ampm === 'noon')              $h  = 12;
    return sprintf('%02d:%02d:00', $h, (int)$min);
}

function detectEventType(string $name): string {
    foreach (['combo', 'small group'] as $kw) {
        if (stripos($name, $kw) !== false) return 'Combo';
    }
    return 'Big Band';
}

function isAdjunct(string $section): bool {
    return stripos($section, 'adjunct') !== false;
}

/**
 * SimpleXLSX returns dates as Excel serial numbers (numeric strings).
 * Convert to Y-m-d.
 */
function excelDateToString($val): ?string {
    if (empty($val)) return null;
    if (is_numeric($val)) {
        $unix = ((float)$val - 25569) * 86400; // Excel epoch = Dec 30 1899
        return date('Y-m-d', (int)$unix);
    }
    $ts = strtotime((string)$val);
    return $ts ? date('Y-m-d', $ts) : null;
}

// ── Main upload logic ─────────────────────────────────────────────────────────

$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file     = $_FILES['excel_file'];
    $origName = basename($file['name']);
    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    if ($ext !== 'xlsx') {
        $message = "Invalid file type. Please upload a .xlsx file.";
        $msgType = 'error';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "Upload error code: " . $file['error'];
        $msgType = 'error';
    } else {
        $xlsx = SimpleXLSX::parse($file['tmp_name']);

        if (!$xlsx) {
            $message = "Could not read file: " . SimpleXLSX::parseError();
            $msgType = 'error';
        } else {
            // rows() gives a 0-indexed array of rows; each row is a 0-indexed array of cell values
            $rows     = $xlsx->rows();
            $inserted = 0;
            $skipped  = 0;

            if (count($rows) < ROW_MEMBERS_START + 1) {
                $message = "File appears empty or doesn't match the expected format.";
                $msgType = 'error';
            } else {
                $totalCols = count($rows[ROW_EVENT_NAMES]);
                $lastCol   = $totalCols - 1; // last column = dietary restrictions

                // ── Step 1: Read event header rows ───────────────────────────
                $events = [];
                for ($col = COL_EVENTS_START; $col < $lastCol; $col++) {
                    $name     = trim((string)($rows[ROW_EVENT_NAMES][$col] ?? ''));
                    $rawDate  = $rows[ROW_DATES][$col] ?? '';
                    $rawTime  = trim((string)($rows[ROW_TIMES][$col] ?? ''));
                    $location = trim((string)($rows[ROW_LOCATIONS][$col] ?? ''));

                    if (empty($name)) continue;

                    $date = excelDateToString($rawDate);
                    if (!$date) continue;

                    [$startTime, $endTime] = parseTimeRange($rawTime);

                    $events[$col] = [
                        'name'       => $name,
                        'date'       => $date,
                        'start_time' => $startTime,
                        'end_time'   => $endTime,
                        'location'   => $location,
                        'type'       => detectEventType($name),
                    ];
                }

                if (empty($events)) {
                    $message = "No events found in header rows. Check the file format.";
                    $msgType = 'error';
                } else {
                    // ── Step 2: Upsert events into DB ────────────────────────
                    $eventIdMap = [];
                    $stmtEvent  = $pdo->prepare("
                        INSERT INTO events (event_name, event_date, start_time, end_time, location, event_type)
                        VALUES (:name, :date, :start, :end, :loc, :type)
                        ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)
                    ");
                    foreach ($events as $col => $ev) {
                        $stmtEvent->execute([
                            ':name'  => $ev['name'],
                            ':date'  => $ev['date'],
                            ':start' => $ev['start_time'],
                            ':end'   => $ev['end_time'],
                            ':loc'   => $ev['location'],
                            ':type'  => $ev['type'],
                        ]);
                        $eventIdMap[$col] = $pdo->lastInsertId();
                    }

                    // ── Step 3: Read member rows & insert attendance ──────────
                    $stmtMember = $pdo->prepare("
                        INSERT IGNORE INTO members
                            (first_name, last_name, section, instrument, dietary_restrictions, is_adjunct)
                        VALUES (:fn, :ln, :sec, :ins, :diet, :adj)
                    ");
                    $stmtGetMember = $pdo->prepare("
                        SELECT id FROM members WHERE first_name = :fn AND last_name = :ln
                    ");
                    $stmtAttend = $pdo->prepare("
                        INSERT INTO attendance (member_id, event_id, status, time_note)
                        VALUES (:mid, :eid, :status, :note)
                        ON DUPLICATE KEY UPDATE id = id
                    ");

                    $currentSection = '';

                    for ($r = ROW_MEMBERS_START; $r < count($rows); $r++) {
                        $row        = $rows[$r];
                        $section    = trim((string)($row[COL_SECTION]    ?? ''));
                        $instrument = trim((string)($row[COL_INSTRUMENT]  ?? ''));
                        $firstName  = trim((string)($row[COL_FIRST_NAME]  ?? ''));
                        $lastName   = trim((string)($row[COL_LAST_NAME]   ?? ''));
                        $dietary    = trim((string)($row[$lastCol]         ?? ''));

                        // Section header row (e.g. "Reeds", "Trumpets") — no name
                        if (empty($firstName) && empty($lastName)) {
                            if (!empty($section)) $currentSection = $section;
                            continue;
                        }

                        $adjunct          = isAdjunct($currentSection) ? 1 : 0;
                        $effectiveSection = $adjunct ? 'Adjunct' : $currentSection;

                        // Insert member — IGNORE skips if already exists
                        $stmtMember->execute([
                            ':fn'   => $firstName,
                            ':ln'   => $lastName,
                            ':sec'  => $effectiveSection,
                            ':ins'  => $instrument,
                            ':diet' => $dietary ?: 'n/a',
                            ':adj'  => $adjunct,
                        ]);

                        // Fetch the member's DB id
                        $stmtGetMember->execute([':fn' => $firstName, ':ln' => $lastName]);
                        $memberId = $stmtGetMember->fetchColumn();
                        if (!$memberId) continue;

                        // One attendance row per event column
                        foreach ($eventIdMap as $col => $eventId) {
                            $rawStatus = trim((string)($row[$col] ?? ''));
                            if (empty($rawStatus)) { $skipped++; continue; }

                            [$status, $timeNote] = parseAttendance($rawStatus);

                            $stmtAttend->execute([
                                ':mid'    => $memberId,
                                ':eid'    => $eventId,
                                ':status' => $status,
                                ':note'   => $timeNote,
                            ]);

                            // rowCount 1 = inserted, 0 = duplicate skipped
                            $stmtAttend->rowCount() > 0 ? $inserted++ : $skipped++;
                        }
                    }

                    // ── Step 4: Log the import ────────────────────────────────
                    $pdo->prepare("
                        INSERT INTO import_log (filename, imported_by, rows_inserted, rows_skipped)
                        VALUES (:fn, :uid, :ins, :skip)
                    ")->execute([
                        ':fn'   => $origName,
                        ':uid'  => $_SESSION['user_id'],
                        ':ins'  => $inserted,
                        ':skip' => $skipped,
                    ]);

                    $message = "Import complete! $inserted records inserted, $skipped skipped (duplicates or empty).";
                    $msgType = 'success';
                }
            }
        }
    }
}

// ── Recent import history ─────────────────────────────────────────────────────
$importHistory = $pdo->query("
    SELECT il.filename, il.imported_at, il.rows_inserted, il.rows_skipped, u.username
    FROM import_log il
    LEFT JOIN users u ON u.id = il.imported_by
    ORDER BY il.imported_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Attendance | MEJ Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .import-box {
            background: #1a1a1c;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 30px;
            max-width: 620px;
            margin: 40px auto;
        }
        .import-box h2 { color: #d4af37; margin-bottom: 20px; }
        .import-box input[type="file"] {
            display: block;
            width: 100%;
            padding: 10px;
            background: #111;
            color: #e0d9d1;
            border: 1px dashed #555;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .import-box button {
            background: #d4af37;
            color: #000;
            border: none;
            padding: 10px 25px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
        }
        .import-box button:hover { background: #c09a20; }
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .alert.success { background: #1e3a2f; border-left: 4px solid #2ecc71; color: #a8f0c6; }
        .alert.error   { background: #3a1e1e; border-left: 4px solid #e74c3c; color: #f0a8a8; }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            font-size: 0.85rem;
        }
        .history-table th {
            color: #d4af37;
            text-align: left;
            padding: 8px 10px;
            border-bottom: 1px solid #333;
        }
        .history-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #222;
            color: #bbb;
        }
        .back-link { display:inline-block; margin:20px 0; color:#d4af37; text-decoration:none; }
        .back-link:hover { text-decoration: underline; }
        .note { font-size: 0.8rem; color: #666; margin-top: 10px; line-height: 1.6; }
    </style>
</head>
<body>
<div class="container">
    <a href="admin_dashboard.php" class="back-link">← Back to Admin Dashboard</a>

    <div class="import-box">
        <h2>📥 Import Attendance from Excel</h2>

        <?php if ($message): ?>
            <div class="alert <?php echo $msgType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label style="color:#aaa; font-size:0.85rem; display:block; margin-bottom:8px;">
                Upload Excel file (.xlsx only)
            </label>
            <input type="file" name="excel_file" accept=".xlsx" required>
            <p class="note">
                Expected format: Row 1 = Event names &nbsp;|&nbsp; Row 2 = Dates &nbsp;|&nbsp;
                Row 3 = Times &nbsp;|&nbsp; Row 4 = Locations<br>
                Col A = Section &nbsp;|&nbsp; Col B = Instrument &nbsp;|&nbsp;
                Col C = First Name &nbsp;|&nbsp; Col D = Last Name &nbsp;|&nbsp;
                Last col = Dietary restrictions &nbsp;|&nbsp; Members start at Row 5
            </p>
            <br>
            <button type="submit">Upload &amp; Import</button>
        </form>

        <?php if (!empty($importHistory)): ?>
        <h3 style="color:#d4af37; margin-top:35px; font-size:1rem;">Recent Imports</h3>
        <table class="history-table">
            <tr>
                <th>File</th>
                <th>By</th>
                <th>Date</th>
                <th>Inserted</th>
                <th>Skipped</th>
            </tr>
            <?php foreach ($importHistory as $log): ?>
            <tr>
                <td><?php echo htmlspecialchars($log['filename']); ?></td>
                <td><?php echo htmlspecialchars($log['username'] ?? '—'); ?></td>
                <td><?php echo date('M j, Y g:i A', strtotime($log['imported_at'])); ?></td>
                <td style="color:#2ecc71"><?php echo (int)$log['rows_inserted']; ?></td>
                <td style="color:#888"><?php echo (int)$log['rows_skipped']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
