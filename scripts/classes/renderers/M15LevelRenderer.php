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
class M15LevelRenderer extends CardRenderer {
	static private $settingSections;
	static private $titleToGuild;
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

		$promoset = explode(',',$config['card.promo.symbols']);
		// Background image.
		$borderImage = null;
		$greyTitleAndTypeOverlay = null;
		if ($card->isArtefact()) {
			$bgImage = @imagecreatefrompng('images/m15/leveler/cards/Art.png');
		} else if ($useMulticolorFrame || $card->isDualManaCost()) {
			// Multicolor frame.
			if($settings['card.multicolor.gold.frame'])
				$bgImage = @imagecreatefrompng("images/m15/leveler/cards/Gld$costColors".".png");
			else
				$bgImage = @imagecreatefrompng("images/m15/leveler/cards/$costColors".".png");
			if (!$bgImage) error("Background image not found for color: $costColors");
		} else {
			// Mono color frame.
			$bgImage = @imagecreatefrompng('images/m15/leveler/cards/' . $card->color . '.png');
			if (!$bgImage) error('Background image not found for color "' . $card->color . '"');
		}

		imagecopy($canvas, $bgImage, 0, 0, 0, 0, 720, 1020);
		imagedestroy($bgImage);

		// Casting cost.
		$costLeft = $this->drawCastingCost($canvas, $card->getCostSymbols(), $card->isDualManaCost() ? $settings['cost.top.dual'] : $settings['cost.top'], $settings['cost.right'], $settings['cost.size'], true);

		echo '.';

		// Set and rarity.
		$rarityLeft = $this->drawRarity($canvas, $card->getDisplayRarity(), $card->getDisplaySet(), $settings['rarity.right'], $settings['rarity.center.y'], $settings['rarity.height'], $settings['rarity.width'], false);

		// Card title.
		$this->drawText($canvas, $settings['title.x'], $settings['title.y'], $costLeft - $settings['title.x'], $card->getDisplayTitle(), $this->font("title$languageFont"));

		echo '.';

		// Type.
		$this->drawText($canvas, $settings['type.x'], $settings['type.y'], $rarityLeft - $settings['type.x'], $card->type, $this->font("type$languageFont"));

		// Legal text.
		$card->legal = str_replace("-----\n", '', $card->legal);
		if(!preg_match_all('/(.*?)\r?\n(.*?) ([0-9\-\+]+?)\r?\n([0-9\*\-\+\/]{3,})\r?\n(.*?)\r?\n?((?<=\n)[^\s]*?(?=\s)) ([0-9\-\+]+?)\r?\n([0-9\*\-\+\/]{3,})\r?\n?(.*?)$/su', $card->legal, $matches))
			error('Wrong format for legal text: ' . $card->title);

		
		//1
		$this->drawText($canvas, $settings['pt.1.center.x'], $settings['pt.1.center.y'], $settings['pt.1.width'], $card->pt, $this->font('pt'));
		$this->drawLegalAndFlavorText($canvas, $settings['text.1.top'], $settings['text.1.left'], $settings['text.1.bottom'], $settings['text.1.right'], $matches[1][0], null, $this->font("text$languageFont"), 0);

		//2
		$this->drawText($canvas, $settings['pt.2.center.x'], $settings['pt.2.center.y'], $settings['pt.2.width'], $matches[4][0], $this->font('pt'));
		$this->drawText($canvas, $settings['name.2.center.x'], $settings['name.2.center.y'], $settings['name.2.width'], $matches[2][0], $this->font("pt$languageFont", 'glow:false'));
		$this->drawText($canvas, $settings['level.2.center.x'], $settings['level.2.center.y'], $settings['level.2.width'], $matches[3][0], $this->font('level', 'glow:false'));
		$this->drawLegalAndFlavorText($canvas, $settings['text.2.top'], $settings['text.2.left'], $settings['text.2.bottom'], $settings['text.2.right'], $matches[5][0], null, $this->font("text$languageFont"), 0);

		//3
		$this->drawText($canvas, $settings['pt.3.center.x'], $settings['pt.3.center.y'], $settings['pt.3.width'], $matches[8][0], $this->font('pt'));
		$this->drawText($canvas, $settings['name.3.center.x'], $settings['name.3.center.y'], $settings['name.3.width'], $matches[6][0], $this->font("pt$languageFont", 'glow:false'));
		$this->drawText($canvas, $settings['level.3.center.x'], $settings['level.3.center.y'], $settings['level.3.width'], $matches[7][0], $this->font('level', 'glow:false'));
		$this->drawLegalAndFlavorText($canvas, $settings['text.3.top'], $settings['text.3.left'], $settings['text.3.bottom'], $settings['text.3.right'], $matches[9][0], null, $this->font("text$languageFont"), 10);


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
			//$promoset = explode(',',$config['card.promo.symbols']);
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
			/*if (in_array($card->set,$promoset) !=false) {
				$lineSizeL2 = imagettfbbox($fontsizePromo,0,"./fonts/$fontNamePromo",$CollectionTxtL2);
			} else {*/
			$lineSizeL2 = imagettfbbox($fontsize,0,"./fonts/$fontName",$CollectionTxtL2);
				/*}*/
			
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
			//$this->drawText($canvas, $settings['artist.x'], $settings['artist.y'], null, $card->artist, $this->font('artist', 'color:' . $footerColor));
			$this->drawText($canvas, $settings['collection.x'] + ($lineSizeL2[2] + $settings['artistOffset.x']), $settings['artist.y'], null, $card->artist, $this->font('artist', 'color:' . $footerColor));
		}
		if ($card->copyright) {
			$CopyrightTxt = $config['card.copyright.m15'];;
			$font = $settings['font.copyright'];
			$font = str_replace(' ','',$font);
			$arrayfont=explode(",",$font);
			$fontName = $arrayfont[1];
			$fontsize = $arrayfont[0];
			$lineSize = imagettfbbox($fontsize,0,"./fonts/$fontName",$CopyrightTxt);
			/*if ($card->pt) {
				//$this->drawText($canvas, $settings['copyright.x'], $settings['copyrightPT.y'], null, $card->copyright, $this->font('copyright', 'color:' . $footerColor));
				$this->drawText($canvas, (720 - ($lineSize[2] + $settings['copyrightLeft.x'])), $settings['copyrightPT.y'], null, $CopyrightTxt, $this->font('copyright', 'color:' . $footerColor));
			} else {*/
				//$this->drawText($canvas, $settings['copyright.x'], $settings['copyright.y'], null, $card->copyright, $this->font('copyright', 'color:' . $footerColor));
				$this->drawText($canvas, (720 - ($lineSize[2] + $settings['copyrightLeft.x'])), $settings['copyright.y'], null, $CopyrightTxt, $this->font('copyright', 'color:' . $footerColor));
			//}
		}

		echo "\n";
		return $canvas;
	}

	public function getSettings () {
		global $rendererSettings;
		return $rendererSettings['config/config-m15level.txt'];
	}

}

?>
