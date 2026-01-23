<?php
/**
 * TaxDataSystem service implementation
 */
class TaxDataService extends CApplicationComponent
{
    public string $wsdl;
    public string $location;
    public string $username;
    public string $password;
    public ?int $data_expiry = null;
    public bool $wsdl_cache_enabled = true;

    private ?SoapClient $_soapClient = null;

    private array $_status = [
        100 => 'Free Limit Reached',
        101 => 'Tax data found for US ZIP Code',
        102 => 'Non Fatal error',
        103 => 'Invalid Input',
        104 => 'Unexpected Error',
        105 => 'Subscription Expired',
        106 => 'No Access'
    ];

    public function init(): void
    {
        parent::init();
        $this->createSoapClient();
    }

    private function createSoapClient(): void
    {
        $options = [
            'trace' => 1,
            'exceptions' => 1,
            'location' => $this->location,
            'cache_wsdl' => $this->wsdl_cache_enabled ? WSDL_CACHE_MEMORY : WSDL_CACHE_NONE,
        ];

        try {
            $this->_soapClient = new SoapClient($this->wsdl, $options);
        } catch (SoapFault $e) {
            Yii::log("Failed to initialize SOAP client: " . $e->getMessage(), CLogger::LEVEL_ERROR, __CLASS__);
            throw new RuntimeException("Failed to initialize SOAP client", 0, $e);
        }
    }

    private function getSoapClient(): SoapClient
    {
        if ($this->_soapClient === null) {
            $this->createSoapClient();
        }
        return $this->_soapClient;
    }

    /**
     * Get US tax by ZIP
     * @param string $zip
     * @return object
     * @throws TaxDataRequestException
     */
    public function getUsTaxDataByZipCode(string $zip): object
    {
        $params = [
            'zipcode'  => $zip,
            'username' => $this->username,
            'password' => $this->password,
        ];

        Yii::log("Calling TaxDataSystem for ZIP: {$zip}", CLogger::LEVEL_INFO, __CLASS__);

        try {
            $result = $this->getSoapClient()->__soapCall("GetTDSBasicUSPlainNetwork", [$params], null, null, $outputHeader ?? null);
            Yii::log("Request: " . $this->getSoapClient()->__getLastRequest(), CLogger::LEVEL_INFO, __CLASS__);
            Yii::log("Response: " . $this->getSoapClient()->__getLastResponse(), CLogger::LEVEL_INFO, __CLASS__);

            $status = $result->GetTDSBasicUSPlainNetworkResult->ServiceStatus ?? null;
            $data   = $result->GetTDSBasicUSPlainNetworkResult->ServiceResult ?? null;

            $this->checkStatus($status);

            return $data;
        } catch (SoapFault $e) {
            Yii::log("SOAP call failed for ZIP {$zip}: " . $e->getMessage(), CLogger::LEVEL_WARNING, __CLASS__);
            throw new TaxDataRequestException("SOAP request failed: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws BadMethodCallException
     */
    public function getUsTaxDataByAddress(string $address, string $city, string $county, string $state, string $zip): object
    {
        throw new BadMethodCallException('Get US tax data by address is not implemented for TaxDataSystem service');
    }

    /**
     * Return all service status codes
     */
    public function getStatus(): array
    {
        return $this->_status;
    }

    /**
     * Get description for a given status code
     * @param int $code
     * @return string
     * @throws OutOfRangeException
     */
    public function getStatusForCode(int $code): string
    {
        if (!isset($this->_status[$code])) {
            throw new OutOfRangeException("Invalid status code: {$code}");
        }
        return $this->_status[$code];
    }

    /**
     * Check TaxDataSystem response status
     * @param object|null $status
     * @throws TaxDataRequestException
     */
    private function checkStatus(?object $status): void
    {
        $code = (int)($status->StatusNo ?? 0);
        if ($code !== 101) {
            $desc = $status->StatusDescription ?? 'Unknown error';
            Yii::log("TaxDataSystem returned error: {$desc} ({$code})", CLogger::LEVEL_WARNING, __CLASS__);
            throw new TaxDataRequestException($desc, $code);
        }
    }
}
