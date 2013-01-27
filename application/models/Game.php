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

	const JAIL_ROLL_FORCE_FINE = 3;

    const DEBUG_MODE = true;

	// action log
	protected $log = array();
	protected $log_file = "C:/wamp/www/monopoly/log.txt";
	protected $state_file = "C:/wamp/www/monopoly/game.txt";
    protected $debug_file = "C:/wamp/www/monopoly/debug.txt";


	protected $valid_commands = array(
		'start_game',
		'roll_dice', 'buy_property', 'pass_property',
		'auction_bid', 'auction_pass', 'use_goojf',
		'build_building', 'mortgage', 'sell_building',
		'trade', 'end_turn', 'quit'
	);

	protected $cards = array(
		self::CARD_COMMUNITY_CHEST => array(),
		self::CARD_CHANCE => array()
	);

	protected $card_index = array(
		self::CARD_COMMUNITY_CHEST => 0,
		self::CARD_CHANCE => 0
	);

	/**
	 * @var Model_Board
	 */
	protected $board;

	protected $last_roll = 0;

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

	public function __construct($load_state=true) {
		$this->board = new Model_Board();

		if ($load_state) {
			$this->loadState();
		}
	}

	public function reset() {
		file_put_contents($this->log_file, '');
		file_put_contents($this->state_file, '');
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
		$this->players_playing = 2; //3

		$this->players[] = new Model_Player();
		$this->players[0]->id = 1;
		$this->players[0]->name = 'Fred';
		$this->players[0]->token = 'car';

		$this->players[] = new Model_Player();
		$this->players[1]->id = 2;
		$this->players[1]->name = 'John';
		$this->players[1]->token = 'shoe';

		//$this->players[] = new Model_Player();
		//$this->players[2]->id = 3;
		//$this->players[2]->name = "Emily";

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
			$this->cards[self::CARD_COMMUNITY_CHEST][] = $i;
		}
		for ($i = 0; $i < self::NUM_CHANCE_CARDS; $i++) {
			$this->cards[self::CARD_CHANCE][] = $i;
		}

		shuffle($this->cards[self::CARD_COMMUNITY_CHEST]);
		shuffle($this->cards[self::CARD_CHANCE]);
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

        try {
            if ($this->commandIsValid($command, $player)) {

                switch ($command) {
                    case 'roll_dice':
                        $dice1 = $this->rollDice();
                        $dice2 = $this->rollDice();
                        $this->log($command, $player, $dice1, $dice2);
                        $this->last_roll = $dice1 + $dice2;

                        $just_released_from_jail = false;

                        if ($this->players[$player]->in_jail) {

                            if ($dice1 == $dice2) {
                                // get out of jail
                                $this->players[$player]->in_jail = false;
                                $just_released_from_jail = true;
                            }
                            else {
                                $this->players[$player]->jail_roll_count++;

                                if ($this->players[$player]->jail_roll_count == self::JAIL_ROLL_FORCE_FINE) {
                                    $this->playerPayTax($player, 50);
                                    $this->players[$player]->in_jail = false;
                                    $just_released_from_jail = true;
                                }
                            }
                        }

                        if (!$this->players[$player]->in_jail) {
                            if (!$just_released_from_jail) {
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
                            }

                            $this->movePlayer($this->last_roll, $player);
                            $this->turn_stage = self::TSTAGE_POST_ROLL;

                            // what did they land on, what should happen next?
                            $this->playerLandedOnSpace($player, $this->players[$player]->position);
                            break;
                        }

                    case 'buy_property':
                        $this->playerBuyProperty($player, $this->players[$player]->position);
                        break;

                    case 'pass_property':
                        break;

                    case 'use_goojf':
                        $this->players[$player]->in_jail = false;

                        $this->movePlayer($this->last_roll, $player);
                        $this->turn_stage = self::TSTAGE_POST_ROLL;

                        // what did they land on, what should happen next?
                        $this->playerLandedOnSpace($player, $this->players[$player]->position);

                        break;

                    case 'end_turn':
                        $this->nextPlayerTurn();
                        break;
                }

                // end of things to do
                $this->saveState();
                return true;
            }
        }
        catch (InvalidCommandException $e) {
            $this->debug($e->getMessage());
            return false;
        }

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
            throw new InvalidCommandException('Player ' . $player . ' tried take their turn when it was ' . $this->player_turn);
		}

		if (!in_array($command, $this->valid_commands)) {
            throw new InvalidCommandException($command . ' is not a valid command');
		}

		if (($command == 'auction_bid' || $command == 'auction_pass') && $this->auctionInProgress()) {
			return true;
		}

		if ($this->players[$player]->in_jail) {
			if ($command == 'roll_dice' || $command == 'use_goojf' || $command == 'build_building' || $command == 'mortage' || $command == 'unmortgage' || $command == 'sell_building' || $command == 'trade') {
				return true;
			}
		}

		switch ($this->turn_stage) {
			case self::TSTAGE_PRE_ROLL:
				if ($command == 'roll_dice' || $command == 'build_building' || $command == 'mortage' || $command == 'unmortgage' || $command == 'sell_building' || $command == 'trade') {
					return true;
				}
				break;
			case self::TSTAGE_POST_ROLL:
				if ($this->players[$player]->previous_doubles[0]) {
					if ($command == 'end_turn') {
                        throw new InvalidCommandException('Player cannot end their turn if they rolled a double');
					}
				}
				if ($command == 'buy_property' || $command == 'pass_property' || $command == 'build_building' || $command == 'mortage' || $command == 'unmortgage'  || $command == 'sell_building' || $command == 'trade' || $command == 'end_turn') {
					return true;
				}
				break;
		}

        throw new InvalidCommandException('Command: ' . $command . ' could not be handled');
	}

	protected function playerLandedOnSpace($player, $position) {
		$this->log("player_landed_on", $player, $position);

		if ($this->board->isCommunityChest($position)) {
			$this->drawCard(self::CARD_COMMUNITY_CHEST, $player);
		}
		elseif ($this->board->isChance($position)) {
			$this->drawCard(self::CARD_CHANCE, $player);
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

		if (func_num_args() > 1) {
			$log['params'] = implode(',', $args);
		}
		//$log["turn_count"] = $this->turn_count;
		$this->log[] = $log;
	}

    protected function debug($msg) {
        file_put_contents($this->debug_file, $msg . "\n", FILE_APPEND);
    }

	public function getLog($from=0) {
		return array_slice($this->log, $from);
	}

	public function getJsState() {
		$state = new stdClass();
		$state->players = $this->players;
		$state->player_turn = $this->player_turn;
		$state->properties = $this->properties;

		return $state;
	}

	protected function saveState() {
		$state = new stdClass();
		$state->player_turn = $this->player_turn;
		$state->turn_stage = $this->turn_stage;
		$state->turn_count = $this->turn_count;
		$state->last_roll = $this->last_roll;
		$state->players = $this->players;
		$state->players_playing = $this->players_playing;
		$state->properties = $this->properties;
		$state->cards = $this->cards;
		$state->card_index = $this->card_index;

		file_put_contents($this->state_file, serialize($state));
		file_put_contents($this->log_file, json_encode($this->log));
	}

	protected function loadState() {
		if (file_exists($this->state_file)) {
			$state = unserialize(file_get_contents($this->state_file));
		}
		if (file_exists($this->log_file)) {
			$log = json_decode(file_get_contents($this->log_file));
		}

		if (isset($state) && $state != '') {
			$this->player_turn = $state->player_turn;
			$this->turn_stage = $state->turn_stage;
			$this->turn_count = $state->turn_count;
			$this->last_roll = $state->last_roll;
			$this->players = $state->players;
			$this->players_playing = $state->players_playing;
			$this->properties = $state->properties;
			$this->cards = $state->cards;
			$this->card_index = $state->card_index;
		}

		if (isset($log) && $log != '') {
			$this->log = $log;
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

	public function movePlayerTo($player, $to, $ignore_land=false) {
		$this->players[$player]->position = $to;
		$this->log("move_player", $player, $to);

		if (!$ignore_land) {
			$this->playerLandedOnSpace($player, $to);
		}
	}

	/**
	 * Utility function - get player object from state
	 * @param int $player
	 */
	public function getPlayer($player) {
		return $this->players[$player];
	}

	/**
	 * Returns an array of player indexes
	 */
	public function getPlayersPlaying() {
		$playing = array();
		foreach ($this->players as $i=>$p) {
			if ($p->playing) {
				$playing[] = $i;
			}
		}
		return $playing;
	}

	public function whoOwns($position) {
		return $this->properties[$position]->owner;
	}

	public function getLastRoll() {
		return $this->last_roll;
	}

	public function calcRentDue($position) {
		$owner = $this->properties[$position]->owner;
		if ($owner !== false) {
			if ($position == Model_Board::POSITION_STATION_1 || $position == Model_Board::POSITION_STATION_2 || $position == Model_Board::POSITION_STATION_3 || $position == Model_Board::POSITION_STATION_4) {
				// stations
				$count = 0;
				if ($this->whoOwns(Model_Board::POSITION_STATION_1) == $owner) {
					$count++;
				}
				if ($this->whoOwns(Model_Board::POSITION_STATION_2) == $owner) {
					$count++;
				}
				if ($this->whoOwns(Model_Board::POSITION_STATION_3) == $owner) {
					$count++;
				}
				if ($this->whoOwns(Model_Board::POSITION_STATION_4) == $owner) {
					$count++;
				}

				if ($count == 4) {
					return 200;
				}
				elseif ($count == 3) {
					return 100;
				}
				elseif ($count == 2) {
					return 50;
				}
				else {
					return 25;
				}
			}
			elseif ($position == Model_Board::POSITION_ELECTRIC_COMPANY || $position == Model_Board::POSITION_WATER_WORKS) {
				// utilities
				$count = 0;
				if ($this->whoOwns(Model_Board::POSITION_ELECTRIC_COMPANY) == $owner) {
					$count++;
				}
				if ($this->whoOwns(Model_Board::POSITION_WATER_WORKS) == $owner) {
					$count++;
				}

				if ($count == 2) {
					return $this->last_roll * 10;
				}
				else {
					return $this->last_roll * 4;
				}
			}
			else {
				// normal properties
				if ($this->properties[$position]->mortgaged) {
					return 0;
				}

				if ($this->properties[$position]->houses == 0 && $this->properties[$position]->hotels == 0) {
					$base = $this->board->getBaseRent($position);

					$properties = $this->board->getPropertiesOfColourGroup($this->board->getColourGroup($position));

					// assume owner controls all of that colour group
					$owns_all = true;

					foreach ($properties as $p) {
						if ($this->properties[$p]->owner != $owner) {
							$owns_all = false;
							break;
						}
					}

					if ($owns_all) {
						return $base * 2;
					}

					return $base;
				}

				$rent = 0;
				if ($this->properties[$position]->hotels > 0) {
					$rent = $this->board->getRentWithHotel($position, $this->properties[$position]->hotels);
				}
				if ($this->properties[$position]->houses > 0) {
					$rent += $this->board->getRentWithHouse($position, $this->properties[$position]->houses);
				}

				return $rent;
			}
		}
		return 0;
	}

	public function givePlayerCash($player, $cash) {
		$this->players[$player]->cash += $cash;
		$this->log("player_get_cash", $player, $cash);
	}

	public function giveGoojfCard($player) {
		$this->players[$player]->goojf_count++;
		$this->log("player_get_goojf_card", $player);
	}

	public function getHouseCountForPlayer($player) {
		$count = 0;

		foreach ($this->properties as $p) {
			if ($p->owner == $player) {
				$count += $p->houses;
			}
		}
		return $count;
	}

	public function getHotelCountForPlayer($player) {
		$count = 0;

		foreach ($this->properties as $p) {
			if ($p->owner == $player) {
				$count += $p->hotels;
			}
		}
		return $count;
	}

	/**
	 * Player has passed go
	 * @param int $player
	 */
	public function playerPassedGo($player) {
		$this->log("passed_go", $player);
		$this->players[$player]->money += self::GO_MONEY;
	}

	protected function drawCard($type, $player) {
		$index = $this->card_index[$type];
		$card = $this->cards[$type][$index];

		$this->log("draw_card", $type, $card);

		$this->board->drawCard($type, $card, $player, $this);

		$index = ($index + 1) % count($this->cards[$type]);
		$this->card_index[$type] = $index;
	}

	protected function getNetWorthOf($player) {
		// TODO
		return 5;
	}

	public function playerPayTax($player, $tax) {
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
	 * Player owes rent to another player
	 * @param int $from
	 * @param int $to
	 * @param int $amount
	 */
	public function playerPayRent($from, $to, $amount) {
		$this->log("player_pay_rent", $from, $amount);

		$from_cash = $this->players[$from]->cash;

		if ($from_cash >= $amount) {
			// amount fully covered in cash
			$this->players[$from]->cash -= $amount;
			$this->players[$to]->cash += $amount;
			return;
		}

		$this->players[$from]->cash -= $amount;
		$this->players[$to]->cash += $from_cash; // hand all money over

		// does this player have enough to cover debt
		$networth = $this->getNetWorthOf($from);
		$remainder = abs($this->players[$from]->cash);

		if ($networth >= $remainder) {
			// has enough money, player will need to find funds
			$this->log("player_in_debt", $from);
		}
		else {
			$this->playerLost($from);
			// turn over all properties to $owner
			//TODO
		}
	}

	/**
	 * Player owes money to another player
	 * @param int $from
	 * @param int $to
	 * @param int $amount
	 */
	public function playerPayPlayer($from, $to, $amount) {
		$this->log("player_pay_player", $from, $amount);

		$from_cash = $this->players[$from]->cash;

		if ($from_cash >= $amount) {
			// amount fully covered in cash
			$this->players[$from]->cash -= $amount;
			$this->players[$to]->cash += $amount;
			return;
		}

		$this->players[$from]->cash -= $amount;
		$this->players[$to]->cash += $from_cash; // hand all money over

		// does this player have enough to cover debt
		$networth = $this->getNetWorthOf($from);
		$remainder = abs($this->players[$from]->cash);

		if ($networth >= $remainder) {
			// has enough money, player will need to find funds
			$this->log("player_in_debt", $from);
		}
		else {
			$this->playerLost($from);
		}
	}

	protected function playerLost($player) {
		$this->log("player_lost", $player);
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
					break;
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
			$this->turn_stage = self::TSTAGE_PRE_ROLL;
			$this->log("start_turn", $this->player_turn);
		}
		else {
			// nobody is left :(
			$this->gameEnd();
		}
	}

	public function sendPlayerToJail($player) {
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

class InvalidCommandException extends Zend_Exception {}