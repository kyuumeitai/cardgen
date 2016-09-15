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
class ImageWriter {
	public $setDB;
	public $cardDB;
	public $artDB;
	public $formatDB;
	public $convertor;
	public $fontSizeDB;
	public $titleToLandColors;
	public $pagedOutput;
	public $decklistOnlyOutput;
	public $titleToLevel;
	public $titleToTransform;
	public $titleToPlaneswalker4;
	//public $titleToRendererOverride;
	public $renderers = array();
	public $titleToAltSet = array();
	private $promoSet = array();

	public function __construct () {
		global $config;

		$this->setDB = new SetDB();
		$this->artDB = new ArtDB($this->setDB);
		$this->cardDB = new CardDB($this->setDB, $this->artDB);
		$this->formatDB = new FormatDB($this->setDB, $this->cardDB);
		$this->fontSizeDB = new FontSizeDB($config['card.text.use.font.size.cache']);
		$this->titleToLandColors = csvToArray('data/titleToLandColors.csv');
		$this->titleToLevel = csvToArray('data/eighth/titleToLevel.csv');
		$this->titleToTransform = csvToArray('data/titleToTransform.csv');
		$this->titleToPlaneswalker2 = csvToArray('data/titleToPlaneswalker2.csv');
		$this->titleToPlaneswalker4 = csvToArray('data/titleToPlaneswalker4.csv');
		$this->convertor = new Convertor();
	}


	public function setOutputType ($pagedOutput, $decklistOnlyOutput) {
		$this->pagedOutput = $pagedOutput;
		$this->decklistOnlyOutput = $decklistOnlyOutput;
	}

	public function parseDecklist ($inputFileName) {
		$this->addDecklist(new Decklist($this->setDB, $this->cardDB, $this->convertor, $inputFileName));
	}

	public function addDecklist (Decklist $decklist) {
		global $config;

		if (!$this->decklistOnlyOutput) $this->addCards($decklist->cards);

		if ($this->decklistOnlyOutput || $config['render.decklist']) {
			$count = count($decklist->cards);
			if ($count <= 75) {
				// 75 or less and we assume this is a standard 60 card deck with up to a 15
				// card sideboard.
				$decklistRenderer = new DecklistRenderer();
				$decklistRenderer->cards = $decklist->cards;
				$decklistRenderer->writer = $this;
				$decklistRenderer->outputDir = $config['output.directory'];
				$decklistRenderer->outputName = 'Decklist - ' . $decklist->name;
				$this->renderers[] = $decklistRenderer;
			} else {
				// Enough cards for a multi page list.
				$cardsPerDeckpage = isset($config['output.decklist.cardsperpage']) ? $config['output.decklist.cardsperpage'] : 50;
				echo "\n";

				warn('Large/Commander deck detected, going to ' . $cardsPerDeckpage . " card per page lists.\n");

				$cardsRendered = 0 ;
				while ($cardsRendered < $count) {
					// slice out each card block.
					$cardPage = array_slice($decklist->cards, $cardsRendered, $cardsPerDeckpage);
					$decklistRenderer = new DecklistRenderer();
					$decklistRenderer->cards = $cardPage;
					$decklistRenderer->wholeDeck = $decklist->cards;
					$decklistRenderer->writer = $this;
					$decklistRenderer->outputDir = $config['output.directory'];
					$decklistRenderer->outputName = 'Decklist - ' . $decklist->name . ' page ' . (floor($cardsRendered / $cardsPerDeckpage) + 1);
					$this->renderers[] = $decklistRenderer;
					$cardsRendered += $cardsPerDeckpage;
				}
			}
		}
	}

	public function addCards ($cards) {
		// Remove duplicates if not doing paged output.
		if (!$this->pagedOutput) {
			$distinctCards = array();
			foreach ($cards as $card) {
				foreach ($distinctCards as $existingCard)
					if (strtolower($card->title) == strtolower($existingCard->title) && $card->pic == $existingCard->pic) continue 2;
				$distinctCards[] = $card;
			}
			$cards = $distinctCards;
		}
		foreach ($cards as $card)
			$this->addCard($card);
	}

	public function addCard (Card $card) {
		global $config;
		if ($config['output.card.set.directories'] && !$this->pagedOutput) {
			// Renderer every version of the card to its set subdirectory.
			foreach ($this->cardDB->getCards($card->title) as $card) {
				$renderer = $this->getCardRenderer($card);
				foreach($renderer as $r) {
					$r->outputDir .= $card->set . '/';
					$this->renderers[] = $r;
				}
			}
		} else {
			$renderer = $this->getCardRenderer($card);
			foreach($renderer as $r)
				$this->renderers[] = $r;
		}
		return $renderer;
	}

