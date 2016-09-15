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
require_once 'version.php';

set_time_limit(0);
ini_set('memory_limit', '1024M');
srand((float) microtime() * 10000000);

$config = parse_ini_file('config/config.txt', false);

$rendererSettings = array();
foreach (glob("config/config-*.txt") as $configFile)
	$rendererSettings[$configFile] = parse_ini_file($configFile, false);

$rendererSections = array();
foreach (glob("config/config*.txt") as $configFile)
	$rendererSections[$configFile] = parse_ini_file($configFile, true);

function __autoload($className) {
	$classFile = getClassFile($className . '.php');
	if ($classFile) require_once($classFile);
}

// Searches folders and subfolders for a class php file.
function getClassFile ($fileName, $path = '/') {
	$dirPath = 'scripts' . $path;
	if (file_exists($dirPath . $fileName)) return $dirPath . $fileName;

	$dir = dir($dirPath);
	while (($subdir = $dir->read()) !== false) {
		if ($subdir == '.' || $subdir == '..') continue;
		if (!is_dir($dirPath . $subdir)) continue;
		$classFile = getClassFile($fileName, $path . $subdir . '/');
		if ($classFile) return $classFile;
	}
	$dir->close();
	return false;
}

function warn ($message) {
	global $config;
	echo "WARNING: $message";
	if ($config['log.errors.and.warnings']) {
		$file = $config['cardgen.error.log'];
		file_put_contents("$file", "WARNING: $message\r\n", FILE_APPEND);
	}
}

function error ($message) {
	global $config;
	echo "\n\nERROR: $message";
	if ($config['exit.on.error']) exit();
	if ($config['log.errors.and.warnings']) {
		$file = $config['cardgen.error.log'];
		file_put_contents("$file", "ERROR: $message\r\n", FILE_APPEND);
	}
}

function parse_csv_file($csvfile) {
    $array = Array();
    $rowcount = 0;
    if (($handle = fopen($csvfile, "r")) !== FALSE) {
        $max_line_length = defined('MAX_LINE_LENGTH') ? MAX_LINE_LENGTH : 10000;
        $header = fgetcsv($handle, $max_line_length);
        $header_colcount = count($header);
        while (($row = fgetcsv($handle, $max_line_length)) !== FALSE) {
            $row_colcount = count($row);
            if ($row_colcount == $header_colcount) {
                $entry = array_combine($header, $row);
                $array[] = $entry;
            }
            else {
                error_log("csvreader: Invalid number of columns at line " . ($rowcount + 2) . " (row " . ($rowcount + 1) . "). Expected=$header_colcount Got=$row_colcount");
                return null;
            }
            $rowcount++;
        }
        //echo "Totally $rowcount rows found\n";
        fclose($handle);
    }
    else {
        error_log("csvreader: Could not read CSV \"$csvfile\"");
        return null;
    }
    return $array;
}

// Converts a two column CSV into an associative array.
function csvToArray ($fileName) {
	$array = array();
	$file = fopen_utf8($fileName, 'r');
	if (!$file) error('Unable to open file: ' . $fileName);
	while (($data = fgetcsv($file, 6000, ',')) !== FALSE)
		$array[(string)strtolower($data[0])] = trim($data[1]);
	fclose($file);
	return $array;
}

function getInputFiles ($fileNames) {
	$files = array();

	for ($i = 0, $n = count($fileNames); $i < $n; $i++) {
		$fileName = $fileNames[$i];
		// Need to strip "" otherwise is_dir fails on paths with spaces.
		if (substr($fileName, 0, 1) == '"') $fileName = substr($fileName, 1);
		if (substr($fileName, -1) == '"') $fileName = substr($fileName, 0, -1);
		if (is_dir($fileName)) {
			foreach (glob($fileName . '/*') as $child)
				if (!is_dir($child)) $files[] = validateFileName($child);
		} else
			$files[] = validateFileName($fileName);
	}

	$requiredFileCount = func_num_args();
	for ($i = count($files) + 1; $i < $requiredFileCount; $i++) {
		echo func_get_arg($i) . "\n";
		$input = trim(fgets(STDIN));
		echo "\n";
		// Need to strip "" otherwise is_dir fails on paths with spaces.
		if (substr($input, 0, 1) == '"') $input = substr($input, 1);
		if (substr($input, -1) == '"') $input = substr($input, 0, -1);
		if (is_dir($input)) {
			foreach (glob($input . '/*') as $child)
				if (!is_dir($child)) $files[] = validateFileName($child);
		} else
			$files[] = validateFileName($input);
	}

	return $files;
}

