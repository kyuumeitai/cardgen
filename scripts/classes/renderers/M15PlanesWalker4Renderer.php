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
class M15PlanesWalker4Renderer extends CardRenderer {
	static private $settingSections;
	static private $titleToTransform;
	static private $titleToFrameDir;

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
		$white = '255,255,255';

		$useMulticolorFrame = strlen($costColors) == 2;

		$canvas = imagecreatetruecolor(720, 1020);

		// Art image.
		if ($config['art.use.xlhq.full.card'] != false && stripos($card->artFileName,'xlhq') != false) {
			$this->drawArt($canvas, $card->artFileName, $settings['art.xlhq.top'], $settings['art.xlhq.left'], $settings['art.xlhq.bottom'], $settings['art.xlhq.right'], !$config['art.keep.aspect.ratio']);
		}
		else {
			$this->drawArt($canvas, $card->artFileName, $settings['art.top'], $settings['art.left'], $settings['art.bottom'], $settings['art.right'], !$config['art.keep.aspect.ratio']);
		}

		echo '.';

		// Background image.
		$borderImage = null;
		$greyTitleAndTypeOverlay = null;
		if ($card->isArtefact()) {
			$bgImage = @imagecreatefrompng('images/m15/planeswalker/regular/cards/Art4.png');
		} else if ($useMulticolorFrame || $card->isDualManaCost()) {
			// Multicolor frame.
			if($settings['card.multicolor.gold.frame']){
				//$bgImage = @imagecreatefrompng("images/m15/planeswalker/regular/cards/Gld$costColors4.png");
				$bgImage = @imagecreatefrompng('images/m15/planeswalker/regular/cards/Gld' . $costColors . '4.png');
			}else{
				$bgImage = @imagecreatefrompng('images/m15/planeswalker/regular/cards/' . $costColors . '4.png');
				}
			if (!$bgImage) error("Background image not found for color: $costColors");
		} else {
			// Mono color frame.
			$bgImage = @imagecreatefrompng('images/m15/planeswalker/regular/cards/' . $card->color . '4.png');
			if (!$bgImage) error('Background image not found for color "' . $card->color . '"');
		}

		imagecopy($canvas, $bgImage, 0, 0, 0, 0, 720, 1020);
		imagedestroy($bgImage);

		// Loyalty
		if ($card->pt) {
			$image = @imagecreatefrompng('images/m15/planeswalker/loyalty/LoyaltyBegin.png');
			if (!$image) error("Loyalty image not found");
			//imagecopy($canvas, $image, 605, 940, 0, 0, 127, 82);
			imagecopy($canvas, $image, 0, 0, 0, 0, 720, 1020);
			imagedestroy($image);
			$card->pt = (string)str_replace('/', '', $card->pt);
			$this->drawText($canvas, $settings['loyalty.starting.center.x'], $settings['loyalty.starting.center.y'], $settings['loyalty.starting.width'], $card->pt, $this->font('loyalty.starting', 'color:'.$white));
		}

		// Casting cost.
		$costLeft = $this->drawCastingCost($canvas, $card->getCostSymbols(), $card->isDualManaCost() ? $settings['cost.top.dual'] : $settings['cost.top'], $settings['cost.right'], $settings['cost.size'], true);

		echo '.';

		// Set and rarity.
		$rarityLeft = $this->drawRarity($canvas, $card->getDisplayRarity(), $card->getDisplaySet(), $settings['rarity.right'], $settings['rarity.center.y'], $settings['rarity.height'], $settings['rarity.width'], false);

		// Card title.
		$this->drawText($canvas, $settings['title.x'], $settings['title.y'], ($costLeft - 20) - $settings['title.x'], $card->getDisplayTitle(), $this->font("title$languageFont"));

		echo '.';

		// Type.
		$this->drawText($canvas, $settings['type.x'], $settings['type.y'], $rarityLeft - $settings['type.x'], $card->type, $this->font("type$languageFont"));

		// Legal text.
		//if (!preg_match_all('#((\+|-)?([0-9XYZ]+): )?(.*?)(?=$|[\+|-]?[0-9XYZ]+:)#s', $card->legal, $matches)) error('Missing legality change from legal text: ' . $card->title);
		$card->legal = str_replace(' : ', ': ', $card->legal);
		if (!preg_match_all('#((\+|-)?([0-9XYZ]+): )?(.*?)(?=$|[\+|-]?[0-9XYZ]+:)#sm', $card->legal, $Result)) error('Missing legality change from legal text: ' . $card->title);
		
		$Temp = Array();
		$matches = Array();
		$N = 0;
		$Size_1 = count($Result);
		$Size_2 = count($Result[0]);