	public function addCardByTitle ($title) {
		return $this->addCard($this->cardDB->getCard($title));
	}

	public function getCardRenderer (Card $card) {
		global $config;
		
		$isPre8th = $this->setDB->isPre8th($card->getDisplaySet()) && !$card->promo;
		$isEighth = $this->setDB->isEighth($card->getDisplaySet()) && !$card->promo;
		$isM15 = $this->setDB->isM15($card->getDisplaySet()) && !$card->promo;

		$renderer = array();
		$promoSet = explode(',', $config['card.promo.symbols']);
		$override = $this->titleToRendererOverride($card->title, $card->set, $card->pic);
		// Determine the correct CardRenderer.
		if ($override && !$this instanceof TransformRenderer && in_array($card->set, $promoSet)) {
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
			$override .= 'Renderer';
			$renderer[] = new $override($this->setDB);
		}
		// Token cards
		else if (($card->isToken()||$card->isEmblem()) && $config['render.token']) {
			$renderer[] = new TokenRenderer($this->setDB);
		}
		// Split Cards
		else if (strpos($card->title, "/") !== FALSE && !$card->isToken()) {
				$renderer[] = new SplitRenderer($this->setDB);
		}
		// Leveler Cards
		else if(array_key_exists(strtolower($card->title), $this->titleToLevel)) {
			if ($isM15 == FALSE) $renderer[] = new LevelRenderer($this->setDB);
			else $renderer[] = new M15LevelRenderer($this->setDB);
		}
		// Transform Cards
		else if(array_key_exists(strtolower($card->title), $this->titleToTransform) && strpos($card->legal, "\n-----\n") !== FALSE) {
			$side1 = $this->titleToTransform[strtolower($card->title)];
			$title2 = preg_match("/\n-----\n(.*?)\n/s", $card->legal, $matches);
			$side2 = @$this->titleToTransform[strtolower($matches[1])];
			if (!isset($side2)) $side2 = 'night';
			
			$renderer[] = new TransformRenderer($this->setDB, $this->artDB, "$side1");
			if ($config['card.transform.as.split'] == FALSE) {
				$renderer[] = new TransformRenderer($this->setDB, $this->artDB, "$side2");
			}
		}
		// Flip Cards
		else if(strpos($card->legal, "\n-----\n") !== FALSE) {
			$renderer[] = new EighthFlipRenderer($this->setDB);
		}
		// Vanguard Cards
		else if (($card->set == "VAN"||$card->set == "VG") && $config['render.vanguard']) {
			$renderer[] = new VanguardRenderer($this->setDB);
		}
		// Planeswalkers with 2 abilities
			//M15 Style
		else if(array_key_exists(strtolower($card->title), $this->titleToPlaneswalker2) && $this->setDB->isM15($card->getDisplaySet()) && $config['render.planeswalker'] && $config['render.m15']) {				
			$renderer[] = new M15PlanesWalker2Renderer($this->setDB);
		} 
		// Planeswalkers with 4 abilities
			//M15 Style
		else if(array_key_exists(strtolower($card->title), $this->titleToPlaneswalker4) && $this->setDB->isM15($card->getDisplaySet()) && $config['render.planeswalker'] && $config['render.m15']) {				
			$renderer[] = new M15PlanesWalker4Renderer($this->setDB);
		} 
			//Eighth Style as M15 Style
		else if(array_key_exists(strtolower($card->title), $this->titleToPlaneswalker4) && !$this->setDB->isM15($card->getDisplaySet()) && $config['render.planeswalker'] && !$config['render.eighth']) {				
			$renderer[] = new M15PlanesWalker4Renderer($this->setDB);
		} 
			//Eighth Style
		else if(array_key_exists(strtolower($card->title), $this->titleToPlaneswalker4) && $config['render.planeswalker']) {				
			$renderer[] = new PlanesWalker4Renderer($this->setDB);
		}
		// Planeswalkers with 3 abilities
			// M15 Style Planeswalkers
		else if (strpos($card->englishType, "Planeswalker")!== FALSE && $this->setDB->isM15($card->getDisplaySet()) && $config['render.planeswalker'] && $config['render.m15']) {
			$renderer[] = new M15PlanesWalkerRenderer($this->setDB);
		}
			// Eighth Style as M15 Style
		else if (strpos($card->englishType, "Planeswalker")!== FALSE && $config['render.planeswalker'] && !$config['render.eighth']) {
			$renderer[] = new M15PlanesWalkerRenderer($this->setDB);
		}
			// Eighth Style Planeswalkers
		else if (strpos($card->englishType, "Planeswalker")!== FALSE && $config['render.planeswalker']) {
			$renderer[] = new PlanesWalkerRenderer($this->setDB);
		}
		// Planechase Plane and Phenomenon
		else if ((strpos($card->englishType, "Plane") !== FALSE  || strpos($card->englishType, "Phenomenon") !== FALSE) && $config['render.plane']) {
			$renderer[] = new PlaneRenderer($this->setDB);
		}
		// Archenemy Scheme
		else if ($card->isScheme() && $config['render.scheme']) {
			$renderer[] = new SchemeRenderer($this->setDB);
		}
		// Theros Block Hero Cards
		else if ($card->isHero() && $config['render.hero']) {
			$renderer[] = new EighthRenderer($this->setDB);
		}
		// Everything Else
		else {
			// Basic Land Style
			if ($isPre8th && !$config['render.preEighth.basic.land.frames'] && $card->isBasicLand()) $isPre8th = false;
			// Pre 8th Style
			if ($config['render.preEighth'] && ($isPre8th || !$config['render.eighth']) && ($isPre8th || !$config['render.m15']))
				$renderer[] = new PreEighthRenderer($this->setDB);
			// Pre 8th as 8th Style
			else if ($config['render.preEighth'] && ($isPre8th || $config['render.eighth']) && ($isPre8th || !$config['render.m15']))
				$renderer[] = new EighthRenderer($this->setDB);
			// M15 Style
			else if ($config['render.m15'] && ($isM15 || !$config['render.eighth']))
				$renderer[] = new M15Renderer($this->setDB);
			// Everything Else
			else if ($config['render.eighth']){
				$renderer[] = new EighthRenderer($this->setDB);
			 
			}
		}
		if (empty($renderer)) error('No renderer enabled for card: ' . $card);

		foreach($renderer as $r) {
			$r->card = $card;
			$r->writer = $this;
			$r->outputDir = $config['output.directory'];

			$r->outputName = $this->cleanOutputName($card, $r);
		}

		return $renderer;
	}

