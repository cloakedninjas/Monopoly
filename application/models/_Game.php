<?php
class Model_Game {

	//protected $_dbTableName = 'items';

	const MAX_POSITIONS = 39;

	const CARD_COMMUNITY_CHEST = 1;
	const CARD_CHANCE = 2;

	const POSITION_JAIL = 10;

	protected $state, $logger;
	protected $filename = "C:/www2/monopoly/game.txt";
	protected $log_name = "C:/www2/monopoly/log.txt";

	protected $previous_roll;

	public function __construct() {
		$this->state = json_decode(file_get_contents($this->filename));
		$this->logger = json_decode(file_get_contents($this->log_name));

		$this->board = new Model_Board();
	}

	public function start() {
		$this->state = $this->initState();
		$this->logger = array();
		$this->log("game_start");
		$this->log("player_start_turn", 0);
		$this->saveState();
	}

	public function getState() {
		return $this->state;
	}

	public function getBoard() {
		return $this->board;
	}

	/*
	 * Game Functions
	 */

	public function rollDice() {
		return rand(2, 12);
	}

	public function movePlayer($spaces) {
		$this->previous_roll = $spaces;

		$this->log("dice_roll", $spaces);

		$current_player = $this->getCurrentPlayer();
		$current_position = $this->state->players[$current_player]->position;

		$full_new_pos = $this->state->players[$current_player]->position + $spaces;
		$new_position = $full_new_pos % self::MAX_POSITIONS;

		// have they passed GO?
		if ($full_new_pos >= self::MAX_POSITIONS) {
			$this->passedGo();
		}

		$this->state->players[$current_player]->position = $new_position;

		// decide what to do based on what player landed on:
		return $this->whatNext();
	}

	public function passedGo() {
		$current_player = $this->getCurrentPlayer();
		$this->state->players[$current_player]->money += 200;
		$this->log("player_passed_go");
	}

	/*
	 * Move has just taken place, what now?
	 */
	public function whatNext() {
		$current_player = $this->getCurrentPlayer();
		$position = $this->state->players[$current_player]->position;
		$next = array();

		// Community Chest / Chance ?
		if ($position == 2 || $position == 17 || $position == 33) {
			$next[] = $this->drawCard(self::CARD_COMMUNITY_CHEST);
		}
		elseif ($position == 7 || $position == 22 || $position == 36) {
			$nextp[] = $this->drawCard(self::CARD_CHANCE);
		}

		// Income Tax
		elseif ($position == 4) {
			$taxable = $this->getNetWorthOf($current_player);

			if ($taxable > 2000) {
				$tax = 200;
			}
			else {
				$tax = round($taxable * 0.1);
			}

			$settled = $this->payTax($tax, $current_player);

			if ($settled !== true) {
				$next[] = $this->addNext("debt", $settled);
			}
			else {
				$next[] = $this->addNext("fine", $tax);
			}
		}

		// Visiting Jail
		elseif ($position == self::POSITION_JAIL) {
			// do nothing
		}

		// free parking
		elseif ($position == 20) {
			// maybe get fine money?
		}

		// go to Jail
		elseif ($position == 30) {
			$this->state->players[$current_player]->position = self::POSITION_JAIL;
			$next[] = $this->addNext("go_to_jail", 1);
		}

		// Super Tax
		elseif ($position == 38) {

			$settled = $this->payTax(100, $current_player);

			if ($settled !== true) {
				$next[] = $this->addNext("debt", $settled);
			}
		}

		// Property space
		else {
			// who owns it?
			$owner = $this->whoOwns($position);

			if ($owner === false) {
				$next[] = $this->addNext("empty_property");
			}
			elseif ($owner != $current_player) {
				$rent_due = $this->calcRentDue($position);
				$this->payPlayer($current_player, $owner, $rent_due);
				$next[] = $this->addNext("rent_due", $rent_due);
			}
		}

		$this->saveState();
		return $next;
	}

	public function drawCard($type) {
		$current_player = $this->getCurrentPlayer();


		if ($type == self::CARD_CHANCE) {
			// quick fix pay Poor tax
			$this->payTax(15, $current_player);
			$card = "poor_tax";
		}
		else {
			//Receive for services $25
			$this->state->players[$current_player]->money += 25;
			$card = "receive_services";
		}

		$return = $this->addNext("draw_card", $type, $card);
		return $return;
	}

	public function getNetWorthOf($player) {
		return $this->state->players[$player]->money;
	}

