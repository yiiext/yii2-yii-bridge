<?php
/**
 * Slavcodev Components
 *
 * @author Veaceslav Medvedev <slavcopost@gmail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace Slavcodev\YiiBridge;

use yii\log\Logger as BaseLogger;
use CLogger;

/**
 * Class FirstYiiCompatibility
 *
 * @author Veaceslav Medvedev <slavcopost@gmail.com>
 * @version 0.1
 */
trait FirstYiiCompatibility
{
    public static $enableIncludePath = true;

    /** @var \yii\log\Logger */
    private static $_logger;

    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array(['YiiBase', $name], $arguments);
    }

    public static function getLogger()
    {
        if (self::$_logger === null) {
            self::$_logger = static::createObject('\Slavcodev\YiiBridge\Log\Logger');
            parent::setLogger(self::$_logger);
        }
        return parent::getLogger();
    }

    public static function log($msg, $level = CLogger::LEVEL_INFO, $category = 'application')
    {
        switch ($level) {
            case CLogger::LEVEL_TRACE:
                $level = BaseLogger::LEVEL_TRACE;
                break;
            case CLogger::LEVEL_WARNING:
                $level = BaseLogger::LEVEL_WARNING;
                break;
            case CLogger::LEVEL_INFO:
                $level = BaseLogger::LEVEL_INFO;
                break;
            case CLogger::LEVEL_PROFILE:
                $level = BaseLogger::LEVEL_PROFILE;
                break;
            default:
                $level = BaseLogger::LEVEL_ERROR;
        }

        static::getLogger()->log($msg, $level, $category);
    }
} 
