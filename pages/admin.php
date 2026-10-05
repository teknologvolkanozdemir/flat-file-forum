<?php
require_login(); require_admin();
$title = 'Yönetim';
$tab = $_GET['tab'] ?? 'settings';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $a = $_POST['act'] ?? ''; $me = user();
    if ($a === 'settings') {
        $new = ['site_name' => trim($_POST['site_name']) ?: 'Forum', 'site_desc' => trim($_POST['site_desc'] ?? ''),
            'per_page' => max(5, min(100, (int)$_POST['per_page'])), 'registration' => empty($_POST['registration']) ? 0 : 1,
            'email_verify' => empty($_POST['email_verify']) ? 0 : 1];
        cfg_save($new); flash('Ayarlar kaydedildi.');
    } elseif ($a === 'smtp') {
        $new = ['smtp_host' => trim($_POST['smtp_host']), 'smtp_port' => (int)$_POST['smtp_port'],
            'smtp_secure' => in_array($_POST['smtp_secure'], ['tls', 'ssl', 'none'], true) ? $_POST['smtp_secure'] : 'tls',
            'smtp_user' => trim($_POST['smtp_user']), 'mail_from' => trim($_POST['mail_from']), 'mail_from_name' => trim($_POST['mail_from_name'])];
        if (($_POST['smtp_pass'] ?? '') !== '') $new['smtp_pass'] = $_POST['smtp_pass'];
        cfg_save($new); flash('SMTP ayarları kaydedildi.');
        if (!empty($_POST['test'])) {
            $ok = smtp_send($me['email'], cfg('site_name') . ' test', "SMTP test e-postası başarılı.", $err);
            flash($ok ? 'Test e-postası gönderildi: ' . $me['email'] : 'Gönderilemedi: ' . $err, $ok ? 'ok' : 'err');
        }
    } elseif ($a === 'addcat' && trim($_POST['title'] ?? '') !== '') {
        db_insert('boards', ['title' => trim($_POST['title']), 'desc' => '', 'parent' => 0, 'sort' => (int)($_POST['sort'] ?? 0)]); flash('Kategori eklendi.');
    } elseif ($a === 'addboard' && trim($_POST['title'] ?? '') !== '' && ($c = db_get('boards', (int)$_POST['parent'])) && !$c['parent']) {
        db_insert('boards', ['title' => trim($_POST['title']), 'desc' => trim($_POST['desc'] ?? ''), 'parent' => $c['id'], 'sort' => (int)($_POST['sort'] ?? 0)]); flash('Forum eklendi.');
    } elseif ($a === 'editboard' && db_get('boards', (int)$_POST['id'])) {
        db_set('boards', (int)$_POST['id'], ['title' => trim($_POST['title']) ?: 'Başlıksız', 'desc' => trim($_POST['desc'] ?? ''), 'sort' => (int)$_POST['sort']]); flash('Güncellendi.');
    } elseif ($a === 'delboard' && ($b = db_get('boards', (int)$_POST['id']))) {
        $ids = [$b['id']];
        foreach (db_all('boards') as $x) if ($x['parent'] == $b['id']) $ids[] = $x['id'];
        $tids = array_keys(array_filter(db_all('topics'), fn($t) => in_array($t['board_id'], $ids)));
        db_delete('posts', array_keys(array_filter(db_all('posts'), fn($p) => in_array($p['topic_id'], $tids))));
        db_delete('topics', $tids); db_delete('boards', $ids); flash('Silindi.');
    } elseif (in_array($a, ['role', 'ban', 'deluser'], true) && ($t = db_get('users', (int)$_POST['id'])) && $t['id'] != $me['id']) {
        if ($a === 'role' && in_array($_POST['role'], ['user', 'mod', 'admin'], true)) db_set('users', $t['id'], ['role' => $_POST['role']]);
        if ($a === 'ban') db_set('users', $t['id'], ['banned' => $t['banned'] ? 0 : 1]);
        if ($a === 'deluser') db_delete('users', [$t['id']]);
        flash('İşlem tamamlandı.');
    }
    redirect('admin', ['tab' => $tab]);
}
$c = cfg();
?>
<h1>Yönetim Paneli</h1>
<div class="tabs"><?php foreach (['settings' => 'Genel', 'smtp' => 'SMTP', 'boards' => 'Forumlar', 'users' => 'Üyeler'] as $k => $v): ?>
<a class="<?= $tab === $k ? 'on' : '' ?>" href="<?= url('admin', ['tab' => $k]) ?>"><?= $v ?></a><?php endforeach; ?></div>

<?php if ($tab === 'settings'): ?>
<div class="card"><h2>Genel Ayarlar</h2><form class="f pad" method="post"><?= csrf_field() ?><input type="hidden" name="act" value="settings">
<label>Site adı</label><input type="text" name="site_name" value="<?= e($c['site_name']) ?>">
<label>Açıklama</label><input type="text" name="site_desc" value="<?= e($c['site_desc']) ?>">
<label>Sayfa başına öğe</label><input type="number" name="per_page" value="<?= (int)$c['per_page'] ?>">
<label><input type="checkbox" name="registration" <?= $c['registration'] ? 'checked' : '' ?>> Yeni üye kaydına izin ver</label>
<label><input type="checkbox" name="email_verify" <?= $c['email_verify'] ? 'checked' : '' ?>> E-posta doğrulaması iste (SMTP gerekir)</label><br><button>Kaydet</button></form></div>

