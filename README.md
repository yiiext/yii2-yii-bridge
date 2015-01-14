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

To use this bridge, edit your entry script (`index.php`)

```php
// Define project directories.
$rootPath = dirname(dirname(__DIR__));

/**
 * Include composer autoloader
 * @var \Composer\Autoload\ClassLoader $loader Registered composer autoloader.
 */
$loader = require($rootPath . '/vendor/autoload.php');

// Load Yii 1 base class
define('YII1_PATH', $rootPath . '/vendor/yiisoft/yii');
// Load Yii 2 base class
define('YII2_PATH', $rootPath . '/vendor/yiisoft/yii2');

// Override base class until v1.1.17 will released.
// You need version of file after this commit
// @link https://github.com/yiisoft/yii/commit/e08e47ce3ce503b5eb92f9f9bd14d36ac07e1ae9
// define('YII1_BASE_PATH', $rootPath . '/vendor/slavcodev/yii-bridge/YiiBase.php');

// Include Yii bridge class file.
require($rootPath . '/vendor/slavcodev/yii-bridge/Yii.php');

// Create old application, but NOT run it!
$gaffer = Yii::createWebApplication($v1AppConfig);

// Create new application and run. Have fun!
$application = new yii\web\Application($v2AppConfig);
$application->run();
```

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
