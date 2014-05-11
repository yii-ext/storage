<?php
/**
 * @author Klimov Paul <klimov@zfort.com>
 * @link http://www.zfort.com/
 * @copyright Copyright &copy; 2000-2014 Zfort Group
 * @license http://www.zfort.com/terms-of-use
 */

namespace yii_ext\storage\behaviors;

use CActiveRecord;
use CBehavior;
use CEvent;
use CException;
use CFileHelper;
use CUploadedFile;
use Yii;
use yii_ext\storage\BucketInterface;
use yii_ext\storage\StorageInterface;

/**
 * Behavior for the {@link CActiveRecord}, which allows to save a single file per each table record.
 * Behavior tracks the file extension and manage file version in order to prevent cache problems.
 * Due to this the database table, which the model refers to, must contain fields {@link fileExtensionAttributeName} and {@link fileVersionAttributeName}.
 * On the model save behavior will automatically search for the attached file in $_FILES.
 * However you can manipulate attached file using property {@link uploadedFile}.
 * For the tabular file input use {@link fileTabularInputIndex} property.
 *
 * Note: you can always use {@link saveFile()} method to attach any file (not just uploaded one) to the model.
 *
 * Attention: this extension requires the extension "yii_ext\storage" to be attached to the application!
 * Files will be saved using file storage component.
 *
 * @see IQsFileStorage
 * @see IQsFileStorageBucket
 *
 * @property string $filePropertyName public alias of {@link _filePropertyName}.
 * @property string $storageComponentName public alias of {@link _storageComponentName}.
 * @property string $storageBucketName public alias of {@link _storageBucketName}.
 * @property string $subDirTemplate public alias of {@link _subDirTemplate}.
 * @property string $fileExtensionAttributeName public alias of {@link _fileExtensionAttributeName}.
 * @property string $fileVersionAttributeName public alias of {@link _fileVersionAttributeName}.
 * @property integer $fileTabularInputIndex public alias of {@link _fileTabularInputIndex}.
 * @property CUploadedFile $uploadedFile public alias of {@link _uploadedFile}.
 * @property string $defaultFileUrl public alias of {@link _defaultFileUrl}.
 * @method CActiveRecord getOwner()
 *
 * @author Klimov Paul <klimov@zfort.com>
 * @version $Id$
 * @package zfort\db\ar\behaviors
 * @since 1.0
 */
class File extends CBehavior
{
    /**
     * @var string name of virual model's attribute, which will be used
     * to fetch file uploaded from the web form.
     * Use value of this attribute to create web form file input field.
     */
    protected $_filePropertyName = 'file';
    /**
     * @var string name of the file storage application component.
     */
    protected $_storageComponentName = 'storage';
    /**
     * @var string name of the file storage bucket, which stores the related files.
     * If empty it will be generated automatically using owner class name and {@link filePropertyName}.
     */
    protected $_storageBucketName = '';
    /**
     * @var string template of all sub directories, which will store a particular
     * model instance's files. Value of this parameter will be parsed per each model instance.
     * You can use model attribute names to create sub directories, for example place all transformed
     * files in the subfolder with the name of model id. To use a dynamic value of attribute
     * place attribute name in curly brackets, for example: {id}.
     * You may also specify special placeholders:
     * {pk} - resolved as primary key value of the owner model,
     * {__model__} - resolved as class name of the owner model,
     * {__file__} - resolved as value of {@link filePropertyName}.
     * You may place symbols "^" before any placeholder name, such placeholder will be resolved as single
     * symbol of the normal value. Number of symbol determined by count of "^".
     * For example:
     * if model id equal to 54321, placeholder {^id} will be resolved as "5", {^^id} - as "4" and so on.
     * Example value:
     * '{__model__}/{__file__}/{group_id}/{^pk}/{pk}'
     */
    protected $_subDirTemplate = '{^pk}/{pk}';
    /**
     * @var string name of model's attribute, which will be used to store file extension.
     * Corresponding model's attribute should be a string type.
     */
    protected $_fileExtensionAttributeName = 'fileExtension';
    /**
     * @var string name of model's attribute, which will be used to store file version number.
     * Corresponding model's attribute should be a string or integer type.
     */
    protected $_fileVersionAttributeName = 'fileVersion';
    /**
     * @var integer index of the HTML input file field in case of tabular input (input name has format "ModelName[$i][file]").
     * Note: after owner is saved this property will be reset.
     */
    protected $_fileTabularInputIndex = null;
    /**
     * @var CUploadedFile instance of {@link CUploadedFile}, allows to save file,
     * passed through the web form.
     */
    protected $_uploadedFile = null;
    /**
     * @var string URL which is used to set up web links, which will be returned, if requested file does not exists.
     * For example:
     * 'http://www.myproject.com/materials/default/image.jpg'
     */
    protected $_defaultFileUrl = '';
    /**
     * @var boolean indicates if behavior will attempt to fetch uploaded file automatically
     * from the HTTP request.
     */
    public $autoFetchUploadedFile = true;

