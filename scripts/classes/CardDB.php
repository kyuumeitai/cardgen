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
class CardDB {
	private $setDB;
	private $artDB;
	private $titleToCards = array();
	private $titleToTokens = array();
	private $titleToFlavors = array();
	private $titleToLocalizedFlavors = array();
	private $promoSet = array();

	public function __construct (SetDB $setDB, ArtDB $artDB) {
		global $config;

		$this->setDB = $setDB;
		$this->artDB = $artDB;
		
		$promoSet = explode(',', $config['card.promo.symbols']);

		// Load english cards.
		if (!$config['card.load.token.only']) {
			echo 'Loading card data';
			$file = fopen_utf8('data/cards.csv', 'r');
			if (!$file) error('Unable to open file: data/cards.csv');
			$i = 0;
			while (($row = fgetcsv($file, 10000, ',')) !== FALSE) {
				if ($i++ % 400 == 0) echo '.';
				
				$card = CardDB::rowToCard($row);
				// Ignore cards with an unknown set.
				$card->set = $setDB->normalize($card->set);
	
				if (!$card->set) continue;
				
				if ($config['card.corrected.promo.symbol'] != FALSE && in_array($card->set, $promoSet)) {
					$card = CardDB::correctPromoSymbols($card);
				}
	
				$title = mb_strtolower($card->title);
				if (!@$this->titleToCards[$title]) $this->titleToCards[$title] = array();
				$this->titleToCards[(string)$title][] = $card;
			}
			fclose($file);
			echo "\n";
		}
		// Load foreign card data.
		$language = strtolower($config['output.language']);
		if (!$config['card.load.token.only']) {
			if ($language && $language != 'english') {
				echo "Loading $language card data";
				$file = fopen_utf8("data/cards-$language.csv", 'r');
				if (!$file) error("Unable to open file: data/cards-$language.csv");
				$i = 0;
				while (($row = fgetcsv($file, 6000, ',')) !== FALSE) {
					if ($i++ % 10000 == 0) echo '.';
					// Overwrite some of the english card values with the foreign values.
					$englishTitle = mb_strtolower($row[0]);
	
					$cards = @$this->titleToCards[(string)$englishTitle];
					if (!$cards){
						//print_r($row);
						echo "\n";
						warn("Error matching card data for card: $row[0]");
						continue; // Skip errors
						}
					foreach ($cards as $card)
						CardDB::applyLanguageRowToCard($row, $card);
				}
				fclose($file);
				echo "\n";
				if (!$config['output.english.flavor.text']) {
					echo "Loading $language card flavor data";
					$file = fopen_utf8("data/cards-$language-flavor.csv", 'r');
					if (!$file)
						echo "\nNo localized flavor for language: $language";
					else {
						$i = 0;
						while (($row = fgetcsv($file, 6000, ',')) !== FALSE) {
							if ($i++ % 400 == 0) echo '.';
							// Overwrite some of the english card values with the foreign values.
							$englishTitle = mb_strtolower((string)$row[0]);
							$cards = @$this->titleToCards[(string)$englishTitle];
							if (!$cards) {
								$cards = @$this->titleToCards[transliterateString((string)$englishTitle)];
								//$englishTitle = transliterateString(strtolower($row[0]));
							}
							if (!$cards){
								//print_r($row);
								//xdebug_break();
								echo "\n";
								warn("Error matching card data for card: $row[0] [$row[1]]");
								continue; // Skip errors.
								}
							// Find the card from needed edition and apply localized flavor.
							foreach ($cards as $card) {
								if ($card->set == $setDB->normalize($row[1]) && @$card->pic == @$row[3]) {
									$card->flavor = $row[2];
									if (!@$this->titleToLocalizedFlavors[$title]) $this->titleToLocalizedFlavors[$title] = array(); // Add flavor to localized flavors for possible randomization..
									$this->titleToLocalizedFlavors[(string)$title][] = $card;
									break;
								}
							}
						}
						fclose($file);
						echo "\n";
					}
				}
			}
		}
		// Load token data.
		if ($config['render.token']) {
		echo 'Loading token data';
		if ($language && $language != 'english') {
			echo "\nLoading $language token data";
		}
		$file = fopen_utf8('data/tokens.csv', 'r');
		if (!$file) error('Unable to open file: data/tokens.csv');
		$i = 0;
		while (($row = fgetcsv($file, 6000, ',')) !== FALSE) {
			if ($i++ % 400 == 0) echo '.';

			$card = CardDB::rowToCard($row);
			// Ignore cards with an unknown set.
			$card->set = $setDB->normalize($card->set);

			if (!$card->set) continue;
			
			if ($config['card.corrected.promo.symbol'] != FALSE && in_array($card->set, $promoSet)) {
				$card = CardDB::correctPromoSymbols($card);
			}
			
			if ($language && $language != 'english') {
				$this->tokenToLanguage($card, $language, $setDB, 'tokens');
			}
			$title = strtolower($card->title);
			if (!@$this->titleToCards[$title]) $this->titleToCards[$title] = array();
			$this->titleToCards[(string)$title][] = $card;
			/*if (!@$this->titleToTokens[$title]) $this->titleToTokens[$title] = array();
			$this->titleToTokens[(string)$title][] = $card;*/
		}
		fclose($file);
		echo "\n";		
		}
		// Load emblem data.
		if ($config['render.emblem']) {
		echo 'Loading emblem data';
		$file = fopen_utf8('data/emblems.csv', 'r');
		if (!$file) error('Unable to open file: data/emblems.csv');
		$i = 0;
		while (($row = fgetcsv($file, 6000, ',')) !== FALSE) {
			if ($i++ % 400 == 0) echo '.';

			$card = CardDB::rowToCard($row);
			// Ignore cards with an unknown set.
			$card->set = $setDB->normalize($card->set);

			if (!$card->set) continue;
			
			if ($config['card.corrected.promo.symbol'] != FALSE && array_key_exists($card->set, $promoSet)) {
				$card = CardDB::correctPromoSymbols($card);
			}
			
			if ($language && $language != 'english') {
				$this->tokenToLanguage($card, $language, $setDB, 'emblems');
			}
			$title = strtolower($card->title);
			if (!@$this->titleToCards[$title]) $this->titleToCards[$title] = array();
			$this->titleToCards[(string)$title][] = $card;
			/*if (!@$this->titleToTokens[$title]) $this->titleToTokens[$title] = array();
			$this->titleToTokens[(string)$title][] = $card;*/
		}
		fclose($file);
		echo "\n";		
		}
		/*foreach ($this->titleToTokens as $title => $card) {
			if (!@$this->titleToCards[$title]) $this->titleToCards[$title][] = $card;
			else $this->titleToCards[$title][] = $card;
		}*/
	}
	
