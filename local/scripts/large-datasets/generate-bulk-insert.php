<?php
/**
 * Helper script to generate bulk insert sql.
 */

 // The total number of stream records to insert per day will be:
 // $number_of_values_to_insert_at_a_time * $number_of_inserts_per_day
 // You can play with these numbers to see performance.
$number_of_values_to_insert_at_a_time = 10;
$number_of_inserts_per_day            = 100;

// The number of days to insert data.
$number_of_days                       = 1640;

// The starting date for the insert. The days will run backwards from this.
$starting_date = '2018-07-02 00:00:00';

$stream_values      = [];
$stream_meta_values = [];

for ( $j=0; $j < $number_of_values_to_insert_at_a_time; $j++ ) {
	$stream_values[] = "(
		1,
		1,
		i,
		1,
		'administrator',
		'This is the summary',
		logdate,
		'posts',
		'post',
		'test',
		'127.0.0.1'
	)";

	$stream_meta_values[] = "(LAST_INSERT_ID() + {$j}, 'test_meta_key_1', 'meta_value_1'),
	(LAST_INSERT_ID() + {$j}, 'test_meta_key_2', 'meta_value_2'),
	(LAST_INSERT_ID() + {$j}, 'test_meta_key_3', 'meta_value_3'),
	(LAST_INSERT_ID() + {$j}, 'test_meta_key_4', 'meta_value_4'),
	(LAST_INSERT_ID() + {$j}, 'test_meta_key_5', 'meta_value_5')";
}

ob_start();
?>

/* This is a generated file. Run it via `php local/scripts/large-datasets/generate-bulk-insert.php` outside the Docker container. */

 DELIMITER / /


 / / DROP PROCEDURE IF EXISTS generateStreamLogs

 / / CREATE PROCEDURE generateStreamLogs(logdate DATETIME) BEGIN DECLARE i INT DEFAULT 0;

 SELECT ( CONCAT( 'Generating data for ', logdate ) );

 WHILE (i <= <?php echo (int) $number_of_inserts_per_day; ?>) DO
 INSERT INTO
	 wordpress.wp_stream (
		 site_id,
		 blog_id,
		 object_id,
		 user_id,
		 user_role,
		 summary,
		 created,
		 connector,
		 context,
		 `action`,
		 ip
	 )
 VALUES
	 <?php echo implode( ',', $stream_values ); ?>;

 INSERT INTO
	 wordpress.wp_stream_meta (record_id, meta_key, meta_value)
 VALUES
	<?php echo implode( ',', $stream_meta_values ); ?>;

 SET
	 i = i + 1;

 END WHILE;

 END;

 / / DROP PROCEDURE IF EXISTS generateStreamLogsByDays

 / / CREATE PROCEDURE generateStreamLogsByDays() BEGIN DECLARE j INT DEFAULT 0;

 WHILE (j <= <?php echo (int) $number_of_days; ?>) DO CALL generateStreamLogs(
	 DATE_ADD(
		 CAST('<?php echo $starting_date; ?>' as DATETIME),
		 INTERVAL - j DAY
	 )
 );

 SET
	 j = j + 1;

 END WHILE;

 END;

 / / CALL generateStreamLogsByDays();

 <?php

$sql = ob_get_clean();

file_put_contents( __DIR__ . '/bulk-insert-logs.sql', $sql );
