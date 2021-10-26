<?php
    function getRandomElement( $arr ){
        $keys = array_keys($arr);
        $len = count( $keys );
        $rand = mt_rand( 0, $len-1 );
        $index = $keys[$rand];
        return [$index, $arr[ $index ]];
    }
    function makeUpdateTokenURL( $TRACKER_URL, $indexes ){
        $s = $TRACKER_URL.'/click.php?lp=data_upd&';
        foreach ($indexes as $tokenName => $tokenValue) {
            $s .= "{$tokenName}={$indexes[$tokenName]}&";
        }
        $s = rtrim($s, "&");
        return $s;
    }
    function getChosen( $blockSet, $randomOptions ){
        $indexes = array();
        $result = array();
        foreach ($blockSet as $blockName=>$values) {
            $indexes[$blockName] = [];
            $result[$blockName]  = [];

            if ( !isset($randomOptions[$blockName]) || $randomOptions[$blockName]==="random" ){
                list($index, $element) = getRandomElement( $values );
            } else {
                $index   = $randomOptions[$blockName];
                $element = $values[$index];
            }

            $indexes[$blockName] = $index;
            $result[$blockName]  = $element;
        }
        return [ $indexes, $result ];
    }
    function replaceNbspWithSpace($content){
        $string = htmlentities($content, null, 'utf-8');
        $content = str_replace("&nbsp;", " ", $string);
        $content = html_entity_decode($content);
        return $content;
    }

    // Your tracker's domain
    $TRACKER_DOMAIN = 'https://tracker.com';

    // get source code
    $source = show_source(__FILE__, true);
    $source = html_entity_decode($source);

    // define symbol
    $symToken = json_decode('"' . '\u25CA' . '"');
    $symNameStart = json_decode('"' . '\u25E2' . '"');
    $symNameEnd = json_decode('"' . '\u25B6' . '"');
    $symValueSep = json_decode('"' . '\u2588' . '"');
    $symValueEnd = json_decode('"' . '\u25C0' . '"');

    // find token symbol
    $lenToken = strlen($symToken);
    $tokenPos = strpos($source, $symToken);    
    if (!$tokenPos) {
        exit;
    }   

    // find another special characters
    $startPos = -1;
    // // // $indexFound = 0;
    $allData = [];
    $urlData = [];
    $options = [];
    $optionsName = [];    
    
    $indexPos = $tokenPos;

    while ($startPos) {
        $startPos = strpos($source, $symNameStart, $indexPos + $lenToken);
        if (!$startPos)
            break;

        $endPos = strpos($source, $symValueEnd, $startPos + $lenToken);
        if (!$endPos)
            break;

        // echo $startPos . ' - ' . $endPos . "\r\n";
            
        // // // $indexFound = $indexFound + 1;
        $eleString = substr($source, $startPos + $lenToken, $endPos - $startPos);
        // echo $eleString . "\r\n";

        // get elements
        $endPosOfName = strpos($eleString, $symNameEnd);

        // name
        $eleName = substr($eleString, 0, $endPosOfName);
        // echo $eleName . "\r\n";
        array_push($optionsName, $eleName);

        // values
        $valueString = substr($eleString, strlen($eleName) + $lenToken, -$lenToken);
        // // // $valueString = str_replace("&nbsp;", " ", $valueString);
        // echo $valueString . "\r\n";

        $values = explode($symValueSep, $valueString);
        // echo $values[2] . "\r\n";

        // add to array
        $key = 1;
        foreach ($values as $value) {
            // echo $value . "\r\n";
            $allData[$eleName][strval($key)] = $value;
            // echo $allData[$eleName][strval($key)] . "\r\n";
            $key = $key + 1;
        }
    
        $indexPos = $endPos + $lenToken;
    }

    // read url parameter
    foreach ($optionsName as $option) {
        if (isset($_GET[$option])) {

            $values = explode('|', $_GET[$option]);              
            // $options[$option] = $values;  
            
            // $index = 1;            
            foreach ($allData[$option] as $key => $value) {
                if (in_array($key, $values)) {
                    // $urlData[$option][strval($index)] = $allData[$option][$key];
                    // $index = $index + 1;
                    $urlData[$option][strval($key)] = $allData[$option][$key];
                }
            }
        }                 
        else {
            // $options[$option] = ["random"];                
            $urlData[$option] = $allData[$option];
        }
    }

    foreach ($optionsName as $name) {
        $options[$name] = "random";
    }

    // print_r($allData);
    // print_r($urlData);
    // print_r($options);

    list( $indexes, $result ) = getChosen( $urlData, $options );
    $updateTokensURL = makeUpdateTokenURL( $TRACKER_DOMAIN, $indexes );    

    $updateLPTokensScript = <<<EOT
        <script type="text/javascript">
        var o = document.createElement("img");
        o.src="{$updateTokensURL}";
        </script>
        EOT;

    // generate new body content
    $newBody = '';
    $preStart = '<pre';
    $preEnd = '</pre>';
    $lenPreStart = strlen($preStart);
 
    $bodyPosStart = strpos($source, $preStart);  
    if ($bodyPosStart) {

        // skip the first occurance
        $bodyPosStart = strpos($source, $preStart, $bodyPosStart + $lenPreStart);            
        if ($bodyPosStart) {

            // echo $bodyPosStart . "\r\n";

            $bodyPosEnd = strpos($source, $preEnd, $bodyPosStart + $lenPreStart);
            $bodyString = substr($source, $bodyPosStart + $lenPreStart + 12, $bodyPosEnd - $bodyPosStart - $lenPreStart - 12);
            $bodyString = str_replace("<br />", '', $bodyString);
            // echo $bodyString . "\r\n";

            $temp = $bodyString;

            foreach ($result as $key => $value) {
                $pattern = '/◢' . $key . '[^◀]*◀/u';
                // echo $pattern;
                $temp = preg_replace($pattern, $value, $temp);
            }
            // echo $temp . "\r\n";
            $newBody = '<div>' . $temp . '</div>';                   
            $newBody = replaceNbspWithSpace($newBody);
            //$newBody = $newBody . $updateLPTokensScript;
        }
    } 
?>

<!DOCTYPE html>
<html>
<head>
<title>php-tracking!</title>
<!-- Don't remove --><?php echo $updateLPTokensScript; ?><!-- END -->
</head>
<body>
<!-- Don't remove --><pre hidden>◊<!-- END -->
<!-- Specify the first element -->
<h1>
◢Name1▶Value 1█Value 2█Value 3█Value 4█Value 5◀
</h1>
<!-- Specify the second element -->
<p>
◢Element2▶HTML code 1█HTML code 2█HTML code 3█HTML code 4◀
</p>
<!-- Specify the third font-color token -->
<a style="color: ◢Color▶Red█Yellow█Pink◀;font-size:300%;text-decoration-line:none" href="" > Link to the offer </a>
<!-- Don't remove --></pre><?php echo $newBody; ?><!-- END -->
</body>
</html>
