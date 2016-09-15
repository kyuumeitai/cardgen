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
class EighthRenderer extends CardRenderer {
	static private $settingSections;
	static private $titleToGuild;
	static private $titleToFuse;
	static private $titleToPhyrexia;
	static private $titleToClan;
	static private $titleToQuest;
	static private $titleToGodStars;
	static private $titleToFrameDir;
	static private $titleToTransform;
	static private $titleToIndicator;
	static private $titleToTombstone;
	static private $titleToPromoSetText;
	static private $titleToPromoCardText;

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
		$frameDir = $this->getFrameDir($card->title, $card->set, $settings);
		$costColors = Card::getCostColors($card->cost);

		$useMulticolorFrame = (strlen($costColors) > 1 && strpos($settings['card.multicolor.frames'], strval(strlen($costColors))) !== false) || ($card->isDualManaCost() && (strpos($settings['card.multicolor.frames'], strval(strlen($costColors))) !== false || strlen($costColors) == 2));
		switch ($frameDir) {
		case "timeshifted": $useMulticolorFrame = false; break;
		case "transform-day": $useMulticolorFrame = false; $pts = explode("|", $card->pt); $card->pt = $pts[0]; break;
		case "transform-night": $useMulticolorFrame = false; break;
		case "fuse-left": $useMulticolorFrame = true; break;
		case "fuse-right": $useMulticolorFrame = true; break;
		case "mgdpromo": $useMulticolorFrame = true; break;
		case "mgdnotypepromo": $useMulticolorFrame = true; break;
		}

		$canvas = imagecreatetruecolor(736, 1050);

		// Art image.
		if ($config['art.use.xlhq.full.card'] != false && stripos($card->artFileName,'xlhq') != false) {
			$this->drawArt($canvas, $card->artFileName, $settings['art.xlhq.top'], $settings['art.xlhq.left'], $settings['art.xlhq.bottom'], $settings['art.xlhq.right'], !$config['art.keep.aspect.ratio']);
		}
		else if(($card->isEldrazi() && $card->isArtefact()) || ($card->isDevoid() && $card->isArtefact()) || (($frameDir == 'fullartbasicland') && $card->isBasicLand()) || ($card->set == "EXP" && $card->isLand()) || ($card->set == 'FUT' && $card->title == 'Ghostfire'))
			$this->drawArt($canvas, $card->artFileName, 0, 0, 1050, 736, !$config['art.keep.aspect.ratio']);
		else
			$this->drawArt($canvas, $card->artFileName, $settings['art.top'], $settings['art.left'], $settings['art.bottom'], $settings['art.right'], !$config['art.keep.aspect.ratio']);

		echo '.';
		$promoset = explode(',',$config['card.promo.symbols']);

		// Background image, border image, and grey title/type image.
		$borderImage = null;
		$greyTitleAndTypeOverlay = null;
		if ($card->isLand()) {
			// Land frame.
			$landColors = @$this->writer->titleToLandColors[strtolower($card->title)];
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
			switch (strtolower($card->title)) {
				case 'arid mesa': $landColors = 'RW'; break;
				case 'marsh flats': $landColors = 'WB'; break;
				case 'misty rainforest': $landColors = 'GU'; break;
				case 'scalding tarn': $landColors = 'UR'; break;
				case 'verdant catacombs': $landColors = 'BG'; break;
				}
			if (!$landColors) error('Land color missing for card: ' . $card->title);
			if (strlen($landColors) > 1) {
				$useMulticolorLandFrame = strpos($settings['card.multicolor.land.frames'], strval(strlen($landColors))) !== false;
				if (!$useMulticolorLandFrame) $landColors = 'A';
			}
			if (($frameDir == 'fullartbasicland') && $settings['card.fullart.land.frames'] != false && $card->isBasicLand()) {
				if ($card->set == 'JGC') $bgImage = @imagecreatefrompng("images/eighth/$frameDir/land/ZEN" . '_' . $landColors . '.png');
				else $bgImage = @imagecreatefrompng("images/eighth/$frameDir/land/$card->set" . '_' . $landColors . '.png');
			}
			else if ($card->title == 'Paliano, the High City' && $card->set == 'CNS') {
				$bgImage = @imagecreatefrompng("images/eighth/$frameDir/land/Conspiracy$landColors.png");
			}
			else {
				$bgImage = @imagecreatefrompng("images/eighth/$frameDir/land/$landColors.png");
			}
			if (!$bgImage) error("Background image not found for land color \"$landColors\": " . $card->title);
			// Grey title/type image.
			if (strlen($landColors) >= 2 && $settings['card.multicolor.grey.title.and.type']) {
				$greyTitleAndTypeOverlay = @imagecreatefrompng("images/eighth/$frameDir/cards/C-overlay.png");
				if (!$greyTitleAndTypeOverlay) error('Image not found: C-overlay.png');
			}
			// Green title/type image
			if (strtolower($card->title) == 'murmuring bosk') {
				$greyTitleAndTypeOverlay = @imagecreatefrompng("images/eighth/$frameDir/cards/G-overlay.png");
				if (!$greyTitleAndTypeOverlay) error('Image not found: G-overlay.png');
			}
		} else {
			if(($card->isEldrazi() && $card->isArtefact())||($card->set == 'FUT' && $card->title == 'Ghostfire')) {
				$bgImage = @imagecreatefrompng("images/eighth/$frameDir/cards/Eldrazi.png");
			} else if($card->isConspiracy() && $card->isArtefact()) {
				$bgImage = @imagecreatefrompng("images/eighth/$frameDir/cards/Conspiracy.png");
			} else if($card->isArtefact() && stripos($card->legal, 'draft') !== false) {
				$bgImage = @imagecreatefrompng("images/eighth/$frameDir/cards/ConspiracyArt.png");
			} else if ($card->isArtefact() && $frameDir != 'futureshiftedcreature') {
				$bgImage = @imagecreatefrompng("images/eighth/$frameDir/cards/Art.png");
				if(strpos($settings['card.artifact.color'], strval(strlen($costColors))) !== false){
					if (strlen($costColors) >= 2) {
						$useMulticolorFrame = false;
						$borderImage = @imagecreatefrompng("images/eighth/$frameDir/borders/$costColors.png");
					}
					else {
						$useMulticolorFrame = false;
						$borderImage = @imagecreatefrompng("images/eighth/$frameDir/borders/$card->color.png");
					}
				} else if (strlen($costColors) >= 2 || $card->color == 'Gld') {
					$useMulticolorFrame = false;
					$borderImage = @imagecreatefrompng("images/eighth/$frameDir/borders/Gld.png");
				}
			} else if ($card->isArtefact() && $frameDir == 'futureshiftedcreature' && strpos($settings['card.artifact.color'], strval(strlen($costColors))) !== false) {
				$bgImage = @imagecreatefrompng("images/eighth/$frameDir/cards/Art$card->color.png");
			} else if ($useMulticolorFrame) {
				// Multicolor frame.
				if ($frameDir == 'mgdnotypepromo' && strlen($costColors) >= 3) $costColors = 'Gld';
				$bgImage = @imagecreatefrompng("images/eighth/$frameDir/cards/$costColors.png");
				if (!$bgImage) $bgImage = @imagecreatefrompng("images/eighth/$frameDir/cards/Gld$costColors.png");
				if (!$bgImage) error("Background image not found for color: $costColors");
				// Grey title/type image.
				if ($settings['card.multicolor.grey.title.and.type']) {
					$greyTitleAndTypeOverlay = @imagecreatefrompng("images/eighth/$frameDir/cards/C-overlay.png");
					if (!$greyTitleAndTypeOverlay) error('Image not found: C-overlay.png');
				}
			} else {
				// Mono color frame.
				$bgImage = @imagecreatefrompng("images/eighth/$frameDir/cards/" . $card->color . '.png');
				if (!$bgImage) error('Background image not found for color "' . $card->color . '" in frame dir: ' . $frameDir);
				// Border image.
				if (strlen($costColors) == 2 && $settings['card.multicolor.dual.borders'] && $frameDir != 'mgdpromo'&& $frameDir != 'mgdnotypepromo')
					$borderImage = @imagecreatefrompng("images/eighth/$frameDir/borders/$costColors.png");
			}
		}
		imagecopy($canvas, $bgImage, 0, 0, 0, 0, 736, 1050);
		imagedestroy($bgImage);
		if ($borderImage) {
			imagecopy($canvas, $borderImage, 0, 0, 0, 0, 736, 1050);
			imagedestroy($borderImage);
		}
		if ($greyTitleAndTypeOverlay) {
			imagecopy($canvas, $greyTitleAndTypeOverlay, 0, 0, 0, 0, 736, 1050);
			imagedestroy($greyTitleAndTypeOverlay);
		}
		
