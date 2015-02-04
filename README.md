Yii bridge between v1.1.x and v2.0
==================================

### Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist slavcodev/yii2-yii-bridge "*"
```

or add

```json
"slavcodev/yii2-yii-bridge": "*"
```

to the require section of your composer.json.

### Usage

To use this bridge, edit your entry scripts (`web/index.php` and `yii`) according to the examples below, which assume that you have put your yii1 application config under `yii1-config/`.

#### ./web/index.php example

Before:

```php
require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../config/bootstrap.php');
$config = require(__DIR__ . '/../config/main.php');
$application = new yii\web\Application($config);
$application->run();
```

After:

```php
require(__DIR__ . '/../vendor/autoload.php');

// Define project directory
$rootPath = __DIR__ . '/..';

// Include yii 1 and yii 2
require("$rootPath/vendor/slavcodev/yii2-yii-bridge/include.php");

// Include yii 1 app config
$v1AppConfig = require("$rootPath/yii1-config/main.php");

// Create old web application, but NOT run it!
Yii::createWebApplication($v1AppConfig);

// Include yii 2 app config
require(__DIR__ . '/../config/bootstrap.php');
$config = require(__DIR__ . '/../config/main.php');
$application = new yii\web\Application($config);
$application->run();
```

#### ./yii example

Before:

```php
require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/config/bootstrap.php');
require(__DIR__ . '/config/main.php');
$application = new yii\console\Application($config);
$exitCode = $application->run();
exit($exitCode);
```

After:

```php
require(__DIR__ . '/vendor/autoload.php');

// Define project directory
$rootPath = __DIR__;

// Include yii 1 and yii 2
require("$rootPath/vendor/slavcodev/yii2-yii-bridge/include.php");

// Include yii 1 app config
$v1AppConfig = require("$rootPath/yii1-config/console.php");

// fix for fcgi
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));

// Create old console application, but NOT run it!
$yii1app=Yii::createConsoleApplication($v1AppConfig);
$yii1app->commandRunner->addCommands(YII1_PATH.'/cli/commands');

// Include yii 2 app config
require(__DIR__ . '/config/bootstrap.php');
require(__DIR__ . '/config/main.php');

$application = new yii\console\Application($config);
$exitCode = $application->run();
exit($exitCode);
```

####

Now you can use old models in your Yii2 application, i.e

```php
// Access new application
echo Yii::$app->user->id;

// Access old application
echo Yii::app()->user->id;

// Use Yii2 grid, data provider with Yii1 ActiveRecords
echo yii\grid\GridView::widget([
    'dataProvider' => new \yii\data\ArrayDataProvider([
            'allModels' => User::model()->with('address.country')->findAll(),
        ]),
    'columns' => [
        ['attribute' => 'id'],
        ['attribute' => 'name'],
        ['attribute' => 'address.country.name'],
    ]
]);

// Save Yii1 AR in Yii2 controller
public function actionCreate()
{
    $user = new User();
    
    if ($data = Yii::app()->request->getPost(CHtml::modelName($user))) {
        $model->attributes = $data;
        
        if ($model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }
    }
    
    return $this->render('create', [
        'model' => $user,
    ]);
}
```
