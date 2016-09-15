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
class TransformRenderer extends CardRenderer {
	public $version;
	static private $titleToTransform;

	public function __construct (SetDB $setDB, ArtDB $artDB, $version) {
		$this->setDB = $setDB;
		$this->artDB = $artDB;
		$this->version = $version;
	}

	public function render () {
		global $config;

		$card = $this->card;

		$settings = $this->getSettings();

		echo $card . ' ' . $this-> version . '...';
		echo "\n";

		$cards = explode("\n-----\n", $card->legal);
		$flavor1 = trim(substr($card->flavor, 0, strpos($card->flavor, "-----")));
		$flavor2 = trim(substr($card->flavor, strpos($card->flavor, "-----") + 6));

		$pts = explode("|", $card->pt);
		if (strpos($card->color, "/")) $colors = explode("/", $card->color);

		$card1 = clone $this->card;
		$card1->legal = $cards[0];
		$card1->flavor = $flavor1;
		if (!empty($colors[0])) $card1->color = $colors[0];

		if (preg_match("/(.*?)\n(.*?(Enchantment|結界|结界|Enchantement|Verzauberung|Incantesimo|エンチャント|Encantamento|Чары|Encantamiento|부여마법|Equipment|武具|武具|équipement|Ausrüstung|Equipaggiamento|装備品|Equipo|Снаряжение|Equipamento|장비).*?)\n(.*?)\n(.*)/su", $cards[1], $matches)) {
			$title2 = $matches[1];
			$type2 = $matches[2];
			$pt2 = "";
			$legal2 = $matches[4] . "\n" . $matches[5];
		} else if (preg_match("/(.*?)\n(.*?)\n(.*?)\n(.*)/s", $cards[1], $matches) && (!$card->isPlaneswalker())) {
			$title2 = $matches[1];
			$type2 = $matches[2];
			$pt2 = $matches[3];
			$legal2 = $matches[4];
		} else {
			preg_match("/(.*?)\n(.*?)\n(.*)/s", $cards[1], $matches);
			$title2 = $matches[1];
			$type2 = $matches[2];
			$pt2 = '';
			$legal2 = $matches[3];
		}
		
		if ($this->version == "spark" || $pt2 == "") {
		} else {
			$card1->pt .= '|'.$pt2;
			}

		//$title2 = str_replace('AE', 'Æ', $title2);
		//$title2 = str_replace("'", '’', $title2);
		//$title2 = str_replace('â€™', '’', $title2);

		$card2 = clone $this->card;
		$card2->setDisplayTitle($title2);
		$card2->title = $this->translationToTitle($title2);
		if (!empty($colors[1])) $card2->color = $colors[1];
		if (!$card2->title) $card2->title = $title2;
		$card2->type = $type2;
		//$card2->englishtype = preg_replace('/^\w*/u', 'Planeswalker', $type2);
		if (stripos($card2->type, 'Arpenteur') !== false) $card2->englishType = str_replace('Arpenteur', 'Planeswalker', $type2);
		else $card2->englishType = $type2;
		$card2->pt = $pt2;
		$card2->legal = $legal2;
		$card2->flavor = $flavor2;
		$card2->cost = "";
		if ($this->translationToTitle($title2) == 'Brisela, Voice of Nightmares') $card2->rarity = 'M';
		$card2->artFileName = $this->artDB->getArtFileName($card2->title, $card2->set, $card2->pic);
		
		if (TransformRenderer::$titleToTransform[(string)strtolower($card1->title)][1] != '')
			$card1->color = TransformRenderer::$titleToTransform[(string)strtolower($card1->title)][1];
		if (TransformRenderer::$titleToTransform[(string)strtolower($card2->title)][1] != '')
			$card2->color = TransformRenderer::$titleToTransform[(string)strtolower($card2->title)][1];
		
		if ($config['card.transform.as.split'] == FALSE) {
			if($this->version == "spark"||$this->version == "day"||$this->version == "moon") {
				$r = $this->writer->getCardRenderer($card1);
			} else
				$r = $this->writer->getCardRenderer($card2);
			$canvas = $r[0]->render();
		} else {
			$isPre8th = $this->setDB->isPre8th($card->set);
			$isEighth = $this->setDB->isEighth($card->set);
			$isM15 = $this->setDB->isM15($card->set);
			
			$r1 = $this->writer->getCardRenderer($card1);
			$image1 = $r1[0]->render();
			$image1tmp = imagerotate($image1, 90, 0);
			imagedestroy($image1);
			$image1 = $image1tmp;
	
			$r2 = $this->writer->getCardRenderer($card2);
			$image2 = $r2[0]->render();
			$image2tmp = imagerotate($image2, 90, 0);
			imagedestroy($image2);
			$image2 = $image2tmp;
			
			if ($isEighth && $config['render.eighth'] || $isPre8th && $config['render.preEighth']) {		
				$canvas = imagecreatetruecolor(736, 1050);
				imagecopyresampled($canvas, $image1, 0, 1050 / 2, 0, 0, 736, 525, 1050, 736);
				imagecopyresampled($canvas, $image2, 0, 0, 0, 0, 736, 525, 1050, 736);
			} else {
				$canvas = imagecreatetruecolor(720, 1020);
				imagecopyresampled($canvas, $image1, 0, 1020 / 2, 0, 0, 720, 510, 1020, 720);
				imagecopyresampled($canvas, $image2, 0, 0, 0, 0, 720, 510, 1020, 720);
			}
				
				imagedestroy($image1);
				imagedestroy($image2);
		}
		
		return $canvas;
	}

	public function getCardName() {
		if($this->version == "spark" || $this->version == "day" || $this->version == "moon") {
			return $this->card->title;
		} else {
			$cards = explode("\n-----\n", $this->card->legal);
			preg_match("/(.*?)\n(.*?)\n(.*)/s", $cards[1], $matches);
			return $this->translationToTitle($matches[1]);
		}
	}

	public function getSettings () {
		global $rendererSettings;
		if (!TransformRenderer::$titleToTransform) TransformRenderer::$titleToTransform = $this->csvToArray('data/titleToTransform.csv');
		return 0;
	}

	private function csvToArray ($fileName) {
		$array = array();
		$file = fopen_utf8($fileName, 'r');
		if (!$file) error('Unable to open file: ' . $fileName);
		while (($data = fgetcsv($file, 6000, ',')) !== FALSE)
			$array[(string)strtolower($data[0])] = array(trim($data[1]), isset($data[2]) ? trim($data[2]) : "");
		fclose($file);
		return $array;
	}
	
	public function translationToTitle ($title) {
		$transToTitle = fopen_utf8("data/translationToTitle.csv", 'rb');
		if (!$transToTitle) error('Unable to open file: ' . $transToTitle);
		$i = 0;
		while (($row = fgetcsv($transToTitle, 300, ',', '"')) !== FALSE) {
			if($i++ == 0) continue; //skip first line
			
			if (in_array($title, $row)) return (string)$row[0];
			continue;
			
		}
	}
}

?>
