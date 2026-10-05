<?php
$boards = db_all('boards'); uasort($boards, fn($a, $b) => $a['sort'] <=> $b['sort']);
$topics = db_all('topics'); $users = db_all('users');
$stats = [];
foreach ($topics as $t) {
    $s = &$stats[$t['board_id']];
    $s['t'] = ($s['t'] ?? 0) + 1; $s['p'] = ($s['p'] ?? 0) + $t['replies'] + 1;
    if (!isset($s['last']) || $t['last_post'] > $s['last']['last_post']) $s['last'] = $t;
    unset($s);
}
$cats = array_filter($boards, fn($b) => !$b['parent']);
?>
<?php foreach ($cats as $c): ?>
<div class="card"><h2><?= e($c['title']) ?></h2>
<?php $any = false; foreach ($boards as $b) if ($b['parent'] == $c['id']): $any = true; $s = $stats[$b['id']] ?? []; ?>
 <div class="row">
  <div class="main"><a href="<?= url('board', ['id' => $b['id']]) ?>"><b><?= e($b['title']) ?></b></a><div class="muted"><?= e($b['desc']) ?></div></div>
  <div class="stat"><b><?= $s['t'] ?? 0 ?></b>konu</div><div class="stat"><b><?= $s['p'] ?? 0 ?></b>ileti</div>
  <div class="muted" style="width:170px"><?php if (!empty($s['last'])): $l = $s['last']; ?>
   <a href="<?= url('topic', ['id' => $l['id'], 'last' => 1]) ?>"><?= e(mb_strimwidth($l['title'], 0, 24, '…')) ?></a><br>
   <?= e($users[$l['last_user']]['username'] ?? '?') ?>, <?= ago($l['last_post']) ?>
  <?php else: ?>–<?php endif; ?></div>
 </div>
<?php endif; if (!$any): ?><div class="row muted">Bu kategoride forum yok.</div><?php endif; ?>
</div>
<?php endforeach; ?>
<div class="card"><div class="pad muted">Üye: <b><?= count($users) ?></b> &middot; Konu: <b><?= count($topics) ?></b> &middot; Son üye: <b><?= e(end($users)['username'] ?? '-') ?></b></div></div>