	public static function correctPromoSymbols ($card) {
		$file = fopen_utf8("data/titleToAltSet.csv", 'r');
		if (!$file) error("Unable to open file: data/titleToAltSet.csv");
		$i = 0;
		while (($row = fgetcsv($file, 6000, ',')) !== FALSE) {
			// Overwrite promo card data with correct symbol.
			$englishTitle = strtolower($row[0]);
			$promoSetKey = strtolower($row[1]);
			$promoPicKey = strtolower($row[2]);
    
		if ((strtolower($card->title) == $englishTitle) && (strtolower($card->set) == $promoSetKey) && (strtolower($card->pic) == $promoPicKey || empty($promoPicKey))) {
			CardDB::applyCorrectedPromoSymbolToCard($row, $card);
			return $card;
			}
		}
		return $card;
	}
	

	public function getCard ($title, $set = null, $pic = null) {
		global $config;

		$cards = @$this->titleToCards[(string)strtolower($title)];
		//file_put_contents('filename.txt', var_export($this->titleToCards, true));
		if (!$cards) return null;
		if (!$set) {
			if ($config['card.set.random']) {
				// Pick a random set.
				$sets = array();
				foreach ($cards as $card)
					$sets[$card->set] = true;
				$set = array_rand($sets);
			} else {
				// Find earliest set.
				$lowest = 999999;
				foreach ($cards as $card) {
					$ordinal = $this->setDB->getOrdinal($card->set);
					if ($ordinal < $lowest) {
						$lowest = $ordinal;
						$set = $card->set;
					}
				}
			}
		} else
			$set = $this->setDB->normalize($set);

		$chosenCard = null;
		if ($pic || $title == 'assembly-worker' && $set == 'TSP' && !$card->isToken()) {
			// Find the specific picture number.
			foreach ($cards as $card) {
				if ($card->set != $set) continue;
				if ($card->pic == $pic) {
					$chosenCard = $card;
					break;
				}
			}
		} else {
			// Randomly pick a card in the set.
			$cardsInSet = array();
			foreach ($cards as $card) {
				if ($card->set != $set) continue;
				$cardsInSet[] = $card;
			}
			if (count($cardsInSet) > 0) $chosenCard = $cardsInSet[array_rand($cardsInSet)];
		}
		if (!$chosenCard) return null;

		// Card was found.
		return $this->configureCard($chosenCard);
	}

