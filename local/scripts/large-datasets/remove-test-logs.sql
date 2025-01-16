/* Delete the test data only */

SELECT COUNT(ID) AS 'number of stream records being deleted:' FROM wordpress.wp_stream WHERE action='test';
SELECT COUNT(meta_id) AS 'number of stream meta data rows being deleted:' FROM wordpress.wp_stream_meta WHERE meta_key LIKE 'test_meta_key_%';

SELECT( 'Please be patient, this can take some time' );
DELETE FROM wordpress.wp_stream_meta WHERE meta_key LIKE 'test_meta_key_%';
DELETE FROM wordpress.wp_stream WHERE action='test';
