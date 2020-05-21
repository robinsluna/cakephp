<?php
App::uses('AppController', 'Controller');
/**
 * Employees Controller
 *
 * @property Employee $Employee
 * @property PaginatorComponent $Paginator
 */
class EmployeesController extends AppController {

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
		$this->_remote( $this->Employee->find('all',  array(
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
		if (!$this->Employee->exists($id)) {
			throw new NotFoundException(__('Invalid Employee'));
		}
		$options = array('conditions' => array('Employee.' . $this->Employee->primaryKey => $id));
		$employee= $this->Employee->find('first', $options);
		return $this->_remote($employee);
	}

	/**
	* add method
	*
	* @return void
	*/
	public function add() {
		if ($this->request->is('post')) {
			$this->Employee->set($this->request->data);
			if ($this->Employee->validates()) {	
				$this->Employee->create();
				if ($employee = $this->Employee->save($this->request->data)) {
					return $this->_remote($employee);	
				} else {
					throw new Exception(__('The new table could not be saved. Please, try again.'));
				}
			} else {
				$errors = $this->Employee->validationErrors;
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
		if (!$this->Employee->exists($id)) {
			throw new NotFoundException(__('Invalid Employee'));
		}
		if ($this->request->is(array('post', 'put'))) {
			$this->Employee->set($this->request->data);
			if ($this->Employee->validates()) {	
				$data = $this->request->data;
				$data['id'] = $id;
				if ($employee = $this->Employee->save($data)) {
					return $this->_remote($employee);	
				} else {
					throw new Exception(__('The new table could not be saved. Please, try again.'));
				}
			} else {
				$errors = $this->Employee->validationErrors;
				return $this->_remote(['errors' => $errors ]);
			}
		}
		throw new Exception(__('Invalid action'));
		
	}
}
