// Ziyaretci ve yonetim paketlerinin ortak kurulumu. Alpine burada baslatilmaz;
// her giris noktasi kendi bilesenlerini tanimladiktan sonra Alpine.start() cagirir.
import Alpine from 'alpinejs';

window.Alpine = Alpine;

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

export default Alpine;
