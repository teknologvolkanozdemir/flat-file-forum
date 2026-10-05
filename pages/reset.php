<?php
$title = 'Şifre sıfırla';
$u = db_get('users', (int)($_GET['id'] ?? 0)); $tok = (string)($_GET['token'] ?? '');
if (!$u || empty($u['reset']) || $u['reset_exp'] < time() || !hash_equals($u['reset'], hash('sha256', $tok))) {
    flash('Geçersiz veya süresi dolmuş bağlantı.', 'err'); redirect('login');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (strlen($_POST['password'] ?? '') < 6) flash('Şifre en az 6 karakter olmalı.', 'err');
    else { db_set('users', $u['id'], ['pass' => password_hash($_POST['password'], PASSWORD_DEFAULT), 'reset' => '', 'reset_exp' => 0]); flash('Şifre güncellendi.'); redirect('login'); }
}
?>
<div class="card auth"><h2>Yeni şifre</h2><form class="f pad" method="post"><?= csrf_field() ?>
<label>Yeni şifre</label><input type="password" name="password" minlength="6" required><br><br><button>Kaydet</button></form></div>
