<?php
/**
 * @author Klimov Paul <klimov@zfort.com>
 * @link http://www.zfort.com/
 * @copyright Copyright &copy; 2000-2014 Zfort Group
 * @license http://www.zfort.com/terms-of-use
 */

namespace yii_ext\storage;

use CApplicationComponent;
use CException;
use CLogger;
use Yii;

/**
 * BaseStorage is a base class for the file storages.
 * This class stores the file storage bucket instances and creates them based on
 * the configuration array.
 * Each particular file storage is supposed to use a particular class for its buckets.
 * Name of this class can be set through the {@link bucketClassName}.
 *
 * @property BucketInterface[] $buckets public alias of {@link _buckets}.
 * @property string $bucketClassName public alias of {@link _bucketClassName}.
 *
 * @author Klimov Paul <klimov@zfort.com>
 * @version $Id$
 * @package yii_ext\storage
 * @since 1.0
 */
abstract class BaseStorage extends CApplicationComponent implements StorageInterface
{
    /**
     * @var BucketInterface[] list of buckets.
     */
    protected $_buckets = array();
    /**
     * @var string name of the bucket class.
     */
    protected $_bucketClassName = 'yii_ext\storage\BaseBucket';

    // Set / Get :

    public function setBucketClassName($bucketClassName)
    {
        if (!is_string($bucketClassName)) {
            throw new CException('"' . get_class($this) . '::bucketClassName" should be a string!');
        }
        $this->_bucketClassName = $bucketClassName;
        return true;
    }

    public function getBucketClassName()
    {
        return $this->_bucketClassName;
    }

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
        Yii::log($message, $level, $category);
        return true;
    }

    /**
     * Creates bucket instance based on the configuration array.
     * @param array $bucketConfig - configuration array for the bucket.
     * @return BucketInterface bucket instance.
     */
    protected function createBucketInstance(array $bucketConfig)
    {
        if (!array_key_exists('class', $bucketConfig)) {
            $bucketClassName = $this->getBucketClassName();
            $bucketConfig['class'] = $bucketClassName;
        }
        $bucketConfig['storage'] = $this;
        return Yii::createComponent($bucketConfig);
    }

    /**
     * Sets the list of available buckets.
     * @param array $buckets - set of bucket instances or bucket configurations.
     * @return boolean success.
     */
    public function setBuckets(array $buckets)
    {
        foreach ($buckets as $bucketKey => $bucketValue) {
            if (is_numeric($bucketKey) && is_string($bucketValue)) {
                $bucketName = $bucketValue;
                $bucketData = array();
            } else {
                $bucketName = $bucketKey;
                $bucketData = $bucketValue;
            }
            $this->addBucket($bucketName, $bucketData);
        }
        return true;
    }

    /**
     * Gets the list of available bucket instances.
     * @return array set of bucket instances.
     */
    public function getBuckets()
    {
        $result = array();
        foreach ($this->_buckets as $bucketName => $bucketData) {
            $result[$bucketName] = $this->getBucket($bucketName);
        }
        return $result;
    }

    /**
     * Gets the bucket intance by name.
     * @param string $bucketName - name of the bucket.
     * @return array set of bucket instances.
     */
    public function getBucket($bucketName)
    {
        if (!array_key_exists($bucketName, $this->_buckets)) {
            throw new CException("Bucket named '{$bucketName}' does not exists in the file storage '" . get_class($this) . "'");
        }
        $bucketData = $this->_buckets[$bucketName];
        if (is_object($bucketData)) {
            $bucketInstance = $bucketData;
        } else {
            $bucketData['name'] = $bucketName;
            $bucketInstance = $this->createBucketInstance($bucketData);
            $this->_buckets[$bucketName] = $bucketInstance;
        }
        return $bucketInstance;
    }

    /**
     * Adds the bucket to the buckets list.
     * @param string $bucketName - name of the bucket.
     * @param mixed $bucketData - bucket instance or configuration array.
     * @return boolean success.
     */
    public function addBucket($bucketName, $bucketData = array())
    {
        if (!is_string($bucketName)) {
            throw new CException('Name of the bucket should be a string!');
        }
        if (is_scalar($bucketData)) {
            throw new CException('Data of the bucket should be an bucket object or configuration array!');
        }
        if (is_object($bucketData)) {
            $bucketData->setName($bucketName);
        }
        $this->_buckets[$bucketName] = $bucketData;
        return true;
    }

    /**
     * Indicates if the bucket has been set up in the storage.
     * @param string $bucketName - name of the bucket.
     * @return boolean success.
     */
    public function hasBucket($bucketName)
    {
        return array_key_exists($bucketName, $this->_buckets);
    }
}