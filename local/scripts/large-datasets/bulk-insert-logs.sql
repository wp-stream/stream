
/* This is a generated file. Run it via `php local/scripts/large-datasets/generate-bulk-insert.php` outside the Docker container. */

 DELIMITER / /


 / / DROP PROCEDURE IF EXISTS generateStreamLogs

 / / CREATE PROCEDURE generateStreamLogs(logdate DATETIME) BEGIN DECLARE i INT DEFAULT 0;

 SELECT ( CONCAT( 'Generating data for ', logdate ) );

 WHILE (i <= 100) DO
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
	 (
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
	),(
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
	),(
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
	),(
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
	),(
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
	),(
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
	),(
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
	),(
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
	),(
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
	),(
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
	);

 INSERT INTO
	 wordpress.wp_stream_meta (record_id, meta_key, meta_value)
 VALUES
	(LAST_INSERT_ID() + 0, 'test_meta_key_1', 'meta_value_1'),
	(LAST_INSERT_ID() + 0, 'test_meta_key_2', 'meta_value_2'),
	(LAST_INSERT_ID() + 0, 'test_meta_key_3', 'meta_value_3'),
	(LAST_INSERT_ID() + 0, 'test_meta_key_4', 'meta_value_4'),
	(LAST_INSERT_ID() + 0, 'test_meta_key_5', 'meta_value_5'),(LAST_INSERT_ID() + 1, 'test_meta_key_1', 'meta_value_1'),
	(LAST_INSERT_ID() + 1, 'test_meta_key_2', 'meta_value_2'),
	(LAST_INSERT_ID() + 1, 'test_meta_key_3', 'meta_value_3'),
	(LAST_INSERT_ID() + 1, 'test_meta_key_4', 'meta_value_4'),
	(LAST_INSERT_ID() + 1, 'test_meta_key_5', 'meta_value_5'),(LAST_INSERT_ID() + 2, 'test_meta_key_1', 'meta_value_1'),
	(LAST_INSERT_ID() + 2, 'test_meta_key_2', 'meta_value_2'),
	(LAST_INSERT_ID() + 2, 'test_meta_key_3', 'meta_value_3'),
	(LAST_INSERT_ID() + 2, 'test_meta_key_4', 'meta_value_4'),
	(LAST_INSERT_ID() + 2, 'test_meta_key_5', 'meta_value_5'),(LAST_INSERT_ID() + 3, 'test_meta_key_1', 'meta_value_1'),
	(LAST_INSERT_ID() + 3, 'test_meta_key_2', 'meta_value_2'),
	(LAST_INSERT_ID() + 3, 'test_meta_key_3', 'meta_value_3'),
	(LAST_INSERT_ID() + 3, 'test_meta_key_4', 'meta_value_4'),
	(LAST_INSERT_ID() + 3, 'test_meta_key_5', 'meta_value_5'),(LAST_INSERT_ID() + 4, 'test_meta_key_1', 'meta_value_1'),
	(LAST_INSERT_ID() + 4, 'test_meta_key_2', 'meta_value_2'),
	(LAST_INSERT_ID() + 4, 'test_meta_key_3', 'meta_value_3'),
	(LAST_INSERT_ID() + 4, 'test_meta_key_4', 'meta_value_4'),
	(LAST_INSERT_ID() + 4, 'test_meta_key_5', 'meta_value_5'),(LAST_INSERT_ID() + 5, 'test_meta_key_1', 'meta_value_1'),
	(LAST_INSERT_ID() + 5, 'test_meta_key_2', 'meta_value_2'),
	(LAST_INSERT_ID() + 5, 'test_meta_key_3', 'meta_value_3'),
	(LAST_INSERT_ID() + 5, 'test_meta_key_4', 'meta_value_4'),
	(LAST_INSERT_ID() + 5, 'test_meta_key_5', 'meta_value_5'),(LAST_INSERT_ID() + 6, 'test_meta_key_1', 'meta_value_1'),
	(LAST_INSERT_ID() + 6, 'test_meta_key_2', 'meta_value_2'),
	(LAST_INSERT_ID() + 6, 'test_meta_key_3', 'meta_value_3'),
	(LAST_INSERT_ID() + 6, 'test_meta_key_4', 'meta_value_4'),
	(LAST_INSERT_ID() + 6, 'test_meta_key_5', 'meta_value_5'),(LAST_INSERT_ID() + 7, 'test_meta_key_1', 'meta_value_1'),
	(LAST_INSERT_ID() + 7, 'test_meta_key_2', 'meta_value_2'),
	(LAST_INSERT_ID() + 7, 'test_meta_key_3', 'meta_value_3'),
	(LAST_INSERT_ID() + 7, 'test_meta_key_4', 'meta_value_4'),
	(LAST_INSERT_ID() + 7, 'test_meta_key_5', 'meta_value_5'),(LAST_INSERT_ID() + 8, 'test_meta_key_1', 'meta_value_1'),
	(LAST_INSERT_ID() + 8, 'test_meta_key_2', 'meta_value_2'),
	(LAST_INSERT_ID() + 8, 'test_meta_key_3', 'meta_value_3'),
	(LAST_INSERT_ID() + 8, 'test_meta_key_4', 'meta_value_4'),
	(LAST_INSERT_ID() + 8, 'test_meta_key_5', 'meta_value_5'),(LAST_INSERT_ID() + 9, 'test_meta_key_1', 'meta_value_1'),
	(LAST_INSERT_ID() + 9, 'test_meta_key_2', 'meta_value_2'),
	(LAST_INSERT_ID() + 9, 'test_meta_key_3', 'meta_value_3'),
	(LAST_INSERT_ID() + 9, 'test_meta_key_4', 'meta_value_4'),
	(LAST_INSERT_ID() + 9, 'test_meta_key_5', 'meta_value_5');

 SET
	 i = i + 1;

 END WHILE;

 END;

 / / DROP PROCEDURE IF EXISTS generateStreamLogsByDays

 / / CREATE PROCEDURE generateStreamLogsByDays() BEGIN DECLARE j INT DEFAULT 0;

 WHILE (j <= 1640) DO CALL generateStreamLogs(
	 DATE_ADD(
		 CAST('2018-07-02 00:00:00' as DATETIME),
		 INTERVAL - j DAY
	 )
 );

 SET
	 j = j + 1;

 END WHILE;

 END;

 / / CALL generateStreamLogsByDays();

 