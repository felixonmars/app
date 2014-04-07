<?php

global $wgAutoloadClasses;
$wgAutoloadClasses['TestController'] = dirname(__FILE__) . '/TestController.php';
$wgAutoloadClasses['AnotherTestController'] = dirname(__FILE__) . '/TestController.php';
$wgAutoloadClasses['NextTestController'] = dirname(__FILE__) . '/NextTestController.php';

class TestController extends WikiaController {

	public function dispatcherCharacterization() {
	}

	/**
	 * This method does nothing and is available in json context only
	 */
	public function jsonOnly() {
	}

	public function index() {
		$this->getResponse()->setVal("wasCalled", "index");
	}

	public function sendTest() {
		$this->sendRequest( 'nonExistentController', 'test' );
	}

	public function forwardTest() {
		$resetResponse = (bool) $this->getRequest()->getVal( 'resetResponse', false );

		$this->getResponse()->setVal( 'content', true );
		$this->getResponse()->setVal( 'controller', __CLASS__ );

		// set some data so we can check that resetData works
		$this->getResponse()->setVal( 'forwardTest', true );

		$this->forward( 'AnotherTest', 'hello', $resetResponse);
	}

	public function overrideTemplateTest(){
		$this->response->setVal( 'output', $this->request->getVal( 'input' ) );
		$this->overrideTemplate( $this->request->getVal( 'template' ) );
	}

	// This is for testing dispatch by skin
	public function skinRoutingTest() {
		$this->getResponse()->setVal( 'wasCalled', "skinRouting");
	}

	// This is for testing dispatch by global var
	public function globalRoutingTest() {
		$this->getResponse()->setVal( 'wasCalled', "globalRouting");
	}

	public function action1() {
	}

	public function action2() {
	}

	public function getDoubleForward() {
		$this->forward("TestController", "action1", false);
		$this->forward("TestController", "action2", false);
	}
}

class AnotherTestController extends WikiaController {

	public function index() {
		$this->skipRendering();
		// this is for testing dispatch by * (total controller override)
		$this->getResponse()->setVal( 'wasCalled', 'controllerRouting');
	}

	public function hello() {
		$this->getResponse()->setVal( 'controller', __CLASS__ );
		$this->getResponse()->setVal( 'wasCalled', "hello" );
		$this->getResponse()->setVal( 'foo', true );

	}
}

class NextTestController extends WikiaController {

	public function index(){}

	public function hasNext() {
		return true;
	}

	public function getNext() {
		return [
			'controller' => 'TestController',
			'method' => 'dispatcherCharacterization',
			'reset' => false
		];
	}
}
