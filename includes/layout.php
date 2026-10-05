<!doctype html>
<html lang="tr"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e(($title ? $title . ' - ' : '') . cfg('site_name')) ?></title>
<link rel="stylesheet" href="assets/style.css">
</head><body>
<header class="top"><div class="wrap bar">
 <a class="brand" href="<?= url() ?>">💬 <?= e(cfg('site_name')) ?></a>
 <form class="search" action="index.php" method="get"><input type="hidden" name="p" value="search"><input name="q" placeholder="Ara..." value="<?= e($_GET['q'] ?? '') ?>"></form>
 <button class="burger" onclick="document.querySelector('.nav').classList.toggle('open')">☰</button>
 <nav class="nav">
 <?php if ($u = user()): ?>
  <?php if (is_admin()): ?><a href="<?= url('admin') ?>">Yönetim</a><?php endif; ?>
  <a href="<?= url('profile', ['id' => $u['id']]) ?>"><?= e($u['username']) ?></a>
  <a href="<?= url('logout', ['t' => csrf()]) ?>">Çıkış</a>
 <?php else: ?>
  <a href="<?= url('login') ?>">Giriş</a><a class="btn" href="<?= url('register') ?>">Üye Ol</a>
 <?php endif; ?>
 </nav></div></header>
<main class="wrap">
<?php foreach (flash() as [$t, $m]): ?><div class="flash <?= $t ?>"><?= e($m) ?></div><?php endforeach; ?>
<?= $content ?>
</main>
<footer class="wrap foot">&copy; <?= date('Y') ?> <?= e(cfg('site_name')) ?> &middot; <?= e(cfg('site_desc')) ?></footer>
<script src="assets/app.js"></script>
</body></html>