<?php elseif ($tab === 'smtp'): ?>
<div class="card"><h2>SMTP Ayarları</h2><form class="f pad" method="post"><?= csrf_field() ?><input type="hidden" name="act" value="smtp">
<p class="muted">Sunucu boşsa PHP mail() kullanılır.</p>
<label>SMTP sunucu</label><input type="text" name="smtp_host" value="<?= e($c['smtp_host']) ?>" placeholder="smtp.example.com">
<label>Port</label><input type="number" name="smtp_port" value="<?= (int)$c['smtp_port'] ?>">
<label>Güvenlik</label><select name="smtp_secure"><?php foreach (['tls' => 'STARTTLS (587)', 'ssl' => 'SSL (465)', 'none' => 'Yok'] as $k => $v): ?><option value="<?= $k ?>" <?= $c['smtp_secure'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
<label>Kullanıcı adı</label><input type="text" name="smtp_user" value="<?= e($c['smtp_user']) ?>" autocomplete="off">
<label>Şifre (boş bırakırsanız değişmez)</label><input type="password" name="smtp_pass" autocomplete="new-password">
<label>Gönderen e-posta</label><input type="text" name="mail_from" value="<?= e($c['mail_from']) ?>">
<label>Gönderen adı</label><input type="text" name="mail_from_name" value="<?= e($c['mail_from_name']) ?>">
<label><input type="checkbox" name="test" value="1"> Kaydet ve bana test e-postası gönder</label><br><button>Kaydet</button></form></div>

<?php elseif ($tab === 'boards'):
 $bs = db_all('boards'); uasort($bs, fn($a, $b) => $a['sort'] <=> $b['sort']); ?>
<?php foreach ($bs as $b): ?><form method="post" class="row"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $b['id'] ?>">
 <div class="main" style="<?= $b['parent'] ? 'padding-left:24px' : 'font-weight:700' ?>">
 <input type="text" name="title" value="<?= e($b['title']) ?>"><?php if ($b['parent']): ?><input type="text" name="desc" value="<?= e($b['desc']) ?>" placeholder="Açıklama"><?php endif; ?></div>
 <input type="number" name="sort" value="<?= (int)$b['sort'] ?>" style="width:70px">
 <button name="act" value="editboard">Kaydet</button><button class="danger" name="act" value="delboard" data-confirm="İçindeki tüm konularla silinsin mi?">Sil</button></form><?php endforeach; ?>
<div class="card" style="margin-top:18px"><h3>Kategori ekle</h3><form class="f pad" method="post"><?= csrf_field() ?><input type="hidden" name="act" value="addcat">
<input type="text" name="title" placeholder="Kategori adı" required><br><br><button>Ekle</button></form></div>
<div class="card"><h3>Forum ekle</h3><form class="f pad" method="post"><?= csrf_field() ?><input type="hidden" name="act" value="addboard">
<select name="parent"><?php foreach ($bs as $b) if (!$b['parent']): ?><option value="<?= $b['id'] ?>"><?= e($b['title']) ?></option><?php endif; ?></select><br><br>
<input type="text" name="title" placeholder="Forum adı" required><br><br><input type="text" name="desc" placeholder="Açıklama"><br><br><button>Ekle</button></form></div>

<?php else: ?>
<div class="card" style="overflow:auto"><table><tr><th>Üye</th><th>E-posta</th><th>İleti</th><th>Rol</th><th></th></tr>
<?php foreach (db_all('users') as $u): ?><tr><td><a href="<?= url('profile', ['id' => $u['id']]) ?>"><?= e($u['username']) ?></a><?= $u['banned'] ? ' <span class="badge admin">engelli</span>' : '' ?><?= $u['verified'] ? '' : ' <span class="badge">doğrulanmamış</span>' ?></td>
<td><?= e($u['email']) ?></td><td><?= (int)$u['posts'] ?></td>
<?php if ($u['id'] == user()['id']): ?><td>Yönetici (siz)</td><td></td><?php else: ?>
<td><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="act" value="role"><input type="hidden" name="id" value="<?= $u['id'] ?>">
<select name="role" onchange="this.form.submit()" style="width:auto"><?php foreach (['user' => 'Üye', 'mod' => 'Moderatör', 'admin' => 'Yönetici'] as $k => $v): ?><option value="<?= $k ?>" <?= $u['role'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></form></td>
<td><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $u['id'] ?>">
<button name="act" value="ban"><?= $u['banned'] ? 'Engeli kaldır' : 'Engelle' ?></button>
<button class="danger" name="act" value="deluser" data-confirm="Üye silinsin mi?">Sil</button></form></td><?php endif; ?></tr><?php endforeach; ?></table></div>
<?php endif; ?>
