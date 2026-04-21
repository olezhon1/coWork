<?php $flashes = popFlashes(); if (!empty($flashes)): ?>
<div class="flash-stack container">
    <?php foreach ($flashes as $f):
        $cls = match ($f['type']) {
            'ok' => 'flash--ok',
            'warn' => 'flash--warn',
            'err' => 'flash--err',
            default => 'flash--info',
        };
    ?>
        <div class="flash <?= $cls ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
