import Alpine from 'alpinejs';

window.Alpine = Alpine;

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

function turkishSlugify(text) {
    const trMap = {
        'ş': 's', 'Ş': 's', 'ı': 'i', 'İ': 'i', 'I': 'i',
        'ğ': 'g', 'Ğ': 'g', 'ü': 'u', 'Ü': 'u',
        'ö': 'o', 'Ö': 'o', 'ç': 'c', 'Ç': 'c'
    };
    let str = text.replace(/[şŞıİIğĞüÜöÖçÇ]/g, (ch) => trMap[ch] || ch);
    return str
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .trim()
        .replace(/[\s-]+/g, '-');
}

function buildSmartEmbedHtml(url) {
    const trimmed = url.trim();

    // YouTube
    const ytMatch = trimmed.match(/^https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/i);
    if (ytMatch) {
        const videoId = ytMatch[1];
        return `<div class="my-6 aspect-video w-full overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-950" contenteditable="false"><iframe src="https://www.youtube-nocookie.com/embed/${videoId}" class="w-full h-full border-0" allowfullscreen loading="lazy"></iframe></div><p><br></p>`;
    }

    // Spotify
    const spMatch = trimmed.match(/^https?:\/\/open\.spotify\.com\/(track|album|playlist|episode)\/([a-zA-Z0-9]+)/i);
    if (spMatch) {
        return `<div class="my-6 w-full overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800" contenteditable="false"><iframe src="https://open.spotify.com/embed/${spMatch[1]}/${spMatch[2]}" class="w-full h-20 border-0" loading="lazy" allow="encrypted-media"></iframe></div><p><br></p>`;
    }

    // CodePen
    const cpMatch = trimmed.match(/^https?:\/\/codepen\.io\/([a-zA-Z0-9_-]+)\/pen\/([a-zA-Z0-9_-]+)/i);
    if (cpMatch) {
        return `<div class="my-6 aspect-video w-full overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800" contenteditable="false"><iframe src="https://codepen.io/${cpMatch[1]}/embed/${cpMatch[2]}?default-tab=result" class="w-full h-full border-0" loading="lazy"></iframe></div><p><br></p>`;
    }

    // Twitter / X
    const twMatch = trimmed.match(/^https?:\/\/(?:www\.)?(?:twitter\.com|x\.com)\/([a-zA-Z0-9_]+)\/status\/([0-9]+)/i);
    if (twMatch) {
        return `<div class="my-6 p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/60 not-prose" contenteditable="false"><div class="flex items-center justify-between"><span class="text-sm font-semibold"><i class="fa-brands fa-x-twitter mr-2"></i>@${twMatch[1]} gönderisi</span><a href="${trimmed}" target="_blank" rel="noopener noreferrer" class="text-xs font-medium px-2.5 py-1 rounded border border-zinc-300 dark:border-zinc-700">X üzerinde görüntüle</a></div></div><p><br></p>`;
    }

    // GitHub Gist
    const gistMatch = trimmed.match(/^https?:\/\/gist\.github\.com\/([a-zA-Z0-9_-]+)\/([a-f0-9]+)/i);
    if (gistMatch) {
        return `<div class="my-6 p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/60 not-prose" contenteditable="false"><div class="flex items-center justify-between"><span class="text-sm font-semibold"><i class="fa-brands fa-github mr-2"></i>GitHub Gist (@${gistMatch[1]})</span><a href="${trimmed}" target="_blank" rel="noopener noreferrer" class="text-xs font-medium px-2.5 py-1 rounded border border-zinc-300 dark:border-zinc-700">Gist'i Aç</a></div></div><p><br></p>`;
    }

    return null;
}