	public function cleanOutputName ($card, $renderer) {
		global $config;

		$suffix = $config['output.suffix'];
		$outputName = $renderer->getCardName();
		if ($card->isToken() && $config['output.tokens.for.forge'] == false) {
			$outputName .= ' Token';
			if ($card->pic && $card->pic != 'token' && $config['card.short.pic'] != false) $outputName .= $card->pic;
			else if ($card->pic && $card->pic != 'token') $outputName .= ' (' . $card->pic . ')';
		}
		else if ($card->isToken() && $config['output.tokens.for.forge'] != false) {
			$suffix = null;
			$outputName = strtolower(str_replace('Art', 'C', $card->color) . '_' . str_replace('*', 'n', $card->pt) . '_' . str_replace(' ', '_', $outputName));
			$outputName = str_replace('/', '_', $outputName);
			if ($card->pic) $outputName .= $card->pic;
			$outputName .= '_' . strtolower($card->set);
		} else {
			if ($card->pic && $config['card.short.pic'] != false) $outputName .= $card->pic;
			else if ($card->pic) $outputName .= ' (' . $card->pic . ')';
		}
		$outputName .= $suffix;

		// Filenames can't contain " and ?.
		if (strpos($outputName, '"') !== FALSE) $outputName = str_replace('"', '',  $outputName);
		if (strpos($outputName, '?') !== FALSE) $outputName = str_replace('?', '',  $outputName);
		
		// Add missing special characters to filenames
		$outputName = str_replace('El-Hajjaj', 'El-Hajjâj', $outputName);
        $outputName = str_replace('Junun', 'Junún', $outputName);
        $outputName = str_replace('Lim-Dul', 'Lim-Dûl', $outputName);
        $outputName = str_replace('Jotun', 'Jötun', $outputName);
        $outputName = str_replace('Ghazban', 'Ghazbán', $outputName);
        $outputName = str_replace('Ifh-Biff', 'Ifh-Bíff', $outputName);
        $outputName = str_replace('Juzam', 'Juzám', $outputName);
        $outputName = str_replace('Khabal', 'Khabál', $outputName);
        $outputName = str_replace('Marton', 'Márton', $outputName);
        $outputName = str_replace("Ma'ruf", "Ma'rûf", $outputName);
        $outputName = str_replace('Deja Vu', 'Déjà Vu', $outputName);
        $outputName = str_replace('Dandan', 'Dandân', $outputName);
        $outputName = str_replace('Bosium', 'Bösium', $outputName);
        $outputName = str_replace('Seance', 'Séance', $outputName);
        $outputName = str_replace('Saute', 'Sauté', $outputName);
        $outputName = str_replace('Chicken a la King', 'Chicken à la King', $outputName);		
		
		return $outputName;
	}

