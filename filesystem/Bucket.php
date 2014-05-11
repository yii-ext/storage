<?php
/**
 * @author Klimov Paul <klimov@zfort.com>
 * @link http://www.zfort.com/
 * @copyright Copyright &copy; 2000-2014 Zfort Group
 * @license http://www.zfort.com/terms-of-use
 */

namespace yii_ext\fileStorage\filesystem;

use CException;
use CLogger;
use yii_ext\fileStorage\BucketSubDirTemplate;

/**
 * Bucket introduces the file storage bucket based simply on the OS file system.
 *
 * Configuration example:
 * <code>
 * 'fileStorage' => array(
 *     'class' => 'zfort\file\storage\filesystem\Storage',
 *     'basePath' => '/home/www/files',
 *     'baseUrl' => 'http://www.mydomain.com/files',
 *     'filePermission' => 0777,
 *     'buckets' => array(
 *         'tempFiles' => array(
 *             'baseSubPath' => 'temp',
 *             'fileSubDirTemplate' => '{^name}/{^^name}',
 *         ),
 *         'imageFiles' => array(
 *             'baseSubPath' => 'image',
 *             'fileSubDirTemplate' => '{ext}/{^name}/{^^name}',
 *         ),
 *     )
 * )
 * </code>
 *
 * @see Storage
 *
 * @property string $baseSubPath public alias of {@link _baseSubPath}.
 * @method Storage getStorage()
 *
 * @author Klimov Paul <klimov@zfort.com>
 * @version $Id$
 * @package zfort\file\storage\filesystem
 * @since 1.0
 */
class Bucket extends BucketSubDirTemplate
{
    /**
     * @var string sub path in the directory specified by {@link QsFileStorageFileSystem::basePath}.
     */
    protected $_baseSubPath = '';

    // Set / Get :

    public function setBaseSubPath($baseSubPath)
    {
        if (!is_string($baseSubPath)) {
            throw new CException('"' . get_class($this) . '::baseSubPath" should be a string!');
        }
        $this->_baseSubPath = $baseSubPath;
        return true;
    }

    public function getBaseSubPath()
    {
        if (empty($this->_baseSubPath)) {
            $this->initBaseSubPath();
        }
        return $this->_baseSubPath;
    }

    /**
     * Initializes base sub path with default value.
     * @return boolean success.
     */
    protected function initBaseSubPath()
    {
        $this->_baseSubPath = $this->getName();
        return true;
    }

    /**
     * Returns the bucket full base path.
     * This path is based on {@link QsFileStorageFileSystem::basePath} and
     * {@link baseSubPath}.
     * @return string bucket full base path.
     */
    public function getFullBasePath()
    {
        $fullBasePath = $this->getStorage()->getBasePath() . '/' . $this->getBaseSubPath();
        $fullBasePath = rtrim($fullBasePath, '/');
        return $fullBasePath;
    }

    /**
     * Gets the full file system name of the file.
     * @param string $fileName - self name of the file.
     * @return string full file name.
     */
    public function getFullFileName($fileName)
    {
        return $this->composeFullFileName($fileName);
    }

    /**
     * Make sure the bucket base path exists and writeable.
     * @throws CException if fails.
     * @return string bucket full base path.
     */
    protected function resolveFullBasePath()
    {
        if (!empty($this->_internalCache['resolvedFullBasePath'])) {
            return $this->_internalCache['resolvedFullBasePath'];
        }
        $fullBasePath = $this->getFullBasePath();
        $this->resolvePath($fullBasePath);
        $this->_internalCache['resolvedFullBasePath'] = $fullBasePath;
        return $fullBasePath;
    }

    /**
     * Composes the full file system name of the file.
     * @param string $fileName - self name of the file.
     * @return string full file name.
     */
    protected function composeFullFileName($fileName)
    {
        return $this->resolveFullBasePath() . '/' . $this->getFileNameWithSubDir($fileName);
    }

