<?php
if (cfg('installed')) redirect('home');
$title = 'Kurulum';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['site_name'] ?? ''); $un = trim($_POST['username'] ?? '');
    $em = trim($_POST['email'] ?? ''); $pw = $_POST['password'] ?? '';
    if ($name === '' || !preg_match('/^[\w.\-]{3,20}$/u', $un) || !filter_var($em, FILTER_VALIDATE_EMAIL) || strlen($pw) < 6) {
        flash('Tüm alanları doğru doldurun (kullanıcı adı 3-20 karakter, şifre en az 6).', 'err');
    } else {
        $uid = db_insert('users', ['username' => $un, 'email' => $em, 'pass' => password_hash($pw, PASSWORD_DEFAULT),
            'role' => 'admin', 'verified' => 1, 'banned' => 0, 'created' => time(), 'last_seen' => time(), 'posts' => 0, 'about' => '']);
        $cat = db_insert('boards', ['title' => 'Genel', 'desc' => '', 'parent' => 0, 'sort' => 1]);
        db_insert('boards', ['title' => 'Genel Sohbet', 'desc' => 'Her konuda sohbet edin', 'parent' => $cat, 'sort' => 1]);
        db_insert('boards', ['title' => 'Duyurular', 'desc' => 'Site duyuruları', 'parent' => $cat, 'sort' => 2]);
        cfg_save(['site_name' => $name, 'mail_from_name' => $name, 'installed' => 1]);
        login_user(db_get('users', $uid));
        flash('Kurulum tamamlandı. Yönetici hesabınız oluşturuldu.');
        redirect('admin');
    }
}
?>
<div class="card auth"><h2>Forum Kurulumu</h2><form class="f pad" method="post"><?= csrf_field() ?>
<label>Site adı</label><input type="text" name="site_name" value="Flat Forum" required>
<label>Yönetici kullanıcı adı</label><input type="text" name="username" required>
<label>E-posta</label><input type="email" name="email" required>
<label>Şifre</label><input type="password" name="password" required minlength="6"><br><br>
<button>Kur</button></form></div>
