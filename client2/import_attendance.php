<?php
ob_start();

include 'includes/auth_check.php';
require 'config/db.php';
require 'libs/SimpleXLSX.php';
require 'libs/SimpleXLSXGen.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

define('SHEET_NAME',       'Band Attendance 2026 Winter');
define('ROW_EVENT_NAMES',   0); // Row 1  — event names start at col 4
define('ROW_DATES',         1); // Row 2  — event dates
define('ROW_TIMES',         2); // Row 3  — event time ranges
define('ROW_LOCATIONS',     3); // Row 4  — event locations
define('ROW_MEMBERS_START', 5); // Row 6  — first member row (row 5 is column headers)
define('COL_SECTION',       0); // Col A  — section name (only on first member of section)
define('COL_INSTRUMENT',    1); // Col B  — instrument
define('COL_FIRST_NAME',    2); // Col C  — first name
define('COL_LAST_NAME',     3); // Col D  — last name
define('COL_EVENTS_START',  4); // Col E  — first event attendance column
define('COL_EVENTS_END',   40); // Col AO — last event attendance column
define('COL_DIETARY',      41); // Col AP — dietary restrictions

/**
 * Parses a raw attendance cell value into a status string and optional time note.
 * Handles "Attending", "Away", "Late HH:MM", and "Leave HH:MM".
 *
 * @param string $raw The raw string value from the Excel cell
 * @return array [status (string), time_note (string|null)]
 */
function parseAttendance(string $raw): array {
    $raw = trim($raw);
    if (stripos($raw, 'Attending') !== false) return ['Attending', null];
    if (stripos($raw, 'Away')      !== false) return ['Away',      null];
    if (preg_match('/^Late\s+(\d{1,2}:\d{2})/i',  $raw, $m)) return ['Late',  $m[1]];
    if (preg_match('/^Leave\s+(\d{1,2}:\d{2})/i', $raw, $m)) return ['Leave', $m[1]];
    return ['Away', null];
}

/**
 * Parses a time range string (e.g. "6:30-8:30 PM") into 24-hour start and end times.
 *
 * @param string $raw The raw time range string from the Excel cell
 * @return array [start_time (string|null), end_time (string|null)] formatted as "HH:MM:SS"
 */
function parseTimeRange(string $raw): array {
    if (preg_match('/(\d{1,2}:\d{2})\s*(AM|PM|noon)?\s*[-–]\s*(\d{1,2}:\d{2})\s*(AM|PM|noon)?/i', $raw, $m)) {
        return [to24h($m[1], $m[2]), to24h($m[3], $m[4] ?: $m[2])];
    }
    return [null, null];
}

/**
 * Converts a 12-hour time and AM/PM marker to a "HH:MM:SS" string.
 *
 * @param string $time The time in "H:MM" format
 * @param string $ampm Period indicator: "AM", "PM", or "noon"
 * @return string Time formatted as "HH:MM:SS"
 */
function to24h(string $time, string $ampm): string {
    [$h, $min] = explode(':', $time);
    $h    = (int)$h;
    $ampm = strtolower(trim($ampm));
    if ($ampm === 'pm'   && $h !== 12) $h += 12;
    if ($ampm === 'am'   && $h === 12) $h  = 0;
    if ($ampm === 'noon')              $h  = 12;
    return sprintf('%02d:%02d:00', $h, (int)$min);
}

/**
 * Determines whether an event is Big Band or Combo based on its name.
 *
 * @param string $name The event name
 * @return string "Combo" if the name contains a combo keyword, "Big Band" otherwise
 */
function detectEventType(string $name): string {
    foreach (['combo', 'small group'] as $keyword) {
        if (stripos($name, $keyword) !== false) return 'Combo';
    }
    return 'Big Band';
}

/**
 * Returns true if the section string indicates an Adjunct (Combo) member.
 *
 * @param string $section The section value from the Excel cell
 * @return bool True if the section contains "adjunct"
 */
function isAdjunct(string $section): bool {
    return stripos($section, 'adjunct') !== false;
}

/**
 * Converts an Excel date value to a "Y-m-d" string.
 * Excel stores dates as serial numbers (days since Dec 30, 1899).
 *
 * @param mixed $val Raw cell value — a numeric serial or a date string
 * @return string|null A "Y-m-d" formatted date, or null if the value is empty or invalid
 */
function excelDateToString($val): ?string {
    if ($val === null || $val === '') return null;
    if (is_numeric($val)) {
        return date('Y-m-d', (int)(((float)$val - 25569) * 86400));
    }
    $ts = strtotime((string)$val);
    return $ts ? date('Y-m-d', $ts) : null;
}


