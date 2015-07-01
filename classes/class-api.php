<?php
namespace WP_Stream;

class API {
	/**
	 * Hold Plugin class
	 * @var Plugin
	 */
	public $plugin;

	/**
	 * API Key key/identifier
	 */
	const API_KEY_OPTION_KEY = 'wp_stream_site_api_key';

	/**
	 * Site UUID key/identifier
	 */
	const SITE_UUID_OPTION_KEY = 'wp_stream_site_uuid';

	/**
	 * Site Retricted key/identifier
	 */
	const RESTRICTED_OPTION_KEY = 'wp_stream_site_restricted';

	/**
	 * The site's API Key
	 *
	 * @var string
	 */
	public $api_key = false;

	/**
	 * The site's unique identifier
	 *
	 * @var string
	 */
	public $site_uuid = false;

	/**
	 * The site's restriction status
	 *
	 * @var bool
	 */
	public $restricted = true;

	/**
	 * The API URL
	 *
	 * @var string
	 */
	public $api_url = 'https://api.wp-stream.com';

	/**
	 * Error messages
	 *
	 * @var array
	 */
	public $errors = array();

	/**
	 * Total API calls made per page load
	 * Used for debugging and optimization
	 *
	 * @var array
	 */
	public $count = 0;

	/**
	 * Class constructor.
	 *
	 * @param Plugin $plugin The main Plugin class.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		$this->api_key    = get_option( self::API_KEY_OPTION_KEY, 0 );
		$this->site_uuid  = get_option( self::SITE_UUID_OPTION_KEY, 0 );
		$this->restricted = get_option( self::RESTRICTED_OPTION_KEY, 1 );
	}

	/**
	 * Check if the current site is restricted
	 *
	 * @param bool $force_check Force the API to send a request to check the site's plan type
	 *
	 * @return bool
	 */
	public function is_restricted( $force_check = false ) {
		if ( $force_check ) {
			$site = $this->get_site();

			$this->restricted = ( ! isset( $site->plan->type ) || 'free' === $site->plan->type );
		}

		return $this->restricted;
	}

	/**
	 * Used to prioritise the streams transport which support non-blocking
	 *
	 * @param array $request_order The current order of the transport priorities
	 * @param array $args          Request settings, e.g. Blocking
	 * @param string $url          Unused
	 *
	 * @filter http_api_transports
	 *
	 * @return bool
	 */
	public function http_api_transport_priority( $request_order, $args, $url ) {
		unset( $url );

		if ( isset( $args['blocking'] ) && false === $args['blocking'] ) {
			$request_order = array( 'streams', 'curl' );
		}

		return $request_order;
	}

	/**
	 * Get the details for a specific site.
	 *
	 * @param array $fields     Returns specified fields only.
	 * @param bool $allow_cache Allow API calls to be cached.
	 * @param int $expiration   Set transient expiration in seconds.
	 *
	 * @return mixed
	 */
	public function get_site( $fields = array(), $allow_cache = true, $expiration = 30 ) {
		if ( ! $this->site_uuid ) {
			return false;
		}

		$params = array();

		if ( ! empty( $fields ) ) {
			$params['fields'] = implode( ',', $fields );
		}

		$url  = $this->request_url( sprintf( '/sites/%s', urlencode( $this->site_uuid ) ), $params );
		$args = array( 'method' => 'GET' );
		$site = $this->remote_request( $url, $args, $allow_cache, $expiration );

		if ( $site && ! is_wp_error( $site ) ) {
			$is_restricted = ( ! isset( $site->plan->type ) || 'free' === $site->plan->type ) ? 1 : 0;

			if ( (bool) $is_restricted !== $this->restricted ) {
				$this->restricted = $is_restricted;

				update_option( self::RESTRICTED_OPTION_KEY, $is_restricted );
			}
		}

		return $site;
	}

	/**
	 * Return this site's plan type
	 *
	 * @return string
	 */
	public function get_plan_type() {
		$site = $this->get_site();

		return isset( $site->plan->type ) ? esc_html( $site->plan->type ) : 'free';
	}

	/**
	 * Return this site's plan type label
	 *
	 * @return string
	 */
	public function get_plan_type_label() {
		$type = $this->get_plan_type();

		// Only check the beginning of these type strings
		if ( 0 === strpos( $type, 'pro' ) ) {
			$label = esc_html__( 'Pro', 'stream' );
		} else {
			$label = esc_html__( 'Free', 'stream' );
		}

		return $label;
	}

