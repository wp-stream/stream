<?php

class WP_Stream_Notifications_List_Table extends WP_List_Table {

	function __construct( $args = array() ) {
		parent::__construct(
			array(
				'post_type' => 'stream_notifications',
				'plural' => 'records',
				'screen' => isset( $args['screen'] ) ? $args['screen'] : null,
			)
		);

		add_screen_option(
			'per_page',
			array(
				'default' => 20,
				'label'   => __( 'Records per page', 'stream' ),
				'option'  => 'edit_stream_notifications_per_page',
				)
			);

		add_filter( 'set-screen-option', array( __CLASS__, 'set_screen_option' ), 10, 3 );
		set_screen_options();
	}

	function extra_tablenav( $which ) {
		if ( $which == 'top' ){
			$this->filters_form();
		}
	}

	function get_columns(){
		return apply_filters(
			'wp_stream_notifications_list_table_columns',
			array(
				'cb'         => '<span class="check-column"><input type="checkbox" /></span>',
				'name'       => __( 'Name', 'stream' ),
				'type'       => __( 'Type', 'stream' ),
				'alert'      => __( 'Alert', 'stream' ),
				'occurences' => __( 'Occurences', 'stream' ),
				'created'    => __( 'Created', 'stream' ),
			)
		);
	}

	function get_sortable_columns() {
		return array(
			'created' => 'created',
		);
	}

