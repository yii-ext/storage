<?php
/**
 * @author Klimov Paul <klimov@zfort.com>
 * @link http://www.zfort.com/
 * @copyright Copyright &copy; 2000-2014 Zfort Group
 * @license http://www.zfort.com/terms-of-use
 */

namespace yii_ext\fileStorage\hub;

use CApplicationComponent;
use CException;
use Yii;
use yii_ext\fileStorage\StorageInterface;

/**
 * Storage introduces the complex file storage, which combines
 * several different file storages in the single facade.
 * While getting the particular bucket from this storage, you may never know
 * it is consist of several ones.
 * Note: to avoid any problems make sure all buckets from all storages have
 * unique name.
 *
 * Configuration example:
 * <code>
 * 'fileStorage' => array(
 *     'class' => 'zfort\file\storage\hub\Storage',
 *     'storages' => array(
 *         array(
 *             'class' => 'zfort\file\storage\filesystem\Storage',
 *             ...
 *             'buckets' => array(
 *                 'fileSystemBucket' => array(...),
 *             ),
 *         ),
 *         array(
 *             'class' => 'zfort\file\storage\ftp\Storage',
 *             ...
 *             'buckets' => array(
 *                 'ftpBucket' => array(...),
 *             ),
 *         ),
 *     )
 * )
 * </code>
 * Usage example:
 * <code>
 * $fileSystemBucket = Yii::app()->fileStorage->getBucket('fileSystemBucket');
 * $ftpBucket = Yii::app()->fileStorage->getBucket('ftpBucket');
 * </code>
 *
 * @property StorageInterface[] $storages public alias of {@link _storages}.
 *
 * @author Klimov Paul <klimov@zfort.com>
 * @version $Id$
 * @package zfort\file\storage\hub
 * @since 1.0
 */
class Storage extends CApplicationComponent implements StorageInterface
{
    /**
     * @var StorageInterface[] list of storages.
     */
    protected $_storages = array();

    /**
     * Creates file storage instance based on the configuration array.
     * @param array $storageConfig - configuration array for the file storage.
     * @return StorageInterface file storage instance.
     */
    protected function createStorageInstance(array $storageConfig)
    {
        return Yii::createComponent($storageConfig);
    }

    /**
     * Sets the list of available file storages.
     * @param StorageInterface[]|array $storages - set of file storage instances or file storage configurations.
     * @return boolean success.
     */
    public function setStorages(array $storages)
    {
        $this->_storages = array();
        foreach ($storages as $storageKey => $storageValue) {
            if (is_numeric($storageKey) && is_string($storageValue)) {
                $storageName = $storageValue;
                $storageData = array();
            } else {
                $storageName = $storageKey;
                $storageData = $storageValue;
            }
            $this->addStorage($storageName, $storageData);
        }
        return true;
    }

    /**
     * Gets the list of available file storage instances.
     * @return StorageInterface[] set of file storage instances.
     */
    public function getStorages()
    {
        $result = array();
        foreach ($this->_storages as $storageName => $storageData) {
            $result[$storageName] = $this->getStorage($storageName);
        }
        return $result;
    }

    /**
     * Gets the file storage instance by name.
     * @param string $storageName - name of the storage.
     * @return StorageInterface file storage instance.
     */
    public function getStorage($storageName)
    {
        if (!array_key_exists($storageName, $this->_storages)) {
            throw new CException("Storage named '{$storageName}' does not exists in the file storage hub '" . get_class($this) . "'");
        }
        $storageData = $this->_storages[$storageName];
        if (is_object($storageData)) {
            $storageInstance = $storageData;
        } else {
            $storageInstance = $this->createStorageInstance($storageData);
            $this->_storages[$storageName] = $storageInstance;
        }
        return $storageInstance;
    }

    /**
     * Adds the storage to the storages list.
     * @param string $storageName - name of the storage.
     * @param mixed $storageData - storage instance or configuration array.
     * @return boolean success.
     */
    public function addStorage($storageName, $storageData = array())
    {
        if (!is_string($storageName)) {
            throw new CException('Name of the storage should be a string!');
        }
        if (is_scalar($storageData) || empty ($storageData)) {
            throw new CException('Data of the storage should be an file storage object or configuration array!');
        }
        $this->_storages[$storageName] = $storageData;
        return true;
    }

    /**
     * Indicates if the storage has been set up in the storage hub.
     * @param string $storageName - name of the storage.
     * @return boolean success.
     */
    public function hasStorage($storageName)
    {
        return array_key_exists($storageName, $this->_storages);
    }

    /**
     * Returns the default file storage, meaning the first one in the {@link storages} list.
     * @throws CException on failure.
     * @return StorageInterface file storage instance.
     */
    protected function getDefaultStorage()
    {
        $storageList = $this->_storages;
        $storageNames = array_keys($storageList);
        $defaultStorageName = array_shift($storageNames);
        if (empty($defaultStorageName)) {
            throw new CException('Unable to determine default storage in the hub!');
        }
        $storage = $this->getStorage($defaultStorageName);
        return $storage;
    }

    /**
     * Sets the list of available buckets.
     * @param array $buckets - set of bucket instances or bucket configurations.
     * @return boolean success.
     */
    public function setBuckets(array $buckets)
    {
        $storage = $this->getDefaultStorage();
        return $storage->setBuckets($buckets);
    }

    /**
     * Gets the list of available bucket instances.
     * @return array set of bucket instances.
     */
    public function getBuckets()
    {
        $buckets = array();
        foreach ($this->getStorages() as $storage) {
            $buckets = array_merge($storage->getBuckets(), $buckets);
        }
        return $buckets;
    }

    /**
     * Gets the bucket instance by name.
     * @param string $bucketName - name of the bucket.
     * @return array set of bucket instances.
     */
    public function getBucket($bucketName)
    {
        $storagesList = $this->_storages;
        foreach ($storagesList as $storageName => $storageData) {
            $storage = $this->getStorage($storageName);
            if ($storage->hasBucket($bucketName)) {
                return $storage->getBucket($bucketName);
            }
        }
        throw new CException("Bucket named '{$bucketName}' does not exists in any file storage of the hub '" . get_class($this) . "'");
    }

    /**
     * Adds the bucket to the buckets list.
     * @param string $bucketName - name of the bucket.
     * @param mixed $bucketData - bucket instance or configuration array.
     * @return boolean success.
     */
    public function addBucket($bucketName, $bucketData = array())
    {
        $storage = $this->getDefaultStorage();
        return $storage->addBucket($bucketName, $bucketData);
    }

    /**
     * Indicates if the bucket has been set up in the storage.
     * @param string $bucketName - name of the bucket.
     * @return boolean success.
     */
    public function hasBucket($bucketName)
    {
        $storagesList = $this->_storages;
        foreach ($storagesList as $storageName => $storageData) {
            $storage = $this->getStorage($storageName);
            if ($storage->hasBucket($bucketName)) {
                return true;
            }
        }
        return false;
    }
}