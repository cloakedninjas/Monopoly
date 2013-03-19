<?php
class Model_Board {

	const POSITION_GO = 0;
	const POSITION_BROWN1 = 1;
	const POSITION_BROWN2 = 3;
	const POSITION_INCOME_TAX = 4;
	const POSITION_STATION_1 = 5;
	const POSITION_BLUE1 = 6;
	const POSITION_CHANCE_1 = 7;
	const POSITION_BLUE2 = 8;
	const POSITION_BLUE3 = 9;
	const POSITION_JAIL = 10;
	const POSITION_PINK1 = 11;
	const POSITION_ELECTRIC_COMPANY = 12;
	const POSITION_PINK2 = 13;
	const POSITION_PINK3 = 14;
	const POSITION_STATION_2 = 15;
	const POSITION_ORANGE1 = 16;
	const POSITION_ORANGE2 = 18;
	const POSITION_ORANGE3 = 19;
	const POSITION_FREE_PARKING = 20;
	const POSITION_RED1 = 21;
	const POSITION_CHANCE_2 = 22;
	const POSITION_RED2 = 23;
	const POSITION_RED3 = 24;
	const POSITION_STATION_3 = 25;
	const POSITION_YELLOW1 = 26;
	const POSITION_YELLOW2 = 27;
	const POSITION_WATER_WORKS = 28;
	const POSITION_YELLOW3 = 29;
	const POSITION_GO_TO_JAIL = 30;
	const POSITION_GREEN1 = 31;
	const POSITION_GREEN2 = 32;
	const POSITION_GREEN3 = 34;
	const POSITION_STATION_4 = 35;
	const POSITION_CHANCE_3 = 36;
	const POSITION_NAVY1 = 37;
	const POSITION_SUPER_TAX = 38;
	const POSITION_NAVY2 = 39;

	const COL_GROUP_BROWN = 0;
	const COL_GROUP_BLUE = 1;
	const COL_GROUP_PINK = 2;
	const COL_GROUP_ORANGE = 3;
	const COL_GROUP_RED = 4;
	const COL_GROUP_YELLOW = 5;
	const COL_GROUP_GREEN = 6;
	const COL_GROUP_NAVY = 7;
	const COL_GROUP_STATION = 8;

	private $names = array(
		"Go", "Old Kent Road", "Community Chest", "Whitechapel Road", "Income Tax", "King's Cross Station", "Angel Islington", "Chance", "Euston Road", "Pentonville Road",
		"Jail", "Pall Mall", "Electric Company", "Whitehall", "Northumberland Avenue", "Marylebone Station", "Bow Street", "Community Chest", "Marlborough Street", "Vine Street",
		"Free Parking", "Strand", "Chance", "Fleet Sreet", "Trafalgar Square", "Fenchurch Street Station", "Leicester Square", "Coventry Street", "Water Works", "Picaddily",
		"Go To Jail", "Regent Street", "Oxford Street", "Community Chest", "Bond Street", "Liverpool St Station", "Chance", "Park Lane", "Super Tax", "Mayfair"
	);

	private $chance_positions = array(7, 22, 36);
	private $cchest_positions = array(2, 17, 33);

	private $colour_groups = array(
		self::COL_GROUP_BROWN => array(
			self::POSITION_BROWN1,
			self::POSITION_BROWN2
		),
		self::COL_GROUP_BLUE => array (
			self::POSITION_BLUE1,
			self::POSITION_BLUE2,
			self::POSITION_BLUE3
		),
		self::COL_GROUP_PINK => array (
			self::POSITION_PINK1,
			self::POSITION_PINK2,
			self::POSITION_PINK3
		),
		self::COL_GROUP_ORANGE => array (
			self::POSITION_ORANGE1,
			self::POSITION_ORANGE2,
			self::POSITION_ORANGE3
		),
		self::COL_GROUP_RED => array (
			self::POSITION_RED1,
			self::POSITION_RED2,
			self::POSITION_RED3
		),
		self::COL_GROUP_YELLOW => array (
			self::POSITION_YELLOW1,
			self::POSITION_YELLOW2,
			self::POSITION_YELLOW3
		),
		self::COL_GROUP_GREEN => array (
			self::POSITION_GREEN1,
			self::POSITION_GREEN2,
			self::POSITION_GREEN3
		),
		self::COL_GROUP_NAVY => array (
			self::POSITION_NAVY1,
			self::POSITION_NAVY2
		),
		
		self::COL_GROUP_STATION => array (
			self::POSITION_STATION_1,
			self::POSITION_STATION_2,
			self::POSITION_STATION_3,
			self::POSITION_STATION_4
		)
	);

	private $costs = array (
		0, 60, 0, 60, 0, 200, 100, 0, 100, 120,
		0, 140, 150, 140, 160, 200, 180, 0, 180, 200,
		0, 220, 0, 220, 240, 200, 260, 260, 150, 280,
		0, 300, 300, 0, 320, 200, 0, 350, 0, 400
	);

