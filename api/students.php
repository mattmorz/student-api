<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

/*
 * For browser clients hosted on another origin, configure an explicit
 * trusted origin. Do not use "*" with authenticated requests.
 * Example:
 * $allowedOrigin = 'http://localhost:5173';
 * if (($_SERVER['HTTP_ORIGIN'] ?? '') === $allowedOrigin) {
 *     header('Access-Control-Allow-Origin: ' . $allowedOrigin);
 *     header('Vary: Origin');
 * }
 */

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/database.php';

function respond(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function readJsonBody(): array
{
    $raw = file_get_contents('php://input');
    try {
        $data = json_decode($raw ?: '', false, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        respond(400, ['error' => 'Request body must be valid JSON']);
    }

    if (!is_object($data)) {
        respond(400, ['error' => 'Request body must be a JSON object']);
    }

    return (array) $data;
}

function validateStudent(array $data): array
{
    $first = trim((string) ($data['first_name'] ?? ''));
    $middle = trim((string) ($data['middle_initial'] ?? ''));
    $last = trim((string) ($data['last_name'] ?? ''));
    $program = $data['program'] ?? '';
    $year = filter_var($data['year_level'] ?? null, FILTER_VALIDATE_INT);

    $errors = [];

    if ($first === '' || mb_strlen($first) > 80) {
        $errors['first_name'] = 'Required; maximum 80 characters.';
    }

    if ($middle !== '' && preg_match('/^.$/us', $middle) !== 1) {
        $errors['middle_initial'] = 'Use one character or an empty string.';
    }

    if ($last === '' || mb_strlen($last) > 80) {
        $errors['last_name'] = 'Required; maximum 80 characters.';
    }

    if (!in_array($program, ['BSIT', 'BSIS', 'BSCS'], true)) {
        $errors['program'] = 'Must be BSIT, BSIS, or BSCS.';
    }

    if ($year === false || $year < 1 || $year > 4) {
        $errors['year_level'] = 'Must be an integer from 1 to 4.';
    }

    if ($errors) {
        respond(422, ['error' => 'Validation failed', 'details' => $errors]);
    }

    return [
        'first_name' => $first,
        'middle_initial' => $middle === '' ? null : $middle,
        'last_name' => $last,
        'program' => $program,
        'year_level' => $year,
    ];
}

$method = $_SERVER['REQUEST_METHOD'];
$idRaw = $_GET['id'] ?? null;
$id = null;
$program = $_GET['program'] ?? null;

if ($idRaw !== null) {
    $id = filter_var($idRaw, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    if ($id === false) {
        respond(400, ['error' => 'id must be a positive integer']);
    }
}

try {
    switch ($method) {
        case 'GET':
            if ($program !== null) {
                if (!in_array($program, ['BSIT', 'BSIS', 'BSCS'], true)) {
                    respond(400, ['error' => 'program must be BSIT, BSIS, or BSCS']);
                }

                $stmt = $pdo->prepare(
                    'SELECT id, first_name, middle_initial, last_name, program, year_level
                     FROM students WHERE program = :program ORDER BY id ASC'
                );
                $stmt->execute(['program' => $program]);
                respond(200, $stmt->fetchAll());
            }
            if ($id !== null) {
                $stmt = $pdo->prepare(
                    'SELECT id, first_name, middle_initial, last_name, program, year_level
                     FROM students WHERE id = :id'
                );
                $stmt->execute(['id' => $id]);
                $student = $stmt->fetch();

                if (!$student) {
                    respond(404, ['error' => 'Student not found']);
                }

                respond(200, [$student]);
            }

            $stmt = $pdo->query(
                'SELECT id, first_name, middle_initial, last_name, program, year_level
                 FROM students ORDER BY id ASC LIMIT 25'
            );
            respond(200, $stmt->fetchAll());

        case 'POST':
            $student = validateStudent(readJsonBody());
            $stmt = $pdo->prepare(
                'INSERT INTO students
                 (first_name, middle_initial, last_name, program, year_level)
                 VALUES (:first_name, :middle_initial, :last_name, :program, :year_level)'
            );
            $stmt->execute($student);
            $student['id'] = (int) $pdo->lastInsertId();
            respond(201, ['message' => 'Student created', 'data' => $student]);

        case 'PUT':
            if ($id === null) {
                respond(400, ['error' => 'Provide id, e.g. ?id=3']);
            }

            $student = validateStudent(readJsonBody());

            $check = $pdo->prepare('SELECT id FROM students WHERE id = :id');
            $check->execute(['id' => $id]);
            if (!$check->fetch()) {
                respond(404, ['error' => 'Student not found']);
            }

            $student['id'] = $id;
            $stmt = $pdo->prepare(
                'UPDATE students SET
                    first_name = :first_name,
                    middle_initial = :middle_initial,
                    last_name = :last_name,
                    program = :program,
                    year_level = :year_level
                 WHERE id = :id'
            );
            $stmt->execute($student);
            respond(200, ['message' => 'Student updated', 'data' => $student]);

        case 'DELETE':
            if ($id === null) {
                respond(400, ['error' => 'Provide id, e.g. ?id=3']);
            }

            $stmt = $pdo->prepare('DELETE FROM students WHERE id = :id');
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() === 0) {
                respond(404, ['error' => 'Student not found']);
            }

            respond(200, ['message' => 'Student deleted', 'id' => $id]);

        default:
            header('Allow: GET, POST, PUT, DELETE, OPTIONS');
            respond(405, ['error' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    error_log('[student-api] Database operation failed: ' . $e->getMessage());
    respond(500, ['error' => 'A database error occurred']);
}