    // Set / Get:

    public function setFilePropertyName($filePropertyName) {
        if (!is_string($filePropertyName)) {
            return false;
        }
        $this->_filePropertyName = $filePropertyName;
        return true;
    }

    public function getFilePropertyName()
    {
        return $this->_filePropertyName;
    }

    public function setFileStorageComponentName($storageComponentName)
    {
        if (!is_string($storageComponentName)) {
            throw new CException('"' . get_class($this) . '::storageComponentName" should be a string!');
        }
        $this->_storageComponentName = $storageComponentName;
        return true;
    }

    public function getFileStorageComponentName()
    {
        return $this->_storageComponentName;
    }

    public function setFileStorageBucketName($storageBucketName)
    {
        if (!is_string($storageBucketName)) {
            throw new CException('"' . get_class($this) . '::storageBucketName" should be a string!');
        }
        $this->_storageBucketName = $storageBucketName;
        return true;
    }

    public function getFileStorageBucketName()
    {
        if (empty($this->_storageBucketName)) {
            $this->initFileStorageBucketName();
        }
        return $this->_storageBucketName;
    }

    public function setSubDirTemplate($subDirTemplate)
    {
        if (!is_string($subDirTemplate)) {
            return false;
        }
        $this->_subDirTemplate = $subDirTemplate;
        return true;
    }

    public function getSubDirTemplate()
    {
        return $this->_subDirTemplate;
    }

    public function setFileExtensionAttributeName($fileExtensionAttributeName)
    {
        if (!is_string($fileExtensionAttributeName)) {
            return false;
        }
        $this->_fileExtensionAttributeName = $fileExtensionAttributeName;
        return true;
    }

    public function getFileExtensionAttributeName()
    {
        return $this->_fileExtensionAttributeName;
    }

    public function setFileVersionAttributeName($fileVersionAttributeName)
    {
        if (!is_string($fileVersionAttributeName)) {
            return false;
        }
        $this->_fileVersionAttributeName = $fileVersionAttributeName;
        return true;
    }

    public function getFileVersionAttributeName()
    {
        return $this->_fileVersionAttributeName;
    }

    public function setFileTabularInputIndex($fileTabularInputIndex)
    {
        $this->_fileTabularInputIndex = $fileTabularInputIndex;
        return true;
    }

    public function getFileTabularInputIndex()
    {
        return $this->_fileTabularInputIndex;
    }

    public function setUploadedFile($uploadedFile)
    {
        if (is_string($uploadedFile)) {
            return $this->initUploadedFile($uploadedFile);
        }
        if (!is_a($uploadedFile, 'CUploadedFile')) {
            return false;
        }
        $this->_uploadedFile = $uploadedFile;
        return true;
    }

    public function getUploadedFile()
    {
        if (!is_object($this->_uploadedFile)) {
            $this->initUploadedFile();
        }
        return $this->_uploadedFile;
    }

    public function setDefaultFileUrl($defaultFileWebSrc)
    {
        $this->_defaultFileUrl = $defaultFileWebSrc;
        return true;
    }

    public function getDefaultFileUrl()
    {
        return $this->_defaultFileUrl;
    }

