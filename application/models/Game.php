<?php
class Model_Game {

	const MAX_POSITIONS = 39;

	const CARD_COMMUNITY_CHEST = 1;
	const CARD_CHANCE = 2;

	const NUM_CCHEST_CARDS = 17;
	const NUM_CHANCE_CARDS = 16;

	const TSTAGE_PRE_ROLL = 1;
	const TSTAGE_POST_ROLL = 2;

	const GO_MONEY = 200;

	// action log
	protected $log = array();
	protected $log_file = "C:/www2/monopoly/log.txt";
	protected $state_file = "C:/www2/monopoly/game.txt";

	protected $valid_commands = array(
		'start_game',
		'roll_dice', 'buy_property', 'pass_property',
		'auction_bid', 'auction_pass', 'use_goojf',
		'build_building', 'mortage', 'sell_building',
		'trade', 'end_turn', 'quit'
	);

	protected $cards = array(
		'chance' => array(),
		'cchest' => array(),
		'chance_index' => 0,
		'cchest_index' => 0
	);

	protected $players = array();
	protected $players_playing = 0;

	protected $turn_count = 0;

	/**
	 * The current player
	 * @var int
	 */
	protected $player_turn;

	/**
	 * The exact part of the turn
	 * @var int
	 */
	protected $turn_stage;

	protected $properties = array();

	public function __construct() {
		//$this->state = json_decode(file_get_contents($this->state_file));
		//$this->log = json_decode(file_get_contents($this->log_file));

		$this->board = new Model_Board();
		$this->loadState();
	}

	/**
	 * After lobby, game is initialized
	 */
	public function start() {
		$this->log("game_start");
		$this->init(); //initialize board, decks + players

		$this->player_turn = $this->pickRandomPlayer();
		$this->turn_stage = self::TSTAGE_PRE_ROLL;
		$this->log("player_to_start", $this->player_turn);

		$this->saveState();
	}


	protected function init() {
		$this->players_playing = 3;

		$this->players[] = new stdClass();
		$this->players[0]->id = 1;
		$this->players[0]->name = "Fred";
		$this->players[0]->position = 0;
		$this->players[0]->playing = true;
		$this->players[0]->in_jail = false;
		$this->players[0]->jail_roll_count = 0;
		$this->players[0]->previous_doubles = array(false, false);

		$this->players[] = new stdClass();
		$this->players[1]->id = 2;
		$this->players[1]->name = "John";
		$this->players[1]->position = 0;
		$this->players[1]->playing = true;
		$this->players[1]->in_jail = false;
		$this->players[1]->jail_roll_count = 0;
		$this->players[1]->previous_doubles = array(false, false);

		$this->players[] = new stdClass();
		$this->players[2]->id = 3;
		$this->players[2]->name = "Emily";
		$this->players[2]->position = 0;
		$this->players[2]->playing = true;
		$this->players[2]->in_jail = false;
		$this->players[2]->jail_roll_count = 0;
		$this->players[2]->previous_doubles = array(false, false);

		//$p1 = $this->addPlayer(346, "Fred", 1);

		for ($i = 0; $i <= Model_Game::MAX_POSITIONS; $i++) {
			if ($this->board->isPurchasable($i)) {
				$this->properties[$i] = new stdClass();
				$this->properties[$i]->owner = false;
				$this->properties[$i]->houses = 0;
				$this->properties[$i]->hotels = 0;
				$this->properties[$i]->mortgaged = false;
			}
		}

		// init community chest + chance indexes
		for ($i = 0; $i < self::NUM_CCHEST_CARDS; $i++) {
			$this->cards['cchest'][$i] = $i;
		}
		for ($i = 0; $i < self::NUM_CHANCE_CARDS; $i++) {
			$this->cards['chance'][$i] = $i;
		}

		shuffle($this->cards['cchest']);
		shuffle($this->cards['chance']);
	}

