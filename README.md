# TOPLUCA V3

FTP/Plesk üzerinde çalışacak şekilde hazırlanmış PHP 8.1+ / MySQL e-ticaret çekirdeği.

## Geçici test kurulumu

Hedef URL: `https://iksiryayincilik.com/topluca`

1. `v3-rebuild` dalındaki dosyaları `topluca` klasörüne yükleyin.
2. Mevcut çalışan `config.php` varsa koruyabilirsiniz. Yoksa Plesk'te oluşturduğunuz MySQL bilgileriyle `install.php` üzerinden bağlantı oluşturun.
3. Temiz geliştirme kurulumu için installer'da **Mevcut TOPLUCA tablolarını tamamen sıfırla** seçeneğini işaretleyin ve onay alanına `TOPLUCA` yazın.
4. Kurulum bitince `install.php` dosyasını sunucudan silin.
5. WebYönet: `/topluca/webyonet/`

İlk WebYönet hesabı:
- Kullanıcı: `emrah`
- Başlangıç şifresi: `emr321456`

İlk girişten sonra şifre değiştirilmelidir.

## Checkout akışı

`Sepet → Adres → Teslimat → Ödeme → Son Kontrol → Sipariş`

Teslimat seçenekleri adres girilmeden hesaplanmaz. Ankara aynı gün teslimat motoru; il, ilçe, çalışma günü, tatil, saat kesimi, günlük kapasite, ürün uygunluğu ve sepet limitini birlikte kontrol eder. Ankara dışı adreslerde aynı gün teslimat seçilemez.

Misafir müşteri siparişini tamamladıktan sonra yalnızca şifre belirleyerek hesap oluşturabilir; sipariş ve teslimat adresi otomatik olarak yeni hesaba bağlanır.

## WebYönet

Dashboard, sipariş, ürün, kategori, marka, stok, satın alma, tedarikçi, müşteri, kargo, Ankara aynı gün, kampanya, banner, ana sayfa modülleri, arama eş anlamlıları, sepet analizi, satış/kâr raporu, CSV içe/dışa aktarma, site ayarları ve işlem logları bulunur.

## Ödeme

Kart ödeme ekranı gerçek bir ödeme kuruluşu entegrasyonu olmadan aktif edilmemelidir. Kart/CVV verisi TOPLUCA veritabanında saklanmamalıdır.

## FTP notu

TOPLUCA PHP dosyaları FTP servisini açıp kapatamaz. FTP erişimi Plesk sistem kullanıcısı, FTP servisi, TCP 21, FTPS/pasif mod ve firewall/Fail2Ban ayarlarından bağımsız olarak yönetilir.