		// God Stars Frame overlay
		/*if($card->set == "THS"||$card->set == "BNG"||$card->set == "JOU"||$card->set == "C15"||in_array($card->set,$promoset)) {
			if ($GodStars = $this->getGodStars($card->title)) {
				list($image) = getPNG("images/eighth/gods/$GodStars.png", "GodStars image not found for: $GodStars");
				imagecopy($canvas, $image, 0, 0 , 0, 0, 736, 1050);
				imagedestroy($image);
			}
		}*/
		
		$whiteBorderSets = explode(',', $settings['card.white.border.sets']);
		if ($settings['card.white.border.core.sets'] != FALSE && in_array($card->set, $whiteBorderSets) != FALSE) {
			$whiteBorder = @imagecreatefrompng('images/eighth/whiteBorder.png');
			if (!$whiteBorder) error('Image not found for White Border');
			imagecopy($canvas, $whiteBorder, 0, 0, 0, 0, 736, 1050);
			imagedestroy($whiteBorder);
		}

		// Power / toughness.
		if ($card->pt) {
			if($card->isEldrazi() && $card->isArtefact()) {
				$image = @imagecreatefrompng("images/eighth/$frameDir/pt/B.png");
			} else if ($useMulticolorFrame) {
				$image = @imagecreatefrompng("images/eighth/$frameDir/pt/" . substr($costColors, -1, 1) . '.png');
				if (!$image) $image = @imagecreatefrompng("images/eighth/$frameDir/pt/" . $card->color . '.png');
			} else if (($frameDir === 'transform-day' || $frameDir === 'transform-night' || $frameDir == 'transform-spark' || $frameDir == 'transform-moon' || $frameDir == 'transform-eldrazi') && strlen($card->color) >= 2 && $card->color != 'Art') {
				$image = @imagecreatefrompng("images/m15/$frameDir/pt/Gld.png");
			} else
				$image = @imagecreatefrompng("images/eighth/$frameDir/pt/" . $card->color . '.png');
			if ($frameDir == 'futureshifted' || $frameDir == 'futureshiftedtextless' || $frameDir == 'futureshiftedcreature') {
				if (preg_match('/([UBRG])/', $card->color))
					$textColor = '255,255,255';
				if (preg_match('/([W])/', $card->color))
					$textColor = '0,0,0';
				$this->drawText($canvas, $settings['pt.center.x'], $settings['pt.center.y'], $settings['pt.width'], $card->pt, $this->font('pt', 'color:' . $textColor));
			} else {
				if (!$image) error("Power/toughness image not found for color: $card->color");
				imagecopy($canvas, $image, 0, 1050 - 162, 0, 0, 736, 162);
				imagedestroy($image);
				
				$this->drawText($canvas, $settings['pt.center.x'], $settings['pt.center.y'], $settings['pt.width'], $card->pt, $this->font('pt'));
			}
		}