	/**
	 * Return this site's plan retention length
	 *
	 * @return int
	 */
	public function get_plan_retention() {
		$site = $this->get_site();

		return isset( $site->plan->retention ) ? absint( $site->plan->retention ) : 30;
	}

	/**
	 * Return this site's plan retention label
	 *
	 * @return string
	 */
	public function get_plan_retention_label() {
		$retention = $this->get_plan_retention();

		if ( 0 === $retention ) {
			$label = esc_html__( '1 Year', 'stream' );
		} else {
			$label = sprintf(
				_n( '1 Day', '%s Days', $retention, 'stream' ),
				$retention
			);
		}

		return $label;
	}

	/**
	 * Return the oldest record date (GMT) allowed for this site's plan
	 *
	 * @return string
	 */
	public function get_plan_retention_max_date( $format = 'Y-m-d H:i:s' ) {
		$retention = $this->get_plan_retention();

		return empty( $retention ) ? gmdate( $format, strtotime( '1 year ago' ) ) : gmdate( $format, strtotime( sprintf( '%d days ago', $retention ) ) );
	}

	/**
	 * Return this site's plan amount
	 *
	 * @return string
	 */
	public function get_plan_amount() {
		$site = $this->get_site();

		return isset( $site->plan->amount ) ? esc_html( $site->plan->amount ) : 0;
	}

	/**
	 * Return the account creation date for this site
	 *
	 * @return string
	 */
	public function get_created_date() {
		$site        = $this->get_site();
		$date_format = get_option( 'date_format' );

		return isset( $site->created ) ? date_i18n( $date_format, strtotime( $site->created ) ) : esc_html__( 'N/A', 'stream' );
	}

	/**
	 * Return the expiration date for this site's plan
	 *
	 * @return string
	 */
	public function get_expiry_date() {
		$site        = $this->get_site();
		$date_format = get_option( 'date_format' );

		return isset( $site->expiry->date ) ? date_i18n( $date_format, strtotime( $site->expiry->date ) ) : esc_html__( 'N/A', 'stream' );
	}

	/**
	 * Get a specific record.
	 *
	 * @param $record_id string A record ID.
	 * @param $fields array     Returns specified fields only.
	 * @param $allow_cache bool Allow API calls to be cached.
	 * @param $expiration int   Set transient expiration in seconds.
	 *
	 * @return mixed
	 */
	public function get_record( $record_id = '', $fields = array(), $allow_cache = true, $expiration = 30 ) {
		if ( empty( $record_id ) ) {
			return false;
		}

		if ( ! $this->site_uuid ) {
			return false;
		}

		$params = array();

		if ( ! empty( $fields ) ) {
			$params['fields'] = implode( ',', $fields );
		}

		$url  = $this->request_url( sprintf( '/sites/%s/records/%s', urlencode( $this->site_uuid ), urlencode( $record_id ) ), $params );
		$args = array( 'method' => 'GET' );

		return $this->remote_request( $url, $args, $allow_cache, $expiration );
	}

	/**
	 * Get all records.
	 *
	 * @param array $fields     Returns specified fields only.
	 * @param bool $allow_cache Allow API calls to be cached.
	 * @param int $expiration   Set transient expiration in seconds.
	 *
	 * @return mixed
	 */
	public function get_records( $fields = array(), $allow_cache = true, $expiration = 30 ) {
		if ( ! $this->site_uuid ) {
			return false;
		}

		$params = array();

		if ( ! empty( $fields ) ) {
			$params['fields'] = implode( ',', $fields );
		}

		$url  = $this->request_url( sprintf( '/sites/%s/records', urlencode( $this->site_uuid ) ), $params );
		$args = array( 'method' => 'GET' );

		return $this->remote_request( $url, $args, $allow_cache, $expiration );
	}

	/**
	 * Create new records.
	 *
	 * @param array $records
	 * @param bool  $blocking
	 *
	 * @return mixed
	 */
	public function new_records( $records, $blocking = false ) {
		if ( ! $this->site_uuid ) {
			return false;
		}

		$url  = $this->request_url( sprintf( '/sites/%s/records', urlencode( $this->site_uuid ) ) );
		$args = array( 'method' => 'POST', 'body' => wp_stream_json_encode( array( 'records' => $records ) ), 'blocking' => (bool) $blocking );

		return $this->remote_request( $url, $args );
	}