	private $purchasable = array (
		false, true, false, true, false, true, true, false, true, true,
		false, true, true, true, true, true, true, true, true, true,
		false, true, false, true, true, true, true, true, true, true,
		false, true, true, false, true, true, false, true, false, true
	);

	private $base_rent = array (
		0, 2, 0, 2, 0, 25, 6, 0, 6, 8,
		0, 10, 0, 10, 12, 25, 14, 0, 14, 16,
		0, 220, 0, 220, 240, 25, 260, 260, 150, 280,
		0, 300, 300, 0, 320, 25, 0, 350, 0, 400
	);

	// TODO - fill in this muthafucka - urgh
	private $rents = array (
		'1_house' => array (
			0, 2, 0, 2, 0, 25, 6, 0, 6, 8,
			0, 10, 0, 10, 12, 25, 14, 0, 14, 16,
			0, 220, 0, 220, 240, 25, 260, 260, 150, 280,
			0, 300, 300, 0, 320, 25, 0, 350, 0, 400
		),
		'2_house' => array (
			0, 2, 0, 2, 0, 25, 6, 0, 6, 8,
			0, 10, 0, 10, 12, 25, 14, 0, 14, 16,
			0, 220, 0, 220, 240, 25, 260, 260, 150, 280,
			0, 300, 300, 0, 320, 25, 0, 350, 0, 400
		),
		'3_house' => array (
			0, 2, 0, 2, 0, 25, 6, 0, 6, 8,
			0, 10, 0, 10, 12, 25, 14, 0, 14, 16,
			0, 220, 0, 220, 240, 25, 260, 260, 150, 280,
			0, 300, 300, 0, 320, 25, 0, 350, 0, 400
		),
		'4_house' => array (
			0, 2, 0, 2, 0, 25, 6, 0, 6, 8,
			0, 10, 0, 10, 12, 25, 14, 0, 14, 16,
			0, 220, 0, 220, 240, 25, 260, 260, 150, 280,
			0, 300, 300, 0, 320, 25, 0, 350, 0, 400
		),
		'hotel' => array (
			0, 2, 0, 2, 0, 25, 6, 0, 6, 8,
			0, 10, 0, 10, 12, 25, 14, 0, 14, 16,
			0, 220, 0, 220, 240, 25, 260, 260, 150, 280,
			0, 300, 300, 0, 320, 25, 0, 350, 0, 400
		)
	);

	public function __construct() {}

	public function isCommunityChest($pos) {
		return in_array($pos, $this->cchest_positions);
	}

	public function isChance($pos) {
		return in_array($pos, $this->chance_positions);
	}

	public function getNameOf($position) {
		return $this->names[$position];
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
		$index = $houses . '_house';
		return $this->rents[$index][$position];
	}

	public function getRentWithHotel($position, $hotel=1) {
		return $this->rents['hotel'][$position] * $hotel;
	}

	public function getColourGroup($position) {
		foreach ($this->colour_groups as $colour=>$group) {
			foreach ($group as $g) {
				if ($g == $position) {
					return $colour;
				}
			}
		}
	}

	public function getPropertiesOfColourGroup($colour) {
		return $this->colour_groups[$colour];
	}

