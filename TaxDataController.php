<?php
declare(strict_types=1);

class TaxDataController extends RestController
{
    public bool $passTaxDataSystemStatus = true;
    private ?CredentialsDto $credentialsDto = null;

    public function filters(): array
    {
        return [
            [
                'application.components.filters.AuthFilter + getByCountryAndCode, getByAny',
                'credentialsDto' => &$this->credentialsDto
            ],
        ];
    }

    public function actionGetByCountryAndCode(): void
    {
        $params = $this->getJsonParams();
        $this->requireParams(['country' => $params['country'] ?? null, 'code' => $params['code'] ?? null]);

        $taxDataResult = $this->handleTaxDataExceptions(function () use ($params) {
            $manager = new TaxDataManager();
            return $manager->getUsTaxDataByZipCode((string)$params['code']);
        });

        $taxData = $taxDataResult['tax_data_us'] ?? null;
        $this->sendTaxDataResponse($taxData, $taxDataResult);
    }

    public function actionGetByAny(): void
    {
        Yii::import('application.components.inputProcessor.AddressInputProcessor');
        $body = $this->getJsonParams();
        $addressDto = (new AddressInputProcessor())->inputProcess($body);

        AddressValidator::validate($addressDto);

        $taxDataResult = $this->handleTaxDataExceptions(function () use ($addressDto) {
            $manager = new TaxDataManager();
            return $manager->getUsTaxDataByAddress(
                $addressDto->zip,
                $addressDto->address,
                $addressDto->city,
                $addressDto->county,
                $addressDto->state
            );
        });

        $taxData = $taxDataResult['tax_data_us'] ?? null;
        $this->sendTaxDataResponse($taxData, $taxDataResult);
    }

    private function sendTaxDataResponse($taxData, array $taxDataResult): void
    {
        if (!$taxData instanceof TaxDataUs) {
            $this->sendJsonResponse(null, 404, 'Failed to retrieve tax data');
        }

        TaxDataUsageService::saveUsage($this->credentialsDto->domain ?? 'unknown', $taxData, $taxDataResult);

        $result = [
            'id' => $this->route,
            'result' => $taxData->toArray(),
            'error' => null,
        ];

        $this->sendJsonResponse($result, 200);
    }

    private function getJsonParams(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = json_decode((string)$raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function handleTaxDataExceptions(callable $fn): array
    {
        try {
            return $fn();
        } catch (InvalidArgumentException $ie) {
            $this->sendJsonResponse(null, 400, $ie->getMessage());
        } catch (TaxDataNotFoundException $te) {
            $this->sendJsonResponse(null, 404, 'Tax data not found');
        } catch (TaxDataRequestException $te) {
            if ($this->passTaxDataSystemStatus) {
                $this->sendJsonResponse(
                    null,
                    503,
                    'TaxDataSystem request failed: ' . $te->getMessage() . ' (' . $te->getCode() . ')'
                );
            }
        }
        return [];
    }

    private function sendJsonResponse(?array $data, int $status = 200, ?string $error = null): void
    {
        header('Content-Type: application/json');
        http_response_code($status);

        $response = $data ?? ['id' => $this->route, 'result' => null, 'error' => $error];
        echo CJSON::encode($response);
        Yii::app()->end();
    }

    private function requireParams(array $params): void
    {
        foreach ($params as $key => $value) {
            if (!$value) {
                $this->sendJsonResponse(null, 400, "Missing parameter '$key'");
            }
        }
    }
}
