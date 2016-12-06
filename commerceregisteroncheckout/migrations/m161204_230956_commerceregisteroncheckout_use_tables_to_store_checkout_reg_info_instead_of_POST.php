<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m161204_230956_commerceregisteroncheckout_use_tables_to_store_checkout_reg_info_instead_of_POST extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
        craft()->db->createCommand()->createTable("commerceregisteroncheckout",["orderNumber"=>"varchar","EPW"=>"varchar"]);
		return true;
	}
}
