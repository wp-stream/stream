/* Whatever can go here, it's just to see the state of things. */

SELECT COUNT(ID) AS 'number of stream records:' FROM wordpress.wp_stream WHERE action='test';
SELECT COUNT(meta_id) AS 'number of stream meta data rows:' FROM wordpress.wp_stream_meta WHERE meta_key LIKE 'test_meta_key_%';