	/**
	 * Handle a player command.
	 *
	 * @param String $command
	 * @param int $player
	 * @return boolean true if the command was successful
	 */
	public function issueCommand($command, $player) {
		/*
		 *
		'roll_dice', 'buy_property', 'pass_property',
		'auction_bid', 'auction_pass',
		'build_building', 'mortage', 'unmortgage', 'sell_building',
		'trade', 'end_turn', 'quit'
		 *
		 */
		if ($this->commandIsValid($command, $player)) {

			switch ($command) {
				case 'roll_dice':
					$dice1 = $this->rollDice();
					$dice2 = $this->rollDice();

					$this->log($command, $player, $dice1, $dice2);

					// check for doubles
					$double = $dice1 == $dice2;

					if ($double && $this->players[$player]->previous_doubles[0] && $this->players[$player]->previous_doubles[1]) {
						// 3 doubles in a row
						$this->sendToJail($player);
						break;
					}
					else {
						$this->players[$player]->previous_doubles[1] = $this->players[$player]->previous_doubles[0];
						$this->players[$player]->previous_doubles[0] = $double;
					}

					$this->movePlayer($dice1 + $dice2, $player);
					$this->turn_stage = self::TSTAGE_POST_ROLL;

					// what did they land on, what should happen next?
					$this->playerLandedOnSpace($player, $this->players[$player]->position);
					break;

				case 'buy_property':
					$this->playerBuyProperty($player, $this->players[$player]->position);
					break;

				case 'pass_property':
					break;

				case 'end_turn':
					$this->nextPlayerTurn();
					break;
			}

			// end of things to do
			$this->saveState();
			return true;
		}
		return false;
	}

	/**
	 * Check if a player is allowed to perform an action requested
	 * @param String $command
	 * @param int $player
	 */
	protected function commandIsValid($command, $player) {
		if ($command == 'quit') {
			return true;
		}

		if ($this->player_turn != $player) {
			return false;
		}

		if (!in_array($command, $this->valid_commands)) {
			return false;
		}

		if (($command == 'auction_bid' || $command == 'auction_pass') && $this->auctionInProgress()) {
			return true;
		}

		if ($this->players[$player]->in_jail) {
			return $command == 'roll_dice';
		}

		switch ($this->turn_stage) {
			case self::TSTAGE_PRE_ROLL:
				if ($command == 'roll_dice' || $command == 'build_building' || $command == 'mortage' || $command == 'unmortgage' || $command == 'sell_building' || $command == 'trade') {
					return true;
				}
				break;
			case self::TSTAGE_POST_ROLL:
				if ($command == 'buy_property' || $command == 'pass_property' || $command == 'build_building' || $command == 'mortage' || $command == 'unmortgage'  || $command == 'sell_building' || $command == 'trade' || $command == 'end_turn') {
					return true;
				}
				break;
		}

		return false;
	}

	protected function playerLandedOnSpace($player, $position) {


		if ($this->board->isCommunityChest($position)) {
			$this->drawComunityChestCard($player);
		}
		elseif ($this->board->isChance($position)) {
			$this->drawChanceCard($player);
		}
			// Income Tax
		elseif ($position == Model_Board::POSITION_INCOME_TAX) {
			$taxable = $this->getNetWorthOf($player);

			if ($taxable > 2000) {
				$tax = 200;
			}
			else {
				$tax = round($taxable * 0.1);
			}

			$this->playerPayTax($player, $tax);
		}

		// free parking
		elseif ($position == Model_Board::POSITION_JAIL) {
			// noop
		}

		// free parking
		elseif ($position == Model_Board::POSITION_FREE_PARKING) {
			// maybe get fine money?
			// TODO
		}

		// go to Jail
		elseif ($position == Model_Board::POSITION_GO_TO_JAIL) {
			$this->players[$player]->position = self::POSITION_JAIL;
			$this->players[$player]->in_jail = true;
			$this->nextPlayerTurn();
		}

		// Super Tax
		elseif ($position == Model_Board::POSITION_SUPER_TAX) {
			$this->playerPayTax($player, 100);
		}

		// Property space
		else {
			// who owns it?
			$owner = $this->whoOwns($position);

			if ($owner === false) {
				// back to the player for next action
			}
			elseif ($owner != $player) {
				$rent_due = $this->calcRentDue($position);
				$this->playerPayRent($player, $owner, $rent_due);
			}
		}
	}

	protected function pickRandomPlayer() {
		return 0;
	}

	protected function log($cmd) {
		$args = func_get_args();

		$log = array('cmd' => $cmd);

		$args = func_get_args();
		array_shift($args);

		$log['params'] = implode(',', $args);
		//$log["turn_count"] = $this->turn_count;
		$this->log[] = $log;
	}

	protected function saveState() {
		$state = new stdClass();
		$state->player_turn = $this->player_turn;
		$state->turn_stage = $this->turn_stage;
		$state->turn_count = $this->turn_count;
		$state->players = $this->players;
		$state->properties = $this->properties;

		file_put_contents($this->state_file, serialize($state));
		file_put_contents($this->log_file, json_encode($this->log));
	}

	protected function loadState() {
		$state = unserialize(file_get_contents($this->state_file));

		if ($state) {
			$this->player_turn = $state->player_turn;
			$this->turn_stage = $state->turn_stage;
			$this->turn_count = $state->turn_count;
			$this->players = $state->players;
			$this->properties = $state->properties;
		}
	}

