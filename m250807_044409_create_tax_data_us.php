<?php

class m250807_044409_create_tax_data_us extends CDbMigration
{
    public function up()
    {
        $this->createTable('tax_data_us', array(
            'zip' => 'CHAR(10) NOT NULL COMMENT "ZIP or ZIP+4"',
            'country_code' => 'CHAR(2) NOT NULL',
            'state' => 'CHAR(2) NOT NULL',
            'county' => 'VARCHAR(64) DEFAULT NULL',
            'city' => 'VARCHAR(64) NOT NULL',

            'total_sales_tax' => 'DECIMAL(7,6) DEFAULT NULL',
            'total_use_tax' => 'DECIMAL(7,6) DEFAULT NULL',
            'state_sales_tax' => 'DECIMAL(7,6) DEFAULT NULL',
            'state_use_tax' => 'DECIMAL(7,6) DEFAULT NULL',
            'county_sales_tax' => 'DECIMAL(7,6) DEFAULT NULL',
            'county_use_tax' => 'DECIMAL(7,6) DEFAULT NULL',
            'city_sales_tax' => 'DECIMAL(7,6) DEFAULT NULL',
            'city_use_tax' => 'DECIMAL(7,6) DEFAULT NULL',

            'tax_shipping_alone' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'tax_shipping_handling' => 'TINYINT(1) NOT NULL DEFAULT 0',

            'modified_on' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',

            // keys
            'PRIMARY KEY (`zip`, `country_code`)',
            'INDEX `idx_tax_data_city` (`city`)',
            'INDEX `idx_tax_data_country_state` (`country_code`, `state`)',
        ), 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        
        // FK: country
        $this->addForeignKey(
            'fk_tax_data_us_country',
            'tax_data_us',
            'country_code',
            'country',
            'code',
            'CASCADE',
            'CASCADE'
        );

        // FK: country + state
        $this->addForeignKey(
            'fk_tax_data_us_country_state',
            'tax_data_us',
            'country_code, state',
            'state',
            'country_code, state',
            'CASCADE',
            'CASCADE'
        );
    }

    public function down()
    {
        $this->dropForeignKey('fk_tax_data_us_country_state', 'tax_data_us');
        $this->dropForeignKey('fk_tax_data_us_country', 'tax_data_us');
        $this->dropTable('tax_data_us');
    }
}
