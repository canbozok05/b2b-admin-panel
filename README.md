# B2B Yönetim Paneli

[![tests](https://github.com/canbozok05/b2b-admin-panel/actions/workflows/tests.yml/badge.svg)](https://github.com/canbozok05/b2b-admin-panel/actions/workflows/tests.yml)

Bir toptancı/e-ticaret firmasının envanter ve sipariş süreçlerini yönettiği bir **Admin Panel (Back-office)** uygulaması. Laravel + React + Inertia.js ile geliştirilmiştir.

Bu proje bir **staj çalışması** olarak, sıfırdan öğrenilerek geliştirilmiştir.

## Özellikler

- **Dashboard**: toplam müşteri, aylık satış hacmi (yalnızca onaylanmış ve sonraki durumdaki siparişlerden), bekleyen sipariş sayısı ve **kategori bazlı kritik stok eşiğine** göre hesaplanan stok uyarıları — gerçek zamanlı veritabanı verileriyle.
- **Kategori yönetimi**: iç içe geçebilen (üst/alt) kategoriler, her kategoriye özel **KDV oranı** ve **kritik stok eşiği**, ekleme/düzenleme/silme.
- **Ürün yönetimi**: kategoriye bağlı ürünler, isme göre anlık arama/filtreleme (sayfa yenilenmeden), zengin metin editörlü (TipTap) açıklama alanı, çoklu görsel yükleme/silme, aktif bir kampanya varsa indirimli fiyatın gösterilmesi.
- **Müşteri yönetimi**: müşteri kayıtları, her müşteriye ev/iş/okul gibi etiketli **birden çok teslimat adresi**, isim/e-posta/telefon üzerinde Türkçe büyük-küçük harf duyarlılığını gözeten arama ve duruma göre filtreleme.
- **Sipariş operasyonları**: bekleyen siparişlerin öne çıkarıldığı liste; sipariş oluşturma/düzenlemede müşterinin kayıtlı bir adresini seçme ya da siparişle birlikte yeni bir adres ekleme (zorunlu), KDV'nin fiyata dahil olduğu varsayılıp geriye doğru hesaplanması, aktif kampanya varsa indirimli birim fiyatın kullanılması; sipariş detay sayfası (müşteri, teslimat adresi, ürünler, ara toplam/indirim/nihai toplam), durum güncelleme ve durum değiştiğinde müşteriye otomatik email bildirimi.
- **Kampanyalar**: bir ürüne veya bir kategorinin tamamına, belirli bir tarih aralığında geçerli olacak yüzde ya da sabit tutarlı indirim tanımlama; ürüne özel kampanya kategori kampanyasından önceliklidir.
- **İndirim kodları**: ürün veya kategori bazlı, yüzde/sabit tutarlı, isteğe bağlı asgari sipariş tutarı şartlı kuponlar; sipariş formunda kaydetmeden önce kodu doğrulayıp uygulanacak indirimi gösteren bir kontrol düğmesi.
- **Satış raporları**: bu ayın ve bir önceki ayın toplam satışını, sipariş listesini ve en çok satan ürünlerini gösteren bir rapor sayfası; aynı rapor tek tıkla PDF olarak indirilebilir.
- **Rol tabanlı erişim kontrolü**: Spatie Laravel Permission ile "Süper Admin" ve "Depo Görevlisi" rolleri — yetkisiz menü öğeleri sidebar'da gizlenir, route seviyesinde de korunur (sadece görsel gizleme değil).
- **Sistem Yöneticileri**: yönetici hesapları oluşturma, düzenleme, rol atama ve silme (sadece Süper Admin).

## Teknolojiler

- **Back-end**: Laravel 13, Spatie Laravel Permission
- **Front-end**: React 19, Inertia.js, TypeScript, Tailwind CSS
- **Zengin metin editörü**: TipTap
- **PDF üretimi**: barryvdh/laravel-dompdf
- **Veritabanı**: SQLite (geliştirme ortamı)

## Kurulum

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
npm run build
```

Seeder çalıştıktan sonra test hesapları ve gerçekçi bir katalog (kategori ağacı, ürünler, görseller, müşteriler, siparişler, örnek kampanya/indirim kodu) hazır olur:

| Rol | Email | Şifre |
|---|---|---|
| Süper Admin | test@example.com | password |
| Süper Admin | admin@test.com | password |
| Depo Görevlisi | depo@example.com | password |

## Geliştirme ortamını çalıştırma

```bash
composer dev
```

Bu komut Laravel sunucusunu, kuyruk dinleyicisini ve Vite'ı birlikte başlatır.

## Testler

Rol tabanlı erişim kontrolü (Süper Admin / Depo Görevlisi), kategori/ürün silme kısıtlamaları, stokun sipariş oluşturma/düzenleme/silme sırasında doğru şekilde düşülüp geri eklenmesi, kampanya ve indirim kodu hesaplamaları, müşteri adres kuralları, satış raporu hesaplamaları ve sipariş durumu güncellemesi (email bildirimi dahil) gibi uygulamanın iş kurallarını doğrulayan bir Pest test paketi bulunuyor.

```bash
php artisan test
```

Her `push` sonrası GitHub Actions üzerinde otomatik olarak çalışır (kod stili, tip kontrolü ve testler dahil).
