<?

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: *');


$site = "1Gfey7wVXXg1rxk751TBTxLJwhddDNfcdp";
$private_key = "xxxxxxxx";

$zeronet_dir = "/home/zeronet/p-private/dev1/bin-zeronet";
$messages_json = "$zeronet_dir/data/$site/messages.json";

if (isset($_SERVER['HTTP_REFERER']) and strpos($_SERVER['HTTP_REFERER'], $site) === false) {
	header('HTTP/1.0 403 Forbidden');
	die("Referer error.");
}

echo " Parsing parameters...\n";
// Parse parameters
$auth_id = $_POST["auth_id"];
$auth_id_md5 = md5($auth_id);
$body = $_POST["body"];
$body_safe = htmlentities($body);


if (!$body or !$auth_id) {
	header("HTTP/1.0 400 Bad Request");
	die("Bad parameters");
}

echo " Loading messages.json...\n";
$messages = json_decode(file_get_contents($messages_json));
$message = array(
	"sender" => $auth_id_md5,
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
if (strpos($out, "Site signed!") === false) {
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