	public function payTax($amount, $player) {
		if ($this->state->players[$player]->money >= $amount) {
			$this->state->players[$player]->money -= $amount;
			return true;
		}
		else {
			$outstanding = $amount - $this->state->players[$player]->money;
			$this->state->players[$player]->money = 0;
			return $outstanding;
		}
	}

	public function calcRentDue($position) {
		// Station
		if ($position == 5 || $position == 15 || $position == 25 || $position == 35) {

			$owner = $this->state->properties[$position]->owner;
			$stations_owned = 0;

			if ($this->state->properties[5]->owner == $owner) {
				$stations_owned++;
			}
			if ($this->state->properties[15]->owner == $owner) {
				$stations_owned++;
			}
			if ($this->state->properties[25]->owner == $owner) {
				$stations_owned++;
			}
			if ($this->state->properties[35]->owner == $owner) {
				$stations_owned++;
			}

			if ($stations_owned == 4) {
				return 200;
			}
			elseif ($stations_owned == 3) {
				return 100;
			}
			elseif ($stations_owned == 2) {
				return 50;
			}
			return 25;
		}

		// electric / water company
		elseif ($position == 12 || $position == 28) {

			if ($this->state->properties[12]->owner == $this->state->properties[12]->owner) {
				return $this->previous_roll * 10;
			}

			return $this->previous_roll * 4;
		}

		// property
		else {
			if ($this->state->properties[$position]->hotels == 0) {
				if ($this->state->properties[$position]->houses == 0) {
					if ($this->state->properties[$position]->mortgaged == false) {
						return $this->getBoard()->getBaseRent($position);
					}
					else {
						return 0;
					}
				}
				else {
					return $this->getBoard()->getRentWithHouse($position, $this->state->properties[$position]->houses);
				}
			}
			else {
				return $this->getBoard()->getRentWithHotel($position);
			}
		}
	}

	public function payPlayer($src_player, $target_player, $amount) {


	}

	public function whoOwns($position) {
		return $this->state->properties[$position]->owner;
	}

	public function buyProperty() {
		$current_player = $this->getCurrentPlayer();
		$position = $this->state->players[$current_player]->position;
		$property_price = $this->getBoard()->getPriceOf($position);
		$return = false;

		if ($this->state->players[$current_player]->money >= $property_price) {
			if ($this->getBoard()->isPurchasable($position)) {
				$this->state->players[$current_player]->money -= $property_price;
				$this->state->properties[$position]->owner = $current_player;
				$this->log("buy_property");
				$return = true;
			}
			else {
				// hack attempt
			}
		}
		$this->saveState();
		return $return;
	}

	public function endTurn() {
		$this->state->turn = ($this->state->turn + 1) % $this->state->players_playing;
		$this->log("end_turn");
		$this->saveState();
	}

	protected function getCurrentPlayer() {
		return $this->state->turn;
	}

	protected function log($type, $p1=null, $p2=null) {
		if ($p2 !== null) {
			$msg = array($type, $p1, $p2);
		}
		elseif ($p1 !== null) {
			$msg = array($type, $p1);
		}
		else {
			$msg = $type;
		}
		$this->logger[] = $msg;
	}

	protected function addNext($task, $p1=null, $p2=null) {
		$return = array("task"=>$task);

		if ($p1 !== null) {
			$return["p1"] = $p1;
		}

		if ($p2 !== null) {
			$return["p2"] = $p2;
		}

		$this->log($task, $p1, $p2);

		return $return;
	}

	protected function initState() {
		$state = new stdClass();
		$state->id = 1;
		$state->players = array();
		$state->players_playing = 3;
		$state->turn = 0;
		$state->properties = array();

		$p1 = $this->addPlayer(346, "Fred", 1);
		$p2 = $this->addPlayer(719, "Bob", 2);
		$p3 = $this->addPlayer(6731, "Mary", 3);

		$state->players[] = $p1;
		$state->players[] = $p2;
		$state->players[] = $p3;

		for ($i = 0; $i <= Model_Game::MAX_POSITIONS; $i++) {
			$state->properties[] = array(
				"owner" => false,
				"houses" => 0,
				"hotels" => 0,
				"mortgaged" => false
			);
		}

		return $state;
	}

	protected function addPlayer($id, $name, $token) {
		return array(
			"id" => $id,
			"name" => $name,
			"token" => 1,
			"money" => 1500,
			"position" => 0
		);
	}

	protected function saveState() {
		file_put_contents($this->filename, json_encode($this->state));
		file_put_contents($this->log_name, json_encode($this->logger));
	}

}