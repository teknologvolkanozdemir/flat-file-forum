<?php
$t = db_get('topics', (int)($_GET['id'] ?? 0));
if (!$t) { http_response_code(404); echo 'Konu bulunamadı.'; return; }
$b = db_get('boards', $t['board_id']); $users = db_all('users'); $u = user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); require_login();
    $act = $_POST['act'] ?? '';
    if ($act === 'reply') {
        $body = trim($_POST['body'] ?? '');
        if ($t['locked'] && !is_mod()) flash('Konu kilitli.', 'err');
        elseif (!throttle('post', 5, 60)) flash('Çok hızlı gönderiyorsunuz.', 'err');
        elseif (mb_strlen($body) < 2 || mb_strlen($body) > 20000) flash('İçerik 2-20000 karakter olmalı.', 'err');
        else {
            throttle_hit('post');
            db_insert('posts', ['topic_id' => $t['id'], 'user_id' => $u['id'], 'body' => $body, 'created' => time(), 'edited' => 0]);
            db_set('topics', $t['id'], ['replies' => $t['replies'] + 1, 'last_post' => time(), 'last_user' => $u['id']]);
            db_set('users', $u['id'], ['posts' => $u['posts'] + 1]);
            redirect('topic', ['id' => $t['id'], 'last' => 1]);
        }
    } elseif (in_array($act, ['pin', 'lock', 'deltopic'], true) && is_mod()) {
        if ($act === 'pin') db_set('topics', $t['id'], ['pinned' => $t['pinned'] ? 0 : 1]);
        if ($act === 'lock') db_set('topics', $t['id'], ['locked' => $t['locked'] ? 0 : 1]);
        if ($act === 'deltopic') {
            $ids = array_keys(array_filter(db_all('posts'), fn($p) => $p['topic_id'] == $t['id']));
            db_delete('posts', $ids); db_delete('topics', [$t['id']]);
            flash('Konu silindi.'); redirect('board', ['id' => $t['board_id']]);
        }
        redirect('topic', ['id' => $t['id']]);
    } elseif ($act === 'delpost') {
        $p = db_get('posts', (int)($_POST['post'] ?? 0));
        if ($p && $p['topic_id'] == $t['id'] && (is_mod() || $p['user_id'] == $u['id'])) {
            $first = min(array_keys(array_filter(db_all('posts'), fn($x) => $x['topic_id'] == $t['id'])));
            if ($p['id'] == $first) flash('İlk ileti silinemez; konuyu silin.', 'err');
            else { db_delete('posts', [$p['id']]); db_set('topics', $t['id'], ['replies' => max(0, $t['replies'] - 1)]); flash('İleti silindi.'); }
        }
        redirect('topic', ['id' => $t['id']]);
    } elseif ($act === 'editpost') {
        $p = db_get('posts', (int)($_POST['post'] ?? 0)); $body = trim($_POST['body'] ?? '');
        if ($p && $p['topic_id'] == $t['id'] && (is_mod() || $p['user_id'] == $u['id']) && mb_strlen($body) >= 2 && mb_strlen($body) <= 20000) {
            db_set('posts', $p['id'], ['body' => $body, 'edited' => time()]); flash('İleti güncellendi.');
        }
        redirect('topic', ['id' => $t['id']]);
    }
    $t = db_get('topics', $t['id']);
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_SESSION['seen'][$t['id']])) {
    $_SESSION['seen'][$t['id']] = 1; db_set('topics', $t['id'], ['views' => $t['views'] + 1]);
}
$title = $t['title'];
$posts = array_values(array_filter(db_all('posts'), fn($p) => $p['topic_id'] == $t['id']));
usort($posts, fn($a, $b2) => $a['id'] <=> $b2['id']);
$per = (int)cfg('per_page'); $pages = max(1, (int)ceil(count($posts) / $per));
$page = !empty($_GET['last']) ? $pages : min($pages, max(1, (int)($_GET['page'] ?? 1)));
$pg = ['pages' => $pages, 'page' => $page];
$rows = array_slice($posts, ($page - 1) * $per, $per);
$edit = (int)($_GET['edit'] ?? 0);
?>
<div class="crumbs"><a href="<?= url() ?>">Ana sayfa</a> › <a href="<?= url('board', ['id' => $b['id']]) ?>"><?= e($b['title']) ?></a> › <?= e($t['title']) ?></div>
<h1><?= e($t['title']) ?> <?php if ($t['locked']): ?><span class="badge">🔒 Kilitli</span><?php endif; ?></h1>
<?php if (is_mod()): ?><form method="post" class="inline"><?= csrf_field() ?>
<button name="act" value="pin" class="btn sec"><?= $t['pinned'] ? 'Sabiti kaldır' : 'Sabitle' ?></button>
<button name="act" value="lock" class="btn sec"><?= $t['locked'] ? 'Kilidi aç' : 'Kilitle' ?></button>
<button name="act" value="deltopic" class="danger" data-confirm="Konu silinsin mi?">Konuyu sil</button></form><?php endif; ?>
<div class="card" style="margin-top:14px">
<?php foreach ($rows as $p): $a = $users[$p['user_id']] ?? ['username' => 'Silinmiş', 'posts' => 0, 'role' => 'user']; ?>
 <div class="post" id="p<?= $p['id'] ?>">
  <div class="who"><?= avatar($a['username']) ?><div><a href="<?= url('profile', ['id' => $p['user_id']]) ?>"><b><?= e($a['username']) ?></b></a></div>
   <?php if ($a['role'] !== 'user'): ?><span class="badge <?= $a['role'] ?>"><?= $a['role'] === 'admin' ? 'Yönetici' : 'Moderatör' ?></span><?php endif; ?>
   <div class="muted"><?= (int)$a['posts'] ?> ileti</div></div>
  <div class="body"><div class="muted"><?= date('d.m.Y H:i', $p['created']) ?><?= $p['edited'] ? ' · düzenlendi' : '' ?></div>
  <?php if ($edit === $p['id'] && $u && (is_mod() || $p['user_id'] == $u['id'])): ?>
   <form method="post" class="f"><?= csrf_field() ?><input type="hidden" name="act" value="editpost"><input type="hidden" name="post" value="<?= $p['id'] ?>">
   <textarea name="body"><?= e($p['body']) ?></textarea><br><button>Kaydet</button></form>
  <?php else: ?><div><?= render_text($p['body']) ?></div>
   <?php if ($u && (is_mod() || $p['user_id'] == $u['id'])): ?><div class="muted" style="margin-top:8px">
    <a href="<?= url('topic', ['id' => $t['id'], 'page' => $page, 'edit' => $p['id']]) ?>">Düzenle</a>
    <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="act" value="delpost"><input type="hidden" name="post" value="<?= $p['id'] ?>"><button class="danger" data-confirm="Silinsin mi?">Sil</button></form></div><?php endif; ?>
  <?php endif; ?></div></div>
<?php endforeach; ?></div>
<?php include __DIR__ . '/../includes/pager.php'; ?>
<?php if (!$u): ?><p><a href="<?= url('login') ?>">Yanıtlamak için giriş yapın</a></p>
<?php elseif ($t['locked'] && !is_mod()): ?><p class="muted">Bu konu kilitli.</p>
<?php else: ?><div class="card"><h3>Yanıt yaz</h3><form class="f pad" method="post"><?= csrf_field() ?><input type="hidden" name="act" value="reply">
<?php include __DIR__ . '/../includes/editor.php'; ?><textarea name="body" required></textarea><br><br><button>Gönder</button></form></div><?php endif; ?>
