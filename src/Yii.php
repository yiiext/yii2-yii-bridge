<?php
/**
 * Slavcodev Components
 *
 * @author Veaceslav Medvedev <slavcopost@gmail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

// Required in Yii1 path
if (!defined('YII1_PATH')) {
    throw new Exception('Yii1 Framework not found.');
} elseif (!defined('YII1_BASE_PATH')) {
    // You need version of file after this commit
    // @link https://github.com/yiisoft/yii/commit/e08e47ce3ce503b5eb92f9f9bd14d36ac07e1ae9
    define('YII1_BASE_PATH', YII1_PATH . DIRECTORY_SEPARATOR . 'YiiBase.php');
}

// Required in Yii2 path
if (!defined('YII2_PATH')) {
    throw new Exception('Yii2 Framework not found.');
} elseif (!defined('YII2_BASE_PATH')) {
    define('YII2_BASE_PATH', YII2_PATH . DIRECTORY_SEPARATOR . 'BaseYii.php');
}

// Predefine Yii1 constants before included YiiBase.php
defined('YII_ZII_PATH') or define('YII_ZII_PATH', YII1_PATH . DIRECTORY_SEPARATOR . 'zii');

// We must first include Yii2 base class to define the correct constant
require(YII2_BASE_PATH);

// Include Yii1 base class
require(YII1_BASE_PATH);

// Override Yii1 system alias
YiiBase::setPathOfAlias('system', YII1_PATH);

/**
 * Yii bridge between v1.1.x and v2.0.
 *
 * @method static CWebApplication|CConsoleApplication app()
 * @method static CWebApplication|CConsoleApplication createApplication($class, $config = null)
 * @method static mixed createComponent($config)
 * @method static string import($alias, $forceInclude = false)
 * @method static string getPathOfAlias($alias)
 * @method static void setPathOfAlias($alias, $path)
 * @method static void trace($msg, $category = 'application')
 *
 * @author Veaceslav Medvedev <slavcopost@gmail.com>
 * @version 0.1
 * @since 1.1.17
 */
class Yii extends \yii\BaseYii
{
    use Slavcodev\YiiBridge\FirstYiiCompatibility;
}

// Yii2 Autoloader and class map
// @link https://github.com/yiisoft/yii2/blob/master/framework/Yii.php#L25:L28
spl_autoload_register(['Yii', 'autoload'], true, true);
Yii::$classMap = include(YII2_PATH . '/classes.php');
Yii::$container = new yii\di\Container();
