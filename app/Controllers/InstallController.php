<?php

namespace App\Controllers;

use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Security;
use Core\Session;
use Core\View;

class InstallController extends Controller
{
    private function guardInstalled(): void
    {
        if (is_installed()) {
            http_response_code(403);
            die('Kurulum zaten tamamlanmış. Yeniden kurulum için storage/installed.lock dosyasını silin.');
        }
    }

    public function index(): void
    {
        $this->guardInstalled();
        $step = (int) Request::get('step', 1);

        if ($step === 1) {
            $checks = $this->getSystemChecks();
            View::renderInstall('install/step1', ['checks' => $checks, 'step' => 1]);
            return;
        }

        if ($step === 2) {
            $dbConfig = Session::get('install_db', [
                'host'     => '127.0.0.1',
                'port'     => '3306',
                'database' => 'bvlog',
                'username' => 'root',
                'password' => '',
            ]);
            View::renderInstall('install/step2', ['db' => $dbConfig, 'step' => 2]);
            return;
        }

        if ($step === 3) {
            if (!Session::has('install_db')) {
                Response::redirect('/install?step=2');
            }
            View::renderInstall('install/step3', ['step' => 3]);
            return;
        }

        if ($step === 4) {
            View::renderInstall('install/step4', ['step' => 4]);
            return;
        }

        Response::redirect('/install?step=1');
    }