    /**
     * Composes the full file system name of the file, making sure its container directory exists.
     * @param string $fileName - self name of the file.
     * @return string full file name.
     */
    protected function resolveFullFileName($fileName)
    {
        $fullFileName = $this->composeFullFileName($fileName);
        $fullFilePath = dirname($fullFileName);
        $this->resolvePath($fullFilePath);
        return $fullFileName;
    }

    /**
     * Resolves file path, making sure it exists and writeable.
     * @param string $path file path to be resolved.
     * @return boolean success.
     * @throws CException on failure.
     */
    protected function resolvePath($path)
    {
        if (!file_exists($path)) {
            $dirPermission = $this->getStorage()->getFilePermission();
            $this->log("creating file path '{$path}'");
            $oldUmask = umask(0);
            @mkdir($path, $dirPermission, true);
            umask($oldUmask);
            if (!file_exists($path)) {
                throw new CException("Unable to create path '{$path}'!");
            }
        }
        if (!is_dir($path)) {
            throw new CException("Path '{$path}' is not a directory!");
        } elseif (!is_writable($path)) {
            throw new CException("Path: '{$path}' should be writeable!");
        }
        return true;
    }

    /**
     * Gets full file name of the file inside the bucket or inside the other bucket in
     * the same storage.
     * File can be passed as string, which means the internal bucket file,
     * or as an array of 2 elements: first one - the name of the bucket,
     * the second one - name of the file in this bucket
     * @param mixed $fileReference - this bucket existing file name or array reference to another bucket file name.
     * @return string full file name.
     */
    protected function getFullFileNameByReference($fileReference)
    {
        if (is_array($fileReference)) {
            list($bucketName, $fileName) = $fileReference;
            $bucket = $this->getStorage()->getBucket($bucketName);
            $fullFileName = $bucket->getFullFileName($fileName);
        } else {
            $fullFileName = $this->getFullFileName($fileReference);
        }
        return $fullFileName;
    }

    /**
     * Creates this bucket.
     * @return boolean success.
     */
    public function create()
    {
        $this->resolveFullBasePath();
        return true;
    }

    /**
     * Destroys this bucket.
     * @return boolean success.
     */
    public function destroy()
    {
        $fullBasePath = $this->resolveFullBasePath();
        $command = 'rm -r ' . escapeshellarg($fullBasePath);
        exec($command);
        $this->log("bucket has been destroyed at base path '{$fullBasePath}'");
        $this->clearInternalCache();
        return true;
    }

    /**
     * Checks is bucket exists.
     * @return boolean success.
     */
    public function exists()
    {
        $fullBasePath = $this->getFullBasePath();
        return file_exists($fullBasePath);
    }

    /**
     * Saves content as new file.
     * @param string $fileName - new file name.
     * @param string $content - new file content.
     * @return boolean success.
     */
    public function saveFileContent($fileName, $content)
    {
        $fullFileName = $this->resolveFullFileName($fileName);
        $writtenBytesCount = file_put_contents($fullFileName, $content);
        $result = ($writtenBytesCount>0);
        if ($result) {
            $this->log("file '{$fullFileName}' has been saved");
            chmod($fullFileName, $this->getStorage()->getFilePermission() );
        } else {
            $this->log("Unable to save file '{$fullFileName}'!", CLogger::LEVEL_ERROR);
        }
        return $result;
    }

    /**
     * Returns content of an existing file.
     * @param string $fileName - new file name.
     * @return string $content - file content.
     */
    public function getFileContent($fileName)
    {
        $fullFileName = $this->resolveFullFileName($fileName);
        $this->log("content of file '{$fullFileName}' has been returned");
        return file_get_contents($fullFileName);
    }

    /**
     * Deletes an existing file.
     * @param string $fileName - new file name.
     * @return boolean success.
     */
    public function deleteFile($fileName)
    {
        $fullFileName = $this->resolveFullFileName($fileName);
        if (file_exists($fullFileName)) {
            $result = unlink($fullFileName);
            if ($result) {
                $this->log("file '{$fullFileName}' has been deleted");
            } else {
                $this->log("unable to delete file '{$fullFileName}'!", CLogger::LEVEL_ERROR);
            }
            return $result;
        }
        $this->log("unable to delete file '{$fullFileName}': file does not exist");
        return true;
    }