	public function writeCards () {
		global $config;

		$resizeWidth = $config['output.card.width'];
		if ($resizeWidth) $resizeHeight = $resizeWidth * (1050 / 736);

		foreach ($this->renderers as $renderer) {
			$imageFileName = $this->getOutputFileName($renderer->outputDir, $renderer->outputName);
			if ($config['output.skip.existing.images'] && file_exists($imageFileName)) continue;

			$image = $renderer->render();
			if ($resizeWidth) {
				$canvas = imagecreatetruecolor($resizeWidth, $resizeHeight);
				imagecopyresampled($canvas, $image, 0, 0, 0, 0, $resizeWidth, $resizeHeight, 736, 1050);
				imagedestroy($image);
				$image = $canvas;
			}
			$this->outputImage($image, $imageFileName);
			imagedestroy($image);
		}

		$count = count($this->renderers);

		$this->reset();

		return $count;
	}

	public function writePages () {
		global $config;

		// Collect and compute paged output data.
		$outputDir = $config['output.directory'];
		$spacing = $config['output.card.spacing'] + 1;
		$borderSize = $config['output.card.border'];
		$rows = $config['output.page.rows'];
		$columns = $config['output.page.columns'];
		$rotate = $config['output.page.rotate'];
		$rotateLastRows = $config['output.page.rotate.last.rows'];
		$cardWidth = $rotate ? (1050 + ($borderSize * 2)) : (736 + ($borderSize * 2));
		$cardHeight = $rotate ? (736 + ($borderSize * 2)) : (1050 + ($borderSize * 2));
		$offsetTop = @$config['output.page.offset.top'];
		$offsetLeft = @$config['output.page.offset.left'];
		$offsetBottom = @$config['output.page.offset.bottom'] + 1;
		$offsetRight = @$config['output.page.offset.right'] + 1;
		$canvasWidth = $cardWidth * $columns + $spacing * ($columns - 1) + $offsetLeft + $offsetRight;
		$canvasHeight = $cardHeight * ($rows - $rotateLastRows) + $spacing * ($rows - 1) + $cardWidth * $rotateLastRows + $offsetTop + $offsetBottom;
		$xOffsets = @explode(',', $config['output.card.offsets.x']);
		$yOffsets = @explode(',', $config['output.card.offsets.y']);
		$pageNumber = 0;

		// Open up an existing page image if lastPage.txt exists.
		$canvas = null;
		$skipImages = 0;
		if (file_exists($outputDir . 'lastPage.txt')) {
			// Read the number of images that were written to the last generated page.
			$file = fopen_utf8($outputDir . 'lastPage.txt', 'r');
			if (!$file) error('Cannot append. Unable to open file: ' . $outputDir . 'lastPage.txt');
			fgets($file, 1000);
			$skipImages = trim(fgets($file, 1000));
			fclose($file);

			// Open the last page image.
			$outputExt = $config['output.extension'];
			while (true) {
				$pageNumber++;
				if (!file_exists($outputDir . 'page' . $pageNumber . '.' . $outputExt)) break;
			}
			$pageNumber--;
			$fileName = $outputDir . 'page' . $pageNumber . '.' . $outputExt;
			$pageNumber--;
			if ($outputExt == 'png')
				$canvas = imagecreatefrompng($fileName);
			else if ($outputExt == 'jpg')
				$canvas = imagecreatefromjpeg($fileName);
		}

		$writtenImageCount = 0;
		for ($i = 0, $n = count($this->renderers); $i < $n;) {
			if (!$canvas) {
				// New page image.
				$canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);
				$black = imagecolorallocate($canvas, 0, 0, 0);
				$white = imagecolorallocate($canvas, 255, 255, 255);
				imagefill($canvas, 0, 0, $white);

			} else {
				// Using existing page image.
				$black = imagecolorallocate($canvas, 0, 0, 0);
			}

			$cardIndex = 0;
			$y = $borderSize;
			$writtenImageCount = 0;
			for ($rowIndex = 0; $rowIndex < $rows; $rowIndex++) {
				$x = $borderSize;
				if ($rowIndex >= $rows - $rotateLastRows) {
					// Rotate the last row the opposite of all previous rows.
					$tempColumns = floor(($cardWidth * $columns) / $cardHeight);
					$tempRotate = !$rotate;
					$xIncrement = $cardHeight + $spacing;
					$yIncrement = $cardWidth + $spacing;
					if ($config['output.page.rotate.align.right'])
						$x = $borderSize + $canvasWidth - ($cardHeight * $tempColumns + $spacing * ($tempColumns - 1));
					else
						$x = $borderSize;
				} else {
					// Normal row.
					$tempColumns = $columns;
					$tempRotate = $rotate;
					$xIncrement = $cardWidth + $spacing;
					$yIncrement = $cardHeight + $spacing;
				}
				for ($columnIndex = 0; $columnIndex < $tempColumns; $columnIndex++) {
					if ($i < $n) {
						if ($skipImages > 0)
							$skipImages--;
						else {
							// Render card image.
							$renderer = $this->renderers[$i];
							$cardImage = $renderer->render();
							if ($renderer instanceof DecklistRenderer) {
								$this->artDB->resetUsedArt();
								$this->cardDB->resetUsedFlavors();
							}
							// Copy it into the page image.
							$xCoord = $x + $offsetLeft + @$xOffsets[$cardIndex];
							$yCoord = $y + $offsetTop + @$yOffsets[$cardIndex];
							if ($this->setDB->isM15($renderer->card->getDisplaySet())) {
								$width = 720 * 1.05;
								$height = 1020 * 1.05;
								$cardImageOversize = imagecreatetruecolor($width, $height);
								imagecopyresampled($cardImageOversize, $cardImage, 0, 0, 0, 0, $width, $height, 720, 1020);
								imagedestroy($cardImage);
								$cardImageTmp = imagecreatetruecolor(736, 1050);
								imagecopy($cardImageTmp, $cardImageOversize, 0, 0, 9, 9, $width, $height);
								imagedestroy($cardImageOversize);
								$cardImage = $cardImageTmp;
							}
							if ($tempRotate) {
								$cardImageTmp = imagerotate($cardImage, 90, 0);
								imagedestroy($cardImage);
								$cardImage = $cardImageTmp;
								imagefilledrectangle($canvas, $xCoord - $borderSize, $yCoord - $borderSize, $xCoord + 1050 + $borderSize, $yCoord + 736 + $borderSize, $black);
								imagecopy($canvas, $cardImage, $xCoord, $yCoord, 0, 0, 1050, 736);
							} else {
								imagefilledrectangle($canvas, $xCoord - $borderSize, $yCoord - $borderSize, $xCoord + 736 + $borderSize, $yCoord + 1050 + $borderSize, $black);
								imagecopy($canvas, $cardImage, $xCoord/* + $offsetX*/, $yCoord/* + $offsetY*/, 0, 0, 736, 1050);
							}
							imagedestroy($cardImage);
							$i++;
						}
						$writtenImageCount++;
					}
					$x += $xIncrement;
					$cardIndex++;
				}
				$y += $yIncrement;
			}

			$this->outputImage($canvas, $this->getOutputFileName($outputDir, 'page' . ++$pageNumber));
			imagedestroy($canvas);
			$canvas = null;
			echo "Page $pageNumber complete...\n";
		}