	public function getSets ($cardTitle) {
		$cards = @$this->titleToCards[(string)strtolower($cardTitle)];
		if (!$cards) return null;
		$sets = array();
		foreach ($cards as $card)
			$sets[$card->set] = true;
		return array_keys($sets);
	}

	public function getCards ($title) {
		$cards = @$this->titleToCards[(string)strtolower($title)];
		if (!$cards) return null;
		$configuredCards = array();
		foreach ($cards as $card)
			$configuredCards[] = $this->configureCard($card);
		return $configuredCards;
	}

	public function configureCard ($card) {
		global $config;

		$card = clone $card;
		$language = strtolower($config['output.language']);

		if ($config["card.flavor.text"] == FALSE) {
			$card->flavor = '';
		} else if ($config["card.flavor.random"]) {
			// Pick a flavor text that hasn't been picked yet.
			$flavors = @$this->titleToFlavors[(string)$card->title];
			if (!$flavors || count($flavors) == 0) {
				$cardWithSameTitle = @$this->titleToCards[(string)strtolower($card->title)];
				foreach ($cardWithSameTitle as $cardWithSameTitle)
					if ($cardWithSameTitle->flavor) $flavors[] = $cardWithSameTitle->flavor;
			}
			if (count($flavors) > 0) {
				$index = array_rand($flavors);
				$card->flavor = $flavors[$index];
				array_splice($flavors, $index, 1);
			}
		} else if ($config["card.localized.flavor.random"] && ($language && $language != 'english')) {
			// Pick a localized flavor text that hasn't been picked yet.
			$flavors = @$this->titleToLocalizedFlavors[(string)$card->title];
			if (!$flavors || count($flavors) == 0) {
				$cardWithSameTitle = @$this->titleToCards[(string)strtolower($card->title)];
				foreach ($cardWithSameTitle as $cardWithSameTitle)
					if ($cardWithSameTitle->flavor) $flavors[] = $cardWithSameTitle->flavor;
			}
			if (count($flavors) > 0) {
				$index = array_rand($flavors);
				$card->flavor = $flavors[$index];
				array_splice($flavors, $index, 1);
			}
		}	

		// Find art image.
		$card->artFileName = $this->artDB->getArtFileName($card->title, $card->set, $card->pic);

		// Artist and copyright.
		$override = $this->titleToRendererOverride($card->title, $card->set, $card->pic);
		if ($override) {
			switch ($override) {
				case 'PreEighth' : {
					switch ($config['render.preEighth']) {
						case 0 : {
							switch ($config['render.eighth']) {
								case 0 : $override = 'M15'; break;
								case 1 : $override = 'Eighth'; break;
							}
						} break;
						case 1 : break;
					}
				} break;
				case 'Eighth' : {
					switch ($config['render.eighth']) {
						case 0 : {
							switch ($config['render.m15']) {
								case 0 : $override = 'PreEithth'; break;
								case 1 : $override = 'M15'; break;
							}
						} break;
						case 1 : break;
					}
				} break;
				case 'Planeswalker' : {
					switch ($config['render.eighth']) {
						case 0 : $override = 'M15Planeswalker'; break;
						case 1 : break;
					}
				} break;
				case 'Planeswalker4' : {
					switch ($config['render.eighth']) {
						case 0 : $override = 'M15Planeswalker4'; break;
						case 1 : break;
					}
				} break;
				case 'M15' : {
					switch ($config['render.m15']) {
						case 0 : {
							switch ($config['render.eighth']) {
								case 0 : $override = 'PreEighth'; break;
								case 1 : $override = 'Eighth'; break;
							}
						} break;
						case 1 : break;
					}
				} break;
				case 'M15Planeswalker' : {
					switch ($config['render.m15']) {
						case 0 : $override = 'Planeswalker'; break;
						case 1 : break;
					}
				} break;
				case 'M15Planeswalker4' : {
					switch ($config['render.m15']) {
						case 0 : $override = 'Planeswalker4'; break;
						case 1 : break;
					}
				} break;
				default : break;
			}
	if ($config['card.artist.and.copyright.m15'] && ($override == 'M15'||$override == 'M15Planeswalker'||$override == 'M15Planeswallker4')){
				$card->copyright = $config['card.copyright.m15'];
			} else {
				$card->copyright = $config['card.copyright'] . ' ' . $card->collectorNumber;
			}
		}
		else if ($config['card.artist.and.copyright.m15'] && $config['render.m15'] && $this->setDB->isM15($card->getDisplaySet()))
			$card->copyright = $config['card.copyright.m15'];
		else if ($config['card.artist.and.copyright.m15'] && !$config['render.eighth'] && $config['render.m15'] && $this->setDB->isEighth($card->getDisplaySet()))
			$card->copyright = $config['card.copyright.m15'];
		else if ($config['card.artist.and.copyright.m15'] && !$config['render.preEighth'] && !$config['render.eighth'] && $config['render.m15'] && $this->setDB->isPre8th($card->getDisplaySet()))
			$card->copyright = $config['card.copyright.m15'];
		else if ($config['card.artist.and.copyright'])
			$card->copyright = $config['card.copyright'] . ' ' . $card->collectorNumber;
		else {
			$card->artist = null;
			$card->copyright = null;
		}

		if (!$config['card.reminder.text']) $card->legal = preg_replace('/#\(.*?\)#/', '', $card->legal);
		
		//START disable flavor for pre-mirage set (doesn't have flavor in original printing)
		$preMirageSets = explode(',', $config['card.premirage.sets']);
		if ($config['card.reminder.text'] && $config['card.premirage.disable.reminder'] && in_array($card->set, $preMirageSets) && preg_match('/([\#]{0,1}[\((]\{T\}[ \::]{1,2}.*?\{[WUBRG]\}.*?\{[WUBRG]\}.*?[\.?][\))][\#]{0,1})(?!.)/su', $card->legal) == FALSE) $card->legal = preg_replace('/#\(.*?\)#/', '', $card->legal);
		//END disable flavor for pre-mirage set

		return $card;
	}

