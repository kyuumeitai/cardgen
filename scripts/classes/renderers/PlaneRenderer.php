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
class PlaneRenderer extends CardRenderer {
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

		$canvas = imagecreatetruecolor(1050, 736);

		// Art image.
		if ($config['art.use.xlhq.full.card'] != false && stripos($card->artFileName,'xlhq') != false) {
			$this->drawArt($canvas, $card->artFileName, $settings['art.xlhq.top'], $settings['art.xlhq.left'], $settings['art.xlhq.bottom'], $settings['art.xlhq.right'], !$config['art.keep.aspect.ratio']);
		}
		else {
			$this->drawArt($canvas, $card->artFileName, $settings['art.top'], $settings['art.left'], $settings['art.bottom'], $settings['art.right'], !$config['art.keep.aspect.ratio']);
		}

		echo '.';

		// Background image.
		if ($card->englishType == "Phenomenon"){
		$bgImage = @imagecreatefrompng('images/plane/phenomenon.png');
		}
		else {
		$bgImage = @imagecreatefrompng('images/plane/plane.png');
		}
		imagecopy($canvas, $bgImage, 0, 0, 0, 0, 1050, 736);
		imagedestroy($bgImage);

		echo '.';

		// Set and rarity.
		$rarityLeft = $this->drawRarity($canvas, $card->getDisplayRarity(), $card->getDisplaySet(), $settings['rarity.right'], $settings['rarity.center.y'], $settings['rarity.height'], $settings['rarity.width'], false);

		// Card title.
		$this->drawText($canvas, $settings['title.x'], $settings['title.y'], null, $card->getDisplayTitle(), $this->font("title$languageFont"));

		echo '.';

		// Type.
		$this->drawText($canvas, $settings['type.x'], $settings['type.y'], null, $card->type, $this->font("type$languageFont"));

		//Chaos Symbol
		if ($card->englishType != "Phenomenon") {
			$this->drawSymbol($canvas, 632, 115, 50, 'CHAOS', null);
			}

		// Legal text.
		$card->legal = str_replace('AE', 'Æ', $card->legal);
		if ($card->englishType == "Phenomenon"){
			if(strlen($card->legal) < 40 && strpos($card->legal, "\n") === FALSE && $card->flavor == '')
				$this->drawText($canvas, ($settings['text.right'] + $settings['text.left']) / 2, ($settings['text.top'] + $settings['text.bottom']) / 2, null, $card->legal, $this->font("text$languageFont", 'centerY:true centerX:true'));
			else {
				$heightAdjust = $card->pt ? $settings['text.pt.height.adjust'] : 0;
				$this->drawLegalAndFlavorText($canvas, $settings['text.top'], $settings['text.left'], $settings['chaos.bottom'], $settings['text.right'], $card->legal, $card->flavor, $this->font("text$languageFont"), $heightAdjust);
				}
			}
		else {
			if(!preg_match('/(.*\n)(.*?)$/s', $card->legal, $matches)) error('Missing chaos ability from legal text: ' . $card->title);
			$this->drawLegalAndFlavorText($canvas, $settings['text.top'], $settings['text.left'], $settings['text.bottom'], $settings['text.right'], $matches[1], null, $this->font("text$languageFont"), 0);
			$this->drawLegalAndFlavorText($canvas, $settings['chaos.top'], $settings['chaos.left'], $settings['chaos.bottom'], $settings['chaos.right'], $matches[2], null, $this->font("text$languageFont"), 0);
			}
			
		// Artist and copyright.
		// The artist color is white if the frame behind it is black.
		$footerColor = '255,255,255';
		if ($card->artist) {
			if ($settings['card.artist.gears']) {
				$artistSymbol = '{gear}';
			} else {
				$artistSymbol = '{brush}';
			}
			$this->drawText($canvas, $settings['artist.x'], $settings['artist.y'], null, $artistSymbol . $card->artist, $this->font('artist', 'color:' . $footerColor));
		}
		if ($card->copyright) $this->drawText($canvas, $settings['copyright.x'], $settings['copyright.y'], null, $card->copyright, $this->font('copyright', 'color:' . $footerColor));

		echo "\n";
		return $canvas;
	}

		public function getSettings() {
		global $rendererSettings;

		return $rendererSettings['config/config-plane.txt'];
	}
}

?>
