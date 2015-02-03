<?php

// Load Yii 1 base class
define('YII1_PATH', $rootPath . '/vendor/yiisoft/yii/framework');
// Load Yii 2 base class
define('YII2_PATH', $rootPath . '/vendor/yiisoft/yii2');

// Override base class until v1.1.17 will released.
// You need version of file after this commit
// @link https://github.com/yiisoft/yii/commit/e08e47ce3ce503b5eb92f9f9bd14d36ac07e1ae9
// define('YII1_BASE_PATH', $rootPath . '/vendor/slavcodev/yii-bridge/src/YiiBase.php');

// Include Yii bridge class file.
require($rootPath . '/vendor/slavcodev/yii2-yii-bridge/src/Yii.php');
