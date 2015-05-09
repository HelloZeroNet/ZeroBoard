<?

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: *');

$site = "1Gfey7wVXXg1rxk751TBTxLJwhddDNfcdp";
$site_domain = "board.zeronetwork.bit";
$private_key = "xxxxxxxx";
$zeronet_dir = "/home/sumo/p-private/dev1/bin-zeronet";
$messages_json = "$zeronet_dir/data/$site/messages.json";

if (isset($_SERVER['HTTP_REFERER']) and strpos($_SERVER['HTTP_REFERER'], $site) === false and strpos(strtolower($_SERVER['HTTP_REFERER']), $site_domain) === false) {
	header('HTTP/1.0 403 Forbidden');
	die("Referer error.");
}


echo " Parsing parameters...\n";

$auth_key = $_POST["auth_key"];
if ($_POST["hash"] == "sha512")
	$auth_key_hash = substr(hash("sha512", $auth_key), 0, 64);
else // Backward compatibility
	$auth_key_hash = md5($auth_key);

$body = $_POST["body"];
$body_safe = htmlentities($body);


if (!$body or !$auth_key) {
	header("HTTP/1.0 400 Bad Request");
	die("Bad parameters");
}


echo " Loading messages.json...\n";
$messages = json_decode(file_get_contents($messages_json));
$message = array(
	"sender" => $auth_key_hash,
	"body" => $body_safe,
	"added" => time()
);
array_unshift($messages, $message);
$out = json_encode($messages, JSON_PRETTY_PRINT);


echo " Writing messages.json...\n";
$f = fopen($messages_json, "w");
fwrite($f, $out);
fclose($f);


echo " Signing content...\n";
chdir($zeronet_dir);
$out = array();
exec("python zeronet.py --debug siteSign $site $private_key 2>&1", $out);
$out = implode("\n", $out);

if (strpos($out, "content.json signed!") === false) {
	header("HTTP/1.0 500 Internal Server Error");
	die("Site signing error");
}


echo " Publishing content...\n";
$out = array();
$server_ip = $_SERVER['SERVER_ADDR'];
exec("python zeronet.py --debug --ip_external $server_ip sitePublish $site 2>&1", $out);
$out = implode("\n", $out);

if (strpos($out, "Successfuly published") === false) {
	header("HTTP/1.0 500 Internal Server Error");
	die("Publish error");
}


echo "OK";

?>