		//Transform P/T
		if($frameDir == "transform-day" && isset($pts[1])) 
			$this->drawText($canvas, $settings['pt.transform.center.x'], $settings['pt.transform.center.y'], $settings['pt.transform.width'], $pts[1], $this->font('pt.transform'));

		// Casting cost.
		if ($frameDir == 'futureshifted'||$frameDir == 'futureshiftedcreature'||$frameDir == 'futureshiftedtextless') {
			$costLeft = 688;
			$costSymbols = $card->getCostSymbols();
			$symbols = array();
			if ($card->color == 'Art'||$card->color == 'C') $symbolColor = 'C';
			else $symbolColor = $card->color;
			foreach ($costSymbols as $symbol) {
				if (preg_match('/([WUBRGS])/', $symbol)) $symbol = $symbol . '_fut';
				else $symbol = $symbolColor . $symbol . '_fut';
				$symbols[] = $symbol;
			}
			if (!empty($symbols[0])) $this->drawSymbol($canvas, $settings['cost.symbol.one.x'],$settings['cost.symbol.one.y'],$settings['cost.size'],$symbols[0],true);
			if (!empty($symbols[1])) $this->drawSymbol($canvas, $settings['cost.symbol.two.x'],$settings['cost.symbol.two.y'],$settings['cost.size'],$symbols[1],true);
			if (!empty($symbols[2])) $this->drawSymbol($canvas, $settings['cost.symbol.three.x'],$settings['cost.symbol.three.y'],$settings['cost.size'],$symbols[2],true);
			if (!empty($symbols[3])) $this->drawSymbol($canvas, $settings['cost.symbol.four.x'],$settings['cost.symbol.four.y'],$settings['cost.size'],$symbols[3],true);
			if (!empty($symbols[4])) $this->drawSymbol($canvas, $settings['cost.symbol.five.x'],$settings['cost.symbol.five.y'],$settings['cost.size'],$symbols[4],true);
		} else {
			$costLeft = $this->drawCastingCost($canvas, $card->getCostSymbols(), $card->isDualManaCost() ? $settings['cost.top.dual'] : $settings['cost.top'], $settings['cost.right'], $settings['cost.size'], true);
		}	

		echo '.';

		// Set and rarity.
		if (($card->isBasicLand()) && ($card->set == "UNH" || $card->set == "UGL")) {
			$rarityLeft = $this->drawRarity($canvas, $card->getDisplayRarity(), $card->getDisplaySet(), $settings['rarity.' . strtolower($card->set) . '.right'], $settings['rarity.center.' . strtolower($card->set) . '.y'], $settings['rarity.height'], $settings['rarity.width'], false);
			}
		else if (($card->isBasicLand()) && ($card->set == "JGC")) {
			$rarityLeft = $this->drawRarity($canvas, $card->getDisplayRarity(), $card->getDisplaySet(), $settings['rarity.right'], $settings['rarity.center.y'], $settings['rarity.height'], $settings['rarity.width'] / 2, false);
			}
		else if ($card->isConspiracy()) {
			$rarityLeft = $this->drawRarity($canvas, $card->getDisplayRarity(), $card->getDisplaySet(), $settings['rarity.right'] - 23, $settings['rarity.center.y'], $settings['rarity.height'], $settings['rarity.width'], false);
			}
		else if ((!$card->isBasicLand() || $settings['card.basic.land.set.symbols']) && $frameDir != 'mgdnotypepromo') {
			$rarityLeft = $this->drawRarity($canvas, $card->getDisplayRarity(), $card->getDisplaySet(), $settings['rarity.right'], $settings['rarity.center.y'], $settings['rarity.height'], $settings['rarity.width'], false);
			}
		//if (explode(',', $config['card.promo.symbols']);
		else
			$rarityLeft = $settings['rarity.right'];

		// Tombstone sign.
		if ($settings['card.tombstone'] && in_array($card->set,$promoset)) {
			if ($this->getTombstone($card->title)) {
				list($image, $width, $height) = getPNG('images/preEighth/tombstone.png', 'Tombstone image not found.');
				$settings['title.x'] = $settings['title.x'] + $width + 10;
				imagecopy($canvas, $image, $settings["tombstone.left"], $settings["tombstone.top"], 0, 0, $width, $height);
				imagedestroy($image);
			}
		}
		
