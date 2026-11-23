<?php
/**
 * WAM 2.0 - Server MP3 Lister
 * Metti questo file DENTRO la cartella dei brani (es. /mp3/list.php)
 * Restituisce JSON: { "dir": "...", "files": [ {name, url, size, type}, ... ] }
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Cartella base = quella dove si trova questo script
$base = realpath(__DIR__);

// Sottocartella richiesta (opzionale, limitata alla base per sicurezza)
$req = isset($_GET['dir']) ? $_GET['dir'] : '';
$req = str_replace('\\', '/', $req);
$req = preg_replace('#\.\./#', '', $req);          // blocca path traversal
$target = $base;

if ($req !== '') {
    $candidate = realpath($base . '/' . ltrim($req, '/'));
    // accetta solo percorsi interni alla base
    if ($candidate && strpos($candidate, $base) === 0 && is_dir($candidate)) {
        $target = $candidate;
    }
}

$allowed = ['mp3','wav','ogg','m4a','aac','flac','opus','webm'];
$files = [];

if ($dh = @opendir($target)) {
    while (($f = readdir($dh)) !== false) {
        if ($f === '.' || $f === '..' || $f[0] === '.') continue;
        $full = $target . DIRECTORY_SEPARATOR . $f;

        if (is_dir($full)) {
            $files[] = ['name' => $f, 'type' => 'dir', 'url' => rawurlencode($f) . '/'];
            continue;
        }

        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) continue;

        $files[] = [
            'name' => $f,
            'type' => 'file',
            'url'  => str_replace('%2F', '/', rawurlencode($f)),
            'size' => filesize($full)
        ];
    }
    closedir($dh);
}

usort($files, function ($a, $b) {
    if ($a['type'] !== $b['type']) return $a['type'] === 'dir' ? -1 : 1;
    return strcasecmp($a['name'], $b['name']);
});

echo json_encode([
    'dir'   => basename($target),
    'count' => count($files),
    'files' => $files
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);