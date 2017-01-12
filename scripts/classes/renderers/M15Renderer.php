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
class M15Renderer extends CardRenderer {
	static private $settingSections;
	static private $titleToGuild;
	static private $titleToFuse;
	static private $titleToClan;
	static private $titleToQuest;
	static private $titleToGodStars;
	static private $titleToPhyrexia;
	static private $titleToFrameDir;
	static public $titleToTransform;
	static private $titleToIndicator;
	static private $titleToStorySpotlight;
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
		$frameDir = $this->getFrameDir($card->title, $card->set/*getDisplaySet()*/, $settings);
		$costColors = Card::getCostColors($card->cost);

		$useMulticolorFrame = (strlen($costColors) > 1 && strpos($settings['card.multicolor.frames'], strval(strlen($costColors))) !== false) || ($card->isDualManaCost() && (strpos($settings['card.multicolor.frames'], strval(strlen($costColors))) !== false || strlen($costColors) == 2));
		switch ($frameDir) {
		case "timeshifted": $useMulticolorFrame = false; break;
		case "transform-day": $useMulticolorFrame = false; /*$pts = explode("|", $card->pt); $card->pt = $pts[0]; $card->pt2 = $pts[1]; */break;
		case "transform-moon": $useMulticolorFrame = false; /*$pts = explode("|", $card->pt); $card->pt = $pts[0]; $card->pt2 = $pts[1]; */break;
		case "transform-spark": $useMulticolorFrame = false; /*$pts = explode("|", $card->pt); $card->pt = $pts[0]; $card->pt2 = $pts[1]; */break;
		case "transform-night": $useMulticolorFrame = false; break;
		case "transform-eldrazi": $useMulticolorFrame = false; break;
		case "fuse-left": $useMulticolorFrame = true; break;
		case "fuse-right": $useMulticolorFrame = true; break;
		}

		$canvas = imagecreatetruecolor(720, 1020);

		// Art image.
		if ($config['art.use.xlhq.full.card'] != false && stripos($card->artFileName,'xlhq') != false) {
			$this->drawArt($canvas, $card->artFileName, $settings['art.xlhq.top'], $settings['art.xlhq.left'], $settings['art.xlhq.bottom'], $settings['art.xlhq.right'], !$config['art.keep.aspect.ratio']);
		}
		else if(($card->isEldrazi() && $card->isArtefact()) || ($card->isDevoid() && $card->isArtefact()) || (substr($card->set, 0, -3) == 'MPS_' && $card->isArtefact()) || $card->title == 'Warping Wail' || $card->title == 'Spatial Contortion' || $card->title == 'Ghostfire' || $card->title == 'Gruesome Slaughter' || $card->title == "Titan's Presence" || $card->title == 'Scion of Ugin' || $card->title == 'Scour from Existence')
			$this->drawArt($canvas, $card->artFileName, 21, 17, 955, 703, !$config['art.keep.aspect.ratio']);
		else if ((($frameDir == 'fullartbasicland') && $card->isBasicLand()) || ($card->set == "EXP" && $card->isLand()))
			$this->drawArt($canvas, $card->artFileName, 103, 36, 842, 682, !$config['art.keep.aspect.ratio']);
		else
			$this->drawArt($canvas, $card->artFileName, $settings['art.top'], $settings['art.left'], $settings['art.bottom'], $settings['art.right'], !$config['art.keep.aspect.ratio']);

