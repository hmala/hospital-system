<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=hospital_pho', 'root', '');
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$counts = [];
foreach ($tables as $table) {
    try {
        $count = $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
        $counts[$table] = $count;
    } catch (Exception $e) {
        $counts[$table] = 'ERR';
    }
}
arsort($counts);
foreach ($counts as $t => $c) {
    if ($c > 0) {
        echo sprintf("%-35s %s\n", $t, $c);
    }
}
