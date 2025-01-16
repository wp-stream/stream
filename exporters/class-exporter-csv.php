<?php
/**
 * CSV Exporter
 *
 * @package WP_Stream
 */

namespace WP_Stream;

/**
 * Class - Exporter_CSV
 */
class Exporter_CSV extends Exporter {
	/**
	 * Exporter name
	 *
	 * @var string
	 */
	public $name = 'CSV';

	/**
	 * Exporter slug
	 *
	 * @var string
	 */
	public $slug = 'csv';

	/**
	 * Outputs CSV data for download
	 *
	 * @param array $data Array of data to output.
	 * @param array $columns Column names included in data set.
	 * @return void
	 */
	public function output_file( $data, $columns ) {
		if ( ! defined( 'WP_STREAM_TESTS' ) || ( defined( 'WP_STREAM_TESTS' ) && ! WP_STREAM_TESTS ) ) {
			header( 'Content-type: text/csv' );
			header( 'Content-Disposition: attachment; filename="stream.csv"' );
		}

		ob_start();

		$csv = fopen( 'php://output', 'w' );
		fputcsv( $csv, array_values( $columns ) );

		foreach ( $data as $row ) {
			fputcsv( $csv, $row );
		}
		fclose( $csv ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( ! defined( 'WP_STREAM_TESTS' ) || ( defined( 'WP_STREAM_TESTS' ) && ! WP_STREAM_TESTS ) ) {
			exit;
		}
	}
}
