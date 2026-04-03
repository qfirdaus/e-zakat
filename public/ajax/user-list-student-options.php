<?php
// ajax/user-list-student-options.php
// Search active students from v210 for Add Pelajar modal (Select2 remote source)
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

while (ob_get_level() > 0) {
    @ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../includes/init.php';
    require_login();
    require_once __DIR__ . '/_helpers.php';
    require_once __DIR__ . '/../includes/functions-db.php';
    require_once __DIR__ . '/../classes/Database.php';

    $pdo = Database::getInstance('mysql')->getConnection();
    ensureAjaxGroupManagePermission($pdo);

    if (!isValidCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
        http_response_code(400);
        echo json_encode([
            'error' => true,
            'message' => (string)__('userGroup_csrf_invalid'),
            'results' => [],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!is_student_mode_enabled()) {
        http_response_code(403);
        echo json_encode([
            'error' => true,
            'message' => (string)__('studentSearch_mode_disabled'),
            'results' => [],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!checkRateLimit('student_options', 40, 60)) {
        http_response_code(429);
        echo json_encode([
            'error' => true,
            'message' => 'Terlalu banyak permintaan. Sila cuba lagi selepas beberapa saat.',
            'results' => [],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $q = trim((string)($_POST['q'] ?? ''));
    $page = max(1, (int)($_POST['page'] ?? 1));
    $perPage = 20;

    if (mb_strlen($q) < 2) {
        echo json_encode([
            'error' => false,
            'results' => [],
            'pagination' => ['more' => false],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $existingIdentifiers = [];
    try {
        $existingSql = "SELECT DISTINCT f_stafID FROM tbl_m_user WHERE TRIM(COALESCE(f_categoryUser, '')) = 'PELAJAR' AND f_stafID IS NOT NULL AND f_stafID <> ''";
        $existingStmt = $pdo->query($existingSql);
        $existingRows = $existingStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        foreach ($existingRows as $identifier) {
            $identifier = trim((string)$identifier);
            if ($identifier !== '') {
                $existingIdentifiers[$identifier] = true;
            }
        }
    } catch (Throwable $e) {
        error_log('[user-list-student-options] Error loading existing student identifiers: ' . $e->getMessage());
    }

    $pdoSybase = Database::pdoSybaseStudent();
    if (!$pdoSybase) {
        throw new RuntimeException('Student database connection is not available.');
    }

    $where = ["upper(convert(varchar(20), statuskategori)) = 'AKTIF'"];
    $params = [];

    $where[] = "(
        upper(convert(varchar(50), matrik)) LIKE :q
        OR upper(convert(varchar(255), nama)) LIKE :q
        OR upper(convert(varchar(255), fakulti)) LIKE :q
        OR upper(convert(varchar(255), program)) LIKE :q
    )";
    $params[':q'] = '%' . strtoupper($q) . '%';

    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $countSql = "SELECT COUNT(*) AS total FROM v210 {$whereSql}";
    $countStmt = $pdoSybase->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total = (int)($countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $limit = $perPage * $page;
    $sql = "
        SELECT TOP {$limit}
            matrik,
            nama,
            email,
            nokp,
            hpno,
            telno,
            telno_terkini,
            notel_terkini,
            kdprogram,
            program,
            kdfakulti,
            fakulti,
            kdtahap,
            tahap_pengajian,
            kadet,
            kategori_kadet,
            status,
            statusketerangan,
            statuskategori
        FROM v210
        {$whereSql}
        ORDER BY nama ASC
    ";

    $stmt = $pdoSybase->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $allResults = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $startIndex = ($page - 1) * $perPage;
    $results = array_slice($allResults, $startIndex, $perPage);

    $formattedResults = [];
    foreach ($results as $row) {
        $matrik = trim((string)($row['matrik'] ?? ''));
        $nama = trim((string)($row['nama'] ?? ''));
        $fakulti = trim((string)($row['fakulti'] ?? ''));
        $program = trim((string)($row['program'] ?? ''));
        $tahap = trim((string)($row['tahap_pengajian'] ?? ''));
        $statuskategori = trim((string)($row['statuskategori'] ?? ''));

        if ($matrik === '') {
            continue;
        }

        $display = $nama !== '' ? $nama : $matrik;
        $display .= ' (' . $matrik . ')';

        $isDisabled = isset($existingIdentifiers[$matrik]);
        if ($isDisabled) {
            $display .= ' [' . (string)__('userList_student_already_exists') . ']';
        }

        $formattedResults[] = [
            'id' => $matrik,
            'text' => $display,
            'matrik' => $matrik,
            'nama' => $nama,
            'email' => trim((string)($row['email'] ?? '')),
            'nokp' => trim((string)($row['nokp'] ?? '')),
            'hpno' => trim((string)($row['hpno'] ?? '')),
            'telno' => trim((string)($row['telno'] ?? '')),
            'telno_terkini' => trim((string)($row['telno_terkini'] ?? '')),
            'notel_terkini' => trim((string)($row['notel_terkini'] ?? '')),
            'kdprogram' => trim((string)($row['kdprogram'] ?? '')),
            'program' => $program,
            'kdfakulti' => trim((string)($row['kdfakulti'] ?? '')),
            'fakulti' => $fakulti,
            'kdtahap' => trim((string)($row['kdtahap'] ?? '')),
            'tahap_pengajian' => $tahap,
            'kategori_kadet' => trim((string)($row['kategori_kadet'] ?? '')),
            'kadet' => trim((string)($row['kadet'] ?? '')),
            'status' => trim((string)($row['status'] ?? '')),
            'statusketerangan' => trim((string)($row['statusketerangan'] ?? '')),
            'statuskategori' => $statuskategori,
            'disabled' => $isDisabled,
        ];
    }

    $hasMore = ($startIndex + count($results)) < $total && count($allResults) >= $limit;

    echo json_encode([
        'error' => false,
        'results' => $formattedResults,
        'pagination' => ['more' => $hasMore],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[user-list-student-options] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => (string)__('studentSearch_system_error'),
        'results' => [],
    ], JSON_UNESCAPED_UNICODE);
}