		for ($i = 0; $i < $Size_2; $i++) {
			if (strlen($Result[0][$i]) > 1) {
				$Temp[$N] = $i;
				$N++;
			}
		}
		
		$Size_2 = count($Temp);
		for ($i = 0; $i < $Size_1; $i++) {
			for ($j = 0; $j < $Size_2; $j++) {
				$matches[$i][$j] = trim($Result[$i][$Temp[$j]]);
			}
		}
		
		//print_r($matches);

		$text_offset=0;
		if(strlen($matches[0][0])==0)
		{
			$text_offset=1;
		}

		$logaltyImage = null;
		$loyalty = array();
		
		//1
		$this->loyaltyIcon($matches[2][$text_offset+0], 1, $logaltyImage, $loyalty, $matches[3][$text_offset+0]);;
		if($logaltyImage != null) {
			imagecopy($canvas, $logaltyImage, 0, $loyalty['y'], 0, 0, 120, 120);
			imagedestroy($logaltyImage);
		}

		$this->drawText($canvas, $settings['loyalty.1.center.x'], $settings['loyalty.1.center.y'], $settings['loyalty.1.center.width'], ((!empty($matches[2][$text_offset+0]))?preg_replace('/([+|-])/', '{\\1}', $matches[2][$text_offset+0]):"\037").$matches[3][$text_offset+0], $this->font('loyalty.change', 'color:'.$white));
		$this->drawLegalAndFlavorText($canvas, $settings['text.1.top'], $settings['text.1.left'], $settings['text.1.bottom'], $settings['text.1.right'], $matches[4][$text_offset+0], null, $this->font("text$languageFont"), 0);

		//2
		$this->loyaltyIcon($matches[2][$text_offset+1], 2,  $logaltyImage, $loyalty, $matches[3][$text_offset+1]);
		if($logaltyImage != null) {
			imagecopy($canvas, $logaltyImage, 0, $loyalty['y'], 0, 0, 120, 120);
			imagedestroy($logaltyImage);
		}

		$this->drawText($canvas, $settings['loyalty.2.center.x'], $settings['loyalty.2.center.y'], $settings['loyalty.2.center.width'], ((!empty($matches[2][$text_offset+1]))?preg_replace('/([+|-])/', '{\\1}', $matches[2][$text_offset+1]):"\037").$matches[3][$text_offset+1], $this->font('loyalty.change', 'color:'.$white));
		$this->drawLegalAndFlavorText($canvas, $settings['text.2.top'], $settings['text.2.left'], $settings['text.2.bottom'], $settings['text.2.right'], $matches[4][$text_offset+1], null, $this->font("text$languageFont"), 0);

		//3
		$this->loyaltyIcon($matches[2][$text_offset+2], 3,  $logaltyImage, $loyalty, $matches[3][$text_offset+2]);
		if($logaltyImage != null) {
			imagecopy($canvas, $logaltyImage, 0, $loyalty['y'], 0, 0, 120, 120);
			imagedestroy($logaltyImage);
		}

		$this->drawText($canvas, $settings['loyalty.3.center.x'], $settings['loyalty.3.center.y'], $settings['loyalty.3.center.width'], ((!empty($matches[2][$text_offset+2]))?preg_replace('/([+|-])/', '{\\1}', $matches[2][$text_offset+2]):"\037").$matches[3][$text_offset+2], $this->font('loyalty.change', 'color:'.$white));
		$this->drawLegalAndFlavorText($canvas, $settings['text.3.top'], $settings['text.3.left'], $settings['text.3.bottom'], $settings['text.3.right'], $matches[4][$text_offset+2], null, $this->font("text$languageFont"), 0);

		//4
		$this->loyaltyIcon($matches[2][$text_offset+3], 4,  $logaltyImage, $loyalty, $matches[3][$text_offset+3]);
		if($logaltyImage != null) {
			imagecopy($canvas, $logaltyImage, 0, $loyalty['y'], 0, 0, 120, 120);
			imagedestroy($logaltyImage);
		}

		$this->drawText($canvas, $settings['loyalty.4.center.x'], $settings['loyalty.4.center.y'], $settings['loyalty.4.center.width'], ((!empty($matches[2][$text_offset+3]))?preg_replace('/([+|-])/', '{\\1}', $matches[2][$text_offset+3]):"\037").$matches[3][$text_offset+3], $this->font('loyalty.change', 'color:'.$white));
		$this->drawLegalAndFlavorText($canvas, $settings['text.4.top'], $settings['text.4.left'], $settings['text.4.bottom'], $settings['text.4.right'], $matches[4][$text_offset+3], null, $this->font("text$languageFont"), 0);