	/**
	 * TODO
	 * Enter description here ...
	 */
	protected function auctionInProgress() {
		return false;
	}

	protected function rollDice($max=6) {
		return rand(1, $max);
	}

	/**
	 * Update the player position based on dice roll
	 * @param int $spaces
	 * @param int $player
	 */
	protected function movePlayer($spaces, $player) {
		$current_position = $this->players[$player]->position;

		$full_new_pos = $this->players[$player]->position + $spaces;
		$new_position = $full_new_pos % self::MAX_POSITIONS;

		// have they passed GO?
		if ($full_new_pos >= self::MAX_POSITIONS) {
			$this->playerPassedGo($player);
		}

		$this->players[$player]->position = $new_position;
	}

	/**
	 * Utility function - get player object from state
	 * @param int $player
	 */
	protected function getPlayer($player) {
		return $this->players[$player];
	}

	/**
	 * Player has passed go
	 * @param int $player
	 */
	protected function playerPassedGo($player) {
		$this->log("passed_go", $player);
		$this->players[$player]->money += self::GO_MONEY;
	}

	protected function drawComunityChestCard($player) {
		// increment index
		$this->cards['cchest_index'] = ($this->cards['cchest_index'] + 1) % self::NUM_CCHEST_CARDS;

		// TODO
		// erm lookup card by ID - do stuff...

	}

	protected function drawChanceCard($player) {
		$this->cards['chance_index'] = ($this->cards['chance_index'] + 1) % self::NUM_CHANCE_CARDS;
	}

	protected function getNetWorthOf($player) {
		// TODO
		return 5;
	}

	protected function playerPayTax($player, $tax) {
		$this->log("player_pay_tax", $player, $tax);

		$this->players[$player]->cash -= $tax;

		if ($this->players[$player]->cash < 0) {
			// does this player have enough to cover debt
			$networth = $this->getNetWorthOf($player);
			$remainder = abs($this->players[$player]->cash);

			if ($networth >= $remainder) {
				// has enough money, player will need to find funds
				$this->log("player_in_debt", $player);
			}
			else {
				$this->playerLost($player);
				// turn over all properties to bank
				//TODO
			}
		}
	}

	/**
	 * Player owes rent to property owner
	 * @param int $player
	 * @param int $owner
	 * @param int $rent_due
	 */
	protected function playerPayRent($player, $owner, $rent) {
		$this->log("player_pay_rent", $player, $rent);

		$this->players[$player]->cash -= $rent;

		if ($this->players[$player]->cash < 0) {
			// does this player have enough to cover debt
			$networth = $this->getNetWorthOf($player);
			$remainder = abs($this->players[$player]->cash);

			if ($networth >= $remainder) {
				// has enough money, player will need to find funds
				$this->log("player_in_debt", $player);
			}
			else {
				$this->playerLost($player);
				// turn over all properties to $owner
				//TODO
			}
		}
	}

	protected function calcRentDue($position) {
		return 100;
		//TODO;
	}

	protected function playerLost($player) {
		$this->log("player_lost", $player);
	}

	protected function whoOwns($position) {
		return $this->properties[$position]->owner;
	}

	protected function nextPlayerTurn() {
		$this->log("end_turn", $this->player_turn);

		$player_found = false;
		$next_player = null;
		$index = $this->player_turn+1;
		$count = 0;

		while(!$player_found && $count <= $this->players_playing) {
			if (isset($this->players[$index])) {
				if ($this->players[$index]->playing) {
					$player_found = true;
					$next_player = $index;
				}
				else {
					$index++;
				}
			}
			else {
				$index = 0;
			}
			$count++;
		}

		if ($player_found) {
			$this->player_turn = $next_player;
			$this->log("start_turn", $this->player_turn);
		}
		else {
			// nobody is left :(
			$this->gameEnd();
		}
	}

	protected function sendToJail($player) {
		$this->log("player_in_jail", $player);
		$this->movePlayerTo($player, Model_Board::POSITION_JAIL);
		$this->players[$player]->in_jail = true;
		$this->nextPlayerTurn();

		// clear out the double history
		$this->players[$player]->previous_doubles[0] = false;
		$this->players[$player]->previous_doubles[1] = false;
	}

	protected function playerBuyProperty($player, $position) {
		$owner = $this->whoOwns($position);
		$price = $this->board->getPriceOf($position);

		if ($owner === false) {
			if ($this->players[$player]->cash >= $price) {
				$this->log("player_buy_property", $player, $position);
				$this->players[$player]->cash -= $price;
				$this->properties[$position]->owner = $player;
			}
		}

	}

	protected function gameEnd() {
		$this->log("game_end");
	}

}