		echo '.';
		$promoset = explode(',',$config['card.promo.symbols']);
	// Background image, border image, and grey title/type image.
		if (($card->rarity == "R" || $card->rarity == "M" || $card->rarity == "S" || $card->rarity == "B" || in_array($card->set,$promoset)) && ($frameDir != 'transform-night'||$frameDir != 'transform-eldrazi')) { // Cards with Holofoil Stamp (R, M, S, B, Promos)
			$holofoil = '_H';
		} else { // Cards without Holofoil Stamp (C, U, Flip side of transform cards)
			$holofoil = '';
		}
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
				$landColorsTemp = /*$card->set . '_' . */$landColors . $holofoil;
			}
			else {
				$landColorsTemp = $landColors . $holofoil;
			}
			$bgImage = @imagecreatefrompng("images/m15/$frameDir/land/$landColorsTemp.png");
			if (!$bgImage) error("Background image not found for land color \"$landColors\": " . $card->title);
			// Grey title/type image.
			if (strlen($landColors) >= 2 && $settings['card.multicolor.grey.title.and.type'] && $landColorsTemp != "A$holofoil" || $card->title == 'Cinder Barrens' || $card->title == 'Hissing Quagmire' || $card->title == 'Meandering River' || $card->title == 'Needle Spires' || $card->title == 'Submerged Boneyard' || $card->title == 'Timber Gorge' || $card->title == 'Tranquil Expanse' || $card->title == 'Wandering Fumarole') {
				$greyTitleAndTypeOverlay = @imagecreatefrompng("images/m15/$frameDir/land/C-overlay.png");
				if (!$greyTitleAndTypeOverlay) error('Image not found: C-overlay.png');
			}
			// Green title/type image
			if (strtolower($card->title) == 'murmuring bosk') {
				$greyTitleAndTypeOverlay = @imagecreatefrompng("images/m15/$frameDir/land/G-overlay.png");
				if (!$greyTitleAndTypeOverlay) error('Image not found: G-overlay.png');
			}
		} else {
			if($card->isEldrazi() && $card->isArtefact() && !$card->isDevoid() || $card->title == 'Warping Wail' || $card->title == 'Spatial Contortion' || $card->title == 'Ghostfire' || $card->title == 'Gruesome Slaughter' || $card->title == "Titan's Presence" || $card->title == 'Scion of Ugin' || $card->title == 'Scour from Existence') { //Eldrazi without color
				$bgImage = @imagecreatefrompng("images/m15/$frameDir/cards/Eldrazi$holofoil.png");
			}	else if ($card->isDevoid() && $card->isArtefact() && (strlen($costColors) >= 2)) { //Devoid card with 2+ colors in casting cost
				$bgImage = @imagecreatefrompng("images/m15/$frameDir/cards/Devoid_Gld$holofoil.png");
			}	/*else if (substr($card->set, 0, -3) == 'MPS_' && $card->isArtefact()) {
				$bgImage = @imagecreatefrompng("images/m15/$frameDir/cards/Eldrazi$holofoil.png");
			}	*/else if ($card->isDevoid() && $card->isArtefact()) {//Devoid card with ONE color in casting cost
				$bgImage = @imagecreatefrompng("images/m15/$frameDir/cards/Devoid_" . $costColors . "$holofoil.png");
			}	else if($card->isConspiracy() && $card->isArtefact()) {
				if (preg_match('/\{([WUBRG])\}/',$card->legal,$matches)) $card->color = $matches[1];
				else $card->color = 'C';
				$bgImage = @imagecreatefrompng("images/m15/$frameDir/cards/Conspiracy" . $card->color . "$holofoil.png");
			}	else if ($card->isArtefact() && $card->isVehicle()) {
				$bgImage = @imagecreatefrompng("images/m15/$frameDir/cards/Vehicle$holofoil.png");
			}	else if ($card->isArtefact()) {
				if (substr($card->set, 0, -3) == 'MPS_') {
					$bgImage = @imagecreatefrompng("images/m15/$frameDir/cards/Art$holofoil.png");
				} else $bgImage = @imagecreatefrompng("images/m15/$frameDir/cards/Art$holofoil.png");
				if(strpos($settings['card.artifact.color'], strval(strlen($costColors))) !== false){ //Artifact with color
					if (strlen($costColors) >= 2) { //Artifact with 2+ color
						$useMulticolorFrame = false;
						$costColorsTemp = $costColors . $holofoil;
						$borderImage = @imagecreatefrompng("images/m15/$frameDir/borders/$costColorsTemp.png");
					}
					else { //Artifact with ONE color
						$useMulticolorFrame = false;
						//$borderImage = @imagecreatefrompng("images/m15/$frameDir/borders/$card->color$holofoil.png");
						$costColorsTemp = $costColors . $holofoil;
						$borderImage = @imagecreatefrompng("images/m15/$frameDir/borders/$costColorsTemp.png");
					}
				} else if (strlen($costColors) >= 2 || $card->color == 'Gld') {
					$useMulticolorFrame = false;
					$borderImage = @imagecreatefrompng("images/m15/$frameDir/borders/Gld$holofoil.png");
				}
			} else if ($useMulticolorFrame) {
				// Multicolor frame.
				$costColorsTemp = $costColors . $holofoil;
				$bgImage = @imagecreatefrompng("images/m15/$frameDir/cards/$costColorsTemp.png");
				if (!$bgImage) error("Background image not found for color: $costColors");
				// Grey title/type image.
				if ($settings['card.multicolor.grey.title.and.type']) {
					$greyTitleAndTypeOverlay = @imagecreatefrompng("images/m15/$frameDir/cards/C-overlay.png");
					if (!$greyTitleAndTypeOverlay) error('Image not found: C-overlay.png');
				}
			} else {
				// Mono color frame.
				$bgImage = @imagecreatefrompng("images/m15/$frameDir/cards/" . $card->color . "$holofoil.png");
				if (!$bgImage) error('Background image not found for color "' . $card->color . '" in frame dir: ' . $frameDir);
				// Border image.
				if (strlen($costColors) == 2 && $settings['card.multicolor.dual.borders']) {
					$costColorsTemp = $costColors . $holofoil;
					$borderImage = @imagecreatefrompng("images/m15/$frameDir/borders/$costColorsTemp.png");
				}
			}
		}
		
		imagecopy($canvas, $bgImage, 0, 0, 0, 0, 720, 1020);
		imagedestroy($bgImage);
		if ($borderImage) {
			imagecopy($canvas, $borderImage, 0, 0, 0, 0, 720, 1020);
			imagedestroy($borderImage);
		}
		if ($greyTitleAndTypeOverlay) {
			imagecopy($canvas, $greyTitleAndTypeOverlay, 0, 0, 0, 0, 720, 1020);
			imagedestroy($greyTitleAndTypeOverlay);
		}

		// God Stars Frame overlay
		/*if($card->set == "THS"||$card->set == "BNG"||$card->set == "JOU"||$card->set == "C15"||in_array($card->set,$promoset)) {
			if ($GodStars = $this->getGodStars($card->title)) {
				list($image) = getPNG("images/m15/gods/$GodStars.png", "GodStars image not found for: $GodStars");
				imagecopy($canvas, $image, 0, 0 , 0, 0, 720, 1020);
				imagedestroy($image);
			}
		}*/

		// Power / toughness.
		if ($card->pt) {
			$ptColor = null;
			if ($card->isEldrazi() && $card->isArtefact() || $card->isDevoid() && $card->isArtefact) {
				$image = @imagecreatefrompng("images/m15/$frameDir/pt/B.png");
			} else if ($card->isArtefact() && $card->isVehicle()) {
				$image = @imagecreatefrompng("images/m15/$frameDir/pt/Vehicle.png");
				$ptColor = 'color:255,255,255';
			} else if ($useMulticolorFrame) {
				$image = @imagecreatefrompng("images/m15/$frameDir/pt/" . substr($costColors, -1, 1) . '.png');
			} else if (($frameDir === 'transform-day' || $frameDir === 'transform-night' || $frameDir == 'transform-spark' || $frameDir == 'transform-moon' || $frameDir == 'transform-eldrazi') && strlen($card->color) >= 2 && $card->color != 'Art') {
				$image = @imagecreatefrompng("images/m15/$frameDir/pt/Gld.png");
			} else
				$image = @imagecreatefrompng("images/m15/$frameDir/pt/" . $card->color . '.png');
			if (!$image) error("Power/toughness image not found for color: $card->color");
			//imagecopy($canvas, $image, 0, 1020 - 162, 0, 0, 720, 162);
			imagecopy($canvas, $image, -1, 0, 0, 0, 720, 1020);
			imagedestroy($image);
			$this->drawText($canvas, $settings['pt.center.x'], $settings['pt.center.y'], $settings['pt.width'], $card->pt, $this->font('pt', "$ptColor"));
		}

		//Transform P/T
		if(($frameDir == "transform-day" || $frameDir == "transform-spark" || $frameDir == "transform-moon") && isset($card->pt2))
			$this->drawText($canvas, $settings['pt.transform.center.x'], $settings['pt.transform.center.y'], $settings['pt.transform.width'], $card->pt2, $this->font('pt.transform'));
			
		// Casting cost.
		$costLeft = $this->drawCastingCost($canvas, $card->getCostSymbols(), $card->isDualManaCost() ? $settings['cost.top.dual'] : $settings['cost.top'], $settings['cost.right'], $settings['cost.size'], true);

		echo '.';

		// Set and rarity.
		if ($card->isConspiracy()) {
			$rarityLeft = $this->drawRarity($canvas, $card->getDisplayRarity(), $card->getDisplaySet(), $settings['rarity.right'] - 20, $settings['rarity.center.y'], $settings['rarity.height'], $settings['rarity.width'], false);
		} else if ($card->rarity == "B" && $card->set == 'VMA') {
			$rarityLeft = $this->drawRarity($canvas, $card->getDisplayRarity(), $card->getDisplaySet(), $settings['rarity.right'], $settings['rarity.center.y'], $settings['rarity.height'], $settings['rarity.width'] * 1.5, false);
		} else if (!$card->isBasicLand() || $settings['card.basic.land.set.symbols']) {
			$rarityLeft = $this->drawRarity($canvas, $card->getDisplayRarity(), $card->getDisplaySet(), $settings['rarity.right'], $settings['rarity.center.y'], $settings['rarity.height'], $settings['rarity.width'], false);
		} else {
			$rarityLeft = $settings['rarity.right'];
			}

		// Card title.
		$this->drawText($canvas, $settings['title.x'], $settings['title.y'], ($costLeft - 20) - $settings['title.x'], $card->getDisplayTitle(), $this->font("title$languageFont"));

		echo '.';

		// Type.
		if($indicator = $this->getIndicator($card->title)) {
			$image = @imagecreatefrompng("images/symbols/indicators/" . $indicator . '.png');
			imagecopyresampled($canvas, $image, $settings['indicator.x'], $settings['indicator.y'], 0, 0, $settings['indicator.size'], $settings['indicator.size'], 300, 300);
			imagedestroy($image);
			$typex = $settings['type.indicator.x'];
			$this->drawText($canvas, $typex, $settings['type.y'], $rarityLeft - $settings['type.x'], $card->type, $this->font("type$languageFont"));
		}
		else if (($card->set == 'BFZ' || $card->set == 'OGW' || $card->set == 'ZEN' || $card->set == 'UGL' || $card->set == 'UNH') && $card->isBasicLand() && $settings['card.fullart.land.frames'] !== FALSE && $frameDir == 'fullartbasicland') {
			$typex = $settings['type.x'];
			$type2x = $settings['type2.x'];
			$typearray = preg_split('/([—:～―])/u', $card->type);
			$type1 = $typearray[0];
			$type2 = @$typearray[1];
			$this->drawText($canvas, $typex, $settings['type.y'], $settings['type2.x'] -  $settings['type.x'], $type1, $this->font("type$languageFont"));
			$this->drawText($canvas, $type2x, $settings['type2.y'], $rarityLeft -  $settings['type2.x'], $type2, $this->font("type$languageFont"));
		}
		else {
			$typex = (($frameDir == "transform-night"||$frameDir == "transform-moon") && $card->isArtefact()) ? $settings['type.art.x'] : $settings['type.x'];
		 $this->drawText($canvas, $typex, $settings['type.y'], $rarityLeft - $typex, $card->type, $this->font("type$languageFont"));
		 }

		// Guild sign.
		if($card->set == "DIS"||$card->set == "GPT"||$card->set == "RAV"||$card->set == "RTR"||$card->set == "GTC"||$card->set == "DGM"||$card->set == "V16"||in_array($card->set,$promoset) && $card->set != 'MGD') {
			if ($guild = $this->getGuild($card->title)) {
				if ($card->title == 'Momir Vig, Simic Visionary' && $card->set == 'V16') $guild .= ' GU';
				list($image, $width, $height) = getPNG("images/watermarks/guilds/$guild.png", "Guild image not found for: $guild");
				if ($titleToFuse = $this->getFuse($card->title)) {
					// Set the width of the area and height of the area
					$inputwidth = 646;
					$inputheight = 230;
					// Set the position we want the new image
					$position = 632;
				}
				else {
					// Set the width of the area and height of the area
					$inputwidth = 646;
					$inputheight = 280;
					// Set the position we want the new image
					$position = 645;
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
		if($card->set == "KTK"||$card->set == "FRF"||$card->set == "DTK"||in_array($card->set,$promoset) && $card->set != 'MGD') {
			if ($clan = $this->getClan($card->title)) {
				list($image, $width, $height) = getPNG("images/watermarks/clans/$clan.png", "Clan image not found for: $clan");
				imagecopy($canvas, $image, 373 - ($width / 2), 652, 0, 0, $width, $height);
				imagecopy($canvas, $image, 373 - ($width / 2), 652, 0, 0, $width, $height); // Too much alpha, need to apply twice.
				imagedestroy($image);
			}
		}
		
		// Quest sign.
		if($card->set == "HRO"||in_array($card->set,$promoset)) {
			if ($quest = $this->getQuest($card->title)) {
				list($image, $width, $height) = getPNG("images/watermarks/quests/$quest.png", "Quest image not found for: $quest");
				imagecopy($canvas, $image, 373 - ($width / 2), 652, 0, 0, $width, $height);
				imagecopy($canvas, $image, 373 - ($width / 2), 652, 0, 0, $width, $height); // Too much alpha, need to apply twice.
				imagedestroy($image);
			}
		}

		// Phyrexia/Mirran sign.
		if($card->set == "SOM"||$card->set == "MBS"||$card->set == "NPH"||$card->set == "V16"||in_array($card->set,$promoset) && $card->set != 'MGD') {
			if ($phyrexia = $this->getPhyrexia($card->title)) {
				list($image, $width, $height) = getPNG("images/watermarks/phyrexia/$phyrexia.png", "Phyrexia/Mirran image not found for: $phyrexia");
				//PNG height = 280px
				imagecopy($canvas, $image, 360 - ($width / 2), 645, 0, 0, $width, $height);
				imagecopy($canvas, $image, 360 - ($width / 2), 645, 0, 0, $width, $height); // Too much alpha, need to apply twice.
				imagedestroy($image);
			}
		}

		// Story Spotlight planeswalker watermark.
		if($card->set == "KLD"||$card->set == "AER"||$card->set == "AKH"||$card->set == "HOU"||$card->set == 'PRE') {
			if ($story = $this->getStorySpotlight($card->title)) {
				list($image, $width, $height) = getPNG("images/watermarks/story/$story[0].png", "Story Spotlight image not found for: $story[0]");
				imagecopy($canvas, $image, (720 / 2) - ($width / 2), 645, 0, 0, $width, $height);
				//imagecopy($canvas, $image, (720 / 2) - ($width / 2), 645, 0, 0, $width, $height); // Too much alpha, need to apply twice.
				imagedestroy($image);
			}
		}
		
		// Promo overlay
		if ($card->promo && !$card->isBasicLand()) {
			list($image, $width, $height) = getPNG('images/promo/' . $card->promo . '.png', 'Promo overlay image not found for: ' . $card->promo);
			//PNG height = 280px
			imagecopy($canvas, $image, 360 - ($width / 2), 645, 0, 0, $width, $height);
			imagedestroy($image);
		}

		if ($card->isBasicLand() || ($card->isLand() && $card->legal == '' && $card->flavor == '')) {
			// Basic land symbol instead of legal text.
			list($image, $width, $height) = getPNG("images/symbols/land/$landColors.png", "Basic land image not found for: images/symbols/land/$landColors.png");
			//imagecopy($canvas, $image, 373 - ($width / 2), 660, 0, 0, $width, $height);
			if ($frameDir == 'fullartbasicland' && $settings['card.fullart.basic.land.frames.set'] != FALSE) {
				// Set the width of the area and height of the area
				$inputwidth = 120;
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
				imagecopy($canvas, $dst_img, 365 - ($outputwidth / 2), 800, 0, 0, $outputwidth, $outputheight);
				imagedestroy($dst_img);
			}
			else {
				imagecopy($canvas, $image, 360 - ($width / 2), 645, 0, 0, $width, $height);
			}
			imagedestroy($image);
		} else if ($card->isLand() && strlen($landColors) == 2 && (!$card->legal || preg_match('/([\#]{0,1}[\((]\{T\}[ \::]{1,2}.*?\{[WUBRG]\}.*?\{[WUBRG]\}.*?[\.?][\))][\#]{0,1})(?!.)/su', $card->legal) && $settings['card.dual.land.symbols'] != FALSE)) {
			// Dual land symbol instead of legal text.
			if ($settings['card.dual.land.symbols'] == 1) {
				// Single hybrid symbol.
				list($image, $width, $height) = getPNG("images/symbols/land/$landColors.png", "Dual symbol image not found for: $landColors");
				imagecopy($canvas, $image, 360 - ($width / 2), 645, 0, 0, $width, $height);
				imagedestroy($image);
			} else if ($settings['card.dual.land.symbols'] == 2) {
				// One of each basic symbol.
				$landColor = substr($landColors, 0, 1);
				list($image, $width, $height) = getPNG("images/symbols/land/$landColor.png", 'Basic land image not found for: ' . $card->title);
				imagecopy($canvas, $image, 360 - ($width / 2), 645, 0, 0, $width, $height);
				imagedestroy($image);

				$landColor = substr($landColors, 1, 2);
				list($image, $width, $height) = getPNG("images/symbols/land/$landColor.png", 'Basic land image not found for: ' . $card->title);
				imagecopy($canvas, $image, 360 - ($width / 2), 645, 0, 0, $width, $height);
				imagedestroy($image);
			}
		} else if (preg_match('/\{T\}[ \:]{1,2} (.*?) \{([WUBRGC])\}([A-Za-z ]*?)\.(?!.)/su', $card->legal, $matches) && $config['card.use.symbol.on.mox'] == true && $card->flavor == '') {
			list($image, $width, $height) = getPNG("images/symbols/land/$matches[1].png", "Basic land image not found for: images/symbols/land/$matches[1].png");
			imagecopy($canvas, $image, 360 - ($width / 2), 645, 0, 0, $width, $height);
			imagedestroy($image);
		} else {
			// Legal and flavor text.
			$legaltemp = str_replace('#', '', $card->legal);
			if ($card->pt) {
				if((strlen($legaltemp) <= 40 || preg_match('/([\#]{0,1}[\((]\{T\}[ \::]{1,2}.*?\{[WUBRG]\}.*?\{[WUBRG]\}.*?[\.?][\))][\#]{0,1})(?!.)/su', $card->legal)) && strpos($card->legal, "\n") === FALSE && $card->flavor == '') {
					$this->drawText($canvas, ($settings['text.right'] + $settings['text.left']) / 2, ($settings['text.top'] + $settings['textPT.bottom']) / 2, null, $card->legal, $this->font("text$languageFont", 'centerY:true centerX:true'));
				} else {
					$heightAdjust = $card->pt ? $settings['text.pt.height.adjust'] : 0;
					$this->drawLegalAndFlavorText($canvas, $settings['text.top'], $settings['text.left'], $settings['textPT.bottom'], $settings['text.right'], $card->legal, $card->flavor, $this->font("text$languageFont"), $heightAdjust);
			}} else {
				if((strlen($legaltemp) <= 40 || preg_match('/([\#]{0,1}[\((]\{T\}[ \::]{1,2}.*?\{[WUBRG]\}.*?\{[WUBRG]\}.*?[\.?][\))][\#]{0,1})(?!.)/su', $card->legal)) && strpos($card->legal, "\n") === FALSE && $card->flavor == '') {
					$this->drawText($canvas, ($settings['text.right'] + $settings['text.left']) / 2, ($settings['text.top'] + $settings['text.bottom']) / 2, null, $card->legal, $this->font("text$languageFont", 'centerY:true centerX:true'));
				} else {
					$heightAdjust = $card->pt ? $settings['text.pt.height.adjust'] : 0;
					$this->drawLegalAndFlavorText($canvas, $settings['text.top'], $settings['text.left'], $settings['text.bottom'], $settings['text.right'], $card->legal, $card->flavor, $this->font("text$languageFont"), $heightAdjust);
				}
			}
			
			//if(strlen($card->legal) < 40 && strpos($card->legal, "\n") === FALSE && $card->flavor == '')
				//$this->drawText($canvas, ($settings['text.right'] + $settings['text.left']) / 2, ($settings['text.top'] + $settings['text.bottom']) / 2, null, $card->legal, $this->font("text$languageFont", 'centerY:true centerX:true'));
			//else {
				//$heightAdjust = $card->pt ? $settings['text.pt.height.adjust'] : 0;
				//$this->drawLegalAndFlavorText($canvas, $settings['text.top'], $settings['text.left'], $settings['text.bottom'], $settings['text.right'], $card->legal, $card->flavor, $this->font("text$languageFont"), $heightAdjust);
			//}
		}

		// Artist and copyright.
		// The artist color is white if the frame behind it is black.
		$footerColor = '255,255,255';

		if ($card->artist) {
			if ($settings['card.artist.gears']) {
				$artistSymbol = '{gear}';
			} else {
				$artistSymbol = '{brush2}';
			}
			//$this->drawText($canvas, $settings['artist.x'], $settings['artist.y'], null, $card->collectorNumber . ' ' . $card->rarity . "\r\n" . $card->set . ' ' . "&#8226;" . ' ' . $config['card.lang'] . ' ' . $artistSymbol . $card->artist, $this->font('artist', 'color:' . $footerColor));
			$lang = $config['card.lang'];
			if (empty($card->collectorNumber)) $card->collectorNumber = '0/0';

			$card->collectorNumber = str_replace("\\", '/', $card->collectorNumber);
			$collectorNumber = explode('/', $card->collectorNumber);
			$collectorNumber[0] = str_pad($collectorNumber[0], 3, "0", STR_PAD_LEFT);
			$collectorNumber[1] = str_pad($collectorNumber[1], 3, "0", STR_PAD_LEFT);
			$card->collectorNumber = implode('/', $collectorNumber);
			//$LineSizeTxtL1 = Font::getLargestWidth($card->collectorNumber,$this->font('collection')) * strlen($card->collectorNumber);
			
			if (substr($card->set, 0, -3) == 'MPS_') $card->setM15DisplaySet('MPS');
			//$CollectionTxtL1 = $card->collectorNumber . "&#160;" . "&#160;" . $card->rarity;
			$CollectionTxtL1 = $card->collectorNumber;
			if (in_array($card->set,$promoset) !=false) {
				if ($card->getM15DisplaySet() != $card->set)
					$CollectionTxtL2 = $card->getM15DisplaySet() . ' ' . "&#9733;" . ' ' . $lang . ' ' ;
				else if (($card->getDisplaySet() != $card->set) && ($card->set == 'PRE'||$card->set == 'FBP'||$card->set == 'MGD'||$card->set == 'REL'||$card->set == 'UGF'))
					$CollectionTxtL2 = $card->getDisplaySet() . ' ' . "&#9733;" . ' ' . $lang . ' ' ;
				else $CollectionTxtL2 = $card->set . ' ' . "&#9733;" . ' ' . $lang . ' ' ;
			}
			else {
				if (substr($card->set, 0, -3) == 'MPS_') $CollectionTxtL2 = $card->getM15DisplaySet() . ' ' . "&#9733;" . ' ' . $lang . ' ' ;
				else $CollectionTxtL2 = $card->set . ' ' . "&#8226;" . ' ' . $lang . ' ' ;
			}
			$font = $settings['font.collection'];
			$font = str_replace(' ','',$font);
			$arrayfont=explode(",",$font);
			$fontName = $arrayfont[1];
			$fontsize = $arrayfont[0];
			$lineSizeL1 = imagettfbbox($fontsize,0,"./fonts/$fontName",$CollectionTxtL1);
			$lineSizeL2 = imagettfbbox($fontsize,0,"./fonts/$fontName",$CollectionTxtL2);
			
			//$this->drawText($canvas, $settings['collection.x'], $settings['collection.y'], null, $card->collectorNumber . "&#160;" . $card->rarity . "\r\n" . $card->set . ' ' . "&#8226;" . ' ' . $lang .  ' ' . $artistSymbol, $this->font('collection', 'color:' . $footerColor));
			//$this->drawText($canvas, $settings['collection.x'], $settings['collection.y'], null, $CollectionTxtL1 . "\r\n" . $CollectionTxtL2 . $artistSymbol, $this->font('collection', 'color:' . $footerColor));
			
			$this->drawText($canvas, $settings['collection.x'], $settings['collection.y'], null, $CollectionTxtL1 . "\r\n" . $CollectionTxtL2 . $artistSymbol, $this->font('collection', 'color:' . $footerColor));
			
			
			if (in_array($card->set, $promoset)) {
				$this->drawText($canvas, $settings['collection.x'] + ($lineSizeL2[2]), $settings['collection.y'], null, "P", $this->font('collection', 'color:' . $footerColor));
			} else if ($card->isBasicLand() && $card->title != 'Wastes') {
				$this->drawText($canvas, $settings['collection.x'] + ($lineSizeL2[2]), $settings['collection.y'], null, "L", $this->font('collection', 'color:' . $footerColor));
			} else if (($lineSizeL1[2] + 8) >= $lineSizeL2[2]) {
				$this->drawText($canvas, $settings['collection.x'] + ($lineSizeL1[2] + 8), $settings['collection.y'], null, $card->rarity, $this->font('collection', 'color:' . $footerColor));
			} else {
				$this->drawText($canvas, $settings['collection.x'] + ($lineSizeL2[2]), $settings['collection.y'], null, $card->rarity, $this->font('collection', 'color:' . $footerColor));
			}
			if (@$story[1] != null) {
				$this->drawText($canvas, $settings['collection.x'] + ($lineSizeL2[2] + $settings['artistOffset.x']), $settings['collection.y'], 170, $story[1], $this->font('promo.text.type', 'color:' . $footerColor));
			} else if ((($promoSetText = $this->getPromoSetText($card->set))||($promoSetText = $this->getPromoSetText($card->getDisplaySet()))) && in_array($card->set, $promoset)) {
				$this->drawText($canvas, $settings['collection.x'] + ($lineSizeL2[2] + $settings['artistOffset.x']), $settings['collection.y'], null, $promoSetText, $this->font('promo.text.type', 'color:' . $footerColor));
			} else if (($promoCardText = $this->getPromoCardText($card->title)) && in_array($card->set, $promoset)){
				$this->drawText($canvas, $settings['collection.x'] + ($lineSizeL2[2] + $settings['artistOffset.x']), $settings['collection.y'], null, $promoCardText, $this->font('promo.text.type', 'color:' . $footerColor));
			}
			//$this->drawText($canvas, $settings['artist.x'], $settings['artist.y'], null, $card->artist, $this->font('artist', 'color:' . $footerColor));
			$this->drawText($canvas, $settings['collection.x'] + ($lineSizeL2[2] + $settings['artistOffset.x']), $settings['artist.y'], null, $card->artist, $this->font('artist', 'color:' . $footerColor));
		}
		if ($card->copyright) {
			if ($config['card.use.set.date']) {
				$date = $this->setDB->getSetDate($card->set);
				if ($date == null) $date = date("Y");
			}
			else $date = date("Y");
			$CopyrightTxt = $config['card.copyright.m15'];
			$CopyrightTxt = str_replace('YYYY', "$date", $CopyrightTxt);
			if (@$story[1] != null) {
				$StoryTxt = 'mtgstory.com';
				if ($language && strtolower($language) != 'english') $StoryTxt .= '/' . strtolower($config['card.lang']);
				$storyFont = $settings['font.story.text.type'];
				$storyFont = str_replace(' ','',$font);
				$storyArrayfont=explode(",",$font);
				$storyFontName = $arrayfont[1];
				$storyFontsize = $arrayfont[0];
				$storyLineSize = imagettfbbox($fontsize,0,"./fonts/$fontName",$StoryTxt);
			}
			$font = $settings['font.copyright'];
			$font = str_replace(' ','',$font);
			$arrayfont=explode(",",$font);
			$fontName = $arrayfont[1];
			$fontsize = $arrayfont[0];
			$lineSize = imagettfbbox($fontsize,0,"./fonts/$fontName",$CopyrightTxt);
			if ($card->pt||@$story[1] != null) {
				//$this->drawText($canvas, $settings['copyright.x'], $settings['copyrightPT.y'], null, $card->copyright, $this->font('copyright', 'color:' . $footerColor));
				$this->drawText($canvas, (720 - ($lineSize[2] + $settings['copyrightLeft.x'])), $settings['copyrightPT.y'], null, $CopyrightTxt, $this->font('copyright', 'color:' . $footerColor));
				if (@$story[1] != null) {
					$this->drawText($canvas, (720 - ($storyLineSize[2] + $settings['storyLeft.x'])), $settings['story.y'], null, $StoryTxt, $this->font('story.text.type', 'color:' . $footerColor));
				}
			} else {
				//$this->drawText($canvas, $settings['copyright.x'], $settings['copyright.y'], null, $card->copyright, $this->font('copyright', 'color:' . $footerColor));
				$this->drawText($canvas, (720 - ($lineSize[2] + $settings['copyrightLeft.x'])), $settings['copyright.y'], null, $CopyrightTxt, $this->font('copyright', 'color:' . $footerColor));
			}
		}

	/*if ($config['output.card.full.border']) {
		list($canvasFull, $width, $height) = getPNG("images/border/m15.png", "Image not found for: images/border/m15.png");
		imagecopy($canvasFull, $canvas, 0, 0, 0, 0, 720, 1020);
		imagedestroy($canvas);
		$canvas = $canvasFull;
	}*/
	
	echo "\n";
	return $canvas;
	}

	public function getSettings () {
		global $rendererSettings, $rendererSections;
		$settings = $rendererSettings['config/config-m15.txt'];
		$frameDir = $this->getFrameDir($this->card->title, $this->card->set/*getDisplaySet()*/, $settings);
		$settings = array_merge($settings, $rendererSections['config/config-m15.txt']['fonts - ' . $frameDir]);
		$settings = array_merge($settings, $rendererSections['config/config-m15.txt']['layout - ' . $frameDir]);
		return $settings;
	}

	private function getGuild ($title) {
		if (!M15Renderer::$titleToGuild) M15Renderer::$titleToGuild = csvToArray('data/m15/titleToGuild.csv');
		return @M15Renderer::$titleToGuild[(string)strtolower($title)];
	}
	
	private function getFuse ($title) {
		if(!M15Renderer::$titleToFuse) M15Renderer::$titleToFuse = csvToArray('data/m15/titleToFuse.csv');
		return @M15Renderer::$titleToFuse[(string)strtolower($title)];
	}
	
	private function getClan ($title) {
		if (!M15Renderer::$titleToClan) M15Renderer::$titleToClan = csvToArray('data/m15/titleToClan.csv');
		return @M15Renderer::$titleToClan[(string)strtolower($title)];
	}
	
	private function getQuest ($title) {
		if (!M15Renderer::$titleToQuest) M15Renderer::$titleToQuest = csvToArray('data/m15/titleToQuest.csv');
		return @M15Renderer::$titleToQuest[(string)strtolower($title)];
	}

	private function getStorySpotlight ($title) {
		global $config;
		$language = $config['output.language'];
		$array = array();
		if (!M15Renderer::$titleToStorySpotlight) M15Renderer::$titleToStorySpotlight = csvToArray13('data/titleToStorySpotlight.csv');
		$data = @M15Renderer::$titleToStorySpotlight[(string)strtolower($title)];
		if ($language && $language != 'english') {
			switch ($language) {
				case "chinese-china": $array = array(@$data[0],@$data[2] != "" ? $data[2] : $data[1]); break;
				case "chinese-taiwan": $array = array($data[0],$data[3] != "" ? $data[3] : $data[1]); break;
				case "french": $array = array($data[0],$data[4] != "" ? $data[4] : $data[1]); break;
				case "french-oracle": $array = array($data[0],$data[4] != "" ? $data[4] : $data[1]); break;
				case "german": $array = array($data[0],$data[5] != "" ? $data[5] : $data[1]); break;
				case "italian": $array = array($data[0],$data[6] != "" ? $data[6] : $data[1]); break;
				case "japanese": $array = array($data[0],$data[7] != "" ? $data[7] : $data[1]); break;
				case "portugese": $array = array($data[0],$data[8] != "" ? $data[8] : $data[1]); break;
				case "russian": $array = array($data[0],$data[9] != "" ? $data[9] : $data[1]); break;
				case "spanish": $array = array($data[0],$data[10] != "" ? $data[10] : $data[1]); break;
				case "korean": $array = array($data[0],$data[11] != "" ? $data[11] : $data[1]); break;
				default: $array = array($data[0],$data[1]); break;
			}
		}
		else $array = array($data[0],$data[1]);
		if ($array[0] != '') return @$array;
	}

	/*private function getGodStars ($title) {
		if (!M15Renderer::$titleToGodStars) M15Renderer::$titleToGodStars = csvToArray('data/m15/titleToGodStars.csv');
		return @M15Renderer::$titleToGodStars[(string)strtolower($title)];
	*/

	private function getPhyrexia ($title) {
		if (!M15Renderer::$titleToPhyrexia) M15Renderer::$titleToPhyrexia = csvToArray('data/m15/titleToPhyrexia.csv');
		return @M15Renderer::$titleToPhyrexia[(string)strtolower($title)];
	}

	private function getIndicator ($title) {
		if (!M15Renderer::$titleToIndicator) M15Renderer::$titleToIndicator = csvToArray('data/m15/titleToIndicator.csv');
		return @M15Renderer::$titleToIndicator[(string)strtolower($title)];
	}

	private function getPromoSetText ($set) {
		if (!M15Renderer::$titleToPromoSetText) M15Renderer::$titleToPromoSetText = csvToArray('data/setToPromoText.csv');
		return @M15Renderer::$titleToPromoSetText[(string)strtolower($set)];
	}
	
	private function getPromoCardText ($title) {
		if (!M15Renderer::$titleToPromoCardText) M15Renderer::$titleToPromoCardText = csvToArray('data/titleToPromoText.csv');
		return @M15Renderer::$titleToPromoCardText[(string)strtolower($title)];
	}
	
	private function getFrameDir ($title, $set, $settings) {
		if (!M15Renderer::$titleToFrameDir) M15Renderer::$titleToFrameDir = csvToArray('data/m15/titleToAlternateFrame.csv');
		if (!M15Renderer::$titleToTransform) M15Renderer::$titleToTransform = csvToArray('data/titleToTransform.csv');
		$frameDir = @M15Renderer::$titleToFrameDir[(string)strtolower($title)];
		if (!$frameDir) $frameDir = 'transform-' . @M15Renderer::$titleToTransform[(string)strtolower($title)];
		$timeshifted = explode(',', $settings['card.timeshifted.frames']);
		$fuse = explode(',',$settings['card.split.fuse.set']);
		$nyxstars = explode(',',$settings['card.nyxstars.set']);
		$fullartbasic = explode(',', $settings['card.fullart.basic.land.frames.set']);
		$expedition = explode(',', $settings['card.fullart.expedition.frames']);
		$gameday = 'MGD';
		$masterpiece = 'MPS_';
		$conspiracy = explode(',',$settings['card.conspiracy.set']);
		if (!$frameDir || $frameDir == 'transform-') $frameDir = "regular";
		if ($frameDir == 'fullartbasicland' && in_array($set, $fullartbasic) === FALSE) $frameDir = "regular";
		if ($frameDir == 'fullartbasicland' && ($set == 'OGW' && $this->card->pic >= 3||$set == 'BFZ' && $this->card->pic >= 6||$set == 'ZEN' && $this->card->pic >= 5)) $frameDir = "regular";
		if ($frameDir == 'expedition' && in_array($set, $expedition) === FALSE) $frameDir = "regular";
		if ($frameDir == 'timeshifted' && in_array($set, $timeshifted) === FALSE) $frameDir = "regular";
		if ($frameDir == 'fuse-' && in_array($set, $fuse) === FALSE) $frameDir = "regular";
		if ($frameDir == 'nyxstars' && in_array($set, $nyxstars) === FALSE) $frameDir = "regular";
		if ($frameDir == 'mgdpromo' && $set != $gameday) $frameDir = "regular";
		if ($frameDir == 'masterpiece' && substr($set, 0, -3) != $masterpiece) $frameDir = "regular";
		if ($frameDir == 'conspiracy' && in_array($set, $conspiracy) === FALSE) $frameDir = "regular";
		return $frameDir;
	}
}

?>
