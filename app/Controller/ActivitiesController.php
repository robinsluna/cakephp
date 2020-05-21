<?php
App::uses('AppController', 'Controller');
/**
 * Activities Controller
 *
 * @property Activity $Activity
 * @property PaginatorComponent $Paginator
 */
class ActivitiesController extends AppController {

/**
 * Components
 *
 * @var array
 */
	public $components = array();

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->_remote( $this->Activity->find('all',  array(
			'recursive' => 0
		)));
	}

/**
 * view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function view($id = null) {
		if (!$this->Activity->exists($id)) {
			throw new NotFoundException(__('Invalid activity'));
		}
		$options = array('conditions' => array('Activity.' . $this->Activity->primaryKey => $id));
		$activity= $this->Activity->find('first', $options);
		return $this->_remote($activity);
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		if ($this->request->is('post')) {
			$this->Activity->set($this->request->data);
			if ($this->Activity->validates()) {	
				$this->Activity->create();
				if ($activity = $this->Activity->save($this->request->data)) {
					return $this->_remote($activity);	
				} else {
					throw new Exception(__('The new table could not be saved. Please, try again.'));
				}
			} else {
				$errors = $this->Activity->validationErrors;
				return $this->_remote(['errors' => $errors ]);
			}
		}
		throw new Exception(__('Invalid action'));
	}

/**
 * edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function edit($id = null) {
		if (!$this->Activity->exists($id)) {
			throw new NotFoundException(__('Invalid activity'));
		}
		if ($this->request->is(array('post', 'put'))) {
			$this->Activity->set($this->request->data);
			if ($this->Activity->validates()) {	
				$data = $this->request->data;
				$data['id'] = $id;
				if ($activity = $this->Activity->save($this->request->data)) {
					return $this->_remote($activity);	
				} else {
					throw new Exception(__('The new table could not be saved. Please, try again.'));
				}
			} else {
				$errors = $this->Activity->validationErrors;
				return $this->_remote(['errors' => $errors ]);
			}
		}
		throw new Exception(__('Invalid action'));
		
	}


}
