<?php
declare(strict_types=1);

class TaxDataUs extends CActiveRecord
{
    public ?string $zip = null;
    public ?string $country_code = null;
    public ?string $state = null;
    public ?string $county = null;
    public ?string $city = null;
    public ?float $total_sales_tax = null;
    public ?float $total_use_tax = null;
    public ?float $state_sales_tax = null;
    public ?float $state_use_tax = null;
    public ?float $county_sales_tax = null;
    public ?float $county_use_tax = null;
    public ?float $city_sales_tax = null;
    public ?float $city_use_tax = null;
    public ?int $tax_shipping_alone = null;
    public ?int $tax_shipping_handling = null;
    public ?string $modified_on = null;

    public static function model(string $className = __CLASS__): TaxDataUs
    {
        return parent::model($className);
    }

    public function tableName(): string
    {
        return 'tax_data_us';
    }

    public function rules(): array
    {
        return [
            ['zip, country_code, state, city', 'required'],
            ['tax_shipping_alone, tax_shipping_handling', 'numerical', 'integerOnly' => true],
            ['zip, state', 'length', 'max' => 10],
            ['country_code', 'length', 'max' => 2],
            ['county, city', 'length', 'max' => 64],
            ['total_sales_tax, total_use_tax, state_sales_tax, state_use_tax, county_sales_tax, county_use_tax, city_sales_tax, city_use_tax', 'numerical', 'allowEmpty' => true],
            ['zip, country_code, state, county, city, total_sales_tax, total_use_tax, state_sales_tax, state_use_tax, county_sales_tax, county_use_tax, city_sales_tax, city_use_tax, tax_shipping_alone, tax_shipping_handling, modified_on', 'safe', 'on' => 'search'],
        ];
    }

    public function relations(): array
    {
        return [
            'countryRelation' => [self::BELONGS_TO, 'Country', 'country_code'],
            'stateRelation' => [self::BELONGS_TO, 'State', ['country_code' => 'country_code', 'state' => 'state']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'zip' => 'Zip',
            'country_code' => 'Country Code',
            'state' => 'State',
            'county' => 'County',
            'city' => 'City',
            'total_sales_tax' => 'Total Sales Tax',
            'total_use_tax' => 'Total Use Tax',
            'state_sales_tax' => 'State Sales Tax',
            'state_use_tax' => 'State Use Tax',
            'county_sales_tax' => 'County Sales Tax',
            'county_use_tax' => 'County Use Tax',
            'city_sales_tax' => 'City Sales Tax',
            'city_use_tax' => 'City Use Tax',
            'tax_shipping_alone' => 'Tax Shipping Alone',
            'tax_shipping_handling' => 'Tax Shipping Handling',
            'modified_on' => 'Modified On',
        ];
    }

    public function search(): CActiveDataProvider
    {
        $criteria = new CDbCriteria();

        $criteria->compare('zip', $this->zip, true);
        $criteria->compare('country_code', $this->country_code, true);
        $criteria->compare('state', $this->state, true);
        $criteria->compare('county', $this->county, true);
        $criteria->compare('city', $this->city, true);
        $criteria->compare('total_sales_tax', $this->total_sales_tax);
        $criteria->compare('total_use_tax', $this->total_use_tax);
        $criteria->compare('state_sales_tax', $this->state_sales_tax);
        $criteria->compare('state_use_tax', $this->state_use_tax);
        $criteria->compare('county_sales_tax', $this->county_sales_tax);
        $criteria->compare('county_use_tax', $this->county_use_tax);
        $criteria->compare('city_sales_tax', $this->city_sales_tax);
        $criteria->compare('city_use_tax', $this->city_use_tax);
        $criteria->compare('tax_shipping_alone', $this->tax_shipping_alone);
        $criteria->compare('tax_shipping_handling', $this->tax_shipping_handling);
        $criteria->compare('modified_on', $this->modified_on, true);

        return new CActiveDataProvider($this, [
            'criteria' => $criteria,
        ]);
    }

    public function toArray(): array
    {
        return [
            'zip' => $this->zip,
            'country_code' => $this->country_code,
            'state' => $this->state,
            'county' => $this->county,
            'city' => $this->city,
            'total_sales_tax' => $this->total_sales_tax,
            'total_use_tax' => $this->total_use_tax,
            'state_sales_tax' => $this->state_sales_tax,
            'state_use_tax' => $this->state_use_tax,
            'county_sales_tax' => $this->county_sales_tax,
            'county_use_tax' => $this->county_use_tax,
            'city_sales_tax' => $this->city_sales_tax,
            'city_use_tax' => $this->city_use_tax,
            'tax_shipping_alone' => $this->tax_shipping_alone,
            'tax_shipping_handling' => $this->tax_shipping_handling,
            'modified_on' => $this->modified_on,
        ];
    }
}