    /**
     * Returns the file storage bucket for the files by name given with {@link storageBucketName}.
     * If no bucket exists attempts to create it.
     * @throws CException if unable to find file storage component
     * @return BucketInterface file storage bucket instance.
     */
    public function getFileStorageBucket()
    {
        /* @var StorageInterface $storage */
        $storage = Yii::app()->getComponent($this->getFileStorageComponentName());
        if (!is_object($storage)) {
            throw new CException('Unable to find file storage application component "' . $this->getFileStorageComponentName() . '"');
        }
        $bucketName = $this->getFileStorageBucketName();
        if (!$storage->hasBucket($bucketName)) {
            $storage->addBucket($bucketName, array());
        }
        return $storage->getBucket($bucketName);
    }

    /**
     * Initializes {@link storageBucketName} using
     * owner class name and {@link filePropertyName}.
     * @return boolean success.
     */
    protected function initFileStorageBucketName()
    {
        $storageBucketName = str_replace('\\', '_', get_class($this->getOwner())) . ucfirst($this->getFilePropertyName());
        $this->_storageBucketName = $storageBucketName;
        return true;
    }

    // Property Access Extension:

    public function __set($name, $value)
    {
        try {
            parent::__set($name, $value);
        } catch (CException $exception) {
            if ($name == $this->getFilePropertyName()) {
                $this->setUploadedFile($value);
            } else {
                throw $exception;
            }
        }
    }

    public function __get($name)
    {
        try {
            return parent::__get($name);
        } catch (CException $exception) {
            if ($name == $this->getFilePropertyName()) {
                return $this->getUploadedFile();
            } else {
                throw $exception;
            }
        }
    }

    public function canGetProperty($name)
    {
        $result = parent::canGetProperty($name);
        if (!$result) {
            return ($name == $this->getFilePropertyName());
        }
        return $result;
    }

    public function canSetProperty($name)
    {
        $result = parent::canSetProperty($name);
        if (!$result) {
            return ($name == $this->getFilePropertyName());
        }
        return $result;
    }

    // SubDir Template:

    /**
     * Gets file storage sub dirs path, resolving {@link subDirTemplate}.
     * @return string actual sub directory string.
     */
    public function getActualSubDir()
    {
        $subDirTemplate = $this->getSubDirTemplate();
        if (empty($subDirTemplate)) {
            return $subDirTemplate;
        }
        $result = preg_replace_callback('/{(\^*(\w+))}/', array($this, 'getSubDirPlaceholderValue'), $subDirTemplate);
        return $result;
    }

    /**
     * Internal callback function for {@link getActualSubDir}.
     * @param array $matches - set of regular expression matches.
     * @return string replacement for the match.
     */
    protected function getSubDirPlaceholderValue($matches)
    {
        $placeholderName = $matches[1];
        $placeholderPartSymbolPosition = strspn($placeholderName, '^') - 1;
        if ($placeholderPartSymbolPosition >= 0) {
            $placeholderName = $matches[2];
        }

        switch ($placeholderName) {
            case 'pk': {
                $placeholderValue = $this->getPrimaryKeyStringValue();
                break;
            }
            case '__model__': {
                $owner = $this->getOwner();
                $placeholderValue = str_replace('\\', '_', get_class($owner));
                break;
            }
            case '__file__': {
                $placeholderValue = $this->getFilePropertyName();
                break;
            }
            default: {
                $owner = $this->getOwner();
                try {
                    $placeholderValue = $owner->$placeholderName;
                } catch (CException $exception) {
                    $placeholderValue = $placeholderName;
                }
            }
        }

        if ($placeholderPartSymbolPosition >= 0) {
            if ($placeholderPartSymbolPosition < strlen($placeholderValue)) {
                $placeholderValue = substr($placeholderValue, $placeholderPartSymbolPosition, 1);
            } else {
                $placeholderValue = '0';
            }
        }

        return $placeholderValue;
    }

    // Service:

    /**
     * Creates string representation of owner model primary key value,
     * handles case when primary key is complex and consist of several fields.
     * @return string representation of owner model primary key value.
     */
    protected function getPrimaryKeyStringValue()
    {
        $owner = $this->getOwner();
        $primaryKey = $owner->getPrimaryKey();
        if (is_array($primaryKey)) {
            $result = implode('_', $primaryKey);
        } else {
            $result = $primaryKey;
        }
        return $result;
    }

    /**
     * Creates base part of the file name.
     * This value will be append with the version and extension for the particular file.
     * @return string file name's base part.
     */
    protected function getFileBaseName()
    {
        $fileBaseName = $this->getPrimaryKeyStringValue();
        return $fileBaseName;
    }

