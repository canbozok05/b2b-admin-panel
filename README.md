# B2B Yönetim Paneli

Bir toptancı/e-ticaret firmasının envanter ve sipariş süreçlerini yönettiği bir **Admin Panel (Back-office)** uygulaması. Laravel + React + Inertia.js ile geliştirilmiştir.

Bu proje bir **staj çalışması** olarak, sıfırdan öğrenilerek geliştirilmiştir.

## Özellikler

- **Dashboard**: toplam müşteri, aylık satış hacmi, bekleyen sipariş sayısı ve kritik stok uyarıları — gerçek zamanlı veritabanı verileriyle.
- **Kategori yönetimi**: iç içe geçebilen (üst/alt) kategoriler, ekleme/düzenleme/silme.
- **Ürün yönetimi**: kategoriye bağlı ürünler, isme göre anlık arama/filtreleme (sayfa yenilenmeden), zengin metin editörlü (TipTap) açıklama alanı, çoklu görsel yükleme ve silme.
- **Sipariş operasyonları**: bekleyen siparişlerin öne çıkarıldığı liste, sipariş detay sayfası (müşteri bilgisi, ürünler, toplam tutar), durum güncelleme ve durum değiştiğinde müşteriye otomatik email bildirimi.
- **Rol tabanlı erişim kontrolü**: Spatie Laravel Permission ile "Süper Admin" ve "Depo Görevlisi" rolleri — yetkisiz menü öğeleri sidebar'da gizlenir, route seviyesinde de korunur (sadece görsel gizleme değil).
- **Sistem Yöneticileri**: yönetici hesapları oluşturma, düzenleme, rol atama ve silme (sadece Süper Admin).

## Teknolojiler

- **Back-end**: Laravel 13, Spatie Laravel Permission
- **Front-end**: React 19, Inertia.js, TypeScript, Tailwind CSS
- **Zengin metin editörü**: TipTap
- **Veritabanı**: SQLite (geliştirme ortamı)

## Kurulum

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

Seeder çalıştıktan sonra iki test hesabı hazır olur:

| Rol | Email | Şifre |
|---|---|---|
| Süper Admin | test@example.com | password |
| Depo Görevlisi | depo@example.com | password |

## Geliştirme ortamını çalıştırma

```bash
composer dev
```

Bu komut Laravel sunucusunu, kuyruk dinleyicisini ve Vite'ı birlikte başlatır.