		// Card title.
		if (($card->set == "UNH") && $card->isBasicLand() != FALSE) {
			$this->drawText($canvas, $settings['title.center.' . strtolower($card->set) . '.x'], $settings['title.center.' . strtolower($card->set) . '.y'], 186 /*$costLeft - $settings['title.center.' . strtolower($card->set) . '.x']*/, $card->getDisplayTitle(), $this->font("title$languageFont", 'centerY:true centerX:true'));
		} else if ($card->isConspiracy()) {
			$this->drawText($canvas, $settings['title.x'] + 4, $settings['title.y'] + 4, $costLeft - $settings['title.x'], $card->getDisplayTitle(), $this->font("title$languageFont"));
		} else if (($frameDir == 'futureshifted' || $frameDir == 'futureshiftedtextless' || $frameDir == 'futureshiftedcreature') && preg_match('/([UBRG])/', $card->color)) {
				$textColor = '255,255,255';
				$this->drawText($canvas, $settings['title.x'], $settings['title.y'], $costLeft - $settings['title.x'], $card->getDisplayTitle(), $this->font("title$languageFont", 'color:' . $textColor));
		} else if ($card->set == 'CD1' && $card->isCreature()) {
			$textWidth = $this->getTextWidth($card->getDisplayTitle(), $this->font("title$languageFont"));
			list($image, $width, $height) = getPNG("images/symbols/tokenborder/challenge1center.png", "Image not found for: center");
			$imgWidth = $textWidth;
			if ($imgWidth > 667) $imgWidth = 667;
			$imgWidthFull = $imgWidth + 40;
			$imgHeight = $height;
			$dst_img = imagecreatetruecolor($imgWidth, $imgHeight);
			imagesavealpha($dst_img, true);
			$trans_colour = imagecolorallocatealpha($dst_img, 0, 0, 0, 127);
			imagefill($dst_img, 0, 0, $trans_colour);
			imagecopyresampled($dst_img,$image,0,0,0,0,$imgWidth,$imgHeight,$width,$height);
			imagedestroy($image);
			$im = imagecreatetruecolor($imgWidthFull, $imgHeight);
			imagesavealpha($im, true);
			$trans_colour = imagecolorallocatealpha($im, 0, 0, 0, 127);
			imagefill($im, 0, 0, $trans_colour);
			imagecopy($im,$dst_img,ceil(($imgWidthFull / 2) - (($imgWidth) / 2)),0,0,0,$imgWidth,$imgHeight);
			imagedestroy($dst_img);
			list($image, $width, $height) = getPNG("images/symbols/tokenborder/challenge1left.png", "Image not found for: left");
			imagecopy($im,$image,5,0,0,0,$width,$height);
			imagedestroy($image);
			list($image, $width, $height) = getPNG("images/symbols/tokenborder/challenge1right.png", "Image not found for: right");
			imagecopy($im,$image,ceil($imgWidthFull - $width - 5),0,0,0,$width,$height);
			imagedestroy($image);
			
			imagecopy($canvas, $im, $settings['title.center.x'] - ($imgWidthFull / 2), $settings["title.y"] - ($imgHeight / 1.5), 0, 0, $imgWidthFull, $imgHeight);
			imagedestroy($im);
			
			$this->drawText($canvas, $settings['title.center.x'], $settings['title.y'], $costLeft - $settings['title.center.x'], $card->getDisplayTitle(), $this->font("title$languageFont", 'color:161,178,174'));
		} else if (($card->set == 'CD2' || $card->set == 'CD3') && $card->isCreature()) {
			$textWidth = $this->getTextWidth($card->getDisplayTitle(), $this->font("title$languageFont"));
			list($image, $width, $height) = getPNG("images/symbols/tokenborder/challenge3center.png", "Image not found for: center");
			$imgWidth = $textWidth;
			if ($imgWidth > 667) $imgWidth = 667;
			$imgWidthFull = $imgWidth + 40;
			$imgHeight = $height;
			$dst_img = imagecreatetruecolor($imgWidth, $imgHeight);
			imagesavealpha($dst_img, true);
			$trans_colour = imagecolorallocatealpha($dst_img, 0, 0, 0, 127);
			imagefill($dst_img, 0, 0, $trans_colour);
			imagecopyresampled($dst_img,$image,0,0,0,0,$imgWidth,$imgHeight,$width,$height);
			imagedestroy($image);
			$im = imagecreatetruecolor($imgWidthFull, $imgHeight);
			imagesavealpha($im, true);
			$trans_colour = imagecolorallocatealpha($im, 0, 0, 0, 127);
			imagefill($im, 0, 0, $trans_colour);
			imagecopy($im,$dst_img,ceil(($imgWidthFull / 2) - (($imgWidth) / 2)),0,0,0,$imgWidth,$imgHeight);
			imagedestroy($dst_img);
			list($image, $width, $height) = getPNG("images/symbols/tokenborder/challenge3left.png", "Image not found for: left");
			imagecopy($im,$image,5,0,0,0,$width,$height);
			imagedestroy($image);
			list($image, $width, $height) = getPNG("images/symbols/tokenborder/challenge3right.png", "Image not found for: right");
			imagecopy($im,$image,ceil($imgWidthFull - $width - 5),0,0,0,$width,$height);
			imagedestroy($image);
			
			imagecopy($canvas, $im, $settings['title.center.x'] - ($imgWidthFull / 2), $settings["title.y"] - ($imgHeight / 1.5), 0, 0, $imgWidthFull, $imgHeight);
			imagedestroy($im);
			
			$this->drawText($canvas, $settings['title.center.x'], $settings['title.y'], $costLeft - $settings['title.x'], $card->getDisplayTitle(), $this->font("title$languageFont", 'color:254,242,0'));
		} else {
			$this->drawText($canvas, $settings['title.x'], $settings['title.y'], $costLeft - $settings['title.x'], $card->getDisplayTitle(), $this->font("title$languageFont"));
		}

		echo '.';

