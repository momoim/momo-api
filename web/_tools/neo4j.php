<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-type: text/html; charset=utf-8");
define('ADMIN_USERNAME', 'admin'); // Admin Username
define('ADMIN_PASSWORD', 'passw0rd'); // Admin Password

///////////////// Password protect ////////////////////////////////////////////////////////////////
if (! isset($_SERVER['PHP_AUTH_USER']) || ! isset($_SERVER['PHP_AUTH_PW']) ||
	$_SERVER['PHP_AUTH_USER'] != ADMIN_USERNAME || $_SERVER['PHP_AUTH_PW'] != ADMIN_PASSWORD
)
{
	Header("WWW-Authenticate: Basic realm=\"Neo4j Login\"");
	Header("HTTP/1.0 401 Unauthorized");

	echo <<<EOB
				<html><body>
                <h1>Rejected!</h1>
                <big>Wrong Username or Password!</big>
                </body></html>
EOB;
	exit;
}
try
{
//	require ('phar://neo4jphp.phar');
	require __DIR__.'/../application/vendor/neo4jphp/bootstrap.php';
	$client = new \Everyman\Neo4j\Client('10.1.242.125', 7474);

	$server_info = $client->getServerInfo();
	echo 'neo4j version = ' . $server_info['neo4j_version'];
	$result = '';
} catch (\Everyman\Neo4j\Exception $e)
{
	$result = $e->getTraceAsString();
	echo str_replace("\n", '<br/>', print_r($result, true));
}
echo '<hr />';
$output = '';
if ($_POST && ! empty($_POST['query']))
{
	try
	{
		$result = array();
		$_POST['query'] = trim($_POST['query']);
		$query = new \Everyman\Neo4j\Cypher\Query($client, $_POST['query']);
		$res = $query->getResultSet();

		foreach($res as $row) {
			/** @var Everyman\Neo4j\Query\Row $row*/
			$proxy = $row->current();
			switch(TRUE) {
				case $proxy instanceof Everyman\Neo4j\Node:
					/** @var Everyman\Neo4j\Node $proxy */
					$result[$proxy->getId()] = $proxy->getProperties();
					break;
				case $proxy instanceof Everyman\Neo4j\Relationship:
					/** @var Everyman\Neo4j\Relationship $proxy */
					$result[$proxy->getId()] = array(
						'type' => $proxy->getType(),
						'properties' => $proxy->getProperties()
					);
					break;
				default:
					$r = array();
					foreach($row as $val) {
						$r[] = $val;
					}
					$result[] = $r;
					break;

			}



		}

	} catch (\Everyman\Neo4j\Exception $e)
	{
		$result = $e->getTraceAsString();
	}
}
$output = str_replace("\n", '<br/>', print_r($result, true));

?>
<form action="" method="post">
    <label for="query">查询语句：</label> <textarea rows="5" cols="40" id="query"
                                               name="query"><?php echo ! empty($_POST['query']) ? $_POST['query']
	: ''; ?></textarea>
    <input type="submit">
    <br/>
    查询结果：<br/>
<span id="result">
<?php echo $output; ?>
</span>
</form>


