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
class PreEighthRenderer extends CardRenderer {
	static private $titleToTombstone;
	static private $titleToIndicator;

	public function render () {
		global $config;

		$languageFont = null;
		$language = strtolower($config['output.language']);
		if ($language && $language != 'english') {
			if ($language == 'russian') $languageFont = '.russian';
			if ($language == 'japanese') $languageFont = '.japanese';
			if ($this->card->title == $this->card->getDisplayTitle()) $languageFont = null;
		}
		
		/*if ($config['card.corrected.promo.symbol'] != FALSE) {
			$this->card = CardDB::correctPromoSymbols($this->card);
			}*/
		echo $this->card . '...';
		$card = $this->card;

		$settings = $this->getSettings();
		$costColors = Card::getCostColors($card->cost);
		$canvas = imagecreatetruecolor(736, 1050);

		// Art image.
		if ($config['art.use.xlhq.full.card'] != false && stripos($card->artFileName,'xlhq') != false) {
			$this->drawArt($canvas, $card->artFileName, $settings['art.xlhq.top'], $settings['art.xlhq.left'], $settings['art.xlhq.bottom'], $settings['art.xlhq.right'], !$config['art.keep.aspect.ratio']);
		}
		if(($card->set == "UGL" && $card->isBasicLand()))
			$this->drawArt($canvas, $card->artFileName, 73, 56, 927, 690, !$config['art.keep.aspect.ratio']);
		else 
			$this->drawArt($canvas, $card->artFileName, $settings['art.top'], $settings['art.left'], $settings['art.bottom'], $settings['art.right'], !$config['art.keep.aspect.ratio']);

		echo '.';

		// Background image.
		if ($card->isLand()) {
			// Land frame.
			$landColors = @$this->writer->titleToLandColors[strtolower($card->title)];
			$notBasicFrame = '';
			if ($card->set == 'LEG') {
				switch (strtolower($card->englishType)) {
				case 'legendary land': $landColors = 'CL'; break;
				}
			}
			if ($card->set == 'MIN'||$card->set == 'BIN') {
				switch (strtolower($card->title)) {
				case 'arena': $landColors = 'CZ'; break;
				}
			}
			if ($settings['card.multicolor.fetch.land.frames']) {
				switch (strtolower($card->title)) {
				case 'flooded strand': $landColors = 'WU'; break;
				case 'bloodstained mire': $landColors = 'BR'; break;
				case 'wooded foothills': $landColors = 'RG'; break;
				case 'polluted delta': $landColors = 'UB'; break;
				case 'windswept heath': $landColors = 'GW'; break;
				case 'flood plain': $landColors = 'WU'; break;
				case 'rocky tar pit': $landColors = 'BR'; break;
				case 'mountain valley': $landColors = 'RG'; break;
				case 'bad river': $landColors = 'UB'; break;
				case 'grasslands': $landColors = 'GW'; break;
				}
			}
			if (!$landColors) error('Land color missing for card: ' . $card->title);
			if (strlen($landColors) > 1) {
				$useMulticolorLandFrame = strpos($settings['card.multicolor.land.frames'], strval(strlen($landColors))) !== false;
				if (strlen($landColors) > 2 || !$useMulticolorLandFrame) $landColors = 'A';
			}
			else if(!$card->isBasicLand() && $landColors != 'A' && $landColors != 'C')
				$notBasicFrame = 'C';
			if (in_array(strtolower($card->title), array('badlands','bayou','plateau','savannah','scrubland','taiga','tropical island','tundra','underground sea','volcanic island'))) {
				$bgImage = @imagecreatefrompng("images/preEighth/land/classicduals/$notBasicFrame$landColors.png");
			} else if ($card->set == "UGL" && $card->isBasicLand()) {
				$bgImage = @imagecreatefrompng("images/preEighth/land/" . $card->set . "_" . $landColors . ".png");
				if (!$bgImage) error("Background image not found for land color \"$card->set . _$landColors\": " . $card->title);
			} else {
				$bgImage = @imagecreatefrompng("images/preEighth/land/$notBasicFrame$landColors.png");
			}
			if (!$bgImage) error("Background image not found for land color \"$notBasicFrame$landColors\": " . $card->title);
		} else {
			// Mono color frame.
			$bgImage = @imagecreatefrompng('images/preEighth/cards/' . $card->color . '.png');
			if (!$bgImage) error('Background image not found for mono color: ' . $card->color);
		}
		imagecopy($canvas, $bgImage, 0, 0, 0, 0, 736, 1050);
		imagedestroy($bgImage);
		
		$whiteBorderSets = explode(',', $settings['card.white.border.sets']);
		if ($settings['card.white.border.core.sets'] != FALSE && in_array($card->set, $whiteBorderSets) != FALSE) {
			$whiteBorder = @imagecreatefrompng('images/preEighth/whiteBorder.png');
			if (!$whiteBorder) error('Image not found for White Border');
			imagecopy($canvas, $whiteBorder, 0, 0, 0, 0, 736, 1050);
			imagedestroy($whiteBorder);
		}

		// Power / toughness.
		$ptColor = preg_match('/color:(\d{0,3},\d{0,3},\d{0,3})/', $settings['font.pt'], $matches);
		if (!empty($matches[1])) $ptColor = "$matches[1]";
		else $ptColor = '255,255,255';
		$greyTextSets = explode(',', $settings['card.grey.title.type.artist.sets']);
		if (in_array($card->set, $greyTextSets) && $settings['card.prerevised.grey.text'] != FALSE) $ptColor = '127,127,127';
		if ($card->set == "UGL" && $card->title == 'B.F.M.' && $card->pic == 'Left'){
			}
		else if ($settings['card.portal.pt.images'] != false && ($card->set == 'POR'||$card->set == 'PO2'||$card->set == 'PTK') && $card->pt) {
			$porPT = explode('/', $card->pt);
			$porPT[0] .= '{P1}';
			$porPT[1] .= '{T1}';
			$card->pt = implode('/',$porPT);
			$this->drawText($canvas, $settings['pt.center.x'] - 10, $settings['pt.center.y'], $settings['pt.width'], $card->pt, $this->font('pt', "color:$ptColor"));
		}
		else if ($card->pt) $this->drawText($canvas, $settings['pt.center.x'], $settings['pt.center.y'], $settings['pt.width'], $card->pt, $this->font('pt', "color:$ptColor"));

		// Casting cost.
		if ($card->set == "UGL" && $card->title == 'B.F.M.' && $card->pic == 'Left'){
			$costLeft = $settings['cost.right'];
			}
		else 
			$costLeft = $this->drawCastingCost($canvas, $card->getCostSymbols(), $settings['cost.top'], $settings['cost.right'], $settings['cost.size']);

		echo '.';

		// Set and rarity.
		$pre6thCoreSets = explode(',', $settings['card.pre6th.core.sets']);
		if ($card->set == "UGL" && ($card->isBasicLand() || $card->title == 'B.F.M.' && $card->pic == 'Left')) {
			$rarityLeft = $settings['rarity.right'];
			//$rarityMiddle = $settings['rarity.center.y'];
			//if ($card->isLand()) $rarityMiddle += 2; // Rarity on pre8th lands is slightly lower.
			//$rarityLeft = $this->drawRarity($canvas, $card->getDisplayRarity(), $card->getDisplaySet(), $settings['rarity.right'], $rarityMiddle, $settings['rarity.height'], $settings['rarity.width'], true, $settings['card.rarity.fallback']);
		}
		else if (!$card->isBasicLand() && in_array($card->set, $pre6thCoreSets) === FALSE || $settings['card.basic.land.set.symbols'] != FALSE && in_array($card->set, $pre6thCoreSets) === FALSE || !$card->isBasicLand() && in_array($card->set, $pre6thCoreSets) !== FALSE && $settings['card.use.symbol.pre6th.core.set'] != FALSE || $settings['card.basic.land.set.symbols'] != FALSE && in_array($card->set, $pre6thCoreSets) !== FALSE && $settings['card.use.symbol.pre6th.core.set'] != FALSE) {
			$rarityMiddle = $settings['rarity.center.y'];
			if ($card->isLand()) $rarityMiddle += 2; // Rarity on pre8th lands is slightly lower.
			$rarityLeft = $this->drawRarity($canvas, $card->getDisplayRarity(), $card->getDisplaySet(), $settings['rarity.right'], $rarityMiddle, $settings['rarity.height'], $settings['rarity.width'], true, $settings['card.rarity.fallback']);
		} 
		else {
			$rarityLeft = $settings['rarity.right'];
			}

		// Tombstone sign.
		if ($settings['card.tombstone']) {
			if ($this->getTombstone($card->title)) {
				list($image, $width, $height) = getPNG('images/preEighth/tombstone.png', 'Tombstone image not found.');
				imagecopy($canvas, $image, $settings["tombstone.left"], $settings["tombstone.top"], 0, 0, $width, $height);
				imagedestroy($image);
			}
		}

		// Card title.
		$titleColor = preg_match('/color:(\d{0,3},\d{0,3},\d{0,3})/', $settings['font.title'], $matches);
		if (!empty($matches[1])) $titleColor = "$matches[1]";
		else $titleColor = '255,255,255';
		$greyTextSets = explode(',', $settings['card.grey.title.type.artist.sets']);
		if (in_array($card->set, $greyTextSets) && $settings['card.prerevised.grey.text'] != FALSE) $titleColor = '127,127,127';
		if ($card->set == "UGL" && $card->isBasicLand()) {
			$this->drawText($canvas, $settings['title.x'], $settings['title.y'], $costLeft - $settings['title.x'], $card->getDisplayTitle(), $this->font("title$languageFont", "color:$titleColor"));
		}
		else if ($card->set == "UGL" && $card->title == "B.F.M." && $card->pic == 'Right') {
		}
		else {
			$this->drawText($canvas, $settings['title.x'], $settings['title.y'], $costLeft - $settings['title.x'], $card->getDisplayTitle(), $this->font("title$languageFont", "color:$titleColor"));
		}

		echo '.';

		// Type.
		$typeColor = preg_match('/color:(\d{0,3},\d{0,3},\d{0,3})/', $settings['font.type'], $matches);
		if (!empty($matches[1])) $typeColor = "$matches[1]";
		else $typeColor = '255,255,255';
		if (in_array($card->set, $greyTextSets) && $settings['card.prerevised.grey.text'] != FALSE) $typeColor = '127,127,127';
		$typeBaseline = $settings['type.y'];
		if ($card->isLand()) $typeBaseline += 2; // Type on pre8th lands is slightly lower.
		if ($card->set == "UGL" && $card->isBasicLand()) {
			$typearray = preg_split('/([—:～])/u', $card->type);
			$typeBaseline += 341;
			$this->drawText($canvas, $settings['type.x'] - 30, $typeBaseline, $rarityLeft - $settings['type.x'], $typearray[0], $this->font("type$languageFont", "color:$typeColor"));
		}
		else if (($indicator = $this->getIndicator($card->title)) && ($settings['card.use.indicator'] != FALSE)) {
			$image = @imagecreatefrompng("images/symbols/indicators/" . $indicator . '.png');
			imagecopyresampled($canvas, $image, $settings['indicator.x'], $settings['indicator.y'], 0, 0, $settings['indicator.size'], $settings['indicator.size'], 300, 300);
			imagedestroy($image);
			$typex = $settings['type.indicator.x'];
			$this->drawText($canvas, $typex, $settings['type.y'], $rarityLeft - $settings['type.x'], $card->type, $this->font("type$languageFont", "color:$typeColor"));
		}
		else {
			$this->drawText($canvas, $settings['type.x'], $typeBaseline, $rarityLeft - $settings['type.x'], $card->type, $this->font("type$languageFont", "color:$typeColor"));
		}

		if ($card->isBasicLand()) {
			// Basic land symbol instead of legal text.
			list($image, $width, $height) = getPNG("images/symbols/land/$landColors.png", "Basic land image not found for: images/symbols/land/$landColors.png");
			if ($card->set == "UGL") {
			}
			else {
				imagecopy($canvas, $image, 373 - ($width / 2), 640, 0, 0, $width, $height);
				}
			imagedestroy($image);
		}
		else if ($card->isLand() && strlen($landColors) == 2 && (!$card->legal || preg_match('/([\#]{0,1}[\((]\{T\}[ \::]{1,2}.*?\{[WUBRG]\}.*?\{[WUBRG]\}.*?[\.?][\))][\#]{0,1})(?!.)/su', $card->legal) && $settings['card.dual.land.symbols'] != FALSE)) {
			// Dual land symbol instead of legal text.
			if ($settings['card.dual.land.symbols'] == 1) {
				// Single hybrid symbol.
				list($image, $width, $height) = getPNG("images/symbols/land/$landColors.png", "Dual symbol image not found for: $landColors");
				imagecopy($canvas, $image, 368 - ($width / 2), 667, 0, 0, $width, $height);
				imagedestroy($image);
			} else if ($settings['card.dual.land.symbols'] == 2) {
				// One of each basic symbol.
				$landColor = substr($landColors, 0, 1);
				list($image, $width, $height) = getPNG("images/symbols/land/$landColor.png", 'Basic land image not found for: ' . $card->title);
				imagecopy($canvas, $image, 217 - ($width / 2), 640, 0, 0, $width, $height);
				imagedestroy($image);

				$landColor = substr($landColors, 1, 2);
				list($image, $width, $height) = getPNG("images/symbols/land/$landColor.png", 'Basic land image not found for: ' . $card->title);
				imagecopy($canvas, $image, 519 - ($width / 2), 640, 0, 0, $width, $height);
				imagedestroy($image);
			}
		} else if (preg_match('/\{T\}[ \:]{1,2} (.*?) \{([WUBRGC])\}([A-Za-z ]*?)\.(?!.)/su', $card->legal, $matches) && $config['card.use.symbol.on.mox'] == true && $card->flavor == '') {
			list($image, $width, $height) = getPNG("images/symbols/land/$matches[1].png", "Basic land image not found for: images/symbols/land/$matches[1].png");
			imagecopy($canvas, $image, 373 - ($width / 2), 640, 0, 0, $width, $height);
			imagedestroy($image);
		} else {
			// Legal and flavor text.
			if ($indicator == "R" && ($settings['card.use.indicator'] != FALSE)) {
				$card->legal = preg_replace("/(.*? is red.)/s", "", $card->legal);
			}
			$legaltemp = str_replace('#', '', $card->legal);
			if((strlen($legaltemp) <= 40 || preg_match('/([\#]{0,1}[\((]\{T\}[ \::]{1,2}.*?\{[WUBRG]\}.*?\{[WUBRG]\}.*?[\.?][\))][\#]{0,1})(?!.)/su', $card->legal)) && strpos($card->legal, "\n") === FALSE && $card->flavor == '') {
				$this->drawText($canvas, ($settings['text.right'] + $settings['text.left']) / 2, ($settings['text.top'] + $settings['text.bottom']) / 2, null, $card->legal, $this->font("text$languageFont", 'centerY:true centerX:true'));
			} else if ($settings['card.portal.pt.images'] != false && ($card->set == 'POR'||$card->set == 'PO2'||$card->set == 'PTK') && preg_match('/[+\-\d]*\/[+\-\d]*/',$card->legal)) {
				$porLegal = preg_match('/(.*?)([+\-\dX\*]*\/[+\-\dX\*]*)(.*)/s',$card->legal,$matches);
				$porLegalPT = explode('/',$matches[2]);
				$porLegalPT[0] .= '{P2}';
				$porLegalPT[1] .= '{T2}';
				$porLegalPT = implode('/',$porLegalPT);
				$card->legal = $matches[1] . $porLegalPT . $matches[3];
				$this->drawLegalAndFlavorText($canvas, $settings['text.top'], $settings['text.left'], $settings['text.bottom'], $settings['text.right'], $card->legal, $card->flavor, $this->font("text$languageFont"));
			} else {
				$this->drawLegalAndFlavorText($canvas, $settings['text.top'], $settings['text.left'], $settings['text.bottom'], $settings['text.right'], $card->legal, $card->flavor, $this->font("text$languageFont"));
			}
		}

		// Artist and copyright.
		$artistColor = preg_match('/color:(\d{0,3},\d{0,3},\d{0,3})/', $settings['font.artist'], $matches);
		if (!empty($matches[1])) $artistColor = "$matches[1]";
		else $artistColor = '255,255,255';
		$greyTextSets = explode(',', $settings['card.grey.title.type.artist.sets']);
		if (in_array($card->set, $greyTextSets) && $settings['card.prerevised.grey.text'] != FALSE) $artistColor = '127,127,127';
		if ($card->set == "UGL" && $card->isBasicLand()) {
			$this->drawText($canvas, 55, 1020, null, 'Illus. ' . $card->artist, $this->font('artist', 'centerX:false'));
		}
		else if ($card->artist) {
			$this->drawText($canvas, $settings['artist.x'], $settings['artist.y'], null, 'Illus. ' . $card->artist, $this->font('artist', "color:$artistColor"));
		}
		if ($card->set == "UGL" && $card->isBasicLand()) {
			$card->copyright = $config['card.copyright.ugl'] . ' ' . $card->collectorNumber;
			$copyrightColor = '255,255,255';
			if ($card->color == 'W') $copyrightColor = '0,0,0';
			$artistWidth = $this->getTextWidth('Illus. ' . $card->artist, $this->font('artist', 'centerX:false'));
			$this->drawText($canvas, 55 + $artistWidth + 5, 1020, null, $card->copyright, $this->font('copyright', 'centerX:false color:' . $copyrightColor));
		}
		else if ($card->copyright) {
			$copyrightColor = '255,255,255';
			if ($card->color == 'W') $copyrightColor = '0,0,0';
			if (in_array($card->set, $greyTextSets) && $settings['card.prerevised.grey.text'] != FALSE) $copyrightColor = $artistColor . ' shadow:true';
			$this->drawText($canvas, $settings['copyright.x'], $settings['copyright.y'], null, $card->copyright, $this->font('copyright', 'color:' . $copyrightColor));
		}

		echo "\n";
		return $canvas;
	}

	public function getSettings () {
		global $rendererSettings;
		return $rendererSettings['config/config-preEighth.txt'];
	}
	
	private function getIndicator ($title) {
		if (!PreEighthRenderer::$titleToIndicator) PreEighthRenderer::$titleToIndicator = csvToArray('data/eighth/titleToIndicator.csv');
		return @PreEighthRenderer::$titleToIndicator[(string)strtolower($title)];
	}

	private function getTombstone ($title) {
		if (!PreEighthRenderer::$titleToTombstone) PreEighthRenderer::$titleToTombstone = csvToArray('data/preEighth/titleToTombstone.csv');
		return array_key_exists((string)strtolower($title), PreEighthRenderer::$titleToTombstone);
	}
}

?>
