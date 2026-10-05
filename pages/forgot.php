<?php
$title = 'Şifremi unuttum';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (throttle('forgot', 3, 600)) {
        throttle_hit('forgot');
        $u = find_user('email', trim($_POST['email'] ?? ''));
        if ($u) {
            $tok = bin2hex(random_bytes(16));
            db_set('users', $u['id'], ['reset' => hash('sha256', $tok), 'reset_exp' => time() + 3600]);
            smtp_send($u['email'], cfg('site_name') . ' - Şifre sıfırlama', "Şifrenizi sıfırlamak için (1 saat geçerli):\n" . base_url() . url('reset', ['id' => $u['id'], 'token' => $tok]) . "\n");
        }
    }
    flash('E-posta kayıtlıysa sıfırlama bağlantısı gönderildi.'); redirect('login');
}
?>
<div class="card auth"><h2>Şifremi unuttum</h2><form class="f pad" method="post"><?= csrf_field() ?>
<label>E-posta</label><input type="email" name="email" required><br><br><button>Gönder</button></form></div>
