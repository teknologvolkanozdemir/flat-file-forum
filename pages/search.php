<?php
$q = trim($_GET['q'] ?? ''); $title = 'Arama';
$res = [];
if (mb_strlen($q) >= 2) {
    $topics = db_all('topics');
    foreach (db_all('posts') as $p) {
        if (!isset($topics[$p['topic_id']])) continue;
        if (mb_stripos($p['body'], $q) !== false || mb_stripos($topics[$p['topic_id']]['title'], $q) !== false) $res[$p['topic_id']] = $topics[$p['topic_id']];
    }
}
?>
<h2>Arama: <?= e($q) ?></h2>
<div class="card"><?php foreach (array_slice($res, 0, 50) as $t): ?>
<div class="row"><div class="main"><a href="<?= url('topic', ['id' => $t['id']]) ?>"><b><?= e($t['title']) ?></b></a><div class="muted"><?= ago($t['created']) ?></div></div></div>
<?php endforeach; if (!$res): ?><div class="row muted"><?= mb_strlen($q) < 2 ? 'En az 2 karakter girin.' : 'Sonuç yok.' ?></div><?php endif; ?></div>
