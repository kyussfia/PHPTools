<?php

namespace Slametrix\Validator\Constraints;

/**
 * @Annotation
 */
class File extends \Symfony\Component\Validator\Constraints\File
{
    public $notFoundMessage = 'validator:file.not_found';
    public $notReadableMessage = 'validator:file.not_readable';
    public $maxSizeMessage = 'validator:file.max_size_exceeded';
    public $mimeTypesMessage = 'validator:file.not_allowed_type';
    public $disallowEmptyMessage = 'validator:file.empty_file';

    public $uploadIniSizeErrorMessage = 'validator:file.max_upload_size_exceeded';
    public $uploadFormSizeErrorMessage = 'validator:file.max_upload_size_exceeded';
    public $uploadPartialErrorMessage = 'validator:file.partially_uploaded';
    public $uploadNoFileErrorMessage = 'validator:file.not_uploaded';
    public $uploadNoTmpDirErrorMessage = 'validator:file.temp_dir_not_configured';
    public $uploadCantWriteErrorMessage = 'validator:file.temp_file_not_writeable';
    public $uploadExtensionErrorMessage = 'validator:file.extension_error';
    public $uploadErrorMessage = 'validator:file.upload_error';
    public $invalidFileNameMessage = 'validator:file.name_invalid';

    private const basicFileNamePattern = "/^[0-9a-zA-Z\^\&\'\@\{\}\[\]\,\$\=\!\-\#\(\)\.\%\+\~\_ ]+$/";
    private const noRestrictionPattern = "/^.*$/";

    public $extension;
    public $fileNamePattern;
    public $fileNameValidationDisabled; //if true, nonRestrictionPatter will allow every pattern

    /**
     * {@inheritdoc}
     */
    public function __construct($options)
    {
        parent::__construct($options);

        $this->fileNamePattern = empty($options['fileNameValidationDisabled']) || !$options['fileNameValidationDisabled'] ? self::basicFileNamePattern : self::noRestrictionPattern;
        $this->extension = empty($options['extension']) ? self::noRestrictionPattern : '/^'. $options['extension'] .'$/';
    }
}