    /**
     * Returns current version value of the model's file.
     * @return integer current version of model's file.
     */
    public function getFileVersionCurrent()
    {
        $owner = $this->getOwner();
        return $owner->getAttribute($this->getFileVersionAttributeName());
    }

    /**
     * Returns next version value of the model's file.
     * @return integer next version of model's file.
     */
    public function getFileVersionNext()
    {
        return $this->getFileVersionCurrent() + 1;
    }

    /**
     * Creates file itself name (without path) including version and extension.
     * @param integer $fileVersion file version number.
     * @param string $fileExtension file extension.
     * @return string file self name.
     */
    public function getFileSelfName($fileVersion = null, $fileExtension = null)
    {
        $owner = $this->getOwner();
        if (is_null($fileVersion)) {
            $fileVersion = $this->getFileVersionCurrent();
        }
        if (is_null($fileExtension)) {
            $fileExtension = $owner->getAttribute($this->getFileExtensionAttributeName());
        }
        return $this->getFileBaseName() . '_' . $fileVersion . '.' . $fileExtension;
    }

    /**
     * Creates the file name in the file storage.
     * This name contains the sub directory, resolved by {@link subDirTemplate}.
     * @param integer $fileVersion file version number.
     * @param string $fileExtension file extension.
     * @return string file full name.
     */
    public function getFileFullName($fileVersion = null, $fileExtension = null)
    {
        $fileName = $this->getFileSelfName($fileVersion, $fileExtension);
        $subDir = $this->getActualSubDir();
        if (!empty($subDir)) {
            $fileName = $subDir . DIRECTORY_SEPARATOR . $fileName;
        }
        return $fileName;
    }

    // Main File Operations:

    /**
     * Associate new file with the owner model.
     * This method will determine new file version and extension, and will update the owner
     * model correspondingly.
     * @param string|CUploadedFile $sourceFileNameOrUploadedFile file system path to source file or {@link CUploadedFile} instance.
     * @param boolean $deleteSourceFile determines would the source file be deleted in the process or not,
     * if null given file will be deleted if it was uploaded via POST.
     * @return boolean save success.
     */
    public function saveFile($sourceFileNameOrUploadedFile, $deleteSourceFile = null)
    {
        $this->deleteFile();

        $fileVersion = $this->getFileVersionNext();

        if (is_object($sourceFileNameOrUploadedFile)) {
            $sourceFileName = $sourceFileNameOrUploadedFile->getTempName();
            $fileExtension = CFileHelper::getExtension($sourceFileNameOrUploadedFile->getName());
        } else {
            $sourceFileName = $sourceFileNameOrUploadedFile;
            $fileExtension = CFileHelper::getExtension($sourceFileName);
        }

        $result = $this->newFile($sourceFileName, $fileVersion, $fileExtension);

        if ($result) {
            if ($deleteSourceFile === null) {
                $deleteSourceFile = is_uploaded_file($sourceFileName);
            }
            if ($deleteSourceFile) {
                unlink($sourceFileName);
            }

            $owner = $this->getOwner();

            $attributes = array(
                $this->_fileVersionAttributeName => $fileVersion,
                $this->_fileExtensionAttributeName => $fileExtension
            );
            $owner->updateByPk($owner->getPrimaryKey(), $attributes);

            $owner->setAttribute($this->_fileVersionAttributeName, $fileVersion);
            $owner->setAttribute($this->_fileExtensionAttributeName, $fileExtension);
        }

        return $result;
    }

    /**
     * Creates the file for the model from the source file.
     * File version and extension are passed to this method.
     * @param string $sourceFileName - source full file name.
     * @param integer $fileVersion - file version number.
     * @param string $fileExtension - file extension.
     * @return boolean success.
     */
    protected function newFile($sourceFileName, $fileVersion, $fileExtension)
    {
        $fileFullName = $this->getFileFullName($fileVersion, $fileExtension);
        $storageBucket = $this->getFileStorageBucket();
        return $storageBucket->copyFileIn($sourceFileName, $fileFullName);
    }

    /**
     * Removes file associated with the owner model.
     * @return boolean success.
     */
    public function deleteFile()
    {
        return $this->unlinkFile();
    }

