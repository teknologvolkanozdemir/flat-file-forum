# Flat File Forum

Veritabanı gerektirmeyen, JSON dosyalarıyla çalışan basit ve şık bir forum scripti (PHP 7.4+/8.x).

## Özellikler
- Üye ol / giriş yap / çıkış, şifre sıfırlama, e-posta doğrulama (opsiyonel)
- Kategoriler, forumlar, konular, yanıtlar, sayfalama, arama, BBCode (b, i, code, quote)
- Konu sabitleme/kilitleme, ileti düzenleme/silme, moderatör rolü
- Profil sayfası, avatar baş harfi
- Yönetim paneli: genel ayarlar, **SMTP ayarları** (test e-postası dahil), forum/kategori yönetimi, üye yönetimi (rol, engelleme, silme)
- CSRF koruması, `password_hash`, oturum yenileme, hız sınırlama, mobil uyumlu tasarım

## Kurulum
1. Dosyaları PHP çalışan bir sunucuya yükleyin (`data/` dizini yazılabilir olmalı).
2. `index.php` adresini açın; kurulum ekranında yönetici hesabını oluşturun.
3. Yerel deneme: `php -S localhost:8000`

> Not: `data/` Apache'de `.htaccess` ile korunur. Nginx kullanıyorsanız `data/` ve `includes/` dizinlerine erişimi engelleyin.
