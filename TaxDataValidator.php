<?php
declare(strict_types=1);

/**
 * Validator for US Tax Data inputs
 */
class TaxDataValidator
{
    /**
     * Validate US ZIP code
     *
     * @param string $zip
     * @throws InvalidArgumentException
     */
    public function validateZip(string $zip): void
    {
        if (!$zip) return;

        Yii::import('application.components.validators.UsZipFormatValidator');
        $validator = new UsZipFormatValidator();
        $validator->allowEmpty = false;
        $validator->allowPlus4Format = false;

        if (!$validator->isValid($zip)) {
            $msg = "Invalid US Zip format: $zip";
            Yii::log($msg, CLogger::LEVEL_ERROR, 'application.components.taxdata.TaxDataValidator');
            throw new InvalidArgumentException($msg);
        }
    }

    /**
     * Validate US state code
     *
     * @param string $state
     * @throws InvalidArgumentException
     */
    public function validateState(string $state): void
    {
        if (!$state) return;

        Yii::import('application.components.validators.UsStateValidator');
        $validator = new UsStateValidator();
        if (!$validator->isValidCode($state)) {
            $msg = "Invalid US state: $state";
            Yii::log($msg, CLogger::LEVEL_ERROR, 'application.components.taxdata.TaxDataValidator');
            throw new InvalidArgumentException($msg);
        }
    }
}
