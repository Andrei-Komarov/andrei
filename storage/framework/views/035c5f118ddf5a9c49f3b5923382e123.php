<?php
    $faviconSettings = \App\Models\SiteSetting::query()->first();
    $faviconPath = $faviconSettings->appearance_favicon_path ?? null;
?>

<?php if($faviconPath): ?>
    <link rel="icon" type="image/png" href="<?php echo e(\Illuminate\Support\Facades\Storage::url($faviconPath)); ?>">
<?php else: ?>
    <link rel="icon" href="<?php echo e(asset('favicon.ico')); ?>">
<?php endif; ?>
<?php /**PATH /home/andrei/web/ebay/resources/views/components/favicon.blade.php ENDPATH**/ ?>