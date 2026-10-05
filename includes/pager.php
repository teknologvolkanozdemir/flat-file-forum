<?php if ($pg['pages'] > 1): $q = $_GET; unset($q['p'], $q['page'], $q['last']); ?>
<div class="pager"><?php for ($i = 1; $i <= $pg['pages']; $i++): ?>
<?php if ($i == $pg['page']): ?><span><?= $i ?></span><?php else: ?><a href="<?= e(url($_GET['p'] ?? 'home', $q + ['page' => $i])) ?>"><?= $i ?></a><?php endif; ?>
<?php endfor; ?></div><?php endif; ?>