	public function getAllCardTitles () {
		$titles = array();
		foreach ($this->titleToCards as $title => $cards)
			$titles[$title] = true;
		return array_keys($titles);
	}
	
	public function getAllTokenTitles () {
		$titles = array();
		foreach ($this->titleToTokens as $title => $cards)
			$titles[$title] = true;
		return array_keys($titles);
	}

	static public function rowToCard ($row) {
		$card = new Card();
		$card->title = (string)$row[0];
		$card->set = (string)$row[1];
		$card->color = (string)$row[2];
		$card->type = (string)$row[3];
		$card->englishType = $card->type;
		$card->pt = (string)str_replace('\\', '/', $row[4]);
		$card->flavor = (string)$row[5];
		$card->rarity = (string)$row[6];
		$card->cost = (string)@$row[7];
		$card->legal = (string)str_replace("\r\n", "\n", @$row[8]);
		$card->pic = (string)@$row[9];
		$card->artist = (string)@$row[10];
		$card->collectorNumber = (string)str_replace('\\', '/', @$row[11]);
		return $card;
	}

	static public function cardToRow ($card) {
		$row = array();
		$row[0] = (string)$card->title;
		$row[1] = (string)$card->set;
		$row[2] = (string)$card->color;
		$row[3] = (string)$card->type;
		$row[4] = (string)str_replace('/', '\\', $card->pt);
		$row[5] = (string)$card->flavor;
		$row[6] = (string)$card->rarity;
		$row[7] = (string)$card->cost;
		$row[8] = (string)$card->legal;
		$row[9] = (string)$card->pic;
		$row[10] = (string)$card->artist;
		$row[11] = (string)str_replace('/', '\\', $card->collectorNumber);
		return $row;
	}

