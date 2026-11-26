<?php

use yii\helpers\Html;
use app\components\TextFormat;

/** @var array $videos [{title, url, channel}] */
/** @var array $sources [{title, url, type}] */
$videos = array_values(array_filter($videos ?? [], function ($v) { return !empty($v['url']); }));
$sources = array_values(array_filter($sources ?? [], function ($s) { return !empty($s['url']); }));
?>

<?php if ($videos): ?>
    <h2 class="unit-section-title"><?= Yii::t('app', 'Videos') ?></h2>
    <div class="kb-videos">
        <?php foreach ($videos as $v): ?>
            <?php $id = TextFormat::youtubeId($v['url']); ?>
            <a href="<?= Html::encode($v['url']) ?>" class="admin5-card admin5-card-border kb-video" target="_blank" rel="nofollow noopener">
                <?php if ($id): ?>
                    <span class="kb-video-thumb">
                        <img src="https://i.ytimg.com/vi/<?= Html::encode($id) ?>/mqdefault.jpg" alt="" loading="lazy">
                        <span class="kb-video-play" aria-hidden="true"></span>
                    </span>
                <?php endif; ?>
                <span class="kb-video-text">
                    <span class="kb-video-title"><?= Html::encode($v['title'] ?? Yii::t('app', 'Video')) ?></span>
                    <?php if (!empty($v['channel'])): ?>
                        <span class="kb-video-channel"><?= Html::encode($v['channel']) ?></span>
                    <?php endif; ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($sources): ?>
    <h2 class="unit-section-title"><?= Yii::t('app', 'Sources and further reading') ?></h2>
    <ul class="kb-sources">
        <?php foreach ($sources as $s): ?>
            <li>
                <span class="kb-source-type"><?= Html::encode(Yii::t('app', ucfirst($s['type'] ?? 'link'))) ?></span>
                <a href="<?= Html::encode($s['url']) ?>" target="_blank" rel="nofollow noopener"><?= Html::encode($s['title'] ?? $s['url']) ?></a>
                <span class="kb-source-host"><?= Html::encode(TextFormat::host($s['url'])) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
