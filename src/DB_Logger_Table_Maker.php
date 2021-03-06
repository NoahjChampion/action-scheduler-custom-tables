<?php

namespace Action_Scheduler\Custom_Tables;

/**
 * Class DB_Logger_Table_Maker
 *
 * @codeCoverageIgnore
 *
 * Creates a custom table for storing action logs
 */
class DB_Logger_Table_Maker extends Table_Maker {
	const LOG_TABLE = 'actionscheduler_logs';

	protected $schema_version = 1;

	public function __construct() {
		$this->tables = [
			self::LOG_TABLE,
		];
	}

	protected function get_table_definition( $table ) {
		global $wpdb;
		$table_name       = $wpdb->$table;
		$charset_collate  = $wpdb->get_charset_collate();
		$max_index_length = 191; // @see wp_get_db_schema()
		switch ( $table ) {

			case self::LOG_TABLE:

				return "CREATE TABLE {$table_name} (
				        log_id bigint(20) unsigned NOT NULL auto_increment,
				        action_id bigint(20) unsigned NOT NULL,
				        message text NOT NULL,
				        log_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
				        log_date_local datetime NOT NULL default '0000-00-00 00:00:00',
				        PRIMARY KEY  (log_id),
				        KEY action_id (action_id),
				        KEY log_date_gmt (log_date_gmt),
				        KEY log_date_local (log_date_local)
				        ) $charset_collate";

			default:
				return '';
		}
	}
}