<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {
	protected function _initAutoload() {
		$autoloader = new Zend_Application_Module_Autoloader(array(
			'namespace' => '',
			'basePath'  => APPLICATION_PATH,
		));

		$autoloader->addResourceType('namespace', 'app', 'App');

		return $autoloader;
	}

	public function _initRouter() {
		$front = Zend_Controller_Front::getInstance();
		$router = $front->getRouter();

		$route = new Zend_Controller_Router_Route(
			'/:ref',
			array(
				'controller' => 'index',
				'action'     => 'index'
			)
		);

		$router->addRoute('char', $route);
	}

	protected function _initDoctype() {
		$this->bootstrap('db');
		$db = $this->getPluginResource('db');
		Zend_Registry::set('db', $db->getDbAdapter('db'));
		$db->getDbAdapter()->setFetchMode(Zend_Db::FETCH_OBJ);
		$db->getDbAdapter()->getProfiler()->setEnabled(false);

		$config = new Zend_Config($this->getOptions());
		Zend_Registry::set('config',$config);

		$this->bootstrap('view');
		$view = $this->getResource('view');
		$view->doctype('HTML5');
		$view->setEncoding('utf-8');

		$view->addHelperPath(APPLICATION_PATH . '/App/View/Helper', 'App_View_Helper');

		//$view->headTitle(Zend_Registry::get('config')->site->name);
		//$view->headMeta()->appendName('keywords', 'Recombobulator, character analysis, character auditing, guild auditing, audit, auditing, aggregator, analysis, wow, world of warcraft');
		//$view->headMeta()->appendName('description', 'Character audting and analysis for World of Warcraft.');

		Zend_Session::start();
	}
}