		// Artist and copyright.
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
			$this->drawText($canvas, $settings['collection.x'], $settings['collection.y'], null, $CollectionTxtL1 . "\r\n" . $CollectionTxtL2 . $artistSymbol, $this->font('collection', 'color:' . $footerColor));
			if (($lineSizeL1[2] + 8) >= $lineSizeL2[2]) {
				$this->drawText($canvas, $settings['collection.x'] + ($lineSizeL1[2] + 8), $settings['collection.y'], null, $card->rarity, $this->font('collection', 'color:' . $footerColor));
			} else {
				$this->drawText($canvas, $settings['collection.x'] + ($lineSizeL2[2]), $settings['collection.y'], null, $card->rarity, $this->font('collection', 'color:' . $footerColor));
			}
			$this->drawText($canvas, $settings['collection.x'] + ($lineSizeL2[2] + $settings['artistOffset.x']), $settings['artist.y'], null, $card->artist, $this->font('artist', 'color:' . $footerColor));
		}
		if ($card->copyright) {
			$CopyrightTxt = $card->copyright;
			$font = $settings['font.copyright'];
			$font = str_replace(' ','',$font);
			$arrayfont=explode(",",$font);
			$fontName = $arrayfont[1];
			$fontsize = $arrayfont[0];
			$lineSize = imagettfbbox($fontsize,0,"./fonts/$fontName",$CopyrightTxt);
			$this->drawText($canvas, (720 - ($lineSize[2] + $settings['copyrightLeft.x'])), $settings['copyrightPT.y'], null, $CopyrightTxt, $this->font('copyright', 'color:' . $footerColor));
		}		
		
		//Art overlay
		$overlayFileName = preg_replace('/(.*)\.(jpg|png)/i','$1-overlay.png', $card->artFileName);
		if(file_exists($overlayFileName))
			$this->drawArt($canvas, $overlayFileName, $settings['art.top'], $settings['art.left'], $settings['art.bottom'], $settings['art.right'], !$config['art.keep.aspect.ratio']);

		echo "\n";
		return $canvas;
	}

	private function loyaltyIcon($sign, $slotNumber, &$logaltyImage, &$loyalty, $valor){
		if ($sign == '+'){
			$logaltyImage = @imagecreatefrompng('images/m15/planeswalker/loyalty/LoyaltyUp.png');
			switch($slotNumber){
				case 1:
					$loyalty['y'] = 555;
					break;
				case 2:
					$loyalty['y'] = 647;
					break;
				case 3:
					$loyalty['y'] = 739;
					break;
				case 4:
					$loyalty['y'] = 819;
					break;
			}
		} else if ($sign == '-'){
			$logaltyImage = @imagecreatefrompng('images/m15/planeswalker/loyalty/LoyaltyDown.png');
			switch($slotNumber){
				case 1:
					$loyalty['y'] = 555;
					break;
				case 2:
					$loyalty['y'] = 647;
					break;
				case 3:
					$loyalty['y'] = 739;
					break;
				case 4:
					$loyalty['y'] = 819;
					break;
			}
		} else {
			if($valor == '')
				$logaltyImage = null;
			else
				$logaltyImage = @imagecreatefrompng('images/m15/planeswalker/loyalty/LoyaltyZero.png');
			switch($slotNumber){
				case 1:
					$loyalty['y'] = 555;
					break;
				case 2:
					$loyalty['y'] = 647;
					break;
				case 3:
					$loyalty['y'] = 739;
					break;
				case 4:
					$loyalty['y'] = 819;
					break;
			}
		}

	}

	//public function getSettings () {
		//global $rendererSettings;
		//return $rendererSettings['config/config-m15planeswalker4.txt'];
	//}
	
	public function getSettings () {
		global $rendererSettings, $rendererSections;
		$settings = $rendererSettings['config/config-m15planeswalker4.txt'];
		$frameDir = $this->getFrameDir($this->card->title, $this->card->set, $settings);
		$settings = array_merge($settings, $rendererSections['config/config-m15planeswalker4.txt']['fonts - ' . $frameDir]);
		$settings = array_merge($settings, $rendererSections['config/config-m15planeswalker4.txt']['layout - ' . $frameDir]);
		return $settings;
	}

	private function getFrameDir ($title, $set, $settings) {
		if (!M15PlanesWalker4Renderer::$titleToTransform) M15PlanesWalker4Renderer::$titleToTransform = csvToArray('data/titleToTransform.csv');
			$frameDir = 'transform-' . @M15PlanesWalker4Renderer::$titleToTransform[(string)strtolower($title)];
			if (!$frameDir || $frameDir == 'transform-') $frameDir = "regular";
		return $frameDir;
	}
	
}

?>