<?php
/*********************************************************************
* Please note, this is NOT an example of stable code ...
**********************************************************************
*
* MySQL is horrible, but it doesn't use an object and resource per connection as MySQLi does, 
* because of this you are able to share a single mysql connection among contexts
*
********************************************************************************
* THIS DOES NOT MAKE IT A GOOD IDEA, I REPEAT, THIS DOES NOT MAKE IT A GOOD IDEA
********************************************************************************
*
* Some helpful notes ...
*	If some logic crashes while you are sharing resources among contexts, try:
		introduce a mutex  
		free results explicitly in the thread that queried for them
		try another database driver
		try another database server
		disable any buffering that you can control ( client side )
		if you're working on the command line try USE_ZEND_ALLOC=0, ie:
			USE_ZEND_ALLOC=0 /usr/local/bin/php my-script.php
	Please note that running without zend alloc is also officially unsupported
		but can sometimes yield a predictable result, as in the case of this script.
*
*	The reasons logic may fail come down to reallocation of buffers in the underlying streams
*	Zend's allocation mechanism is built to not share, when you try to reallocate a chunk of memory allocated in another thread
*	zend will throw an error. Using the suggested environment variable may work around this, but leaves you open to memory errors
*	that zend usually manages for extensions that rely on memory management freeing memory when necessary.
*	Please remember that the resources as defined in C were never meant to be shared, they are designed with this in mind, and most
*	types of resource WILL have problems.
*
* The preferable way to use MySQL is MySQLi or PDO, there is a connection-per-thread (much preferred) example using MySQLi in MySQLi.php
*
* This code remains to keep history in tact - ish
*/
class MyShared extends Thread {
	public function __construct($mysql, $mutex = null){
		$this->mysql = $mysql;
		$this->mutex = $mutex;
	}

	public function run(){
		if ($this->mutex)
			printf("LOCK(%d): %d\n", $this->getThreadId(), Mutex::lock($this->mutex));

		if (($result = mysql_query("SHOW PROCESSLIST;", $this->mysql))) {
			while(($row = mysql_fetch_assoc($result))) {
				print_r($row);
			}
		}

		if ($this->mutex)
			printf("UNLOCK(%d): %d\n", $this->getThreadId(), Mutex::unlock($this->mutex));
	}
}


$mysql = mysql_connect("127.0.0.1", "root", "");
if ($mysql) {
	$mutex = Mutex::create();
	$instances = array(new MyShared($mysql, $mutex), new MyShared($mysql, $mutex), new MyShared($mysql, $mutex));
	foreach($instances as $instance)
		$instance->start();
	foreach($instances as $instance)
		$instance->join();
	Mutex::destroy($mutex);
}
?>
