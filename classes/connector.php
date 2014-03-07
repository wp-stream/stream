<?php
abstract class WP_Stream_Connector {

	/**
	* Name/slug of the context
	* @var string
	*/
	public static $name = null;

	/**
	* Actions this context is hooked to
	* @var array
	*/
	public static $actions = array();

	/**
	* Previous Stream entry in same request
	* @var int
	*/
	public static $prev_stream = null;

	/**
	 * Register all context hooks
	 *
	 * @return void
	 */
	public static function register() {
		$class = get_called_class();

		foreach ( $class::$actions as $action ) {
			add_action( $action, array( $class, 'callback' ), null, 5 );
		}

		add_filter( 'wp_stream_action_links_' . $class::$name, array( $class, 'action_links' ), 10, 2 );
	}

	/**
	 * Callback for all registered hooks throughout Stream
	 * Looks for a class method with the convention: "callback_{action name}"
	 *
	 * @return void
	 */
	public static function callback() {
		$action   = current_filter();
		$class    = get_called_class();
		$callback = array( $class, 'callback_' . str_replace( '-', '_', $action ) );

		//For the sake of testing, trigger an action with the name of the callback
		if ( defined( 'STREAM_TESTS' ) ) {
			/**
			 * Action fires during testing to test the current callback
			 *
			 * @param  array  $callback  Callback name
			 */
			do_action( 'stream_test_' . $callback[1] );
		}

		//Call the real function
		if ( is_callable( $callback ) ) {
			return call_user_func_array( $callback, func_get_args() );
		}
	}

	/**
	 * Add action links to Stream drop row in admin list screen
	 *
	 * @filter wp_stream_action_links_{connector}
	 * @param  array $links      Previous links registered
	 * @param  int   $record     Stream record
	 * @return array             Action links
	 */
	public static function action_links( $links, $record ) {
		return $links;
	}

	/**
	 * Log handler
	 *
	 * @param  string $message   sprintf-ready error message string
	 * @param  array  $args      sprintf (and extra) arguments to use
	 * @param  int    $object_id Target object id
	 * @param  array  $contexts  Contexts of the action
	 * @param  int    $user_id   User responsible for the action
	 *
	 * @internal param string $action Action performed (stream_action)
	 * @return void
	 */
	public static function log( $message, $args, $object_id, $contexts, $user_id = null ) {
		//Prevent inserting Excluded Context & Actions
		foreach ( $contexts as $context => $action ) {
			if ( ! WP_Stream_Connectors::is_logging_enabled( 'contexts', $context ) ) {
				unset( $contexts[ $context ] );
			} else {
				if ( ! WP_Stream_Connectors::is_logging_enabled( 'actions', $action ) ) {
					unset( $contexts[ $context ] );
				}
			}
		}
		if ( count( $contexts ) == 0 ){
			return ;
		}
		$class = get_called_class();

		return WP_Stream_Log::get_instance()->log(
			$class::$name,
			$message,
			$args,
			$object_id,
			$contexts,
			$user_id
			);
	}

	/**
	 * Save log data till shutdown, so other callbacks would be able to override
	 *
	 * @param  string $handle Special slug to be shared with other actions
	 *
	 * @internal param mixed $arg1 Extra arguments to sent to log()
	 * @internal param mixed $arg2 , etc..
	 * @return void
	 */
	public static function delayed_log( $handle ) {
		$args = func_get_args();
		array_shift( $args );

		self::$delayed[$handle] = $args;
		add_action( 'shutdown', array( __CLASS__, 'delayed_log_commit' ) );
	}

	/**
	 * Commit delayed logs saved by @delayed_log
	 * @return void
	 */
	public static function delayed_log_commit() {
		foreach ( self::$delayed as $handle => $args ) {
			call_user_func_array( array( __CLASS__, 'log' ) , $args );
		}
	}

}