if (isset($_GET['action']) && $_GET['action'] === 'download') {

    $events = $pdo->query("
        SELECT id, event_name, event_date, start_time, end_time, location, event_type
        FROM events
        ORDER BY event_date, start_time
    ")->fetchAll(PDO::FETCH_ASSOC);

    $members = $pdo->query("
        SELECT id, section, instrument, first_name, last_name, dietary_restrictions, is_adjunct
        FROM members
        ORDER BY section, instrument, last_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    $attRows = $pdo->query("SELECT member_id, event_id, status, time_note FROM attendance")->fetchAll(PDO::FETCH_ASSOC);
    $attMap  = [];
    foreach ($attRows as $r) {
        $attMap[$r['member_id']][$r['event_id']] = $r;
    }

    $rows = [];

    // Row 1 — disclaimer + event names
    $r1 = ['We track attendance for scheduling and reimbursements. Not a disciplinary measure.', '', '', 'Event'];
    foreach ($events as $ev) $r1[] = $ev['event_name'];
    $r1[]   = 'Dietary restrictions';
    $rows[] = $r1;

    // Row 2 — dates
    $r2 = ['', '', '', 'Date'];
    foreach ($events as $ev) $r2[] = $ev['event_date'];
    $r2[]   = '';
    $rows[] = $r2;

    // Row 3 — times
    $r3 = ['', '', '', 'Time'];
    foreach ($events as $ev) {
        $start  = $ev['start_time'] ? date('g:i', strtotime($ev['start_time'])) : '';
        $end    = $ev['end_time']   ? date('g:i A', strtotime($ev['end_time'])) : '';
        $r3[]   = ($start && $end) ? "$start-$end" : '';
    }
    $r3[]   = '';
    $rows[] = $r3;

    // Row 4 — locations
    $r4 = ['', '', '', 'Location'];
    foreach ($events as $ev) $r4[] = $ev['location'];
    $r4[]   = '';
    $rows[] = $r4;

    // Row 5 — column headers
    $r5 = ['Section', 'Instrument', 'First Name', 'Last Name'];
    foreach ($events as $ev) $r5[] = '';
    $r5[]   = '';
    $rows[] = $r5;

    $prevSection = null;
    foreach ($members as $m) {
        $section = $m['is_adjunct'] ? 'Adjunct (Combo)' : $m['section'];
        $showSec = ($section !== $prevSection) ? $section : '';
        $prevSection = $section;

        $row = [$showSec, $m['instrument'], $m['first_name'], $m['last_name']];

        foreach ($events as $ev) {
            $rec = $attMap[$m['id']][$ev['id']] ?? null;
            if (!$rec)                              $row[] = '';
            elseif ($rec['status'] === 'Attending') $row[] = 'Attending';
            elseif ($rec['status'] === 'Away')      $row[] = 'Away';
            elseif ($rec['status'] === 'Late')      $row[] = 'Late '  . ($rec['time_note'] ?? '');
            elseif ($rec['status'] === 'Leave')     $row[] = 'Leave ' . ($rec['time_note'] ?? '');
            else                                    $row[] = $rec['status'];
        }

        $row[]  = $m['dietary_restrictions'] ?? 'n/a';
        $rows[] = $row;
    }

    /*
     * ob_end_clean() discards any buffered output (whitespace, debug text)
     * before sending the binary Excel file. Without this, corrupted output
     * headers would cause the download to fail or produce a broken file.
     */
    ob_end_clean();

    $xlsx = SimpleXLSXGen::fromArray($rows);
    $xlsx->downloadAs('MEJ_Attendance_Export_' . date('Y-m-d') . '.xlsx');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_import') {
    header('Content-Type: application/json');

    $importId = (int)($_POST['import_id'] ?? 0);
    if (!$importId) {
        echo json_encode(['success' => false, 'error' => 'Invalid import ID']);
        exit();
    }

    $check = $pdo->prepare("SELECT id, filename FROM import_log WHERE id = ?");
    $check->execute([$importId]);
    $log = $check->fetch(PDO::FETCH_ASSOC);

    if (!$log) {
        echo json_encode(['success' => false, 'error' => 'Import not found']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        $pdo->prepare("
            DELETE a FROM attendance a
            INNER JOIN import_attendance_map m ON a.id = m.attendance_id
            WHERE m.import_id = ?
        ")->execute([$importId]);

        $pdo->prepare("
            DELETE mem FROM members mem
            INNER JOIN import_member_map mm ON mem.id = mm.member_id
            WHERE mm.import_id = ?
              AND NOT EXISTS (
                  SELECT 1 FROM attendance a WHERE a.member_id = mem.id
              )
        ")->execute([$importId]);

        $pdo->prepare("
            DELETE ev FROM events ev
            INNER JOIN import_event_map em ON ev.id = em.event_id
            WHERE em.import_id = ?
              AND NOT EXISTS (
                  SELECT 1 FROM attendance a WHERE a.event_id = ev.id
              )
        ")->execute([$importId]);
        $pdo->prepare("DELETE FROM import_log WHERE id = ?")->execute([$importId]);

        $pdo->commit();
        echo json_encode(['success' => true, 'filename' => $log['filename']]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}


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
            $sheetIndex = 0;
            foreach ($xlsx->sheetNames() as $idx => $name) {
                if (trim($name) === SHEET_NAME) { $sheetIndex = $idx; break; }
            }

            $rows     = $xlsx->rows($sheetIndex);
            $inserted = 0;
            $skipped  = 0;

            if (count($rows) <= ROW_MEMBERS_START) {
                $message = "File appears empty or doesn't match the expected MEJ format.";
                $msgType = 'error';
            } else {
                $events = [];
                for ($col = COL_EVENTS_START; $col <= COL_EVENTS_END; $col++) {
                    $name = trim((string)($rows[ROW_EVENT_NAMES][$col] ?? ''));
                    if (empty($name)) continue;
                    $date = excelDateToString($rows[ROW_DATES][$col] ?? '');
                    if (!$date) continue;
                    [$startTime, $endTime] = parseTimeRange(trim((string)($rows[ROW_TIMES][$col] ?? '')));
                    $events[$col] = [
                        'name'       => $name,
                        'date'       => $date,
                        'start_time' => $startTime,
                        'end_time'   => $endTime,
                        'location'   => trim((string)($rows[ROW_LOCATIONS][$col] ?? '')),
                        'type'       => detectEventType($name),
                    ];
                }

                if (empty($events)) {
                    $message = "No events found. Check the file format.";
                    $msgType = 'error';
                } else {
                    $pdo->beginTransaction();
                    try {
                        $pdo->prepare("
                            INSERT INTO import_log (filename, imported_by, rows_inserted, rows_skipped)
                            VALUES (?, ?, 0, 0)
                        ")->execute([$origName, $_SESSION['user_id']]);
                        $importId = (int)$pdo->lastInsertId();

                        $eventIdMap   = [];
                        $stmtEvent    = $pdo->prepare("INSERT INTO events (event_name, event_date, start_time, end_time, location, event_type) VALUES (:name,:date,:start,:end,:loc,:type) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)");
                        $stmtEventMap = $pdo->prepare("INSERT IGNORE INTO import_event_map (import_id, event_id) VALUES (?,?)");

                        foreach ($events as $col => $ev) {
                            $stmtEvent->execute([':name'=>$ev['name'],':date'=>$ev['date'],':start'=>$ev['start_time'],':end'=>$ev['end_time'],':loc'=>$ev['location'],':type'=>$ev['type']]);
                            $eid              = (int)$pdo->lastInsertId();
                            $eventIdMap[$col] = $eid;
                            $stmtEventMap->execute([$importId, $eid]);
                        }

                        $stmtMember    = $pdo->prepare("INSERT IGNORE INTO members (first_name,last_name,section,instrument,dietary_restrictions,is_adjunct) VALUES (:fn,:ln,:sec,:ins,:diet,:adj)");
                        $stmtGetMember = $pdo->prepare("SELECT id FROM members WHERE first_name=:fn AND last_name=:ln");
                        $stmtMemberMap = $pdo->prepare("INSERT IGNORE INTO import_member_map (import_id, member_id) VALUES (?,?)");
                        $stmtAttend    = $pdo->prepare("INSERT INTO attendance (member_id,event_id,status,time_note) VALUES (:mid,:eid,:status,:note) ON DUPLICATE KEY UPDATE id=id");
                        $stmtAttMap    = $pdo->prepare("INSERT IGNORE INTO import_attendance_map (import_id, attendance_id) VALUES (?,?)");
                        $stmtGetAtt    = $pdo->prepare("SELECT id FROM attendance WHERE member_id=? AND event_id=?");

                        $currentSection = '';

                        for ($r = ROW_MEMBERS_START; $r < count($rows); $r++) {
                            $row        = $rows[$r];
                            $section    = trim((string)($row[COL_SECTION]    ?? ''));
                            $instrument = trim((string)($row[COL_INSTRUMENT]  ?? ''));
                            $firstName  = trim((string)($row[COL_FIRST_NAME]  ?? ''));
                            $lastName   = trim((string)($row[COL_LAST_NAME]   ?? ''));
                            $dietary    = trim((string)($row[COL_DIETARY]     ?? ''));

                            if (empty($firstName) && empty($lastName)) {
                                if (!empty($section)) $currentSection = $section;
                                continue;
                            }
                            $adjunct = isAdjunct($currentSection) ? 1 : 0;

                            $stmtMember->execute([
                                ':fn'   => $firstName,
                                ':ln'   => $lastName,
                                ':sec'  => $adjunct ? 'Adjunct' : $currentSection,
                                ':ins'  => $instrument,
                                ':diet' => $dietary ?: 'n/a',
                                ':adj'  => $adjunct,
                            ]);

                            $stmtGetMember->execute([':fn' => $firstName, ':ln' => $lastName]);
                            $memberId = (int)$stmtGetMember->fetchColumn();
                            if (!$memberId) continue;

                            $stmtMemberMap->execute([$importId, $memberId]);
                            foreach ($eventIdMap as $col => $eventId) {
                                $rawStatus = trim((string)($row[$col] ?? ''));
                                if (empty($rawStatus)) { $skipped++; continue; }

                                [$status, $timeNote] = parseAttendance($rawStatus);
                                $stmtAttend->execute([':mid'=>$memberId,':eid'=>$eventId,':status'=>$status,':note'=>$timeNote]);

                                if ($stmtAttend->rowCount() > 0) {
                                    $stmtGetAtt->execute([$memberId, $eventId]);
                                    $attId = (int)$stmtGetAtt->fetchColumn();
                                    if ($attId) $stmtAttMap->execute([$importId, $attId]);
                                    $inserted++;
                                } else {
                                    $skipped++; // duplicate 
                                }
                            }
                        }

                        $pdo->prepare("UPDATE import_log SET rows_inserted=?, rows_skipped=? WHERE id=?")
                            ->execute([$inserted, $skipped, $importId]);

                        $pdo->commit();
                        $message = "Import complete! $inserted records inserted, $skipped skipped.";
                        $msgType = 'success';

                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $message = "Import failed: " . htmlspecialchars($e->getMessage());
                        $msgType = 'error';
                    }
                }
            }
        }
    }
}

$importHistory = $pdo->query("
    SELECT il.id, il.filename, il.imported_at, il.rows_inserted, il.rows_skipped, u.username
    FROM import_log il
    LEFT JOIN users u ON u.id = il.imported_by
    ORDER BY il.imported_at DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Attendance | MEJ Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<a href="admin_dashboard.php" class="back-link" style="display:inline-block; margin: 20px 20px 0;">
    ← Back to Admin Dashboard
</a>

<div class="import-box">
    <h2> Import Attendance from Excel</h2>

    <?php if ($message): ?>
        <div class="alert <?php echo $msgType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label style="color:#aaa; font-size:0.85rem; display:block; margin-bottom:8px;">
            Upload MEJ Attendance Excel file (.xlsx)
        </label>
        <input type="file" name="excel_file" accept=".xlsx" required>
        <p class="note">
            Reads sheet: <strong style="color:#d4af37">Band Attendance 2026 Winter</strong><br>
            Events cols E–AO &nbsp;·&nbsp; Dietary col AP &nbsp;·&nbsp; Members start row 6
        </p>
        <div class="action-row">
            <button type="submit" class="btn-gold">Upload &amp; Import</button>
            <a href="?action=download" class="btn-outline"> Download Current Data as Excel</a>
        </div>
    </form>

    <?php if (!empty($importHistory)): ?>
    <h3 class="section-title">Import History</h3>
    <table class="history-table">
        <tr>
            <th>File</th>
            <th>By</th>
            <th>Date</th>
            <th style="text-align:center">Inserted</th>
            <th style="text-align:center">Skipped</th>
            <th></th>
        </tr>
        <?php foreach ($importHistory as $log): ?>
        <tr id="import-row-<?php echo $log['id']; ?>">
            <td><?php echo htmlspecialchars($log['filename']); ?></td>
            <td><?php echo htmlspecialchars($log['username'] ?? '—'); ?></td>
            <td><?php echo date('M j, Y g:i A', strtotime($log['imported_at'])); ?></td>
            <td style="text-align:center; color:#2ecc71"><?php echo (int)$log['rows_inserted']; ?></td>
            <td style="text-align:center; color:#888"><?php echo (int)$log['rows_skipped']; ?></td>
            <td>
                <button class="btn-danger" onclick="confirmDelete(
                    <?php echo $log['id']; ?>,
                    '<?php echo addslashes(htmlspecialchars($log['filename'])); ?>',
                    <?php echo (int)$log['rows_inserted']; ?>
                )">Delete</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>
<div class="warn-backdrop" id="warnBackdrop">
    <div class="warn-box">
        <h3> Delete Import?</h3>
        <p>You are about to permanently delete everything added by:</p>
        <p class="filename" id="warnFilename"></p>
        <p class="counts"   id="warnCounts"></p>
        <p>This removes all attendance records, members, and events
           <strong>uniquely</strong> created by this import.
           <strong>This cannot be undone.</strong></p>
        <div class="warn-actions">
            <button class="btn-cancel"         onclick="closeWarn()">Cancel</button>
            <button class="btn-confirm-delete" id="confirmDeleteBtn">Yes, Delete Everything</button>
        </div>
    </div>
</div>

<script src="js/import.js"></script>
</body>
</html>
