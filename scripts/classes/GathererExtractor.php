<?php
////////////////////////////////////////////////////////////////////////
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
////////////////////////////////////////////////////////////////////////
class GathererExtractor {
	public $cards = array();
	public $cardsCN = array();
	public $cardsTW = array();
	public $cardsFR = array();
	public $cardsDE = array();
	public $cardsIT = array();
	public $cardsJP = array();
	public $cardsPT = array();
	public $cardsRU = array();
	public $cardsES = array();
	public $cardsKO = array();
	public $cardsCNflavor = array();
	public $cardsTWflavor = array();
	public $cardsFRflavor = array();
	public $cardsDEflavor = array();
	public $cardsITflavor = array();
	public $cardsJPflavor = array();
	public $cardsPTflavor = array();
	public $cardsRUflavor = array();
	public $cardsESflavor = array();
	public $cardsKOflavor = array();
	public $sides = array();
	public $titleID = array();

	static private $totalCardsInSet;

	public function __construct ($geFileName) {
		// Normalize all new lines to \r\n before opening the file. Uncomment only if needed
		$geFileStr = file_get_contents($geFileName);
		$geFileStr = preg_replace("/\r(?!\n)/s", "\r\n", $geFileStr);
		$geFileStr = preg_replace("/(?<!\r)\n/s", "\r\n", $geFileStr);
		file_put_contents($geFileName, $geFileStr);
		unset($geFileStr);
		
		global $language;
		
		
		$geFile = fopen_utf8($geFileName, 'rb');
		if (!$geFile) error('Unable to open Gatherer Extractor CSV file: ' . $geFileName);

		echo "Processing Gatherer Extractor file...";

		
		$i = 0;
		while (($row = fgetcsv($geFile, 20000, '|', '"')) !== FALSE) {
			if($i++ <= 1) continue; //skip first line
			if ($i % 50 == 0) echo '.';
			if ($i % 2000 == 0) echo "\n";
			
			$row = str_replace('N/A', '', $row);
			
			$row[34] = '';
			if ($row[0] == 'Ichiga, Who Topples Oaks') $row[18] = '0';
			
			// Combine Double-faced Cards
			if (!empty($row[52])) {
				$sides[(string)trim($row[6])] = $row;
				if (array_key_exists((string)trim($row[52]), $sides) == FALSE) continue;
				$combined_array = array();
				if (/*$row[18] != '0' || */substr($row[28], -1) == 'a') {
				foreach($sides[(string)trim($row[6])] as $key=>$value) {
						$combined_array[$key]=$value."|".$sides[(string)trim($row[52])][$key];
						if ($combined_array[$key] == "|") $combined_array[$key] = "";
					}
				} else {
				foreach($sides[(string)trim($row[52])] as $key=>$value) {
						$combined_array[$key]=$value."|".$sides[(string)trim($row[6])][$key];
						if ($combined_array[$key] == "|") $combined_array[$key] = null;
					}
				}
				$row = $combined_array;
			}
			
			// Extract.
			$title = (string)trim($row[0]);
			$title = str_replace('|', '/', $title); // Clean Double-faced card data
			$set = (string)trim($row[4]);
			if (strpos($set, '|')) $set = substr($set, 0, strpos($set, '|'));
			$color = (string)trim($row[24]);
			$type = (string)trim($row[8]);
			$type = str_replace('|', '/', $type); // Clean Double-faced card data
			$p = (string)trim($row[10]); // Power
			$t = (string)trim($row[12]); // Toughness	
			$l = (string)trim($row[14]); // Loyalty
			$flavor = (string)trim($row[22]);
			$flavor = str_replace('|', "\n//\n", $flavor); // Clean Double-faced card data
			$rarity = (string)trim($row[30]);
			if (strpos($rarity, '|')) $rarity = substr($rarity, 0, strpos($rarity, '|')); // Clean Double-faced card data
			if (strpos($rarity, '/')) $rarity = substr($rarity, 0, strpos($rarity, '/') - 1);
			$cost = (string)trim($row[16]);
			if (strpos($cost, '|')) $cost = substr($cost, 0, strpos($cost, '|')); // Clean Double-faced card data
			$legal = trim($row[38]);
			$legal = str_replace('|', "\n//\n", $legal);
			$pic = (string)trim($row[36]); // Version
			if (strpos($pic, '|')) $pic = substr($pic, 0, strpos($pic, '|')); // Clean Double-faced card data
			if ($pic == '|') $pic = "";
			$artist = (string)trim($row[20]);
			if (strpos($artist, '|')) $artist = substr($artist, 0, strpos($artist, '|')); // Clean Double-faced card data
			if (strpos($artist, '/')) $artist = substr($artist, 0, strpos($artist, '/'));
			$collectorNumber = (string)trim($row[28]);
			if (strpos($collectorNumber, '|')) $collectorNumber = substr($collectorNumber, 0, strpos($collectorNumber, '|')); // Clean Double-faced card data
			$collectorNumber = preg_replace('/([a|b])/', '', $collectorNumber); // Clean Double-faced card data
			$id = $row[6];
			if ($id == '|') $id = "";
			$id = str_replace('|', '/', $id);
			if (strpos($id, '/')) $id = (int)substr($id, 0, strpos($id, '/'));
			
			// Traditional Chinese Data
			//if (!empty($row[56]) && strtolower($language) == 'chinese-china') {
			$titleCN = (string)trim(@$row[56]);
			$titleCN = str_replace('|', '/', @$titleCN); // Clean Double-faced card data
			$typeCN = (string)trim(@$row[76]);
			$typeCN = str_replace('|', '/', @$typeCN); // Clean Double-faced card data
			$legalCN = (string)trim(@$row[96]);
			$legalCN = str_replace('|', "\n//\n", @$legalCN); // Clean Double-faced card data
			$flavorCN = (string)trim(@$row[116]);
			$flavorCN = str_replace('|', "\n//\n", @$flavorCN); // Clean Double-faced card data
			//}
			// Simplified Chinese Data
			//if (!empty($row[58]) && strtolower($language) == 'chinese-taiwan') {
			$titleTW = (string)trim(@$row[58]);
			$titleTW = str_replace('|', '/', @$titleTW); // Clean Double-faced card data
			$typeTW = (string)trim(@$row[78]);
			$typeTW = str_replace('|', '/', @$typeTW); // Clean Double-faced card data
			$legalTW = (string)trim(@$row[98]);
			$legalTW = str_replace('|', "\n//\n", @$legalTW); // Clean Double-faced card data
			$flavorTW = (string)trim(@$row[118]);
			$flavorTW = str_replace('|', "\n//\n", @$flavorTW); // Clean Double-faced card data
			//}
			// French Data
			//if (!empty($row[60]) && (strtolower($language) == 'french' || strtolower($language) == 'french-oracle')) {
			$titleFR = (string)trim(@$row[60]);
			$titleFR = str_replace('|', '/', @$titleFR); // Clean Double-faced card data
			$typeFR = (string)trim(@$row[80]);
			$typeFR = str_replace('|', '/', @$typeFR); // Clean Double-faced card data
			$legalFR = (string)trim(@$row[100]);
			$legalFR = str_replace('|', "\n//\n", @$legalFR); // Clean Double-faced card data
			$flavorFR = (string)trim(@$row[120]);
			$flavorFR = str_replace('|', "\n//\n", @$flavorFR); // Clean Double-faced card data
			//}
			// German Data
			//if (!empty($row[62]) && strtolower($language) == 'german') {
			$titleDE = (string)trim(@$row[62]);
			$titleDE = str_replace('|', '/', @$titleDE); // Clean Double-faced card data
			$typeDE = (string)trim(@$row[82]);
			$typeDE = str_replace('|', '/', @$typeDE); // Clean Double-faced card data
			$legalDE = (string)trim(@$row[102]);
			$legalDE = str_replace('|', "\n//\n", @$legalDE); // Clean Double-faced card data
			$flavorDE = (string)trim(@$row[122]);
			$flavorDE = str_replace('|', "\n//\n", @$flavorDE); // Clean Double-faced card data
			//}
			// Italian Data
			//if (!empty($row[64]) && strtolower($language) == 'italian') {
			$titleIT = (string)trim(@$row[64]);
			$titleIT = str_replace('|', '/', @$titleIT); // Clean Double-faced card data
			$typeIT = (string)trim(@$row[84]);
			$typeIT = str_replace('|', '/', @$typeIT); // Clean Double-faced card data
			$legalIT = (string)trim(@$row[104]);
			$legalIT = str_replace('|', "\n//\n", @$legalIT); // Clean Double-faced card data
			$flavorIT = (string)trim(@$row[124]);
			$flavorIT = str_replace('|', "\n//\n", @$flavorIT); // Clean Double-faced card data
			//}
			// Japanese Data
			//if (!empty($row[66]) && strtolower($language) == 'japanese') {
			$titleJP = (string)trim(@$row[66]);
			$titleJP = str_replace('|', '/', @$titleJP); // Clean Double-faced card data
			$typeJP = (string)trim(@$row[86]);
			$typeJP = str_replace('|', '/', @$typeJP); // Clean Double-faced card data
			$legalJP = (string)trim(@$row[106]);
			$legalJP = str_replace('|', "\n//\n", @$legalJP); // Clean Double-faced card data
			$flavorJP = (string)trim(@$row[126]);
			$flavorJP = str_replace('|', "\n//\n", @$flavorJP); // Clean Double-faced card data
			//}
			// Portugese Data
			//if (!empty($row[68]) && strtolower(@$language) == 'portugese') {
			$titlePT = (string)trim(@$row[68]);
			$titlePT = str_replace('|', '/', @$titlePT); // Clean Double-faced card data
			$typePT = (string)trim(@$row[88]);
			$typePT = str_replace('|', '/', @$typePT); // Clean Double-faced card data
			$legalPT = (string)trim(@$row[108]);
			$legalPT = str_replace('|', "\n//\n", @$legalPT); // Clean Double-faced card data
			$flavorPT = (string)trim(@$row[128]);
			$flavorPT = str_replace('|', "\n//\n", @$flavorPT); // Clean Double-faced card data
			//}
			// Russian Data
			//if (!empty($row[70]) && strtolower(@$language) == 'russian') {
			$titleRU = (string)trim(@$row[70]);
			$titleRU = str_replace('|', '/', @$titleRU); // Clean Double-faced card data
			$typeRU = (string)trim(@$row[90]);
			$typeRU = str_replace('|', '/', @$typeRU); // Clean Double-faced card data
			$legalRU = (string)trim(@$row[110]);
			$legalRU = str_replace('|', "\n//\n", @$legalRU); // Clean Double-faced card data
			$flavorRU = (string)trim(@$row[130]);
			$flavorRU = str_replace('|', "\n//\n", @$flavorRU); // Clean Double-faced card data
			//}
			// Spanish Data
			//if (!empty($row[72]) && strtolower(@$language) == 'spanish') {
			$titleES = (string)trim(@$row[72]);
			$titleES = str_replace('|', '/', @$titleES); // Clean Double-faced card data
			$typeES = (string)trim(@$row[92]);
			$typeES = str_replace('|', '/', @$typeES); // Clean Double-faced card data
			$legalES = (string)trim(@$row[112]);
			$legalES = str_replace('|', "\n//\n", @$legalES); // Clean Double-faced card data
			$flavorES = (string)trim(@$row[132]);
			$flavorES = str_replace('|', "\n//\n", @$flavorES); // Clean Double-faced card data
			//}
			// Korean Data
			//if (!empty($row[74]) && strtolower(@$language) == 'korean') {
			$titleKO = (string)trim(@$row[74]);
			$titleKO = str_replace('|', '/', @$titleKO); // Clean Double-faced card data
			$typeKO = (string)trim(@$row[94]);
			$typeKO = str_replace('|', '/', @$typeKO); // Clean Double-faced card data
			$legalKO = (string)trim(@$row[114]);
			$legalKO = str_replace('|', "\n//\n", @$legalKO); // Clean Double-faced card data
			$flavorKO = (string)trim(@$row[134]);
			$flavorKO = str_replace('|', "\n//\n", @$flavorKO); // Clean Double-faced card data
			//}
			
			
			// Title.
			if ($set == 'VG') $title = 'Avatar: ' . $title;

			// Casting cost.
			//$cost = $this->replaceDualManaSymbols($cost);
			//$cost = $this->replacePhyrexiaSymbols($cost);
			//$cost = preg_replace('/([0-9]+)/', '{\\1}', $cost); //
			//$cost = preg_replace('/([WUBRGXYZ])/', '{\\1}', $cost);
			//$cost = preg_replace('/{{([0-9XYZWUBRG])}{([WUBRG])}}/', '{\\1\\2}', $cost);
			//$cost = preg_replace('/{([P]){([WUBRG])}}/', '{\\1\\2}', $cost);

			// Color.
			$color = str_replace(' // ', '/', $color);
			if (strpos($color, '/') !== FALSE) {
			$color1 = substr($color, 0, strpos($color, '/'));
			$color2 = substr($color, strpos($color, '/') + 1);
			switch ($color1) {
				case "C" : $color1 = "Art"; break;
				case (preg_match('/A([WUBRG](?!.))/', $color1) ? TRUE : FALSE) : $color1 = preg_replace('/A([WUBRG](?!.))/', '\\1', $color1); break;
				case "L" : $color1 = "Lnd"; break;
				case "AL" : $color1 = "Lnd"; break;
				case "A" : $color1 = "Art"; break;
				case trim(strlen($color1)) >= 2 : $color1= "Gld"; break;
				}
			switch ($color2) {
				case "C" : $color2 = "Art"; break;
				case (preg_match('/A([WUBRG](?!.))/', $color2) ? TRUE : FALSE) : $color2 = preg_replace('/A([WUBRG](?!.))/', '\\1', $color2); break;
				case "L" : $color2 = "Lnd"; break;
				case "AL" : $color2 = "Lnd"; break;
				case "A" : $color2 = "Art"; break;
				case trim(strlen($color2)) >= 2 : $color2 = "Gld"; break;
				}
			$color = $color1 . '/' . $color2;
			} else if (strpos($color, '|') !== FALSE) {
				$color1 = substr($color, 0, strpos($color, '|'));
				$color2 = substr($color, strpos($color, '|') + 1);
				if ($color1 === $color2) {
					$color = $color1;
					switch ($color) {
						case "C" : $color = "Art"; break;
						case (preg_match('/A([WUBRG](?!.))/', $color) ? TRUE : FALSE) : $color = preg_replace('/A([WUBRG](?!.))/', '\\1', $color); break;
						case "L" : $color = "Lnd"; break;
						case "A" : $color = "Art"; break;
						case trim(strlen($color)) >= 2 : $color = "Gld"; break;
					}
				} else {
					$color = 'Gld';
				}
			} else {
			switch ($color) {
				case "C" : $color = "Art"; break;
				case (preg_match('/A([WUBRG](?!.))/', $color) ? TRUE : FALSE) : $color = preg_replace('/A([WUBRG](?!.))/', '\\1', $color); break;
				case "L" : $color = "Lnd"; break;
				case "A" : $color = "Art"; break;
				case trim(strlen($color)) >= 2 : $color = "Gld"; break;
			}
			}
			
				
			//if ($color == 'Z/Z' || /*(*/strpos($title, '/') !== FALSE/* && $p == "" && $t == "")*/) {
				// Determine split card colors.
				/*$cost1 = substr($cost, 0, strpos($cost, '/'));
				$colors = Card::getCostColors($cost1);
				$color = strlen($colors) == 1 ? $colors : 'Gld';

				$color .= '/';

				$cost2 = substr($cost, strpos($cost, '/') + 1);
				$colors = Card::getCostColors($cost2);
				$color .= strlen($colors) == 1 ? $colors : 'Gld';
			}*/
			
			if (strpos($title, "/") !== FALSE && (($p != "" && $t != "" || $l != "" || substr($type, 0, strpos($type, '/'))) && ($set == 'DKA' || $set == 'ISD'||$set == 'ORI'||$set == 'SOI'||$set == 'EMN')||($p != "" && $t != "" || $l != "") && ($set == 'PRE'||$set == 'MIN'||$set == 'CVP'||$set== 'REL'))) {
				// Double-faced card fixes
				$title1 = substr($title, 0, strpos($title, '/'));
				$title2 = substr($title, strpos($title, '/') + 1);
				$title = $title1;

				$type1 = substr($type, 0, strpos($type, '/'));
				$type2 = substr($type, strpos($type, '/') + 1);
				$type = $type1;

				$p1 = substr($p, 0, strpos($p, '|'));
				$p2 = substr($p, strpos($p, '|') + 1);
				$p = $p1;
				
				$t1 = substr($t, 0, strpos($t, '|'));
				$t2 = substr($t, strpos($t, '|') + 1);
				$t = $t1;
				
				$l1 = substr($l, 0, strpos($l, '|'));
				$l2 = substr($l, strpos($l, '|') + 1);
				$l = $l1;
				
				$pt = "";
				
				if ($p2 != "") {
					$pt = $p2 . '/' . $t2;
				} else $pt = '/' . $l2;
				if ($pt == '/') $pt = '';

				$insertPosition = strpos($legal, "//");
				$insertString = "\n" . $title2 . "\n" . $type2 . ($pt != "" ? "\n" . $pt : "");
				$legalTmp = substr_replace($legal, $insertString, $insertPosition+2, 0);
				$legal = $legalTmp;
				
				if (!empty($titleCN) && strtolower($language) == 'chinese-china') {
				$titleCN1 = substr($titleCN, 0, strpos($titleCN, '/'));
				$titleCN2 = substr($titleCN, strpos($titleCN, '/') + 1);
				$titleCN = $titleCN1;
				
				$typeCN1 = substr($typeCN, 0, strpos($typeCN, '/'));
				$typeCN2 = substr($typeCN, strpos($typeCN, '/') + 1);
				$typeCN = $typeCN1;

				$insertPosition = strpos($legalCN, "//");
				$insertString = "\n" . $titleCN2 . "\n" . $typeCN2 . ($pt != "" ? "\n" . $pt : "");
				$legalTmp = substr_replace($legalCN, $insertString, $insertPosition+2, 0);
				$legalCN = $legalTmp;
				if ($legalCN == "\n//\n") $legalCN = '';
				}
				
				if (!empty($titleTW) && strtolower($language) == 'chinese-taiwan') {
				$titleTW1 = substr($titleTW, 0, strpos($titleTW, '/'));
				$titleTW2 = substr($titleTW, strpos($titleTW, '/') + 1);
				$titleTW = $titleTW1;

				$typeTW1 = substr($typeTW, 0, strpos($typeTW, '/'));
				$typeTW2 = substr($typeTW, strpos($typeTW, '/') + 1);
				$typeTW = $typeTW1;

				$insertPosition = strpos($legalTW, "//");
				$insertString = "\n" . $titleTW2 . "\n" . $typeTW2 . ($pt != "" ? "\n" . $pt : "");
				$legalTmp = substr_replace($legalTW, $insertString, $insertPosition+2, 0);
				$legalTW = $legalTmp;
				if ($legalTW == "\n//\n") $legalTW = '';
				}
				
				if (!empty($titleFR) && (strtolower($language) == 'french' || strtolower($language) == 'french-oracle')) {
				$titleFR1 = substr($titleFR, 0, strpos($titleFR, '/'));
				$titleFR2 = substr($titleFR, strpos($titleFR, '/') + 1);
				$titleFR = $titleFR1;

				$typeFR1 = substr($typeFR, 0, strpos($typeFR, '/'));
				$typeFR2 = substr($typeFR, strpos($typeFR, '/') + 1);
				$typeFR = $typeFR1;

				$insertPosition = strpos($legalFR, "//");
				$insertString = "\n" . $titleFR2 . "\n" . $typeFR2 . ($pt != "" ? "\n" . $pt : "");
				$legalTmp = substr_replace($legalFR, $insertString, $insertPosition+2, 0);
				$legalFR = $legalTmp;
				if ($legalFR == "\n//\n") $legalFR = '';
				}
				
				if (!empty($titleDE) && strtolower($language) == 'german') {
				$titleDE1 = substr($titleDE, 0, strpos($titleDE, '/'));
				$titleDE2 = substr($titleDE, strpos($titleDE, '/') + 1);
				$titleDE = $titleDE1;
				
				$typeDE1 = substr($typeDE, 0, strpos($typeDE, '/'));
				$typeDE2 = substr($typeDE, strpos($typeDE, '/') + 1);
				$typeDE = $typeDE1;
				
				$insertPosition = strpos($legalDE, "//");
				$insertString = "\n" . $titleDE2 . "\n" . $typeDE2 . ($pt != "" ? "\n" . $pt : "");
				$legalTmp = substr_replace($legalDE, $insertString, $insertPosition+2, 0);
				$legalDE = $legalTmp;
				if ($legalDE == "\n//\n") $legalDE = '';
				}
				
				if (!empty($titleIT) && strtolower($language) == 'italian') {
				$titleIT1 = substr($titleIT, 0, strpos($titleIT, '/'));
				$titleIT2 = substr($titleIT, strpos($titleIT, '/') + 1);
				$titleIT = $titleIT1;

				$typeIT1 = substr($typeIT, 0, strpos($typeIT, '/'));
				$typeIT2 = substr($typeIT, strpos($typeIT, '/') + 1);
				$typeIT = $typeIT1;

				$insertPosition = strpos($legalIT, "//");
				$insertString = "\n" . $titleIT2 . "\n" . $typeIT2 . ($pt != "" ? "\n" . $pt : "");
				$legalTmp = substr_replace($legalIT, $insertString, $insertPosition+2, 0);
				$legalIT = $legalTmp;
				if ($legalIT == "\n//\n") $legalIT = '';
				}
				
				if (!empty($titleJP) && strtolower($language) == 'japanese') {
				$titleJP1 = substr($titleJP, 0, strpos($titleJP, '/'));
				$titleJP2 = substr($titleJP, strpos($titleJP, '/') + 1);
				$titleJP = $titleJP1;

				$typeJP1 = substr($typeJP, 0, strpos($typeJP, '/'));
				$typeJP2 = substr($typeJP, strpos($typeJP, '/') + 1);
				$typeJP = $typeJP1;

				$insertPosition = strpos($legalJP, "//");
				$insertString = "\n" . $titleJP2 . "\n" . $typeJP2 . ($pt != "" ? "\n" . $pt : "");
				$legalTmp = substr_replace($legalJP, $insertString, $insertPosition+2, 0);
				$legalJP = $legalTmp;
				if ($legalJP == "\n//\n") $legalJP = '';
				}
				
				if (!empty($titlePT) && strtolower($language) == 'portugese') {
				$titlePT1 = substr($titlePT, 0, strpos($titlePT, '/'));
				$titlePT2 = substr($titlePT, strpos($titlePT, '/') + 1);
				$titlePT = $titlePT1;

				$typePT1 = substr($typePT, 0, strpos($typePT, '/'));
				$typePT2 = substr($typePT, strpos($typePT, '/') + 1);
				$typePT = $typePT1;

				$insertPosition = strpos($legalPT, "//");
				$insertString = "\n" . $titlePT2 . "\n" . $typePT2 . ($pt != "" ? "\n" . $pt : "");
				$legalTmp = substr_replace($legalPT, $insertString, $insertPosition+2, 0);
				$legalPT = $legalTmp;
				if ($legalPT == "\n//\n") $legalPT = '';
				}
				
				if (!empty($titleRU) && strtolower($language) == 'russian') {
				$titleRU1 = substr($titleRU, 0, strpos($titleRU, '/'));
				$titleRU2 = substr($titleRU, strpos($titleRU, '/') + 1);
				$titleRU = $titleRU1;

				$typeRU1 = substr($typeRU, 0, strpos($typeRU, '/'));
				$typeRU2 = substr($typeRU, strpos($typeRU, '/') + 1);
				$typeRU = $typeRU1;

				$insertPosition = strpos($legalRU, "//");
				$insertString = "\n" . $titleRU2 . "\n" . $typeRU2 . ($pt != "" ? "\n" . $pt : "");
				$legalTmp = substr_replace($legalRU, $insertString, $insertPosition+2, 0);
				$legalRU = $legalTmp;
				if ($legalRU == "\n//\n") $legalRU = '';
				}
				
				if (!empty($titleES) && strtolower($language) == 'spanish') {
				$titleES1 = substr($titleES, 0, strpos($titleES, '/'));
				$titleES2 = substr($titleES, strpos($titleES, '/') + 1);
				$titleES = $titleES1;

				$typeES1 = substr($typeES, 0, strpos($typeES, '/'));
				$typeES2 = substr($typeES, strpos($typeES, '/') + 1);
				$typeES = $typeES1;

				$insertPosition = strpos($legalES, "//");
				$insertString = "\n" . $titleES2 . "\n" . $typeES2 . ($pt != "" ? "\n" . $pt : "");
				$legalTmp = substr_replace($legalES, $insertString, $insertPosition+2, 0);
				$legalES = $legalTmp;
				if ($legalES == "\n//\n") $legalES = '';
				}
				
				if (!empty($titleKO) && strtolower($language) == 'korean') {
				$titleKO1 = substr($titleKO, 0, strpos($titleKO, '/'));
				$titleKO2 = substr($titleKO, strpos($titleKO, '/') + 1);
				$titleKO = $titleKO1;

				$typeKO1 = substr($typeKO, 0, strpos($typeKO, '/'));
				$typeKO2 = substr($typeKO, strpos($typeKO, '/') + 1);
				$typeKO = $typeKO1;
				
				$insertPosition = strpos($legalKO, "//");
				$insertString = "\n" . $titleKO2 . "\n" . $typeKO2 . ($pt != "" ? "\n" . $pt : "");
				$legalTmp = substr_replace($legalKO, $insertString, $insertPosition+2, 0);
				$legalKO = $legalTmp;
				if ($legalKO == "\n//\n") $legalKO = '';
				}
			}
			

			//php5 fixups
			$flavor = str_replace("\xA0", '', $flavor);
			$legal = preg_replace('/\x{2212}/siu', '-', $legal);
			//$flavor = iconv('windows-1250', 'utf-8', $flavor);
			//$legal = iconv('windows-1250', 'utf-8' ,$legal);
			//$artist = iconv('windows-1250', 'utf-8' ,$artist);
			//convert title and type just in case
			//$title = iconv('windows-1250', 'utf-8', $title);
			//$type = iconv('windows-1250', 'utf-8' ,$type);
			
			// Title.
			$title = str_replace('Æ', 'AE', $title);
			$title = str_replace(' // ', '/', $title);
			$title = str_replace('El-Hajjâj', 'El-Hajjaj', $title);
			$title = str_replace('Junún', 'Junun', $title);
			$title = str_replace('Lim-Dûl', 'Lim-Dul', $title);
			$title = str_replace('Jötun', 'Jotun', $title);
			$title = str_replace('Ghazbán', 'Ghazban', $title);
			$title = str_replace('Ifh-Bíff', 'Ifh-Biff', $title);
			$title = str_replace('Juzám', 'Juzam', $title);
			$title = str_replace('Khabál', 'Khabal', $title);
			$title = str_replace('Márton', 'Marton', $title);
			$title = str_replace("Ma'rûf", "Ma'ruf", $title);
			$title = str_replace("Ma’rûf", "Ma’ruf", $title);
			$title = str_replace('Déjà Vu', 'Deja Vu', $title);
			$title = str_replace('Dandân', 'Dandan', $title);
			$title = str_replace('Bösium', 'Bosium', $title);
			$title = str_replace('Séance', 'Seance', $title);
			$title = str_replace('Sauté', 'Saute', $title);
			
			// Type.
			$type = str_replace(' - ', ' — ', $type);
			$type = str_replace(' // ', '/', $type);

			// Legal.
			$legal = str_replace('#_', '#', $legal);
			$legal = str_replace('_#', '#', $legal);
			$legal = str_replace('£', "\n", $legal);
			$legal = preg_replace("/([A-Za-z])'([A-Za-z])/", '\1’\2', $legal); // ' to ’
			$legal = preg_replace('/CHAOS/', '{CHAOS}', $legal); // Chaos symbol

			$legal = str_replace("\n———\n", "-----", $legal);
			$flavor = str_replace("\n———\n", "-----", $flavor);
			$legal = str_replace("//", "-----", $legal);
			$flavor = str_replace("//", "-----", $flavor);

			//card specific
			if (strpos($type, 'Basic Land') !== FALSE) {
				// Clean up basic lands
				$legal = preg_replace('/{([WUBRGC])}/s', "", $legal);
				if (!empty($legalCN) && strtolower($language) == 'chinese-china') $legalCN = preg_replace('/{([WUBRGC])}/s', "", $legalCN);
				if (!empty($legalTW) && strtolower($language) == 'chinese-taiwan') $legalTW = preg_replace('/{([WUBRGC])}/s', "", $legalTW);
				if (!empty($legalFR) && (strtolower($language) == 'french' || strtolower($language) == 'french-oracle')) $legalFR = preg_replace('/{([WUBRGC])}/s', "", $legalFR);
				if (!empty($legalDE) && strtolower($language) == 'german') $legalDE = preg_replace('/{([WUBRGC])}/s', "", $legalDE);
				if (!empty($legalIT) && strtolower($language) == 'italian') $legalIT = preg_replace('/{([WUBRGC])}/s', "", $legalIT);
				if (!empty($legalJP) && strtolower($language) == 'japanese') $legalJP = preg_replace('/{([WUBRGC])}/s', "", $legalJP);
				if (!empty($legalPT) && strtolower($language) == 'portugese') $legalPT = preg_replace('/{([WUBRGC])}/s', "", $legalPT);
				if (!empty($legalRU) && strtolower($language) == 'russian') $legalRU = preg_replace('/{([WUBRGC])}/s', "", $legalRU);
				if (!empty($legalES) && strtolower($language) == 'spanish') $legalES = preg_replace('/{([WUBRGC])}/s', "", $legalES);
				if (!empty($legalKO) && strtolower($language) == 'korean') $legalKO = preg_replace('/{([WUBRGC])}/s', "", $legalKO);
				}
			$legal = str_replace('El-Hajjaj', 'El-Hajjâj', $legal);
			$legal = str_replace('Junun', 'Junún', $legal);
			$legal = str_replace('Lim-Dul', 'Lim-Dûl', $legal);
			$legal = str_replace('Jotun', 'Jötun', $legal);
			$legal = str_replace('Ghazban', 'Ghazbán', $legal);
			$legal = str_replace('Ifh-Biff', 'Ifh-Bíff', $legal);
			$legal = str_replace('Juzam', 'Juzám', $legal);
			$legal = str_replace('Khabal', 'Khabál', $legal);
			$legal = str_replace('Marton', 'Márton', $legal);
			$legal = str_replace("Ma'ruf", "Ma'rûf", $legal);
			$legal = str_replace("Ma’ruf", "Ma’rûf", $legal);
			$legal = str_replace('Deja Vu', 'Déjà Vu', $legal);
			$legal = str_replace('Dandan', 'Dandân', $legal);
			$legal = str_replace('Bosium', 'Bösium', $legal);
			$legal = str_replace(' en-', ' #en#-', $legal);
			$legal = str_replace(' il-', ' #il#-', $legal);
			$legal = str_replace('Seance', 'Séance', $legal);
			$legal = str_replace('Saute', 'Sauté', $legal);
			if ($title != 'Curse of the Fire Penguin') {
				$legal  = preg_replace('/(.*?\n?)(-----)(\n.*?)(\n.*?)(\n.*?)(\n.*)(\n\d\/\d)/su', "\\1\\2\\3\\4\\7\\6", $legal);
			} else {
				$legal = str_replace('Whenthiscreatureisputintoagraveyardfromplay,returnCurseoftheFirePenguinfromyourgraveyardtoplay.', 'When this creature is put into a graveyard from play, return Curse of the Fire Penguin from your graveyard to play.', $legal);
				$legal  = preg_replace('/(.*?\n?)(-----)(\n.*?)(\n.*?)(\n.*?)(\n\d\/\d)(\n.*)/su', "\\1\\2\\5\\3\\6\\4\\7", $legal);
				$legal = str_replace('Creature Penguin', 'Creature — Penguin', $legal);
			} // Flip card fixes
			$legal = str_replace("Fuse #(You may cast one or both halves of this card from your hand.)#\n ----- ", "-----", $legal); // Fuse Fixes
			$legal = str_replace("\nFuse ", "\n-----\nFuse", $legal); // Fuse Fixes
			$legal = str_replace(" ----- ", "-----", $legal);
			$legal = str_replace("\n\n-----", "\n-----", $legal);
			$legal = preg_replace('/([a-z\p{Ll}#])([A-Z\p{Lu}])/s', "\\1\n\\2", $legal);
			$legal = preg_replace('/(\n)([A-Z][a-z]+) —/s', '\\1#\\2# —', $legal);
			$legal = preg_replace('/(?<!.)([A-Z][a-z]+) —/s', '#\\1# —', $legal);
			
			
			$legal = preg_replace('/#([^#]+)# – /', '\\1 – ', $legal); // Remove italics from ability keywords.
			$legal = str_replace("\r\n-----\r\n", "\n-----\n", $legal); // Flip card separator.
			$legal = str_replace('#Creature# — ', 'Creature — ', $legal);
			$legal = str_replace('#Planeswalker# — ', 'Planeswalker — ', $legal);
			$legal = str_replace('#Enchantment# — ', 'Enchantment — ', $legal);
			$legal = str_replace(' upkeep - ', ' upkeep—', $legal);
			$legal = str_replace(' - ', ' — ', $legal);
			$legal = preg_replace('/[−—]([0-9X]+)/', '-\\1', $legal);
			$legal = str_replace('AE', 'Æ', $legal);
			$legal = str_replace(".]", ".)", $legal);
			$legal = str_replace("\r\n", "\n", $legal);
			// Fix vanguard inconsistencies.
			if (preg_match('/Starting & Max[^\+\-]+([\+\-][0-9]+)[^\+\-]+([\+\-][0-9]+)/', $legal, $matches))
				$legal = 'Hand ' . $matches[1] . ', Life ' . $matches[2] . "\n" . substr($legal, 0, strpos($legal, ' Starting & Max'));
			if (preg_match('/Hand Size[^\+\-]+([\+\-][0-9]+)[^\+\-]+([\+\-][0-9]+)\.?/', $legal, $matches))
				$legal = 'Hand ' . $matches[1] . ', Life ' . $matches[2] . "\n" . substr($legal, 0, strpos($legal, 'Hand Size'));
			$legal = trim($legal);
			$legal = str_replace("Markov's Servant\nCreature — Vampire\n4/4", "Markov's Servant\nCreature — Vampire\n4/4\n", $legal);

			// Flavor.
			$flavor = str_replace('#_', '', $flavor);
			$flavor = str_replace('_#', '', $flavor);
			$flavor = str_replace('£', "\n", $flavor);
			$flavor = str_replace("'", '’', $flavor); // ' to ’
			$flavor = preg_replace('/"([^"]*)"/', '“\\1”', $flavor); // "text" to “text”
			$flavor = preg_replace("/(.*[^.]) '([^']*)'/", "\\1 ‘\\2’", $flavor); // 'text' to ‘text’
			$flavor = preg_replace('/(.*[^.]) ’(.*)’/', '\\1 ‘\\2’', $flavor); // ’text’ to ‘text’
			$flavor = str_replace('”’', '’”', $flavor); // ”’ to ’”
			$flavor = str_replace('‘”', '”‘', $flavor); // ‘” to ”‘
			$flavor = str_replace('“’', '“‘', $flavor); // “’ to “‘
			$flavor = str_replace(',’', '’,', $flavor); // ,’ to ’,
			$flavor = preg_replace("/\r\n- (.?)/", "\n—\\1", $flavor); // - to —
			$flavor = preg_replace("/\r\n#- (.?)/", "\n#—\\1", $flavor);
			$flavor = preg_replace("/ - /", "—", $flavor);
			$flavor = str_replace('AE', 'Æ', $flavor);
			$flavor = str_replace(' en-', ' #en#-', $flavor);
			$flavor = str_replace(' Weatherlight', ' #Weatherlight#', $flavor);
			$flavor = str_replace("\r\n", "\n", $flavor);
			$flavor = str_replace('"', '”', $flavor); // " to ”
			
			$cost = str_replace(' // ', '/', $cost);
			
			// Non English language fixes
			if (!empty($titleCN) && strtolower($language) == 'chinese-china'){
				$titleCN = str_replace(' // ', '/', $titleCN);
				$typeCN = str_replace(' // ', '/', $typeCN);
				if ($typeCN == '//') $typeCN = '';
				$legalCN = str_replace('#_', '#', $legalCN);
				$legalCN = str_replace('_#', '#', $legalCN);
				$legalCN = str_replace('£', "\n", $legalCN);
				$legalCN = preg_replace('/\x{2212}/siu', '-', $legalCN);
				$legalCN = str_replace("\n———\n", "-----", $legalCN);
				$legalCN = str_replace("//", "-----", $legalCN);
				$flavorCN = str_replace("\n———\n", "-----", $flavorCN);
				$flavorCN = str_replace("//", "-----", $flavorCN);
				$legalCN = str_replace(" ----- ", "-----", $legalCN);
				$legalCN = preg_replace('/{([0-9XYZWUBRG])\/([WUBRG])}/', '{\\1\\2}', $legalCN);
				if ($legalCN == "\n ----- \n") $legalCN = '';
				$legalCN = preg_replace('/([.])([^-\s\)"])/su', "\\1\n\\2", $legalCN);
				$legalCN = preg_replace('/(\n)([A-Z\p{L}][a-z{\p{L}]+) —/su', '\\1#\\2# —', $legalCN);
				$legalCN = preg_replace('/(?<!.)([A-Z\p{L}][a-z\p{L}]+) —/su', '#\\1# —', $legalCN);
				$legalCN = preg_replace('/([。\.])([-+][\dX]+)([：: ]+)/', "\\1\n\\2: ", $legalCN);
				$legalCN = str_replace('加{1}到你的法术力池中', '加{C}到你的法术力池中', $legalCN);
				$legalCN = str_replace('加{2}到你的法术力池中', '加{C}{C}到你的法术力池中', $legalCN);
				$legalCN = str_replace('加{3}到你的法术力池中', '加{C}{C}{C}到你的法术力池中', $legalCN);
				$legalCN = str_replace('加{4}到你的法术力池中', '加{C}{C}{C}{C}到你的法术力池中', $legalCN);
				$legalCN = str_replace("融咒（你可以从手牌中单独施放此牌任一边或一同施放两边。）\n-----", "-----", $legalCN);// Fuse Fixes
				$legalCN = str_replace("\n融咒 ", "\n-----\n融咒", $legalCN); // Fuse Fixes
				$flavorCN = str_replace('#_', '', $flavorCN);
				$flavorCN = str_replace('_#', '', $flavorCN);
				$flavorCN = str_replace('£', "\n", $flavorCN);
				$flavorCN = str_replace("'", '’', $flavorCN); // ' to ’
				$flavorCN = preg_replace('/"([^"]*)"/', '“\\1”', $flavorCN); // "text" to “text”
				$flavorCN = preg_replace("/(.*[^.]) '([^']*)'/", "\\1 ‘\\2’", $flavorCN); // 'text' to ‘text’
				$flavorCN = preg_replace('/(.*[^.]) ’(.*)’/', '\\1 ‘\\2’', $flavorCN); // ’text’ to ‘text’
				$flavorCN = str_replace('”’', '’”', $flavorCN); // ”’ to ’”
				$flavorCN = str_replace('‘”', '”‘', $flavorCN); // ‘” to ”‘
				$flavorCN = str_replace('“’', '“‘', $flavorCN); // “’ to “‘
				$flavorCN = str_replace(',’', '’,', $flavorCN); // ,’ to ’,
				$flavorCN = str_replace("\r\n", "\n", $flavorCN);
				$flavorCN = str_replace('"', '”', $flavorCN); // " to ”
			}
			if (!empty($titleTW) && strtolower($language) == 'chinese-taiwan'){
				$titleTW = str_replace(' // ', '/', $titleTW);
				$typeTW = str_replace(' // ', '/', $typeTW);
				if ($typeTW == '//') $typeTW = '';
				$legalTW = str_replace('#_', '#', $legalTW);
				$legalTW = str_replace('_#', '#', $legalTW);
				$legalTW = str_replace('£', "\n", $legalTW);
				$legalTW = preg_replace('/\x{2212}/siu', '-', $legalTW);
				$legalTW = str_replace("\n———\n", "-----", $legalTW);
				$legalTW = str_replace("//", "-----", $legalTW);
				$flavorTW = str_replace("\n———\n", "-----", $flavorTW);
				$flavorTW = str_replace("//", "-----", $flavorTW);
				$legalTW = str_replace(" ----- ", "-----", $legalTW);
				$legalTW = preg_replace('/{([0-9XYZWUBRG])\/([WUBRG])}/', '{\\1\\2}', $legalTW);
				if ($legalTW == "\n ----- \n") $legalTW = '';
				$legalTW = preg_replace('/([.])([^-\s\)"])/su', "\\1\n\\2", $legalTW);
				$legalTW = preg_replace('/(.\)#\ )([^-\s\)"])/su', "\\1\n\\2", $legalTW);
				$legalTW = preg_replace('/(\n)([A-Z\p{Lu}][a-z\p{Ll}]+) —/u', "\\1#\\2# —", $legalTW);
				$legalTW = preg_replace('/(?<!.)([A-Z\p{Lu}][a-z\p{Ll}]+) —/u', "#\\1# —", $legalTW);
				$legalTW = preg_replace('/([。\.])([-+][\dX]+)([：: ]+)/', "\\1\n\\2: ", $legalTW);
				$legalTW = str_replace('加{1}到你的魔法力池中', '加{C}到你的魔法力池中', $legalTW);
				$legalTW = str_replace('加{2}到你的魔法力池中', '加{C}{C}到你的魔法力池中', $legalTW);
				$legalTW = str_replace('加{3}到你的魔法力池中', '加{C}{C}{C}到你的魔法力池中', $legalTW);
				$legalTW = str_replace('加{4}到你的魔法力池中', '加{C}{C}{C}{C}到你的魔法力池中', $legalTW);
				$legalTW = str_replace("融咒（你可以從手牌中單獨施放此牌任一邊或一同施放兩邊。）\n-----", "-----", $legalTW); // Fuse Fixes
				$legalTW = str_replace("\n融咒 ", "\n-----\n融咒", $legalTW); // Fuse Fixes
				$flavorTW = str_replace('#_', '', $flavorTW);
				$flavorTW = str_replace('_#', '', $flavorTW);
				$flavorTW = str_replace('£', "\n", $flavorTW);
				$flavorTW = str_replace("'", '’', $flavorTW); // ' to ’
				$flavorTW = preg_replace('/"([^"]*)"/', '“\\1”', $flavorTW); // "text" to “text”
				$flavorTW = preg_replace("/(.*[^.]) '([^']*)'/", "\\1 ‘\\2’", $flavorTW); // 'text' to ‘text’
				$flavorTW = preg_replace('/(.*[^.]) ’(.*)’/', '\\1 ‘\\2’', $flavorTW); // ’text’ to ‘text’
				$flavorTW = str_replace('”’', '’”', $flavorTW); // ”’ to ’”
				$flavorTW = str_replace('‘”', '”‘', $flavorTW); // ‘” to ”‘
				$flavorTW = str_replace('“’', '“‘', $flavorTW); // “’ to “‘
				$flavorTW = str_replace(',’', '’,', $flavorTW); // ,’ to ’,
				$flavorTW = str_replace("\r\n", "\n", $flavorTW);
				$flavorTW = str_replace('"', '”', $flavorTW); // " to ”
			}
			if (!empty($titleFR) && (strtolower($language) == 'french' || strtolower($language) == 'french-oracle')){
				$titleFR = str_replace(' // ', '/', $titleFR);
				$typeFR = str_replace(' // ', '/', $typeFR);
				if ($typeFR == '//') $typeFR = '';
				$legalFR = preg_replace('/([a-z\p{Ll}#])([A-Z\p{Lu}])/s', "\\1\n\\2", $legalFR);
				$legalFR = str_replace('#_', '#', $legalFR);
				$legalFR = str_replace('_#', '#', $legalFR);
				$legalFR = str_replace('£', "\n", $legalFR);
				$legalFR = preg_replace('/\x{2212}/siu', '-', $legalFR);
				$legalFR = str_replace("\n———\n", "-----", $legalFR);
				$legalFR = str_replace("//", "-----", $legalFR);
				$flavorFR = str_replace("\n———\n", "-----", $flavorFR);
				$flavorFR = str_replace("//", "-----", $flavorFR);
				$legalFR = str_replace(" ----- ", "-----", $legalFR);
				$legalFR = preg_replace('/{([0-9XYZWUBRG])\/([WUBRG])}/', '{\\1\\2}', $legalFR);
				if ($legalFR == "\n ----- \n") $legalFR = '';
				$legalFR = preg_replace('/([.])([^-\s\)"])/su', "\\1\n\\2", $legalFR);
				$legalFR = preg_replace('/(.\)#\ )([^-\s\)"])/su', "\\1\n\\2", $legalFR);
				$legalFR = preg_replace('/(\n)([A-Z\p{Lu}][a-z\p{Ll}]+) —/u', "\\1#\\2# —", $legalFR);
				$legalFR = preg_replace('/(?<!.)([A-Z\p{Lu}][a-z\p{Ll}]+) —/u', "#\\1# —", $legalFR);
				$legalFR = preg_replace('/([。\.])([-+][\dX]+)([：: ]+)/', "\\1\n\\2: ", $legalFR);
				$legalFR = str_replace('Ajoutez {1} à votre réserve', 'Ajoutez {C} à votre réserve', $legalFR);
				$legalFR = str_replace('Ajoutez {2} à votre réserve', 'Ajoutez {C}{C} à votre réserve', $legalFR);
				$legalFR = str_replace('Ajoutez {3} à votre réserve', 'Ajoutez {C}{C}{C} à votre réserve', $legalFR);
				$legalFR = str_replace('Ajoutez {4} à votre réserve', 'Ajoutez {C}{C}{C}{C} à votre réserve', $legalFR);
				$legalFR = str_replace("Fusion #(Vous pouvez lancer une ou deux moitiés de cette carte depuis votre main.)#\n-----", "-----", $legalFR); // Fuse Fixes
				$legalFR = str_replace("\nFusion ", "\n-----\nFusion", $legalFR); // Fuse Fixes
				$flavorFR = str_replace('#_', '', $flavorFR);
				$flavorFR = str_replace('_#', '', $flavorFR);
				$flavorFR = str_replace('£', "\n", $flavorFR);
				$flavorFR = str_replace("'", '’', $flavorFR); // ' to ’
				$flavorFR = preg_replace('/"([^"]*)"/', '“\\1”', $flavorFR); // "text" to “text”
				$flavorFR = preg_replace("/(.*[^.]) '([^']*)'/", "\\1 ‘\\2’", $flavorFR); // 'text' to ‘text’
				$flavorFR = preg_replace('/(.*[^.]) ’(.*)’/', '\\1 ‘\\2’', $flavorFR); // ’text’ to ‘text’
				$flavorFR = str_replace('”’', '’”', $flavorFR); // ”’ to ’”
				$flavorFR = str_replace('‘”', '”‘', $flavorFR); // ‘” to ”‘
				$flavorFR = str_replace('“’', '“‘', $flavorFR); // “’ to “‘
				$flavorFR = str_replace(',’', '’,', $flavorFR); // ,’ to ’,
				$flavorFR = str_replace("\r\n", "\n", $flavorFR);
				$flavorFR = str_replace('"', '”', $flavorFR); // " to ”
			}
			if (!empty($titleDE) && strtolower($language) == 'german'){
				$titleDE = str_replace(' // ', '/', $titleDE);
				$typeDE = str_replace(' // ', '/', $typeDE);
				if ($typeDE == '//') $typeDE = '';
				$legalDE = preg_replace('/([a-z\p{Ll}#])([A-Z\p{Lu}])/s', "\\1\n\\2", $legalDE);
				$legalDE = str_replace('#_', '#', $legalDE);
				$legalDE = str_replace('_#', '#', $legalDE);
				$legalDE = str_replace('£', "\n", $legalDE);
				$legalDE = preg_replace('/\x{2212}/siu', '-', $legalDE);
				$legalDE = str_replace("\n———\n", "-----", $legalDE);
				$legalDE = str_replace("//", "-----", $legalDE);
				$flavorDE = str_replace("\n———\n", "-----", $flavorDE);
				$flavorDE = str_replace("//", "-----", $flavorDE);
				$legalDE = str_replace(" ----- ", "-----", $legalDE);
				$legalDE = preg_replace('/{([0-9XYZWUBRG])\/([WUBRG])}/', '{\\1\\2}', $legalDE);
				if ($legalDE == "\n ----- \n") $legalDE = '';
				$legalDE = preg_replace('/([.])([^-\s\)"])/su', "\\1\n\\2", $legalDE);
				$legalDE = preg_replace('/(.\)#\ )([^-\s\)"])/su', "\\1\n\\2", $legalDE);
				$legalDE = preg_replace('/(\n)([A-Z\p{Lu}][a-z\p{Ll}]+) —/u', "\\1#\\2# —", $legalDE);
				$legalDE = preg_replace('/(?<!.)([A-Z\p{Lu}][a-z\p{Ll}]+) —/u', "#\\1# —", $legalDE);
				$legalDE = preg_replace('/([。\.])([-+][\dX]+)([：: ]+)/', "\\1\n\\2: ", $legalDE);
				$legalDE = str_replace('#Kreatur# — ', 'Kreatur — ', $legalDE);
				$legalDE = str_replace('#Planeswalker# — ', 'Planeswalker — ', $legalDE);
				$legalDE = str_replace('#Verzauberung# — ', 'Verzauberung — ', $legalDE);
				$legalDE = str_replace('Erhöhe deinen Manavorrat um {1}', 'Erhöhe deinen Manavorrat um {C}', $legalDE);
				$legalDE = str_replace('Erhöhe deinen Manavorrat um {2}', 'Erhöhe deinen Manavorrat um {C}{C}', $legalDE);
				$legalDE = str_replace('Erhöhe deinen Manavorrat um {3}', 'Erhöhe deinen Manavorrat um {C}{C}{C}', $legalDE);
				$legalDE = str_replace('Erhöhe deinen Manavorrat um {4}', 'Erhöhe deinen Manavorrat um {C}{C}{C}{C}', $legalDE);
				$legalDE = str_replace("Fusion #(Du kannst eine oder beide Hälften dieser Karte aus deiner Hand wirken.)#\n-----", "-----", $legalDE); // Fuse Fixes
				$legalDE = str_replace("\nFusion ", "\n-----\nFusion", $legalDE); // Fuse Fixes
				$flavorDE = str_replace('#_', '', $flavorDE);
				$flavorDE = str_replace('_#', '', $flavorDE);
				$flavorDE = str_replace('£', "\n", $flavorDE);
				$flavorDE = str_replace("'", '’', $flavorDE); // ' to ’
				$flavorDE = preg_replace('/"([^"]*)"/', '“\\1”', $flavorDE); // "text" to “text”
				$flavorDE = preg_replace("/(.*[^.]) '([^']*)'/", "\\1 ‘\\2’", $flavorDE); // 'text' to ‘text’
				$flavorDE = preg_replace('/(.*[^.]) ’(.*)’/', '\\1 ‘\\2’', $flavorDE); // ’text’ to ‘text’
				$flavorDE = str_replace('”’', '’”', $flavorDE); // ”’ to ’”
				$flavorDE = str_replace('‘”', '”‘', $flavorDE); // ‘” to ”‘
				$flavorDE = str_replace('“’', '“‘', $flavorDE); // “’ to “‘
				$flavorDE = str_replace(',’', '’,', $flavorDE); // ,’ to ’,
				$flavorDE = str_replace("\r\n", "\n", $flavorDE);
				$flavorDE = str_replace('"', '”', $flavorDE); // " to ”
			}
			if (!empty($titleIT) && strtolower($language) == 'italian'){
				$titleIT = str_replace(' // ', '/', $titleIT);
				$typeIT = str_replace(' // ', '/', $typeIT);
				if ($typeIT == '//') $typeIT = '';
				$legalIT = preg_replace('/([a-z\p{Ll}#])([A-Z\p{Lu}])/s', "\\1\n\\2", $legalIT);
				$legalIT = str_replace('#_', '#', $legalIT);
				$legalIT = str_replace('_#', '#', $legalIT);
				$legalIT = str_replace('£', "\n", $legalIT);
				$legalIT = preg_replace('/\x{2212}/siu', '-', $legalIT);
				$legalIT = str_replace("\n———\n", "-----", $legalIT);
				$legalIT = str_replace("//", "-----", $legalIT);
				$flavorIT = str_replace("\n———\n", "-----", $flavorIT);
				$flavorIT = str_replace("//", "-----", $flavorIT);
				$legalIT = str_replace(" ----- ", "-----", $legalIT);
				$legalIT = preg_replace('/{([0-9XYZWUBRG])\/([WUBRG])}/', '{\\1\\2}', $legalIT);
				if ($legalIT == "\n ----- \n") $legalIT = '';
				$legalIT = preg_replace('/([.])([^-\s\)"])/s', "\\1\n\\2", $legalIT);
				$legalIT = preg_replace('/(.\)#\ )([^-\s\)"])/su', "\\1\n\\2", $legalIT);
				$legalIT = preg_replace('/(\n)([A-Z\p{Lu}][a-z\p{Ll}]+) —/u', "\\1#\\2# —", $legalIT);
				$legalIT = preg_replace('/(?<!.)([A-Z\p{Lu}][a-z\p{Ll}]+) —/u', "#\\1# —", $legalIT);
				$legalIT = preg_replace('/([。\.])([-+][\dX]+)([：: ]+)/', "\\1\n\\2: ", $legalIT);
				$legalIT = str_replace('#Creatura# — ', 'Creatura — ', $legalIT);
				$legalIT = str_replace('#Planeswalker# — ', 'Planeswalker — ', $legalIT);
				$legalIT = str_replace('#Incantesimo# — ', 'Incantesimo — ', $legalIT);
				$legalIT = str_replace('Aggiungi {1} alla tua riserva di mana', 'Aggiungi {C} alla tua riserva di mana', $legalIT);
				$legalIT = str_replace('Aggiungi {2} alla tua riserva di mana', 'Aggiungi {C}{C} alla tua riserva di mana', $legalIT);
				$legalIT = str_replace('Aggiungi {3} alla tua riserva di mana', 'Aggiungi {C}{C}{C} alla tua riserva di mana', $legalIT);
				$legalIT = str_replace('Aggiungi {4} alla tua riserva di mana', 'Aggiungi {C}{C}{C}{C} alla tua riserva di mana', $legalIT);
				$legalIT = str_replace("Fusione #(Puoi lanciare una o entrambe le metà di questa carta dalla tua mano.)#\n-----", "-----", $legalIT); // Fuse Fixes
				$legalIT = str_replace("\nFusione ", "\n-----\nFusione", $legalIT); // Fuse Fixes
				$flavorIT = str_replace('#_', '', $flavorIT);
				$flavorIT = str_replace('_#', '', $flavorIT);
				$flavorIT = str_replace('£', "\n", $flavorIT);
				$flavorIT = str_replace("'", '’', $flavorIT); // ' to ’
				$flavorIT = preg_replace('/"([^"]*)"/', '“\\1”', $flavorIT); // "text" to “text”
				$flavorIT = preg_replace("/(.*[^.]) '([^']*)'/", "\\1 ‘\\2’", $flavorIT); // 'text' to ‘text’
				$flavorIT = preg_replace('/(.*[^.]) ’(.*)’/', '\\1 ‘\\2’', $flavorIT); // ’text’ to ‘text’
				$flavorIT = str_replace('”’', '’”', $flavorIT); // ”’ to ’”
				$flavorIT = str_replace('‘”', '”‘', $flavorIT); // ‘” to ”‘
				$flavorIT = str_replace('“’', '“‘', $flavorIT); // “’ to “‘
				$flavorIT = str_replace(',’', '’,', $flavorIT); // ,’ to ’,
				$flavorIT = str_replace("\r\n", "\n", $flavorIT);
				$flavorIT = str_replace('"', '”', $flavorIT); // " to ”
			}
			if (!empty($titleJP) && strtolower($language) == 'japanese'){
				$titleJP = str_replace(' // ', '/', $titleJP);
				$typeJP = str_replace(' // ', '/', $typeJP);
				if ($typeJP == '//') $typeJP = '';
				$legalJP = str_replace('#_', '#', $legalJP);
				$legalJP = str_replace('_#', '#', $legalJP);
				$legalJP = str_replace('£', "\n", $legalJP);
				$legalJP = preg_replace('/\x{2212}/siu', '-', $legalJP);
				$legalJP = str_replace("\n———\n", "-----", $legalJP);
				$legalJP = str_replace("//", "-----", $legalJP);
				$flavorJP = str_replace("\n———\n", "-----", $flavorJP);
				$flavorJP = str_replace("//", "-----", $flavorJP);
				$legalJP = str_replace(" ----- ", "-----", $legalJP);
				$legalJP = preg_replace('/{([0-9XYZWUBRG])\/([WUBRG])}/', '{\\1\\2}', $legalJP);
				if ($legalJP == "\n ----- \n") $legalJP = '';
				$legalJP = preg_replace('/([.])([^-\s\)"])/su', "\\1\n\\2", $legalJP);
				$legalJP = preg_replace('/(.\)#\ )([^-\s\)"])/su', "\\1\n\\2", $legalJP);
				$legalJP = preg_replace('/(\n)([A-Z\p{Lu}][a-z\p{Ll}]+) —/u', "\\1#\\2# —", $legalJP);
				$legalJP = preg_replace('/(?<!.)([A-Z\p{Lu}][a-z\p{Ll}]+) —/u', "#\\1# —", $legalJP);
				$legalJP = preg_replace('/([。\.])([-+][\dX]+)([：: ]+)/u', "\\1\n\\2: ", $legalJP);
				$legalJP = str_replace('#クリーチャー# — ', 'クリーチャー — ', $legalJP);
				$legalJP = str_replace('#プレインズウォーカー# — ', 'プレインズウォーカー — ', $legalJP);
				$legalJP = str_replace('#エンチャント# — ', 'エンチャント — ', $legalJP);
				$legalJP = str_replace('あなたのマナ・プールに{1}を加える', 'あなたのマナ・プールに{C}を加える', $legalJP);
				$legalJP = str_replace('あなたのマナ・プールに{2}を加える', 'あなたのマナ・プールに{C}{C}を加える', $legalJP);
				$legalJP = str_replace('あなたのマナ・プールに{3}を加える', 'あなたのマナ・プールに{C}{C}{C}を加える', $legalJP);
				$legalJP = str_replace('あなたのマナ・プールに{4}を加える', 'あなたのマナ・プールに{C}{C}{C}{C}を加える', $legalJP);
				$legalJP = str_replace("融合（あなたはこのカードの片方の半分または両方の半分をあなたの手札から唱えてもよい。）\n-----", "-----", $legalJP); // Fuse Fixes
				$legalJP = str_replace("\n融合 ", "\n-----\n融合", $legalJP); // Fuse Fixes
				$flavorJP = str_replace('#_', '', $flavorJP);
				$flavorJP = str_replace('_#', '', $flavorJP);
				$flavorJP = str_replace('£', "\n", $flavorJP);
				$flavorJP = str_replace("'", '’', $flavorJP); // ' to ’
				$flavorJP = preg_replace('/"([^"]*)"/', '“\\1”', $flavorJP); // "text" to “text”
				$flavorJP = preg_replace("/(.*[^.]) '([^']*)'/", "\\1 ‘\\2’", $flavorJP); // 'text' to ‘text’
				$flavorJP = preg_replace('/(.*[^.]) ’(.*)’/', '\\1 ‘\\2’', $flavorJP); // ’text’ to ‘text’
				$flavorJP = str_replace('”’', '’”', $flavorJP); // ”’ to ’”
				$flavorJP = str_replace('‘”', '”‘', $flavorJP); // ‘” to ”‘
				$flavorJP = str_replace('“’', '“‘', $flavorJP); // “’ to “‘
				$flavorJP = str_replace(',’', '’,', $flavorJP); // ,’ to ’,
				$flavorJP = str_replace("\r\n", "\n", $flavorJP);
				$flavorJP = str_replace('"', '”', $flavorJP); // " to ”
			}
			if (!empty($titlePT) && strtolower($language) == 'portugese'){
				$titlePT = str_replace(' // ', '/', $titlePT);
				$typePT = str_replace(' // ', '/', $typePT);
				if ($typePT == '//') $typePT = '';
				$legalPT = preg_replace('/([a-z\p{Ll}#])([A-Z\p{Lu}])/s', "\\1\n\\2", $legalPT);
				$legalPT = str_replace('#_', '#', $legalPT);
				$legalPT = str_replace('_#', '#', $legalPT);
				$legalPT = str_replace('£', "\n", $legalPT);
				$legalPT = preg_replace('/\x{2212}/siu', '-', $legalPT);
				$legalPT = str_replace("\n———\n", "-----", $legalPT);
				$legalPT = str_replace("//", "-----", $legalPT);
				$flavorPT = str_replace("\n———\n", "-----", $flavorPT);
				$flavorPT = str_replace("//", "-----", $flavorPT);
				$legalPT = str_replace(" ----- ", "-----", $legalPT);
				$legalPT = preg_replace('/{([0-9XYZWUBRG])\/([WUBRG])}/', '{\\1\\2}', $legalPT);
				if ($legalPT == "\n ----- \n") $legalPT = '';
				$legalPT = preg_replace('/([.])([^-\s\)"])/su', "\\1\n\\2", $legalPT);
				$legalPT = preg_replace('/(.\)#\ )([^-\s\)"])/su', "\\1\n\\2", $legalPT);
				$legalPT = preg_replace('/(\n)([A-Z\p{Lu}][a-z\p{Ll}]+) —/u', "\\1#\\2# —", $legalPT);
				$legalPT = preg_replace('/(?<!.)([A-Z\p{Lu}][a-z\p{Ll}]+) —/u', "#\\1# —", $legalPT);
				$legalPT = preg_replace('/([。\.])([-+][\dX]+)([：: ]+)/u', "\\1\n\\2: ", $legalPT);
				$legalPT = str_replace('#Criatura# — ', 'Criatura — ', $legalPT);
				$legalPT = str_replace('#Planeswalker# — ', 'Planeswalker — ', $legalPT);
				$legalPT = str_replace('#Encantamento# — ', 'Encantamento — ', $legalPT);
				$legalPT = str_replace('Adicione {C} à sua reserva de mana', 'Adicione {C} à sua reserva de mana', $legalPT);
				$legalPT = str_replace('Adicione {C} à sua reserva de mana', 'Adicione {C} à sua reserva de mana', $legalPT);
				$legalPT = str_replace('Adicione {C} à sua reserva de mana', 'Adicione {C} à sua reserva de mana', $legalPT);
				$legalPT = str_replace('Adicione {C} à sua reserva de mana', 'Adicione {C} à sua reserva de mana', $legalPT);
				$legalPT = str_replace("Fundir #(Você pode conjurar uma ou ambas as metades desse card a partir de sua mão.)#\n-----", "-----", $legalPT); // Fuse Fixes
				$legalPT = str_replace("\nFundir ", "\n-----\nFundir", $legalPT); // Fuse Fixes
				$flavorPT = str_replace('#_', '', $flavorPT);
				$flavorPT = str_replace('_#', '', $flavorPT);
				$flavorPT = str_replace('£', "\n", $flavorPT);
				$flavorPT = str_replace("'", '’', $flavorPT); // ' to ’
				$flavorPT = preg_replace('/"([^"]*)"/', '“\\1”', $flavorPT); // "text" to “text”
				$flavorPT = preg_replace("/(.*[^.]) '([^']*)'/", "\\1 ‘\\2’", $flavorPT); // 'text' to ‘text’
				$flavorPT = preg_replace('/(.*[^.]) ’(.*)’/', '\\1 ‘\\2’', $flavorPT); // ’text’ to ‘text’
				$flavorPT = str_replace('”’', '’”', $flavorPT); // ”’ to ’”
				$flavorPT = str_replace('‘”', '”‘', $flavorPT); // ‘” to ”‘
				$flavorPT = str_replace('“’', '“‘', $flavorPT); // “’ to “‘
				$flavorPT = str_replace(',’', '’,', $flavorPT); // ,’ to ’,
				$flavorPT = str_replace("\r\n", "\n", $flavorPT);
				$flavorPT = str_replace('"', '”', $flavorPT); // " to ”
			}
			if (!empty($titleRU) && strtolower($language) == 'russian'){
				$titleRU = str_replace(' // ', '/', $titleRU);
				$typeRU = str_replace(' // ', '/', $typeRU);
				if ($typeRU == '//') $typeRU = '';
				$legalRU = preg_replace('/([a-z\p{Ll}#])([A-Z\p{Lu}])/s', "\\1\n\\2", $legalRU);
				$legalRU = str_replace('#_', '#', $legalRU);
				$legalRU = str_replace('_#', '#', $legalRU);
				$legalRU = str_replace('£', "\n", $legalRU);
				$legalRU = preg_replace('/\x{2212}/siu', '-', $legalRU);
				$legalRU = str_replace("\n———\n", "-----", $legalRU);
				$legalRU = str_replace("//", "-----", $legalRU);
				$flavorRU = str_replace("\n———\n", "-----", $flavorRU);
				$flavorRU = str_replace("//", "-----", $flavorRU);
				$legalRU = str_replace(" ----- ", "-----", $legalRU);
				$legalRU = preg_replace('/{([0-9XYZWUBRG])\/([WUBRG])}/', '{\\1\\2}', $legalRU);
				if ($legalRU == "\n ----- \n") $legalRU = '';
				$legalRU = preg_replace('/([.])([^-\s\)"])/su', "\\1\n\\2", $legalRU);
				$legalRU = preg_replace('/(.\)#\ )([^-\s\)"])/su', "\\1\n\\2", $legalRU);
				$legalRU = preg_replace('/(\n)([A-Z\p{Lu}][a-z\p{Ll}]+) —/u', "\\1#\\2# —", $legalRU);
				$legalRU = preg_replace('/(?<!.)([A-Z\p{Lu}][a-z\p{Ll}]+) —/u', "#\\1# —", $legalRU);
				$legalRU = preg_replace('/([。\.])([-+][\dX]+)([：: ]+)/', "\\1\n\\2: ", $legalRU);
				$legalRU = str_replace('#Существо# — ', 'Существо — ', $legalRU);
				$legalRU = str_replace('#Planeswalker# — ', 'Planeswalker — ', $legalRU);
				$legalRU = str_replace('#Чары# — ', 'Чары — ', $legalRU);
				$legalRU = str_replace('Добавьте {1} в ваше хранилище маны', 'Добавьте {C} в ваше хранилище маны', $legalRU);
				$legalRU = str_replace('Добавьте {2} в ваше хранилище маны', 'Добавьте {C}{C} в ваше хранилище маны', $legalRU);
				$legalRU = str_replace('Добавьте {3} в ваше хранилище маны', 'Добавьте {C}{C}{C} в ваше хранилище маны', $legalRU);
				$legalRU = str_replace('Добавьте {4} в ваше хранилище маны', 'Добавьте {C}{C}{C}{C} в ваше хранилище маны', $legalRU);
				$legalRU = str_replace("Слияние #(Вы можете разыграть одну или обе половины этой карты из вашей руки.)#\n-----", "-----", $legalRU); // Fuse Fixes
				$legalRU = str_replace("\nСлияние ", "\n-----\nСлияние", $legalRU); // Fuse Fixes
				$flavorRU = str_replace('#_', '', $flavorRU);
				$flavorRU = str_replace('_#', '', $flavorRU);
				$flavorRU = str_replace('£', "\n", $flavorRU);
				$flavorRU = str_replace("'", '’', $flavorRU); // ' to ’
				$flavorRU = preg_replace('/"([^"]*)"/', '“\\1”', $flavorRU); // "text" to “text”
				$flavorRU = preg_replace("/(.*[^.]) '([^']*)'/", "\\1 ‘\\2’", $flavorRU); // 'text' to ‘text’
				$flavorRU = preg_replace('/(.*[^.]) ’(.*)’/', '\\1 ‘\\2’', $flavorRU); // ’text’ to ‘text’
				$flavorRU = str_replace('”’', '’”', $flavorRU); // ”’ to ’”
				$flavorRU = str_replace('‘”', '”‘', $flavorRU); // ‘” to ”‘
				$flavorRU = str_replace('“’', '“‘', $flavorRU); // “’ to “‘
				$flavorRU = str_replace(',’', '’,', $flavorRU); // ,’ to ’,
				$flavorRU = str_replace("\r\n", "\n", $flavorRU);
				$flavorRU = str_replace('"', '”', $flavorRU); // " to ”
			}
			if (!empty($titleES) && strtolower($language) == 'spanish'){
				$titleES = str_replace(' // ', '/', $titleES);
				$typeES = str_replace(' // ', '/', $typeES);
				if ($typeES == '//') $typeES = '';
				$legalES = preg_replace('/([a-z\p{Ll}#])([A-Z\p{Lu}])/s', "\\1\n\\2", $legalES);
				$legalES = str_replace('#_', '#', $legalES);
				$legalES = str_replace('_#', '#', $legalES);
				$legalES = str_replace('£', "\n", $legalES);
				$legalES = preg_replace('/\x{2212}/siu', '-', $legalES);
				$legalES = str_replace("\n———\n", "-----", $legalES);
				$legalES = str_replace("//", "-----", $legalES);
				$flavorES = str_replace("\n———\n", "-----", $flavorES);
				$flavorES = str_replace("//", "-----", $flavorES);
				$legalES = preg_replace('/{([0-9XYZWUBRG])\/([WUBRG])}/', '{\\1\\2}', $legalES);
				if ($legalES == "\n ----- \n") $legalES = '';
				$legalES = str_replace(" ----- ", "-----", $legalES);
				$legalES = preg_replace('/([.])([^-\s\)"])/su', "\\1\n\\2", $legalES);
				$legalES = preg_replace('/(.\)#\ )([^-\s\)"])/su', "\\1\n\\2", $legalES);
				$legalES = preg_replace('/(\n)([A-Z\p{Lu}][a-z\p{Ll}]+) —/u', "\\1#\\2# —", $legalES);
				$legalES = preg_replace('/(?<!.)([A-Z\p{Lu}][a-z\p{Ll}]+) —/u', "#\\1# —", $legalES);
				$legalES = preg_replace('/([。\.])([-+][\dX]+)([：: ]+)/', "\\1\n\\2: ", $legalES);
				$legalES = str_replace('#Criatura# — ', 'Criatura — ', $legalES);
				$legalES = str_replace('#Planeswalker# — ', 'Planeswalker — ', $legalES);
				$legalES = str_replace('#Encantamiento# — ', 'Encantamiento — ', $legalES);
				$legalES = str_replace('Agrega {1} a tu reserva de maná', 'Agrega {C} a tu reserva de maná', $legalES);
				$legalES = str_replace('Agrega {2} a tu reserva de maná', 'Agrega {C}{C} a tu reserva de maná', $legalES);
				$legalES = str_replace('Agrega {3} a tu reserva de maná', 'Agrega {C}{C}{C} a tu reserva de maná', $legalES);
				$legalES = str_replace('Agrega {4} a tu reserva de maná', 'Agrega {C}{C}{C}{C} a tu reserva de maná', $legalES);
				$legalES = str_replace("Fusionar #(Puedes lanzar una o las dos mitades de esta carta desde tu mano.)#\n-----", "-----", $legalES); // Fuse Fixes
				$legalES = str_replace("\nFusionar", "\n-----\nFusionar", $legalES); // Fuse Fixes
				$flavorES = str_replace('#_', '', $flavorES);
				$flavorES = str_replace('_#', '', $flavorES);
				$flavorES = str_replace('£', "\n", $flavorES);
				$flavorES = str_replace("'", '’', $flavorES); // ' to ’
				$flavorES = preg_replace('/"([^"]*)"/', '“\\1”', $flavorES); // "text" to “text”
				$flavorES = preg_replace("/(.*[^.]) '([^']*)'/", "\\1 ‘\\2’", $flavorES); // 'text' to ‘text’
				$flavorES = preg_replace('/(.*[^.]) ’(.*)’/', '\\1 ‘\\2’', $flavorES); // ’text’ to ‘text’
				$flavorES = str_replace('”’', '’”', $flavorES); // ”’ to ’”
				$flavorES = str_replace('‘”', '”‘', $flavorES); // ‘” to ”‘
				$flavorES = str_replace('“’', '“‘', $flavorES); // “’ to “‘
				$flavorES = str_replace(',’', '’,', $flavorES); // ,’ to ’,
				$flavorES = str_replace("\r\n", "\n", $flavorES);
				$flavorES = str_replace('"', '”', $flavorES); // " to ”
			}
			if (!empty($titleKO) && strtolower($language) == 'korean'){
				$titleKO = str_replace(' // ', '/', $titleKO);
				$typeKO = str_replace(' // ', '/', $typeKO);
				if ($typeKO == '//') $typeKO = '';
				$legalKO = str_replace('#_', '#', $legalKO);
				$legalKO = str_replace('_#', '#', $legalKO);
				$legalKO = str_replace('£', "\n", $legalKO);
				$legalKO = preg_replace('/\x{2212}/siu', '-', $legalKO);
				$legalKO = str_replace("\n———\n", "-----", $legalKO);
				$legalKO = str_replace("//", "-----", $legalKO);
				$flavorKO = str_replace("\n———\n", "-----", $flavorKO);
				$flavorKO = str_replace("//", "-----", $flavorKO);
				$legalKO = str_replace(" ----- ", "-----", $legalKO);
				$legalKO = preg_replace('/{([0-9XYZWUBRGC])\/([WUBRGC])}/', '{\\1\\2}', $legalKO);
				if ($legalKO == "\n ----- \n") $legalKO = '';
				$legalKO = preg_replace('/([.])([^-\s\)"])/su', "\\1\n\\2", $legalKO);
				$legalKO = preg_replace('/(.\)#\ )([^-\s\)"])/su', "\\1\n\\2", $legalKO);
				$legalKO = preg_replace('/(\n)([A-Z\p{Lu}][a-z\p{Ll}]+) —/u', "\\1#\\2# —", $legalKO);
				$legalKO = preg_replace('/(?<!.)([A-Z\p{Lu}][a-z\p{Ll}]+) —/u', "#\\1# —", $legalKO);
				$legalKO = preg_replace('/([。\.])([-+][\dX]+)([：: ]+)/', "\\1\n\\2: ", $legalKO);
				$legalKO = str_replace('#생물# — ', '생물 — ', $legalKO);
				$legalKO = str_replace('#플레인즈워커# — ', '플레인즈워커 — ', $legalKO);
				$legalKO = str_replace('#부여마법# — ', '부여마법 — ', $legalKO);
				$legalKO = str_replace('{1}를 당신의 마나풀에 담는다', '{C}를 당신의 마나풀에 담는다', $legalKO);
				$legalKO = str_replace('{2}를 당신의 마나풀에 담는다', '{C}{C}를 당신의 마나풀에 담는다', $legalKO);
				$legalKO = str_replace('{3}를 당신의 마나풀에 담는다', '{C}{C}{C}를 당신의 마나풀에 담는다', $legalKO);
				$legalKO = str_replace('{4}를 당신의 마나풀에 담는다', '{C}{C}{C}{C}를 당신의 마나풀에 담는다', $legalKO);
				$legalKO = str_replace("융합 #(당신은 손에서 이 카드의 반쪽 혹은 양쪽 모두를 발동할 수 있다.)#\n-----", "-----", $legalKO); // Fuse Fixes
				$legalKO = str_replace("\n융합 ", "\n-----\n융합", $legalKO); // Fuse Fixes
				$flavorKO = str_replace('#_', '', $flavorKO);
				$flavorKO = str_replace('_#', '', $flavorKO);
				$flavorKO = str_replace('£', "\n", $flavorKO);
				$flavorKO = str_replace("'", '’', $flavorKO); // ' to ’
				$flavorKO = preg_replace('/"([^"]*)"/', '“\\1”', $flavorKO); // "text" to “text”
				$flavorKO = preg_replace("/(.*[^.]) '([^']*)'/", "\\1 ‘\\2’", $flavorKO); // 'text' to ‘text’
				$flavorKO = preg_replace('/(.*[^.]) ’(.*)’/', '\\1 ‘\\2’', $flavorKO); // ’text’ to ‘text’
				$flavorKO = str_replace('”’', '’”', $flavorKO); // ”’ to ’”
				$flavorKO = str_replace('‘”', '”‘', $flavorKO); // ‘” to ”‘
				$flavorKO = str_replace('“’', '“‘', $flavorKO); // “’ to “‘
				$flavorKO = str_replace(',’', '’,', $flavorKO); // ,’ to ’,
				$flavorKO = str_replace("\r\n", "\n", $flavorKO);
				$flavorKO = str_replace('"', '”', $flavorKO); // " to ”
			}

			// Store.
			$card = new Card();
			$card->title = $title;
			$card->set = $set;
			$card->color = $color;
			$card->type = $type;
			$card->pt = ($p != "" && $t !="") ? $p . '/' . $t : (preg_match('/([0-9]+)/', $l, $matches) ? "/$matches[1]" : '');
			$card->flavor = $flavor;
			$card->rarity = $rarity;
			$card->cost = $cost;
			$card->legal = $legal;
			$card->pic = $pic;
			$card->artist = $artist;
			$card->collectorNumber = $collectorNumber;
			$this->cards[] = $card;
			
			// Traditional Chinese Cards
			if (!empty($titleCN) && strtolower($language) == 'chinese-china'){
				// Add unique title
				$titleSearch = self::objArrayFilter($this->cardsCN, 'title', $title);
				if ($titleSearch == FALSE || !@$this->cardsCN[0]) {
					$cardCN = new Card();
					$cardCN->title = $title;
					$cardCN->set = $titleCN;
					$cardCN->color = null;
					$cardCN->type = $typeCN;
					$cardCN->pt = null;
					$cardCN->flavor = null;
					$cardCN->rarity = null;
					$cardCN->cost = null;
					$cardCN->legal = $legalCN;
					$cardCN->pic = null;
					$cardCN->artist = null;
					$cardCN->collectorNumber = null;
					$this->cardsCN[] = $cardCN;
					$this->titleID[$title] = $id;
				} else {
					$titleKey = @$this->cardsCN[$titleSearch];
					@$titleKeyLegal = trim(@$titleKey->legal, "-/");
					if (empty($titleKey->set) || $id > @$this->titleID[$titleKey]) $titleKey->set = $titleCN;
					if (empty($titleKey->type) || $id > @$this->titleID[$titleKey]) $titleKey->type = $typeCN;
					if (empty($titleKeyLegal) || $id > @$this->titleID[$titleKey]) $titleKey->legal = $legalCN;
					if ($id > @$this->titleID[$titleKey]) $this->titleID[$title] = $id;
				}
				
				if (!empty($flavorCN)) {
					$cardCNflavor = new Card();
					$cardCNflavor->title = $title;
					$cardCNflavor->set = $set;
					$cardCNflavor->color = null;
					$cardCNflavor->type = null;
					$cardCNflavor->pt = null;
					$cardCNflavor->flavor = $flavorCN;
					$cardCNflavor->rarity = null;
					$cardCNflavor->cost = null;
					$cardCNflavor->legal = null;
					$cardCNflavor->pic = $pic;
					$cardCNflavor->artist = null;
					$cardCNflavor->collectorNumber = null;
					$this->cardsCNflavor[] = $cardCNflavor;
				}
			}
			// Simplified Chinese Cards
			if (!empty($titleTW) && strtolower($language) == 'chinese-taiwan'){
				// Add unique title
				$titleSearch = self::objArrayFilter($this->cardsTW, 'title', $title);
				if ($titleSearch == FALSE || !@$this->cardsTW[0]) {
					$cardTW = new Card();
					$cardTW->title = $title;
					$cardTW->set = $titleTW;
					$cardTW->color = null;
					$cardTW->type = $typeTW;
					$cardTW->pt = null;
					$cardTW->flavor = null;
					$cardTW->rarity = null;
					$cardTW->cost = null;
					$cardTW->legal = $legalTW;
					$cardTW->pic = null;
					$cardTW->artist = null;
					$cardTW->collectorNumber = null;
					$this->cardsTW[] = $cardTW;
					$this->titleID[$title] = $id;
				} else {
					$titleKey = @$this->cardsTW[$titleSearch];
					@$titleKeyLegal = trim(@$titleKey->legal, "-/");
					if (empty($titleKey->set) || $id > @$this->titleID[$titleKey]) $titleKey->set = $titleTW;
					if (empty($titleKey->type) || $id > @$this->titleID[$titleKey]) $titleKey->type = $typeTW;
					if (empty($titleKeyLegal) || $id > @$this->titleID[$titleKey]) $titleKey->legal = $legalTW;
					if ($id > @$this->titleID[$titleKey]) $this->titleID[$title] = $id;
				}
				
				if (!empty($flavorTW)) {
					$cardTWflavor = new Card();
					$cardTWflavor->title = $title;
					$cardTWflavor->set = $set;
					$cardTWflavor->color = null;
					$cardTWflavor->type = null;
					$cardTWflavor->pt = null;
					$cardTWflavor->flavor = $flavorTW;
					$cardTWflavor->rarity = null;
					$cardTWflavor->cost = null;
					$cardTWflavor->legal = null;
					$cardTWflavor->pic = $pic;
					$cardTWflavor->artist = null;
					$cardTWflavor->collectorNumber = null;
					$this->cardsTWflavor[] = $cardTWflavor;
				}
			}
			// French Cards
			if (!empty($titleFR) && (strtolower($language) == 'french' || strtolower($language) == 'french-oracle')){
				// Add unique title
				$titleSearch = self::objArrayFilter($this->cardsFR, 'title', $title);
				if ($titleSearch == FALSE || !@$this->cardsFR[0]) {
					$cardFR = new Card();
					$cardFR->title = $title;
					$cardFR->set = $titleFR;
					$cardFR->color = null;
					$cardFR->type = $typeFR;
					$cardFR->pt = null;
					$cardFR->flavor = null;
					$cardFR->rarity = null;
					$cardFR->cost = null;
					$cardFR->legal = $legalFR;
					$cardFR->pic = null;
					$cardFR->artist = null;
					$cardFR->collectorNumber = null;
					$this->cardsFR[] = $cardFR;
					$this->titleID[$title] = $id;
				} else {
					$titleKey = @$this->cardsFR[$titleSearch];
					@$titleKeyLegal = trim(@$titleKey->legal, "-/");
					if (empty($titleKey->set) || $id > @$this->titleID[$titleKey]) $titleKey->set = $titleFR;
					if (empty($titleKey->type) || $id > @$this->titleID[$titleKey]) $titleKey->type = $typeFR;
					if (empty($titleKeyLegal) || $id > @$this->titleID[$titleKey]) $titleKey->legal = $legalFR;
					if ($id > @$this->titleID[$titleKey]) $this->titleID[$title] = $id;
				}
				
				if (!empty($flavorFR)) {
					$cardFRflavor = new Card();
					$cardFRflavor->title = $title;
					$cardFRflavor->set = $set;
					$cardFRflavor->color = null;
					$cardFRflavor->type = null;
					$cardFRflavor->pt = null;
					$cardFRflavor->flavor = $flavorFR;
					$cardFRflavor->rarity = null;
					$cardFRflavor->cost = null;
					$cardFRflavor->legal = null;
					$cardFRflavor->pic = $pic;
					$cardFRflavor->artist = null;
					$cardFRflavor->collectorNumber = null;
					$this->cardsFRflavor[] = $cardFRflavor;
				}
			}
			// German Cards
			if (!empty($titleDE) && strtolower($language) == 'german'){
				// Add unique title
				$titleSearch = self::objArrayFilter($this->cardsDE, 'title', $title);
				if ($titleSearch == FALSE || !@$this->cardsDE[0]) {
					$cardDE = new Card();
					$cardDE->title = $title;
					$cardDE->set = $titleDE;
					$cardDE->color = null;
					$cardDE->type = $typeDE;
					$cardDE->pt = null;
					$cardDE->flavor = null;
					$cardDE->rarity = null;
					$cardDE->cost = null;
					$cardDE->legal = $legalDE;
					$cardDE->pic = null;
					$cardDE->artist = null;
					$cardDE->collectorNumber = null;
					$this->cardsDE[] = $cardDE;
					$this->titleID[$title] = $id;
				} else {
					$titleKey = @$this->cardsDE[$titleSearch];
					@$titleKeyLegal = trim(@$titleKey->legal, "-/");
					if (empty($titleKey->set) || $id > @$this->titleID[$titleKey]) $titleKey->set = $titleDE;
					if (empty($titleKey->type) || $id > @$this->titleID[$titleKey]) $titleKey->type = $typeDE;
					if (empty($titleKeyLegal) || $id > @$this->titleID[$titleKey]) $titleKey->legal = $legalDE;
					if ($id > @$this->titleID[$titleKey]) $this->titleID[$title] = $id;
				}
				
				if (!empty($flavorDE)) {
					$cardDEflavor = new Card();
					$cardDEflavor->title = $title;
					$cardDEflavor->set = $set;
					$cardDEflavor->color = null;
					$cardDEflavor->type = null;
					$cardDEflavor->pt = null;
					$cardDEflavor->flavor = $flavorDE;
					$cardDEflavor->rarity = null;
					$cardDEflavor->cost = null;
					$cardDEflavor->legal = null;
					$cardDEflavor->pic = $pic;
					$cardDEflavor->artist = null;
					$cardDEflavor->collectorNumber = null;
					$this->cardsDEflavor[] = $cardDEflavor;
				}
			}
			// Italian Cards
			if (!empty($titleIT) && strtolower($language) == 'italian'){
				// Add unique title
				$titleSearch = self::objArrayFilter($this->cardsIT, 'title', $title);
				if ($titleSearch == FALSE || !@$this->cardsIT[0]) {
					$cardIT = new Card();
					$cardIT->title = $title;
					$cardIT->set = $titleIT;
					$cardIT->color = null;
					$cardIT->type = $typeIT;
					$cardIT->pt = null;
					$cardIT->flavor = null;
					$cardIT->rarity = null;
					$cardIT->cost = null;
					$cardIT->legal = $legalIT;
					$cardIT->pic = null;
					$cardIT->artist = null;
					$cardIT->collectorNumber = null;
					$this->cardsIT[] = $cardIT;
					$this->titleID[$title] = $id;
				} else {
					$titleKey = @$this->cardsIT[$titleSearch];
					@$titleKeyLegal = trim(@$titleKey->legal, "-/");
					if (empty($titleKey->set) || $id > @$this->titleID[$titleKey]) $titleKey->set = $titleIT;
					if (empty($titleKey->type) || $id > @$this->titleID[$titleKey]) $titleKey->type = $typeIT;
					if (empty($titleKeyLegal) || $id > @$this->titleID[$titleKey]) $titleKey->legal = $legalIT;
					if ($id > @$this->titleID[$titleKey]) $this->titleID[$title] = $id;
				}
				
				if (!empty($flavorIT)) {
					$cardITflavor = new Card();
					$cardITflavor->title = $title;
					$cardITflavor->set = $set;
					$cardITflavor->color = null;
					$cardITflavor->type = null;
					$cardITflavor->pt = null;
					$cardITflavor->flavor = $flavorIT;
					$cardITflavor->rarity = null;
					$cardITflavor->cost = null;
					$cardITflavor->legal = null;
					$cardITflavor->pic = $pic;
					$cardITflavor->artist = null;
					$cardITflavor->collectorNumber = null;
					$this->cardsITflavor[] = $cardITflavor;
				}
			}
			// Japanese Cards
			if (!empty($titleJP) && strtolower($language) == 'japanese'){
				// Add unique title
				$titleSearch = self::objArrayFilter($this->cardsJP, 'title', $title);
				if ($titleSearch == FALSE || !@$this->cardsJP[0]) {
					$cardJP = new Card();
					$cardJP->title = $title;
					$cardJP->set = $titleJP;
					$cardJP->color = null;
					$cardJP->type = $typeJP;
					$cardJP->pt = null;
					$cardJP->flavor = null;
					$cardJP->rarity = null;
					$cardJP->cost = null;
					$cardJP->legal = $legalJP;
					$cardJP->pic = null;
					$cardJP->artist = null;
					$cardJP->collectorNumber = null;
					$this->cardsJP[] = $cardJP;
					$this->titleID[$title] = $id;
				} else {
					$titleKey = @$this->cardsJP[$titleSearch];
					@$titleKeyLegal = trim(@$titleKey->legal, "-/");
					if (empty($titleKey->set) || $id > @$this->titleID[$titleKey]) $titleKey->set = $titleJP;
					if (empty($titleKey->type) || $id > @$this->titleID[$titleKey]) $titleKey->type = $typeJP;
					if (empty($titleKeyLegal) || $id > @$this->titleID[$titleKey]) $titleKey->legal = $legalJP;
					if ($id > @$this->titleID[$titleKey]) $this->titleID[$title] = $id;
				}
				
				if (!empty($flavorJP)) {
					$cardJPflavor = new Card();
					$cardJPflavor->title = $title;
					$cardJPflavor->set = $set;
					$cardJPflavor->color = null;
					$cardJPflavor->type = null;
					$cardJPflavor->pt = null;
					$cardJPflavor->flavor = $flavorJP;
					$cardJPflavor->rarity = null;
					$cardJPflavor->cost = null;
					$cardJPflavor->legal = null;
					$cardJPflavor->pic = $pic;
					$cardJPflavor->artist = null;
					$cardJPflavor->collectorNumber = null;
					$this->cardsJPflavor[] = $cardJPflavor;
				}
			}
			// Portugese Cards
			if (!empty($titlePT) && strtolower($language) == 'portugese'){
				// Add unique title
				$titleSearch = self::objArrayFilter($this->cardsPT, 'title', $title);
				if ($titleSearch == FALSE || !@$this->cardsPT[0]) {
					$cardPT = new Card();
					$cardPT->title = $title;
					$cardPT->set = $titlePT;
					$cardPT->color = null;
					$cardPT->type = $typePT;
					$cardPT->pt = null;
					$cardPT->flavor = null;
					$cardPT->rarity = null;
					$cardPT->cost = null;
					$cardPT->legal = $legalPT;
					$cardPT->pic = null;
					$cardPT->artist = null;
					$cardPT->collectorNumber = null;
					$this->cardsPT[] = $cardPT;
					$this->titleID[$title] = $id;
				} else {
					$titleKey = @$this->cardsPT[$titleSearch];
					@$titleKeyLegal = trim(@$titleKey->legal, "-/");
					if (empty($titleKey->set) || $id > @$this->titleID[$titleKey]) $titleKey->set = $titlePT;
					if (empty($titleKey->type) || $id > @$this->titleID[$titleKey]) $titleKey->type = $typePT;
					if (empty($titleKeyLegal) || $id > @$this->titleID[$titleKey]) $titleKey->legal = $legalPT;
					if ($id > @$this->titleID[$titleKey]) $this->titleID[$title] = $id;
				}
				
				if (!empty($flavorPT)) {
					$cardPTflavor = new Card();
					$cardPTflavor->title = $title;
					$cardPTflavor->set = $set;
					$cardPTflavor->color = null;
					$cardPTflavor->type = null;
					$cardPTflavor->pt = null;
					$cardPTflavor->flavor = $flavorPT;
					$cardPTflavor->rarity = null;
					$cardPTflavor->cost = null;
					$cardPTflavor->legal = null;
					$cardPTflavor->pic = $pic;
					$cardPTflavor->artist = null;
					$cardPTflavor->collectorNumber = null;
					$this->cardsPTflavor[] = $cardPTflavor;
				}
			}
			// Russian Cards
			if (!empty($titleRU) && strtolower($language) == 'russian'){
				// Add unique title
				$titleSearch = self::objArrayFilter($this->cardsRU, 'title', $title);
				if ($titleSearch == FALSE || !@$this->cardsRU[0]) {
					$cardRU = new Card();
					$cardRU->title = $title;
					$cardRU->set = $titleRU;
					$cardRU->color = null;
					$cardRU->type = $typeRU;
					$cardRU->pt = null;
					$cardRU->flavor = null;
					$cardRU->rarity = null;
					$cardRU->cost = null;
					$cardRU->legal = $legalRU;
					$cardRU->pic = null;
					$cardRU->artist = null;
					$cardRU->collectorNumber = null;
					$this->cardsRU[] = $cardRU;
					$this->titleID[$title] = $id;
				} else {
					$titleKey = @$this->cardsRU[$titleSearch];
					@$titleKeyLegal = trim(@$titleKey->legal, "-/");
					if (empty($titleKey->set) || $id > @$this->titleID[$titleKey]) $titleKey->set = $titleRU;
					if (empty($titleKey->type) || $id > @$this->titleID[$titleKey]) $titleKey->type = $typeRU;
					if (empty($titleKeyLegal) || $id > @$this->titleID[$titleKey]) $titleKey->legal = $legalRU;
					if ($id > @$this->titleID[$titleKey]) $this->titleID[$title] = $id;
				}
				
				if (!empty($flavorRU)) {
					$cardRUflavor = new Card();
					$cardRUflavor->title = $title;
					$cardRUflavor->set = $set;
					$cardRUflavor->color = null;
					$cardRUflavor->type = null;
					$cardRUflavor->pt = null;
					$cardRUflavor->flavor = $flavorRU;
					$cardRUflavor->rarity = null;
					$cardRUflavor->cost = null;
					$cardRUflavor->legal = null;
					$cardRUflavor->pic = $pic;
					$cardRUflavor->artist = null;
					$cardRUflavor->collectorNumber = null;
					$this->cardsRUflavor[] = $cardRUflavor;
				}
			}
			// Spanish Cards
			if (!empty($titleES) && strtolower($language) == 'spanish'){
				// Add unique title
				$titleSearch = self::objArrayFilter($this->cardsES, 'title', $title);
				if ($titleSearch == FALSE || !@$this->cardsES[0]) {
					$cardES = new Card();
					$cardES->title = $title;
					$cardES->set = $titleES;
					$cardES->color = null;
					$cardES->type = $typeES;
					$cardES->pt = null;
					$cardES->flavor = null;
					$cardES->rarity = null;
					$cardES->cost = null;
					$cardES->legal = $legalES;
					$cardES->pic = null;
					$cardES->artist = null;
					$cardES->collectorNumber = null;
					$this->cardsES[] = $cardES;
					$this->titleID[$title] = $id;
				} else {
					$titleKey = @$this->cardsES[$titleSearch];
					@$titleKeyLegal = trim(@$titleKey->legal, "-/");
					if (empty($titleKey->set) || $id > @$this->titleID[$titleKey]) $titleKey->set = $titleES;
					if (empty($titleKey->type) || $id > @$this->titleID[$titleKey]) $titleKey->type = $typeES;
					if (empty($titleKeyLegal) || $id > @$this->titleID[$titleKey]) $titleKey->legal = $legalES;
					if ($id > @$this->titleID[$titleKey]) $this->titleID[$title] = $id;
				}
				
				if (!empty($flavorES)) {
					$cardESflavor = new Card();
					$cardESflavor->title = $title;
					$cardESflavor->set = $set;
					$cardESflavor->color = null;
					$cardESflavor->type = null;
					$cardESflavor->pt = null;
					$cardESflavor->flavor = $flavorES;
					$cardESflavor->rarity = null;
					$cardESflavor->cost = null;
					$cardESflavor->legal = null;
					$cardESflavor->pic = $pic;
					$cardESflavor->artist = null;
					$cardESflavor->collectorNumber = null;
					$this->cardsESflavor[] = $cardESflavor;
				}
			}
			// Korean Cards
			if (!empty($titleKO) && strtolower($language) == 'korean'){
				// Add unique title
				$titleSearch = self::objArrayFilter($this->cardsKO, 'title', $title);
				if ($titleSearch == FALSE || !@$this->cardsKO[0]) {
					$cardKO = new Card();
					$cardKO->title = $title;
					$cardKO->set = $titleKO;
					$cardKO->color = null;
					$cardKO->type = $typeKO;
					$cardKO->pt = null;
					$cardKO->flavor = null;
					$cardKO->rarity = null;
					$cardKO->cost = null;
					$cardKO->legal = $legalKO;
					$cardKO->pic = null;
					$cardKO->artist = null;
					$cardKO->collectorNumber = null;
					$this->cardsKO[] = $cardKO;
					$this->titleID[$title] = $id;
				} else {
					$titleKey = @$this->cardsKO[$titleSearch];
					@$titleKeyLegal = trim(@$titleKey->legal, "-/");
					if (empty($titleKey->set) || $id > @$this->titleID[$titleKey]) $titleKey->set = $titleKO;
					if (empty($titleKey->type) || $id > @$this->titleID[$titleKey]) $titleKey->type = $typeKO;
					if (empty($titleKeyLegal) || $id > @$this->titleID[$titleKey]) $titleKey->legal = $legalKO;
					if ($id > @$this->titleID[$titleKey]) $this->titleID[$title] = $id;
				}
				
				if (!empty($flavorKO)) {
					$cardKOflavor = new Card();
					$cardKOflavor->title = $title;
					$cardKOflavor->set = $set;
					$cardKOflavor->color = null;
					$cardKOflavor->type = null;
					$cardKOflavor->pt = null;
					$cardKOflavor->flavor = $flavorKO;
					$cardKOflavor->rarity = null;
					$cardKOflavor->cost = null;
					$cardKOflavor->legal = null;
					$cardKOflavor->pic = $pic;
					$cardKOflavor->artist = null;
					$cardKOflavor->collectorNumber = null;
					$this->cardsKOflavor[] = $cardKOflavor;
				}
			}
		}

		// Compute total cards in each set.
		$setToCollectorNumbers = array();
		foreach ($this->cards as $card) {
			// Only count cards with collector numbers.
			if (!$card->collectorNumber) continue;
			// Don't count the same collector number twice.
			if (!@$setToCollectorNumbers[$card->set]) $setToCollectorNumbers[$card->set] = array();
			if (@$setToCollectorNumbers[$card->set][$card->collectorNumber]) continue;

			$setToCollectorNumbers[$card->set][$card->collectorNumber] = true;
		}
		foreach ($this->cards as $card) {
			if (!$card->collectorNumber) continue;
			// Try hardcoded value first.
			$cardsInSet = GathererExtractor::getTotalCardsInSet($card->set);
			// Then try computed value.
			if (!$cardsInSet && @$setToCollectorNumbers[$card->set]) $cardsInSet = count($setToCollectorNumbers[$card->set]);
			if (!$cardsInSet) continue;
			$card->collectorNumber .= '/' . $cardsInSet;
		}
	
		/*$geFileStr = file_get_contents($geFileName);
		$geFileStr = preg_replace("/\r\n/s", "\r", $geFileStr);
		file_put_contents($geFileName, $geFileStr);
		unset($geFileStr);*/
	}

/*	private function replaceDualManaSymbols ($text) {
		$text = str_replace('%V', '{UB}', $text);
		$text = str_replace('%P', '{RW}', $text);
		$text = str_replace('%Q', '{BG}', $text);
		$text = str_replace('%A', '{GW}', $text);
		$text = str_replace('%I', '{UR}', $text);
		$text = str_replace('%L', '{RG}', $text);
		$text = str_replace('%O', '{WB}', $text);
		$text = str_replace('%S', '{GU}', $text);
		$text = str_replace('%K', '{BR}', $text);
		$text = str_replace('%D', '{WU}', $text);
		$text = str_replace('%N', '{S}', $text);

		$text = str_replace('%E', '{2W}', $text);
		$text = str_replace('%F', '{2U}', $text);
		$text = str_replace('%H', '{2B}', $text);
		$text = str_replace('%J', '{2R}', $text);
		$text = str_replace('%M', '{2G}', $text);
		return $text;
	}
	private function replacePhyrexiaSymbols ($text) {
		$text = str_replace('%!', '{PW}', $text);
		$text = str_replace('%`', '{PU}', $text);
		$text = str_replace('%$', '{PB}', $text);
		$text = str_replace('%^', '{PR}', $text);
		$text = str_replace('%@', '{PG}', $text);
		return $text;
	}*/

	private function getTotalCardsInSet ($set) {
		if (!GathererExtractor::$totalCardsInSet) {
			// These totals are not computed correctly by counting cards in the masterbase. The masterbase is wrong?
			GathererExtractor::$totalCardsInSet = array();
			GathererExtractor::$totalCardsInSet["CS"] = 155;
			GathererExtractor::$totalCardsInSet["HL"] = 140;
			GathererExtractor::$totalCardsInSet["WL"] = 167;
		}
		return @GathererExtractor::$totalCardsInSet[$set];
	}
	
	public static function objArrayFilter($array,$index,$value){
                $item = null;
                foreach($array as $key => $arrayInf) {
                    if(trim($arrayInf->{$index})==trim($value)){
                        return $key;
                    }
                }
                return $item;
            }
}

?>