	public function drawCard($type, $card_id, $player, Model_Game &$game) {
		if ($type == Model_Game::CARD_CHANCE) {
			switch ($card_id) {
				case 0:
					//Advance to Go (Collect $200)
					$game->movePlayerTo($player, Model_Board::POSITION_GO);
					$game->playerPassedGo($player);
					break;

				case 1:
					//Advance to Illinois Ave - if you pass Go, collect $200
					if ($game->getPlayer($player)->position > self::POSITION_TRAFALGAR_SQUARE) {
						$game->playerPassedGo($player);
					}
					$game->movePlayerTo($player, self::POSITION_TRAFALGAR_SQUARE);

					break;

				case 2:
					//Advance token to nearest Utility.
					//If unowned, you may buy it from the Bank.
					//If owned, throw dice and pay owner a total ten times the amount thrown.

					if ($game->getPlayer($player)->position == self::POSITION_CHANCE_1) {
						$landed_on = self::POSITION_ELECTRIC_COMPANY;
					}
					elseif ($game->getPlayer($player)->position == self::POSITION_CHANCE_2) {
						$landed_on = self::POSITION_WATER_WORKS;
					}
					else {
						$game->playerPassedGo($player);
						$landed_on = self::POSITION_ELECTRIC_COMPANY;
					}

					$game->movePlayerTo($player, $landed_on, true);
					$owner = $game->whoOwns($landed_on);

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

					if ($game->getPlayer($player)->position == self::POSITION_CHANCE_1) {
						$landed_on = self::POSITION_STATION_2;
					}
					elseif ($game->getPlayer($player)->position == self::POSITION_CHANCE_2) {
						$landed_on = self::POSITION_STATION_3;
					}
					else {
						$game->playerPassedGo($player);
						$landed_on = self::POSITION_STATION_1;
					}

					$game->movePlayerTo($player, $landed_on, true);
					$due = $game->calcRentDue($landed_on) * 2;

					if ($due > 0) {
						$owner = $game->whoOwns($landed_on);
						$game->playerPayRent($player, $owner, $due);
					}
					break;

				case 5:
					//Advance to St. Charles Place – if you pass Go, collect $200

					if ($game->getPlayer($player)->position > self::POSITION_PALL_MALL) {
						$game->playerPassedGo($player);
					}

					$game->movePlayerTo($player, self::POSITION_PALL_MALL);

					break;

				case 6:
					//Bank pays you dividend of $50
					$game->givePlayerCash($player, 50);
					break;

				case 7:
					//Get out of Jail Free – this card may be kept until needed, or traded/sold
					$game->giveGoojfCard($player);
					break;

				case 8:
					//Go back 3 spaces
					$new_position = $game->getPlayer($player)->position - 3;
					$game->movePlayerTo($player, $new_position);
					break;

				case 9:
					//Go directly to Jail – do not pass Go, do not collect $200
					$game->sendPlayerToJail($player);
					break;

				case 10:
					//Make general repairs on all your property
					//for each house pay $25 – for each hotel $100
					$fine = $game->getHouseCountForPlayer($player) * 25;
					$fine += $game->getHotelCountForPlayer($player) * 100;

					if ($fine > 0) {
						$game->playerPayTax($player, $fine);
					}

					break;

				case 11:
					//Pay poor tax of $15
					$game->playerPayTax($player, 15);

					break;

				case 12:
					//Take a trip to Reading Railroad – if you pass Go, collect $200
					$game->playerPassedGo($player); // you HAVE to pass Go to get there
					$game->movePlayerTo($player, self::POSITION_STATION_1);
					break;

				case 13:
					//Take a walk on the Boardwalk – advance token to Boardwalk
					$game->movePlayerTo($player, self::POSITION_MAYFAIR);
					break;

				case 14:
					//You have been elected chairman of the board – pay each player $50
					$players = $game->getCurrentPlayersPlaying();

					foreach ($players as $p) {
						if ($p != $player) {
							$game->playerPayPlayer($player, $p, 50);
						}
					}
					break;

				case 15:
					//Your building loan matures – collect $150
					$game->givePlayerCash($player, 150);
					break;

				case 16:
					//You have won a crossword competition - collect $100
					$game->givePlayerCash($player, 100);
					break;
			}
		}
		else {
			switch ($card_id) {
				case 0:
					//Advance to Go (Collect $200)
					$game->movePlayerTo($player, self::POSITION_GO);
					$game->playerPassedGo($player);
					break;

				case 1:
					//Bank error in your favor – collect $200
					$game->givePlayerCash($player, 200);
					break;

				case 2:
					//Doctor's fees – Pay $50
					$game->playerPayTax($player, 50);
					break;

				case 3:
					//Get Out of Jail Free – this card may be kept until needed, or sold
					$game->giveGoojfCard($player);
					break;

				case 4:
					//Go to Jail – go directly to jail – Do not pass Go, do not collect $200
					$game->sendPlayerToJail($player);
					break;

				case 5:
					//It is your birthday - Collect $10 from each player
					$players = $game->getCurrentPlayersPlaying();

					foreach ($players as $p) {
						if ($p != $player) {
							$game->playerPayPlayer($p, $player, 10);
						}
					}

					break;

				case 6:
					//Grand Opera Night – collect $50 from every player for opening night seats
					$players = $game->getCurrentPlayersPlaying();

					foreach ($players as $p) {
						if ($p != $player) {
							$game->playerPayPlayer($p, $player, 50);
						}
					}

					break;

				case 7:
					//Income Tax refund – collect $20
					$game->givePlayerCash($player, 20);
					break;

				case 8:
					//Life Insurance Matures – collect $100
					$game->givePlayerCash($player, 100);
					break;

				case 9:
					//Pay Hospital Fees of $100
					$game->playerPayTax($player, 100);
					break;

				case 10:
					//Pay School Fees of $50
					$game->playerPayTax($player, 50);
					break;

				case 11:
					//Receive $25 Consultancy Fee
					$game->givePlayerCash($player, 25);
					break;

				case 12:
					//You are assessed for street repairs – $40 per house, $115 per hotel
					$fine = $game->getHouseCountForPlayer($player) * 40;
					$fine += $game->getHotelCountForPlayer($player) * 115;

					$game->playerPayTax($player, $fine);
					break;

				case 13:
					//You have won second prize in a beauty contest– collect $10
					$game->givePlayerCash($player, 10);
					break;

				case 14:
					//You inherit $100
					$game->givePlayerCash($player, 100);
					break;

				case 15:
					//From sale of stock you get $50
					$game->givePlayerCash($player, 50);
					break;

				case 16:
					//Holiday Fund matures - Receive $100
					$game->givePlayerCash($player, 100);
					break;
			}

		}
	}
}