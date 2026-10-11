<?php
$hosts = ['localhost', '127.0.0.1', '::1'];
$ports = [3306, 3307];
foreach ($hosts as $h) {
    foreach ($ports as $p) {
        try {
            $pdo = new PDO("mysql:host=$h;port=$p", 'root', '');
            $dbs = $pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
            echo "SUCCESS: $h:$p -> " . implode(', ', $dbs) . PHP_EOL;
        } catch (Exception $e) {
            echo "FAIL: $h:$p -> " . $e->getMessage() . PHP_EOL;
        }
    }
}
