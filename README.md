# TOPLUCA V1

FTP ile yüklenebilen PHP 8.1+ / MySQL tabanlı TOPLUCA e-ticaret çekirdeği.

## Kurulum
1. Depoyu ZIP olarak indirip `public_html` içine çıkarın.
2. Hosting panelinden boş bir MySQL veritabanı oluşturun.
3. Tarayıcıdan `/install.php` adresini açın.
4. Veritabanı bilgilerini girip kurulumu tamamlayın.
5. Yönetim paneli: `/webyonet/`

Başlangıç yönetici hesabı:
- Kullanıcı: `emrah`
- Şifre: `emr321456`

İlk girişten sonra şifreyi değiştirin.

## Gereksinimler
- PHP 8.1+
- PDO MySQL
- MySQL 5.7+ / MariaDB 10.4+
- Apache mod_rewrite önerilir

## Not
Kredi kartı ödeme entegrasyonu bu çekirdekte bilerek etkin değildir. PayTR/iyzico gibi bir sağlayıcı daha sonra bağlanmalıdır; kart verisi uygulama veritabanında tutulmamalıdır.