function validateFileName ($fileName) {
	$fileName = str_replace('\\', '/', trim($fileName));
	if (substr($fileName, 0, 1) == '"') $fileName = substr($fileName, 1);
	if (substr($fileName, -1) == '"') $fileName = substr($fileName, 0, -1);
	if (!$fileName) error('Missing input file.');
	if (!file_exists($fileName)) error("File does not exist: $fileName");
	return $fileName;
}

function configPrompt ($decklistOnlyOutput) {
	global $config, $rendererSettings, $rendererSections, $promptOutputClean;

	$configFiles = glob("config*.txt");
	foreach ($configFiles as $configFile) {
		if ($configFile == 'config.txt')
			$currentConfig =& $config;
		else
			$currentConfig =& $rendererSettings[$configFile];
		$prompt = @$rendererSections[$configFile]['prompt'];
		if (!$prompt) continue;
		foreach ($prompt as $name => $value) {
			if (!$value) continue;
			if ($name == 'output.clean') {
				// This is handled as a special case in cleanOutputDir().
				$promptOutputClean = true;
				continue;
			}
			echo $name . '=[' . $currentConfig[$name] . '] ';
			$input = trim(fgets(STDIN));
			if ($input == '') $input = $currentConfig[$name];
			$currentConfig[$name] = $input;
		}
	}
}

function cleanOutputDir ($pagedOutput) {
	global $config, $promptOutputClean;

	$outputDir = $config['output.directory'];
	$ext = $config['output.extension'];

	$skipImages = 0;
	if ($files = glob("$outputDir*.$ext")) {
		$append = false;
		echo strtoupper($ext) . ' files in output directory: ' . count($files) . "\n";
		if ($pagedOutput && file_exists($outputDir . 'lastPage.txt') && file_exists($outputDir . "page1.$ext")) {
			echo 'Append to files in output directory? (y/n) ';
			$append = strtolower(trim(fgets(STDIN))) == 'y';
		}
		if (!$append) {
			if (@$promptOutputClean) {
				echo 'output.clean=[' . $config['output.clean'] . '] ';
				$input = trim(fgets(STDIN));
				if ($input != '') $config['output.clean'] = $input;
			}
			if ($config['output.clean']) {
				echo 'Deleting ' . strtoupper($ext) . " files from output directory...\n";
				foreach ($files as $file)
					unlink($file);
				@unlink($outputDir . 'lastPage.txt');
			}
		}
	}

	@mkdir($outputDir);
	if (!file_exists($outputDir)) error("Error locating output directory: $outputDir");
}

function getNameFromPath ($fileName) {
	$fileName = basename($fileName);
	if (strrpos($fileName, '/') !== FALSE) $fileName = strrchr($fileName, '/');
	if (strrpos($fileName, '.') !== FALSE) $fileName = substr($fileName, 0, strrpos($fileName, '.'));
	return splitWords($fileName);
}

function splitWords ($text) {
	// Seperate "[lowercase char][uppercase char]" with a space.
	for ($i = 1, $n = strlen($text); $i < $n; $i++) {
		$char = substr($text, $i, 1);
		if ($char < 'A' || $char > 'Z') continue;
		$prevChar = substr($text, $i - 1, 1);
		if ($prevChar < 'a' || $prevChar > 'z') continue;
		$text = substr($text, 0, $i) . ' ' . substr($text, $i);
		$i++;
	}
	return upperCaseWords($text);
}

function upperCaseWords ($text) {
	$text = ucwords($text);
	$text = str_replace(' Of ', ' of ', $text);
	$text = str_replace(' A ', ' a ', $text);
	$text = str_replace(' The ', ' the ', $text);
	$text = str_replace(' De ', ' de ', $text);
	$text = str_replace(' With ', ' with ', $text);
	return $text;
}