	/**
	 * Search all records.
	 *
	 * @param array $query        Elasticsearch's Query DSL query object.
	 * @param array $fields       Returns specified fields only.
	 * @param array $sites        Which sites to search.
	 * @param string $search_type Type of search.
	 * @param bool $allow_cache   Allow API calls to be cached.
	 * @param int $expiration     Set transient expiration in seconds.
	 *
	 * @return mixed
	 */
	public function search( $query = array(), $fields = array(), $sites = array(), $search_type = '', $allow_cache = false, $expiration = 120 ) {
		if ( ! $this->site_uuid ) {
			return false;
		}

		$body = array();

		$body['query']       = ! empty( $query ) ? $query : array();
		$body['fields']      = ! empty( $fields ) ? $fields : array();
		$body['sites']       = ! empty( $sites ) ? $sites : array( $this->site_uuid );
		$body['search_type'] = ! empty( $search_type ) ? $search_type : '';

		$url  = $this->request_url( '/search' );
		$args = array( 'method' => 'POST', 'body' => wp_stream_json_encode( (object) $body ) );

		return $this->remote_request( $url, $args, $allow_cache, $expiration );
	}

	/**
	 * Helper function to create and escape a URL for an API request.
	 *
	 * @param string $path  The endpoint path, with a starting slash.
	 * @param array $params The $_GET parameters.
	 *
	 * @return string A properly escaped URL.
	 */
	public function request_url( $path, $params = array() ) {
		return esc_url_raw(
			add_query_arg(
				$params,
				untrailingslashit( $this->api_url ) . $path
			)
		);
	}

	/**
	 * Helper function to query the marketplace API via wp_safe_remote_request.
	 *
	 * @param string $url       The url to access.
	 * @param array $args       The headers sent during the request.
	 * @param bool $allow_cache Allow API calls to be cached.
	 * @param int $expiration   Set transient expiration in seconds.
	 *
	 * @return object The results of the wp_safe_remote_request request.
	 */
	protected function remote_request( $url = '', $args = array(), $allow_cache = true, $expiration = 300 ) {
		if ( empty( $url ) || empty( $this->api_key ) ) {
			return false;
		}

		$defaults = array(
			'headers'   => array(),
			'method'    => 'GET',
			'body'      => '',
			'sslverify' => true,
		);

		$this->count++;

		$args = wp_parse_args( $args, $defaults );

		$args['headers']['Stream-Site-API-Key'] = $this->api_key;
		$args['headers']['Content-Type']        = 'application/json';

		add_filter( 'http_api_transports', array( $this, 'http_api_transport_priority' ), 10, 3 );

		$transient = 'wp_stream_' . md5( $url );

		if ( 'GET' === $args['method'] && $allow_cache ) {
			if ( false === ( $request = get_transient( $transient ) ) ) {
				$request = wp_safe_remote_request( $url, $args );

				set_transient( $transient, $request, $expiration );
			}
		} else {
			$request = wp_safe_remote_request( $url, $args );
		}

		remove_filter( 'http_api_transports', array( $this, 'http_api_transport_priority' ), 10 );

		// Return early if the request is non blocking
		if ( isset( $args['blocking'] ) && false === $args['blocking'] ) {
			return true;
		}

		if ( ! is_wp_error( $request ) ) {
			/**
			 * Filter the request data of the API response.
			 *
			 * Does not fire on non-blocking requests.
			 *
			 * @param string $url
			 * @param array  $args
			 *
			 * @return array
			 */
			$data = apply_filters( 'wp_stream_api_request_data', json_decode( $request['body'] ), $url, $args );

			// Loose comparison needed
			if (
				200 === absint( $request['response']['code'] )
				||
				201 === absint( $request['response']['code'] )
			) {
				return $data;
			} else {
				$this->errors['errors']['http_code'] = $request['response']['code'];
			}

			if ( isset( $data->error ) ) {
				$this->errors['errors']['api_error'] = $data->error;
			}
		} else {
			$this->errors['errors']['remote_request_error'] = $request->get_error_message();

			$this->plugin->notice( sprintf( '<strong>%s</strong> %s.', esc_html__( 'Stream API Error.', 'stream' ), $this->errors['errors']['remote_request_error'] ) );
		}

		if ( ! empty( $this->errors ) ) {
			delete_transient( $transient );
		}

		return false;
	}
}
