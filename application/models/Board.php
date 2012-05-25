<?php
class Model_Board {

	const POSITION_GO = 0;
	const POSITION_INCOME_TAX = 4;
	const POSITION_KINGS_CROSS_STATION = 5;
	const POSITION_JAIL = 10;
	const POSITION_ELECTRIC_COMPANY = 12;
	const POSITION_MARYLEBONE_STATION = 15;
	const POSITION_FREE_PARKING = 20;
	const POSITION_TRAFALGAR_SQUARE = 24;
	const POSITION_FENCHURCH_STREET_STATION = 25;
	const POSITION_WATER_WORKS = 28;
	const POSITION_GO_TO_JAIL = 30;
	const POSITION_LIVERPOOL_STREET_STATION = 35;
	const POSITION_SUPER_TAX = 38;


	private $names = array(
		"Go", "Old Kent Road", "Community Chest", "Whitechapel Road", "Income Tax", "King's Cross Station", "Angel Islington", "Chance", "Euston Road", "Pentonville Road",
		"Jail", "Pall Mall", "Electric Company", "Whitehall", "Northumberland Avenue", "Marylebone Station", "Bow Street", "Community Chest", "Marlborough Street", "Vine Street",
		"Free Parking", "Strand", "Chance", "Fleet Sreet", "Trafalgar Square", "Fenchurch Street Station", "Leicester Square", "Coventry Street", "Water Works", "Picaddily",
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

	public function drawCard($type, $card_id, $player, Model_Game &$game) {
		if ($type == Model_Game::CARD_CHANCE) {
			/*
			 *


    Advance to St. Charles Place – if you pass Go, collect $200
    Bank pays you dividend of $50
    Get out of Jail Free – this card may be kept until needed, or traded/sold
    Go back 3 spaces
    Go directly to Jail – do not pass Go, do not collect $200
    Make general repairs on all your property – for each house pay $25 – for each hotel $100
    Pay poor tax of $15
    Take a trip to Reading Railroad – if you pass Go, collect $200
    Take a walk on the Boardwalk – advance token to Boardwalk
    You have been elected chairman of the board – pay each player $50
    Your building loan matures – collect $150
    You have won a crossword competition - collect $100
			 */
			switch ($card_id) {
				case 0:
					//Advance to Go (Collect $200)
					$game->movePlayerTo($player, Model_Board::POSITION_GO);
					$game->passedGo($player);
					break;

				case 1:
					//Advance to Illinois Ave - if you pass Go, collect $200
					if ($game->getPlayer($player)->position > self::POSITION_TRAFALGAR_SQUARE) {
						$game->passedGo($player);
					}
					$game->movePlayerTo($player, self::POSITION_TRAFALGAR_SQUARE);

					break;

				case 2:
					//Advance token to nearest Utility.
					//If unowned, you may buy it from the Bank.
					//If owned, throw dice and pay owner a total ten times the amount thrown.

					if ($game->getPlayer($player)->position > self::POSITION_WATER_WORKS) {
						$game->passedGo($player);
						$landed_on = self::POSITION_ELECTRIC_COMPANY;
					}
					else {
						$game->movePlayerTo($player, self::POSITION_WATER_WORKS);
						$landed_on = self::POSITION_WATER_WORKS;
					}

					$game->movePlayerTo($player, $landed_on);
					$owner = $game->whoOwns(self::POSITION_ELECTRIC_COMPANY);

					if ($owner !== false) {
						//TODO - make this a separate roll
						$owed = $game->getLastRoll() * 10;
						$game->playerPayRent($player, $owner, $owed);
					}
					break;

				case 3:
				case 4:
					//Advance token to the nearest Railroad and pay owner twice the rental to which he/she is otherwise entitled.
					//If Railroad is unowned, you may buy it from the Bank.

					if ($game->getPlayer($player)->position > self::POSITION_LIVERPOOL_STREET_STATION) {
						$game->passedGo($player);
						$landed_on = self::POSITION_KINGS_CROSS_STATION;
					}
					elseif ($game->getPlayer($player)->position > self::POSITION_FENCHURCH_STREET_STATION) {
						$landed_on = self::POSITION_LIVERPOOL_STREET_STATION;
					}
					elseif ($game->getPlayer($player)->position > self::POSITION_MARYLEBONE_STATION) {
						$landed_on = self::POSITION_FENCHURCH_STREET_STATION;
					}
					else {
						$landed_on = self::POSITION_KINGS_CROSS_STATION;
					}

					$game->movePlayerTo($player, $landed_on);
					$due = $game->calcRentDue($landed_on) * 2;
					$game->playerPayRent($player, $owner, $due);

					break;
			}
		}
		else {

		}
		var_dump(func_get_args());
		exit;
	}
}