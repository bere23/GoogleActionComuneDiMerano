<?php


function get_string_between($string, $start, $end){
	global $debugfunc;
	$string = ' ' . $string;
	$ini = strpos($string, $start);
	$found = ($ini > 0);
	if ($found) {
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;
		$found = ($len > 0);
		if ($found) {
			return substr($string, $ini, $len);
		}
	}
	return "";
}

function get_string_title_and_body($string, $start, $end){
	global $debugfunc;
	$string = ' ' . $string;
	$ini = strpos($string, $start);
	$found = ($ini > 0);
	if ($found) {
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;
		$found = ($len > 0);
		if ($found) {

			$str["title"] = substr($string, $ini, $len);
			$str["body"] = substr($string, $ini + $len - 5);

		}
	}
	return $str;
}

function get_all_strings_between($string, $start, $end){
	$res = array();
	$found = true;

	while ($found) {
		$string = ' ' . $string;
		$ini = strpos($string, $start);

		$found = ($ini > 0);
		if ($found) {
			$ini += strlen($start);
			$len = strpos($string, $end, $ini) - $ini;
			$found = ($len > 0);
			if ($found) {

				$res[] =  substr($string, $ini, $len);

			}
			$string = substr($string, $ini  + $len + strlen( $end));
		}
	}


	return array_unique($res);
}

function getTags( $dom, $tagName, $attrName = "", $attrValue = "" ){
	$html = '';
	$domxpath = new DOMXPath($dom);
	$newDom = new DOMDocument;
	$newDom->formatOutput = true;

	if ($attrName == "") {
		$filtered = $domxpath->query("//$tagName" );
	} else {
		$filtered = $domxpath->query("//$tagName" . '[@' . $attrName . "='$attrValue']");
	}

	// $filtered =  $domxpath->query('//div[@class="className"]');
	// '//' when you don't know 'absolute' path

	// since above returns DomNodeList Object
	// I use following routine to convert it to string(html); copied it from someone's post in this site. Thank you.
	$i = 0;
	while( $myItem = $filtered->item($i++) ){
		$node = $newDom->importNode( $myItem, true );    // import node

		$newDom->appendChild($node);                    // append node
	}
	$html = $newDom->saveHTML();
	return $html;
}



function getTagsAsArray( $dom, $tagName, $attrName = "", $attrValue = "" ){
	$domxpath = new DOMXPath($dom);

	if ($attrName == "") {
		$filtered = $domxpath->query("//$tagName" );
	} else {
		$filtered = $domxpath->query("//$tagName" . '[@' . $attrName . "='$attrValue']");
	}

	// $filtered =  $domxpath->query('//div[@class="className"]');
	// '//' when you don't know 'absolute' path

	// since above returns DomNodeList Object
	// I use following routine to convert it to string(html); copied it from someone's post in this site. Thank you.
	$i = 0;
	$res = array();
	while( $myItem = $filtered->item($i++) ){
		$html = '';

		$newDom = new DOMDocument;
		$newDom->formatOutput = true;

		$node = $newDom->importNode( $myItem, true );    // import node

		$newDom->appendChild($node);
		$html = $newDom->saveHTML();                  // append node
		$res[] = $html;
	}

	return $res;
}

function getTagsInHTML($html, $tagName, $attrName = "", $attrValue = "" ) {
	$dom = new DOMDocument;
	$dom->preserveWhiteSpace = false;
	$dom->encoding = 'UTF-8';
	$html= mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8");
	@$dom->loadHtml($html);
	return getTags( $dom, $tagName, $attrName, $attrValue);
}

function getTagsAsArrayInHTML($html, $tagName, $attrName = "", $attrValue = "" ) {
	$dom = new DOMDocument;
	$dom->preserveWhiteSpace = false;
	$dom->encoding = 'UTF-8';
	$html= mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8");
	@$dom->loadHtml($html);
	return getTagsAsArray( $dom, $tagName, $attrName, $attrValue);
}

function getFirstTag( $dom, $tagName, $attrName, $attrValue ){
	$html = '';
	$domxpath = new DOMXPath($dom);
	$newDom = new DOMDocument;
	$newDom->formatOutput = true;

	$filtered = $domxpath->query("//$tagName" . '[@' . $attrName . "='$attrValue']");
	// $filtered =  $domxpath->query('//div[@class="className"]');
	// '//' when you don't know 'absolute' path

	// since above returns DomNodeList Object
	// I use following routine to convert it to string(html); copied it from someone's post in this site. Thank you.
	$i = 0;
	if( $myItem = $filtered->item($i++) ){
		$node = $newDom->importNode( $myItem, true );    // import node

		$newDom->appendChild($node);                    // append node
	}
	$html = $newDom->saveHTML();
	return $html;
}

function getTagsWithText( $dom, $tagName, $attrName, $attrValue, $txtToFind ){
	$html = '';
	$domxpath = new DOMXPath($dom);


	$filtered = $domxpath->query("//$tagName" . '[@' . $attrName . "='$attrValue']");
	// $filtered =  $domxpath->query('//div[@class="className"]');
	// '//' when you don't know 'absolute' path

	// since above returns DomNodeList Object
	// I use following routine to convert it to string(html); copied it from someone's post in this site. Thank you.
	$i = 0;
	while( $myItem = $filtered->item($i++) ){
		$newDom = new DOMDocument;
		$newDom->formatOutput = true;
		$node = $newDom->importNode( $myItem, true );    // import node
		$newDom->appendChild($node);
		$html2 = $newDom->saveHTML();                  // append node
		if (strpos($html2, $txtToFind) && (strlen($html)==0)) {
			$html = $html2;
		}
	}

	return $html;
}

function cleanHtml($html) {
	$dom = new DOMDocument();
	$dom->loadHTML($html);
	$list = $dom->getElementsByTagName("a");

	while ($list->length > 0) {
		$node = $list->item(0);

		if (trim($node->textContent) == "Chiudi") {
			$node->parentNode->replaceChild(new DOMText(''), $node);
		} else {
			$belem = $dom->createElement('h3');
			$belem->appendChild(new DOMText($node->textContent));
			$node->parentNode->replaceChild($belem, $node);
		}
	}
	$str = $dom->saveHTML();
	return $str;

}

function removeBomUtf8($z){
	$s = $z;
	if(substr($s,0,3)==chr(hexdec('EF')).chr(hexdec('BB')).chr(hexdec('BF'))){
		$s=substr($s,3);
	}
	for ($i = 0; $i <= 31; ++$i) {
		$s = str_replace(chr($i), "", $s);
	}
	$s = stripslashes(str_replace(chr(127), "", $s));
	return $s;
}


?>