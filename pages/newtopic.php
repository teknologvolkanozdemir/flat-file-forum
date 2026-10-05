<?php
require_login();
$b = db_get('boards', (int)($_GET['board'] ?? 0));
if (!$b || !$b['parent']) { http_response_code(404); echo 'Forum bulunamadı.'; return; }
$title = 'Yeni Konu';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $t = trim($_POST['title'] ?? ''); $body = trim($_POST['body'] ?? '');
    if (!throttle('post', 5, 60)) flash('Çok hızlı gönderiyorsunuz.', 'err');
    elseif (mb_strlen($t) < 3 || mb_strlen($t) > 120 || mb_strlen($body) < 2 || mb_strlen($body) > 20000) flash('Başlık (3-120) ve içerik gerekli.', 'err');
    else {
        throttle_hit('post'); $u = user(); $now = time();
        $tid = db_insert('topics', ['board_id' => $b['id'], 'user_id' => $u['id'], 'title' => $t, 'created' => $now,
            'last_post' => $now, 'last_user' => $u['id'], 'replies' => 0, 'views' => 0, 'pinned' => 0, 'locked' => 0]);
        db_insert('posts', ['topic_id' => $tid, 'user_id' => $u['id'], 'body' => $body, 'created' => $now, 'edited' => 0]);
        db_set('users', $u['id'], ['posts' => $u['posts'] + 1]);
        redirect('topic', ['id' => $tid]);
    }
}
?>
<div class="crumbs"><a href="<?= url() ?>">Ana sayfa</a> › <a href="<?= url('board', ['id' => $b['id']]) ?>"><?= e($b['title']) ?></a> › Yeni konu</div>
<div class="card"><h2>Yeni Konu</h2><form class="f pad" method="post"><?= csrf_field() ?>
<label>Başlık</label><input type="text" name="title" maxlength="120" required value="<?= e($_POST['title'] ?? '') ?>">
<label>İçerik</label><?php include __DIR__ . '/../includes/editor.php'; ?><textarea name="body" required><?= e($_POST['body'] ?? '') ?></textarea><br><br>
<button>Yayınla</button></form></div>
