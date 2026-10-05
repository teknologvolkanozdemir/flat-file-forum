<?php
$u = db_get('users', (int)($_GET['id'] ?? 0)); $tok = (string)($_GET['token'] ?? '');
if ($u && !empty($u['token']) && hash_equals($u['token'], hash('sha256', $tok))) {
    db_set('users', $u['id'], ['verified' => 1, 'token' => '']); flash('E-posta doğrulandı. Giriş yapabilirsiniz.');
} else flash('Geçersiz veya kullanılmış bağlantı.', 'err');
redirect('login');
