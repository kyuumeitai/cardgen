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
Class GECleanUp {
	private $setDB;
	private $artDB;
	private $titleToCards = array();
	private $titleToFlavors = array();
	
	public function __construct (SetDB $setDB, ArtDB $artDB, $cardsFile, $oldCards) {
		global $config;

		$this->setDB = $setDB;
		$this->artDB = $artDB;

		// Load english cards.
		echo 'Loading card data';
		$file = fopen_utf8("$cardsFile", 'r');
		if (!$file) error("Unable to open file: $cardsFile");
		$i = 0;
		while (($row = fgetcsv($file, 10000, ',')) !== FALSE) {
			if ($i++ % 400 == 0) echo '.';
	
			$card = CardDB::rowToCard($row);
			// Ignore cards with an unknown set.
			$card->set = $setDB->normalize($card->set);
	
			if (!$card->set) continue;
	
			$this->titleToCards[] = $card;
		}
		fclose($file);
		echo "\n";
	}
	
	
}

?>