<?php
declare(strict_types=1);

// Run from the project root: php scripts/import_students.php
// This is a one-time CLI utility, not a public API endpoint.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

require_once __DIR__ . '/../config/database.php';

$file = __DIR__ . '/../students_25.json';
if (!is_file($file)) {
    exit("Import file not found: {$file}\n");
}

try {
    $rows = json_decode(
        file_get_contents($file),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
} catch (JsonException $e) {
    exit("The JSON file is invalid: {$e->getMessage()}\n");
}

if (!is_array($rows) || array_is_list($rows) === false) {
    // The expected top-level value is a JSON array.
    exit("The import file must contain a JSON array of student objects.\n");
}

$sql = 'INSERT INTO students
        (first_name, middle_initial, last_name, program, year_level)
        VALUES (:first_name, :middle_initial, :last_name, :program, :year_level)';
$stmt = $pdo->prepare($sql);

$pdo->beginTransaction();

try {
    foreach ($rows as $index => $row) {
        if (!is_array($row)) {
            throw new RuntimeException('Invalid record at array index ' . $index);
        }

        $first = trim((string) ($row['first_name'] ?? ''));
        $middle = trim((string) ($row['middle_initial'] ?? ''));
        $last = trim((string) ($row['last_name'] ?? ''));
        $program = $row['program'] ?? '';
        $year = filter_var($row['year_level'] ?? null, FILTER_VALIDATE_INT);

        if ($first === '' || mb_strlen($first) > 80 ||
            ($middle !== '' && preg_match('/^.$/us', $middle) !== 1) ||
            $last === '' || mb_strlen($last) > 80 ||
            !in_array($program, ['BSIT', 'BSIS', 'BSCS'], true) ||
            $year === false || $year < 1 || $year > 4) {
            throw new RuntimeException('Invalid student data at array index ' . $index);
        }

        $stmt->execute([
            'first_name' => $first,
            'middle_initial' => $middle === '' ? null : $middle,
            'last_name' => $last,
            'program' => $program,
            'year_level' => $year,
        ]);
    }

    $pdo->commit();
    echo 'Imported ' . count($rows) . " student records.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Import failed; transaction rolled back: ' . $e->getMessage() . "\n");
    exit(1);
}
