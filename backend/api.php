<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function respond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function request_body(): array
{
    $decoded = json_decode(file_get_contents('php://input'), true);
    if (!is_array($decoded)) {
        respond(400, ['error' => 'Format permintaan tidak valid.']);
    }
    return $decoded;
}

function require_method(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        header('Allow: ' . $method);
        respond(405, ['error' => 'Metode permintaan tidak diizinkan.']);
    }
}

function require_user(?string $role = null): array
{
    $user = $_SESSION['user'] ?? null;
    if (!is_array($user)) {
        respond(401, ['error' => 'Sesi berakhir. Silakan masuk kembali.']);
    }
    if ($role !== null && $user['role'] !== $role) {
        respond(403, ['error' => 'Anda tidak memiliki izin untuk tindakan ini.']);
    }
    return $user;
}

function require_csrf(): void
{
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';
    if (!is_string($expected) || $expected === '' || !hash_equals($expected, $provided)) {
        respond(403, ['error' => 'Token keamanan tidak valid. Muat ulang halaman dan coba lagi.']);
    }
}

function validate_student(array $input, bool $creating): array
{
    $username = trim((string) ($input['username'] ?? ''));
    $nim = trim((string) ($input['nim'] ?? ''));
    $name = trim((string) ($input['nama'] ?? ''));
    $program = trim((string) ($input['program_studi'] ?? ''));
    $semester = filter_var($input['semester'] ?? null, FILTER_VALIDATE_INT);
    $password = (string) ($input['password'] ?? '');

    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
        respond(422, ['error' => 'Username harus berisi 3–50 karakter: huruf, angka, titik, garis bawah, atau tanda hubung.']);
    }
    if (!preg_match('/^[A-Za-z0-9-]{4,20}$/', $nim)) {
        respond(422, ['error' => 'NIM harus berisi 4–20 karakter alfanumerik atau tanda hubung.']);
    }
    if ($name === '' || mb_strlen($name) > 100 || $program === '' || mb_strlen($program) > 100) {
        respond(422, ['error' => 'Nama dan program studi wajib diisi (maksimal 100 karakter).']);
    }
    if ($semester === false || $semester < 1 || $semester > 14) {
        respond(422, ['error' => 'Semester harus berupa angka antara 1 dan 14.']);
    }
    if (($creating && strlen($password) < 10) || (!$creating && $password !== '' && strlen($password) < 10)) {
        respond(422, ['error' => 'Password minimal 10 karakter.']);
    }

    return [
        'username' => $username,
        'nim' => $nim,
        'nama' => $name,
        'program_studi' => $program,
        'semester' => $semester,
        'password' => $password,
    ];
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remoteAddress, ['127.0.0.1', '::1', '::ffff:127.0.0.1'], true)) {
    respond(403, ['error' => 'Mode password plaintext hanya boleh digunakan dari komputer lokal.']);
}
if ($origin !== '') {
    if (!in_array($origin, allowed_origins(), true)) {
        respond(403, ['error' => 'Origin tidak diizinkan oleh konfigurasi API.']);
    }
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
}
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$sameSite = env_value('PORTAL_SESSION_SAMESITE', 'Lax');
if (!in_array($sameSite, ['Lax', 'Strict', 'None'], true)) {
    respond(500, ['error' => 'Konfigurasi SameSite sesi tidak valid.']);
}
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if ($sameSite === 'None' && !$isHttps) {
    respond(500, ['error' => 'Cookie SameSite=None memerlukan HTTPS.']);
}
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => $sameSite,
]);
session_start();

