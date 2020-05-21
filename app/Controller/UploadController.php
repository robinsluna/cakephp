<?php
App::uses('AppController', 'Controller');
/**
 * Activities Controller
 *
 * @property Activity $Activity
 * @property ValidadorComponent $Validador
 */
class UploadController extends AppController {

/**
 * Components
 *
 * @var array
 */
	public $components = array('Validador');

    public function fileValidate() {
        $uploaddir = TMP;
        
        if ($this->request->is('post')) {
            $form = $this->request->form;
            $data = $form['file'];
            $uploadfile = $uploaddir . basename($data['name']);
            if (move_uploaded_file($data['tmp_name'], $uploadfile)) {
                $validateCsv = $this->Validador->valida('employees', $uploadfile, array());
                if (sizeof($validateCsv)) {
                    if ($validateCsv['resultado'] == FALSE) {
                        $result['mensajes'] = $validateCsv['mensajes'];
                       
                    }
                }
                return $this->_remote($validateCsv);

            } else {
                echo "Possible file upload attack!\n";
            }
            
          
        }
        throw new Exception(__('Invalid action'));
	}

}
