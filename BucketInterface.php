<?php
/**
 * @author Klimov Paul <klimov@zfort.com>
 * @link http://www.zfort.com/
 * @copyright Copyright &copy; 2000-2014 Zfort Group
 * @license http://www.zfort.com/terms-of-use
 */

namespace yii_ext\storage;

/**
 * BucketInterface is an interface for the all file storage buckets.
 * All buckets should be controlled by the instance of {@link StorageInterface}.
 *
 * @author Klimov Paul <klimov@zfort.com>
 * @version $Id$
 * @package yii_ext\storage
 * @since 1.0
 */
interface BucketInterface
{
    /**
     * Sets bucket name.
     * @param string $name - bucket name.
     * @return boolean success.
     */
    public function setName($name);

    /**
     * Gets current bucket name.
     * @return string $name - bucket name.
     */
    public function getName();

    /**
     * Sets bucket file storage.
     * @param StorageInterface $storage - file storage.
     * @return boolean success.
     */
    public function setStorage(StorageInterface $storage);

    /**
     * Gets bucket file storage.
     * @return StorageInterface - bucket file storage.
     */
    public function getStorage();

    /**
     * Creates this bucket.
     * @return boolean success.
     */
    public function create();

    /**
     * Destroys this bucket.
     * @return boolean success.
     */
    public function destroy();

    /**
     * Checks is bucket exists.
     * @return boolean success.
     */
    public function exists();

    /**
     * Saves content as new file.
     * @param string $fileName - new file name.
     * @param string $content - new file content.
     * @return boolean success.
     */
    public function saveFileContent($fileName, $content);

    /**
     * Returns content of an existing file.
     * @param string $fileName - new file name.
     * @return string $content - file content.
     */
    public function getFileContent($fileName);

    /**
     * Deletes an existing file.
     * @param string $fileName - new file name.
     * @return boolean success.
     */
    public function deleteFile($fileName);

    /**
     * Checks if the file exists in the bucket.
     * @param string $fileName - searching file name.
     * @return boolean file exists.
     */
    public function fileExists($fileName);

    /**
     * Copies file from the OS file system into the bucket.
     * @param string $srcFileName - OS full file name.
     * @param string $fileName - new bucket file name.
     * @return boolean success.
     */
    public function copyFileIn($srcFileName, $fileName);

    /**
     * Copies file from the bucket into the OS file system.
     * @param string $fileName - bucket existing file name.
     * @param string $destFileName - new OS full file name.
     * @return boolean success.
     */
    public function copyFileOut($fileName, $destFileName);

    /**
     * Copies file inside this bucket or between this bucket and another
     * bucket of this file storage.
     * File can be passed as string, which means the internal bucket file,
     * or as an array of 2 elements: first one - the name of the bucket,
     * the second one - name of the file in this bucket
     * @param mixed $srcFile - this bucket existing file name or array reference to another bucket file name.
     * @param mixed $destFile - this bucket existing file name or array reference to another bucket file name.
     * @return boolean success.
     */
    public function copyFileInternal($srcFile, $destFile);

    /**
     * Copies file from the OS file system into the bucket and
     * deletes the source file.
     * @param string $srcFileName - OS full file name.
     * @param string $fileName - new bucket file name.
     * @return boolean success.
     */
    public function moveFileIn($srcFileName, $fileName);

    /**
     * Copies file from the bucket into the OS file system and
     * deletes the source bucket file.
     * @param string $fileName - bucket existing file name.
     * @param string $destFileName - new OS full file name.
     * @return boolean success.
     */
    public function moveFileOut($fileName, $destFileName);

    /**
     * Moves file inside this bucket or between this bucket and another
     * bucket of this file storage.
     * File can be passed as string, which means the internal bucket file,
     * or as an array of 2 elements: first one - the name of the bucket,
     * the second one - name of the file in this bucket
     * @param mixed $srcFile - this bucket existing file name or array reference to another bucket file name.
     * @param mixed $destFile - this bucket existing file name or array reference to another bucket file name.
     * @return boolean success.
     */
    public function moveFileInternal($srcFile, $destFile);

    /**
     * Gets web URL of the file.
     * @param string $fileName - self file name.
     * @return string file web URL.
     */
    public function getFileUrl($fileName);
}