	function prepare_items() {
		$columns  = $this->get_columns();
		$sortable = $this->get_sortable_columns();
		$hidden   = get_hidden_columns( $this->screen );

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $this->get_records();

		$total_items = $this->get_total_found_rows();

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page' => $this->get_items_per_page( 'edit_stream_notifications_per_page', 20 ),
			)
		);
	}

	/**
	 * Render the checkbox column
	 *
	 * @param  array $item Contains all the data for the checkbox column
	 * @return string Displays a checkbox
	 */
	function column_cb( $item ) {
			return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ 'wp_stream_notifications_checkbox',
			/*$2%s*/ $item->ID
		);
	}

	function count_records( $args = array() ) {
		$defaults = array(
			'records_per_page'  => 1,
			'ignore_url_params' => true,
		);

		$args    = wp_parse_args( $args, $defaults );
		$records = $this->get_records( $args );

		return $this->get_total_found_rows();
	}

	function get_records( $args = array() ) {

		$defaults = array(
			'ignore_url_params' => false,
		);
		$args = wp_parse_args( $args, $defaults );

		// Parse sorting params
		if ( ! $order = filter_input( INPUT_GET, 'order' ) ) {
			$order = 'DESC';
		}
		if ( ! $orderby = filter_input( INPUT_GET, 'orderby' ) ) {
			$orderby = '';
		}
		$args['order']          = $order;
		$args['orderby']        = $orderby;
		$args['paged']          = $this->get_pagenum();
		$args['type']           = 'notification_rule';
		$args['ignore_context'] = true;

		if ( ! $args['ignore_url_params'] ) {
			$allowed_params = array(
				'search', 'visibility',
			);

			foreach ( $allowed_params as $param ) {
				if ( $paramval = filter_input( INPUT_GET, $param ) ) {
					$args[$param] = $paramval;
				}
			}
		}

		if ( ! isset( $args['records_per_page'] ) ) {
			$args['records_per_page'] = $this->get_items_per_page( 'edit_stream_notifications_per_page', 20 );
		}

		$items = stream_query( $args );
		return $items;
	}

	function get_total_found_rows() {
		global $wpdb;
		return $wpdb->get_var( 'SELECT FOUND_ROWS()' ); // db call ok, cache ok
	}

	function column_default( $item, $column_name ) {
		switch ( $column_name ) {

			case 'name':
				$out  = $this->column_link( $item->summary, 'name', $item->summary );
				$out .= $this->get_action_links( $item );
				break;

			// TODO: The following columns need to pull actual values
			case 'type':
				$out = __( 'N/A', 'stream' );
				break;

			case 'alert':
				$out = __( 'N/A', 'stream' );
				break;

			case 'occurences':
				$out = '0';
				break;

			case 'created':
				$out  = $this->column_link( get_date_from_gmt( $item->created, 'Y/m/d' ), 'date', date( 'Y/m/d', strtotime( $item->created ) ) );
				$out .= '<br />';
				$out .= 'active' == $item->visibility
					? __( 'Active', 'stream' )
					: __( 'Inactive', 'stream' );
				break;

			default:
				// Register inserted column defaults. Must match a column header from get_columns.
				$inserted_columns = apply_filters( 'wp_stream_notifications_register_column_defaults', $new_columns = array() );

				if ( ! empty( $inserted_columns ) && is_array( $inserted_columns ) ) {
					foreach ( $inserted_columns as $column_title ) {
						/**
						 * If column title inserted via wp_stream_notifications_register_column_defaults ($column_title) exists
						 * among columns registered with get_columns ($column_name) and there is an action associated
						 * with this column, do the action
						 *
						 * Also, note that the action name must include the $column_title registered
						 * with wp_stream_notifications_register_column_defaults
						 */
						if ( $column_title == $column_name && has_action( 'wp_stream_notifications_insert_column_default-' . $column_title ) ) {
							$out = do_action( 'wp_stream_notifications_insert_column_default-' . $column_title, $item );
						} else {
							$out = $column_name;
						}
					}
				} else {
					$out = $column_name; // xss okay
				}
				break;
		}

		echo $out; // xss okay
	}


	public static function get_action_links( $record ){
		$out          = '';
		$custom_links = apply_filters( 'wp_stream_notifications_custom_action_links_' . $record->ID, array(), $record );

		$out .= '<div class="row-actions">';

		$activation_nonce = wp_create_nonce( "activate-record_$record->ID" );

		$action_links = array();
		$action_links[ __( 'Edit', 'stream' ) ] = admin_url(
			sprintf(
				'admin.php?page=wp_stream_notifications&view=edit&id=%s',
				$record->ID
			)
		);

		if ( 'active' == $record->visibility ) {
			$action_links[ __( 'Deactivate', 'stream' ) ] = admin_url(
				sprintf(
					'admin.php?page=wp_stream_notifications&action=deactivate&r=%s&_wpnonce=%s',
					$record->ID,
					$activation_nonce
				)
			);
		} elseif ( 'inactive' == $record->visibility ) {
			$action_links[ __( 'Activate', 'stream' ) ] = admin_url(
				sprintf(
					'admin.php?page=wp_stream_notifications&action=activate&r=%s&_wpnonce=%s',
					$record->ID,
					$activation_nonce
				)
			);
		}

		if ( $action_links ) {
			$links = array();
			$i     = 0;
			foreach ( $action_links as $al_title => $al_href ) {
				$i++;
				$links[] = sprintf(
					'<span><a href="%s" class="action-link">%s</a>%s</span>',
					$al_href,
					$al_title,
					( $i === count( $action_links ) ) ? null : ' | '
				);
			}
			$out .= implode( '', $links );
		}

		if ( $custom_links ) {
			$out .= ' | ';
		}

		if ( $custom_links && is_array( $custom_links ) ) {
			$last_link = end( $custom_links );
			foreach ( $custom_links as $key => $link ) {
				$out .= $link;
				if ( $key != $last_link ) {
					$out .= ' | ';
				}
			}
		}

		$out .= '</div>';

		return $out;
	}

	function column_link( $display, $key, $value = null, $title = null ) {
		$url = admin_url( 'admin.php?page=' . WP_Stream_Notifications::NOTIFICATIONS_PAGE_SLUG );

		$args = ! is_array( $key ) ? array( $key => $value ) : $key;

		foreach ( $args as $k => $v ) {
			$url = add_query_arg( $k, $v, $url );
		}

		return sprintf(
			'<a href="%s" title="%s">%s</a>',
			esc_url( $url ),
			esc_attr( $title ),
			esc_html( $display )
		);
	}

	function filters_form() {
		$filters_string = sprintf( '<input type="hidden" name="page" value="%s"/>', WP_Stream_Notifications::NOTIFICATIONS_PAGE_SLUG );

		echo sprintf(
			'<ul class="subsubsub">
				%s
			</ul>
			%s
			<div class="alignleft actions">
				%s
			</div>',
			$this->list_navigation(),
			$this->filter_search(),
			$filters_string
		); // xss okay
	}

	function list_navigation() {
		$navigation_items = array(
			'all' => array(
				'link_text' => __( 'All', 'stream' ),
				'url'  => admin_url( sprintf( 'admin.php?page=%s', WP_Stream_Notifications::NOTIFICATIONS_PAGE_SLUG ) ),
				'link_class' => null,
				'li_class'   => null,
				'count'      => $this->count_records(),
			),
			'active' => array(
				'link_text' => __( 'Active', 'stream' ),
				'url'  => admin_url( sprintf( 'admin.php?page=%s&visibility=active', WP_Stream_Notifications::NOTIFICATIONS_PAGE_SLUG ) ),
				'link_class' => null,
				'li_class'   => null,
				'count'      => $this->count_records( array( 'visibility' => 'active' ) ),
			),
			'inactive' => array(
				'link_text' => __( 'Inactive', 'stream' ),
				'url'  => admin_url( sprintf( 'admin.php?page=%s&visibility=inactive', WP_Stream_Notifications::NOTIFICATIONS_PAGE_SLUG ) ),
				'link_class' => null,
				'li_class'   => null,
				'count'      => $this->count_records( array( 'visibility' => 'inactive' ) ),
			),
		);

		$navigation_items = apply_filters( 'wp_stream_notifications_list_navigation_array', $navigation_items );

		$navigation_links = array();
		$navigation_html  = '';
		$visibility       = filter_input( INPUT_GET, 'visibility', FILTER_DEFAULT, array( 'options' => array( 'default' => 'all' ) ) );

		$i = 0;

		foreach ( $navigation_items as $visibility_filter => $item ) {
			$i++;
			$navigation_links[] = sprintf(
				'<li class="%s"><a href="%s" class="%s">%s%s</a>%s</li>',
				esc_attr( $item[ 'li_class' ] ),
				esc_attr( $item[ 'url' ] ),
				$visibility == $visibility_filter
					? 'current ' . esc_attr( $item[ 'link_class' ] )
					: esc_attr( $item[ 'link_class' ] ),
				esc_html( $item[ 'link_text' ] ),
				$item[ 'count' ] !== null
					? sprintf( ' <span class="count">(%s)</span>', esc_html( $item[ 'count' ] ) )
					: '',
				$i === count( $navigation_items ) ? '' : ' | '
			);
		}

		$navigation_links = apply_filters( 'wp_stream_notifications_list_navigation_links', $navigation_links );
		$navigation_html  = is_array( $navigation_links ) ? implode( "\n", $navigation_links ) : $navigation_links;

		return $navigation_html;
	}

	function filter_search() {
		$out = sprintf(
			'<p class="search-box">
				<label class="screen-reader-text" for="record-search-input">%1$s:</label>
				<input type="search" id="record-search-input" name="search" value="%2$s" />
				<input type="submit" name="" id="search-submit" class="button" value="%1$s" />
			</p>',
			esc_attr__( 'Search Notifications', 'stream' ),
			isset( $_GET['search'] ) ? esc_attr( $_GET['search'] ) : null
		);

		return $out;
	}

	function display() {
		echo '<form method="get" action="', esc_attr( admin_url( 'admin.php' ) ), '">';
		parent::display();
		echo '</form>';
	}

	function display_tablenav( $which ) {
		if ( 'top' == $which ) { ?>
			<div class="tablenav <?php echo esc_attr( $which ); ?>">
				<?php
				$this->extra_tablenav( $which );
				$this->pagination( $which );
				?>

				<br class="clear" />
			</div>
		<?php } else { ?>
			<div class="tablenav <?php echo esc_attr( $which ); ?>">
				<?php
				do_action( 'wp_stream_notifications_after_list_table' );
				$this->extra_tablenav( $which );
				$this->pagination( $which );
				?>

				<br class="clear" />
			</div>
		<?php
		}
	}

	static function set_screen_option( $dummy, $option, $value ) {
		if ( $option == 'edit_stream_notifications_per_page' ) {
			return $value;
		} else {
			return $dummy;
		}
	}

}