		$file = fopen_utf8($outputDir . 'lastPage.txt', 'w');
		if (!$file) error('Unable to write file: ' . $outputDir . 'lastPage.txt');
		fwrite($file, "Number of cards output on the last page image:\r\n");
		fwrite($file, "$writtenImageCount\r\n");
		fclose($file);

		$this->reset();

		return $pageNumber;
	}

	public function reset () {
		$this->renderers = array();
		$this->artDB->resetUsedArt();
		$this->cardDB->resetUsedFlavors();
	}

	private function outputImage ($image, $imageFileName) {
		global $config;

		@mkdir(dirname($imageFileName));

		$outputExt = $config['output.extension'];
		if ($outputExt == 'png') {
			if (!@imagepng($image, $imageFileName)) error("Unable to write image: $imageFileName");
		} else if ($outputExt == 'jpg') {
			if (!@imagejpeg($image, $imageFileName, 100)) error("Unable to write image: $imageFileName");
		} else
			error('Invalid output.extension: ' . $outputExt);
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

	private function getOutputFileName ($outputDir, $fileName) {
		global $config;

		$fileName = str_replace('Avatar: ', '', $fileName);
		$fileName = str_replace(':', '', $fileName);
		$fileName = str_replace('/', '', $fileName);
		$fileName = iconv("UTF-8", "Windows-1252//TRANSLIT", $fileName);
		return $outputDir . $fileName . '.' . $config['output.extension'];
	}
}

?>
