<?php
$host = 'localhost';
$dbname = 'internship_manager';
$username = 'root';
$password = '';

$dsnCandidates = [
    "mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4",
    "mysql:host=$host;port=3307;dbname=$dbname;charset=utf8mb4",
    "mysql:host=127.0.0.1;port=3306;dbname=$dbname;charset=utf8mb4",
    "mysql:host=127.0.0.1;port=3307;dbname=$dbname;charset=utf8mb4"
];

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

$lastError = null;
foreach ($dsnCandidates as $dsn) {
    try {
        $pdo = new PDO($dsn, $username, $password, $options);
        break;
    } catch (PDOException $e) {
        $lastError = $e;
    }
}

if (!isset($pdo)) {
    die("Database connection failed: " . $lastError->getMessage());
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Số tuần mặc định của một kỳ thực tập
const INTERNSHIP_WEEKS = 6;

/**
 * Chia một kỳ thực tập thành các tuần (mặc định 6 tuần), mỗi tuần 7 ngày tính từ ngày bắt đầu.
 * Trả về mảng: [['week'=>1,'start'=>'Y-m-d','end'=>'Y-m-d'], ...]
 */
function periodWeeks(string $startDate, int $weeks = INTERNSHIP_WEEKS): array
{
    $result = [];
    try {
        $start = new DateTimeImmutable($startDate);
    } catch (Exception $ex) {
        return $result;
    }
    for ($w = 1; $w <= $weeks; $w++) {
        $wStart = $start->modify('+' . (($w - 1) * 7) . ' days');
        $wEnd = $wStart->modify('+6 days');
        $result[] = [
            'week' => $w,
            'start' => $wStart->format('Y-m-d'),
            'end' => $wEnd->format('Y-m-d'),
        ];
    }
    return $result;
}
?>
