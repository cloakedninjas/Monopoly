<?php

class AjaxController extends Zend_Controller_Action {

    public function init() {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();

    	$this->game = new Model_Game();
    }

    public function startGameAction() {
		$this->game->start();
		$this->outputGameState();
    }

	public function debugGameStateAction() {
    	var_dump($this->game->getState());
    }

	public function rollDiceAction() {
		$roll = $this->game->rollDice();
		$next = $this->game->movePlayer($roll);

		echo json_encode(array("roll" => $roll, "state" => $this->game->getState(), "next" => $next));
    }

    public function buyPropertyAction() {
    	$this->game->buyProperty();
    	$this->outputGameState();
    }

    public function endTurnAction() {
		$this->game->endTurn();
		$this->outputGameState();
    }

	protected function outputGameState() {
		echo json_encode($this->game->getState());
    }

}