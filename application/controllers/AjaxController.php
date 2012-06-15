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

    public function userCommandAction() {
    	$cmd = $this->_getParam("cmd");
    	$result = $this->game->issueCommand($cmd, $this->player->player_num);

    	$output = array('success'=>true, 'log'=>array(), 'start_listen'=>false);

    	if ($result === false) {
    		$output['success'] = false;
    	}
    	else {
    		$output['log'] = $this->game->getLog($this->_getParam("log_index"));
    	}

    	echo json_encode($output);
    }

    public function gameListenAction() {
    	$config = Zend_Registry::get('config');

    	$start_time = time();
    	$listen_time = intval($this->_getParam('timeout', $config->game->listen->default));

    	if ($listen_time > $config->game->listen->maxtime) {
    		$listen_time = $config->game->listen->maxtime;
    	}

    	$end_time = $start_time + $listen_time;

    	$i = 0;
    	while (time() < $end_time) {
    		$i++;
    		// do something - use memcache here to check status
    		sleep(1);

    		if ($i >= 5) {
    			echo "AYE CARAMBA!";
    			exit;
    		}

    	}

    }


}