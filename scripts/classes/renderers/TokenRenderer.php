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
class TokenRenderer extends CardRenderer {
	static private $settingSections;
	static private $titleToGuild;
	static private $titleToPhyrexia;
	static private $titleToClan;
	static private $titleToQuest;
	static private $titleToGodStars;
	static private $titleToFrameDir;
	static private $titleToTransform;
	static private $titleToToken;
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
		$language = strtolower($config['output.language']);
		$card = $this->card;
		$settings = $this->getSettings();
		if ($card->isToken()) {
			$frameDir = $this->getFrameDir($card->title, $card->getDisplaySet(), $settings);
		} else $frameDir = $this->getFrameDir($card->title, $card->set, $settings);
		$costColors = Card::getCostColors($card->cost);
		
		$useMulticolorFrame = (strlen($costColors) > 1 && strpos($settings['card.multicolor.frames'], strval(strlen($costColors))) !== false) || ($card->isDualManaCost() && (strpos($settings['card.multicolor.frames'], strval(strlen($costColors))) !== false || strlen($costColors) == 2));
		$promoset = explode(',',$config['card.promo.symbols']);
		//Eighth tokens
		if ($frameDir == 'token' || $frameDir == 'tokentext' || $frameDir == 'nyxstarstoken' || $frameDir == 'nyxstarstokentext' || $frameDir == 'emblem') {
			$canvas = imagecreatetruecolor(736, 1050);
	
			// Art image.
			if ($config['art.use.xlhq.full.card'] != false && stripos($card->artFileName,'xlhq') != false) {
				$this->drawArt($canvas, $card->artFileName, $settings['art.xlhq.top'], $settings['art.xlhq.left'], $settings['art.xlhq.bottom'], $settings['art.xlhq.right'], !$config['art.keep.aspect.ratio']);
			}
			else if (($card->isEldrazi() && $card->isArtefact()) ||($card->isDevoid() && $card->isArtefact()) || (($frameDir == 'fullartbasicland') && $card->isBasicLand()) || ($card->set == "EXP" && $card->isLand()) || (stripos($card->englishType, 'Sliver') !== FALSE))
				$this->drawArt($canvas, $card->artFileName, 12, 13, 1037, 722, !$config['art.keep.aspect.ratio']);
			else
				$this->drawArt($canvas, $card->artFileName, $settings['art.top'], $settings['art.left'], $settings['art.bottom'], $settings['art.right'], !$config['art.keep.aspect.ratio']);
	
			echo '.';
	
			// Background image, border image, and grey title/type image.
			$borderImage = null;
			$greyTitleAndTypeOverlay = null;
				$colorTemp = $card->color;
				if (strlen($colorTemp) >= 3 && $colorTemp != 'Art') $colorTemp = 'Gld';
				if($card->isEldrazi() && $card->isArtefact()||stripos($card->englishType, 'Sliver') !== FALSE) {
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/Eldrazi.png");
				} else if ($card->color == 'Art' && $card->isEmblem()) {
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/Emblem.png");
				} else if ($card->color == 'Art' && strpos($card->englishType, 'Artifact') === FALSE) {
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/C.png");
				}  else if ($card->isArtefact()) {
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/Art.png");
					if (strpos($settings['card.artifact.color'], strval(strlen($colorTemp))) !== false && $colorTemp != 'Art'){ //Artifact with color
						if (strlen($colorTemp) >= 2 && $colorTemp != 'Gld' && $colorTemp != 'Art') {//Artifact with 2+ color
							$useMulticolorFrame = false;
							$costColorsTemp = $colorTemp;
							$borderImage = @imagecreatefrompng("images/token/$frameDir/borders/$costColorsTemp.png");
						}
						else {//Artifact with ONE color
							$useMulticolorFrame = false;
							$costColorsTemp = $colorTemp;
							$borderImage = @imagecreatefrompng("images/token/$frameDir/borders/$costColorsTemp.png");
						}
					} else if (strlen($costColors) >= 2 || $card->color == 'Gld') {
						$useMulticolorFrame = false;
						$borderImage = @imagecreatefrompng("images/token/$frameDir/borders/Gld.png");
					}
				} else if ($card->isToken()){
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/$colorTemp.png");
				} else if ($useMulticolorFrame) {
					// Multicolor frame.
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/$costColors.png");
					if (!$bgImage) error("Background image not found for color: $costColors");
					// Grey title/type image.
					if ($settings['card.multicolor.grey.title.and.type']) {
						$greyTitleAndTypeOverlay = @imagecreatefrompng("images/token/$frameDir/cards/C-overlay.png");
						if (!$greyTitleAndTypeOverlay) error('Image not found: C-overlay.png');
					}
				} else {
					// Mono color frame.
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/" . $colorTemp . '.png');
					if (!$bgImage) error('Background image not found for color "' . $colorTemp . '" in frame dir: ' . $frameDir);
					// Border image.
					if (strlen($costColors) == 2 && $settings['card.multicolor.dual.borders'])
						$borderImage = @imagecreatefrompng("images/token/$frameDir/borders/$costColors.png");
				}
			//}
			if (!$bgImage) error("Background image not found for color: $colorTemp");
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
			if($card->set == "THS"||$card->set == "BNG"||$card->set == "JOU"||$card->set == "PRE"||$card->set == "REL") {
				if ($GodStars = $this->getGodStars($card->title)) {
					list($image) = getPNG("images/token/gods/$GodStars.png", "GodStars image not found for: $GodStars");
					imagecopy($canvas, $image, 0, 0 , 0, 0, 736, 1050);
					imagedestroy($image);
				}
			}
	
			// Power / toughness.
			if ($card->pt) {
				if($card->isEldrazi() && $card->isArtefact() || $card->isDevoid() && $card->isArtefact||stripos($card->englishType, 'Sliver') !== FALSE) {
					$image = @imagecreatefrompng("images/token/$frameDir/pt/B.png");
				} else if ($card->color == 'Art' && !$card->isArtefact()) {
					$image = @imagecreatefrompng("images/token/$frameDir/pt/C.png");
				} else if ($card->color == 'Art') {
					$image = @imagecreatefrompng("images/token/$frameDir/pt/" . $card->color . '.png');
				} else if ($useMulticolorFrame) {
					$image = @imagecreatefrompng("images/token/$frameDir/pt/" . substr($costColors, -1, 1) . '.png');
				} else if ($card->isToken() && strlen($card->color) == 2) {
					$image = @imagecreatefrompng("images/token/$frameDir/pt/" . substr($card->color, -1, 1) . '.png');
				} else if ($card->isToken() && strlen($card->color) == 3 && $colorTemp != 'Gld') {
					$image = @imagecreatefrompng("images/token/$frameDir/pt/" . substr($card->color, -1, 1) . '.png');
				} else {
					$image = @imagecreatefrompng("images/token/$frameDir/pt/" . $colorTemp . '.png');
				}
				if (!$image) error("Power/toughness image not found for color: $colorTemp");
				imagecopy($canvas, $image, 0, 1050 - 162, 0, 0, 736, 162);
				imagedestroy($image);
				$this->drawText($canvas, $settings['pt.center.x'], $settings['pt.center.y'], $settings['pt.width'], $card->pt, $this->font('pt'));
			}
	
			//Transform P/T
			if($frameDir == "transform-day" && isset($pts[1]))
				$this->drawText($canvas, $settings['pt.transform.center.x'], $settings['pt.transform.center.y'], $settings['pt.transform.width'], $pts[1], $this->font('pt.transform'));
	
			// Casting cost.
			$costLeft = $this->drawCastingCost($canvas, $card->getCostSymbols(), $card->isDualManaCost() ? $settings['cost.top.dual'] : $settings['cost.top'], $settings['rarity.right'/*cost.right*/] - 70, $settings['cost.size'], true);
	
			echo '.';
	
			// Set and rarity.
			if ((!$card->isBasicLand() || $settings['card.basic.land.set.symbols']) && ($card->isBasicLand() && ($card->set == "UNH" || $card->set == "UGL"))) {
				$rarityLeft = $this->drawRarity($canvas, $card->getDisplayRarity(), $card->getDisplaySet(), $settings['rarity.' . strtolower($card->set) . '.right'], $settings['rarity.center.' . strtolower($card->set) . '.y'], $settings['rarity.height'], $settings['rarity.width'], false);
				}
			else if ($card->isConspiracy()) {
				$rarityLeft = $this->drawRarity($canvas, $card->getDisplayRarity(), $card->getDisplaySet(), $settings['rarity.right'] - 28, $settings['rarity.center.y'], $settings['rarity.height'], $settings['rarity.width'], false);
				}
			else if (!$card->isBasicLand() || $settings['card.basic.land.set.symbols']) {
				$rarityLeft = $this->drawRarity($canvas, $card->getDisplayRarity(), $card->getDisplaySet(), $settings['rarity.right'], $settings['rarity.center.y'], $settings['rarity.height'], $settings['rarity.width'], false);
				}
			//if (explode(',', $config['card.promo.symbols']);
			else
				$rarityLeft = $settings['rarity.right'];
			
			//Art overlay
			if ($card->isEmblem()) {
				$overlayFileName = preg_replace('/(.*)\.(jpg|png)/i','$1-overlay.png', $card->artFileName);
				if(file_exists($overlayFileName))
				$this->drawArt($canvas, $overlayFileName, $settings['art.top'], $settings['art.left'], $settings['art.bottom'], $settings['art.right'], !$config['art.keep.aspect.ratio']);
			}
	
			// Card title.
			$titleCharacters = trim(mb_strtoupper($card->getDisplayTitle()));
			if ($card->isEmblem()) $titleLines = explode(':', $titleCharacters);
			$symbolFont = null;
			$center = null;
			if ($language && $language != 'english' || $card->isEmblem() !== FALSE || strlen(preg_replace('/{Img(\p{L}+)}/iu', ' ', $card->getDisplayTitle())) > 18) {
				$yOffset = 3;
			} else {
				$yOffset = -10;
			}
			if ($settings['card.title.beveling']) {
				if ($card->isEmblem()) {
					$titleLines[0] = trim($titleLines[0]);
					$titleLines[0] = $this->titleToSymbol($titleLines[0]);
					$titleLines[1] = trim($titleLines[1]);
					$titleLines[1] = $this->titleToSymbol($titleLines[1]);
					$card->setDisplayTitle($titleLines[1]);
					$symbolFont = '.symbol';
					$center = '.center';
				} 
				else {
					$titleLine = $this->titleToSymbol($titleCharacters);
					$card->setDisplayTitle($titleLine);
					$symbolFont = '.symbol';
					$center = '.center';
				}
			}
			$textWidth = $this->getTextWidth($card->getDisplayTitle(), $this->font("title$languageFont$symbolFont"));
			/*if ($textWidth > 667) {
				$titleLine = mb_wordwrap($titleCharacters, strlen($titleCharacters) * .65, '|');
				$titleLines = explode('|', $titleLine);
				if ($settings['card.title.beveling']) {
					$titleLines[0]  = $this->titleToSymbol($titleLines[0]);
					$titleLines[1]  = $this->titleToSymbol($titleLines[1]);
					$card->setDisplayTitle($titleLines[0]);
				}
			}*/
			if ($card->isEmblem()) {
				list($image, $width, $height) = getPNG("images/symbols/tokenborder/emblemcenter.png", "Image not found for: emblemcenter");
				$imgWidth = $textWidth;
				if ($imgWidth > 635) $imgWidth = 635;
				$imgWidthFull = $imgWidth + 72;
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
				list($image, $width, $height) = getPNG("images/symbols/tokenborder/emblemleft.png", "Image not found for: left");
				imagecopy($im,$image,0,0,0,0,$width,$height);
				imagedestroy($image);
				list($image, $width, $height) = getPNG("images/symbols/tokenborder/emblemright.png", "Image not found for: right");
				imagecopy($im,$image,ceil($imgWidthFull - $width - 2),0,0,0,$width,$height);
				imagedestroy($image);
			
				imagecopy($canvas, $im, ceil($settings['title.center.x'] - ($imgWidthFull / 2)), ceil($settings["title.y"] - ($imgHeight / 2)), 0, 0, $imgWidthFull, $imgHeight);
				imagedestroy($im);
				$this->drawText($canvas, $settings['title.center.x'], $settings["title$center.y"] + $yOffset - 22, 677, $titleLines[0], $this->font("emblem$languageFont$symbolFont"));
				$this->drawText($canvas, $settings['title.center.x'], $settings["title$center.y"] + $yOffset + 22, 677, $card->getDisplayTitle(), $this->font("title$languageFont$symbolFont"));
			} else {
			list($image, $width, $height) = getPNG("images/symbols/tokenborder/center.png", "Image not found for: center");
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
			list($image, $width, $height) = getPNG("images/symbols/tokenborder/left.png", "Image not found for: left");
			imagecopy($im,$image,5,0,0,0,$width,$height);
			imagedestroy($image);
			list($image, $width, $height) = getPNG("images/symbols/tokenborder/right.png", "Image not found for: right");
			imagecopy($im,$image,ceil($imgWidthFull - $width - 5),0,0,0,$width,$height);
			imagedestroy($image);
			
			imagecopy($canvas, $im, ceil($settings['title.center.x'] - ($imgWidthFull / 2)), ceil($settings["title.y"] - ($imgHeight / 2)), 0, 0, $imgWidthFull, $height);
			imagedestroy($im);
			
			//$this->drawTextWrapped($canvas, ($settings["title$center.y"] + $yOffset) / 2, $settings['title.center.x'] / 2, $settings['title.center.x'] + ($settings['title.center.x'] / 2), $card->getDisplayTitle(), $this->font("title$languageFont$symbolFont"));
			$this->drawText($canvas, $settings['title.center.x'], $settings["title$center.y"] + $yOffset, 677, $card->getDisplayTitle(), $this->font("title$languageFont$symbolFont"));
			}
	
			echo '.';
	
			// Type.
			$typex = ($frameDir == "transform-night" && $card->isArtefact()) ? $settings['type.art.x'] : $settings['type.x'];
			$card->type = str_replace('Token ', '', $card->type);
			$card->type = preg_replace('/([- ]{0,1}jeton)/u', '', $card->type);
			if ($card->isEmblem() === FALSE) {
				$this->drawText($canvas, $typex, $settings['type.y'], $rarityLeft - $settings['type.x'], $card->type, $this->font("type$languageFont"));
			}
	
			// Guild sign.
			if(($card->set == "DIS"||$card->set == "GPT"||$card->set == "RAV"||$card->set == "RTR"||$card->set == "GTC"||$card->set == "DGM"||in_array($card->set,$promoset) && $card->set != 'MGD') && !empty($card->legal) && !empty($card->flavor)) {
				if ($guild = $this->getGuild($card->title)) {
					list($image, $width, $height) = getPNG("images/token/guilds/$guild.png", "Guild image not found for: $guild");
					// Set the width of the area and height of the area
						$inputwidth = 646;
						$inputheight = 187;
						// Set the position we want the new image
						$position = 767;
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
			if(($card->set == "KTK"||$card->set == "FRF"||$card->set == "DTK"||in_array($card->set,$promoset) && $card->set != 'MGD') && !empty($card->legal) && !empty($card->flavor)) {
				if ($clan = $this->getClan($card->title)) {
					list($image, $width, $height) = getPNG("images/watermarks/clans/$clan.png", "Clan image not found for: $clan");
					// Set the width of the area and height of the area
						$inputwidth = 646;
						$inputheight = 187;
						// Set the position we want the new image
						$position = 767;
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
			
			// Quest sign.
			if($card->set == "THS"||$card->set == "BNG"||$card->set == "JOU"||$card->set == "HRO"||in_array($card->set,$promoset)) {
				if ($quest = $this->getQuest($card->title)) {
					list($image, $width, $height) = getPNG("images/watermarks/quests/$quest.png", "Quest image not found for: $quest");
					imagecopy($canvas, $image, 373 - ($width / 2), 652, 0, 0, $width, $height);
					imagecopy($canvas, $image, 373 - ($width / 2), 652, 0, 0, $width, $height); // Too much alpha, need to apply twice.
					imagedestroy($image);
				}
			}
	
			// Phyrexia/Mirran sign.
			if($card->set == "SOM"||$card->set == "MBS"||$card->set == "NPH"||in_array($card->set,$promoset) && $card->set != 'MGD') {
				if ($phyrexia = $this->getPhyrexia($card->title)) {
					list($image, $width, $height) = getPNG("images/watermarks/phyrexia/$phyrexia.png", "Phyrexia/Mirran image not found for: $phyrexia");
					imagecopy($canvas, $image, 373 - ($width / 2), 652, 0, 0, $width, $height);
					imagecopy($canvas, $image, 373 - ($width / 2), 652, 0, 0, $width, $height); // Too much alpha, need to apply twice.
					imagedestroy($image);
				}
			}
			
			// Promo overlay
			if ($card->promo && !$card->isBasicLand()) {
				list($image, $width, $height) = getPNG('images/promo/' . $card->promo . '.png', 'Promo overlay image not found for: ' . $card->promo);
				imagecopy($canvas, $image, 359 - ($width / 2), 680, 0, 0, $width, $height);
				imagedestroy($image);
			}
	
			// Legal and flavor text.
			$card->legal = preg_replace('/#\(.*?\)#/', '', $card->legal);
			if(strlen($card->legal) < 40 && strpos($card->legal, "\n") === FALSE && $card->flavor == '')
				$this->drawText($canvas, ($settings['text.right'] + $settings['text.left']) / 2, ($settings['text.top'] + $settings['text.bottom']) / 2, null, $card->legal, $this->font("text$languageFont", 'centerY:true centerX:true'));
			else {
				$heightAdjust = $card->pt ? $settings['text.pt.height.adjust'] : 0;
				$this->drawLegalAndFlavorText($canvas, $settings['text.top'], $settings['text.left'], $settings['text.bottom'], $settings['text.right'], $card->legal, $card->flavor, $this->font("text$languageFont"), $heightAdjust);
			}
			//}
	
			// Artist and copyright.
			// The artist color is white if the frame behind it is black.
			$footerColor = '0,0,0';
			if ($card->isLand())
				$footerColor = '255,255,255';
			else if (($costColors == 'B' || $card->color == 'B'/* || substr($card->color, 0, 1) == 'B')*/) && !$card->isArtefact())
				$footerColor = '255,255,255';
			/*else if ($card->isEldrazi() && $card->isArtefact())
				$footerColor = '255,255,255';*/
			else if ($card->isToken() && (strlen($card->color) <= 2 && substr($card->color, 0, 1) == 'B') || (strlen($card->color) >= 3 && substr($card->color, 2, 1) == 'B'))
					$footerColor = '255,255,255';
			else if ($useMulticolorFrame && $titleToFuse != $this->getFuse($card->title)) {
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
				$this->drawText($canvas, $settings['artist.x'], $settings['artist.y'], null, $artistSymbol . $card->artist, $this->font('artist', 'color:' . $footerColor));
			} else {
				if ($settings['card.artist.gears']) {
					$artistSymbol = '{gear}';
				} else {
					$artistSymbol = '{brush}';
				}
				$card->artist = 'Unknown';
				$this->drawText($canvas, $settings['artist.x'], $settings['artist.y'], null, $artistSymbol . $card->artist, $this->font('artist', 'color:' . $footerColor));
			}
			if ($card->copyright) $this->drawText($canvas, $settings['copyright.x'], $settings['copyright.y'], null, $card->copyright, $this->font('copyright', 'color:' . $footerColor));
	
			echo "\n";
			return $canvas;
		
		}
		// PreEighth Tokens
		else if ($frameDir == 'pre8thtoken') {
			$canvas = imagecreatetruecolor(736, 1050);
	
			// Art image.
			if ($config['art.use.xlhq.full.card'] != false && stripos($card->artFileName,'xlhq') != false) {
				$this->drawArt($canvas, $card->artFileName, $settings['art.xlhq.top'], $settings['art.xlhq.left'], $settings['art.xlhq.bottom'], $settings['art.xlhq.right'], !$config['art.keep.aspect.ratio']);
			}
			else if(($card->isEldrazi() && $card->isArtefact()) || ($card->isDevoid() && $card->isArtefact()) || (($frameDir == 'fullartbasicland') && $card->isBasicLand()) || ($card->set == "EXP" && $card->isLand()))
				$this->drawArt($canvas, $card->artFileName, 0, 0, 1050, 736, !$config['art.keep.aspect.ratio']);
			else
				$this->drawArt($canvas, $card->artFileName, $settings['art.top'], $settings['art.left'], $settings['art.bottom'], $settings['art.right'], !$config['art.keep.aspect.ratio']);
	
			echo '.';
	
			// Background image, border image, and grey title/type image.
			$borderImage = null;
			$greyTitleAndTypeOverlay = null;
			$colorTemp = $card->color;
				if (strlen($colorTemp) >= 3 && $colorTemp != 'Art') $colorTemp = 'Gld';
				if($card->isEldrazi() && $card->isArtefact()) {
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/Eldrazi.png");
				} else if ($card->color == 'Art' && strpos($card->englishType, 'Artifact') === FALSE) {
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/C.png");
				} else if ($card->isToken()){
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/$colorTemp.png");
				} else if ($card->isArtefact()) {
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/Art.png");
					if(strpos($settings['card.artifact.color'], strval(strlen($costColors))) !== false){
						if (strlen($costColors) >= 2) {
							$useMulticolorFrame = false;
							$borderImage = @imagecreatefrompng("images/token/$frameDir/borders/$costColors.png");
						}
						else {
							$useMulticolorFrame = false;
							$borderImage = @imagecreatefrompng("images/token/$frameDir/borders/$card->color.png");
						}
					} else if (strlen($costColors) >= 2 || $card->color == 'Gld') {
						$useMulticolorFrame = false;
						$borderImage = @imagecreatefrompng("images/token/$frameDir/borders/Gld.png");
					}
				} else if ($useMulticolorFrame) {
					// Multicolor frame.
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/$costColors.png");
					if (!$bgImage) error("Background image not found for color: $costColors");
					// Grey title/type image.
					if ($settings['card.multicolor.grey.title.and.type']) {
						$greyTitleAndTypeOverlay = @imagecreatefrompng("images/token/$frameDir/cards/C-overlay.png");
						if (!$greyTitleAndTypeOverlay) error('Image not found: C-overlay.png');
					}
				} else {
					// Mono color frame.
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/" . $colorTemp . '.png');
					if (!$bgImage) error('Background image not found for color "' . $colorTemp . '" in frame dir: ' . $frameDir);
					// Border image.
					if (strlen($costColors) == 2 && $settings['card.multicolor.dual.borders'])
						$borderImage = @imagecreatefrompng("images/token/$frameDir/borders/$costColors.png");
				}
			//}
			if (!$bgImage) error("Background image not found for color: $colorTemp");
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
	
			// Power / toughness.
			if ($card->pt) $this->drawText($canvas, $settings['pt.center.x'], $settings['pt.center.y'], $settings['pt.width'], $card->pt, $this->font('pt'));
	
			// Casting cost.
			$costLeft = $this->drawCastingCost($canvas, $card->getCostSymbols(), $card->isDualManaCost() ? $settings['cost.top.dual'] : $settings['cost.top'], $settings['cost.right'], $settings['cost.size'], true);
	
			echo '.';
	
			// Set and rarity.
			if ($card->set == "UGL" && $card->isBasicLand()) {
			$rarityLeft = $settings['rarity.right'];
			//$rarityMiddle = $settings['rarity.center.y'];
			//if ($card->isLand()) $rarityMiddle += 2; // Rarity on pre8th lands is slightly lower.
			//$rarityLeft = $this->drawRarity($canvas, $card->getDisplayRarity(), $card->getDisplaySet(), $settings['rarity.right'], $rarityMiddle, $settings['rarity.height'], $settings['rarity.width'], true, $settings['card.rarity.fallback']);
			}
			else if (!$card->isBasicLand() || $settings['card.basic.land.set.symbols']) {
				$rarityMiddle = $settings['rarity.center.y'];
				if ($card->isLand()) $rarityMiddle += 2; // Rarity on pre8th lands is slightly lower.
				$rarityLeft = $this->drawRarity($canvas, $card->getDisplayRarity(), $card->getDisplaySet(), $settings['rarity.right'], $rarityMiddle, $settings['rarity.height'], $settings['rarity.width'], true, $settings['card.rarity.fallback']);
			} 
			else {
				$rarityLeft = $settings['rarity.right'];
			}

			// Card title.
			if (empty($language)) $language = 'english';
			$token = csvToArray('data/tokenTranslated.csv');
			if ($card->title != 'Morph') $card->setDisplayTitle(mb_strtoupper($token[$language]));
			$this->drawText($canvas, $settings['title.center.x'], $settings["title.y"], null, $card->getDisplayTitle(), $this->font("title$languageFont"));
				
			echo '.';
	
			// Type.
			$typeBaseline = $settings['type.y'];
			$card->type = str_replace('Token ', '', $card->type);
			$card->type = preg_replace('/([- ]{0,1}jeton)/u', '', $card->type);
			$card->type = str_replace('衍生物', '', $card->type);
			$card->type = str_replace('spielstein', '', $card->type);
			$card->type = str_replace('pedina', '', $card->type);
			$card->type = str_replace('토큰', '', $card->type);
			$card->type = str_replace('ficha', '', $card->type);
			$card->type = str_replace('トークン', '', $card->type);
			$card->type = str_replace('фишку', '', $card->type);
			$this->drawText($canvas, $settings['type.x'], $typeBaseline, $rarityLeft - $settings['type.x'], $card->type, $this->font("type$languageFont"));
			
			// Promo overlay
			if ($card->promo && !$card->isBasicLand()) {
				list($image, $width, $height) = getPNG('images/promo/' . $card->promo . '.png', 'Promo overlay image not found for: ' . $card->promo);
				imagecopy($canvas, $image, 359 - ($width / 2), 680, 0, 0, $width, $height);
				imagedestroy($image);
			}
	
			// Legal and flavor text.
			//$card->legal = preg_replace('/#\(.*?\)#/', '', $card->legal);
			if(strlen(preg_replace('/#\(.*?\)#/', '', $card->legal)) < 40 && strpos($card->legal, "\n") === FALSE && $card->flavor == '')
				$this->drawText($canvas, ($settings['text.right'] + $settings['text.left']) / 2, ($settings['text.top'] + $settings['text.bottom']) / 2, null, $card->legal, $this->font("text$languageFont", 'centerY:true centerX:true'));
			else {
				$heightAdjust = $card->pt ? $settings['text.pt.height.adjust'] : 0;
				$this->drawLegalAndFlavorText($canvas, $settings['text.top'], $settings['text.left'], $settings['text.bottom'], $settings['text.right'], $card->legal, $card->flavor, $this->font("text$languageFont"), $heightAdjust);
			}
			//}
	
			// Artist and copyright.
			// The artist color is white if the frame behind it is black.
			if ($card->set == "UGL" && $card->isBasicLand()) {
			$this->drawText($canvas, 55, 1020, null, 'Illus. ' . $card->artist, $this->font('artist', 'centerX:false'));
			}
			else if ($card->artist) {
				$this->drawText($canvas, $settings['artist.x'], $settings['artist.y'], null, 'Illus. ' . $card->artist, $this->font('artist'));
			}
			else {
				$card->artist = 'Unknown';
				$this->drawText($canvas, $settings['artist.x'], $settings['artist.y'], null, 'Illus. ' . $card->artist, $this->font('artist'));
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
				$this->drawText($canvas, $settings['copyright.x'], $settings['copyright.y'], null, $card->copyright, $this->font('copyright', 'color:' . $copyrightColor));
			}
	
			echo "\n";
			return $canvas;
		
		}
		//M15 Tokens
		else {
			$canvas = imagecreatetruecolor(720, 1020);
	
			// Art image.
			if ($config['art.use.xlhq.full.card'] != false && stripos($card->artFileName,'xlhq') != false) {
				$this->drawArt($canvas, $card->artFileName, $settings['art.xlhq.top'], $settings['art.xlhq.left'], $settings['art.xlhq.bottom'], $settings['art.xlhq.right'], !$config['art.keep.aspect.ratio']);
			}
			else if(($card->isEldrazi() && $card->isArtefact()) || ($card->isDevoid() && $card->isArtefact()) || (($frameDir == 'fullartbasicland') && $card->isBasicLand()) || ($card->set == "EXP" && $card->isLand())||(stripos($card->englishType, 'Sliver') !== FALSE && $card->set != 'TPR') || $card->title == 'Spirit' && $card->pic == '1' && $card->set == 'EMA' || $card->title == 'Morph' || $card->title == 'Manifest' || $card->title == 'The Monarch') {
				$this->drawArt($canvas, $card->artFileName, 21, 17, 955, 703, !$config['art.keep.aspect.ratio']);
			} else {
				$this->drawArt($canvas, $card->artFileName, $settings['art.top'], $settings['art.left'], $settings['art.bottom'], $settings['art.right'], !$config['art.keep.aspect.ratio']);
			}
	
			echo '.';
			// Background image, border image, and grey title/type image.
			$borderImage = null;
			$colorTemp = $card->color;
				if (strlen($colorTemp) >= 3 && $colorTemp != 'Art') $colorTemp = 'Gld';
				if($card->isEldrazi() && $card->isArtefact() && !$card->isDevoid()||stripos($card->englishType, 'Sliver') !== FALSE && $card->set != 'TPR' || $card->title == 'Morph' || $card->title == 'Manifest' || stripos($card->englishType, 'Spirit') !== FALSE && $card->isArtefact() && $card->set == 'EMA') {//Eldrazi without color
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/Eldrazi.png");
				} else if ($card->title == 'Clue' && $card->isArtefact()) {
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/Clue.png");
				} else if ($card->title == 'The Monarch' && $card->isArtefact()) {
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/Monarch.png");
				} else if ($card->color == 'Art' && $card->isEmblem()) {
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/Emblem.png");
				} else if ($card->isDevoid() && $card->isArtefact() && (strlen($costColors) >= 2)) {//Devoid card with 2+ colors in casting cost
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/Devoid_Gld.png");
				} else if ($card->isDevoid() && $card->isArtefact()) {//Devoid card with ONE color in casting cost
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/Devoid_" . $costColors . '.png');
				} else if ($card->color == 'Art' && strpos($card->englishType, 'Artifact') === FALSE) {
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/C.png");
				} else if ($card->isArtefact()) {
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/Art.png");
					if (strpos($settings['card.artifact.color'], strval(strlen($colorTemp))) !== false && $colorTemp != 'Art'){ //Artifact with color
						if (strlen($colorTemp) >= 2 && $colorTemp != 'Gld' && $colorTemp != 'Art') {//Artifact with 2+ color
							$useMulticolorFrame = false;
							$costColorsTemp = $colorTemp;
							$borderImage = @imagecreatefrompng("images/token/$frameDir/borders/$costColorsTemp.png");
						}
						else {//Artifact with ONE color
							$useMulticolorFrame = false;
							$costColorsTemp = $colorTemp;
							$borderImage = @imagecreatefrompng("images/token/$frameDir/borders/$costColorsTemp.png");
						}
					} else if (strlen($colorTemp) >= 2 && $colorTemp != 'Art' || $card->color == 'Gld') {
						$useMulticolorFrame = false;
						$borderImage = @imagecreatefrompng("images/token/$frameDir/borders/Gld.png");
					}
				} else if ($useMulticolorFrame) {
					// Multicolor frame.
					$costColorsTemp = $colorTemp;
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/$costColorsTemp.png");
					if (!$bgImage) error("Background image not found for color: $colorTemp");
					// Grey title/type image.
					if ($settings['card.multicolor.grey.title.and.type']) {
						$greyTitleAndTypeOverlay = @imagecreatefrompng("images/token/$frameDir/cards/C-overlay.png");
						if (!$greyTitleAndTypeOverlay) error('Image not found: C-overlay.png');
					}
				} else {
					// Mono color frame.
					$bgImage = @imagecreatefrompng("images/token/$frameDir/cards/" . $colorTemp . '.png');
					if (!$bgImage) error('Background image not found for color "' . $colorTemp . '" in frame dir: ' . $frameDir);
					// Border image.
					if (strlen($costColors) == 2 && $settings['card.multicolor.dual.borders']) {
						$costColorsTemp = $colorTemp;
						$borderImage = @imagecreatefrompng("images/token/$frameDir/borders/$costColorsTemp.png");
					}
				}
		if (!$bgImage) error("Background image not found for color: $card->color");
		imagecopy($canvas, $bgImage, 0, 0, 0, 0, 720, 1020);
			imagedestroy($bgImage);
			if ($borderImage) {
				imagecopy($canvas, $borderImage, 0, 0, 0, 0, 720, 1020);
				imagedestroy($borderImage);
			}
	
			// God Stars Frame overlay
			if($card->set == "THS"||$card->set == "BNG"||$card->set == "JOU"||$card->set == "PRE"||$card->set == "REL") {
				if ($GodStars = $this->getGodStars($card->title)) {
					list($image) = getPNG("images/token/gods/$GodStars.png", "GodStars image not found for: $GodStars");
					imagecopy($canvas, $image, 0, 0 , 0, 0, 720, 1020);
					imagedestroy($image);
				}
			}
	
			// Power / toughness.
			if ($card->pt) {
				if($card->isEldrazi() && $card->isArtefact() || $card->isDevoid() && $card->isArtefact||stripos($card->englishType, 'Sliver') !== FALSE && $card->set != 'TPR') {
					$image = @imagecreatefrompng("images/token/$frameDir/pt/B.png");
				} else if ($card->color == 'Art' && !$card->isArtefact()) {
					$image = @imagecreatefrompng("images/token/$frameDir/pt/C.png");
				} else if ($card->color == 'Art') {
					$image = @imagecreatefrompng("images/token/$frameDir/pt/" . $card->color . '.png');
				} else if ($useMulticolorFrame) {
					$image = @imagecreatefrompng("images/token/$frameDir/pt/" . substr($costColors, -1, 1) . '.png');
				} else if ($card->isToken() && strlen($card->color) == 2) {
					$image = @imagecreatefrompng("images/token/$frameDir/pt/" . substr($card->color, -1, 1) . '.png');
				}else if ($card->isToken() && strlen($card->color) >= 3 && $colorTemp != 'Gld') {
					$image = @imagecreatefrompng("images/token/$frameDir/pt/" . substr($card->color, -1, 1) . '.png');
				} else {
					$image = @imagecreatefrompng("images/token/$frameDir/pt/" . $colorTemp . '.png');
				}
				if (!$image) error("Power/toughness image not found for color: $colorTemp");
				//imagecopy($canvas, $image, 0, 1020 - 162, 0, 0, 720, 162);
				imagecopy($canvas, $image, -1, 0, 0, 0, 720, 1020);
				imagedestroy($image);
				$this->drawText($canvas, $settings['pt.center.x'], $settings['pt.center.y'], $settings['pt.width'], $card->pt, $this->font('pt'));
			}
	
			//Transform P/T
			if($frameDir == "transform-day" && isset($pts[1]))
				$this->drawText($canvas, $settings['pt.transform.center.x'], $settings['pt.transform.center.y'], $settings['pt.transform.width'], $pts[1], $this->font('pt.transform'));
				
			if($frameDir == "transform-spark" && isset($pts[1]))
				$this->drawText($canvas, $settings['pt.transform.center.x'], $settings['pt.transform.center.y'], $settings['pt.transform.width'], $pts[1], $this->font('pt.transform'));
	
			// Casting cost.
			$costLeft = $this->drawCastingCost($canvas, $card->getCostSymbols(), $card->isDualManaCost() ? $settings['cost.top.dual'] : $settings['cost.top'], $settings['cost.right'], $settings['cost.size'], true);
	
			echo '.';
	
			// Set and rarity.
			if (!$card->isBasicLand() || $settings['card.basic.land.set.symbols']) {
				$rarityLeft = $this->drawRarity($canvas, $card->getDisplayRarity(), $card->getDisplaySet(), $settings['rarity.right'], $settings['rarity.center.y'], $settings['rarity.height'], $settings['rarity.width'], false);
			} else {
				$rarityLeft = $settings['rarity.right'];
				}
	
			// Card title.
			if (strpos($card->getDisplayTitle(), ':') && $card->isEmblem()) {
				$emblem = explode(':', $card->getDisplayTitle());
				$card->setDisplayTitle(trim($emblem[0]));
			}
			$this->drawText($canvas, $settings['title.center.x'], $settings['title.y'], 0, $card->getDisplayTitle(), $this->font("title$languageFont"));
	
			echo '.';
	
			// Type.
			$this->drawText($canvas, $settings['type.x'], $settings['type.y'], $rarityLeft - $settings['type.x'], $card->type, $this->font("type$languageFont"));
	
			// Guild sign.
			if(($card->set == "DIS"||$card->set == "GPT"||$card->set == "RAV"||$card->set == "RTR"||$card->set == "GTC"||$card->set == "DGM"||in_array($card->set,$promoset) && $card->set != 'MGD') && !empty($card->legal) && !empty($card->flavor)) {
				if ($guild = $this->getGuild($card->title)) {
					list($image, $width, $height) = getPNG("images/watermarks/guilds/$guild.png", "Guild image not found for: $guild");
					// Set the width of the area and height of the area
						$inputwidth = 632;
						$inputheight = 190;
						// Set the position we want the new image
						$position = 757;
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
			if(($card->set == "KTK"||$card->set == "FRF"||$card->set == "DTK"||in_array($card->set,$promoset) && $card->set != 'MGD') && !empty($card->legal) && !empty($card->flavor)) {
				if ($clan = $this->getClan($card->title)) {
					list($image, $width, $height) = getPNG("images/watermarks/clans/$clan.png", "Clan image not found for: $clan");
					// Set the width of the area and height of the area
						$inputwidth = 632;
						$inputheight = 190;
						// Set the position we want the new image
						$position = 757;
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
			
			// Quest sign.
			if(($card->set == "HRO"||$card->set == "PRE"||$card->set == "REL"||$card->set == "MGD") && !empty($card->legal) && !empty($card->flavor)) {
				if ($quest = $this->getQuest($card->title)) {
					list($image, $width, $height) = getPNG("images/watermarks/quests/$quest.png", "Quest image not found for: $quest");
					imagecopy($canvas, $image, 373 - ($width / 2), 652, 0, 0, $width, $height);
					imagecopy($canvas, $image, 373 - ($width / 2), 652, 0, 0, $width, $height); // Too much alpha, need to apply twice.
					imagedestroy($image);
				}
			}
	
			// Phyrexia/Mirran sign.
			if($card->set == "SOM"||$card->set == "MBS"||$card->set == "NPH") {
				if ($phyrexia = $this->getPhyrexia($card->title) && !empty($card->legal) && !empty($card->flavor)) {
					list($image, $width, $height) = getPNG("images/watermarks/phyrexia/$phyrexia.png", "Phyrexia/Mirran image not found for: $phyrexia");
					//PNG height = 280px
					imagecopy($canvas, $image, 360 - ($width / 2), 645, 0, 0, $width, $height);
					imagecopy($canvas, $image, 360 - ($width / 2), 645, 0, 0, $width, $height); // Too much alpha, need to apply twice.
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
	
				// Legal and flavor text.
				if ($card->pt) {
					if(strlen($card->legal) < 40 && strpos($card->legal, "\n") === FALSE && $card->flavor == '') {
						$this->drawText($canvas, ($settings['text.right'] + $settings['text.left']) / 2, ($settings['text.top'] + $settings['textPT.bottom']) / 2, null, $card->legal, $this->font("text$languageFont", 'centerY:true centerX:true'));
					} else {
						$heightAdjust = $card->pt ? $settings['text.pt.height.adjust'] : 0;
						$this->drawLegalAndFlavorText($canvas, $settings['text.top'], $settings['text.left'], $settings['textPT.bottom'], $settings['text.right'], $card->legal, $card->flavor, $this->font("text$languageFont"), $heightAdjust);
				}} else {
					if(strlen($card->legal) < 40 && strpos($card->legal, "\n") === FALSE && $card->flavor == '') {
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
			//}
	
			// Artist and copyright.
			// The artist color is white if the frame behind it is black.
			$footerColor = '255,255,255';
	
			if ($card->artist) {
				if ($settings['card.artist.gears']) {
					$artistSymbol = '{gear}';
				} else {
					$artistSymbol = '{brush2}';
				}
			} else {
				$card->artist = 'Unknown';
				if ($settings['card.artist.gears']) {
					$artistSymbol = '{gear}';
				} else {
					$artistSymbol = '{brush2}';
				}
			}
			//$this->drawText($canvas, $settings['artist.x'], $settings['artist.y'], null, $card->collectorNumber . ' ' . $card->rarity . "\r\n" . $card->set . ' ' . "&#8226;" . ' ' . $config['card.lang'] . ' ' . $artistSymbol . $card->artist, $this->font('artist', 'color:' . $footerColor));
			$lang = $config['card.lang'];
			if (empty($card->collectorNumber)) $card->collectorNumber = '0/0';

			$card->collectorNumber = str_replace("\\", '/', $card->collectorNumber);
			$collectorNumber = explode('/', $card->collectorNumber);
			$collectorNumber[0] = str_pad($collectorNumber[0], 3, "0", STR_PAD_LEFT);
			$collectorNumber[1] = str_pad($collectorNumber[1], 3, "0", STR_PAD_LEFT);
			$card->collectorNumber = implode('/', $collectorNumber);

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
				$CollectionTxtL2 = $card->set . ' ' . "&#8226;" . ' ' . $lang . ' ' ;
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
			} else if ($card->isBasicLand()) {
				$this->drawText($canvas, $settings['collection.x'] + ($lineSizeL2[2]), $settings['collection.y'], null, "L", $this->font('collection', 'color:' . $footerColor));
			} else if (($lineSizeL1[2] + 8) >= $lineSizeL2[2]) {
				$this->drawText($canvas, $settings['collection.x'] + ($lineSizeL1[2] + 8), $settings['collection.y'], null, $card->rarity, $this->font('collection', 'color:' . $footerColor));
			} else {
				$this->drawText($canvas, $settings['collection.x'] + ($lineSizeL2[2]), $settings['collection.y'], null, $card->rarity, $this->font('collection', 'color:' . $footerColor));
			}
			//$this->drawText($canvas, $settings['artist.x'], $settings['artist.y'], null, $card->artist, $this->font('artist', 'color:' . $footerColor));
			$this->drawText($canvas, $settings['collection.x'] + ($lineSizeL2[2] + $settings['artistOffset.x']), $settings['artist.y'], null, $card->artist, $this->font('artist', 'color:' . $footerColor));
			if ($card->copyright) {
				$CopyrightTxt = $card->copyright;
				$font = $settings['font.copyright'];
				$font = str_replace(' ','',$font);
				$arrayfont=explode(",",$font);
				$fontName = $arrayfont[1];
				$fontsize = $arrayfont[0];
				$lineSize = imagettfbbox($fontsize,0,"./fonts/$fontName",$CopyrightTxt);
				if ($card->pt) {
					//$this->drawText($canvas, $settings['copyright.x'], $settings['copyrightPT.y'], null, $card->copyright, $this->font('copyright', 'color:' . $footerColor));
					$this->drawText($canvas, (720 - ($lineSize[2] + $settings['copyrightLeft.x'])), $settings['copyrightPT.y'], null, $CopyrightTxt, $this->font('copyright', 'color:' . $footerColor));
				} else {
					//$this->drawText($canvas, $settings['copyright.x'], $settings['copyright.y'], null, $card->copyright, $this->font('copyright', 'color:' . $footerColor));
					$this->drawText($canvas, (720 - ($lineSize[2] + $settings['copyrightLeft.x'])), $settings['copyright.y'], null, $CopyrightTxt, $this->font('copyright', 'color:' . $footerColor));
				}
			}
			//Art overlay
			if ($card->isEmblem()) {
				$overlayFileName = preg_replace('/(.*)\.(jpg|png)/i','$1-overlay.png', $card->artFileName);
				if(file_exists($overlayFileName))
				$this->drawArt($canvas, $overlayFileName, $settings['art.top'], $settings['art.left'], $settings['art.bottom'], $settings['art.right'], !$config['art.keep.aspect.ratio']);
			}
	
		echo "\n";
		return $canvas;
		}
	}



	public function getSettings () {
		global $rendererSettings, $rendererSections;
		$settings = $rendererSettings['config/config-token.txt'];
		if ($this->card->isToken()) {
			$frameDir = $this->getFrameDir($this->card->title, $this->card->getDisplaySet(), $settings);
		} else $frameDir = $this->getFrameDir($this->card->title, $this->card->set, $settings);
		$settings = array_merge($settings, $rendererSections['config/config-token.txt']['fonts - ' . $frameDir]);
		$settings = array_merge($settings, $rendererSections['config/config-token.txt']['layout - ' . $frameDir]);
		return $settings;
	}

	private function getGuild ($title) {
		if (!TokenRenderer::$titleToGuild) TokenRenderer::$titleToGuild = csvToArray('data/eighth/titleToGuild.csv');
		return @TokenRenderer::$titleToGuild[(string)strtolower($title)];
	}
	
	private function getFuse ($title) {
		if(!TokenRenderer::$titleToFuse) TokenRenderer::$titleToFuse = csvToArray('data/eighth/titleToFuse.csv');
		return @TokenRenderer::$titleToFuse[(string)strtolower($title)];
	}
	
	private function getClan ($title) {
		if (!TokenRenderer::$titleToClan) TokenRenderer::$titleToClan = csvToArray('data/eighth/titleToClan.csv');
		return @TokenRenderer::$titleToClan[(string)strtolower($title)];
	}
	
	private function getQuest ($title) {
		if (!TokenRenderer::$titleToQuest) TokenRenderer::$titleToQuest = csvToArray('data/eighth/titleToQuest.csv');
		return @TokenRenderer::$titleToQuest[(string)strtolower($title)];
	}
	
	private function getGodStars ($title) {
		if (!TokenRenderer::$titleToGodStars) TokenRenderer::$titleToGodStars = csvToArray('data/eighth/titleToGodStars.csv');
		return @TokenRenderer::$titleToGodStars[(string)strtolower($title)];
	}

	private function getPhyrexia ($title) {
		if (!TokenRenderer::$titleToPhyrexia) TokenRenderer::$titleToPhyrexia = csvToArray('data/eighth/titleToPhyrexia.csv');
		return @TokenRenderer::$titleToPhyrexia[(string)strtolower($title)];
	}

	private function getIndicator ($title) {
		if (!TokenRenderer::$titleToIndicator) TokenRenderer::$titleToIndicator = csvToArray('data/eighth/titleToIndicator.csv');
		return @TokenRenderer::$titleToIndicator[(string)strtolower($title)];
	}
	
	private static function titleToSymbol ($title) {
				global $config;
				
				$language = strtolower($config['output.language']);
				if ($language && $language != 'english') {
					$mb = 1;
				} else $mb = 0;
				$titleCharacters = str_split_unicode($title, $mb);
				array_walk($titleCharacters, create_function('&$str', '$str = "{Img$str}";'));
				$titleCharacters = str_replace('&', 'ampersand', $titleCharacters);
				$titleCharacters = str_replace('\'', 'apostrophe', $titleCharacters);
				$titleCharacters = str_replace(',', 'comma', $titleCharacters);
				$titleCharacters = str_replace('-', 'dash', $titleCharacters);
				$titleCharacters = str_replace("'", 'apostrophe', $titleCharacters);
				$titleCharacters = str_replace("’", 'apostrophe', $titleCharacters);
				$titleCharacters = str_replace(':', 'colon', $titleCharacters);
				$titleCharacters = str_replace(' ', 'space', $titleCharacters);
				$titleCharacters = str_replace('Æ', 'ae', $titleCharacters);
				$titleCharacters = str_replace('À', 'GraveA', $titleCharacters);
				$titleCharacters = str_replace('È', 'GraveE', $titleCharacters);
				$titleCharacters = str_replace('Ì', 'GraveI', $titleCharacters);
				$titleCharacters = str_replace('Ò', 'GraveO', $titleCharacters);
				$titleCharacters = str_replace('Ù', 'GraveU', $titleCharacters);
				$titleCharacters = str_replace('Á', 'AcuteA', $titleCharacters);
				$titleCharacters = str_replace('É', 'AcuteE', $titleCharacters);
				$titleCharacters = str_replace('Í', 'AcuteI', $titleCharacters);
				$titleCharacters = str_replace('Ó', 'AcuteO', $titleCharacters);
				$titleCharacters = str_replace('Ú', 'AcuteU', $titleCharacters);
				$titleCharacters = str_replace('Ć', 'AcuteC', $titleCharacters);
				$titleCharacters = str_replace('Ý', 'AcuteY', $titleCharacters);
				$titleCharacters = str_replace('Ğ', 'BreveG', $titleCharacters);
				$titleCharacters = str_replace('Č', 'CaronC', $titleCharacters);
				$titleCharacters = str_replace('Š', 'CaronS', $titleCharacters);
				$titleCharacters = str_replace('Ž', 'CaronZ', $titleCharacters);
				$titleCharacters = str_replace('Ç', 'CedillaC', $titleCharacters);
				$titleCharacters = str_replace('Ş', 'CedillaS', $titleCharacters);
				$titleCharacters = str_replace('Â', 'CircumA', $titleCharacters);
				$titleCharacters = str_replace('Ê', 'CircumE', $titleCharacters);
				$titleCharacters = str_replace('Î', 'CircumI', $titleCharacters);
				$titleCharacters = str_replace('Ô', 'CircumO', $titleCharacters);
				$titleCharacters = str_replace('Û', 'CircumU', $titleCharacters);
				$titleCharacters = str_replace('Ä', 'DiaeresisA', $titleCharacters);
				$titleCharacters = str_replace('Ë', 'DiaeresisE', $titleCharacters);
				$titleCharacters = str_replace('Ï', 'DiaeresisI', $titleCharacters);
				$titleCharacters = str_replace('Ö', 'DiaeresisO', $titleCharacters);
				$titleCharacters = str_replace('Ü', 'DiaeresisU', $titleCharacters);
				$titleCharacters = str_replace('Ÿ', 'DiaeresisY', $titleCharacters);
				$titleCharacters = str_replace('İ', 'DotI', $titleCharacters);
				$titleCharacters = str_replace('Ð', 'Eth', $titleCharacters);
				$titleCharacters = str_replace('Œ', 'OE', $titleCharacters);
				$titleCharacters = str_replace('Å', 'RingA', $titleCharacters);
				$titleCharacters = str_replace('ß', 'SharpS', $titleCharacters);
				$titleCharacters = str_replace('Ł', 'StrokeL', $titleCharacters);
				$titleCharacters = str_replace('Ø', 'StrokeO', $titleCharacters);
				$titleCharacters = str_replace('Þ', 'Thorne', $titleCharacters);
				$titleCharacters = str_replace('Ã', 'TildeA', $titleCharacters);
				$titleCharacters = str_replace('Ñ', 'TildeN', $titleCharacters);
				$titleCharacters = str_replace('Õ', 'TildeO', $titleCharacters);
				$title = implode($titleCharacters);
				return $title;
			}

	private function getFrameDir ($title, $set, $settings) {
		global $config;
		
		if (!TokenRenderer::$titleToToken) TokenRenderer::$titleToToken = csvToArray('data/titleToToken.csv');
		$frameDir = @TokenRenderer::$titleToToken[(string)strtolower($title)];
		$m15Set = $this->setDB->isM15($set);
		$pre8thSet = $this->setDB->isPre8th($set);
		if (!$frameDir) $frameDir = "token";
		if ($frameDir == 'token' && $title == 'Clue') $frameDir = "m15clue";
		if ($frameDir == 'token' && $title == 'The Monarch') $frameDir = "monarch";
		if ($frameDir == 'token' && $this->card->isEnchantment()) $frameDir = "nyxstarstoken";
		if ($frameDir == 'nyxstarstoken' && $this->card->legal != "") $frameDir = "nyxstarstokentext";
		if ($frameDir == 'nyxstarstoken' && $m15Set != FALSE && $config['render.m15'] != FALSE) $frameDir = "m15nyxstarstoken";
		if ($frameDir == 'nyxstarstokentext' && $m15Set != FALSE && $config['render.m15'] != FALSE) $frameDir = "m15nyxstarstokentext";
		if ($frameDir == 'token' && $m15Set != FALSE && $config['render.m15'] != FALSE) $frameDir = "m15token";
		if ($frameDir == 'token' && $pre8thSet != FALSE && $config['render.preEighth'] != FALSE) $frameDir = "pre8thtoken";
		if ($frameDir == 'token' && $config['render.eighth'] == FALSE) $frameDir = "m15token";
		if ($frameDir == 'm15token' && $this->card->legal != "") $frameDir = "m15tokentext";
		if ($frameDir == 'token' && $this->card->legal != "") $frameDir = "tokentext";
		if ($frameDir == 'emblem' && $m15Set != FALSE && $config['render.m15'] != FALSE) $frameDir = "m15emblem";
		return $frameDir;
	}
	
}

?>
