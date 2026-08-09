<?php
$v = '?v=' . time();
$dirs = [dirname(__DIR__).'/admin', dirname(__DIR__)];
foreach ($dirs as $dir) {
    foreach (glob($dir.'/*.php') as $f) {
        $c = file_get_contents($f);
        $new = preg_replace('/style\.css(\?v=\d+)?/', 'style.css'.$v, $c);
        if ($new !== $c) { file_put_contents($f, $new); echo "OK: ".basename($f)."<br>"; }
    }
}
echo "<br>Done! <a href='../admin/dashboard.php'>→ Dashboard</a>";
?>