window.bvlogMediaPicker = function () {
    return {
        tab: 'library',
        items: [],
        selected: null,
        searchQuery: '',
        loading: false,
        uploading: false,
        uploadError: '',
        uploadMode: 'inline',
        onSelectCallback: null,

        openPicker(detail = {}) {
            this.uploadMode = detail.mode || 'inline';
            this.onSelectCallback = detail.onSelect || null;
            this.selected = null;
            this.uploadError = '';
            this.tab = 'library';

            const dialog = document.getElementById('bvlog-media-picker-dialog');
            if (dialog && typeof dialog.showModal === 'function') {
                dialog.showModal();
            }
            this.fetchMedia();
        },

        closePicker() {
            const dialog = document.getElementById('bvlog-media-picker-dialog');
            if (dialog && typeof dialog.close === 'function') {
                dialog.close();
            }
        },

        async fetchMedia() {
            this.loading = true;
            try {
                const res = await fetch('/admin/media/json?q=' + encodeURIComponent(this.searchQuery), {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.items = data.items || [];
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },

        async handleUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.uploading = true;
            this.uploadError = '';

            const formData = new FormData();
            formData.append('file', file);
            formData.append('mode', this.uploadMode);
            formData.append('_csrf_token', getCsrfToken());

            try {
                const res = await fetch('/admin/media/upload', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': getCsrfToken() },
                    body: formData
                });
                const data = await res.json();
                if (data.success && data.media) {
                    this.items.unshift(data.media);
                    this.selected = data.media;
                    this.tab = 'library';
                } else {
                    this.uploadError = data.message || 'Görsel yüklenemedi.';
                }
            } catch (e) {
                this.uploadError = 'Yükleme sırasında bağlantı hatası oluştu.';
            } finally {
                this.uploading = false;
                event.target.value = '';
            }
        },

        confirmSelection() {
            if (this.selected && typeof this.onSelectCallback === 'function') {
                this.onSelectCallback(this.selected);
            }
            this.closePicker();
        }
    };
};

window.bvlogPostEditor = function (init) {
    return {
        title: init.title || '',
        slug: init.slug || '',
        slugEdited: Boolean(init.slug),
        summary: init.summary || '',
        seoTitle: init.seoTitle || '',
        seoDesc: init.seoDesc || '',
        coverImage: init.coverImage || '',
        siteTitle: init.siteTitle || 'Bvlog',
        baseUrl: init.baseUrl || '/blog/',
        sourceMode: false,
        isDragging: false,
        inlineUploading: false,
        savedRange: null,

        autoSlug() {
            if (!this.slugEdited) {
                this.slug = turkishSlugify(this.title);
            }
        },

        saveSelection() {
            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0 && this.$refs.wysiwyg) {
                const range = sel.getRangeAt(0);
                if (this.$refs.wysiwyg.contains(range.commonAncestorContainer)) {
                    this.savedRange = range.cloneRange();
                }
            }
        },

        restoreSelection() {
            if (!this.$refs.wysiwyg) return;
            this.$refs.wysiwyg.focus();
            const sel = window.getSelection();
            if (!sel) return;

            if (this.savedRange && this.$refs.wysiwyg.contains(this.savedRange.commonAncestorContainer)) {
                sel.removeAllRanges();
                sel.addRange(this.savedRange);
            } else {
                const range = document.createRange();
                range.selectNodeContents(this.$refs.wysiwyg);
                range.collapse(false);
                sel.removeAllRanges();
                sel.addRange(range);
                this.savedRange = range.cloneRange();
            }
        },

        insertHtmlAtCursor(html) {
            this.restoreSelection();
            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0) {
                const range = sel.getRangeAt(0);
                range.deleteContents();

                const temp = document.createElement('div');
                temp.innerHTML = html;
                const frag = document.createDocumentFragment();
                let node;
                let lastNode = null;
                while ((node = temp.firstChild)) {
                    lastNode = frag.appendChild(node);
                }

                range.insertNode(frag);
                if (lastNode) {
                    const newRange = document.createRange();
                    newRange.setStartAfter(lastNode);
                    newRange.collapse(true);
                    sel.removeAllRanges();
                    sel.addRange(newRange);
                    this.savedRange = newRange.cloneRange();
                }
            } else {
                document.execCommand('insertHTML', false, html);
            }
            this.syncEditorContent();
        },

        exec(cmd, val = null) {
            this.restoreSelection();
            document.execCommand(cmd, false, val);
            this.saveSelection();
            this.syncEditorContent();
        },

        formatBlock(tag) {
            this.restoreSelection();
            document.execCommand('formatBlock', false, tag);
            this.saveSelection();
            this.syncEditorContent();
        },

        insertCodeBlock() {
            this.restoreSelection();
            const sel = window.getSelection();
            const text = sel && sel.toString() ? sel.toString() : '// Kodunuzu buraya yazın';
            const html = `<pre><code>${text.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</code></pre><p><br></p>`;
            this.insertHtmlAtCursor(html);
        },

        insertLink() {
            this.saveSelection();
            window.dispatchEvent(new CustomEvent('open-confirm-modal', {
                detail: {
                    mode: 'prompt',
                    variant: 'primary',
                    title: 'Bağlantı (Link) Ekle',
                    message: 'Eklemek istediğiniz bağlantı adresini (URL) girin:',
                    defaultValue: 'https://',
                    placeholder: 'https://ornek.com',
                    confirmText: 'Bağlantıyı Ekle',
                    onConfirm: (url) => {
                        if (url && url.trim() !== '' && url.trim() !== 'https://') {
                            const cleanUrl = url.trim();
                            this.restoreSelection();
                            const sel = window.getSelection();
                            if (sel && !sel.isCollapsed) {
                                document.execCommand('createLink', false, cleanUrl);
                                this.saveSelection();
                                this.syncEditorContent();
                            } else {
                                this.insertHtmlAtCursor(`<a href="${cleanUrl}" target="_blank" rel="noopener noreferrer">${cleanUrl}</a>`);
                            }
                        }
                    }
                }
            }));
        },

        promptEmbed() {
            this.saveSelection();
            window.dispatchEvent(new CustomEvent('open-confirm-modal', {
                detail: {
                    mode: 'prompt',
                    variant: 'primary',
                    title: 'Akıllı Medya / Embed Ekle',
                    message: 'YouTube, Spotify, CodePen, X (Twitter) veya GitHub Gist bağlantısını yapıştırın:',
                    defaultValue: '',
                    placeholder: 'https://www.youtube.com/watch?v=...',
                    confirmText: 'İçeriğe Yerleştir',
                    onConfirm: (url) => {
                        if (!url || !url.trim()) return;
                        const embedHtml = buildSmartEmbedHtml(url);
                        if (embedHtml) {
                            this.insertHtmlAtCursor(embedHtml);
                        } else {
                            this.insertHtmlAtCursor(`<p><a href="${url}" target="_blank" rel="noopener noreferrer">${url}</a></p>`);
                        }
                    }
                }
            }));
        },

        openEditorMediaPicker() {
            this.saveSelection();
            window.dispatchEvent(new CustomEvent('open-media-picker', {
                detail: {
                    mode: 'inline',
                    onSelect: (media) => {
                        const alt = (media.alt_text || media.title || '').replace(/"/g, '&quot;');
                        this.insertHtmlAtCursor(`<figure class="my-2"><img src="${media.filepath}" alt="${alt}" loading="lazy"></figure><p><br></p>`);
                    }
                }
            }));
        },

        async uploadInlineFile(event) {
            const file = event.target.files[0];
            if (!file) return;
            await this.performInlineUpload(file);
            event.target.value = '';
        },

        async handleDrop(event) {
            this.isDragging = false;
            if (typeof document.caretRangeFromPoint === 'function') {
                const dropRange = document.caretRangeFromPoint(event.clientX, event.clientY);
                if (dropRange && this.$refs.wysiwyg && this.$refs.wysiwyg.contains(dropRange.commonAncestorContainer)) {
                    this.savedRange = dropRange;
                }
            }
            const files = event.dataTransfer?.files;
            if (files && files.length > 0 && files[0].type.startsWith('image/')) {
                await this.performInlineUpload(files[0]);
            }
        },

        async handlePaste(event) {
            this.saveSelection();
            const items = event.clipboardData?.items;
            if (items) {
                for (let i = 0; i < items.length; i++) {
                    if (items[i].type.indexOf('image') !== -1) {
                        event.preventDefault();
                        const file = items[i].getAsFile();
                        if (file) {
                            await this.performInlineUpload(file);
                        }
                        return;
                    }
                }
            }

            const pastedText = event.clipboardData?.getData('text/plain') || '';
            const embedHtml = buildSmartEmbedHtml(pastedText);
            if (embedHtml) {
                event.preventDefault();
                this.insertHtmlAtCursor(embedHtml);
            }
        },

        async performInlineUpload(file) {
            this.inlineUploading = true;
            const formData = new FormData();
            formData.append('file', file);
            formData.append('mode', 'inline');
            formData.append('_csrf_token', getCsrfToken());

            try {
                const res = await fetch('/admin/media/upload', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': getCsrfToken() },
                    body: formData
                });
                const data = await res.json();
                if (data.success && data.url) {
                    const alt = (data.media?.alt_text || '').replace(/"/g, '&quot;');
                    this.insertHtmlAtCursor(`<figure class="my-2"><img src="${data.url}" alt="${alt}" loading="lazy"></figure><p><br></p>`);
                } else {
                    window.dispatchEvent(new CustomEvent('open-confirm-modal', {
                        detail: {
                            mode: 'alert',
                            variant: 'danger',
                            title: 'Yükleme Hatası',
                            message: data.message || 'Görsel yüklenemedi.',
                            confirmText: 'Tamam'
                        }
                    }));
                }
            } catch (e) {
                window.dispatchEvent(new CustomEvent('open-confirm-modal', {
                    detail: {
                        mode: 'alert',
                        variant: 'danger',
                        title: 'Bağlantı Hatası',
                        message: 'Görsel yüklenirken sunucu bağlantısında hata oluştu.',
                        confirmText: 'Tamam'
                    }
                }));
            } finally {
                this.inlineUploading = false;
            }
        },

        previewLocalCover(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                this.coverImage = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        toggleSourceMode() {
            if (!this.sourceMode) {
                this.$refs.rawHtml.value = this.$refs.wysiwyg.innerHTML.trim();
                this.sourceMode = true;
            } else {
                this.$refs.wysiwyg.innerHTML = this.$refs.rawHtml.value;
                this.sourceMode = false;
            }
        },

        syncEditorContent() {
            if (!this.sourceMode && this.$refs.wysiwyg && this.$refs.rawHtml) {
                this.$refs.rawHtml.value = this.$refs.wysiwyg.innerHTML.trim();
            }
        }
    };
};

window.bvlogConfirmModal = function () {
    return {
        mode: 'confirm',
        variant: 'danger',
        title: 'Silme Onayı',
        message: 'Bu işlemi gerçekleştirmek istediğinize emin misiniz?',
        confirmText: 'Evet, Sil',
        cancelText: 'Vazgeç',
        promptValue: '',
        placeholder: '',
        onConfirmCallback: null,

        openModal(detail = {}) {
            this.mode = detail.mode || 'confirm';
            this.variant = detail.variant || (this.mode === 'confirm' ? 'danger' : 'primary');
            this.title = detail.title || (this.mode === 'confirm' ? 'Silme Onayı' : 'Bilgi');
            this.message = detail.message || 'Bu işlemi gerçekleştirmek istediğinize emin misiniz?';
            this.confirmText = detail.confirmText || (this.variant === 'danger' ? 'Evet, Sil' : 'Onayla');
            this.cancelText = detail.cancelText || 'Vazgeç';
            this.promptValue = detail.defaultValue || '';
            this.placeholder = detail.placeholder || '';
            this.onConfirmCallback = detail.onConfirm || null;

            const dialog = document.getElementById('bvlog-confirm-dialog');
            if (dialog && typeof dialog.showModal === 'function') {
                dialog.showModal();
                setTimeout(() => {
                    if (this.mode === 'prompt' && this.$refs.promptInput) {
                        this.$refs.promptInput.focus();
                        this.$refs.promptInput.select();
                    } else if (this.$refs.confirmBtn) {
                        this.$refs.confirmBtn.focus();
                    }
                }, 20);
            }
        },

        confirm() {
            const callback = this.onConfirmCallback;
            const val = this.promptValue;
            this.closeDialog();
            if (typeof callback === 'function') {
                callback(val);
            }
        },

        cancel() {
            this.closeDialog();
        },

        closeDialog() {
            const dialog = document.getElementById('bvlog-confirm-dialog');
            if (dialog && typeof dialog.close === 'function') {
                dialog.close();
            }
        }
    };
};

// Evrensel Form Onay Yakalayıcı (data-confirm niteliği olan tüm formlar için)
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    const message = form.getAttribute('data-confirm');
    if (!message || form.dataset.confirmed === 'true') return;

    event.preventDefault();
    window.dispatchEvent(new CustomEvent('open-confirm-modal', {
        detail: {
            mode: 'confirm',
            variant: form.getAttribute('data-confirm-variant') || 'danger',
            title: form.getAttribute('data-confirm-title') || 'Silme Onayı',
            message: message,
            confirmText: form.getAttribute('data-confirm-btn') || 'Evet, Sil',
            cancelText: form.getAttribute('data-confirm-cancel') || 'Vazgeç',
            onConfirm: () => {
                form.dataset.confirmed = 'true';
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            }
        }
    }));
});

// <dialog closedby="any"> desteklemeyen tarayıcılar için Light-Dismiss yedek mekanizması
if (typeof HTMLDialogElement !== 'undefined' && !('closedBy' in HTMLDialogElement.prototype)) {
    document.querySelectorAll('dialog[closedby="any"]').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target !== dialog) return;
            const rect = dialog.getBoundingClientRect();
            const isDialogContent = (
                rect.top <= event.clientY &&
                event.clientY <= rect.top + rect.height &&
                rect.left <= event.clientX &&
                event.clientX <= rect.left + rect.width
            );
            if (isDialogContent) return;
            dialog.close();
        });
    });
}

Alpine.start();
