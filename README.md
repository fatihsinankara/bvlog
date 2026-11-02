# Bvlog — Kişisel Blog, Portfolyo ve Özgeçmiş (CV) Sistemi

![Version](https://img.shields.io/badge/S%C3%BCr%C3%BCm-v1.3.1-18181b?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-18181b?style=flat-square&logo=php)
![MySQL](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-5.7%2B-18181b?style=flat-square&logo=mysql)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.4-18181b?style=flat-square&logo=tailwindcss)
![Alpine.js](https://img.shields.io/badge/Alpine.js-3.14-18181b?style=flat-square&logo=alpinedotjs)
![License](https://img.shields.io/badge/Lisans-MIT-16a34a?style=flat-square)

**Bvlog**, ağır framework bağımlılıkları ve karmaşık derleme süreçleri olmadan standart paylaşımlı hostinglerde (cPanel, Plesk, DirectAdmin, LiteSpeed/Apache) sorunsuz çalışmak üzere sıfırdan geliştirilmiş; **güvenlik**, **hız**, **sadelik** ve **okunabilirlik** odaklı açık kaynaklı bir kişisel yayın platformudur.

Varsayılan olarak yalın bir kişisel blog olarak çalışır; ihtiyacınıza göre yönetim panelinden **Portfolyo Vitrini** ve **CV / Özgeçmiş** modüllerini tek tıkla etkinleştirebilirsiniz.

- **Canlı Demo:** https://bvlog.fsklabs.com
- **Kaynak Kod:** https://github.com/fatihsinankara/bvlog

---

## ✨ Öne Çıkan Özellikler

### 1. Paylaşımlı Hosting Dostu & 4 Adımlı Kurulum Sihirbazı
- Sunucuda SSH erişimi, Composer veya Node.js **gerektirmez**.
- Tüm ön yüz varlıkları (`/assets`) önceden derlenmiş ve optimize edilmiş olarak gelir.
- Dosyaları FTP veya Dosya Yöneticisi ile yükleyip site adresinize girdiğinizde çalışan `/install` sihirbazı; ortam gereksinimlerini denetler, veritabanı tablolarını kurar ve kurulum bittiğinde kendini otomatik olarak kilitler (`storage/installed.lock`).

### 2. Tek Tıkla OTA Güncelleme & Otomatik Veritabanı Göçü (Auto-Migrator)
- **GitHub Releases Entegrasyonu (`Core\Updater`)**: Yeni bir sürüm yayınlandığında yönetim panelinde bildirim gösterir ve sürüm notlarını listeler.
- **Tek Tıkla OTA (Over-The-Air) Güncelleme**: Kullanıcı verilerinize (`config/`, `storage/`, yüklenen görseller, özel tema ve eklentileriniz) dokunmadan çekirdek dosyaları doğrudan yönetim panelinden günceller.
- **Otomatik Şema Yükseltici (`Core\Migrator`)**: Güncelleme sonrasında yeni sürümün gerektirdiği veritabanı tablo ve sütun değişikliklerini veri kaybı olmadan otomatik olarak uygular.

### 3. Katı Güvenlik Mimarisi
- **SQL Injection Koruması**: Tüm sorgularda `PDO` gerçek hazır ifadeleri (`ATTR_EMULATE_PREPARES = false`) kullanılır.
- **Zorunlu CSRF Koruması**: Tüm `POST` formlarında ve AJAX isteklerinde kriptografik CSRF jetonu doğrulanır.
- **XSS & İçerik Temizleyici (`Core\HtmlSanitizer`)**: Editörden kaydedilen HTML içerikler beyaz liste (whitelist) tabanlı DOM ayrıştırıcıdan geçirilir; `<script>`, `onload`, `onerror`, `javascript:` gibi zararlı vektörler temizlenir.
- **Atomik Brute-Force Koruması (`Core\RateLimiter`)**: Yönetim paneli girişleri, parola sıfırlama, yorumlar ve iletişim formu IP bazlı hız sınırlandırmasına tabidir (Cloudflare `CF-Connecting-IP` gerçek ziyaretçi IP algılama destekli).
- **Güvenli Görsel Yükleme (`Core\ImageProcessor`)**: Yalnızca dosya uzantısına değil, `finfo` gerçek MIME tipine ve `getimagesize()` görüntü başlığına bakılarak doğrulama yapılır; yüklenen görseller GD kütüphanesiyle yeniden oluşturularak olası zararlı EXIF/payload verilerinden arındırılır.
- **Yönetici Parola Kurtarma**: E-posta üzerinden güvenli parola sıfırlama akışı.

### 4. Zengin İçerik Editörü, Çoklu Kategori & Zamanlanmış Yazılar
- **Sürükle-Bırak & Panodan Görsel Yapıştırma**: Editör içine `Ctrl+V` ile doğrudan görsel yapıştırma.
- **Otomatik WebP Görsel Optimizasyonu**:
  - **Öne Çıkan Görseller**: En-boy oranı korunarak maksimum `1080px` genişliğe boyutlandırılır, sıkıştırılır ve `.webp` formatında kaydedilir.
  - **İçerik İçi Görseller**: Çözünürlük bozulmadan kayıpsız sıkıştırmayla `.webp` formatına dönüştürülür.
- **Akıllı Embed Desteği**: Editöre yapıştırılan YouTube, Spotify, CodePen, X (Twitter) ve GitHub Gist bağlantıları otomatik olarak duyarlı (responsive) medya kartlarına dönüştürülür.
- **Çoklu Kategori Desteği**: Bir yazıyı birden fazla kategoriye atayabilme; her kategoriye özel ikon, renk kodu ve arşiv kapak görseli tanımlayabilme.
- **İleri Tarihli (Zamanlanmış) Yazılar**: Yazıları ileri bir tarih ve saat için planlayabilme (`scheduled`); zamanı geldiğinde otomatik olarak yayına alınma.
- **Merkezi Medya Galerisi**: Yüklenen tüm görselleri tek ekrandan yönetme, SEO `alt` metinlerini düzenleme ve içeriklerde tekrar kullanabilme.

### 5. Hibrit Zamanlanmış Görev Yöneticisi (`Core\Cron`)
- Zamanlanmış yazıların yayına alınması, arka plan güncelleme denetimi ve önbellek/rate-limit temizliği gibi periyodik görevleri yönetir.
- **3 Farklı Tetikleme Modu**:
  1. **Dahili Web-Cron (Varsayılan)**: Sunucu ayarı gerektirmeden ziyaretçi trafiğiyle arka planda sessizce çalışır.
  2. **Harici HTTP Tetikleyici**: Gizli anahtar korumalı uç nokta (`/cron/run?key=...`) ile UptimeRobot veya cron-job.org üzerinden tetiklenebilir.
  3. **Sunucu CLI Cron**: cPanel / sunucu üzerinden `php cron.php` komutuyla çalıştırılabilir.
- Yönetim panelinden tüm görevlerin çalışma geçmişini izleme ve tek tıkla manuel tetikleme imkanı.

### 6. Modüler Yapı: Blog, Portfolyo ve A4/PDF Uyumlu CV
- **Anasayfa Yerleşim Yöneticisi**: Giriş (Hero) stilini (*Minimalist Yazar Kartı*, *Öne Çıkan Yazı Vitrini*, *İkiye Bölünmüş* veya *Kapalı*) ve yazı listeleme düzenini (*Okuma Listesi* / *Kart Izgara*) seçebilme; anasayfa bloklarının sırasını değiştirebilme.
- **Portfolyo Modülü (`/portfolio`)**: Proje vitrini, kullanılan teknolojiler, canlı demo ve kaynak kod bağlantıları.
- **CV / Özgeçmiş Modülü (`/cv`)**: İş deneyimleri, eğitim geçmişi ve dinamik teknik yetenek kategorileri (CRUD) yönetimi. Tarayıcıdan doğrudan **A4 PDF kaydetme / yazdırma (`@media print`)** için özel optimize edilmiş sayfa düzeni.

### 7. Hata Korumalı (Crash-Proof) Tema ve Eklenti Ekosistemi
- Yönetim panelinden `.zip` paketi olarak tema veya eklenti yükleyebilme.
- **Otomatik Eklenti Karantinası**: Etkinleştirilen bir eklenti çalışma zamanı hatası (`Throwable` / `ParseError`) üretirse site çökmez; ilgili eklenti otomatik olarak devre dışı bırakılıp karantinaya alınır ve yöneticiye hata detayı raporlanır.
- **Güvenli Tema Yedeklemesi (Fallback)**: Aktif temada eksik bir şablon dosyası varsa çekirdek otomatik olarak varsayılan (`default`) şablonu yükler.

#### 🎨 Yerleşik Temalar
1. **Bvlog Editorial (`default`)**: Yüksek kontrastlı, saf tipografik ve okuma konforu odaklı çekirdek tema (Karanlık mod destekli).
2. **Bvlog Paper (`paper`)**: Editoryal tipografi ve dingin okuma deneyimine odaklanan minimal blog teması (Karanlık mod destekli).

#### 🔌 Kutudan Hazır Çıkan Eklentiler
1. **Yazı Koleksiyonları & Seriler (`collections`)**: Çok bölümlü yazı dizilerini ve rehberleri sıralı koleksiyonlar altında gruplar, yazı içinde seri navigasyonu sunar.
2. **İçindekiler Tablosu — TOC (`table-of-contents`)**: Yazılardaki `H2` ve `H3` başlıklarını otomatik tarayarak daraltılabilir, pürüzsüz kaydırmalı (smooth scroll) içindekiler fihristi oluşturur.
3. **CAPTCHA Güvenlik Koruması (`captcha`)**: **Cloudflare Turnstile** ve **Google reCAPTCHA v2** desteğiyle giriş, iletişim, yorum ve parola sıfırlama formlarını bot ve spam saldırılarına karşı korur.
4. **SMTP E-posta Yapılandırması (`smtp`)**: Özel SMTP sunucusu üzerinden güvenli e-posta gönderimi, canlı bağlantı testi, sunucu tanılama aracı ve eklentiler için global `bvlog_mail()` API'si sunar.
5. **E-posta Bülteni — Newsletter (`newsletter`)**: Footer üstü KVKK ve 6563 sayılı ETK açık rıza onaylı bülten abonelik kutusu, çift aşamalı doğrulama (Double Opt-In), tek tıkla abonelikten çıkış (`List-Unsubscribe`) ve SMTP üzerinden toplu/seçili bülten gönderim sistemi.
6. **Okuma İlerleme Çubuğu (`reading-progress`)**: Blog yazılarında sayfanın en üstünde sade bir okuma ilerleme göstergesi sunar.
7. **Kod Bloğu Kopyalama Butonu (`code-copy-button`)**: Yazılardaki kod bloklarının sağ üst köşesine tek tıkla kopyalama butonu ekler.

### 8. Çok Katmanlı Dahili Önbellek Motoru (`Core\Cache`)
- **L1 (Bellek İçi) + L2 (Dosya Tabanlı) Önbellek Mimarisi**: Harici Redis/Memcached bağımlılığı gerektirmeden standart paylaşımlı hostinglerde dahi veritabanı yükünü sıfıra indirir.
- **Sıfır Sorgu (Zero-Query) Ön Yüz Yanıtları**: Önbellek dolu (warm) olduğunda anasayfa, blog listeleri, kategori arşivleri, koleksiyonlar, portfolyo, CV, `/sitemap.xml` ve `/rss.xml` isteklerinde MySQL bağlantısı dahi açılmaz (`PDO` lazy connection).
- **6 İzole Önbellek Grubu**: `settings` (Ayarlar & Yazar), `home` (Anasayfa Blokları), `blog` (Yazılar, Kategoriler, Etiketler, Yorumlar, İşlenmiş TOC & Seri Kutuları), `portfolio` (Projeler), `cv` (Özgeçmiş & JSON-LD) ve `feeds` (Sitemap & RSS).
- **Akıllı Otomatik Temizleme (Auto-Invalidation)**: Yazı, kategori, yorum, proje, CV veya site ayarı güncellendiğinde veritabanı katmanı yalnızca ilgili önbellek grubunu anında otomatik olarak yeniler.
- **Gelişmiş Önbellek Yönetim Paneli (`/admin/cache`)**:
  - Tek tıkla **Önbelleği Isıtma (Preload / Warmup)**, grup bazlı temizleme veya tüm önbelleği sıfırlama.
  - **PHP OPcache** (sunucu bytecode önbelleği) isabet oranı, bellek kullanımı ve derlenmiş betik istatistiklerini izleme ve sıfırlama.
  - Canlı önbellek kayıtlarını anahtar, grup, boyut, oluşturulma zamanı ve kalan yaşam süresi (TTL) detaylarıyla filtreleyip tekil olarak yönetebilme.

### 9. Kusursuz SEO & Performans Altyapısı
- **Esnek Kalıcı Bağlantılar (Permalinks) & Otomatik 301 Yönlendirmeleri**: `/blog/{slug}`, `/{slug}`, `/{kategori}/{slug}`, `/{yil}/{ay}/{slug}` gibi URL yapıları arasından seçim yapabilme; URL yapısı değiştirildiğinde eski bağlantıları otomatik olarak `301` ile yeni adrese yönlendirme.
- Yazı ekleme/düzenleme ekranında canlı Google arama sonucu (SERP) önizlemesi.
- Otomatik ve önbellek destekli `/sitemap.xml`, `/rss.xml` (`/feed`) ve `/robots.txt` üretimi.
- Canonical URL, OpenGraph, Twitter Card ve Schema.org (`BlogPosting`, `Person`, `WebSite`) JSON-LD yapısal verileri.
- `.htaccess` üzerinde hazır Gzip/Brotli sıkıştırma ve statik dosya tarayıcı önbellekleme kuralları.

---

## 📋 Sistem Gereksinimleri

- **PHP**: `8.1` veya üzeri (`8.2` / `8.3` / `8.4` tam uyumlu)
- **Veritabanı**: MySQL `5.7+` veya MariaDB `10.3+`
- **PHP Eklentileri**: `pdo_mysql`, `gd` (WebP desteğiyle), `mbstring`, `fileinfo`, `zip` (OTA güncelleme ve tema/eklenti ZIP yükleme için), `curl` veya `allow_url_fopen` (GitHub sürüm kontrolü için)
- **Web Sunucusu**: Apache (`mod_rewrite` aktif) veya LiteSpeed / Nginx

---

## 🚀 Kurulum

### A) Paylaşımlı Hosting (cPanel / Plesk / FTP) Kurulumu
1. Bu repoyu veya [Releases](https://github.com/fatihsinankara/bvlog/releases) sayfasındaki en güncel dağıtım paketini ZIP olarak indirin ve sunucunuzun `public_html` (veya alt klasör) dizinine yükleyin.
2. `config/`, `storage/`, `themes/` ve `plugins/` dizinlerinin yazılabilir (`755` veya `775`) olduğundan emin olun.
3. Tarayıcınızdan sitenizin adresine (`https://alanadiniz.com/install`) gidin.
4. **4 Adımlı Kurulum Sihirbazını** takip edin:
   - **Adım 1**: Sunucu ve PHP eklenti kontrolü
   - **Adım 2**: Veritabanı bağlantı bilgileri (`config/database.php` otomatik oluşturulur ve tablolar kurulur)
   - **Adım 3**: Site başlığı, çalışma modu ve yönetici hesabı oluşturma
   - **Adım 4**: Kurulum tamamlanır ve `storage/installed.lock` oluşturularak kurulum sihirbazı kilitlenir.

### B) Geliştirici Ortamı (CSS/JS Yeniden Derleme & Paketleme)
Ön yüz stil ve betiklerinde (`resources/css/app.css` veya `resources/js/app.js`) değişiklik yapmak isterseniz:

```bash
npm install
npm run dev          # Geliştirme modu
npm run build        # Üretim için /assets dizinine derler
php build-release.php # Dağıtıma hazır temiz ZIP paketi (/dist) oluşturur
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
├── core/                   # Çekirdek sınıflar (Router, Database, Cache, Updater, Migrator, Cron, Hook vb.)
├── plugins/                # Eklentiler (captcha, collections, smtp, table-of-contents vb.)
├── resources/              # Tailwind CSS ve Alpine.js kaynak dosyaları
├── storage/
│   ├── cache/              # L2 uygulama önbellek kayıtları (bvcache_*), cron durum ve sürüm kontrol dosyaları
│   ├── logs/               # Sistem, güncelleme, rate-limit ve hata günlükleri
│   └── uploads/            # Medya kütüphanesi görselleri (featured / content)
├── themes/                 # Temalar (default ve paper temaları)
├── .htaccess               # Güvenlik, önbellek ve rota yönlendirme kuralları
├── cron.php                # CLI zamanlanmış görev tetikleyicisi
├── index.php               # Tekil giriş noktası (Front Controller)
└── schema.sql              # Veritabanı başlangıç şeması
```

---

## 🧩 Eklenti & Tema Geliştirme

### Eklenti Oluşturma (`plugins/eklenti-adi/`)
Her eklenti klasöründe bir `plugin.json` bildirimi ve bir giriş dosyası (`plugin.php`) yer alır:

**`plugins/ornek-eklenti/plugin.json`**:
```json
{
  "id": "ornek-eklenti",
  "name": "Örnek Eklenti",
  "version": "1.0.0",
  "author": "Geliştirici",
  "description": "Yazı sonuna özel bilgi kutusu ekler.",
  "main": "plugin.php"
}
```

**`plugins/ornek-eklenti/plugin.php`**:
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
`themes/tema-adi/theme.json` dosyasını oluşturup yalnızca özelleştirmek istediğiniz şablonları (`views/layouts/main.php`, `views/home/index.php`, `views/blog/show.php` vb.) kendi tema klasörünüze eklemeniz yeterlidir. Bulunmayan şablonlar otomatik olarak varsayılan (`default`) temadan tamamlanır.

---

## 📄 Lisans

Bu proje **MIT Lisansı** altında tamamen ücretsiz ve açık kaynak olarak sunulmaktadır. Dilediğiniz gibi kullanabilir, değiştirebilir ve dağıtabilirsiniz.