function getPNG ($fileName, $errorMessage = null) {
	$image = @imagecreatefrompng($fileName);
	if (!$image) {
		if ($errorMessage)
			error($errorMessage);
		else
			return null;
	}
	list($width, $height) = getimagesize($fileName);
	return array($image, $width, $height);
}

function getGIF ($fileName, $errorMessage = null) {
	$image = @imagecreatefromgif($fileName);
	if (!$image) {
		if ($errorMessage)
			error($errorMessage);
		else
			return null;
	}
	list($width, $height) = getimagesize($fileName);
	return array($image, $width, $height);
}

// Avoid PHP5's fputcsv because it writes far too many extra quotes (any time the field contains a space!).
function writeCsvRow ($csvFile, $row) {
	$csvString = '';
	$writeComma = false;
	foreach ($row as $value) {
		if ($writeComma) $csvString .= ',';
		$writeQuote = strpos($value, ',') !== false || strpos($value, "\n") !== false;
		if ($writeQuote) {
			$value = str_replace('"', '""', $value);
			$csvString .= '"';
		}
		$csvString .= $value;
		if ($writeQuote) $csvString .= '"';
		$writeComma = true;
	}
	fwrite($csvFile, ($csvString . "\n"));
}

function parseNameValues ($text) {
	$values = array();
	if (preg_match_all('/([^:\s]+):("(?P<value1>[^"]+)"|\'(?P<value2>[^\']+)\'|(?P<value3>[^ ]+))/', $text, $matches, PREG_SET_ORDER))
		foreach ($matches as $match)
			$values[trim($match[1])] = trim(@$match['value1'] . @$match['value2'] . @$match['value3']);
	return $values;
}

// Reads past the UTF-8 bom if it is there.
function fopen_utf8 ($filename, $mode) {
	$file = @fopen($filename, $mode);
	if (!$file) error('Unable to open file: ' . $filename);
	$bom = fread($file, 3);
	if ($bom != "\xEF\xBB\xBF") rewind($file);
	return $file;
}

function getInnerSubstring($string,$delim){
	$string = explode($delim, $string, 3);
	return isset($string[1]) ? $string[1] : '';
}
	
function getLastSubstring($string,$delim){
	$string = explode($delim, $string, 3);
	return isset($string[2]) ? $string[2] : '';
}

function in_array_r($needle, $haystack, $strict = true) {
	foreach ($haystack as $item) {
		if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
			return true;
		}
	}
 
   return false;
}

function objectToArray($o) { 
	$a = array(); 
	foreach ($o as $k => $v) $a[$k] = (is_array($v) || is_object($v)) ? objectToArray($v): $v;
	return $a; 
}

function array_unique_multidimensional($input) {
	$serialized = array_map('serialize', $input);
	$unique = array_unique($serialized);
	return array_intersect_key($input, $unique);
}

function objArraySearch($array,$index,$value){
	$item = null;
	foreach($array as $arrayInf) {
		if($arrayInf->{$index}==$value){
			return $arrayInf;
		}
	}
    return $item;
}

function objArraySearchMultiple($array,$index1,$index2,$value1,$value2){
	$item = null;
	foreach($array as $arrayInf) {
		if(strtolower($arrayInf->{$index1})==$value1){
			$array2[] =  $arrayInf;
		}
	}
	foreach($array2 as $arrayInf) {
		if(strtolower($arrayInf->{$index2})==$value2){
			return $arrayInf;
		}
	}
    return $item;
}

function strposa($haystack, $needle, $offset=0) {
    if(!is_array($needle)) $needle = array($needle);
    foreach($needle as $query) {
        if(strpos($haystack, $query, $offset) !== false) return true; // stop on first true result
    }
    return false;
}

/* strpos that takes an array of values to match against a string
 * note the stupid argument order (to match strpos)
 */
function strpos_arr($haystack, $needle) {
    if(!is_array($needle)) $needle = array($needle);
    foreach($needle as $what) {
        if(($pos = strpos($haystack, $what))!==false) return $pos;
    }
    return false;
}

function str_split_unicode($str, $l = 0) {
    if ($l > 0) {
        $ret = array();
        $len = mb_strlen($str, "UTF-8");
        for ($i = 0; $i < $len; $i += $l) {
            $ret[] = mb_substr($str, $i, $l, "UTF-8");
        }
        return $ret;
    }
    return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
}

