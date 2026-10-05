<?php
if (user()) redirect('home');
$title = 'Üye Ol';
if (!cfg('registration')) { echo '<div class="flash err">Kayıtlar kapalı.</div>'; return; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $un = trim($_POST['username'] ?? ''); $em = trim($_POST['email'] ?? ''); $pw = $_POST['password'] ?? '';
    $err = null;
    if (!empty($_POST['website'])) $err = 'Geçersiz istek.';
    elseif (!throttle('reg', 5, 3600)) $err = 'Çok fazla deneme.';
    elseif (!preg_match('/^[\w.\-]{3,20}$/u', $un)) $err = 'Kullanıcı adı 3-20 karakter (harf, rakam, . _ -).';
    elseif (!filter_var($em, FILTER_VALIDATE_EMAIL)) $err = 'Geçerli bir e-posta girin.';
    elseif (strlen($pw) < 6) $err = 'Şifre en az 6 karakter olmalı.';
    elseif ($pw !== ($_POST['password2'] ?? '')) $err = 'Şifreler eşleşmiyor.';
    elseif (find_user('username', $un) || find_user('email', $em)) $err = 'Kullanıcı adı veya e-posta kullanılıyor.';
    throttle_hit('reg');
    if ($err) flash($err, 'err');
    else {
        $ver = cfg('email_verify'); $tok = bin2hex(random_bytes(16));
        $id = db_insert('users', ['username' => $un, 'email' => $em, 'pass' => password_hash($pw, PASSWORD_DEFAULT), 'role' => 'user',
            'verified' => $ver ? 0 : 1, 'token' => $ver ? hash('sha256', $tok) : '', 'banned' => 0, 'created' => time(), 'last_seen' => time(), 'posts' => 0, 'about' => '']);
        if ($ver) {
            $ok = smtp_send($em, cfg('site_name') . ' - E-posta doğrulama', "Merhaba $un,\n\nHesabınızı doğrulamak için:\n" . base_url() . url('verify', ['id' => $id, 'token' => $tok]) . "\n");
            flash($ok ? 'Doğrulama e-postası gönderildi.' : 'Kayıt yapıldı ancak e-posta gönderilemedi; yönetici ile iletişime geçin.', $ok ? 'ok' : 'err');
            redirect('login');
        }
        login_user(db_get('users', $id)); flash('Hoş geldiniz!'); redirect('home');
    }
}
?>
<div class="card auth"><h2>Üye Ol</h2><form class="f pad" method="post"><?= csrf_field() ?>
<input class="hp" name="website" tabindex="-1" autocomplete="off">
<label>Kullanıcı adı</label><input type="text" name="username" required value="<?= e($_POST['username'] ?? '') ?>">
<label>E-posta</label><input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
<label>Şifre</label><input type="password" name="password" required minlength="6">
<label>Şifre (tekrar)</label><input type="password" name="password2" required><br><br>
<button>Kayıt ol</button> <a href="<?= url('login') ?>">Giriş yap</a></form></div>
