<?php
/**
 * Safe migration runner
 * Usage (CLI): php run_migration.php --confirm
 * Usage (web): open /migrations/run_migration.php?confirm=1 (not recommended on public hosts)
 */
require_once __DIR__ . '/../config.php';

if (!isset($conn) || !$conn) {
    echo "Error: no DB connection (check config.php)\n";
    exit(1);
}

$migrationFile = __DIR__ . '/20260227_add_status_code.sql';
if (!file_exists($migrationFile)) {
    echo "Migration file not found: $migrationFile\n";
    exit(1);
}

$isCli = php_sapi_name() === 'cli';
$confirmed = false;
if ($isCli) {
    global $argv;
    $confirmed = in_array('--confirm', $argv) || in_array('-y', $argv);
} else {
    $confirmed = isset($_GET['confirm']) && ($_GET['confirm'] === '1' || $_GET['confirm'] === 'true');
}

function line($s = '') { if (php_sapi_name() === 'cli') echo $s."\n"; else echo nl2br(htmlspecialchars($s))."<br>\n"; }

line("Running migration: $migrationFile");
if (!$confirmed) {
    line('Preview only - no changes will be made.');
    line('To actually run the migration:');
    if ($isCli) line('  php run_migration.php --confirm');
    else line('  open this URL with ?confirm=1 (web)');
    exit(0);
}

$ts = date('Ymd_His');
$bakOrders = 'orders_backup_' . $ts;

// check engine for orders table
$dbNameEsc = $conn->real_escape_string($DB_NAME);
$engineQ = $conn->query("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA='" . $dbNameEsc . "' AND TABLE_NAME='orders'");
$engine = null;
if ($engineQ && ($er = $engineQ->fetch_assoc())) $engine = $er['ENGINE'] ?? null;
line('Current orders table engine: ' . ($engine ?? 'unknown'));
if (strtoupper((string)$engine) !== 'INNODB') {
    line('Attempting to convert `orders` to InnoDB (required for FK).');
    if (!$conn->query('ALTER TABLE orders ENGINE=InnoDB')) {
        line('Warning: failed to convert orders to InnoDB: ' . $conn->error);
        line('You may need to convert the table manually or remove FK step in migration.');
    } else {
        line('Converted `orders` to InnoDB.');
    }
}

// create backup table copy
line("Creating backup table: $bakOrders");
if (!$conn->query("CREATE TABLE `$bakOrders` LIKE orders")) {
    line('Error creating backup table: ' . $conn->error);
    exit(1);
}
if (!$conn->query("INSERT INTO `$bakOrders` SELECT * FROM orders")) {
    line('Error copying rows into backup table: ' . $conn->error);
    // attempt to drop backup to avoid partial backups
    $conn->query("DROP TABLE IF EXISTS `$bakOrders`");
    exit(1);
}
line('Backup created successfully.');

// backup status_codes table if exists
$exists = $conn->query("SHOW TABLES LIKE 'status_codes'");
if ($exists && $exists->num_rows) {
    $bakStatus = 'status_codes_backup_' . $ts;
    line("Backing up status_codes -> $bakStatus");
    if (!$conn->query("CREATE TABLE `$bakStatus` LIKE status_codes") || !$conn->query("INSERT INTO `$bakStatus` SELECT * FROM status_codes")) {
        line('Warning: failed to backup status_codes: ' . $conn->error);
    } else {
        line('status_codes backup created.');
    }
}

// read migration SQL
$sql = file_get_contents($migrationFile);
if ($sql === false) { line('Failed to read migration SQL file.'); exit(1); }

line('Executing migration SQL...');
// execute multiple statements
if ($conn->multi_query($sql)) {
    do {
        if ($res = $conn->store_result()) { $res->free(); }
        if ($conn->errno) {
            line('SQL error: ' . $conn->error);
            break;
        }
    } while ($conn->more_results() && $conn->next_result());
    if ($conn->errno) {
        line('Migration finished with errors: ' . $conn->error);
        line('You can inspect the DB and restore from backup table: ' . $bakOrders);
        exit(1);
    } else {
        line('Migration executed successfully.');
    }
} else {
    line('Failed to execute migration SQL: ' . $conn->error);
    exit(1);
}

line('Done. Please review tables and run application tests.');
exit(0);
