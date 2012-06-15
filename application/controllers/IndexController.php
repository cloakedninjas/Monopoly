<?php

class IndexController extends Zend_Controller_Action {

    public function init() {
    }

    public function indexAction() {
    	$session = new Zend_Session_Namespace('game');

    	//$session->unsetAll(); exit;

    	if (!$session->player) {
    		if (!isset($_REQUEST['player_num'])) {
				echo "you need a player num";
				exit;
			}
			else {
				$session->player->player_num = $_REQUEST['player_num'];
			}
    	}


    	// init Game
    	$this->game = new Model_Game(false);
    	$this->game->reset();
    	$this->game->start();
    }
}