		// Type.
		if($indicator = $this->getIndicator($card->title)) {
			$image = @imagecreatefrompng("images/symbols/indicators/" . $indicator . '.png');
			imagecopyresampled($canvas, $image, $settings['indicator.x'], $settings['indicator.y'], 0, 0, $settings['indicator.size'], $settings['indicator.size'], 300, 300);
			imagedestroy($image);
			$typex = $settings['type.indicator.x'];
			if ($frameDir == 'futureshifted'||$frameDir == 'futureshiftedcreature'||$frameDir == 'futureshiftedtextless') {
				$types = explode(' ', $card->englishType);
				if ($card->color == 'W' || $card->isArtefact() && ($card->color == 'W' || $card->color == 'Art') || $card->color == 'Lnd' && strlen($landColors) >= 2 && strpos($landColors,'W') == 0 || $card->color == 'Lnd' && $landColors == 'C') {
					$typeColor = '0,0,0';
					$textColor = '0,0,0';
				} else if ($card->isArtefact() && $card->color != 'W') {
					$typeColor = '0,0,0';
					$textColor = '255,255,255';

				} else {
					$typeColor = '255,255,255';
					$textColor = '255,255,255';
				}
				if ((($types[0] == 'Land' || $types[0] == 'Artifact') && $types[1] == 'Creature') || ($types[0] == 'Enchantment' && $types[1] == 'Creature')) {
					$this->drawSymbol($canvas,$settings['type.symbol.y'],$settings['type.symbol.x'],$settings['type.symbol.size'],'Multiple',false,false,$typeColor);
				} else if ($types[0] == 'Legendary' || $types[0] == 'Tribal' || $types[0] == 'Snow') {
					$this->drawSymbol($canvas,$settings['type.symbol.y'],$settings['type.symbol.x'],$settings['type.symbol.size'],"$types[1]",false,false,$typeColor);
				} else {
					$this->drawSymbol($canvas,$settings['type.symbol.y'],$settings['type.symbol.x'],$settings['type.symbol.size'],"$types[0]",false,false,$typeColor);
				}
				$this->drawText($canvas, $typex, $settings['type.y'], $rarityLeft - $typex, $card->type, $this->font("type$languageFont", 'color:' . $textColor));
			} elseif ($frameDir == 'mpr') {
			
			} else {
				$this->drawText($canvas, $typex, $settings['type.y'], $rarityLeft - $typex, $card->type, $this->font("type$languageFont"));
			}
		}
		else if (($card->set == "UNH" || $card->set == "UGL") && $card->isBasicLand() && $settings['card.fullart.land.frames'] !== FALSE) {/*
			$this->drawText($canvas, $typex, $settings['type.y'], $rarityLeft - $settings['type.x'], $card->type, $this->font('type'));*/
			}
		else if (($card->set == 'ZEN' || $card->set == 'JGC') && $card->isBasicLand() && $settings['card.fullart.land.frames'] !== FALSE && $frameDir == 'fullartbasicland') {
			$typex = $settings['type.x'];
			$type2x = $settings['type2.x'];
			$typearray = preg_split('/([—:～])/u', $card->type);
			$type1 = $typearray[0];
			$type2 = @$typearray[1];
			$this->drawText($canvas, $typex, $settings['type.y'], $settings['type2.x'] -  $settings['type.x'], $type1, $this->font("type$languageFont"));
			$this->drawText($canvas, $type2x, $settings['type2.y'], $rarityLeft -  $settings['type2.x'], $type2, $this->font("type$languageFont"));
		} elseif ($frameDir == 'mpr') {
			
		}
		else {
			$typex = ($frameDir == "transform-night" && $card->isArtefact()) ? $settings['type.art.x'] : $settings['type.x'];
			if ($frameDir == 'futureshifted'||$frameDir == 'futureshiftedcreature'||$frameDir == 'futureshiftedtextless') {
				$types = explode(' ', $card->englishType);
				if ($card->color == 'W' || $card->isArtefact() && ($card->color == 'W' || $card->color == 'Art') || $card->color == 'Lnd' && strlen($landColors) >= 2 && strpos($landColors,'W') == 0 || $card->color == 'Lnd' && $landColors == 'C' || $card->title == 'Ghostfire') {
					$typeColor = '0,0,0';
					$textColor = '0,0,0';
				} else if ($card->isArtefact() && $card->color != 'W') {
					$typeColor = '0,0,0';
					$textColor = '255,255,255';

				} else if ($card->color == 'Lnd' && strlen($landColors) >= 2) {
					$typeColor = '255,255,255';
					$textColor = '0,0,0';
				} else {
					$typeColor = '255,255,255';
					$textColor = '255,255,255';
				}
				if ((($types[0] == 'Land' || $types[0] == 'Artifact') && @$types[1] == 'Creature') || ($types[0] == 'Enchantment' && @$types[1] == 'Creature')) {
					$this->drawSymbol($canvas,$settings['type.symbol.y'],$settings['type.symbol.x'],$settings['type.symbol.size'],'Multiple',false,false,$typeColor);
				} else if ($types[0] == 'Legendary' || $types[0] == 'Tribal' || $types[0] == 'Snow') {
					$this->drawSymbol($canvas,$settings['type.symbol.y'],$settings['type.symbol.x'],$settings['type.symbol.size'],"$types[1]",false,false,$typeColor);
				} else {
					$this->drawSymbol($canvas,$settings['type.symbol.y'],$settings['type.symbol.x'],$settings['type.symbol.size'],"$types[0]",false,false,$typeColor);
				}
				$this->drawText($canvas, $typex, $settings['type.y'], $rarityLeft - $settings['type.x'], $card->type, $this->font("type$languageFont", 'color:' . $textColor));
			} elseif ($frameDir == 'mpr') {
			
			} else {
				$this->drawText($canvas, $typex, $settings['type.y'], $rarityLeft - $settings['type.x'], $card->type, $this->font("type$languageFont"));
			}
		}

		// Guild sign.
		if ($card->set == "DIS"||$card->set == "GPT"||$card->set == "RAV"||$card->set == "RTR"||$card->set == "GTC"||$card->set == "DGM"||in_array($card->set,$promoset) && ($frameDir != 'mgdpromo'||$frameDir != 'mpr'||$frameDir != 'mgdnotypepromo') && $card->getDisplaySet() != 'IDW') {
			if ($guild = $this->getGuild($card->title)) {
				list($image, $width, $height) = getPNG("images/watermarks/guilds/$guild.png", "Guild image not found for: $guild");
				if ($titleToFuse = $this->getFuse($card->title)) {
					// Set the width of the area and height of the area
					$inputwidth = 646;
					$inputheight = 230;
					// Set the position we want the new image
					$position = 654;
				}
				else {
					// Set the width of the area and height of the area
					$inputwidth = 646;
					$inputheight = 280;
					// Set the position we want the new image
					$position = 672;
				}
				// So then if the image is wider rather than taller, set the width and figure out the height
				if (($width/$height) > ($inputwidth/$inputheight)) {
					$outputwidth = $inputwidth;
					$outputheight = ($inputwidth * $height)/ $width;
				}
				// And if the image is taller rather than wider, then set the height and figure out the width
				elseif (($width/$height) < ($inputwidth/$inputheight)) {
					$outputwidth = ($inputheight * $width)/ $height;
					$outputheight = $inputheight;
				}
				// And because it is entirely possible that the image could be the exact same size/aspect ratio of the desired area, so we have that covered as well
				elseif (($width/$height) == ($inputwidth/$inputheight)) {
					$outputwidth = $inputwidth;
					$outputheight = $inputheight;
				}
				$dst_img = ImageCreate($outputwidth,$outputheight);
				imagecopyresampled($dst_img,$image,0,0,0,0,$outputwidth,$outputheight,$width,$height);
				imagedestroy($image);
				imagecopy($canvas, $dst_img, 373 - ($outputwidth / 2), $position, 0, 0, $outputwidth, $outputheight);
				imagecopy($canvas, $dst_img, 373 - ($outputwidth / 2), $position, 0, 0, $outputwidth, $outputheight);// Too much alpha, need to apply twice.
				imagedestroy($dst_img);	
			}
		}
		
