<?php
declare(strict_types=1);

class TaxDataUsageService
{
    public static function saveUsage(string $domain, TaxDataUs $taxData, array $extraData = []): void
    {
        Yii::import('application.models.taxdata.TaxDataUsage');

        $usage = new TaxDataUsage();
        $usage->domain = $domain;
        $usage->zip = $taxData->zip;
        $usage->state = $taxData->state ?? null;
        $usage->country = $taxData->country_code;
        $usage->result = CJSON::encode([
            'id' => Yii::app()->controller->route,
            'result' => $taxData->toArray(),
            'error' => null
        ]);
        $usage->used_tds = $extraData['tds'] ?? 0;
        $usage->requested_on = gmdate('Y-m-d H:i:s');

        if (!$usage->save()) {
            Yii::log('Failed to save TaxDataUsage: ' . print_r($usage->getErrors(), true), CLogger::LEVEL_ERROR);
        }
    }
}
