# Katkı Rehberi

Bvlog'a katkı vermek istediğiniz için teşekkürler. Bu belge geliştirme ortamını, kod ve commit kurallarını ve PR sürecini anlatır.

Güvenlik açıkları için issue açmayın; [SECURITY.md](SECURITY.md) dosyasındaki özel kanalı kullanın.

## Geliştirme ortamı

Gereksinimler:

- PHP 8.1 veya üzeri; `pdo_mysql`, `gd` (WebP desteğiyle), `mbstring`, `fileinfo`, `zip` ve `curl` eklentileri
- MySQL 5.7+ ya da MariaDB 10.3+
- Ön yüz değişiklikleri için Node.js 20 ve npm

Kurulum:

1. Depoyu klonlayın ve bir web sunucusunda (Apache, Nginx ya da LiteSpeed) yayınlayın.
2. Tarayıcıda `/install` adresine gidip kurulum sihirbazını tamamlayın.
3. Ön yüzde değişiklik yapacaksanız `npm ci` çalıştırın.

## Ön yüz dosyaları

Derlenmiş dosyalar (`assets/`) depoda tutulur; Node.js kurmadan paylaşımlı hostinge yüklenebilmesi için bu bilinçli bir tercih.

- `resources/` altındaki CSS ya da JS dosyalarını değiştirdiyseniz `npm run build` çalıştırın ve değişen `assets/` dosyalarını aynı PR'a ekleyin.
- Görünümlerde (`app/Views`, `themes/*/views`, `plugins/*/views`) yeni Tailwind sınıfları kullandıysanız da yeniden derleyin.
- `resources/` değişmediyse `assets/` dosyalarını PR'a eklemeyin; farklı ortamlarda yapılan derlemeler gereksiz farklar üretebilir.

## Testler ve statik analiz

`tests/` altındaki her `*_checks.php` dosyası bağımsız bir betiktir ve veritabanı gerektirmez:

```bash
for t in tests/*_checks.php; do php "$t"; done
```

Statik analiz için PHPStan (seviye 3) kullanılır:

```bash
phpstan analyse
# PHP kurulu değilse:
docker run --rm -v "$PWD":/app -w /app ghcr.io/phpstan/phpstan:2-php8.2 analyse
```

`phpstan-baseline.neon` bilinen hataları tutar. Yeni hata eklemeyin; bir hatayı düzelttiyseniz ilgili satırı baseline'dan silebilirsiniz.

Her PR'da GitHub Actions şunları çalıştırır: PHP 8.1–8.4 üzerinde sözdizimi kontrolü ve testler, PHPStan, `schema.sql` içe aktarma (MariaDB ve MySQL) ve ön yüz derlemesi.

Düzelttiğiniz hata ya da eklediğiniz davranış için `tests/` altına kontrol eklemeniz beklenir. Yeni bir alan için yeni bir `*_checks.php` dosyası açabilirsiniz; mevcut dosyalardaki `check()` yardımcısını örnek alın.

## Kod kuralları

- PHP kodu PSR-12'ye yakın biçimde yazılır: 4 boşluk girinti, sınıf başına bir dosya, `Core\` ve `App\` ad alanları dizin yapısını izler.
- Görünümlerde kullanıcıdan gelen her değeri `e()` ile kaçırın.
- Veritabanı sorgularında değerleri her zaman parametre olarak verin (`Database::fetchAll($sql, $params)`); SQL'e değişken birleştirmeyin.
- Yeni bir POST rotası CSRF korumasını `Core\Router` üzerinden otomatik alır; formlara `<?= csrf_field() ?>` ekleyin.
- Arayüz metinleri Türkçedir.

## Commit mesajları

[Conventional Commits](https://www.conventionalcommits.org/) biçimi kullanılır; açıklama Türkçe ve yalnızca ASCII karakterlerle yazılır:

```
fix(newsletter): abonelikten cikma baglantisini duzelt
feat(collections): seri sayfasina siralama ekle
perf(cache): yorumda yalnizca ilgili yazinin onbellegini sil
```

Sık kullanılan türler: `feat`, `fix`, `perf`, `refactor`, `docs`, `test`, `chore`.

## Pull request süreci

1. Kendi fork'unuzda `main` dalından yeni bir dal açın (`fix/...`, `feat/...` gibi).
2. Her PR tek bir konuya odaklansın; ilgisiz değişiklikleri ayrı PR'lara bölün.
3. Testlerin ve PHPStan'in geçtiğinden emin olun.
4. PR açıklamasında neyi neden değiştirdiğinizi ve nasıl test ettiğinizi yazın.
5. Görsel değişikliklerde önce/sonra ekran görüntüsü ekleyin.

Büyük bir özellik ya da mimari değişiklik planlıyorsanız önce bir issue açıp yaklaşımı tartışın.
