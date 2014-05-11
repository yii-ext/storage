<?php
/**
 * @author Klimov Paul <klimov@zfort.com>
 * @link http://www.zfort.com/
 * @copyright Copyright &copy; 2000-2014 Zfort Group
 * @license http://www.zfort.com/terms-of-use
 */

namespace yii_ext\fileStorage;

use CComponent;
use CException;
use CLogger;
use Yii;

/**
 * BaseBucket is a base class for the file storage buckets.
 *
 * @property string $name public alias of {@link _name}.
 * @property StorageInterface $storage public alias of {@link _storage}.
 *
 * @author Klimov Paul <klimov@zfort.com>
 * @version $Id$
 * @package zfort\file\storage
 * @since 1.0
 */
abstract class BaseBucket extends CComponent implements BucketInterface
{
    /**
     * @var string bucket name.
     */
    protected $_name = '';
    /**
     * @var StorageInterface file storage, which owns the bucket.
     */
    protected $_storage = null;

    /**
     * Logs a message.
     * @see CLogRouter
     * @param string $message message to be logged.
     * @param string $level level of the message (e.g. 'trace', 'warning', 'error'). It is case-insensitive.
     * @return boolean success.
     */
    protected function log($message, $level = CLogger::LEVEL_INFO)
    {
        $category = str_replace('\\', '.', get_class($this));
        $message = 'Bucket "' . $this->getName() . '": ' . $message;
        Yii::log($message, $level, $category);
        return true;
    }

    /**
     * Sets bucket name.
     * @param string $name - bucket name.
     * @return boolean success.
     */
    public function setName($name)
    {
        if (!is_string($name)) {
            throw new CException('"' . get_class($this) . '::name" should be a string!');
        }
        $this->_name = $name;
        return true;
    }

    /**
     * Gets current bucket name.
     * @return string $name - bucket name.
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Sets bucket file storage.
     * @param StorageInterface $storage - file storage.
     * @return boolean success.
     */
    public function setStorage(StorageInterface $storage)
    {
        $this->_storage = $storage;
        return true;
    }

    /**
     * Gets bucket file storage.
     * @return StorageInterface - bucket file storage.
     */
    public function getStorage()
    {
        return $this->_storage;
    }
}