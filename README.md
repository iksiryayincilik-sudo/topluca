# TOPLUCA V4 Professional

TOPLUCA için PHP 8.0+ / MySQL tabanlı, Composer gerektirmeyen ve FTP/Plesk ile kurulabilen e-ticaret sistemi.

## Kurulum

1. Paket içeriğini sitenin hedef klasörüne yükleyin. Test adresi için hedef: `/httpdocs/topluca/`.
2. Plesk'te boş bir MySQL veritabanı ve kullanıcı oluşturun.
3. Tarayıcıda `https://iksiryayincilik.com/topluca/install.php` adresini açın.
4. MySQL bilgilerini ve site adresini girin.
5. Demo veritabanını tamamen sıfırlamak istiyorsanız **Mevcut TOPLUCA tablolarını silip sıfırdan kur** seçeneğini işaretleyip `TOPLUCA SIFIRLA` onayını yazın.
6. Kurulum tamamlanınca `install.php` dosyasını sunucudan kaldırın.

## WebYönet

Adres: `/webyonet/`

İlk kullanıcı: `emrah`

Başlangıç şifresi: `emr321456`

Şifre veritabanında `password_hash()` ile tutulur. İlk girişten sonra WebYönet → Site Ayarları → Güvenlik bölümünden değiştirilmelidir.

## V4 özellikleri

- 7 ana kategori ve çok seviyeli kategori ağacı
- Ürün, marka, barkod, ISBN, stok kodu ve eş anlamlı arama
- Marka/fiyat/stok filtreleri ve sıralama
- Ürün galerisi ve çoklu görsel yükleme
- Teknik ürün özellikleri
- Kademeli/toplu alım fiyatları
- Sepet ve guest checkout
- Adres → Teslimat → Ödeme → Son Kontrol akışı
- Ankara aynı gün teslimat: şehir, ilçe, saat, tatil, ürün uygunluğu, kapasite ve sepet limiti kontrolü
- Havale/EFT ödeme ekranı ve WebYönet'ten düzenlenebilir banka bilgileri
- Benzersiz sipariş kodları
- Sipariş, ödeme ve durum geçmişi
- Guest siparişten sonra sadece şifre belirleyerek üyelik oluşturma
- Üyeler, adresler, favoriler ve sipariş geçmişi
- Stok hareketleri, tedarikçiler ve alış girişleri
- Kampanyalar
- Masaüstü/mobil banner yönetimi ve ana sayfa modülleri
- Arama, sonuçsuz arama, aktif/terk sepet ve ürün performans analizi
- Günlük ciro ve brüt kâr dashboard'u
- Admin işlem logları
- CSV ürün içe/dışa aktarma
- Responsive storefront ve responsive WebYönet

## Logo

Storefront önce `assets/img/topluca_logo.png` dosyasını arar. Bu dosya mevcutsa doğrudan onu kullanır; yoksa `assets/img/topluca-logo.svg` fallback'i kullanılır.

## Güvenlik

PDO prepared statements, CSRF tokenları, HttpOnly/SameSite session cookie'leri, login rate limiting, upload MIME/size kontrolü, admin audit logları ve public erişimi kapatılmış uygulama/veritabanı klasörleri bulunur.

Kredi kartı V4 test paketinde aktif değildir. Canlı kart ödemesinde kart verisi TOPLUCA üzerinde tutulmadan PayTR/iyzico gibi hosted/redirect bir sağlayıcı bağlanmalıdır.
