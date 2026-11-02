<div class="space-y-6 w-full"
     x-data="{
         copiedKey: '',
         copyText(text, key) {
             if (navigator.clipboard && navigator.clipboard.writeText) {
                 navigator.clipboard.writeText(text);
             } else {
                 const ta = document.createElement('textarea');
                 ta.value = text;
                 document.body.appendChild(ta);
                 ta.select();
                 document.execCommand('copy');
                 document.body.removeChild(ta);
             }
             this.copiedKey = key;
             setTimeout(() => { if (this.copiedKey === key) this.copiedKey = ''; }, 2000);
         }
     }">

    <!-- Üst Özet ve Hızlı Çalıştırma Kartı -->
    <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800 pb-5">
            <div>
                <h2 class="text-base font-bold tracking-tight flex items-center gap-2">
                    <i class="fa-regular fa-clock text-zinc-500"></i>
                    <span>Zamanlanmış Görevler (Cron) Yönetimi</span>
                </h2>
                <p class="text-xs text-zinc-500 mt-0.5">
                    İleri tarihli blog yazılarının otomatik yayınlanması ve sistem bakım görevlerinin çalışma durumu.
                </p>
            </div>
            <form action="<?= e(url('/admin/cron/run')) ?>" method="POST">
                <?= csrf_field() ?>
                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 transition-opacity">
                    <i class="fa-solid fa-play text-[10px]"></i>
                    <span>Tüm Görevleri Şimdi Çalıştır</span>
                </button>
            </form>
        </div>

        <!-- Durum Metrikleri -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40 space-y-1">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Son Cron Çalışması</span>
                <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                    <?= !empty($cronSummary['ran_at']) ? e(format_date($cronSummary['ran_at'], true)) : 'Henüz çalışmadı' ?>
                </div>
                <span class="inline-block text-[11px] text-zinc-500">
                    Kaynak: <?= e($cronSummary['trigger_label'] ?? '—') ?>
                    <?php if (!empty($cronSummary['duration_ms'])): ?>
                        (<?= (int) $cronSummary['duration_ms'] ?> ms)
                    <?php endif; ?>
                </span>
            </div>

            <div class="p-4 rounded-xl border <?= !empty($scheduledPostsCount) ? 'border-amber-300 dark:border-amber-800 bg-amber-50/40 dark:bg-amber-950/20' : 'border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40' ?> space-y-1">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Bekleyen Zamanlanmış Yazı</span>
                <div class="text-2xl font-black font-mono <?= !empty($scheduledPostsCount) ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-zinc-100' ?>">
                    <?= (int) ($scheduledPostsCount ?? 0) ?>
                </div>
                <a href="<?= e(url('/admin/posts')) ?>" class="inline-block text-[11px] text-zinc-500 hover:underline">
                    Yazı listesinde görüntüle &rarr;
                </a>
            </div>

            <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40 space-y-1">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Aktif Görev Sayısı</span>
                <div class="text-2xl font-black font-mono text-zinc-900 dark:text-zinc-100">
                    <?= count($cronTasks ?? []) ?>
                </div>
                <span class="inline-block text-[11px] text-zinc-500">Çekirdek & eklenti görevleri</span>
            </div>
        </div>
    </div>

    <!-- Sunucuya Özel cPanel / Plesk / SSH Cron Komutları -->
    <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5">
        <div>
            <h3 class="text-sm font-bold tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-terminal text-emerald-600"></i>
                <span>Sunucunuza Özel Hazır Cron Komutları (cPanel / Plesk / DirectAdmin)</span>
            </h3>
            <p class="text-xs text-zinc-500 mt-1">
                Sunucu dizininiz (<code class="font-mono text-[11px] px-1 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800"><?= e($cronCommands['cron_file'] ?? '') ?></code>) ve PHP çalışma yolunuz otomatik algılandı. Hosting panelinizdeki <strong>Cron Jobs (Zamanlanmış Görevler)</strong> ekranına aşağıdaki komutlardan birini <strong>Her Dakika (<code>* * * * *</code>)</strong> çalışacak şekilde ekleyin.
            </p>
        </div>

        <div class="space-y-4">
            <!-- 1. PHP CLI Komutu (cPanel Önerilen) -->
            <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-950/50 space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <span>1. cPanel / DirectAdmin Komutu (PHP CLI)</span>
                        <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-semibold">Önerilen</span>
                    </div>
                    <span class="text-[11px] text-zinc-400 font-mono">Sıklık: * * * * * (Dakikada bir)</span>
                </div>
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="<?= e($cronCommands['cpanel_cli'] ?? '') ?>"
                           class="flex-1 px-3 py-2 text-xs font-mono rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 select-all focus:outline-none">
                    <button type="button"
                            @click="copyText(<?= e(json_encode($cronCommands['cpanel_cli'] ?? '')) ?>, 'cpanel_cli')"
                            class="px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800 shrink-0 inline-flex items-center gap-1.5">
                        <i :class="copiedKey === 'cpanel_cli' ? 'fa-solid fa-check text-emerald-500' : 'fa-regular fa-copy'"></i>
                        <span x-text="copiedKey === 'cpanel_cli' ? 'Kopyalandı!' : 'Kopyala'">Kopyala</span>
                    </button>
                </div>
                <p class="text-[11px] text-zinc-500">
                    cPanel'de süre seçimi (<code>* * * * *</code>) ayrı kutulardan yapıldığı için "Komut (Command)" alanına doğrudan yukarıdaki satırı yapıştırabilirsiniz.
                </p>
            </div>

            <!-- 2. HTTP / cURL Komutu -->
            <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-950/50 space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">2. cURL ile HTTP Tetikleme Komutu (Alternatif)</span>
                    <span class="text-[11px] text-zinc-400 font-mono">Plesk / Harici Cron Uyumlu</span>
                </div>
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="<?= e($cronCommands['cpanel_curl'] ?? '') ?>"
                           class="flex-1 px-3 py-2 text-xs font-mono rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 select-all focus:outline-none">
                    <button type="button"
                            @click="copyText(<?= e(json_encode($cronCommands['cpanel_curl'] ?? '')) ?>, 'cpanel_curl')"
                            class="px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800 shrink-0 inline-flex items-center gap-1.5">
                        <i :class="copiedKey === 'cpanel_curl' ? 'fa-solid fa-check text-emerald-500' : 'fa-regular fa-copy'"></i>
                        <span x-text="copiedKey === 'cpanel_curl' ? 'Kopyalandı!' : 'Kopyala'">Kopyala</span>
                    </button>
                </div>
            </div>

            <!-- 3. Doğrudan Cron URL ve Güvenlik Anahtarı (Token) -->
            <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-950/50 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">3. Doğrudan Tetikleme URL'si & Gizli Anahtar (Token)</span>
                    <a href="<?= e($cronCommands['cron_url'] ?? '#') ?>" target="_blank"
                       class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1">
                        <span>Tarayıcıda Aç / Test Et</span>
                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                    </a>
                </div>
                <div class="flex flex-wrap sm:flex-nowrap items-center gap-2">
                    <input type="text" readonly value="<?= e($cronCommands['cron_url'] ?? '') ?>"
                           class="w-full sm:flex-1 px-3 py-2 text-xs font-mono rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 select-all focus:outline-none">
                    <button type="button"
                            @click="copyText(<?= e(json_encode($cronCommands['cron_url'] ?? '')) ?>, 'cron_url')"
                            class="px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800 shrink-0 inline-flex items-center gap-1.5">
                        <i :class="copiedKey === 'cron_url' ? 'fa-solid fa-check text-emerald-500' : 'fa-regular fa-copy'"></i>
                        <span x-text="copiedKey === 'cron_url' ? 'Kopyalandı!' : 'URL Kopyala'">URL Kopyala</span>
                    </button>
                    <form action="<?= e(url('/admin/cron/regenerate-token')) ?>" method="POST"
                          data-confirm="Cron güvenlik anahtarını (token) yenilemek istediğinize emin misiniz? Mevcut HTTP/cURL Cron komutlarındaki eski anahtar geçersiz olacaktır."
                          data-confirm-title="Cron Anahtarını Yenile"
                          data-confirm-btn="Evet, Anahtarı Yenile"
                          data-confirm-variant="primary"
                          class="inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-xs font-semibold text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/30 shrink-0 inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-key"></i>
                            <span>Anahtarı Yenile</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Opsiyonel Ziyaretçi Tetiklemeli Yedek Mod (Web-Cron) -->
        <form action="<?= e(url('/admin/settings/cron')) ?>" method="POST" class="pt-3 border-t border-zinc-100 dark:border-zinc-800 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="active_tab" value="cron">

            <label class="flex items-center justify-between gap-4 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                <div class="text-xs space-y-0.5">
                    <strong class="block text-sm font-bold text-zinc-900 dark:text-zinc-100">Ziyaretçi Trafiğiyle Otomatik Tetikleme (Web-Cron Yedek Modu)</strong>
                    <span class="text-zinc-500 dark:text-zinc-400">cPanel Cron tanımlayamadığınız ortamlarda etkinleştirebilirsiniz. Siteye ziyaretçi girdiğinde dakikada en fazla 1 kez bekleyen zamanlanmış görevleri çalıştırır.</span>
                </div>
                <input type="checkbox" name="cron_web_fallback" value="1" role="switch" <?= ($settings['cron_web_fallback'] ?? '0') === '1' ? 'checked' : '' ?> class="ios-switch shrink-0">
            </label>

            <button type="submit" class="px-5 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                Cron Tercihlerini Kaydet
            </button>
        </form>
    </div>

    <!-- Kayıtlı Zamanlanmış Görevler Tablosu -->
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold">Kayıtlı Zamanlanmış Görevler</h3>
                <p class="text-xs text-zinc-500">Sistemde tanımlı tüm çekirdek ve eklenti görevlerini tekil olarak test edebilirsiniz.</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 font-semibold">
                        <th class="py-3 px-4">Görev & Açıklama</th>
                        <th class="py-3 px-4">Sıklık</th>
                        <th class="py-3 px-4">Son Çalışma</th>
                        <th class="py-3 px-4">Sonuç / Durum</th>
                        <th class="py-3 px-4 text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <?php foreach ($cronTasks ?? [] as $task): ?>
                        <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-sm text-zinc-900 dark:text-zinc-100"><?= e($task['title']) ?></span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-zinc-100 dark:bg-zinc-800 text-zinc-500"><?= e($task['source']) ?></span>
                                </div>
                                <p class="text-[11px] text-zinc-500 mt-0.5"><?= e($task['description']) ?></p>
                                <span class="text-[10px] font-mono text-zinc-400">ID: <?= e($task['id']) ?></span>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 font-semibold">
                                    <?= e($task['interval_label']) ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                                <?php if (!empty($task['last_run_at'])): ?>
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100"><?= e(format_date($task['last_run_at'], true)) ?></div>
                                    <div class="text-[11px] text-zinc-400"><?= (int) ($task['last_duration_ms'] ?? 0) ?> ms &middot; <?= (int) $task['run_count'] ?> kez</div>
                                <?php else: ?>
                                    <span class="text-zinc-400">Henüz çalışmadı</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4">
                                <?php if (($task['last_status'] ?? '') === 'success'): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 font-semibold mb-1">
                                        <i class="fa-solid fa-check text-[10px]"></i>
                                        <span>Başarılı</span>
                                    </span>
                                <?php elseif (($task['last_status'] ?? '') === 'error'): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300 border border-red-200 dark:border-red-800 font-semibold mb-1">
                                        <i class="fa-solid fa-xmark text-[10px]"></i>
                                        <span>Hata</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 font-semibold mb-1">
                                        <span>Bekliyor</span>
                                    </span>
                                <?php endif; ?>
                                <div class="text-[11px] text-zinc-500 max-w-xs"><?= e($task['last_message']) ?></div>
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <form action="<?= e(url('/admin/cron/run')) ?>" method="POST" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="task_id" value="<?= e($task['id']) ?>">
                                    <button type="submit" class="px-2.5 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 font-semibold inline-flex items-center gap-1">
                                        <i class="fa-solid fa-play text-[10px]"></i>
                                        <span>Çalıştır</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Son Çalışma Günlüğü (Cron Geçmişi) -->
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800">
            <h3 class="text-sm font-bold">Son Çalışma Günlüğü (Geçmiş)</h3>
            <p class="text-xs text-zinc-500">En son gerçekleşen 25 Cron tetiklemesinin özeti.</p>
        </div>
        <?php if (empty($cronHistory)): ?>
            <div class="p-8 text-center text-xs text-zinc-500">Henüz kaydedilmiş bir Cron çalışma geçmişi bulunmuyor.</div>
        <?php else: ?>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800 max-h-80 overflow-y-auto">
                <?php foreach ($cronHistory as $h): ?>
                    <div class="px-6 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-zinc-900 dark:text-zinc-100"><?= e(format_date($h['ran_at'] ?? '', true)) ?></span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950">
                                    <?= e($h['trigger_label'] ?? $h['trigger'] ?? 'CLI') ?>
                                </span>
                                <span class="text-[11px] text-zinc-400"><?= (int) ($h['duration_ms'] ?? 0) ?> ms</span>
                            </div>
                            <div class="text-zinc-600 dark:text-zinc-400 text-[11px]"><?= e($h['summary'] ?? '') ?></div>
                        </div>
                        <div class="shrink-0">
                            <?php if (($h['status'] ?? 'success') === 'error'): ?>
                                <span class="px-2 py-0.5 rounded bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300 font-semibold text-[11px]">Hata</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 font-semibold text-[11px]">Tamamlandı</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>