$action = (string) ($_GET['action'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($action === 'login') {
        require_method('POST');
        $input = request_body();
        $username = trim((string) ($input['username'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        if ($username === '' || $password === '' || strlen($username) > 50 || strlen($password) > 255) {
            respond(422, ['error' => 'Username dan password wajib diisi.']);
        }

        $statement = database_connection()->prepare(
            'SELECT id, username, password, role FROM users WHERE username = :username LIMIT 1'
        );
        $statement->execute(['username' => $username]);
        $record = $statement->fetch();
        if (!$record || !hash_equals($record['password'], $password)) {
            respond(401, ['error' => 'Username atau password salah.']);
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $record['id'],
            'username' => $record['username'],
            'role' => $record['role'],
        ];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        respond(200, ['user' => $_SESSION['user'], 'csrf_token' => $_SESSION['csrf_token']]);
    }

    if ($action === 'logout') {
        require_method('POST');
        require_user();
        require_csrf();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
        respond(200, ['message' => 'Anda telah keluar.']);
    }

    if ($action === 'dashboard') {
        require_method('GET');
        $user = require_user();
        $pdo = database_connection();
        if ($user['role'] === 'admin') {
            $students = $pdo->query(
                'SELECT m.id, u.username, m.nim, m.nama, m.program_studi, m.semester
                 FROM mahasiswa m JOIN users u ON u.id = m.user_id
                 ORDER BY m.nama'
            )->fetchAll();
            $programs = (int) $pdo->query('SELECT COUNT(DISTINCT program_studi) FROM mahasiswa')->fetchColumn();
            respond(200, [
                'user' => $user,
                'csrf_token' => $_SESSION['csrf_token'],
                'stats' => [
                    'mahasiswa' => count($students),
                    'program_studi' => $programs,
                    'semester_aktif' => (int) $pdo->query('SELECT COALESCE(MAX(semester), 0) FROM mahasiswa')->fetchColumn(),
                ],
                'students' => $students,
            ]);
        }

        $profileStatement = $pdo->prepare(
            'SELECT id, nim, nama, program_studi, semester FROM mahasiswa WHERE user_id = :user_id'
        );
        $profileStatement->execute(['user_id' => $user['id']]);
        $profile = $profileStatement->fetch();
        if (!$profile) {
            respond(404, ['error' => 'Profil mahasiswa tidak ditemukan. Hubungi administrator.']);
        }
        $scheduleStatement = $pdo->prepare(
            'SELECT mk.kode_mk, mk.nama_mk, mk.sks, j.hari, j.jam_mulai, j.jam_selesai, j.ruangan
             FROM jadwal_kuliah j JOIN mata_kuliah mk ON mk.id = j.mata_kuliah_id
             WHERE j.mahasiswa_id = :mahasiswa_id
             ORDER BY FIELD(j.hari, "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"), j.jam_mulai'
        );
        $scheduleStatement->execute(['mahasiswa_id' => $profile['id']]);
        $gradeStatement = $pdo->prepare(
            'SELECT mk.kode_mk, mk.nama_mk, n.nilai_angka, n.semester
             FROM nilai n JOIN mata_kuliah mk ON mk.id = n.mata_kuliah_id
             WHERE n.mahasiswa_id = :mahasiswa_id ORDER BY n.semester, mk.kode_mk'
        );
        $gradeStatement->execute(['mahasiswa_id' => $profile['id']]);
        respond(200, [
            'user' => $user,
            'csrf_token' => $_SESSION['csrf_token'],
            'profile' => $profile,
            'schedule' => $scheduleStatement->fetchAll(),
            'grades' => $gradeStatement->fetchAll(),
        ]);
    }

    if ($action === 'student_save') {
        require_method('POST');
        require_user('admin');
        require_csrf();
        $input = request_body();
        $id = null;
        if (array_key_exists('id', $input)) {
            $id = filter_var($input['id'], FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) {
                respond(422, ['error' => 'ID mahasiswa tidak valid.']);
            }
        }
        $creating = !$id;
        $student = validate_student($input, $creating);
        $pdo = database_connection();
        try {
            $pdo->beginTransaction();
            if ($creating) {
                $userStatement = $pdo->prepare(
                    'INSERT INTO users (username, password, role) VALUES (:username, :password, "mahasiswa")'
                );
                $userStatement->execute([
                    'username' => $student['username'],
                    'password' => $student['password'],
                ]);
                $studentStatement = $pdo->prepare(
                    'INSERT INTO mahasiswa (user_id, nim, nama, program_studi, semester)
                     VALUES (:user_id, :nim, :nama, :program_studi, :semester)'
                );
                $studentStatement->execute([
                    'user_id' => (int) $pdo->lastInsertId(),
                    'nim' => $student['nim'],
                    'nama' => $student['nama'],
                    'program_studi' => $student['program_studi'],
                    'semester' => $student['semester'],
                ]);
            } else {
                $existing = $pdo->prepare('SELECT user_id FROM mahasiswa WHERE id = :id');
                $existing->execute(['id' => $id]);
                $record = $existing->fetch();
                if (!$record) {
                    $pdo->rollBack();
                    respond(404, ['error' => 'Data mahasiswa tidak ditemukan.']);
                }
                $userSql = 'UPDATE users SET username = :username';
                $params = ['username' => $student['username'], 'user_id' => $record['user_id']];
                if ($student['password'] !== '') {
                    $userSql .= ', password = :password';
                    $params['password'] = $student['password'];
                }
                $userSql .= ' WHERE id = :user_id';
                $pdo->prepare($userSql)->execute($params);
                $pdo->prepare(
                    'UPDATE mahasiswa SET nim = :nim, nama = :nama, program_studi = :program_studi,
                     semester = :semester WHERE id = :id'
                )->execute([
                    'nim' => $student['nim'],
                    'nama' => $student['nama'],
                    'program_studi' => $student['program_studi'],
                    'semester' => $student['semester'],
                    'id' => $id,
                ]);
            }
            $pdo->commit();
            respond(200, ['message' => $creating ? 'Mahasiswa berhasil ditambahkan.' : 'Data mahasiswa berhasil diperbarui.']);
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception->getCode() === '23000') {
                respond(409, ['error' => 'Username atau NIM sudah digunakan.']);
            }
            throw $exception;
        }
    }

    if ($action === 'student_delete') {
        require_method('POST');
        require_user('admin');
        require_csrf();
        $input = request_body();
        $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            respond(422, ['error' => 'ID mahasiswa tidak valid.']);
        }
        $statement = database_connection()->prepare('DELETE FROM mahasiswa WHERE id = :id');
        $statement->execute(['id' => $id]);
        if ($statement->rowCount() === 0) {
            respond(404, ['error' => 'Data mahasiswa tidak ditemukan.']);
        }
        respond(200, ['message' => 'Data mahasiswa berhasil dihapus.']);
    }

    respond(404, ['error' => 'Endpoint tidak ditemukan.']);
} catch (Throwable $exception) {
    error_log((string) $exception);
    respond(500, ['error' => 'Terjadi kesalahan pada server. Periksa log PHP untuk detailnya.']);
}
