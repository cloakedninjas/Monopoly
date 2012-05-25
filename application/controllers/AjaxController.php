<?php

class AjaxController extends Zend_Controller_Action {

    public function init() {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();

    	// init Game
    	$this->game = new Model_Game();

    	$session = new Zend_Session_Namespace('game');

    	if (isset($session->player)) {
		}
		else {
			$session->player = new stdClass(); // hard code this bad boy
			$session->player->player_num = $_REQUEST['player_num'];
		}

		$this->player = $session->player;
    }

    public function startAction() {
    	$this->game->start();
    }

    public function userCommandAction() {
    	$cmd = $this->_getParam("cmd");
    	$result = $this->game->issueCommand($cmd, $this->player->player_num);

    	if ($result === false) {
    		echo "command not good";
    	}
    	else {
    		var_dump($this->game->getLog());
    	}
    }

	protected function outputGameState() {
		echo json_encode($this->game->getState());
    }

}