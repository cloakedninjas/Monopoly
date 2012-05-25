<?php
class Model_Player {

	public $id, $name, $piece, $position, $cash, $playing, $in_jail, $jail_roll_count, $previous_doubles;

	public function __construct($params=null) {
		$this->position = 0;
		$this->cash = 2500; //TODO: change
		$this->playing = true;
		$this->in_jail = false;
		$this->jail_roll_count = 0;
		$this->previous_doubles = array(false, false);
	}
}