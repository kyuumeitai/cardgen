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
class SplitRenderer extends CardRenderer {
	public function render () {
		global $config;
		
		$language = strtolower($config['output.language']);
		if ($language && $language != 'english') {
			if ($language == 'russian') {
				$font = 'MinionPro-Cn.ttf';
				$fonti = 'MinionPro-CnItalic.ttf';
				if ($config['render.m15'] != FALSE && $this->setDB->isM15($this->card->getDisplaySet()) != FALSE) {
					$fuseFontSize = 18;
				} else {
					$fuseFontSize = 19;
				}
			} else if ($language == 'japanese') {
				$font = 'mtgrulej.ttf';
				$fonti = 'mtgrulej.ttf';
				if ($config['render.m15'] != FALSE && $this->setDB->isM15($this->card->getDisplaySet()) != FALSE) {
					$fuseFontSize = 21;
				} else {
					$fuseFontSize = 22;
				}
			} else {
				$font = 'MPlantin.ttf';
				$fonti = 'MPlantinI.ttf';
				if ($config['render.m15'] != FALSE && $this->setDB->isM15($this->card->getDisplaySet()) != FALSE) {
					$fuseFontSize = 21;
				} else {
					$fuseFontSize = 22;
				}
			}
		} else {
			$font = 'MPlantin.ttf';
			$fonti = 'MPlantinI.ttf';
			if ($config['render.m15'] != FALSE && $this->setDB->isM15($this->card->getDisplaySet()) != FALSE) {
				$fuseFontSize = 24;
			} else {
				$fuseFontSize = 25;
			}
		}

		$card1 = clone $this->card;
		//var_dump(get_object_vars($this->card));
		$card1->setDisplayTitle(substr($card1->getDisplayTitle(), 0, strpos($card1->getDisplayTitle(), '/')));
		if(strpos($card1->englishType, '/')) $card1->englishType = substr($card1->englishType, 0, strpos($card1->englishType, '/'));
		$card1->title = substr($card1->title, 0, strpos($card1->title, '/'));
		$card1->type = substr($card1->type, 0, strpos($card1->type, '/'));
		if (strpos($card1->color, '/') != FALSE) $card1->color = substr($card1->color, 0, strpos($card1->color, '/'));
		$card1->cost = substr($card1->cost, 0, strpos($card1->cost, '/'));
		$card1->legal = trim(substr($card1->legal, 0, strpos($card1->legal, '-----')));
		$card1->flavor = trim(substr($card1->flavor, 0, strpos($card1->flavor, '//')));
		$card1->pt = substr($card1->pt, 0, strpos($card1->pt, '//'));
		$card1->artFileName = substr($card1->artFileName, 0, strpos($card1->artFileName, '|'));
		if ($config['output.english.title.on.translated.card'])	$card1->artist = $card1->title;
		else if(strpos($card1->artist, '//')) $card1->artist = substr($card1->artist, 0, strpos($card1->artist, '//'));


		$card2 = clone $this->card;
		$card2->setDisplayTitle(substr($card2->getDisplayTitle(), strpos($card2->getDisplayTitle(), '/') + 1));
		if(strpos($card2->englishType, '/')) $card2->englishType = substr($card2->englishType, strpos($card2->englishType, '/') + 1);
		$card2->title = substr($card2->title, strpos($card2->title, '/') + 1);
		$card2->type = substr($card2->type, strpos($card2->type, '/') + 1);
		if (strpos($card2->color, '/') != FALSE) $card2->color = substr($card2->color, strpos($card2->color, '/') + 1);
		$card2->cost = substr($card2->cost, strpos($card2->cost, '/') + 1);
		if ($card2->isFuse()) {
			$card2->legal = trim(getInnerSubstring($card2->legal,'-----'));
			}
		else {
			$card2->legal = trim(substr($card2->legal, strpos($card2->legal, '-----') + 5));
			}
		$card2->flavor = trim(substr($card2->flavor, strpos($card2->flavor, '//') + 2));
		$card2->pt = substr($card2->pt, strpos($card2->pt, '//') + 2);
		$card2->artFileName = substr($card2->artFileName, strpos($card2->artFileName, '|') + 1);
		if ($config['output.english.title.on.translated.card'])	$card2->artist = $card2->title;
		else if(strpos($card2->artist, '//')) $card2->artist = substr($card2->artist, strpos($card2->artist, '//') + 2);
		
		// Get fuse text and color if exists
		$fuse = clone $this->card;
		$costColors = $fuse->getCostColors(str_replace("/","",$fuse->cost));
		$fuse->englishType = "";
		$fuse->title = "";
		$fuse->type = "";
		if (strlen($costColors) >= 3) {
		$fuse->color = "Gld_$costColors";
		} else {
		$fuse->color = $costColors;
		}
		$fuse->cost = "";
		$fuse->legal = trim(getLastSubstring($fuse->legal,'-----'));
		$fuse->legal = preg_replace('/([ a-z]\#)/', '\\1  ', $fuse->legal);
		$fuse->flavor = "";
		$fuse->pt = "";
		$fuse->collectorNumber = "";
		$fuse->rarity = "";
		$fuse->artFileName = "";
		$fuse->artist = "";
		$fuse->set = "";
		

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
		
		
		//get fuse bar overlay
		if ($fuse->legal != "" && $config['render.m15'] != FALSE && $this->setDB->isM15($this->card->getDisplaySet()) != FALSE && $this->card->set != 'UNH') {
			$fuseColor = @imagecreatefrompng("images/m15/fuse-overlay/" . $fuse->color . ".png");
			if (!$fuseColor) error("Fuse bar missing for color: $fuse->color.");
		} else if ($fuse->legal != "" && $config['render.m15'] != FALSE && $config['render.eighth'] == FALSE && $this->setDB->isM15($this->card->getDisplaySet()) == FALSE && $this->card->set != 'UNH') {
			$fuseColor = @imagecreatefrompng("images/m15/fuse-overlay/" . $fuse->color . ".png");
			if (!$fuseColor) error("Fuse bar missing for color: $fuse->color.");
		} else if ($fuse->legal != "" && $this->card->set != 'UNH') {
			$fuseColor = @imagecreatefrompng("images/eighth/fuse-overlay/" . $fuse->color . ".png");
			if (!$fuseColor) error("Fuse bar missing for color: $fuse->color.");
		}

		echo $this->card . '...';
		
		if (/*($config['render.m15'] != FALSE && $this->setDB->isM15($this->card->getDisplaySet()) != FALSE || $config['render.m15'] != FALSE && $config['render.eighth'] == FALSE && $this->setDB->isEighth($this->card->getDisplaySet()) != FALSE || $config['render.m15'] != FALSE && $config['render.preEighth'] == FALSE && $this->setDB->isPre8th($this->card->getDisplaySet())) && */strpos(get_class($r1[0]),'M15') != FALSE) {
			$height = 720;
			$width = 1020;
			$fuseY = 626;
			$fuseX = 33;
			$fuseRight = 980;
		} else {
			$height = 736;
			$width = 1050;
			$fuseY = 663;
			$fuseX = 33;
			$fuseRight = 1006;
		}
		
		$canvas = imagecreatetruecolor($height, $width);
		imagecopyresampled($canvas, $image1, 0, $width / 2, 0, 0, $height, $width / 2, $width, $height);
		imagecopyresampled($canvas, $image2, 0, 0, 0, 0, $height, $width / 2, $width, $height);
		imagedestroy($image1);
		imagedestroy($image2);
		// Write fuse bar overlay if it exists
		if ($fuse->legal != "" && $this->card->set != 'UNH') {
			$fontObject = new Font("$fuseFontSize, $font/$fonti");
			$this->drawText($fuseColor, $fuseX, $fuseY, $fuseRight, $fuse->legal, $fontObject);
			$fuseColorTemp = imagerotate($fuseColor, 90, 0);
			imagedestroy($fuseColor);
			$fuseColor = $fuseColorTemp;
			imagecopy($canvas, $fuseColor, 0, 0, 0, 0, $height, $width);
			imagedestroy($fuseColor);
			}

		echo "\n";
		return $canvas;
	}
	

	public function getSettings () {
		throw new Exception('SplitRenderer does not have settings.');
	}
}

?>
