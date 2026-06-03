<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=dasheng;charset=utf8mb4', 'root', 'cxdxfx12');

// Fix all menu routes that have /admin/ prefix
$rows = $pdo->query("SELECT id, name, route FROM ds_menu WHERE route LIKE '/admin/%'")->fetchAll(PDO::FETCH_ASSOC);

echo "FIXING menu routes - removing /admin/ prefix:\n";
echo str_repeat("-", 80) . "\n";

foreach ($rows as $r) {
    $old = $r['route'];
    $new = substr($old, 6); // remove '/admin' prefix (6 chars)
    $pdo->prepare("UPDATE ds_menu SET route = ? WHERE id = ?")->execute([$new, $r['id']]);
    echo "ID {$r['id']}: {$r['name']}  {$old} -> {$new}\n";
}

echo "\nDone. Routes fixed.\n";
