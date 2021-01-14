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
class ArtDB {
	private $ext;
	private $root;
	private $directories = array();
	private $titleToUsedArt = array();
	private $picPatterns = array(' %', '%', ' (%)');

	public function __construct (SetDB $setDB) {
		global $config;

		$this->setDB = $setDB;

		/*if ($config['art.use.xlhq.full.card'] != false) {
			$this->ext = '.' . $config['art.xlhq.extension'] . '.' . $config['art.extension'];
		}
		else */$this->ext = '.' . $config['art.extension'];

		$this->root = $config['art.directory'];
		if (!$this->root) error('Missing art.directory from config.txt.');
		$this->root = str_replace('\\', '/', $this->root);
		if (substr($this->root, -1, 1) == '/') $this->root = substr($this->root, 0, -1);

		echo 'Scanning art directories...';
		$this->collectArtDirectories($this->root);
		echo "\n";
	}

	private function collectArtDirectories ($path) {
		global $config;

		$this->directories[] = $path . '/';

		if (count($this->directories) % 3 == 0) echo '.';

		$dir = @opendir($path);
		if (!$dir) {
			if ($config['art.error.when.missing']) error("Unable to locate art directory: $path");
			echo "\n";
			warn("Unable to locate art directory: $path");
			return;
		}

		$dirs = array();
		while (false !== ($file = readdir($dir))) {
			if ($file == '.' || $file == '..') continue;
			if (!is_dir($path . '/' . $file)) continue;
			$dirs[] = $path . '/' . $file;
		}
		closedir($dir);

		foreach ($dirs as $path)
			$this->collectArtDirectories($path);
	}

	public function getArtFileName ($title, $set, $pic) {
		global $config;

		if (strpos($title, '/') !== false) {
			// Split cards art file names are delimited by a pipe ("|").
			$title1 = substr($title, 0, strpos($title, '/'));
			$title2 = substr($title, strpos($title, '/') + 1);
			return $this->getArtFileName($title1, $set, $pic) . '|' . $this->getArtFileName($title2, $set, $pic);
		}

		$name = $title;
		//$name = preg_replace('/([\w\s\,\-\p{L}]+)([ 0-3]{0,2})/', '\\1', $name);
		$name = str_replace('Avatar: ', '', $name);
		$name = str_replace(':', '', $name);
		$name = str_replace('"', '', $name);
		$name = str_replace('?', '', $name);
		$name = str_replace('’', "'", $name);
		$name = str_replace('“', '', $name);
		$name = str_replace('”', '', $name);
		$name = str_replace('é', 'e', $name);

		$fileName = null;
		if ($config['art.random']) {
			$images = array();
			foreach ($this->directories as $path)
				$this->collectImages($images, $path, $name);
			$images = array_keys($images);
			if (count($images) > 0) {
				// Choose one that hasn't been chosen yet.
				$usedArt = @$this->titleToUsedArt[$title];
				if (!$usedArt) $this->titleToUsedArt[$title] = $usedArt = array();
				while (true) {
					$i = rand(0, count($images) - 1);
					$fileName = $images[$i];
					if (!in_array($fileName, $usedArt)) break;
				}
				$this->titleToUsedArt[$title][] = $fileName;
				// Reset if all images have been chosen.
				if (count($this->titleToUsedArt[$title]) == count($images)) $this->titleToUsedArt[$title] = array();
			}
		} else {
			if ($config['art.debug']) echo "\n";
			if ($config['art.subdirectory.token'] != '') {
				$tokenDir = $config['art.subdirectory.token'] . '/';
			}
			else {
				$tokenDir = null;
			}
			$titleToToken = csvToArray('data/titleToToken.csv');
			$tokenAndCard = array('Kobolds of Kher Keep', 'Festering Goblin', 'Goldmeadow Harrier', 'Llanowar Elves', 'Metallic Sliver', 'Spark Elemental', 'Cloud Sprite', 'Illusion', 'Shapeshifter', 'Assembly-Worker', 'Giant Adephage');
			
			foreach ($this->setDB->getAbbrevs($set) as $abbrev) {
				if ((array_key_exists(strtolower($name), $titleToToken) && in_array($name, $tokenAndCard) == FALSE) || (in_array($name, $tokenAndCard) && ($set == 'FUT'||$set == 'C13'||$set == 'TSP' && $name == 'Kobolds of Kher Keep'||$set == 'TSP' && $name == 'Assembly-Worker' && $pic == 'token'||$set == 'GTC' && $name == 'Giant Adephage' && $pic == 'token'||$name == 'Illusion' && $set != 'APC'||$set == 'C15'||$set == 'LRW')) || strpos($name, 'Emblem') !== FALSE) {
					$fileName = $this->findImage($this->root . '/' . $abbrev . '/' . $tokenDir, $name, $pic);
					if (!$fileName) $fileName = $this->findImage($this->root . '/' . $abbrev . '/', $name, $pic);
				} else {
					$fileName = $this->findImage($this->root . '/' . $abbrev . '/', $name, $pic);
				}
				if ($fileName)
					break;
			}
		}

		if (!$fileName && $config['art.error.when.missing']) error('No art found for card: ' . $title . ' [' . $set . ']');

		if ($config['art.debug']) {
			if ($fileName)
				echo "Using art: $fileName\n";
			else
				echo "Art not found for: $title [$set]\n";
		}

		return $fileName;
	}

