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

        // Looking for the item name, something like this:
        // <span id="productTitle" class="a-size-large">ASUS VS228H-P 22-Inch Full-HD 5ms LED-Lit LCD Monitor</span>
        //$nameSpanIdPosition = strpos($responseHTML,"productTitle");
        // or this:
        // <span id="btAsinTitle">PNY 64GB SDXC Elite Performance UHS-1 90MB/sec</span>
        //if (!$nameSpanIdPosition) {
        //      $nameSpanIdPosition = strpos($responseHTML,"btAsinTitle");
        //}
  // Shorten the string by getting 100 chars around the price
  //$shorterStr = substr($responseHTML,$nameSpanIdPosition,200);
        // Regexp match for a price in the string
        $pattern = '/<title>(.*?)<\/title>/';
  $pattern = '/Amazon\.com\: (.*?)<\/title>/';
  preg_match($pattern, $responseHTML, $matches);
        $itemName = htmlspecialchars($matches[0]);
  str_replace(find,replace,string,count);
  $itemName = 

        /*
        // Looking for itemName in the html <title></title> tag
        $titleTagStart = strpos($responseHTML,"<title>");
        */
        // Cannot get regexp to work for now, hard coding name.
        //$itemName = "ASUS VS228H-P 22-Inch Full-HD 5ms LED-Lit LCD Monitor";

        // Looking for the price, something like this:
        // <span id="priceblock_ourprice" class="a-size-medium a-color-price">$129.99</span>
        $priceSpanIdPosition = strpos($responseHTML,"priceblock_ourprice");
        // or this:
        // <b class="priceLarge">$34.81</b>
        if (!$priceSpanIdPosition) {
                $priceSpanIdPosition = strpos($responseHTML,"priceLarge");
        }
        // Shorten the string by getting 100 chars around the price
        $shorterStr = htmlspecialchars(substr($responseHTML,$priceSpanIdPosition,100));
        // Regexp match for a price in the string
        $pattern = '/(\$[0-9]+(\.[0-9]{2})?)/';
        preg_match($pattern, $shorterStr, $matches);
        $itemPrice = $matches[0];
}

// Close the curl, free resources
curl_close($curl);

// Write the results to the cache file
file_put_contents($cacheDir."/".$itemID , $itemName . " " . $itemPrice);

?>

{
        "itemName" : "<?php echo $itemName?>",
        "itemPrice" : "<?php echo $itemPrice?>"
}

<p><?php echo $responseHTML?></p>
