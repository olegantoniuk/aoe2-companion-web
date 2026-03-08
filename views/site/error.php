<?php

use yii\helpers\Html;

$this->title = $name;
?>

<div class="text-center py-5">
    <h1 style="font-size: 5rem; font-weight: 700; color: #E1E3E7;"><?= Html::encode($statusCode ?? 404) ?></h1>
    <h2><?= Html::encode($name) ?></h2>
    <p class="text-muted"><?= nl2br(Html::encode($message)) ?></p>
    <a href="<?= Yii::$app->homeUrl ?>" class="btn btn-primary btn-sm mt-3">Go Home</a>
</div>
