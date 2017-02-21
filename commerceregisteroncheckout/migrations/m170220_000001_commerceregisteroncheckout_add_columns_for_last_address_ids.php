<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170220_000001_commerceregisteroncheckout_add_columns_for_last_address_ids extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
        $this->addColumnAfter('commerceregisteroncheckout', 'lastUsedBillingAddressId', 'int(11) DEFAULT NULL', 'EPW');
        $this->addColumnAfter('commerceregisteroncheckout', 'lastUsedShippingAddressId', 'int(11) DEFAULT NULL', 'EPW');
		return true;
	}
}