		// Clan sign.
		if($card->set == "KTK"||$card->set == "FRF"||$card->set == "DTK"||in_array($card->set,$promoset) && ($frameDir != 'mgdpromo'||$frameDir != 'mpr'||$frameDir != 'mgdnotypepromo') && $card->getDisplaySet() != 'IDW') {
			if ($clan = $this->getClan($card->title)) {
				list($image, $width, $height) = getPNG("images/watermarks/clans/$clan.png", "Clan image not found for: $clan");
				imagecopy($canvas, $image, 373 - ($width / 2), 652, 0, 0, $width, $height);
				imagecopy($canvas, $image, 373 - ($width / 2), 652, 0, 0, $width, $height); // Too much alpha, need to apply twice.
				imagedestroy($image);
			}
		}
		
		// Quest sign.
		if($card->set == "THS"||$card->set == "BNG"||$card->set == "JOU"||$card->set == "HRO"||in_array($card->set,$promoset) && $frameDir != 'mpr') {
			if ($quest = $this->getQuest($card->title)) {
				list($image, $width, $height) = getPNG("images/watermarks/quests/$quest.png", "Quest image not found for: $quest");
				imagecopy($canvas, $image, 373 - ($width / 2), 652, 0, 0, $width, $height);
				imagecopy($canvas, $image, 373 - ($width / 2), 652, 0, 0, $width, $height); // Too much alpha, need to apply twice.
				imagedestroy($image);
			}
		}

		// Phyrexia/Mirran sign.
		if($card->set == "SOM"||$card->set == "MBS"||$card->set == "NPH"||in_array($card->set,$promoset) && ($frameDir != 'mgdpromo'||$frameDir != 'mpr'||$frameDir != 'mgdnotypepromo') && $card->getDisplaySet() != 'IDW') {
			if ($phyrexia = $this->getPhyrexia($card->title)) {
				list($image, $width, $height) = getPNG("images/watermarks/phyrexia/$phyrexia.png", "Phyrexia/Mirran image not found for: $phyrexia");
				imagecopy($canvas, $image, 373 - ($width / 2), 652, 0, 0, $width, $height);
				imagecopy($canvas, $image, 373 - ($width / 2), 652, 0, 0, $width, $height); // Too much alpha, need to apply twice.
				imagedestroy($image);
			}
		}
		
		// Promo overlay
		if ($card->promo && !$card->isBasicLand() && !$frameDir == 'mpr') {
			list($image, $width, $height) = getPNG('images/promo/' . $card->promo . '.png', 'Promo overlay image not found for: ' . $card->promo);
			imagecopy($canvas, $image, 359 - ($width / 2), 680, 0, 0, $width, $height);
			imagedestroy($image);
		}

