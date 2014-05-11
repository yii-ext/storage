<?php
/**
 * @author Klimov Paul <klimov@zfort.com>
 * @link http://www.zfort.com/
 * @copyright Copyright &copy; 2000-2014 Zfort Group
 * @license http://www.zfort.com/terms-of-use
 */

namespace yii_ext\storage;

/**
 * StorageInterface is an interface for the all file storages.
 * File storage should be a hub for the {@link IQsFileStorageBucket} instances.
 *
 * @author Klimov Paul <klimov@zfort.com>
 * @version $Id$
 * @package yii_ext\storage
 * @since 1.0
 */
interface StorageInterface
{
    /**
     * Sets the list of available buckets.
     * @param array $buckets - set of bucket instances or bucket configurations.
     * @return boolean success.
     */
    public function setBuckets(array $buckets);

    /**
     * Gets the list of available bucket instances.
     * @return BucketInterface[] set of bucket instances.
     */
    public function getBuckets();

    /**
     * Gets the bucket instance by name.
     * @param string $bucketName - name of the bucket.
     * @return BucketInterface bucket instance.
     */
    public function getBucket($bucketName);

    /**
     * Adds the bucket to the buckets list.
     * @param string $bucketName - name of the bucket.
     * @param mixed $bucketData - bucket instance or configuration array.
     * @return boolean success.
     */
    public function addBucket($bucketName, $bucketData = array());

    /**
     * Indicates if the bucket has been set up in the storage.
     * @param string $bucketName - name of the bucket.
     * @return boolean success.
     */
    public function hasBucket($bucketName);
}