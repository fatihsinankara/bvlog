# Bvlog — Kişisel Blog, Portfolyo ve Özgeçmiş (CV) Sistemi

![PHP](https://img.shields.io/badge/PHP-8.1%2B-18181b?style=flat-square&logo=php)
![MySQL](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-5.7%2B-18181b?style=flat-square&logo=mysql)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.4-18181b?style=flat-square&logo=tailwindcss)
![Alpine.js](https://img.shields.io/badge/Alpine.js-3.14-18181b?style=flat-square&logo=alpinedotjs)
![License](https://img.shields.io/badge/Lisans-MIT-16a34a?style=flat-square)

**Bvlog**, ağır framework bağımlılıkları olmadan standart paylaşımlı hostinglerde (cPanel, Plesk, DirectAdmin, LiteSpeed/Apache) sorunsuz çalışmak üzere sıfırdan geliştirilmiş; **güvenlik**, **sadelik** ve **okunabilirlik** odaklı açık kaynaklı bir kişisel yayın platformudur.

Varsayılan olarak yalın bir kişisel blog olarak çalışır; ihtiyacınıza göre yönetim panelinden **Portfolyo Vitrini** ve **CV / Özgeçmiş** modüllerini tek tıkla etkinleştirebilirsiniz.

---

## ✨ Öne Çıkan Özellikler

### 1. Paylaşımlı Hosting Dostu & 4 Adımlı Kurulum Sihirbazı
- Sunucuda SSH erişimi, Composer veya Node.js **gerektirmez**.
- Tüm ön yüz varlıkları (`/assets`) önceden derlenmiş olarak gelir.
- Dosyaları FTP veya Dosya Yöneticisi ile yükleyip site adresinize girdiğinizde çalışan `/install` sihirbazı; ortam gereksinimlerini denetler, veritabanı tablolarını kurar ve kurulum bittiğinde kendini otomatik olarak kilitler.

### 2. Öncelik 1: Katı Güvenlik Mimarisi
- **SQL Injection Koruması**: Tüm sorgularda `PDO` gerçek hazır ifadeleri (`ATTR_EMULATE_PREPARES = false`) kullanılır.
- **Zorunlu CSRF Koruması**: Tüm `POST` formlarında ve AJAX isteklerinde kriptografik CSRF jetonu doğrulanır.
- **XSS & İçerik Temizleyici (`Core\Sanitizer`)**: Editörden kaydedilen HTML içerikler beyaz liste (whitelist) tabanlı DOM ayrıştırıcıdan geçirilir; `<script>`, `onload`, `onerror`, `javascript:` gibi zararlı vektörler temizlenir.
- **Brute-Force Koruması (`Core\RateLimiter`)**: Yönetim paneli girişleri, yorumlar ve iletişim formu IP bazlı hız sınırlandırmasına tabidir.
- **Güvenli Görsel Yükleme**: Yalnızca dosya uzantısına değil, `finfo` gerçek MIME tipine ve `getimagesize()` görüntü başlığına bakılarak doğrulama yapılır; yüklenen görseller GD kütüphanesiyle yeniden oluşturularak olası zararlı EXIF/payload verilerinden arındırılır.

### 3. Özel Zengin İçerik Editörü & Akıllı Medya
- Sürükle-bırak veya panodan (`Ctrl+V`) doğrudan editör içine görsel yapıştırma.
- **Akıllı Embed Desteği**: Editöre yapıştırılan YouTube, Spotify, CodePen, X (Twitter) ve GitHub Gist bağlantıları otomatik olarak duyarlı (responsive) medya kartlarına dönüştürülür.
- **Otomatik Görsel Optimizasyonu**:
  - **Öne Çıkan Görseller**: En-boy oranı korunarak maksimum `1080px` genişliğe boyutlandırılır, sıkıştırılır ve `.webp` formatında kaydedilir.
  - **İçerik İçi Görseller**: Çözünürlük bozulmadan kayıpsız sıkıştırmayla `.webp` formatına dönüştürülür.
- **Merkezi Medya Galerisi**: Yüklenen tüm görselleri tek ekrandan yönetme, SEO `alt` metinlerini düzenleme ve yazılarda/kategorilerde/projelerde tekrar kullanabilme.

### 4. Renk, İkon ve Kapak Destekli Kategoriler
- Her kategoriye özel ikon, düz renk kodu ve arşiv kapak görseli tanımlanabilir.
- Seçilen renk ve ikonlar site genelindeki yazı kartlarında, kategori arşivlerinde ve yönetim panelinde otomatik olarak kullanılır.

### 5. Modüler Yapı & Özelleştirilebilir Anasayfa
- **Anasayfa Yerleşim Yöneticisi**: Giriş (Hero) stilini (*Minimalist Yazar Kartı*, *Öne Çıkan Yazı Vitrini*, *İkiye Bölünmüş* veya *Kapalı*) ve yazı listeleme düzenini (*Okuma Listesi* / *Kart Izgara*) seçebilme; anasayfa bloklarının sırasını değiştirebilme.
- **Portfolyo Modülü (`/portfolio`)**: Proje vitrini, kullanılan teknolojiler, canlı demo ve kaynak kod bağlantıları.
- **CV / Özgeçmiş Modülü (`/cv`)**: İş deneyimleri, eğitim geçmişi ve teknik yetkinlikleri içeren, tarayıcıdan doğrudan PDF/yazdırma (`@media print`) uyumlu özgeçmiş sayfası.

### 6. Hata Korumalı (Crash-Proof) Tema ve Eklenti Sistemi
- Yönetim panelinden `.zip` paketi olarak tema veya eklenti yükleyebilme.
- **Otomatik Eklenti Karantinası**: Etkinleştirilen bir eklenti çalışma zamanı hatası (`Throwable` / `ParseError`) üretirse sistem çökmez; ilgili eklenti otomatik olarak devre dışı bırakılıp karantinaya alınır ve yöneticiye hata detayı raporlanır.
- **Güvenli Tema Yedeklemesi (Fallback)**: Aktif temada eksik bir şablon dosyası varsa çekirdek otomatik olarak varsayılan (`default`) şablonu yükler.

### 7. Kusursuz SEO Altyapısı
- Yazı ekleme/düzenleme ekranında canlı Google arama sonucu önizlemesi.
- Otomatik `/sitemap.xml`, `/rss.xml` (`/feed`) ve `/robots.txt` üretimi.
- Canonical URL, OpenGraph, Twitter Card ve Schema.org (`BlogPosting`, `Person`, `WebSite`) JSON-LD yapısal verileri.

---

## 📋 Sistem Gereksinimleri

- **PHP**: `8.1` veya üzeri (`8.2` / `8.3` / `8.4` tam uyumlu)
- **Veritabanı**: MySQL `5.7+` veya MariaDB `10.3+`
- **PHP Eklentileri**: `pdo_mysql`, `gd` (WebP desteğiyle), `mbstring`, `fileinfo`, `zip` (tema/eklenti ZIP yükleme için)
- **Web Sunucusu**: Apache (`mod_rewrite` aktif) veya LiteSpeed / Nginx

---

## 🚀 Kurulum

### A) Paylaşımlı Hosting (cPanel / Plesk / FTP) Kurulumu
1. Bu repoyu ZIP olarak indirin veya klonlayın ve dosyaları sunucunuzun `public_html` (veya alt klasör) dizinine yükleyin.
2. `config/`, `storage/`, `themes/` ve `plugins/` dizinlerinin yazılabilir (`755` veya `775`) olduğundan emin olun.
3. Tarayıcınızdan sitenizin adresine (`https://alanadiniz.com/install`) gidin.
4. **4 Adımlı Kurulum Sihirbazını** takip edin:
   - **Adım 1**: Sunucu ve PHP eklenti kontrolü
   - **Adım 2**: Veritabanı bağlantı bilgileri (`config/database.php` otomatik oluşturulur ve tablolar kurulur)
   - **Adım 3**: Site başlığı, çalışma modu ve yönetici hesabı oluşturma
   - **Adım 4**: Kurulum tamamlanır ve `storage/installed.lock` oluşturularak kurulum sihirbazı kilitlenir.

### B) Geliştirici Ortamı (CSS/JS Yeniden Derleme)
Ön yüz stil ve betiklerinde (`resources/css/app.css` veya `resources/js/app.js`) değişiklik yapmak isterseniz:

```bash
npm install
npm run dev     # Geliştirme modu
npm run build   # Üretim için /assets dizinine derler
```

---

## 📂 Dizin Yapısı

```text
bvlog/
├── app/
│   ├── Controllers/        # Ön yüz ve Yönetim Paneli (Admin) denetleyicileri
│   ├── Models/             # PDO tabanlı veri modelleri
│   └── Views/              # Yönetim paneli, kurulum ve çekirdek şablonları
├── assets/                 # Vite ile derlenmiş üretim CSS, JS ve FontAwesome dosyaları
├── config/                 # Uygulama ve veritabanı yapılandırması
├── core/                   # Çekirdek sınıflar (Router, Database, Security, ImageService, Hook vb.)
├── plugins/                # Yüklenen eklentiler (ör. reading-progress)
├── resources/              # Tailwind CSS ve Alpine.js kaynak dosyaları
├── storage/
│   ├── cache/              # Önbellek dizini
│   ├── logs/               # Sistem ve hata günlükleri
│   └── uploads/            # Medya kütüphanesi görselleri (Yıl/Ay kırılımlı)
├── themes/                 # Temalar (default çekirdek teması ve özel temalar)
├── .htaccess               # Güvenlik ve rota yönlendirme kuralları
├── index.php               # Tekil giriş noktası (Front Controller)
└── schema.sql              # Veritabanı şeması
```

---

## 🧩 Eklenti & Tema Geliştirme

### Eklenti Oluşturma (`plugins/eklenti-adi/`)
Her eklenti klasöründe bir `plugin.json` ve bir `boot.php` yer alır:

**`plugins/ornek-eklenti/plugin.json`**:
```json
{
  "name": "Örnek Eklenti",
  "version": "1.0.0",
  "author": "Geliştirici",
  "description": "Yazı sonuna özel bilgi kutusu ekler."
}
```

**`plugins/ornek-eklenti/boot.php`**:
```php
<?php
use Core\Hook;

Hook::addAction('theme_head', function () {
    echo '<!-- Özel Head Çıktısı -->';
});

Hook::addFilter('post_content', function (string $content): string {
    return $content . '<p><em>Okuduğunuz için teşekkürler!</em></p>';
});
```

### Tema Oluşturma (`themes/tema-adi/`)
`themes/tema-adi/theme.json` dosyasını oluşturup yalnızca özelleştirmek istediğiniz şablonları (`layouts/main.php`, `home/index.php`, `blog/show.php` vb.) kendi tema klasörünüze eklemeniz yeterlidir. Bulunmayan şablonlar otomatik olarak varsayılan temadan tamamlanır.

---

## 📄 Lisans

Bu proje **MIT Lisansı** altında tamamen ücretsiz ve açık kaynak olarak sunulmaktadır. Dilediğiniz gibi kullanabilir, değiştirebilir ve dağıtabilirsiniz.
