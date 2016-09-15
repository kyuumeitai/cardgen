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

echo "Card Generator v$version - Gatherer Extractor to cards.csv\n\n";

$files = getInputFiles(
	array_slice($argv, 1),
	'Drag and drop an Gatherer Extractor CSV file here and press enter...'
);

echo "Creating temporary file: data/cards.csv.temp\n";
$cardsFile = fopen_utf8('data/cards.csv.temp', 'w+b');
if (!$cardsFile) error('Unable to write CSV file: data/cards.csv.temp');

// Copy Header.
echo "Copying Header...\n";
$headerFile = fopen_utf8('misc/import/header.csv', 'rb');
if (!$headerFile) error('Unable to read Header CSV file.');
while (!feof($headerFile))
	fwrite($cardsFile, fgets($headerFile));
fclose($headerFile);

// Copy orginal vanguard avatars.
echo "Copying orginal Vanguard avatars...\n";
$origVanguardAvatarFile = fopen_utf8('misc/import/orginalVanguardAvatars.csv', 'rb');
if (!$origVanguardAvatarFile) error('Unable to read orginal Vanguard CSV file.');
while (!feof($origVanguardAvatarFile))
	fwrite($cardsFile, fgets($origVanguardAvatarFile));
fclose($origVanguardAvatarFile);

// Copy extra vanguard avatars.
echo "Copying extra Vanguard avatars...\n";
$vanguardAvatarFile = fopen_utf8('misc/import/extraVanguardAvatars.csv', 'rb');
if (!$vanguardAvatarFile) error('Unable to read extra Vanguard CSV file.');
while (!feof($vanguardAvatarFile))
	fwrite($cardsFile, fgets($vanguardAvatarFile));
fclose($vanguardAvatarFile);

// Copy planes.
/*echo "Copying Planes...\n";
$planesFile = fopen_utf8('misc/import/planes.csv', 'rb');
if (!$planesFile) error('Unable to read Plane CSV file.');
while (!feof($planesFile))
	fwrite($cardsFile, fgets($planesFile));
fclose($planesFile);*/

// Copy schemes.
/*echo "Copying Schemes...\n";
$schemesFile = fopen_utf8('misc/import/schemes.csv', 'rb');
if (!$schemesFile) error('Unable to read Scheme CSV file.');
while (!feof($schemesFile))
	fwrite($cardsFile, fgets($schemesFile));
fclose($schemesFile);*/

// Copy Sorcerer's Apprentice set.
echo "Copying Sorcerer's Apprentice Cards...\n";
$sorcFile = fopen_utf8('misc/import/sorcerersApprentice.csv', 'rb');
if (!$sorcFile) error('Unable to read Sorcerer\'s Apprentice Cards CSV file.');
while (!feof($sorcFile))
	fwrite($cardsFile, fgets($sorcFile));
fclose($sorcFile);

// Copy Astral Set.
echo "Copying Astral Cards...\n";
$astralFile = fopen_utf8('misc/import/astralCards.csv', 'rb');
if (!$astralFile) error('Unable to read Astral Set CSV file.');
while (!feof($astralFile))
	fwrite($cardsFile, fgets($astralFile));
fclose($astralFile);

// Copy Dreamcast Set.
echo "Copying Dreamcast Cards...\n";
$sdcFile = fopen_utf8('misc/import/dreamcastCards.csv', 'rb');
if (!$sdcFile) error('Unable to read Dreamcast Set CSV file.');
while (!feof($sdcFile))
	fwrite($cardsFile, fgets($sdcFile));
fclose($sdcFile);

// Copy World Magic Cup Cards.
/*echo "Copying World Magic Cup Cards...\n";
$wmcFile = fopen_utf8('misc/import/worldMagicCupCards.csv', 'rb');
if (!$wmcFile) error('Unable to read World Magic Cup Cards CSV file.');
while (!feof($wmcFile))
	fwrite($cardsFile, fgets($wmcFile));
fclose($wmcFile);*/

// Copy Legacy Championship Cards.
echo "Copying Legacy Championship Cards...\n";
$legacyFile = fopen_utf8('misc/import/legacyChampionshipCards.csv', 'rb');
if (!$legacyFile) error('Unable to read World Magic Cup Cards CSV file.');
while (!feof($legacyFile))
	fwrite($cardsFile, fgets($legacyFile));
fclose($legacyFile);

// Copy Vintage Championship Cards.
echo "Copying Vintage Championship Cards...\n";
$vintageFile = fopen_utf8('misc/import/vintageChampionshipCards.csv', 'rb');
if (!$vintageFile) error('Unable to read World Magic Cup Cards CSV file.');
while (!feof($vintageFile))
	fwrite($cardsFile, fgets($vintageFile));
fclose($vintageFile);

// Copy Theros Hero Cards.
/*echo "Copying Theros Hero Cards...\n";
$heroFile = fopen_utf8('misc/import/heroCards.csv', 'rb');
if (!$heroFile) error('Unable to read Theros Hero Cards CSV file.');
while (!feof($heroFile))
	fwrite($cardsFile, fgets($heroFile));
fclose($heroFile);*/

// Write Gatherer Extractor.
$gathererExtractor = new GathererExtractor($files[0]);
$cards = $gathererExtractor->cards;
foreach ($cards as $card)
	writeCsvRow($cardsFile, CardDB::cardToRow($card));

if (file_exists('data/cards.csv')) {
	$oldCards = 'data/cards.csv';
}
fclose($cardsFile);

echo "\n" . count($cards) . " cards processed.\n";
echo "Temporary file complete.\n";

if (file_exists('data/cards.csv')) {
	echo "Backing up file \"data/cards.csv\" to \"data/cards.csv.bak\"...\n";
	@unlink('data/cards.csv.bak');
	@rename('data/cards.csv', 'data/cards.csv.bak');
}

echo "Moving temporary file to \"data/cards.csv\"...\n";
rename('data/cards.csv.temp', 'data/cards.csv');

echo "Import complete.\n";

?>
