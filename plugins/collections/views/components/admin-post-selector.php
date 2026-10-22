<?php
/**
 * @var array|null $post
 * @var array $collections
 * @var array|null $currentRelation
 */
$selectedCollectionId = (int) ($currentRelation['collection_id'] ?? 0);
$selectedOrder = (int) ($currentRelation['sort_order'] ?? 1);
?>

<div class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4">
    <div class="flex items-center justify-between">
        <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400 flex items-center gap-1.5">
            <i class="fa-solid fa-layer-group text-blue-500"></i>
            <span>Koleksiyon / Seri</span>
        </h3>
        <?php if ($selectedCollectionId > 0): ?>
            <a href="<?= e(url('/admin/collections/' . $selectedCollectionId . '/edit')) ?>" 
               target="_blank" 
               class="text-[11px] text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1">
                <span>Koleksiyonu Gör</span>
                <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
            </a>
        <?php endif; ?>
    </div>

    <div>
        <label class="block text-xs font-semibold mb-1.5">Bağlı Olduğu Koleksiyon</label>
        <select name="collection_id" 
                class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none">
            <option value="0">— Bir Koleksiyona Ait Değil —</option>
            <?php foreach ($collections as $coll): ?>
                <option value="<?= (int) $coll['id'] ?>" <?= $selectedCollectionId === (int) $coll['id'] ? 'selected' : '' ?>>
                    <?= e($coll['title']) ?> <?= $coll['status'] === 'draft' ? '(Taslak)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="block text-xs font-semibold mb-1.5">Bölüm / Parça Sırası</label>
        <input type="number" 
               name="collection_order" 
               min="1" 
               value="<?= $selectedOrder ?>" 
               placeholder="1"
               class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none font-mono">
        <p class="text-[11px] text-zinc-500 mt-1">Örn: Serinin 1. yazısı için 1, 2. yazısı için 2.</p>
    </div>
</div>
