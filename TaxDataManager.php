<?php
declare(strict_types=1);

/**
 * Managing class for Tax Data
 */
class TaxDataManager extends CComponent
{
    public bool $useTaxDataService = true;
    public bool $enableDbExpiration = true;

    private TaxDataValidator $validator;

    public function __construct()
{
    $this->validator = new TaxDataValidator();
}

    /**
     * Load US tax data by zip code
     *
     * @param string $zip
     * @return array ['tax_data_us' => TaxDataUs, 'tds' => (int)]
     * @throws InvalidArgumentException
     * @throws TaxDataNotFoundException
     * @throws TaxDataRequestException
     */
    public function getUsTaxDataByZipCode(string $zip): array
    {
        return $this->getUsTaxDataByAddress($zip);
    }

    /**
     * Load US tax data by address
     *
     * @param string $zip
     * @param string $address
     * @param string $city
     * @param string $county
     * @param string $state
     * @return array ['tax_data_us' => TaxDataUs, 'tds' => (int)]
     * @throws InvalidArgumentException
     * @throws TaxDataNotFoundException
     * @throws TaxDataRequestException
     */
    public function getUsTaxDataByAddress(
        string $zip,
        string $address = '',
        string $city = '',
        string $county = '',
        string $state = ''
    ): array {
        $this->validator->validateZip($zip);
        $this->validator->validateState($state);

        $taxDataUs = $this->fetchFromDatabase($zip);

        if (!$taxDataUs && !$zip) {
            $zip = $this->detectUsPostalCodeByAddress($address, $city, $county, $state);
            $taxDataUs = $this->fetchFromDatabase($zip);
        }

        $useTaxDataService = $this->useTaxDataService && !$taxDataUs;

        if ($useTaxDataService) {
            $taxDataUs = $this->fetchFromServiceAndSave($zip, $address, $city, $county, $state);
        }

        if (!$taxDataUs) {
            throw new TaxDataNotFoundException('Tax data not found');
        }

        return ['tax_data_us' => $taxDataUs, 'tds' => (int)$useTaxDataService];
    }

    /**
     * Reset tax data in database
     */
    public function reset(?string $country = null, ?string $state = null, ?string $zip = null, ?string $date = null): array
    {
        $condition = '1';
        $params = [];

        if ($country) {
            $condition .= ' AND country_code=:country_code';
            $params[':country_code'] = $country;
        }
        if ($state) {
            $condition .= ' AND state=:state';
            $params[':state'] = $state;
        }
        if ($zip) {
            $condition .= ' AND zip=:zip';
            $params[':zip'] = $zip;
        }
        if ($date) {
            $condition .= ' AND modified_on<=:modified_on';
            $params[':modified_on'] = $date . ' 23:59:59';
        }

        if ($params) {
            Yii::import('application.models.taxdata.TaxDataUs');
            $deleted = TaxDataUs::model()->deleteAll($condition, $params);
            return ['deleted' => $deleted];
        }

        return [];
    }

    /**
     * Detect ZIP by US address
     */
    public function detectUsPostalCodeByAddress(
        string $address = '',
        string $city = '',
        string $county = '',
        string $state = '',
        string $country = 'US'
    ): string {
        Yii::import('application.components.postalcode.AddressDetailsDetector');
        Yii::import('application.components.dto.AddressDetailsDto');

        $dto = new AddressDetailsDto($country, $state, $county, $city, '', $address);
        $result = (new AddressDetailsDetector())->detectUsPostalCodeByAddress($dto);

        return $result->zip ?? '';
    }

    private function fetchFromDatabase(string $zip): ?TaxDataUs
    {
        Yii::import('application.models.taxdata.TaxDataUs');
        $taxDataService = Yii::app()->getComponent('taxdata');

        $condition = 'zip=:zip';
        $params = [':zip' => $zip];

        if ($taxDataService->data_expiry) {
            $dateExpire = gmdate('Y-m-d H:i:s', time() - $taxDataService->data_expiry);
            $condition .= ' AND modified_on>:date';
            $params[':date'] = $dateExpire;
        }

        $taxDataUs = TaxDataUs::model()->find($condition, $params);

        $this->logInfo($taxDataUs ? "Tax data found for $zip" : "No tax data found for $zip");

        return $taxDataUs;
    }

    private function fetchFromServiceAndSave(string $zip, string $address, string $city, string $county, string $state): ?TaxDataUs
    {
        Yii::import('application.models.taxdata.TaxDataUs');
        Yii::import('application.models.location.State');

        $taxDataService = Yii::app()->getComponent('taxdata');
        $serviceResult = $taxDataService->getUsTaxDataByAddress($zip, $address, $city, $county, $state);

        if (!$serviceResult) return null;

        $taxDataUs = TaxDataUs::model()->findByPk($zip) ?? new TaxDataUs();
        $taxDataUs->zip = $zip;
        $taxDataUs->country_code = 'US';

        // Find state record
        $stateRecord = State::model()->find(
            'country_code=:country AND (state=:state OR SOUNDEX(name)=SOUNDEX(:state))',
            [':country'=>'US', ':state'=>trim($serviceResult->State), ':name'=>trim($serviceResult->State)]
        );

        if (!$stateRecord) {
            $this->logError('Failed to find state: ' . trim($serviceResult->State));
        }

        $taxDataUs->state = $stateRecord->state ?? null;
        $taxDataUs->county = $serviceResult->County;
        $taxDataUs->city = $serviceResult->City;
        $taxDataUs->total_sales_tax = (float)$serviceResult->TotalSalesTax;
        $taxDataUs->total_use_tax = (float)$serviceResult->TotalUseTax;
        $taxDataUs->state_sales_tax = (float)$serviceResult->StateSalesTax;
        $taxDataUs->state_use_tax = (float)$serviceResult->StateUseTax;
        $taxDataUs->county_sales_tax = (float)$serviceResult->CountySalesTax;
        $taxDataUs->county_use_tax = (float)$serviceResult->CountyUseTax;
        $taxDataUs->city_sales_tax = (float)$serviceResult->CitySalesTax;
        $taxDataUs->city_use_tax = (float)$serviceResult->CityUseTax;
        $taxDataUs->tax_shipping_alone = strtoupper(trim($serviceResult->IsTaxShippingAlone)) === 'Y' ? 1 : 0;
        $taxDataUs->tax_shipping_handling = strtoupper(trim($serviceResult->IsTaxShipHandling)) === 'Y' ? 1 : 0;
        $taxDataUs->modified_on = gmdate('Y-m-d H:i:s');

        if (!$taxDataUs->save()) {
            $this->logError('Failed to save TaxDataUs: ' . print_r($taxDataUs->getErrors(), true));
        }

        return $taxDataUs;
    }

    private function logError(string $msg): void
    {
        Yii::log($msg, CLogger::LEVEL_ERROR, 'application.components.taxdata.TaxDataManager');
    }

    private function logInfo(string $msg): void
    {
        Yii::log($msg, CLogger::LEVEL_INFO, 'application.components.taxdata.TaxDataManager');
    }
}
