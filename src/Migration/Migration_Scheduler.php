<?php


namespace Action_Scheduler\Custom_Tables\Migration;


use Action_Scheduler\Custom_Tables\DB_Logger;
use Action_Scheduler\Custom_Tables\DB_Store;

class Migration_Scheduler {
	const STATUS_FLAG     = 'action_schedule_custom_table_migration_status';
	const STATUS_COMPLETE = 'complete';
	const HOOK            = 'action_scheduler_custom_table_migration_hook';
	const GROUP           = 'action-scheduler-custom-tables';

	/**
	 * Set up the callback for the scheduled job
	 */
	public function hook() {
		add_action( self::HOOK, [ $this, 'run_migration' ], 10, 0 );
	}

	/**
	 * Remove the callback for the scheduled job
	 */
	public function unhook() {
		remove_action( self::HOOK, [ $this, 'run_migration' ], 10 );
	}

	/**
	 * The migration callback.
	 */
	public function run_migration() {
		$migration_runner = $this->get_migration_runner();
		$count            = $migration_runner->run( $this->get_batch_size() );
		if ( $count === 0 ) {
			$this->mark_complete();
		} else {
			$this->schedule_migration( time() + $this->get_schedule_interval() );
		}
	}

	public function mark_complete() {
		$this->unschedule_migration();
		update_option( self::STATUS_FLAG, self::STATUS_COMPLETE );
		do_action( 'action_scheduler_custom_tables_migration_complete' );
	}

	/**
	 * @return bool Whether the flag has been set marking the migration as complete
	 */
	public function is_migration_complete() {
		return get_option( self::STATUS_FLAG ) === self::STATUS_COMPLETE;
	}

	/**
	 * @return bool Whether there is a pending action in the store to handle the migration
	 */
	public function is_migration_scheduled() {
		$next = wc_next_scheduled_action( self::HOOK );

		return ! empty( $next );
	}

	/**
	 * @param int $when Timestamp to run the next migration batch. Defaults to now.
	 *
	 * @return string The action ID
	 */
	public function schedule_migration( $when = 0 ) {
		if ( empty( $when ) ) {
			$when = time();
		}

		return wc_schedule_single_action( $when, self::HOOK, [], self::GROUP );
	}

	/**
	 * Removes the scheduled migration action
	 */
	public function unschedule_migration() {
		wc_unschedule_action( self::HOOK, null, self::GROUP );
	}

	/**
	 * @return int Seconds between migration runs. Defaults to five minutes.
	 */
	private function get_schedule_interval() {
		return (int) apply_filters( 'action_scheduler_custom_tables_migration_interval', 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * @return int Number of actions to migrate in each batch. Defaults to 100.
	 */
	private function get_batch_size() {
		return (int) apply_filters( 'action_scheduler_custom_tables_migration_batch_size', 100 );
	}

	/**
	 * @return Migration_Runner
	 */
	private function get_migration_runner() {
		$config = $this->get_migration_config();

		return new Migration_Runner( $config );
	}

	/**
	 * @return Migration_Config
	 */
	private function get_migration_config() {
		$config = new Migration_Config();
		$config->set_source_store( new \ActionScheduler_wpPostStore() );
		$config->set_source_logger( new \ActionScheduler_wpCommentLogger() );
		$config->set_destination_store( new DB_Store() );
		$config->set_destination_logger( new DB_Logger() );

		return apply_filters( 'action_scheduler_custom_tables_migration_config', $config );
	}

}