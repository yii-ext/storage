<?php
/**
 * @author Klimov Paul <klimov@zfort.com>
 * @link http://www.zfort.com/
 * @copyright Copyright &copy; 2000-2014 Zfort Group
 * @license http://www.zfort.com/terms-of-use
 */

namespace yii_ext\storage;
use CException;
use CFileHelper;

/**
 * BucketSubDirTemplate improves the {@link BaseBucket} bucket base class,
 * allowing to specify template for the dynamic file sub directories.
 * @see BaseBucket
 *
 * @author Klimov Paul <klimov@zfort.com>
 * @version $Id$
 * @package yii_ext\storage
 * @since 1.0
 */
abstract class BucketSubDirTemplate extends BaseBucket
{
    /**
     * @var string template of all sub directories, which will store a particular
     * file. Value of this parameter will be parsed per each file.
     * This template may be used to create sub directories avoiding storing too many
     * files at the single directory.
     * To use a dynamic value of the file attribute, place attribute name in curly brackets,
     * for example: {name}.
     * Allowed placeholders:
     * {name} - name of the file,
     * {ext} - extension of the file.
     * You may place symbols "^" before any placeholder name, such placeholder will be resolved as single
     * symbol of the normal value. Number of symbol determined by count of "^".
     * For example:
     * if file name equal to 54321.tmp, placeholder {^name} will be resolved as "5", {^^name} - as "4" and so on.
     * Example value:
     * '{^name}/{^^name}'
     */
    protected $_fileSubDirTemplate = '';
    /**
     * @var array internal cache data.
     * This field is for the internal usage only.
     */
    protected $_internalCache = array();

    // Set / Get :

    public function setFileSubDirTemplate($fileSubDirTemplate)
    {
        if (!is_string($fileSubDirTemplate)) {
            throw new CException('"' . get_class($this) . '::fileSubDirTemplate" should be a string!');
        }
        $this->_fileSubDirTemplate = $fileSubDirTemplate;
        return true;
    }

    public function getFileSubDirTemplate()
    {
        return $this->_fileSubDirTemplate;
    }

     /**
     * Clears internal cache data.
     * @return boolean success.
     */
    public function clearInternalCache()
    {
        $this->_internalCache = array();
        return true;
    }

    /**
     * Gets file storage sub dirs path, resolving {@link subDirTemplate}.
     * @param string $fileName - name of the file.
     * @return string file sub dir value.
     */
    protected function getFileSubDir($fileName)
    {
        $subDirTemplate = $this->getFileSubDirTemplate();
        if (empty($subDirTemplate)) {
            return $subDirTemplate;
        }
        $this->_internalCache['getFileSubDirFileName'] = $fileName;
        $result = preg_replace_callback("/{(\^*(\w+))}/", array($this, 'getFileSubDirPlaceholderValue'), $subDirTemplate);
        unset($this->_internalCache['getFileSubDirFileName']);
        return $result;
    }

    /**
     * Internal callback function for {@link getFileSubDir}.
     * @param array $matches set of regular expression matches.
     * @throws CException on failure.
     * @return string value of the placeholder.
     */
    protected function getFileSubDirPlaceholderValue($matches)
    {
        $placeholderName = $matches[1];
        $placeholderPartSymbolPosition = strspn($placeholderName, '^') - 1;
        if ($placeholderPartSymbolPosition >= 0) {
            $placeholderName = $matches[2];
        }

        $fileName = $this->_internalCache['getFileSubDirFileName'];

        switch ($placeholderName) {
            case 'name': {
                $placeholderValue = $fileName;
                break;
            }
            case 'ext':
            case 'extension': {
                $placeholderValue = CFileHelper::getExtension($fileName);
                break;
            }
            default: {
                throw new CException("Unable to resolve file sub dir: unknown placeholder '{$placeholderName}'!");
            }
        }

        $defaultPlaceholderValue = '0';

        if ($placeholderPartSymbolPosition >= 0) {
            if ($placeholderPartSymbolPosition < strlen($placeholderValue)) {
                $placeholderValue = substr($placeholderValue, $placeholderPartSymbolPosition, 1);
            } else {
                $placeholderValue = $defaultPlaceholderValue;
            }
        }

        if (strlen($placeholderValue) <= 0 || in_array($placeholderValue, array('.'))) {
            $placeholderValue = $defaultPlaceholderValue;
        }
        return $placeholderValue;
    }

    /**
     * Returns the file name, including path resolved from {@link fileSubDirTemplate}.
     * @param string $fileName - name of the file.
     * @return string name of the file including sub path.
     */
    public function getFileNameWithSubDir($fileName)
    {
        $fileSubDir = $this->getFileSubDir($fileName);
        if (!empty($fileSubDir)) {
            $fullFileName = $fileSubDir . '/' . $fileName;
        } else {
            $fullFileName = $fileName;
        }
        return $fullFileName;
    }
}
