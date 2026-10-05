<?php
// Core: storage, session, security, mail
define('ROOT', dirname(__DIR__));
define('DATA', ROOT . '/data');
session_name('ffforum');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();

/* ---------- flat-file storage ---------- */
function db_path($name) { return DATA . '/' . preg_replace('/[^a-z_]/', '', $name) . '.json'; }
function db_load($name) {
    $f = db_path($name);
    if (!is_file($f)) return ['seq' => 0, 'items' => []];
    $d = json_decode((string)file_get_contents($f), true);
    return is_array($d) && isset($d['items']) ? $d : ['seq' => 0, 'items' => []];
}
// Run $fn(&$db) under an exclusive lock and persist the result atomically.
function db_update($name, callable $fn) {
    $lock = fopen(DATA . '/' . $name . '.lock', 'c');
    flock($lock, LOCK_EX);
    $db = db_load($name);
    $ret = $fn($db);
    $tmp = db_path($name) . '.tmp';
    file_put_contents($tmp, json_encode($db, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    rename($tmp, db_path($name));
    flock($lock, LOCK_UN);
    fclose($lock);
    return $ret;
}
function db_insert($name, array $row) {
    return db_update($name, function (&$db) use ($row) {
        $id = ++$db['seq'];
        $row['id'] = $id;
        $db['items'][$id] = $row;
        return $id;
    });
}
function db_set($name, $id, array $fields) {
    db_update($name, function (&$db) use ($id, $fields) {
        if (isset($db['items'][$id])) $db['items'][$id] = $fields + $db['items'][$id];
    });
}
function db_delete($name, array $ids) {
    db_update($name, function (&$db) use ($ids) { foreach ($ids as $i) unset($db['items'][$i]); });
}
function db_get($name, $id) { $d = db_load($name); return $d['items'][$id] ?? null; }
function db_all($name) { return db_load($name)['items']; }

/* ---------- config ---------- */
function default_config() {
    return [
        'site_name' => 'Flat Forum', 'site_desc' => 'Dosya tabanlı basit forum',
        'per_page' => 15, 'registration' => 1, 'email_verify' => 0, 'installed' => 0,
        'smtp_host' => '', 'smtp_port' => 587, 'smtp_secure' => 'tls', 'smtp_user' => '', 'smtp_pass' => '',
        'mail_from' => '', 'mail_from_name' => 'Flat Forum',
    ];
}
function cfg($k = null) {
    static $c = null;
    if ($c === null) {
        $f = DATA . '/config.json';
        $c = default_config();
        if (is_file($f)) $c = (json_decode((string)file_get_contents($f), true) ?: []) + $c;
    }
    return $k === null ? $c : ($c[$k] ?? null);
}
function cfg_save(array $new) {
    $c = $new + cfg();
    file_put_contents(DATA . '/config.json', json_encode($c, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    @chmod(DATA . '/config.json', 0600);
}

/* ---------- helpers ---------- */
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url($p = '', array $q = []) { return 'index.php?' . http_build_query(['p' => $p] + $q); }
function redirect($p, array $q = []) { header('Location: ' . url($p, $q)); exit; }
function flash($msg = null, $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'][] = [$type, $msg]; return; }
    $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f;
}
function csrf() { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_field() { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function csrf_check() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(csrf(), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400); exit('Geçersiz istek (CSRF).');
    }
}
function ago($t) {
    $d = time() - $t;
    if ($d < 60) return 'az önce';
    if ($d < 3600) return floor($d / 60) . ' dk önce';
    if ($d < 86400) return floor($d / 3600) . ' sa önce';
    if ($d < 2592000) return floor($d / 86400) . ' gün önce';
    return date('d.m.Y', $t);
}
function render_text($s) {
    $s = e($s);
    $s = preg_replace('~\[b\](.+?)\[/b\]~s', '<strong>$1</strong>', $s);
    $s = preg_replace('~\[i\](.+?)\[/i\]~s', '<em>$1</em>', $s);
    $s = preg_replace('~\[code\](.+?)\[/code\]~s', '<pre>$1</pre>', $s);
    $s = preg_replace('~\[quote\](.+?)\[/quote\]~s', '<blockquote>$1</blockquote>', $s);
    $s = preg_replace_callback('~(?<![">])\bhttps?://[^\s<]+~i', fn($m) => '<a href="' . $m[0] . '" rel="nofollow noopener" target="_blank">' . $m[0] . '</a>', $s);
    return nl2br($s);
}
function paginate($total, $page, $per) { return ['pages' => max(1, (int)ceil($total / $per)), 'page' => max(1, $page)]; }

/* ---------- auth ---------- */
function user() {
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $u = db_get('users', $_SESSION['uid']);
            if (!$u || !empty($u['banned'])) { $u = null; unset($_SESSION['uid']); }
        }
    }
    return $u;
}
function is_admin() { return (user()['role'] ?? '') === 'admin'; }
function is_mod() { return in_array(user()['role'] ?? '', ['admin', 'mod'], true); }
function require_login() { if (!user()) { flash('Önce giriş yapmalısınız.', 'err'); redirect('login'); } }
function require_admin() { if (!is_admin()) { http_response_code(403); exit('Yetkiniz yok.'); } }
function find_user($field, $val) {
    foreach (db_all('users') as $u) if (strcasecmp((string)($u[$field] ?? ''), (string)$val) === 0) return $u;
    return null;
}
function login_user($u) {
    session_regenerate_id(true);
    $_SESSION['uid'] = $u['id'];
    db_set('users', $u['id'], ['last_seen' => time()]);
}
function throttle($key, $max = 5, $window = 600) {
    $now = time();
    $a = array_filter($_SESSION['thr'][$key] ?? [], fn($t) => $t > $now - $window);
    $_SESSION['thr'][$key] = $a;
    return count($a) < $max;
}
function throttle_hit($key) { $_SESSION['thr'][$key][] = time(); }
function avatar($name) {
    $c = 'hsl(' . (crc32(strtolower($name)) % 360) . ',55%,50%)';
    return '<span class="avatar" style="background:' . $c . '">' . e(mb_strtoupper(mb_substr($name, 0, 1))) . '</span>';
}

/* ---------- SMTP mail ---------- */
function smtp_send($to, $subject, $body, &$err = null) {
    $c = cfg();
    if ($c['smtp_host'] === '') {
        $from = $c['mail_from'] ?: 'noreply@localhost';
        $h = "From: =?UTF-8?B?" . base64_encode($c['mail_from_name']) . "?= <$from>\r\nContent-Type: text/plain; charset=UTF-8\r\nMIME-Version: 1.0";
        $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $h);
        if (!$ok) $err = 'mail() başarısız';
        return $ok;
    }
    $host = ($c['smtp_secure'] === 'ssl' ? 'ssl://' : '') . $c['smtp_host'];
    $fp = @stream_socket_client("$host:" . (int)$c['smtp_port'], $en, $es, 15);
    if (!$fp) { $err = "Bağlantı hatası: $es"; return false; }
    stream_set_timeout($fp, 15);
    $read = function () use ($fp) {
        $r = '';
        while (($l = fgets($fp, 515)) !== false) { $r .= $l; if (strlen($l) < 4 || $l[3] === ' ') break; }
        return $r;
    };
    $cmd = function ($s, $ok) use ($fp, $read, &$err) {
        if ($s !== null) fwrite($fp, $s . "\r\n");
        $r = $read();
        if (strpos($r, (string)$ok) !== 0) { $err = trim($r); return false; }
        return true;
    };
    $name = preg_replace('/[^a-z0-9.\-]/i', '', $_SERVER['SERVER_NAME'] ?? 'localhost') ?: 'localhost';
    $from = $c['mail_from'] ?: $c['smtp_user'];
    $clean = fn($s) => str_replace(["\r", "\n", '>', '<'], '', $s);
    $steps = $cmd(null, 220) && $cmd("EHLO $name", 250);
    if ($steps && $c['smtp_secure'] === 'tls') {
        $steps = $cmd('STARTTLS', 220);
        if ($steps && !stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { $err = 'TLS başarısız'; $steps = false; }
        $steps = $steps && $cmd("EHLO $name", 250);
    }
    if ($steps && $c['smtp_user'] !== '') {
        $steps = $cmd('AUTH LOGIN', 334) && $cmd(base64_encode($c['smtp_user']), 334) && $cmd(base64_encode($c['smtp_pass']), 235);
    }
    $steps = $steps && $cmd('MAIL FROM:<' . $clean($from) . '>', 250) && $cmd('RCPT TO:<' . $clean($to) . '>', 250) && $cmd('DATA', 354);
    if ($steps) {
        $msg = 'From: =?UTF-8?B?' . base64_encode($c['mail_from_name']) . '?= <' . $clean($from) . ">\r\n"
            . 'To: <' . $clean($to) . ">\r\nSubject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n"
            . "Date: " . date('r') . "\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($body));
        fwrite($fp, $msg . "\r\n.\r\n");
        $steps = $cmd(null, 250);
    }
    @fwrite($fp, "QUIT\r\n"); fclose($fp);
    return $steps;
}
function base_url() {
    $s = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    return $s . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
}
