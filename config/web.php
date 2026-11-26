<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'aoe2-companion',
    'name' => 'AoE2 Companion',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'app\components\Lang'],
    'language' => 'en',
    'sourceLanguage' => 'en',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => 'aoe2-companion-secret-key-change-me',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'i18n' => [
            'translations' => [
                // interface strings (app) and game names (names), keyed by the English text; see components/Tr.php
                '*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => 'en',
                ] + (YII_ENV_DEV ? [
                    // dev: collect what a page asked for and did not find, for the translators
                    'on missingTranslation' => function ($e) {
                        $dir = Yii::getAlias('@runtime/i18n-missing/' . $e->language);
                        if (!is_dir($dir)) mkdir($dir, 0777, true);
                        file_put_contents("$dir/{$e->category}.jsonl", json_encode($e->message, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
                    },
                ] : []),
            ],
        ],
        'urlManager' => [
            'class' => 'app\components\LangUrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '' => 'site/index',
                'login' => 'site/login',
                'sitemap.xml' => 'sitemap/index',
                'logout' => 'site/logout',
                'civilizations' => 'civilization/index',
                'civilizations/<slug:[a-z0-9_-]+>' => 'civilization/view',
                'units' => 'unit/index',
                'units/classes' => 'unit/classes',
                'units/classes/<slug:[a-z0-9_-]+>' => 'unit/class',
                'units/category/<slug:[a-z0-9_-]+>' => 'unit/category',
                'units/<slug:[a-z0-9_-]+>' => 'unit/view',
                'buildings' => 'building/index',
                'buildings/<slug:[a-z0-9_-]+>' => 'building/view',
                'technologies' => 'technology/index',
                'builds' => 'build/index',
                'builds/<slug:[a-z0-9_-]+>' => 'build/view',
                'guides' => 'guide/index',
                'guides/<slug:[a-z0-9_-]+>' => 'guide/view',
                'matchup' => 'matchup/index',
                'matchup/<yourCiv:[a-z0-9_-]+>-vs-<enemyCiv:[a-z0-9_-]+>' => 'matchup/view',
                'api/civilizations' => 'api/civilizations',
                'api/matchup' => 'api/matchup',
            ],
        ],
        'assetManager' => [
            'appendTimestamp' => true,
            'bundles' => [
                'yii\bootstrap\BootstrapAsset' => false,
                'yii\bootstrap\BootstrapPluginAsset' => false,
            ],
        ],
    ],
    'params' => $params,
    'on afterRequest' => function () {
        \app\components\Tr::flushMissing();
    },
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        'allowedIPs' => ['*'],
    ];
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'allowedIPs' => ['*'],
    ];
}

return $config;
