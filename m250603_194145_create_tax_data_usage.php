<?php

class m250603_194145_create_tax_data_usage extends CDbMigration
{
    public function up()
    {
        $this->createTable('tax_data_usage', array(
            'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',

            'domain' => 'VARCHAR(255) NOT NULL',
            'zip' => 'CHAR(10) NOT NULL',
            'state' => 'CHAR(10) DEFAULT NULL',
            'country' => 'CHAR(2) NOT NULL',

            'result' => 'TEXT DEFAULT NULL',
            'used_tds' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'requested_on' => 'DATETIME NOT NULL',

            // keys
            'PRIMARY KEY (`id`)',
            'UNIQUE KEY `uq_tax_usage_request` (`domain`, `zip`, `country`, `requested_on`)',
            'KEY `idx_tax_usage_zip` (`zip`)',
            'KEY `idx_tax_usage_country_state` (`country`, `state`)',
        ), 'ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addForeignKey(
            'fk_tax_usage_country',
            'tax_data_usage',
            'country',
            'country',
            'code',
            'CASCADE',
            'CASCADE'
        );
    }

    public function down()
    {
        $this->dropForeignKey('fk_tax_usage_country', 'tax_data_usage');
        $this->dropTable('tax_data_usage');
    }
}