		public static function applyCorrectedPromoSymbolToCard ($row, $card) {
		
		//if (!empty($row[3])) $card->pic = $row[3];
		if (!empty($row[3])) $card->setDisplaySet($row[3]);
		if (!empty($row[4])) $card->setM15DisplaySet($row[4]);
		if (!empty($row[5])) $card->setDisplayRarity($row[5]);
		if (!empty($row[6])) $card->collectorNumber = (string)str_replace('\\', '/', @$row[6]);
	}

	
	static public function applyLanguageRowToCard ($row, $card) {
		global $config;
		if($row[1]){
			$cardName = preg_replace("/ ?\(\d\)/", "", $row[1]);
			$card->setDisplayTitle($cardName);
		}
		else
			$card->setDisplayTitle($row[0]);
		if (!empty($row[2])) $card->type = $row[2];
		if(!empty($row[3])) $card->legal = str_replace("\r\n", "\n", $row[3]);
			
		/*else
			$card->legal = "";*/
		//if (!$config['output.english.flavor.text']) $card->flavor = @$row[4];
		if ($config['output.english.title.on.translated.card']) $card->artist = $card->title;
	}
	
	static public function cardToLanguageRow ($card) {
		$row = array();
		$row[0] = $card->title;
		$row[1] = $card->getDisplayTitle();
		$row[2] = $card->type;
		$row[3] = $card->legal;
		$row[4] = $card->flavor;
		return $row;
	}
		
	static public function tokenToLanguage ($card, $language, $setDB, $type) {
		$file = fopen_utf8("data/$type-$language.csv", 'r');
		if (!$file) error("Unable to open file: data/$type-$language.csv");
		//echo '.';
		while (($row = fgetcsv($file, 6000, ',')) !== FALSE) {
			// Overwrite some of the english card values with the foreign values.
			$englishTitle = strtolower($row[0]);
			$tokenSet = $setDB->normalize($row[2]);
			$tokenPic = @$row[6];
			if ($englishTitle != strtolower($card->title)) {
				continue;
			} 
			if ($tokenSet != $card->set) {
				continue;
			}
			if (@$tokenPic != @$card->pic){
				continue;
			}
			CardDB::applyLanguageRowToToken($row, $card);
			fclose($file);
			break;
		}
	}
	
	static public function tokenToLanguageRow ($card) {
		$row = array();
		$row[0] = $card->title;
		$row[1] = $card->getDisplayTitle();
		$row[2] = $card->set;
		$row[3] = $card->type;
		$row[4] = $card->legal;
		$row[5] = $card->flavor;
		$row[6] = $card->pic;
		return $row;
	}
	
	static public function applyLanguageRowToToken ($row, $card) {
		global $config;
		if($row[1]){
			$cardName = preg_replace("/ ?\(\d\)/", "", $row[1]);
			$card->setDisplayTitle($cardName);
		}
		else
			$card->setDisplayTitle($row[0]);
		if (!empty($row[3])) $card->type = $row[3];
		if (!empty($row[4])) $card->legal = str_replace("\r\n", "\n", $row[4]);
		if (!empty($row[5])) $card->flavor = str_replace("\r\n", "\n", $row[5]);
		if ($config['output.english.title.on.translated.card']) $card->artist = $card->title;
	}

	static public function cardToGELanguageRow ($card) {
		$row = array();
		$row[0] = $card->title;
		$row[1] = $card->set;
		$row[2] = $card->type;
		$row[3] = $card->legal;
		//$row[4] = $card->flavor;
		return $row;
	}
	
	static public function cardToFlavorLanguageRow ($card) {
		$row = array();
		$row[0] = $card->title;
		$row[1] = $card->set;
		$row[2] = $card->flavor;
		@$row[3] = $card->pic;
		return $row;
	}
	
