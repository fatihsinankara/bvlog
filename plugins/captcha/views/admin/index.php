<?php
$provider   = $config['provider'] ?? 'off';
$cfSiteKey  = $config['cf_site_key'] ?? '';
$cfSecretKey = $config['cf_secret_key'] ?? '';
$rcSiteKey  = $config['rc_site_key'] ?? '';
$rcSecretKey = $config['rc_secret_key'] ?? '';
$forms      = $config['forms'] ?? ['login', 'contact', 'comment', 'forgot_password'];
$theme      = $config['theme'] ?? 'auto';
?>

<div class="space-y-6 max-w-4xl" x-data="{
    provider: '<?= e($provider) ?>',
    testing: false,
    testResult: null,
    testError: null,
    testLatency: null,
    async runTest() {
        this.testing = true;
        this.testResult = null;
        this.testError = null;

        var token = '';
        if (this.provider === 'cloudflare') {
            var cfInput = document.querySelector('input[name=\'cf-turnstile-response\']');
            token = cfInput ? cfInput.value : '';
        } else if (this.provider === 'recaptcha') {
            var rcInput = document.getElementById('g-recaptcha-response');
            token = rcInput ? rcInput.value : '';
        }

        if (!token) {
            this.testing = false;
            this.testResult = false;
            this.testError = 'Lütfen aşağıdaki test kutucuğunu onaylayıp (işaretleyip) ardından teste basın.';
            return;
        }

        try {
            var formData = new FormData();
            formData.append('_csrf_token', '<?= e(\Core\Csrf::token()) ?>');
            formData.append('provider', this.provider);
            formData.append('token', token);

            var res = await fetch('<?= e(url('/admin/captcha/test')) ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            var data = await res.json();
            this.testing = false;
            this.testResult = data.success === true;
            this.testLatency = data.latency_ms || null;
            if (!data.success) {
                this.testError = data.error || 'Doğrulama başarısız.';
            }
        } catch (err) {
            this.testing = false;
            this.testResult = false;
            this.testError = 'Sunucuyla iletişim kurulurken hata oluştu: ' + err.message;
        }
    }
}">
    <!-- Başlık Çubuğu -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-5">
        <div>
            <h1 class="text-xl font-bold tracking-tight">CAPTCHA Güvenlik Koruması</h1>
            <p class="text-xs text-zinc-500 mt-1">Formlarınızı botlara, kaba kuvvet (brute-force) ve spam saldırılarına karşı koruyun.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold <?= $provider !== 'off' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' ?>">
                <span class="w-2 h-2 rounded-full <?= $provider !== 'off' ? 'bg-emerald-500' : 'bg-zinc-400' ?>"></span>
                <span><?= $provider === 'cloudflare' ? 'Cloudflare Turnstile Aktif' : ($provider === 'recaptcha' ? 'Google reCAPTCHA Aktif' : 'Devre Dışı') ?></span>
            </span>
        </div>
    </div>

    <!-- Yapılandırma Formu -->
    <form action="<?= e(url('/admin/captcha/save')) ?>" method="POST" class="space-y-6">
        <?= csrf_field() ?>

        <!-- 1. Sağlayıcı Seçimi -->
        <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4">
            <div class="space-y-1">
                <h2 class="text-sm font-bold tracking-tight">1. Doğrulama Sağlayıcısı</h2>
                <p class="text-xs text-zinc-500">Sitenizde kullanmak istediğiniz CAPTCHA servisini seçin.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                <!-- Kapalı -->
                <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all"
                       :class="provider === 'off' ? 'border-zinc-900 dark:border-zinc-100 bg-zinc-50 dark:bg-zinc-800/40 ring-1 ring-zinc-900 dark:ring-zinc-100' : 'border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700'">
                    <input type="radio" name="captcha_provider" value="off" x-model="provider" class="sr-only">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-ban text-zinc-400 text-base"></i>
                        <span class="w-4 h-4 rounded-full border border-zinc-300 dark:border-zinc-600 flex items-center justify-center"
                              :class="provider === 'off' ? 'border-zinc-900 dark:border-zinc-100' : ''">
                            <span class="w-2 h-2 rounded-full bg-zinc-900 dark:bg-zinc-100" x-show="provider === 'off'"></span>
                        </span>
                    </div>
                    <span class="text-sm font-bold">Kapalı</span>
                    <span class="text-xs text-zinc-500 mt-1">CAPTCHA koruması uygulanmaz.</span>
                </label>

                <!-- Cloudflare Turnstile -->
                <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all"
                       :class="provider === 'cloudflare' ? 'border-amber-500 bg-amber-50/40 dark:bg-amber-950/20 ring-1 ring-amber-500' : 'border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700'">
                    <input type="radio" name="captcha_provider" value="cloudflare" x-model="provider" class="sr-only">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-brands fa-cloudflare text-amber-500 text-xl"></i>
                        <span class="w-4 h-4 rounded-full border border-zinc-300 dark:border-zinc-600 flex items-center justify-center"
                              :class="provider === 'cloudflare' ? 'border-amber-500' : ''">
                            <span class="w-2 h-2 rounded-full bg-amber-500" x-show="provider === 'cloudflare'"></span>
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold">Cloudflare Turnstile</span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-500 text-white">Önerilen</span>
                    </div>
                    <span class="text-xs text-zinc-500 mt-1">Zahmetsiz, gizlilik dostu ve kullanıcıyı yormayan akıllı doğrulama.</span>
                </label>

                <!-- Google reCAPTCHA v2 -->
                <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all"
                       :class="provider === 'recaptcha' ? 'border-blue-500 bg-blue-50/40 dark:bg-blue-950/20 ring-1 ring-blue-500' : 'border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700'">
                    <input type="radio" name="captcha_provider" value="recaptcha" x-model="provider" class="sr-only">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-brands fa-google text-blue-500 text-base"></i>
                        <span class="w-4 h-4 rounded-full border border-zinc-300 dark:border-zinc-600 flex items-center justify-center"
                              :class="provider === 'recaptcha' ? 'border-blue-500' : ''">
                            <span class="w-2 h-2 rounded-full bg-blue-500" x-show="provider === 'recaptcha'"></span>
                        </span>
                    </div>
                    <span class="text-sm font-bold">Google reCAPTCHA</span>
                    <span class="text-xs text-zinc-500 mt-1">Klasik "Ben robot değilim" onay kutulu doğrulama.</span>
                </label>
            </div>
        </div>

        <!-- 2. API Anahtarları -->
        <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5"
             x-show="provider !== 'off'" style="display: none;">
            <div class="space-y-1">
                <h2 class="text-sm font-bold tracking-tight">2. API Anahtarları</h2>
                <p class="text-xs text-zinc-500">Seçtiğiniz sağlayıcının konsolundan aldığınız anahtarları girin.</p>
            </div>

            <!-- Cloudflare Turnstile Alanları -->
            <div x-show="provider === 'cloudflare'" class="space-y-4">
                <div class="p-3.5 rounded-lg border border-amber-200 dark:border-amber-900/60 bg-amber-50/50 dark:bg-amber-950/30 text-xs text-amber-800 dark:text-amber-300 space-y-1">
                    <div class="font-bold flex items-center gap-1.5">
                        <i class="fa-brands fa-cloudflare"></i>
                        <span>Cloudflare Turnstile Nasıl Kurulur?</span>
                    </div>
                    <p class="leading-relaxed">
                        <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" class="underline font-semibold hover:opacity-80">Cloudflare Dashboard &rarr; Turnstile</a>
                        bölümünden sitenizi ekleyin. Widget modu olarak <strong>Managed</strong> (önerilen) seçip üretilen <em>Site Key</em> ve <em>Secret Key</em> değerlerini aşağıdaki alanlara yapıştırın.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Turnstile Site Key (Genel Anahtar)</label>
                        <input type="text" name="captcha_cf_site_key" value="<?= e($cfSiteKey) ?>" placeholder="0x4AAAAAA..."
                               class="w-full px-3 py-2 text-sm font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 focus:outline-none focus:border-zinc-900">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Turnstile Secret Key (Gizli Anahtar)</label>
                        <input type="password" name="captcha_cf_secret_key" placeholder="<?= !empty($cfSecretKey) ? '••••••••••••••••••••••••••••' : '0x4AAAAAA...' ?>"
                               class="w-full px-3 py-2 text-sm font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 focus:outline-none focus:border-zinc-900">
                        <span class="inline-block text-[11px] text-zinc-400 mt-1"><?= !empty($cfSecretKey) ? 'Kayıtlı gizli anahtar mevcut. Değiştirmek istemiyorsanız boş bırakın.' : 'Doğrulama için gereklidir.' ?></span>
                    </div>
                </div>
            </div>

            <!-- Google reCAPTCHA Alanları -->
            <div x-show="provider === 'recaptcha'" class="space-y-4" style="display: none;">
                <div class="p-3.5 rounded-lg border border-blue-200 dark:border-blue-900/60 bg-blue-50/50 dark:bg-blue-950/30 text-xs text-blue-800 dark:text-blue-300 space-y-1">
                    <div class="font-bold flex items-center gap-1.5">
                        <i class="fa-brands fa-google"></i>
                        <span>Google reCAPTCHA v2 Nasıl Kurulur?</span>
                    </div>
                    <p class="leading-relaxed">
                        <a href="https://www.google.com/recaptcha/admin" target="_blank" class="underline font-semibold hover:opacity-80">Google reCAPTCHA Admin Console</a>
                        üzerinden yeni bir site ekleyin, tür olarak <strong>reCAPTCHA v2 -> "Ben robot değilim" Onay Kutusu</strong> seçin ve aldığınız anahtarları buraya girin.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold mb-1.5">reCAPTCHA v2 Site Key</label>
                        <input type="text" name="captcha_rc_site_key" value="<?= e($rcSiteKey) ?>" placeholder="6Ld..."
                               class="w-full px-3 py-2 text-sm font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 focus:outline-none focus:border-zinc-900">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5">reCAPTCHA v2 Secret Key</label>
                        <input type="password" name="captcha_rc_secret_key" placeholder="<?= !empty($rcSecretKey) ? '••••••••••••••••••••••••••••' : '6Ld...' ?>"
                               class="w-full px-3 py-2 text-sm font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 focus:outline-none focus:border-zinc-900">
                        <span class="inline-block text-[11px] text-zinc-400 mt-1"><?= !empty($rcSecretKey) ? 'Kayıtlı gizli anahtar mevcut. Değiştirmek istemiyorsanız boş bırakın.' : 'Doğrulama için gereklidir.' ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Hangi Formlarda Aktif Edilsin? -->
        <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4"
             x-show="provider !== 'off'" style="display: none;">
            <div class="space-y-1">
                <h2 class="text-sm font-bold tracking-tight">3. Korunacak Formlar</h2>
                <p class="text-xs text-zinc-500">CAPTCHA doğrulamasının gösterileceği ve zorunlu tutulacağı formları seçin.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                <!-- Yönetici Girişi -->
                <label class="flex items-start gap-3 p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 cursor-pointer">
                    <input type="checkbox" name="captcha_forms[]" value="login" <?= in_array('login', $forms, true) ? 'checked' : '' ?>
                           class="mt-0.5 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-700">
                    <div>
                        <span class="text-xs font-bold block">Yönetici Giriş Formu</span>
                        <span class="text-[11px] text-zinc-500">/admin/login — Şifre tahmin ve brute-force saldırılarını engeller.</span>
                    </div>
                </label>

                <!-- Parola Sıfırlama -->
                <label class="flex items-start gap-3 p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 cursor-pointer">
                    <input type="checkbox" name="captcha_forms[]" value="forgot_password" <?= in_array('forgot_password', $forms, true) ? 'checked' : '' ?>
                           class="mt-0.5 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-700">
                    <div>
                        <span class="text-xs font-bold block">Parolamı Unuttum Formu</span>
                        <span class="text-[11px] text-zinc-500">/admin/forgot-password — Sahte e-posta gönderimlerini durdurur.</span>
                    </div>
                </label>

                <!-- İletişim Formu -->
                <label class="flex items-start gap-3 p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 cursor-pointer">
                    <input type="checkbox" name="captcha_forms[]" value="contact" <?= in_array('contact', $forms, true) ? 'checked' : '' ?>
                           class="mt-0.5 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-700">
                    <div>
                        <span class="text-xs font-bold block">İletişim Formu</span>
                        <span class="text-[11px] text-zinc-500">/contact — Botların gelen kutunuza spam mesaj yığmasını engeller.</span>
                    </div>
                </label>

                <!-- Blog Yorum Formu -->
                <label class="flex items-start gap-3 p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 cursor-pointer">
                    <input type="checkbox" name="captcha_forms[]" value="comment" <?= in_array('comment', $forms, true) ? 'checked' : '' ?>
                           class="mt-0.5 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-700">
                    <div>
                        <span class="text-xs font-bold block">Blog Yorum Formu</span>
                        <span class="text-[11px] text-zinc-500">/blog/{slug} — Makalelere otomatik link ve reklam basan botları önler.</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- 4. Tema Seçimi -->
        <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4"
             x-show="provider !== 'off'" style="display: none;">
            <div class="space-y-1">
                <h2 class="text-sm font-bold tracking-tight">4. Görünüm & Tema</h2>
                <p class="text-xs text-zinc-500">Kutucuğun açık/koyu renk temasını belirleyin.</p>
            </div>

            <div class="max-w-xs">
                <select name="captcha_theme" class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 focus:outline-none focus:border-zinc-900">
                    <option value="auto" <?= $theme === 'auto' ? 'selected' : '' ?>>Otomatik (Sitenin Temasına Göre)</option>
                    <option value="light" <?= $theme === 'light' ? 'selected' : '' ?>>Açık Tema (Light)</option>
                    <option value="dark" <?= $theme === 'dark' ? 'selected' : '' ?>>Koyu Tema (Dark)</option>
                </select>
            </div>
        </div>

        <!-- Kaydet Butonu -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 transition-opacity flex items-center gap-2">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Ayarları Kaydet</span>
            </button>
        </div>
    </form>

    <!-- 5. Canlı Test Aracı (Interactive Live Test Card) -->
    <?php if ($provider !== 'off' && (($provider === 'cloudflare' && !empty($cfSiteKey)) || ($provider === 'recaptcha' && !empty($rcSiteKey)))): ?>
        <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-vial-circle-check text-emerald-500"></i>
                        <h2 class="text-sm font-bold tracking-tight">Canlı Doğrulama Test Aracı</h2>
                    </div>
                    <p class="text-xs text-zinc-500">
                        Aşağıdaki gerçek doğrulama kutusunu onaylayıp <strong>"Doğrulamayı Canlı Test Et"</strong> butonuna basarak anahtarlarınızın çalıştığını ve sunucu iletişimini test edebilirsiniz.
                    </p>
                </div>
            </div>

            <div class="p-4 rounded-xl border border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40 space-y-4">
                <!-- Gerçek Doğrulama Widget'ı -->
                <div class="flex flex-col items-start gap-3">
                    <?php if ($provider === 'cloudflare'): ?>
                        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                        <div class="cf-turnstile" data-sitekey="<?= e($cfSiteKey) ?>" data-theme="<?= e($theme) ?>"></div>
                    <?php elseif ($provider === 'recaptcha'): ?>
                        <script src="https://www.google.com/recaptcha/api.js?hl=tr" async defer></script>
                        <div class="g-recaptcha" data-sitekey="<?= e($rcSiteKey) ?>" data-theme="<?= $theme === 'dark' ? 'dark' : 'light' ?>"></div>
                    <?php endif; ?>

                    <button type="button" @click="runTest()" :disabled="testing"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 disabled:opacity-50 transition-colors shadow-sm">
                        <i :class="testing ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-bolt'"></i>
                        <span x-text="testing ? 'Sunucu Doğruluyor...' : 'Doğrulamayı Canlı Test Et'"></span>
                    </button>
                </div>

                <!-- Test Sonuç Konsolu -->
                <template x-if="testResult !== null">
                    <div class="p-4 rounded-lg border text-xs space-y-1.5 transition-all"
                         :class="testResult ? 'border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200' : 'border-rose-300 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/40 text-rose-900 dark:text-rose-200'">
                        <div class="flex items-center gap-2 font-bold text-sm">
                            <i :class="testResult ? 'fa-solid fa-circle-check text-emerald-600' : 'fa-solid fa-circle-xmark text-rose-600'"></i>
                            <span x-text="testResult ? 'Tebrikler! CAPTCHA Doğrulaması Başarılı' : 'Doğrulama Başarısız'"></span>
                        </div>
                        <template x-if="testResult">
                            <div class="space-y-0.5 text-xs text-emerald-800 dark:text-emerald-300">
                                <div>Sağlayıcı: <strong class="uppercase" x-text="provider"></strong></div>
                                <div x-show="testLatency">Yanıt Süresi: <strong x-text="testLatency + ' ms'"></strong></div>
                                <div>Durum: Anahtarlarınız doğru, API ile tam uyumlu çalışıyor ve formlarınız koruma altında.</div>
                            </div>
                        </template>
                        <template x-if="!testResult">
                            <div class="space-y-0.5 text-xs text-rose-800 dark:text-rose-300">
                                <div x-text="testError"></div>
                                <div class="text-[11px] opacity-80 pt-1">İpucu: Site Key ve Secret Key değerlerinin aynı sağlayıcıya ait ve doğru alanlara girildiğinden emin olun.</div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    <?php endif; ?>
</div>
