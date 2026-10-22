<?php
/**
 * @var array $config
 * @var string $pageTitle
 */

$csrfToken = \Core\Csrf::token();
$defaultTestEmail = $config['admin_email'] ?: ($config['from_email'] ?: setting('social_email', ''));
?>

<div class="space-y-6" x-data="{
    enabled: <?= $config['enabled'] ? 'true' : 'false' ?>,
    host: <?= e(json_encode($config['host'])) ?>,
    port: <?= (int) $config['port'] ?>,
    encryption: <?= e(json_encode($config['encryption'])) ?>,
    username: <?= e(json_encode($config['username'])) ?>,
    fromEmail: <?= e(json_encode($config['from_email'])) ?>,
    fromName: <?= e(json_encode($config['from_name'])) ?>,
    showPassword: false,
    
    // Test Durumu
    testEmail: <?= e(json_encode($defaultTestEmail)) ?>,
    testing: false,
    testResult: null,
    showLog: false,

    applyPreset(name) {
        if (name === 'gmail') {
            this.host = 'smtp.gmail.com';
            this.port = 587;
            this.encryption = 'tls';
        } else if (name === 'yandex') {
            this.host = 'smtp.yandex.com';
            this.port = 465;
            this.encryption = 'ssl';
        } else if (name === 'outlook') {
            this.host = 'smtp.office365.com';
            this.port = 587;
            this.encryption = 'tls';
        } else if (name === 'cpanel') {
            const domain = window.location.hostname.replace('www.', '');
            this.host = 'mail.' + domain;
            this.port = 465;
            this.encryption = 'ssl';
        } else if (name === 'mailtrap') {
            this.host = 'sandbox.smtp.mailtrap.io';
            this.port = 2525;
            this.encryption = 'tls';
        }
    },

    runTest() {
        if (!this.testEmail) {
            alert('Lütfen test e-postasının gönderileceği adresi girin.');
            return;
        }

        this.testing = true;
        this.testResult = null;
        this.showLog = false;

        const formData = new FormData(document.getElementById('smtp-settings-form'));
        formData.append('test_email', this.testEmail);

        fetch('<?= e(url('/admin/smtp/test')) ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            this.testResult = data;
            this.testing = false;
            if (!data.success) {
                this.showLog = true; // Hata durumunda logu otomatik aç
            }
        })
        .catch(err => {
            this.testing = false;
            this.testResult = {
                success: false,
                message: 'İstek gönderilirken ağ hatası oluştu: ' + err.message,
                log: []
            };
            this.showLog = true;
        });
    }
}">

    <!-- Üst Başlık & Açıklama -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <i class="fa-solid fa-envelope-open-text text-blue-500"></i>
                <span>SMTP E-posta Yapılandırması & Test</span>
            </h2>
            <p class="text-xs text-zinc-500 mt-1">
                Sitenizin giden e-postalarını özel bir SMTP sunucusu (Gmail, Yandex, cPanel vb.) ile güvenle gönderin.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold"
                  :class="enabled ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500 border border-zinc-200 dark:border-zinc-700'">
                <span class="w-2 h-2 rounded-full" :class="enabled ? 'bg-emerald-500 animate-pulse' : 'bg-zinc-400'"></span>
                <span x-text="enabled ? 'SMTP Aktif' : 'SMTP Devre Dışı (Yerel PHP mail)'"></span>
            </span>
        </div>
    </div>

    <!-- Hızlı Sağlayıcı Ön Ayarları -->
    <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-2.5 shadow-sm">
        <span class="text-xs font-bold uppercase tracking-wider text-zinc-400 flex items-center gap-1.5">
            <i class="fa-solid fa-bolt text-amber-500"></i>
            <span>Tek Tıkla Sağlayıcı Şablonu Yükle</span>
        </span>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="applyPreset('gmail')" class="px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-xs font-semibold inline-flex items-center gap-1.5 transition-colors">
                <i class="fa-brands fa-google text-red-500"></i>
                <span>Gmail / Workspace</span>
            </button>
            <button type="button" @click="applyPreset('yandex')" class="px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-xs font-semibold inline-flex items-center gap-1.5 transition-colors">
                <i class="fa-brands fa-yandex text-red-600"></i>
                <span>Yandex Mail</span>
            </button>
            <button type="button" @click="applyPreset('outlook')" class="px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-xs font-semibold inline-flex items-center gap-1.5 transition-colors">
                <i class="fa-brands fa-microsoft text-blue-500"></i>
                <span>Outlook / Office 365</span>
            </button>
            <button type="button" @click="applyPreset('cpanel')" class="px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-xs font-semibold inline-flex items-center gap-1.5 transition-colors">
                <i class="fa-solid fa-server text-emerald-500"></i>
                <span>cPanel / Standart Hosting</span>
            </button>
            <button type="button" @click="applyPreset('mailtrap')" class="px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-xs font-semibold inline-flex items-center gap-1.5 transition-colors">
                <i class="fa-solid fa-flask text-purple-500"></i>
                <span>Mailtrap Test</span>
            </button>
        </div>
    </div>

    <!-- 2 Sütunlu Ana Alan: Ayarlar ve Canlı Test Paneli -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Sol Sütun: Ayar Formu (7 Kolon) -->
        <form id="smtp-settings-form" action="<?= e(url('/admin/smtp/save')) ?>" method="POST"
              class="lg:col-span-7 p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5 shadow-sm">
            <?= csrf_field() ?>

            <!-- Aktif / Pasif Anahtarı -->
            <label class="flex items-center justify-between gap-3 p-3.5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/70 dark:bg-zinc-950/40 cursor-pointer hover:bg-zinc-100/60 dark:hover:bg-zinc-800/40 transition-colors">
                <div class="text-xs font-medium">
                    <span class="block font-bold text-zinc-900 dark:text-zinc-100 text-sm">SMTP İle Gönderimi Etkinleştir</span>
                    <span class="text-zinc-500 text-[11px]">Kapalıyken sistem standart PHP mail() fonksiyonunu kullanır.</span>
                </div>
                <input type="checkbox" name="smtp_enabled" value="1" role="switch" x-model="enabled" class="ios-switch shrink-0">
            </label>

            <!-- Sunucu & Port & Şifreleme -->
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3.5">
                <div class="sm:col-span-6">
                    <label class="block text-xs font-semibold mb-1.5">SMTP Sunucu Adresi (Host) *</label>
                    <input type="text" name="smtp_host" x-model="host" required placeholder="smtp.gmail.com"
                           class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold mb-1.5">Port *</label>
                    <input type="number" name="smtp_port" x-model="port" required placeholder="587" min="1" max="65535"
                           class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold mb-1.5">Şifreleme</label>
                    <select name="smtp_encryption" x-model="encryption"
                            class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none">
                        <option value="tls">TLS (STARTTLS)</option>
                        <option value="ssl">SSL</option>
                        <option value="none">Yok (Düz Metin)</option>
                    </select>
                </div>
            </div>

            <!-- Kullanıcı Adı & Şifre -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-semibold mb-1.5">Kullanıcı Adı / E-posta *</label>
                    <input type="text" name="smtp_username" x-model="username" placeholder="ornek@alanadiniz.com"
                           class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1.5">SMTP Şifresi / Uygulama Şifresi</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" name="smtp_password"
                               placeholder="<?= !empty($config['password']) ? '•••••••••••• (Korumalı)' : 'Şifrenizi girin...' ?>"
                               class="w-full px-3 py-2 pr-9 text-xs font-mono rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
                        <button type="button" @click="showPassword = !showPassword"
                                class="absolute right-2.5 top-2.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 text-xs">
                            <i :class="showPassword ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
                        </button>
                    </div>
                    <p class="text-[10px] text-zinc-500 mt-1">Gmail kullanıyorsanız 2 adımlı doğrulamadan sonra "Uygulama Şifresi" üretmelisiniz.</p>
                </div>
            </div>

            <!-- Gönderen Bilgileri -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-2 border-t border-zinc-100 dark:border-zinc-800/80">
                <div>
                    <label class="block text-xs font-semibold mb-1.5">Gönderen E-posta Adresi (From Email)</label>
                    <input type="email" name="smtp_from_email" x-model="fromEmail" placeholder="info@alanadiniz.com"
                           class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1.5">Gönderen Adı (From Name)</label>
                    <input type="text" name="smtp_from_name" x-model="fromName" placeholder="Bvlog"
                           class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
                </div>
            </div>

            <!-- Bildirim Ayarları -->
            <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/30 space-y-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="smtp_notify_contact" value="1" <?= $config['notify_contact'] ? 'checked' : '' ?>
                           class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">İletişim Formu Mesajlarını Yöneticiye İlet</span>
                </label>

                <div>
                    <label class="block text-[11px] font-semibold text-zinc-500 mb-1">Bildirim Gönderilecek Yönetici E-postası</label>
                    <input type="email" name="smtp_admin_email" value="<?= e($config['admin_email']) ?>" placeholder="admin@alanadiniz.com"
                           class="w-full px-3 py-1.5 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 focus:outline-none">
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 transition-opacity flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Ayarları Kaydet</span>
                </button>
            </div>
        </form>

        <!-- Sağ Sütun: Canlı Test & Tanılama Paneli (5 Kolon) -->
        <div class="lg:col-span-5 space-y-6">

            <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400 flex items-center gap-1.5">
                        <i class="fa-solid fa-vial-circle-check text-emerald-500"></i>
                        <span>Canlı Bağlantı & E-posta Testi</span>
                    </h3>
                </div>

                <p class="text-xs text-zinc-500 leading-relaxed">
                    Girilen SMTP yapılandırmasını test etmek için alttaki adrese anında bir deneme e-postası gönderin.
                </p>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1">Test Alıcı E-postası</label>
                        <input type="email" x-model="testEmail" placeholder="test@alanadiniz.com"
                               class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
                    </div>

                    <button type="button" @click="runTest()" :disabled="testing"
                            class="w-full py-2.5 px-4 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold flex items-center justify-center gap-2 transition-colors disabled:opacity-50">
                        <template x-if="testing">
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch fa-spin"></i>
                                <span>SMTP Sunucusuna Bağlanılıyor...</span>
                            </span>
                        </template>
                        <template x-if="!testing">
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-paper-plane"></i>
                                <span>Bağlantıyı Sına & Test E-postası Gönder</span>
                            </span>
                        </template>
                    </button>
                </div>

                <!-- Test Sonucu Bildirimi -->
                <template x-if="testResult">
                    <div class="pt-2 space-y-3">
                        <div class="p-4 rounded-xl border text-xs space-y-1.5"
                             :class="testResult.success ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200' : 'bg-red-50 dark:bg-red-950/40 border-red-300 dark:border-red-800 text-red-900 dark:text-red-200'">
                            
                            <div class="flex items-center justify-between font-bold text-sm">
                                <span class="flex items-center gap-1.5">
                                    <i :class="testResult.success ? 'fa-solid fa-circle-check text-emerald-600 dark:text-emerald-400' : 'fa-solid fa-circle-xmark text-red-600 dark:text-red-400'"></i>
                                    <span x-text="testResult.success ? 'Test Başarılı!' : 'Bağlantı Hatası!'"></span>
                                </span>
                                <template x-if="testResult.duration_ms">
                                    <span class="text-[11px] font-mono opacity-80" x-text="testResult.duration_ms + ' ms'"></span>
                                </template>
                            </div>

                            <p class="text-xs leading-relaxed" x-text="testResult.message"></p>
                        </div>

                        <!-- Log Konsolu Göster / Gizle Butonu -->
                        <div class="flex items-center justify-between pt-1">
                            <button type="button" @click="showLog = !showLog" 
                                    class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 inline-flex items-center gap-1">
                                <i class="fa-solid fa-terminal text-[11px]"></i>
                                <span x-text="showLog ? 'İletişim Günlüğünü Gizle' : 'SMTP İletişim Günlüğünü Göster (Debug Log)'"></span>
                                <i class="fa-solid" :class="showLog ? 'fa-chevron-up text-[10px]' : 'fa-chevron-down text-[10px]'"></i>
                            </button>
                        </div>

                        <!-- Canlı SMTP İletişim Günlüğü (Console Transcript) -->
                        <div x-show="showLog" x-transition class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-950 text-zinc-300 p-3 font-mono text-[11px] max-h-72 overflow-y-auto space-y-1">
                            <template x-for="(entry, index) in testResult.log" :key="index">
                                <div class="flex items-start gap-2 leading-relaxed">
                                    <span class="text-zinc-500 shrink-0 select-none" x-text="entry.time"></span>
                                    
                                    <span class="px-1 rounded text-[10px] font-bold uppercase shrink-0"
                                          :class="{
                                              'bg-blue-500/20 text-blue-400': entry.type === 'client',
                                              'bg-emerald-500/20 text-emerald-400': entry.type === 'server',
                                              'bg-amber-500/20 text-amber-400': entry.type === 'info',
                                              'bg-red-500/20 text-red-400': entry.type === 'error',
                                              'bg-purple-500/20 text-purple-400': entry.type === 'success'
                                          }"
                                          x-text="entry.type"></span>

                                    <span class="break-all" 
                                          :class="entry.type === 'error' ? 'text-red-400 font-bold' : ''"
                                          x-text="entry.message"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Bilgilendirme Kartı -->
            <div class="p-5 rounded-xl border border-blue-200 dark:border-blue-900/60 bg-blue-50/50 dark:bg-blue-950/20 text-xs text-blue-900 dark:text-blue-200 space-y-2">
                <div class="font-bold flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-info text-blue-600 dark:text-blue-400"></i>
                    <span>Tavsiye Edilen SMTP Ayarları</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-blue-800 dark:text-blue-300">
                    <li><strong>Gmail:</strong> Port 587 ve TLS şifreleme seçin. Google Hesap Güvenliği'nden <em>Uygulama Şifresi</em> oluşturun.</li>
                    <li><strong>Yandex:</strong> Port 465 ve SSL şifreleme seçin.</li>
                    <li><strong>cPanel / Hosting:</strong> Port 465 SSL veya 587 TLS standarttır.</li>
                </ul>
            </div>

        </div>

    </div>
</div>
