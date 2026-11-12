# Güvenlik Politikası

## Desteklenen sürümler

Güvenlik düzeltmeleri yalnızca en son yayınlanan sürüme yapılır. Eski bir sürüm kullanıyorsanız önce yönetim panelindeki güncelleme aracıyla ya da [Releases](https://github.com/fatihsinankara/bvlog/releases) sayfasından en son sürüme geçin.

| Sürüm | Destek |
|---|---|
| En son sürüm (1.3.x) | Evet |
| Daha eski sürümler | Hayır |

## Açık bildirme

Güvenlik açıklarını **herkese açık issue, tartışma ya da PR olarak paylaşmayın.**

Bildirimi GitHub'ın özel bildirim kanalından yapın: depo sayfasında **Security → Report a vulnerability** ([doğrudan bağlantı](https://github.com/fatihsinankara/bvlog/security/advisories/new)). Bildirim yalnızca depo yöneticilerine görünür.

Bildiriminize şunları ekleyin:

- Etkilenen sürüm ve bileşen (çekirdek, tema ya da eklenti adı)
- Açığın türü ve etkisi
- Yeniden üretme adımları ya da kavram kanıtı
- Varsa önerdiğiniz düzeltme

## Süreç

1. Bildirim alındığında doğrulanır ve önem derecesi belirlenir.
2. Düzeltme özel olarak hazırlanır ve yeni bir sürümle yayınlanır.
3. Düzeltme yayınlandıktan sonra açık, sizin onayınızla adınız anılarak duyurulur.

Düzeltme yayınlanana kadar açığın ayrıntılarını paylaşmamanızı rica ederiz.

## Kapsam dışı

- Sunucu, PHP ya da veritabanı yazılımının kendisindeki açıklar. Bunlar için sağlayıcınızın ya da ilgili projenin güvenlik kanalını kullanın.
- Yönetici yetkisi gerektiren işlemler (tema ya da eklenti yükleme gibi). Yönetici hesabı zaten siteye tam erişime sahiptir.
- Hız sınırı olmayan uç noktalara yönelik hacimli istekler (DoS).

## Kurulum önerileri

- PHP'yi güvenlik yamalarını alan bir sürümde ve en son yama seviyesinde tutun.
- Nginx kullanıyorsanız `nginx.conf.example` içindeki erişim kurallarını sunucu bloğunuza ekleyin; `.htaccess` Nginx'te çalışmaz.
- `config/`, `storage/logs/` ve `storage/cache/` dizinlerinin web'den erişilemediğini kontrol edin.
