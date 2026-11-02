<?php
use Plugins\Newsletter\NewsletterSchema;

$cfg = NewsletterSchema::getDefaultConfig();
$activeTheme = setting('active_theme', 'default');
$containerMaxW = $activeTheme === 'paper' ? 'max-w-3xl px-5 sm:px-8' : 'max-w-4xl px-4 sm:px-6';
?>

<section aria-labelledby="newsletter-heading" class="border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50/70 dark:bg-zinc-900/40 print:hidden">
    <div class="<?= $containerMaxW ?> mx-auto py-10 sm:py-12"
         x-data="{
             email: '',
             name: '',
             consent: false,
             loading: false,
             statusType: '',
             statusMessage: '',
             openLegalModal() {
                 const dlg = document.getElementById('bvlog-newsletter-kvkk-dialog');
                 if (dlg && typeof dlg.showModal === 'function') {
                     dlg.showModal();
                 }
             },
             acceptLegalFromModal() {
                 this.consent = true;
                 const dlg = document.getElementById('bvlog-newsletter-kvkk-dialog');
                 if (dlg && typeof dlg.close === 'function') {
                     dlg.close('accepted');
                 }
             },
             async submitForm(e) {
                 if (!this.consent) {
                     this.statusType = 'error';
                     this.statusMessage = 'Bültene abone olabilmek için KVKK ve Elektronik İleti Açık Rıza onay kutusunu işaretlemeniz yasal olarak zorunludur.';
                     return;
                 }
                 this.loading = true;
                 this.statusType = '';
                 this.statusMessage = '';
                 try {
                     const formEl = e.target;
                     const formData = new FormData(formEl);
                     const res = await fetch(formEl.action, {
                         method: 'POST',
                         headers: { 'X-Requested-With': 'XMLHttpRequest' },
                         body: formData
                     });
                     const data = await res.json();
                     this.loading = false;
                     if (data.success) {
                         this.statusType = 'success';
                         this.statusMessage = data.message || 'Aboneliğiniz başarıyla kaydedildi!';
                         this.email = '';
                         this.name = '';
                         this.consent = false;
                     } else {
                         this.statusType = 'error';
                         this.statusMessage = data.message || 'Bir hata oluştu.';
                     }
                 } catch (err) {
                     this.loading = false;
                     this.statusType = 'error';
                     this.statusMessage = 'Sunucuyla bağlantı kurulamadı. Lütfen tekrar deneyin.';
                 }
             }
         }">

        <div class="p-6 sm:p-8 rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1.5 max-w-xl">
                    <div class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        <i class="fa-regular fa-paper-plane text-zinc-900 dark:text-zinc-100" aria-hidden="true"></i>
                        <span>E-posta Bülteni</span>
                    </div>
                    <h2 id="newsletter-heading" class="text-lg sm:text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        <?= e($cfg['title']) ?>
                    </h2>
                    <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                        <?= e($cfg['description']) ?>
                    </p>
                </div>
            </div>

            <form action="<?= e(url('/newsletter/subscribe')) ?>" method="POST" @submit.prevent="submitForm($event)" class="space-y-3.5">
                <?= csrf_field() ?>
                <!-- Honeypot Spam Koruması -->
                <div class="hidden" aria-hidden="true">
                    <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
                </div>

                <div class="flex flex-col sm:flex-row gap-2.5">
                    <?php if (!empty($cfg['show_name_field'])): ?>
                        <div class="sm:w-1/3">
                            <label for="newsletter-name-input" class="sr-only">Adınız Soyadınız</label>
                            <input type="text"
                                   id="newsletter-name-input"
                                   name="name"
                                   x-model="name"
                                   autocomplete="name"
                                   placeholder="Adınız (İsteğe bağlı)"
                                   class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-zinc-900 dark:focus:border-zinc-300">
                        </div>
                    <?php endif; ?>

                    <div class="flex-1">
                        <label for="newsletter-email-input" class="sr-only">E-posta Adresiniz</label>
                        <input type="email"
                               id="newsletter-email-input"
                               name="email"
                               x-model="email"
                               inputmode="email"
                               autocomplete="email"
                               required
                               aria-describedby="newsletter-legal-consent-desc"
                               placeholder="<?= e($cfg['placeholder_email']) ?>"
                               class="bvlog-newsletter-email w-full px-3.5 py-2.5 text-sm rounded-xl border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-zinc-900 dark:focus:border-zinc-300">
                    </div>

                    <button type="submit"
                            :disabled="loading"
                            class="px-5 py-2.5 rounded-xl bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs sm:text-sm font-semibold hover:opacity-90 disabled:opacity-50 transition-opacity shrink-0 inline-flex items-center justify-center gap-2">
                        <i :class="loading ? 'fa-solid fa-spinner fa-spin' : 'fa-regular fa-envelope'" aria-hidden="true"></i>
                        <span x-text="loading ? 'Kaydediliyor...' : <?= e(json_encode($cfg['button_text'])) ?>">
                            <?= e($cfg['button_text']) ?>
                        </span>
                    </button>
                </div>

                <!-- Yasal Zorunlu KVKK & 6563 Sayılı ETK Açık Rıza Onay Kutusu -->
                <div id="newsletter-legal-consent-desc" class="pt-1">
                    <label class="flex items-start gap-2.5 cursor-pointer select-none text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                        <input type="checkbox"
                               name="kvkk_consent"
                               value="1"
                               x-model="consent"
                               required
                               class="mt-0.5 w-4 h-4 rounded border-zinc-300 dark:border-zinc-700 text-zinc-900 focus:ring-zinc-900 shrink-0">
                        <span>
                            <button type="button"
                                    @click.prevent="openLegalModal()"
                                    class="font-semibold text-zinc-900 dark:text-zinc-100 underline hover:opacity-80">
                                KVKK Aydınlatma Metni ve Elektronik İleti Koşulları
                            </button>'nı okudum; <?= e($cfg['consent_label']) ?>
                            <span class="text-zinc-400 dark:text-zinc-500">(Her e-postadaki bağlantıdan tek tıkla ayrılabilirsiniz.)</span>
                        </span>
                    </label>
                </div>

                <!-- Geri Bildirim Mesajı -->
                <template x-if="statusMessage !== ''">
                    <div class="p-3.5 rounded-xl border text-xs flex items-center gap-2.5"
                         :class="statusType === 'success'
                             ? 'border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-900 dark:text-emerald-200'
                             : 'border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/50 text-red-900 dark:text-red-200'">
                        <i :class="statusType === 'success' ? 'fa-solid fa-circle-check text-emerald-600' : 'fa-solid fa-circle-exclamation text-red-600'" aria-hidden="true"></i>
                        <span x-text="statusMessage"></span>
                    </div>
                </template>
            </form>
        </div>

        <!-- Yasal KVKK Aydınlatma Metni & Açık Rıza Beyanı Native Dialog Modal -->
        <dialog id="bvlog-newsletter-kvkk-dialog"
                closedby="any"
                class="w-full max-w-2xl rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 p-0 shadow-2xl">
            <div class="p-5 sm:p-6 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between gap-4">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-scale-balanced text-zinc-500" aria-hidden="true"></i>
                    <h3 class="text-sm sm:text-base font-bold tracking-tight">
                        <?= e($cfg['legal_modal_title']) ?>
                    </h3>
                </div>
                <form method="dialog">
                    <button type="submit" value="close" aria-label="Kapat" class="w-8 h-8 rounded-lg border border-zinc-200 dark:border-zinc-800 inline-flex items-center justify-center text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </form>
            </div>

            <div class="p-5 sm:p-6 max-h-[60vh] overflow-y-auto text-xs sm:text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed whitespace-pre-line">
                <?= e($cfg['legal_modal_body']) ?>
            </div>

            <div class="p-4 sm:p-5 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/60 flex flex-wrap items-center justify-end gap-2.5">
                <form method="dialog">
                    <button type="submit" value="cancel" class="px-4 py-2 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800">
                        Kapat
                    </button>
                </form>
                <button type="button"
                        @click="acceptLegalFromModal()"
                        class="px-4 py-2 rounded-xl bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                    <span>Okudum, Açık Rıza Veriyorum</span>
                </button>
            </div>
        </dialog>

    </div>
</section>
