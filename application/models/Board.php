<?php
class Model_Board {

	const POSITION_JAIL = 10;
	const POSITION_INCOME_TAX = 4;
	const POSITION_FREE_PARKING = 20;
	const POSITION_GO_TO_JAIL = 30;
	const POSITION_SUPER_TAX = 38;


	private $names = array(
		"Go", "Old Kent Road", "Community Chest", "Whitechapel Road", "Income Tax", "King's Cross Station", "Angel Islington", "Chance", "Euston Road", "Pentonville Road",
		"Jail", "Pall Mall", "Electric Company", "Whitehall", "Northumberland Avenue", "Marylebone Station", "Bow Street", "Community Chest", "Marlborough Street", "Vine Street",
		"Free Parking", "Strand", "Chance", "Fleet Sreet", "Trafalgar Square", "Fenchurch Street", "Leicester Square", "Coventry Street", "Water Works", "Picaddily",
		"Go To Jail", "Regent Street", "Oxford Street", "Community Chest", "Bond Street", "Liverpool St Station", "Chance", "Park Lane", "Super Tax", "Mayfair"
	);

	private $chance_positions = array(7, 22, 36);
	private $cchest_positions = array(2, 17, 33);

	private $costs = array (
		0, 60, 0, 60, 0, 200, 100, 0, 100, 120,
		0, 140, 150, 140, 160, 200, 180, 0, 180, 200,
		0, 220, 0, 220, 240, 200, 260, 260, 150, 280,
		0, 300, 300, 0, 320, 100, 0, 350, 0, 400
	);

	private $purchasable = array (
		false, true, false, true, false, true, true, false, true, true,
		false, true, true, true, true, true, true, true, true, true,
		false, true, false, true, true, true, true, true, true, true,
		false, true, true, false, true, true, false, true, false, true
	);

	private $base_rent = array (
		0, 2, 0, 2, 0, 25, 6, 0, 6, 8,
		0, 10, 0, 10, 12, 0, 14, 0, 14, 16,
		0, 220, 0, 220, 240, 200, 260, 260, 150, 280,
		0, 300, 300, 0, 320, 100, 0, 350, 0, 400
	);


	public function __construct() {}

	public function isCommunityChest($pos) {
		return in_array($pos, $this->cchest_positions);
	}

	public function isChance($pos) {
		return in_array($pos, $this->chance_positions);
	}

	public function getPriceOf($position) {
		return $this->costs[$position];
	}

	public function isPurchasable($position) {
		return $this->purchasable[$position];
	}

	public function getBaseRent($position) {
		return $this->base_rent[$position];
	}

	public function getRentWithHouse($position, $houses) {
		return $this->base_rent[$position];
	}

	public function getRentWithHotel($position, $hotel=1) {
		return $this->base_rent[$position];
	}
}