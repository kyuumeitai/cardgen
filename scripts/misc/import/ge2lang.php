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
require_once 'scripts/includes/global.php';
require_once 'scripts/includes/newfunctions.php';
mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");

echo "Card Generator v$version - Gatherer Extractor to cards-<language>.csv\n\n";

$files = getInputFiles(
	array_slice($argv, 1),
	'Drag and drop an Gatherer Extractor CSV file here and press enter...'
);

echo 'Enter the name of the language: ';
$language = strtolower(trim(fgets(STDIN)));

if ($language == 'chinese') {
	echo 'China or Taiwan?: ';
	$language .= '-' . strtolower(trim(fgets(STDIN)));
}

echo "Creating temporary file: data/cards-$language.csv.temp\n";
$cardsFile = fopen_utf8("data/cards-$language.csv.temp", 'w+');
if (!$cardsFile) error("Unable to write CSV file: data/cards-$language.csv.temp");

echo "Creating temporary file: data/cards-$language-flavor.csv.temp\n";
$cardsFlavorFile = fopen_utf8("data/cards-$language-flavor.csv.temp", 'w+');
if (!$cardsFlavorFile) error("Unable to write CSV file: data/cards-$language-flavor.csv.temp");

// Write Gather Extractor csv.
$gathererExtractor = new GathererExtractor($files[0]);
$cards = null;
switch (strtolower($language)) {
	case 'chinese-china' : $cards = $gathererExtractor->cardsCN; break;
	case 'chinese-taiwan' : $cards = $gathererExtractor->cardsTW; break;
	case 'french' : $cards = $gathererExtractor->cardsFR; break;
	case 'french-oracle' : $cards = $gathererExtractor->cardsFR; break;
	case 'german' : $cards = $gathererExtractor->cardsDE; break;
	case 'italian' : $cards = $gathererExtractor->cardsIT; break;
	case 'japanese' : $cards = $gathererExtractor->cardsJP; break;
	case 'portugese' : $cards = $gathererExtractor->cardsPT; break;
	case 'portugese-brazil' : $cards = $gathererExtractor->cardsPT; break;
	case 'russian' : $cards = $gathererExtractor->cardsRU; break;
	case 'spanish' : $cards = $gathererExtractor->cardsES; break;
	case 'korean' : $cards = $gathererExtractor->cardsKO; break;
	default : error('There is no language data for that language. Please enter a valid language: Chinese-China, Chinese-Taiwan, French(-Oracle), German, Italian, Portugese(-Brazil), Russian, Spanish, or Korean'); break;
	}
foreach ($cards as $card)
	writeCsvRow($cardsFile, CardDB::cardToGELanguageRow($card));

$cardsFlavor = null;
switch (strtolower($language)) {
	case 'chinese-china' : $cardsFlavor = $gathererExtractor->cardsCNflavor; break;
	case 'chinese-taiwan' : $cardsFlavor = $gathererExtractor->cardsTWflavor; break;
	case 'french' : $cardsFlavor = $gathererExtractor->cardsFRflavor; break;
	case 'french-oracle' : $cardsFlavor = $gathererExtractor->cardsFRflavor; break;
	case 'german' : $cardsFlavor = $gathererExtractor->cardsDEflavor; break;
	case 'italian' : $cardsFlavor = $gathererExtractor->cardsITflavor; break;
	case 'japanese' : $cardsFlavor = $gathererExtractor->cardsJPflavor; break;
	case 'portugese' : $cardsFlavor = $gathererExtractor->cardsPTflavor; break;
	case 'portugese-brazil' : $cardsFlavor = $gathererExtractor->cardsPTflavor; break;
	case 'russian' : $cardsFlavor = $gathererExtractor->cardsRUflavor; break;
	case 'spanish' : $cardsFlavor = $gathererExtractor->cardsESflavor; break;
	case 'korean' : $cardsFlavor = $gathererExtractor->cardsKOflavor; break;
	default : error('There is no language data for that language. Please enter a valid language: Chinese-China, Chinese-Taiwan, French, German, Italian, Portugese(-Brazil), Russian, Spanish, or Korean'); break;
	}
foreach ($cardsFlavor as $card)
	writeCsvRow($cardsFlavorFile, CardDB::cardToFlavorLanguageRow($card));

fclose($cardsFile);
fclose($cardsFlavorFile);

echo "\n" . count($cards) . " cards processed.\n";
echo "Temporary file complete.\n";

if (file_exists("data/cards-$language.csv")) {
	echo "Backing up file \"data/cards-$language.csv\" to \"data/cards-$language.csv.bak\"...\n";
	@unlink("data/cards-$language.csv.bak");
	@rename("data/cards-$language.csv", "data/cards-$language.csv.bak");
}
if (file_exists("data/cards-$language-flavor.csv")) {
	echo "Backing up file \"data/cards-$language-flavor.csv\" to \"data/cards-$language-flavor.csv.bak\"...\n";
	@unlink("data/cards-$language-flavor.csv.bak");
	@rename("data/cards-$language-flavor.csv", "data/cards-$language-flavor.csv.bak");
}

echo "Moving temporary file to \"data/cards-$language.csv\"...\n";
rename("data/cards-$language.csv.temp", "data/cards-$language.csv");

echo "Moving temporary file to \"data/cards-$language-flavor.csv\"...\n";
rename("data/cards-$language-flavor.csv.temp", "data/cards-$language-flavor.csv");



echo "Import complete.\n";


?>