		if ($card->isBasicLand() || ($card->isLand() && $card->legal == '' && $card->flavor == '') || $card->isLand() && $card->title == 'Dryad Arbor' && $card->set == 'V12') {
			// Basic land symbol instead of legal text.
			list($image, $width, $height) = getPNG("images/symbols/land/$landColors.png", "Basic land image not found for: images/symbols/land/$landColors.png");
			if ($card->set == "UNH" || $card->set == "UG"){
			}
			else if ($frameDir == 'fullartbasicland' && $settings['card.fullart.land.frames'] != FALSE) {
				// Set the width of the area and height of the area
				$inputwidth = 130;
				$inputheight = 120;
				// So then if the image is wider rather than taller, set the width and figure out the height
				if (($width/$height) > ($inputwidth/$inputheight)) {
					$outputwidth = $inputwidth;
					$outputheight = ($inputwidth * $height)/ $width;
				}
				// And if the image is taller rather than wider, then set the height and figure out the width
				elseif (($width/$height) < ($inputwidth/$inputheight)) {
					$outputwidth = ($inputheight * $width)/ $height;
					$outputheight = $inputheight;
				}
				// And because it is entirely possible that the image could be the exact same size/aspect ratio of the desired area, so we have that covered as well
				elseif (($width/$height) == ($inputwidth/$inputheight)) {
					$outputwidth = $inputwidth;
					$outputheight = $inputheight;
				}
				$dst_img = ImageCreate($outputwidth,$outputheight);
				imagecopyresampled($dst_img,$image,0,0,0,0,$outputwidth,$outputheight,$width,$height);
				imagecopy($canvas, $dst_img, 371 - ($outputwidth / 2), 855, 0, 0, $outputwidth, $outputheight);
				imagedestroy($dst_img);
			}
			else {
				imagecopy($canvas, $image, 373 - ($width / 2), 660, 0, 0, $width, $height);
			}
			imagedestroy($image);
		} else if ($card->isLand() && strlen($landColors) == 2 && (!$card->legal || preg_match('/([\#]{0,1}[\((]\{T\}[ \::]{1,2}.*?\{[WUBRG]\}.*?\{[WUBRG]\}.*?[\.?][\))][\#]{0,1})(?!.)/su', $card->legal) && $settings['card.dual.land.symbols'] != FALSE)) {
			// Dual land symbol instead of legal text.
			if ($settings['card.dual.land.symbols'] == 1) {
				// Single hybrid symbol.
				list($image, $width, $height) = getPNG("images/symbols/land/$landColors.png", "Dual symbol image not found for: $landColors");
				imagecopy($canvas, $image, 373 - ($width / 2), 680, 0, 0, $width, $height);
				imagedestroy($image);
			} else if ($settings['card.dual.land.symbols'] == 2) {
				// One of each basic symbol.
				$landColor = substr($landColors, 0, 1);
				list($image, $width, $height) = getPNG("images/symbols/land/$landColor.png", 'Basic land image not found for: ' . $card->title);
				imagecopy($canvas, $image, 217 - ($width / 2), 660, 0, 0, $width, $height);
				imagedestroy($image);

				$landColor = substr($landColors, 1, 2);
				list($image, $width, $height) = getPNG("images/symbols/land/$landColor.png", 'Basic land image not found for: ' . $card->title);
				imagecopy($canvas, $image, 519 - ($width / 2), 660, 0, 0, $width, $height);
				imagedestroy($image);
			}
		} else if ($frameDir == 'mpr') {
		} else if (preg_match('/\{T\}[ \:]{1,2} (.*?) \{([WUBRGC])\}([A-Za-z ]*?)\.(?!.)/su', $card->legal, $matches) && $config['card.use.symbol.on.mox'] == true && $card->flavor == '') {
			list($image, $width, $height) = getPNG("images/symbols/land/$matches[2].png", "Basic land image not found for: images/symbols/land/$matches[2].png");
			imagecopy($canvas, $image, 373 - ($width / 2), 650, 0, 0, $width, $height);
			imagedestroy($image);
		} else {
			// Legal and flavor text.
			$legaltemp = str_replace('#', '', $card->legal);
			if((strlen($legaltemp) <= 40  && $card->set != 'HRO'|| preg_match('/([\#]{0,1}[\((]\{T\}[ \::]{1,2}.*?\{[WUBRG]\}.*?\{[WUBRG]\}.*?[\.?][\))][\#]{0,1})(?!.)/su', $card->legal)) && strpos($card->legal, "\n") === FALSE && $card->flavor == '') {
				$this->drawText($canvas, ($settings['text.right'] + $settings['text.left']) / 2, ($settings['text.top'] + $settings['text.bottom']) / 2, 632, $card->legal, $this->font("text$languageFont", 'centerY:true centerX:true'));
			} else {
				$heightAdjust = $card->pt ? $settings['text.pt.height.adjust'] : 0;
				$this->drawLegalAndFlavorText($canvas, $settings['text.top'], $settings['text.left'], $settings['text.bottom'], $settings['text.right'], $card->legal, $card->flavor, $this->font("text$languageFont"), $heightAdjust);
			}
		}

		// Artist and copyright.
		// The artist color is white if the frame behind it is black.
		$footerColor = '0,0,0';
		if ($card->isLand())
			$footerColor = '255,255,255';
		else if (($costColors == 'B' || $card->color == 'B') && !$card->isArtefact())
			$footerColor = '255,255,255';
		else if ($card->isEldrazi() && $card->isArtefact())
			$footerColor = '255,255,255';
		else if (($frameDir == 'futureshifted' || $frameDir == 'futureshiftedtextless' || $frameDir == 'futureshiftedcreature') && preg_match('/([UBRG])/', $card->color))
			$footerColor = '255,255,255';
		else if ($useMulticolorFrame && !$titleToFuse = $this->getFuse($card->title)) {
			// Only multicolor frames with a bottom left color of black should use a white footer.
			if ((strlen($costColors) <= 2 && substr($costColors, 0, 1) == 'B') || (strlen($costColors) >= 3 && substr($costColors, 2, 1) == 'B'))
				$footerColor = '255,255,255';
		}
		if ($card->artist) {
			if ($settings['card.artist.gears']) {
				$artistSymbol = '{gear}';
			} else {
				$artistSymbol = '{brush}';
			}
			if (($frameDir == 'futureshifted' || $frameDir == 'futureshiftedtextless' || $frameDir == 'futureshiftedcreature')) {
				$this->drawText($canvas, $settings['artist.x'], $settings['artist.y'], 500, $artistSymbol . $card->artist, $this->font('artist', 'alignRight:true color:' . $footerColor));
			}
			else {
				$this->drawText($canvas, $settings['artist.x'], $settings['artist.y'], null, $artistSymbol . $card->artist, $this->font('artist', 'color:' . $footerColor));
			}
		}
		
		if ($card->copyright){
			if ($frameDir == 'futureshifted' || $frameDir == 'futureshiftedtextless' || $frameDir == 'futureshiftedcreature') {
				$this->drawText($canvas, $settings['copyright.x'], $settings['copyright.y'], 500, $card->copyright, $this->font('copyright', 'alignRight:true color:' . $footerColor));
			} else if ((($promoSetText = $this->getPromoSetText($card->set))||($promoSetText = $this->getPromoSetText($card->getDisplaySet()))) && in_array($card->set, $promoset)) {
				$this->drawText($canvas, $settings['copyright.x'], $settings['copyright.y'], null, $card->copyright . ' ' . $promoSetText, $this->font('copyright', 'color:' . $footerColor));
			} else if (($promoCardText = $this->getPromoCardText($card->title)) && in_array($card->set, $promoset)){
				$this->drawText($canvas, $settings['copyright.x'], $settings['copyright.y'], null, $card->copyright . ' ' . $promoCardText, $this->font('copyright', 'color:' . $footerColor));
			} else {
				$this->drawText($canvas, $settings['copyright.x'], $settings['copyright.y'], null, $card->copyright, $this->font('copyright', 'color:' . $footerColor));
			}
		} 

