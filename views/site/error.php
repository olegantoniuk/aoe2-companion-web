<?php

use yii\helpers\Html;

// the name comes from the framework in English ("Not Found (#404)"); the message is translated where it is thrown
$name = Yii::t('app', $name);
$this->title = $name;
?>

<div class="text-center py-5">
    <h1 style="font-size: 5rem; font-weight: 700; color: var(--color-border);"><?= Html::encode($statusCode ?? 404) ?></h1>
    <h2><?= Html::encode($name) ?></h2>
    <p class="text-muted"><?= nl2br(Html::encode($message)) ?></p>
    <a href="<?= Yii::$app->homeUrl ?>" class="btn btn-primary btn-sm mt-3"><?= Yii::t('app', 'Go Home') ?></a>
</div>