	public function resetUsedFlavors () {
		$this->titleToFlavors = array();
	}
	
	public function titleToRendererOverride ($title, $set, $pic) {
		$titleToRendererOverride = fopen_utf8("data/titleToRendererOverride.csv", 'rb');
		if (!$titleToRendererOverride) error('Unable to open file: ' . $titleToRendererOverride);
		$i = 0;
		while (($row = fgetcsv($titleToRendererOverride, 300, ',', '"')) !== FALSE) {
			if($i++ == 0) continue; //skip first line
			
			if ($title == $row[0] && $set == $row[1] && $pic == $row[2]) {
				return (string)$row[3];
			}
			continue;
		}
	}
}

	function transliterateString($txt) {
		$transliterationTable = array('á' => 'a', 'Á' => 'A', 'à' => 'a', 'À' => 'A', 'ă' => 'a', 'Ă' => 'A', 'â' => 'a', 'Â' => 'A', 'å' => 'a', 'Å' => 'A', 'ã' => 'a', 'Ã' => 'A', 'ą' => 'a', 'Ą' => 'A', 'ā' => 'a', 'Ā' => 'A', 'ä' => 'ae', 'Ä' => 'AE', 'æ' => 'ae', 'Æ' => 'AE', 'ḃ' => 'b', 'Ḃ' => 'B', 'ć' => 'c', 'Ć' => 'C', 'ĉ' => 'c', 'Ĉ' => 'C', 'č' => 'c', 'Č' => 'C', 'ċ' => 'c', 'Ċ' => 'C', 'ç' => 'c', 'Ç' => 'C', 'ď' => 'd', 'Ď' => 'D', 'ḋ' => 'd', 'Ḋ' => 'D', 'đ' => 'd', 'Đ' => 'D', 'ð' => 'dh', 'Ð' => 'Dh', 'é' => 'e', 'É' => 'E', 'è' => 'e', 'È' => 'E', 'ĕ' => 'e', 'Ĕ' => 'E', 'ê' => 'e', 'Ê' => 'E', 'ě' => 'e', 'Ě' => 'E', 'ë' => 'e', 'Ë' => 'E', 'ė' => 'e', 'Ė' => 'E', 'ę' => 'e', 'Ę' => 'E', 'ē' => 'e', 'Ē' => 'E', 'ḟ' => 'f', 'Ḟ' => 'F', 'ƒ' => 'f', 'Ƒ' => 'F', 'ğ' => 'g', 'Ğ' => 'G', 'ĝ' => 'g', 'Ĝ' => 'G', 'ġ' => 'g', 'Ġ' => 'G', 'ģ' => 'g', 'Ģ' => 'G', 'ĥ' => 'h', 'Ĥ' => 'H', 'ħ' => 'h', 'Ħ' => 'H', 'í' => 'i', 'Í' => 'I', 'ì' => 'i', 'Ì' => 'I', 'î' => 'i', 'Î' => 'I', 'ï' => 'i', 'Ï' => 'I', 'ĩ' => 'i', 'Ĩ' => 'I', 'į' => 'i', 'Į' => 'I', 'ī' => 'i', 'Ī' => 'I', 'ĵ' => 'j', 'Ĵ' => 'J', 'ķ' => 'k', 'Ķ' => 'K', 'ĺ' => 'l', 'Ĺ' => 'L', 'ľ' => 'l', 'Ľ' => 'L', 'ļ' => 'l', 'Ļ' => 'L', 'ł' => 'l', 'Ł' => 'L', 'ṁ' => 'm', 'Ṁ' => 'M', 'ń' => 'n', 'Ń' => 'N', 'ň' => 'n', 'Ň' => 'N', 'ñ' => 'n', 'Ñ' => 'N', 'ņ' => 'n', 'Ņ' => 'N', 'ó' => 'o', 'Ó' => 'O', 'ò' => 'o', 'Ò' => 'O', 'ô' => 'o', 'Ô' => 'O', 'ő' => 'o', 'Ő' => 'O', 'õ' => 'o', 'Õ' => 'O', 'ø' => 'oe', 'Ø' => 'OE', 'ō' => 'o', 'ö' => 'o', 'Ō' => 'O', 'ơ' => 'o', 'Ơ' => 'O', 'ö' => 'oe', 'Ö' => 'OE', 'ṗ' => 'p', 'Ṗ' => 'P', 'ŕ' => 'r', 'Ŕ' => 'R', 'ř' => 'r', 'Ř' => 'R', 'ŗ' => 'r', 'Ŗ' => 'R', 'ś' => 's', 'Ś' => 'S', 'ŝ' => 's', 'Ŝ' => 'S', 'š' => 's', 'Š' => 'S', 'ṡ' => 's', 'Ṡ' => 'S', 'ş' => 's', 'Ş' => 'S', 'ș' => 's', 'Ș' => 'S', 'ß' => 'SS', 'ť' => 't', 'Ť' => 'T', 'ṫ' => 't', 'Ṫ' => 'T', 'ţ' => 't', 'Ţ' => 'T', 'ț' => 't', 'Ț' => 'T', 'ŧ' => 't', 'Ŧ' => 'T', 'ú' => 'u', 'Ú' => 'U', 'ù' => 'u', 'Ù' => 'U', 'ŭ' => 'u', 'Ŭ' => 'U', 'û' => 'u', 'Û' => 'U', 'ů' => 'u', 'Ů' => 'U', 'ű' => 'u', 'Ű' => 'U', 'ũ' => 'u', 'Ũ' => 'U', 'ų' => 'u', 'Ų' => 'U', 'ū' => 'u', 'Ū' => 'U', 'ư' => 'u', 'Ư' => 'U', 'ü' => 'ue', 'Ü' => 'UE', 'ẃ' => 'w', 'Ẃ' => 'W', 'ẁ' => 'w', 'Ẁ' => 'W', 'ŵ' => 'w', 'Ŵ' => 'W', 'ẅ' => 'w', 'Ẅ' => 'W', 'ý' => 'y', 'Ý' => 'Y', 'ỳ' => 'y', 'Ỳ' => 'Y', 'ŷ' => 'y', 'Ŷ' => 'Y', 'ÿ' => 'y', 'Ÿ' => 'Y', 'ź' => 'z', 'Ź' => 'Z', 'ž' => 'z', 'Ž' => 'Z', 'ż' => 'z', 'Ż' => 'Z', 'þ' => 'th', 'Þ' => 'Th', 'µ' => 'u', 'а' => 'a', 'А' => 'a', 'б' => 'b', 'Б' => 'b', 'в' => 'v', 'В' => 'v', 'г' => 'g', 'Г' => 'g', 'д' => 'd', 'Д' => 'd', 'е' => 'e', 'Е' => 'E', 'ё' => 'e', 'Ё' => 'E', 'ж' => 'zh', 'Ж' => 'zh', 'з' => 'z', 'З' => 'z', 'и' => 'i', 'И' => 'i', 'й' => 'j', 'Й' => 'j', 'к' => 'k', 'К' => 'k', 'л' => 'l', 'Л' => 'l', 'м' => 'm', 'М' => 'm', 'н' => 'n', 'Н' => 'n', 'о' => 'o', 'О' => 'o', 'п' => 'p', 'П' => 'p', 'р' => 'r', 'Р' => 'r', 'с' => 's', 'С' => 's', 'т' => 't', 'Т' => 't', 'у' => 'u', 'У' => 'u', 'ф' => 'f', 'Ф' => 'f', 'х' => 'h', 'Х' => 'h', 'ц' => 'c', 'Ц' => 'c', 'ч' => 'ch', 'Ч' => 'ch', 'ш' => 'sh', 'Ш' => 'sh', 'щ' => 'sch', 'Щ' => 'sch', 'ъ' => '', 'Ъ' => '', 'ы' => 'y', 'Ы' => 'y', 'ь' => '', 'Ь' => '', 'э' => 'e', 'Э' => 'e', 'ю' => 'ju', 'Ю' => 'ju', 'я' => 'ja', 'Я' => 'ja');
		return str_replace(array_keys($transliterationTable), array_values($transliterationTable), $txt);
	}

?>
