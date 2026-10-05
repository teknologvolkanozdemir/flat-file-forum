<?php
$p = db_get('users', (int)($_GET['id'] ?? 0));
if (!$p) { http_response_code(404); echo 'Kullanıcı bulunamadı.'; return; }
$title = $p['username']; $me = user(); $own = $me && $me['id'] == $p['id'];
if ($own && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $set = ['about' => mb_substr(trim($_POST['about'] ?? ''), 0, 500)];
    $em = trim($_POST['email'] ?? '');
    if ($em !== $p['email']) {
        if (!filter_var($em, FILTER_VALIDATE_EMAIL) || find_user('email', $em)) { flash('E-posta geçersiz veya kullanılıyor.', 'err'); redirect('profile', ['id' => $p['id']]); }
        $set['email'] = $em;
    }
    if (($_POST['password'] ?? '') !== '') {
        if (!password_verify($_POST['current'] ?? '', $p['pass'])) { flash('Mevcut şifre hatalı.', 'err'); redirect('profile', ['id' => $p['id']]); }
        if (strlen($_POST['password']) < 6) { flash('Şifre en az 6 karakter olmalı.', 'err'); redirect('profile', ['id' => $p['id']]); }
        $set['pass'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }
    db_set('users', $p['id'], $set); flash('Profil güncellendi.'); redirect('profile', ['id' => $p['id']]);
}
$tc = count(array_filter(db_all('topics'), fn($t) => $t['user_id'] == $p['id']));
?>
<div class="card"><div class="pad" style="display:flex;gap:18px;align-items:center"><?= avatar($p['username']) ?>
<div><h2 style="margin:0;padding:0;background:none;border:0"><?= e($p['username']) ?> <?php if ($p['role'] !== 'user'): ?><span class="badge <?= $p['role'] ?>"><?= $p['role'] === 'admin' ? 'Yönetici' : 'Moderatör' ?></span><?php endif; ?></h2>
<div class="muted">Üyelik: <?= date('d.m.Y', $p['created']) ?> · <?= (int)$p['posts'] ?> ileti · <?= $tc ?> konu</div>
<p><?= nl2br(e($p['about'])) ?></p></div></div></div>
<?php if ($own): ?><div class="card"><h3>Profili düzenle</h3><form class="f pad" method="post"><?= csrf_field() ?>
<label>E-posta</label><input type="email" name="email" value="<?= e($p['email']) ?>">
<label>Hakkımda</label><textarea name="about" maxlength="500"><?= e($p['about']) ?></textarea>
<label>Mevcut şifre (değiştirmek için)</label><input type="password" name="current">
<label>Yeni şifre</label><input type="password" name="password"><br><br><button>Kaydet</button></form></div><?php endif; ?>
