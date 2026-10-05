<?php
$b = db_get('boards', (int)($_GET['id'] ?? 0));
if (!$b || !$b['parent']) { http_response_code(404); echo 'Forum bulunamadı.'; return; }
$title = $b['title'];
$users = db_all('users');
$all = array_filter(db_all('topics'), fn($t) => $t['board_id'] == $b['id']);
usort($all, fn($x, $y) => [$y['pinned'], $y['last_post']] <=> [$x['pinned'], $x['last_post']]);
$per = (int)cfg('per_page'); $pg = paginate(count($all), (int)($_GET['page'] ?? 1), $per);
$rows = array_slice($all, ($pg['page'] - 1) * $per, $per);
?>
<div class="crumbs"><a href="<?= url() ?>">Ana sayfa</a> › <?= e($b['title']) ?></div>
<p><?php if (user()): ?><a class="btn" href="<?= url('newtopic', ['board' => $b['id']]) ?>">+ Yeni Konu</a><?php else: ?><a href="<?= url('login') ?>">Konu açmak için giriş yapın</a><?php endif; ?></p>
<div class="card"><h2><?= e($b['title']) ?></h2>
<?php foreach ($rows as $t): ?>
 <div class="row"><?= avatar($users[$t['user_id']]['username'] ?? '?') ?>
  <div class="main"><?php if ($t['pinned']): ?><span class="badge pin">📌 Sabit</span><?php endif; ?><?php if ($t['locked']): ?><span class="badge">🔒</span><?php endif; ?>
   <a href="<?= url('topic', ['id' => $t['id']]) ?>"><b><?= e($t['title']) ?></b></a>
   <div class="muted"><?= e($users[$t['user_id']]['username'] ?? '?') ?> · <?= ago($t['created']) ?></div></div>
  <div class="stat"><b><?= $t['replies'] ?></b>yanıt</div><div class="stat"><b><?= $t['views'] ?></b>görüntülenme</div>
  <div class="muted" style="width:120px"><?= ago($t['last_post']) ?><br><?= e($users[$t['last_user']]['username'] ?? '?') ?></div></div>
<?php endforeach; if (!$rows): ?><div class="row muted">Henüz konu yok.</div><?php endif; ?></div>
<?php include __DIR__ . '/../includes/pager.php'; ?>
