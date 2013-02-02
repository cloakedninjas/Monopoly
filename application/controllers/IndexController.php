<?php

class IndexController extends Zend_Controller_Action {

    public function init() {
    }

    public function indexAction() {
    	$session = new Zend_Session_Namespace('game');

		$load_session = isset($session->player);
		$this->view->game = new Model_Game($load_session);

		//uncomment to reset
    	//$session->unsetAll(); exit;

    	if (!$load_session) {
            // init Game
            $this->view->game->reset();
            $this->view->game->start();

    		if (!isset($_REQUEST['player_num'])) {
				echo "you need a player num";
				exit;
			}
			else {
				$session->player->player_num = $_REQUEST['player_num'];
			}
    	}

        if ($this->_getParam('debug')) {
            $this->view->headLink()->appendStylesheet('/css/debug.css');
            $this->renderScript('index/debug.phtml');
        }


    }
}