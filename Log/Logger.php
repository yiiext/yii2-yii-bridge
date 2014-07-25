<?php
/**
 * Slavcodev Components
 *
 * @author Veaceslav Medvedev <slavcopost@gmail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace Slavcodev\YiiBridge\Log;

use yii\base\UnknownMethodException;
use yii\log\Logger as BaseLogger;
use CLogger;

/**
 * Class Logger
 *
 * @author Veaceslav Medvedev <slavcopost@gmail.com>
 * @version 0.1
 */
class Logger extends BaseLogger
{
    private $old;

    private function getOldLogger()
    {
        if (!$this->old) {
            $this->old = new CLogger();
        }
        return $this->old;
    }

    public function __call($name, $params)
    {
        try {
            return parent::__call($name, $params);
        } catch (UnknownMethodException $e) {
            return call_user_func_array([$this->getOldLogger(), $name], $params);
        }
    }
} 