    public function saveDatabase(): void
    {
        $this->guardInstalled();

        $config = [
            'host'     => Request::post('db_host', '127.0.0.1'),
            'port'     => Request::post('db_port', '3306'),
            'database' => Request::post('db_name', 'bvlog'),
            'username' => Request::post('db_user', 'root'),
            'password' => Request::post('db_pass', ''),
            'charset'  => 'utf8mb4',
        ];

        try {
            // Veritabanı yoksa otomatik oluşturmayı dene
            $dsnNoDb = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
            $pdoRoot = new \PDO($dsnNoDb, $config['username'], $config['password'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $safeDbName = preg_replace('/[^a-zA-Z0-9_]/', '', $config['database']);
            $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `{$safeDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $config['database'] = $safeDbName;

            // Bağlantıyı doğrula
            Database::createConnection($config);
        } catch (\Throwable $e) {
            Session::flash('error', 'Veritabanı bağlantısı kurulamadı: ' . $e->getMessage());
            Session::set('install_db', $config);
            Response::redirect('/install?step=2');
        }

        Session::set('install_db', $config);
        Response::redirect('/install?step=3');
    }

    public function complete(): void
    {
        $this->guardInstalled();

        $dbConfig = Session::get('install_db');
        if (!$dbConfig) {
            Response::redirect('/install?step=2');
        }

        $siteTitle = Request::post('site_title', 'Bvlog');
        $siteTagline = Request::post('site_tagline', 'Yazılım, Mimari ve Kişisel Notlar');
        $adminName = Request::post('admin_name', 'Yönetici');
        $adminUsername = Request::post('admin_username', 'admin');
        $adminEmail = Request::post('admin_email', 'admin@bvlog.test');
        $adminPassword = Request::post('admin_password', '');
        $enablePortfolio = Request::post('module_portfolio') ? '1' : '0';
        $enableCv = Request::post('module_cv') ? '1' : '0';

        if (strlen($adminPassword) < 6) {
            Session::flash('error', 'Yönetici parolası en az 6 karakter olmalıdır.');
            Response::redirect('/install?step=3');
        }

        try {
            // 1. config/database.php dosyasını yaz
            $configPhp = "<?php\n\nreturn " . var_export($dbConfig, true) . ";\n";
            file_put_contents(BVLOG_ROOT . '/config/database.php', $configPhp);

            // 2. Şemayı çalıştır
            $pdo = Database::createConnection($dbConfig);
            $schemaSql = file_get_contents(BVLOG_ROOT . '/schema.sql');
            $pdo->exec($schemaSql);

            // 3. Yönetici hesabını ekle
            $stmtUser = $pdo->prepare('SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1');
            $stmtUser->execute(['u' => $adminUsername, 'e' => $adminEmail]);
            $existingUser = $stmtUser->fetch();

            $bioText = 'Modern web teknolojileri, sade sistem mimarileri ve yüksek performanslı yazılım çözümleri üzerine odaklanan yazılım geliştirici.';

            if ($existingUser) {
                $userId = (int) $existingUser['id'];
            } else {
                $stmtIns = $pdo->prepare(
                    'INSERT INTO users (name, username, email, password_hash, role_title, bio, long_bio) VALUES (:n, :u, :e, :p, :r, :b, :lb)'
                );
                $stmtIns->execute([
                    'n'  => $adminName,
                    'u'  => $adminUsername,
                    'e'  => $adminEmail,
                    'p'  => Security::hashPassword($adminPassword),
                    'r'  => 'Kıdemli Yazılım Geliştirici',
                    'b'  => $bioText,
                    'lb' => '<p>' . $bioText . '</p>',
                ]);
                $userId = (int) $pdo->lastInsertId();
            }

            // 4. Varsayılan Ayarları kaydet
            $defaultSections = [
                ['id' => 'hero', 'title' => 'Yazar Tanıtım Kartı (Hero)', 'enabled' => true],
                ['id' => 'featured_posts', 'title' => 'Öne Çıkan Yazılar', 'enabled' => true],
                ['id' => 'latest_posts', 'title' => 'Son Blog Yazıları', 'enabled' => true],
                ['id' => 'featured_projects', 'title' => 'Seçkin Portfolyo Projeleri', 'enabled' => true],
                ['id' => 'cv_skills_summary', 'title' => 'Yetenek & Deneyim Özeti', 'enabled' => true],
                ['id' => 'contact_cta', 'title' => 'İletişim Çağrı Kutusu', 'enabled' => true],
            ];

            $defaultSettings = [
                'site_title'          => $siteTitle,
                'site_tagline'        => $siteTagline,
                'site_description'    => $siteTitle . ' — ' . $siteTagline,
                'author_name'         => $adminName,
                'author_title'        => 'Kıdemli Yazılım Geliştirici',
                'author_bio'          => $bioText,
                'module_portfolio'    => $enablePortfolio,
                'module_cv'           => $enableCv,
                'module_comments'     => '1',
                'module_contact'      => '1',
                'comments_moderation' => '1',
                'posts_per_page'      => '10',
                'homepage_hero_style' => 'minimal_bio',
                'homepage_post_style' => 'list',
                'homepage_sections'   => json_encode($defaultSections, JSON_UNESCAPED_UNICODE),
                'active_theme'        => 'default',
                'active_plugins'      => json_encode(['reading-progress'], JSON_UNESCAPED_UNICODE),
                'quarantined_plugins' => json_encode([], JSON_UNESCAPED_UNICODE),
                'seo_allow_index'     => '1',
                'maintenance_mode'    => '0',
                'maintenance_message' => 'Sitemizde kısa süreli bakım çalışması yapılmaktadır. Lütfen daha sonra tekrar ziyaret edin.',
                'social_github'       => 'https://github.com',
                'social_linkedin'     => 'https://linkedin.com',
                'social_twitter'      => 'https://x.com',
                'social_email'        => $adminEmail,
            ];

            $stmtSet = $pdo->prepare('INSERT INTO settings (key_name, value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE value = :v2');
            foreach ($defaultSettings as $k => $v) {
                $stmtSet->execute(['k' => $k, 'v' => $v, 'v2' => $v]);
            }

            // 5. Örnek Kategoriler (İkon ve Düz Renklerle)
            $catCount = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
            if ($catCount === 0) {
                $sampleCats = [
                    ['Yazılım Mimarisi', 'yazilim-mimarisi', 'Temiz kod, tasarım desenleri ve sistem mimarisi üzerine yazılar.', 'fa-solid fa-cubes', '#2563eb'],
                    ['Siber Güvenlik', 'siber-guvenlik', 'Web uygulama güvenliği, kriptografi ve savunma yöntemleri.', 'fa-solid fa-shield-halved', '#16a34a'],
                    ['Performans & Web', 'performans-web', 'Modern frontend, veritabanı optimizasyonu ve sunucu performansı.', 'fa-solid fa-bolt', '#d97706'],
                    ['Kişisel Notlar', 'kisisel-notlar', 'Deneyimler, kitap incelemeleri ve teknoloji dünyasından düşünceler.', 'fa-solid fa-feather', '#52525b'],
                ];
                $stmtCat = $pdo->prepare('INSERT INTO categories (name, slug, description, icon, color) VALUES (?, ?, ?, ?, ?)');
                foreach ($sampleCats as $c) {
                    $stmtCat->execute($c);
                }
            }

            // 6. Örnek Blog Yazısı
            $postCount = (int) $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
            if ($postCount === 0) {
                $catId = (int) $pdo->query('SELECT id FROM categories ORDER BY id ASC LIMIT 1')->fetchColumn();
                $sampleContent = <<<HTML
<p>Merhaba! <strong>Bvlog</strong> kişisel blog, portfolyo ve özgeçmiş sistemine hoş geldiniz. Bu platform; ağır framework karmaşasına girmeden, standart paylaşımlı hostinglerde dahi maksimum hız ve güvenlikle çalışmak üzere sıfırdan inşa edildi.</p>
<h2>Neden Sade ve Bağımsız Mimari?</h2>
<p>Modern web dünyasında bazen basit bir blog için bile yüzlerce megabaytlık bağımlılık yükleniyor. Oysa odak noktamız içerik üretmek, bilgiyi en okunaklı tipografiyle sunmak ve güvenliği en üst seviyede tutmak olduğunda <strong>yalınlık</strong> en büyük güçtür.</p>
<blockquote>İyi tasarım, mümkün olduğunca az tasarımdır. Gereksiz süslemelerden ve göz yoran renk geçişlerinden arındırılmış metinler, okuyucuyu doğrudan düşünceyle buluşturur.</blockquote>
<h3>Bvlog'un Öne Çıkan Özellikleri</h3>
<ul>
    <li><strong>Zengin İçerik Editörü:</strong> Yazarken anında biçimlenen başlıklar, sürükle-bırak görsel yükleme ve akıllı medya yerleştirme desteği.</li>
    <li><strong>Güçlü SEO Altyapısı:</strong> Otomatik görsel optimizasyonu, arama motoru yapısal verileri ve dinamik site haritası.</li>
    <li><strong>Hata Korumalı Tema & Eklenti Sistemi:</strong> Hatalı bir eklenti yüklendiğinde sistemi çökertmeyen otomatik karantina mekanizması.</li>
    <li><strong>Modüler Portfolyo ve CV:</strong> İhtiyacınız olduğunda tek tıkla açıp kapatabileceğiniz proje vitrini ve yazdırılabilir özgeçmiş sayfası.</li>
</ul>
<p>Yönetim paneline giriş yaparak bu yazıyı düzenleyebilir, anasayfa yerleşimini değiştirebilir veya yeni içerikler üretmeye hemen başlayabilirsiniz.</p>
HTML;
                $stmtPost = $pdo->prepare(
                    'INSERT INTO posts (user_id, category_id, title, slug, summary, content, seo_title, seo_description, is_featured, status, published_at)
                     VALUES (:uid, :cid, :title, :slug, :sum, :content, :st, :sd, 1, "published", NOW())'
                );
                $stmtPost->execute([
                    'uid'     => $userId,
                    'cid'     => $catId,
                    'title'   => 'Bvlog ile Yalın, Güvenli ve Modern Web Yayıncılığına Merhaba',
                    'slug'    => 'bvlog-ile-yalin-guvenli-ve-modern-web-yayinciligina-merhaba',
                    'sum'     => 'Ağır framework bağımlılıkları olmadan, yüksek güvenlikli, hızlı ve tamamen okunabilirlik odaklı kişisel yayın sistemi.',
                    'content' => $sampleContent,
                    'st'      => 'Bvlog ile Yalın ve Güvenli Web Yayıncılığı',
                    'sd'      => 'Paylaşımlı hosting uyumlu, güvenli, hızlı ve minimalist kişisel blog, portfolyo ve CV sistemi.',
                ]);
            }

            // 7. Örnek Portfolyo Projesi
            $projCount = (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
            if ($projCount === 0) {
                $stmtProj = $pdo->prepare(
                    'INSERT INTO projects (title, slug, summary, description, demo_url, github_url, tech_stack, is_featured, display_order, status)
                     VALUES (:t, :s, :sum, :desc, :demo, :git, :tech, 1, 1, "published")'
                );
                $stmtProj->execute([
                    't'    => 'Bvlog Çekirdek İçerik ve Portfolyo Sistemi',
                    's'    => 'bvlog-cekirdek-icerik-ve-portfolyo-sistemi',
                    'sum'  => 'PHP 8+, MySQL, Tailwind CSS ve Alpine.js ile geliştirilen, sıfır harici framework bağımlılıklı, yüksek güvenlikli yayın platformu.',
                    'desc' => '<p>Paylaşımlı hosting ortamlarında sorunsuz çalışabilen, otomatik görsel optimizasyonuna, zengin metin editörüne ve hata toleranslı eklenti/tema mimarisine sahip açık mimarili kişisel web sistemi.</p>',
                    'demo' => base_url(),
                    'git'  => 'https://github.com',
                    'tech' => 'PHP 8, MySQL, Tailwind CSS, Alpine.js, Vite',
                ]);
            }

            // 8. Örnek CV Verileri
            $expCount = (int) $pdo->query('SELECT COUNT(*) FROM cv_experiences')->fetchColumn();
            if ($expCount === 0) {
                $pdo->exec("INSERT INTO cv_experiences (company, role, location, start_date, end_date, is_current, description, display_order) VALUES
                    ('Teknoloji Stüdyosu A.Ş.', 'Kıdemli Full-Stack Geliştirici', 'İstanbul / Uzaktan', '2023', NULL, 1, 'Yüksek trafikli web uygulamalarının mimari tasarımı, güvenlik denetimleri ve performans optimizasyonu süreçlerini yönettim.', 1),
                    ('Dijital Çözümler Ltd.', 'Backend Yazılım Uzmanı', 'Ankara', '2020', '2023', 0, 'PHP ve MySQL tabanlı kurumsal API servislerinin geliştirilmesi, veritabanı indeksleme ve önbellekleme mimarilerinin kurulumu.', 2)");

                $pdo->exec("INSERT INTO cv_educations (institution, degree, field_of_study, start_date, end_date, is_current, description, display_order) VALUES
                    ('Teknik Üniversite', 'Lisans Derecesi', 'Bilgisayar Mühendisliği', '2016', '2020', 0, 'Yazılım mühendisliği, veri yapıları, ağ güvenliği ve dağıtık sistemler üzerine onur derecesi ile mezuniyet.', 1)");

                $pdo->exec("INSERT INTO cv_skill_categories (id, name, display_order) VALUES
                    (1, 'Backend & Veritabanı', 1),
                    (2, 'Frontend & Arayüz', 2),
                    (3, 'Araçlar & DevOps', 3)");

                $pdo->exec("INSERT INTO cv_skills (category_id, category, name, proficiency, display_order) VALUES
                    (1, 'Backend & Veritabanı', 'PHP 8+ / OOP Mimari', 95, 1),
                    (1, 'Backend & Veritabanı', 'MySQL / MariaDB Optimizasyonu', 90, 2),
                    (1, 'Backend & Veritabanı', 'Uygulama Güvenliği (OWASP)', 90, 3),
                    (2, 'Frontend & Arayüz', 'Tailwind CSS', 95, 1),
                    (2, 'Frontend & Arayüz', 'Alpine.js & Modern JS', 85, 2),
                    (3, 'Araçlar & DevOps', 'Git, Linux & Sunucu Yönetimi', 85, 1)");
            }

            // 9. Kurulum Kilidi Oluştur
            $lockData = json_encode([
                'installed_at' => date('Y-m-d H:i:s'),
                'version'      => '1.0.0',
            ], JSON_PRETTY_PRINT);
            file_put_contents(BVLOG_ROOT . '/storage/installed.lock', $lockData);

            // Yöneticiyi otomatik oturum açtır
            Session::regenerate();
            Session::set('admin_user_id', $userId);
            Session::set('admin_user_name', $adminName);
            Session::remove('install_db');

            View::renderInstall('install/step4', ['step' => 4, 'adminEmail' => $adminEmail]);
        } catch (\Throwable $e) {
            Session::flash('error', 'Kurulum sırasında hata oluştu: ' . $e->getMessage());
            Response::redirect('/install?step=3');
        }
    }

    private function getSystemChecks(): array
    {
        return [
            [
                'label'  => 'PHP Sürümü (8.1 veya üzeri)',
                'detail' => 'Mevcut: PHP ' . PHP_VERSION,
                'passed' => version_compare(PHP_VERSION, '8.1.0', '>='),
            ],
            [
                'label'  => 'PDO MySQL Uzantısı',
                'detail' => 'Güvenli veritabanı bağlantısı için zorunlu',
                'passed' => extension_loaded('pdo_mysql'),
            ],
            [
                'label'  => 'GD Resim İşleme & WebP Desteği',
                'detail' => 'Otomatik 1080px boyutlandırma ve WebP sıkıştırma için',
                'passed' => extension_loaded('gd') && function_exists('imagewebp'),
            ],
            [
                'label'  => 'Mbstring Çoklu Bayt Dize Uzantısı',
                'detail' => 'Türkçe karakter ve UTF-8 işlemleri için',
                'passed' => extension_loaded('mbstring'),
            ],
            [
                'label'  => 'Fileinfo (MIME Türü Doğrulaması)',
                'detail' => 'Güvenli dosya yükleme kontrolü için',
                'passed' => extension_loaded('fileinfo'),
            ],
            [
                'label'  => 'config/ Klasörü Yazma İzni',
                'detail' => 'Veritabanı ayarlarının kaydedilmesi için',
                'passed' => is_writable(BVLOG_ROOT . '/config'),
            ],
            [
                'label'  => 'storage/ Klasörü Yazma İzni',
                'detail' => 'Medya yüklemeleri ve kurulum kilidi için',
                'passed' => is_writable(BVLOG_ROOT . '/storage'),
            ],
        ];
    }
}