function smart_wordwrap($string, $width = 10, $break = "\n") {
	// split on problem words over the line length
	$pattern = sprintf('/([^ ]{%d,})/', $width);
	$output = '';
	$words = preg_split($pattern, $string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
	
	foreach ($words as $word) {
		if (false !== strpos($word, ' ')) {
			// normal behaviour, rebuild the string
			$output .= $word;
		} else {
			// work out how many characters would be on the current line
			$wrapped = explode($break, wordwrap($output, $width, $break));
			$count = $width - (strlen(end($wrapped)) % $width);
	
			// fill the current line and add a break
			$output .= substr($word, 0, $count) . $break;
	
			// wrap any remaining characters from the problem word
			$output .= wordwrap(substr($word, $count), $width, $break, true);
		}
	}
}
/**
 * Wraps any string to a given number of characters.
 *
 * This implementation is multi-byte aware and relies on {@link
 * http://www.php.net/manual/en/book.mbstring.php PHP's multibyte
 * string extension}.
 *
 * @see wordwrap()
 * @link https://api.drupal.org/api/drupal/core%21vendor%21zendframework%21zend-stdlib%21Zend%21Stdlib%21StringWrapper%21AbstractStringWrapper.php/function/AbstractStringWrapper%3A%3AwordWrap/8
 * @param string $string
 *   The input string.
 * @param int $width [optional]
 *   The number of characters at which <var>$string</var> will be
 *   wrapped. Defaults to <code>75</code>.
 * @param string $break [optional]
 *   The line is broken using the optional break parameter. Defaults
 *   to <code>"\n"</code>.
 * @param boolean $cut [optional]
 *   If the <var>$cut</var> is set to <code>TRUE</code>, the string is
 *   always wrapped at or before the specified <var>$width</var>. So if
 *   you have a word that is larger than the given <var>$width</var>, it
 *   is broken apart. Defaults to <code>FALSE</code>.
 * @return string
 *   Returns the given <var>$string</var> wrapped at the specified
 *   <var>$width</var>.
 */
function mb_wordwrap($string, $width = 75, $break = "\n", $cut = false) {
  $string = (string) $string;
  if ($string === '') {
    return '';
  }

  $break = (string) $break;
  if ($break === '') {
    trigger_error('Break string cannot be empty', E_USER_ERROR);
  }

  $width = (int) $width;
  if ($width === 0 && $cut) {
    trigger_error('Cannot force cut when width is zero', E_USER_ERROR);
  }

  if (strlen($string) === mb_strlen($string)) {
    return wordwrap($string, $width, $break, $cut);
  }

  $stringWidth = mb_strlen($string);
  $breakWidth = mb_strlen($break);

  $result = '';
  $lastStart = $lastSpace = 0;

  for ($current = 0; $current < $stringWidth; $current++) {
    $char = mb_substr($string, $current, 1);

    $possibleBreak = $char;
    if ($breakWidth !== 1) {
      $possibleBreak = mb_substr($string, $current, $breakWidth);
    }

    if ($possibleBreak === $break) {
      $result .= mb_substr($string, $lastStart, $current - $lastStart + $breakWidth);
      $current += $breakWidth - 1;
      $lastStart = $lastSpace = $current + 1;
      continue;
    }

    if ($char === ' ') {
      if ($current - $lastStart >= $width) {
        $result .= mb_substr($string, $lastStart, $current - $lastStart) . $break;
        $lastStart = $current + 1;
      }

      $lastSpace = $current;
      continue;
    }

    if ($current - $lastStart >= $width && $cut && $lastStart >= $lastSpace) {
      $result .= mb_substr($string, $lastStart, $current - $lastStart) . $break;
      $lastStart = $lastSpace = $current;
      continue;
    }

    if ($current - $lastStart >= $width && $lastStart < $lastSpace) {
      $result .= mb_substr($string, $lastStart, $lastSpace - $lastStart) . $break;
      $lastStart = $lastSpace = $lastSpace + 1;
      continue;
    }
  }

  if ($lastStart !== $current) {
    $result .= mb_substr($string, $lastStart, $current - $lastStart);
  }

  return $result;
}

?>
