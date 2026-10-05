<?php
if (user()) redirect('home');
$title = 'Giriş';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!throttle('login', 6, 600)) flash('Çok fazla başarısız deneme. Daha sonra tekrar deneyin.', 'err');
    else {
        $u = find_user('username', trim($_POST['username'] ?? '')) ?: find_user('email', trim($_POST['username'] ?? ''));
        if (!$u || !password_verify($_POST['password'] ?? '', $u['pass'])) { throttle_hit('login'); flash('Kullanıcı adı veya şifre hatalı.', 'err'); }
        elseif (!empty($u['banned'])) flash('Hesabınız engellenmiş.', 'err');
        elseif (!$u['verified']) flash('Önce e-postanızı doğrulayın.', 'err');
        else { login_user($u); flash('Hoş geldiniz, ' . $u['username'] . '!'); redirect('home'); }
    }
}
?>
<div class="card auth"><h2>Giriş Yap</h2><form class="f pad" method="post"><?= csrf_field() ?>
<label>Kullanıcı adı veya e-posta</label><input type="text" name="username" required>
<label>Şifre</label><input type="password" name="password" required><br><br>
<button>Giriş</button> <a href="<?= url('register') ?>">Üye ol</a> · <a href="<?= url('forgot') ?>">Şifremi unuttum</a></form></div>
