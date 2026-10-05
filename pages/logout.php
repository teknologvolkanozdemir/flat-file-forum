<?php
if (hash_equals(csrf(), (string)($_GET['t'] ?? ''))) { unset($_SESSION['uid']); session_regenerate_id(true); flash('Çıkış yapıldı.'); }
redirect('home');
