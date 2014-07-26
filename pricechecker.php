<?php

if (!isset($_GET['itemid']) or $_GET['itemid'] == "") {
	die("ERROR - No 'itemid' specified in GET paramaters. Expected 10 character alphanumeric string.");
}

// Save itemID after cleaning any malicious input
$itemID = htmlspecialchars($_GET['itemid']);

// Check cacheDir for recent entry of the item first
$cacheDir = "cache";

/*
if cacheDir exists {
	if file named itemID exists {
		if datemodified is within last x seconds {
			read file, set itemPrice and itemName
		}
	}
	file is too old or doesnt exist, so clear it
} else {
	create dir
}
*/

if (file_exists($cacheDir)) {
	if (file_exists($cacheDir."/".$itemID)) {
		$dateStrModified = filemtime($cacheDir."/".$itemID);
		$timeTooOldInSec = 1;
		if ((time() - $timeTooOldInSec) < $dateStrModified) {
			// file last modified recent enough, return values from the file
			die("file found, return values from file here.");
		}
	}
	// file doesnt exist or is too old to be useful. Either way, clear it
	file_put_contents($cacheDir."/".$itemID , "");
} else {
	mkdir ($cacheDir, 0755);
}

$amazon_url = "http://www.amazon.com/dp/" . $itemID;

$curl = curl_init();

curl_setopt_array($curl, array(
	CURLOPT_RETURNTRANSFER => 1,
	CURLOPT_HEADER => "Content-Type:application/xml",
	CURLOPT_URL => $amazon_url
));

// If cURL cannot reach site, die with error message
if(!curl_exec($curl)){
	die('ERROR - "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
}

// Handle curl errors
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
if($httpCode == 404) {
	die("ERROR - 404 file not found at amazon.com");
} else {
	// Run the curl
	$responseHTML = curl_exec($curl);
	// Close the curl, free resources
	curl_close($curl);
	
	libxml_use_internal_errors(true);
	$doc = new DOMDocument();
	$doc->loadHTML($responseHTML);
	
	// itemPrice could be in a few different spans
	$itemPrice = $doc -> getElementById('actualPriceValue')->textContent;
	if (is_null($itemPrice) or !isset($itemPrice)) {
		$itemPrice = $doc -> getElementById('priceblock_ourprice')->textContent;
	}
	if (is_null($itemPrice) or !isset($itemPrice)) {
		$finder = new DomXPath($doc);
		$classname="priceLarge";
		$nodes = $finder->query("//*[contains(@class, '$classname')]");
		$itemPrice = $nodes->item(0)->textContent;
	}
	//echo "itemPrice:<br>";var_dump($itemPrice);echo "<br><br>";
	
	$itemName = $doc -> getElementById('btAsinTitle')->textContent;
	//echo "itemName:<br>";var_dump($itemName);echo "<br><br>";
}

// Write the item id, item name, and item price to the cache file (named itemID)
file_put_contents($cacheDir."/".$itemID , $itemID . "\n" . $itemName . "\n" . $itemPrice);

?>

{
	"itemID" : "<?php echo $itemID?>",
	"itemName" : "<?php echo $itemName?>",
	"itemPrice" : "<?php echo $itemPrice?>"
}
