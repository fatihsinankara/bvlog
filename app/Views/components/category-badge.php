<?php
/**
 * Kategori rozeti bileşeni: Seçilen düz renk ve FontAwesome ikonu ile render eder.
 * $name, $slug, $icon, $color, $linked (bool)
 */
$catName  = $name ?? '';
$catSlug  = $slug ?? '';
$catIcon  = !empty($icon) ? $icon : 'fa-solid fa-folder';
$catColor = !empty($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#18181b';
$isLinked = $linked ?? true;

if ($catName === '') {
    return;
}

$style = "color: {$catColor}; border-color: {$catColor};";
?>
<?php if ($isLinked && $catSlug !== ''): ?>
    <a href="<?= e(url('/category/' . $catSlug)) ?>"
       class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded border bg-zinc-50 dark:bg-zinc-900 hover:opacity-80 transition-opacity"
       style="<?= e($style) ?>">
        <i class="<?= e($catIcon) ?> text-[11px]"></i>
        <span><?= e($catName) ?></span>
    </a>
<?php else: ?>
    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded border bg-zinc-50 dark:bg-zinc-900"
          style="<?= e($style) ?>">
        <i class="<?= e($catIcon) ?> text-[11px]"></i>
        <span><?= e($catName) ?></span>
    </span>
<?php endif; ?>
