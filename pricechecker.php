<?php


//error_reporting(E_ALL);
//ini_set('display_errors', 1);


if (!isset($_GET['itemId']) or $_GET['itemId'] == "") {
	die("ERROR - No 'itemId' specified in GET paramaters. Expected 10 character alphanumeric string.");
}

// Save itemId after cleaning any malicious input
$itemId = htmlspecialchars($_GET['itemId']);

// Check cacheDir for recent entry of the item first
$cacheDir = "cache";

if (file_exists($cacheDir)) {  // if cacheDir exist
	if (file_exists($cacheDir."/".$itemId)) {  // if file named itemId exists
		$dateStrModified = filemtime($cacheDir."/".$itemId);
		$timeTooOldInSec = 1;
		if ((time() - $timeTooOldInSec) < $dateStrModified) {  // if datemodified is within last x seconds
			// file last modified recent enough, return values from the file
			die("file found, return values from file here.");
			// TODO Read in id, name, price from cache file
		}
	}
	// file doesnt exist or is too old to be useful. Either way, clear it
	file_put_contents($cacheDir."/".$itemId , "");
} else {
	// cacheDir doesn't exist yet, create it so we can start caching amazon searches
	mkdir ($cacheDir, 0755);
}

// Couldnt find cached information for this product, search amazon.com

$amazon_url = "http://www.amazon.com/dp/" . $itemId;

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

// Write the item Id, item name, and item price to the cache file (named itemId)
file_put_contents($cacheDir."/".$itemId , $itemId . "\n" . $itemName . "\n" . $itemPrice);

?>

{
	"itemId" : "<?php echo $itemId?>",
	"itemName" : "<?php echo $itemName?>",
	"itemPrice" : "<?php echo $itemPrice?>"
}
