Introduction
============

If you are using [CouchDB](https://github.com/apache/couchdb) and, possibly, [CouchDB-Lucene](https://github.com/rnewson/couchdb-lucene) to create your PHP site
then these tools might just make your life a little easier.

I've created this project because of lack of querying capabilities in CouchDB's Futon for views and Lucene extension. 
I also know I should've created a couch app, but this way was faster.


Quick-Start Guide
=================

1. Copy project folder to any web directory
   
2. Configure CouchDB access through config.php
        
        <?php
		...
		
        $cdb = new couchClient("http://<user>:<pass>@<host>:<port>/", "<db_name>");
		?>

3. Just use it by running couch.php or lucene.php from your browser

        
Requests
========
		
If anyone knows a good and free JSON formatting tool for PHP, please, let me know

		
Feedback
========

Don't hesitate to submit your feedback, bugs reports and feature requests! 


Special Thanks
==============

Thanks to the [PHP-On-Couch](https://github.com/dready92/PHP-on-Couch) project by [dready92](https://github.com/dready92)