	private function findImage ($path, $name, $pic) {
		global $config;

		if ($pic) {
			foreach ($this->picPatterns as $pattern) {
				$fileNameXLHQ = $path . $name . str_replace('%', $pic, $pattern) . '.' . $config['art.xlhq.extension'] . $this->ext;
				$fileNameXLHQLowercase = $path . strtolower($name) . str_replace('%', $pic, $pattern) . '.' . $config['art.xlhq.extension'] . $this->ext;
				$fileName = $path . $name . str_replace('%', $pic, $pattern) . $this->ext;
				$fileNameLowercase = $path . strtolower($name) . str_replace('%', $pic, $pattern) . $this->ext;
				if ($config['art.debug']) {
					echo "Looking for art: $fileName";
					if (url_validate($fileName)) echo ' *found*';
					elseif (url_validate($fileNameLowercase)) echo ' *found*';
					echo "\n";
				}
				if (file_exists($fileNameXLHQ) && $config['art.use.xlhq.full.card'] != false) return $fileNameXLHQ;
				elseif (file_exists($fileNameXLHQLowercase) && $config['art.use.xlhq.full.card'] != false) return $fileNameXLHQLowercase;
				elseif (file_exists($fileName)) return $fileName;
				elseif (file_exists($fileNameLowercase)) return $fileNameLowercase;
			}
		} else {
			$fileNameXLHQ =  $path . $name . '.' . $config['art.xlhq.extension'] . $this->ext;
			$fileNameXLHQLowercase =  $path . strtolower($name) . '.' . $config['art.xlhq.extension'] . $this->ext;
			$fileName = $path . $name . $this->ext;
			$fileNameLowercase = $path . strtolower($name) . $this->ext;
			if ($config['art.debug']) {
				echo "Looking for art: $fileName";
				if (url_validate($fileName)) echo ' *found*';
				elseif (url_validate($fileNameLowercase)) echo ' *found*';
				echo "\n";
			}
			if (file_exists($fileNameXLHQ) && $config['art.use.xlhq.full.card'] != false) return $fileNameXLHQ;
			elseif (file_exists($fileNameXLHQLowercase) && $config['art.use.xlhq.full.card'] != false) return $fileNameXLHQLowercase;
			elseif (file_exists($fileName)) return $fileName;
			elseif (file_exists($fileNameLowercase)) return $fileNameLowercase;
		}
		return null;
	}

	private function collectImages (&$images, $path, $name) {
		global $config;

		// Look for a file with no picture number.
		$fileNameXLHQ =  $path . $name . '.' . $config['art.xlhq.extension'] . $this->ext;
		$fileName = $path . $name . $this->ext;
		if ($config['art.debug']) {
			echo "\nLooking for art: $fileName";
			if (url_validate($fileName)) echo ' *found*';
			echo "\n";
		}
		if (file_exists($fileNameXLHQ) && $config['art.use.xlhq.full.card'] != false) $images[$fileName] = true;
		else if (file_exists($fileName)) $images[$fileName] = true;

		// Find all images with picture numbers.
		$i = 1;
		while (true) {
			$fileName = $this->findImage($path, $name, $i);
			if ($fileName)
				$images[$fileName] = true;
			else
				break;
			$i++;
		}

		return $images;
	}

	public function resetUsedArt () {
		$this->titleToUsedArt = array();
	}
}

?>