    /**
     * Deletes file associated with the model without any checks.
     * @return boolean success.
     */
    protected function unlinkFile()
    {
        $storageBucket = $this->getFileStorageBucket();
        $fileName = $this->getFileFullName();
        if ($storageBucket->fileExists($fileName)) {
            return $storageBucket->deleteFile($fileName);
        }
        return true;
    }

    /**
     * Finds the uploaded through the web file, creating {@link CUploadedFile} instance.
     * If parameter $fullFileName is passed, creates a mock up instance of {@link CUploadedFile} from the local file,
     * passed with this parameter.
     * @param $fullFileName - source full file name for the {@link CUploadedFile} mock up.
     * @return boolean success.
     */
    protected function initUploadedFile($fullFileName = null)
    {
        if (is_string($fullFileName) && !empty($fullFileName)) {
            $this->_uploadedFile = new CUploadedFile(basename($fullFileName), $fullFileName, CFileHelper::getMimeType($fullFileName), filesize($fullFileName), UPLOAD_ERR_OK);
        } elseif ($this->autoFetchUploadedFile) {
            $owner = $this->getOwner();
            $fileAttributeName = $this->getFilePropertyName();
            $tabularInputIndex = $this->getFileTabularInputIndex();
            if ($tabularInputIndex !== null) {
                $fileAttributeName = "[{$tabularInputIndex}]" . $fileAttributeName;
            }
            $uploadedFile = CUploadedFile::getInstance($owner, $fileAttributeName);
            if (is_object($uploadedFile)) {
                if (!$uploadedFile->getHasError() && !file_exists($uploadedFile->getTempName())) {
                    // uploaded file has been already processed:
                    $this->_uploadedFile = null;
                } else {
                    $this->_uploadedFile = $uploadedFile;
                }
            } else {
                $this->_uploadedFile = null;
            }
        }
        return true;
    }

    /**
     * Unsets {@link uploadedFile} internal property.
     * @return boolean success.
     */
    protected function clearUploadedFile()
    {
        $this->_uploadedFile = null;
        return true;
    }

    /**
     * Unsets {@link fileTabularInputIndex} internal property.
     * @return boolean success.
     */
    protected function clearFileTabularInputIndex()
    {
        $this->_fileTabularInputIndex = null;
        return true;
    }

    // File Interface Function Shortcuts:

    /**
     * Checks if file related to the model exists.
     * @return boolean file exists.
     */
    public function fileExists()
    {
        $storageBucket = $this->getFileStorageBucket();
        return $storageBucket->fileExists($this->getFileFullName());
    }

    /**
     * Returns the content of the model related file.
     * @return string file content.
     */
    public function getFileContent()
    {
        $storageBucket = $this->getFileStorageBucket();
        return $storageBucket->getFileContent($this->getFileFullName());
    }

    /**
     * Returns full web link to the model related file.
     * @return string web link to file.
     */
    public function getFileUrl()
    {
        $storageBucket = $this->getFileStorageBucket();
        $fileFullName = $this->getFileFullName();
        $defaultFileUrl = $this->getDefaultFileUrl();
        if (!empty($defaultFileUrl)) {
            if (!$storageBucket->fileExists($fileFullName)) {
                return $defaultFileUrl;
            }
        }
        return $storageBucket->getFileUrl($fileFullName);
    }

    // Events:

    /**
     * Declares events and the corresponding event handler methods.
     * @return array events (array keys) and the corresponding event handler methods (array values).
     */
    public function events()
    {
        return array(
            'onAfterSave' => 'afterSave',
            'onBeforeDelete' => 'beforeDelete',
        );
    }

    /**
     * This event raises after owner saved.
     * It saves uploaded file if it exists.
     * @param CEvent $event - event instance.
     */
    public function afterSave($event)
    {
        $uploadedFile = $this->getUploadedFile();
        if (is_object($uploadedFile) && !$uploadedFile->getHasError()) {
            $this->saveFile($uploadedFile);
        }
        $this->clearUploadedFile();
        $this->clearFileTabularInputIndex();
    }

    /**
     * This event raises before owner deleted.
     * It deletes related file.
     * @param CEvent $event - event instance.
     */
    public function beforeDelete($event)
    {
        $this->deleteFile();
    }
}