		echo "\n";
		return $canvas;
	}

	public function getSettings () {
		global $rendererSettings, $rendererSections;
		$settings = $rendererSettings['config/config-eighth.txt'];
		$frameDir = $this->getFrameDir($this->card->title, $this->card->set, $settings);
		$settings = array_merge($settings, $rendererSections['config/config-eighth.txt']['fonts - ' . $frameDir]);
		$settings = array_merge($settings, $rendererSections['config/config-eighth.txt']['layout - ' . $frameDir]);
		return $settings;
	}

	private function getGuild ($title) {
		if (!EighthRenderer::$titleToGuild) EighthRenderer::$titleToGuild = csvToArray('data/eighth/titleToGuild.csv');
		return @EighthRenderer::$titleToGuild[(string)strtolower($title)];
	}
	
	private function getFuse ($title) {
		if(!EighthRenderer::$titleToFuse) EighthRenderer::$titleToFuse = csvToArray('data/eighth/titleToFuse.csv');
		return @EighthRenderer::$titleToFuse[(string)strtolower($title)];
	}
	
	private function getClan ($title) {
		if (!EighthRenderer::$titleToClan) EighthRenderer::$titleToClan = csvToArray('data/eighth/titleToClan.csv');
		return @EighthRenderer::$titleToClan[(string)strtolower($title)];
	}
	
	private function getQuest ($title) {
		if (!EighthRenderer::$titleToQuest) EighthRenderer::$titleToQuest = csvToArray('data/eighth/titleToQuest.csv');
		return @EighthRenderer::$titleToQuest[(string)strtolower($title)];
	}
	
	private function getGodStars ($title) {
		if (!EighthRenderer::$titleToGodStars) EighthRenderer::$titleToGodStars = csvToArray('data/eighth/titleToGodStars.csv');
		return @EighthRenderer::$titleToGodStars[(string)strtolower($title)];
	}

	private function getPhyrexia ($title) {
		if (!EighthRenderer::$titleToPhyrexia) EighthRenderer::$titleToPhyrexia = csvToArray('data/eighth/titleToPhyrexia.csv');
		return @EighthRenderer::$titleToPhyrexia[(string)strtolower($title)];
	}

	private function getIndicator ($title) {
		if (!EighthRenderer::$titleToIndicator) EighthRenderer::$titleToIndicator = csvToArray('data/eighth/titleToIndicator.csv');
		return @EighthRenderer::$titleToIndicator[(string)strtolower($title)];
	}
	
	private function getTombstone ($title) {
		if (!EighthRenderer::$titleToTombstone) EighthRenderer::$titleToTombstone = csvToArray('data/preEighth/titleToTombstone.csv');
		return array_key_exists((string)strtolower($title), EighthRenderer::$titleToTombstone);
	}
	
	private function getPromoSetText ($set) {
		if (!EighthRenderer::$titleToPromoSetText) EighthRenderer::$titleToPromoSetText = csvToArray('data/setToPromoText.csv');
		return @EighthRenderer::$titleToPromoSetText[(string)strtolower($set)];
	}
	
	private function getPromoCardText ($title) {
		if (!EighthRenderer::$titleToPromoCardText) EighthRenderer::$titleToPromoCardText = csvToArray('data/titleToPromoText.csv');
		return @EighthRenderer::$titleToPromoCardText[(string)strtolower($title)];
	}
	
	private function getFrameDir ($title, $set, $settings) {
		if (!EighthRenderer::$titleToFrameDir) EighthRenderer::$titleToFrameDir = csvToArray('data/eighth/titleToAlternateFrame.csv');
		if (!EighthRenderer::$titleToTransform) EighthRenderer::$titleToTransform = csvToArray('data/titleToTransform.csv');
		$frameDir = @EighthRenderer::$titleToFrameDir[(string)strtolower($title)];
		if (!$frameDir) $frameDir = 'transform-' . @EighthRenderer::$titleToTransform[(string)strtolower($title)];
		$timeshifted = explode(',', $settings['card.timeshifted.frames']);
		$futureshifted = explode(',', $settings['card.futureshifted.frames']);
		$fuse = explode(',',$settings['card.split.fuse.set']);
		$nyxstars = explode(',',$settings['card.nyxstars.set']);
		$fullartbasic = explode(',', $settings['card.fullart.basic.land.frames.set']);
		$expedition = explode(',', $settings['card.fullart.expedition.frames']);
		$gameday = array('MGD', 'CHP');
		$mpr = array('MPR');
		if (!$frameDir || $frameDir == 'transform-') $frameDir = "regular";
		if ($frameDir == 'fullartbasicland' && in_array($set, $fullartbasic) === FALSE) $frameDir = "regular";
		if ($frameDir == 'fullartbasicland' && ($set == 'OGW' && $this->card->pic >= 3||$set == 'BFZ' && $this->card->pic >= 6||$set == 'ZEN' && $this->card->pic >= 5)) $frameDir = "regular";
		if ($frameDir == 'expedition' && in_array($set, $expedition) === FALSE) $frameDir = "regular";
		if ($frameDir == 'timeshifted' && in_array($set, $timeshifted) === FALSE) $frameDir = "regular";
		if ($frameDir == 'futureshifted' && in_array($set, $futureshifted) === FALSE) $frameDir = "regular";
		if ($frameDir == 'futureshiftedcreature' && in_array($set, $futureshifted) === FALSE) $frameDir = "regular";
		if ($frameDir == 'futureshiftedtextless' && in_array($set, $futureshifted) === FALSE) $frameDir = "regular";
		if ($frameDir == 'fuse-' && in_array($set, $fuse) === FALSE) $frameDir = "regular";
		if ($frameDir == 'nyxstars' && in_array($set, $nyxstars) === FALSE) $frameDir = "regular";
		if ($frameDir == 'mgdpromo' && in_array($set, $gameday) === FALSE) $frameDir = "regular";
		if ($frameDir == 'mgdnotypepromo' && in_array($set, $gameday) === FALSE) $frameDir = "regular";
		if ($frameDir == 'mpr' && in_array($set, $mpr) === FALSE) $frameDir = "regular";
		return $frameDir;
	}
}

?>
