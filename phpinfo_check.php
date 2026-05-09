<?php
echo "<style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#e5e7eb;}h2{color:#E8A020;}span.ok{color:#10b981;}span.fail{color:#ef4444;}</style>";

echo "<h2>PHP Version</h2>";
echo phpversion() . "<br><br>";

echo "<h2>Available PDO Drivers</h2>";
foreach(PDO::getAvailableDrivers() as $d) {
    echo "<span class='ok'>✓ $d</span><br>";
}

echo "<h2>Loaded Extensions</h2>";
$exts = get_loaded_extensions();
sort($exts);
foreach($exts as $e) {
    $highlight = in_array($e, ['pdo','pdo_mysql','pdo_sqlsrv','sqlsrv','pdo_pgsql','mysqli']);
    echo ($highlight ? "<span class='ok'>✓ <b>$e</b></span>" : $e) . "<br>";
}