    /**
     * Checks if the file exists in the bucket.
     * @param string $fileName - searching file name.
     * @return boolean file exists.
     */
    public function fileExists($fileName)
    {
        $fullFileName = $this->composeFullFileName($fileName);
        return file_exists($fullFileName);
    }

    /**
     * Copies file from the OS file system into the bucket.
     * @param string $srcFileName - OS full file name.
     * @param string $fileName - new bucket file name.
     * @return boolean success.
     */
    public function copyFileIn($srcFileName, $fileName)
    {
        $fullFileName = $this->resolveFullFileName($fileName);
        $result = copy($srcFileName, $fullFileName);
        if ($result) {
            $this->log("file '{$srcFileName}' has been copied to '{$fullFileName}'");
            chmod($fullFileName, $this->getStorage()->getFilePermission());
        } else {
            $this->log("unable to copy file from '{$srcFileName}' to '{$fullFileName}'!", CLogger::LEVEL_ERROR);
        }
        return $result;
    }

    /**
     * Copies file from the bucket into the OS file system.
     * @param string $fileName - bucket existing file name.
     * @param string $destFileName - new OS full file name.
     * @return boolean success.
     */
    public function copyFileOut($fileName, $destFileName)
    {
        $fullFileName = $this->resolveFullFileName($fileName);
        $result = copy($fullFileName, $destFileName);
        if ($result) {
            $this->log("file '{$fullFileName}' has been copied to '{$destFileName}'");
        } else {
            $this->log("unable to copy file from '{$fullFileName}' to '{$destFileName}'!", CLogger::LEVEL_ERROR);
        }
        return $result;
    }

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
    public function copyFileInternal($srcFile, $destFile)
    {
        $srcFullFileName = $this->getFullFileNameByReference($srcFile);
        $destFullFileName = $this->getFullFileNameByReference($destFile);
        $this->resolvePath(dirname($destFullFileName));
        $result = copy($srcFullFileName, $destFullFileName);
        if ($result) {
            $this->log("file '{$srcFullFileName}' has been copied to '{$destFullFileName}'");
            chmod($destFullFileName, $this->getStorage()->getFilePermission());
        } else {
            $this->log("unable to copy file from '{$srcFullFileName}' to '{$destFullFileName}'!", CLogger::LEVEL_ERROR);
        }
        return $result;
    }

    /**
     * Copies file from the OS file system into the bucket and
     * deletes the source file.
     * @param string $srcFileName - OS full file name.
     * @param string $fileName - new bucket file name.
     * @return boolean success.
     */
    public function moveFileIn($srcFileName, $fileName)
    {
        return ($this->copyFileIn($srcFileName, $fileName) && unlink($srcFileName));
    }

    /**
     * Copies file from the bucket into the OS file system and
     * deletes the source bucket file.
     * @param string $fileName - bucket existing file name.
     * @param string $destFileName - new OS full file name.
     * @return boolean success.
     */
    public function moveFileOut($fileName, $destFileName)
    {
        return ($this->copyFileOut($fileName, $destFileName) && unlink($this->resolveFullFileName($fileName)));
    }

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
    public function moveFileInternal($srcFile, $destFile)
    {
        $result = $this->copyFileInternal($srcFile, $destFile);
        if ($result) {
            $fullSrcFileName = $this->getFullFileNameByReference($srcFile);
            $result = ($result && unlink($fullSrcFileName));
        }
        return $result;
    }

    /**
     * Gets web URL of the file.
     * @param string $fileName - self file name.
     * @return string file web URL.
     */
    public function getFileUrl($fileName)
    {
        $baseUrl = $this->getStorage()->getBaseUrl();
        $baseUrl .= '/' . $this->getBaseSubPath();
        $fileSubDir = $this->getFileSubDir($fileName);
        if (!empty($fileSubDir)) {
            $baseUrl .= '/' . $fileSubDir;
        }
        $fileUrl = $baseUrl . '/' . $fileName;
        return $fileUrl;
    }
}