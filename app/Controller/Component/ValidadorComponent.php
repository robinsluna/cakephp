<?php

App::uses('Component', 'Controller');
App::uses('Xml', 'Utility');
App::uses('Dual', 'Model');

/**
 * Description of Validador
 *
 * @author robinsluna
 */
class ValidadorComponent extends Component {

  protected $columnas = array();
  protected $validaciones = array();
  protected $parametros = array();
  protected $numeroCampos = 0;
  protected $numeroValidaciones = 0;
  protected $salto = 1;
  protected $MAX_ERRORES = 100;

  /**
   * valida method
   * 
   * @param type $id
   */
  public function valida($idFormato, $file, $params) {
    //$resultado = array("resultado" => true, "mensajes" => array("mensaje" => "", "linea" => 0, "nivel" => ""));
    $resultado = array("resultado" => true, "mensajes" => array());
    //TODO: Validar que $fileName trae un nombre seguro
    $archivo = $file;

    // Verifica la existencia del archivo a validar
    if (!file_exists($archivo)) {
      throw new Exception("El archivo " . $archivo . " no existe.");
    }

    $xmlValidacion = realpath("../Controller/Data/.") . DS . 'validation_' . $idFormato . '.xml';
    // Verifica la existencia del archivo con las reglas de validacion
    if (!file_exists($xmlValidacion)) {
      throw new Exception("El archivo " . $xmlValidacion . " no existe.");
    }
    
    $xml = Xml::build($xmlValidacion, array('return' => 'simplexml'));
   
    $xmlArray = Xml::toArray($xml);

    if (isset($xmlArray["root"]["archivo"]["@salto"])) {
      $this->salto = $xmlArray["root"]["archivo"]["@salto"] + 0;
    } else {
      $this->salto = 1; // Por defecto salta una línea de títulos
    }
    $this->columnas = $xmlArray["root"]["archivo"]["columnas"]["columna"];
    if ($xmlArray["root"]["validaciones"]["@cuenta"] == 1) {
      $this->validaciones = array($xmlArray["root"]["validaciones"]["validacion"]);
    } else {
      $this->validaciones = $xmlArray["root"]["validaciones"]["validacion"];
    }

    $this->numeroCampos = count($this->columnas);
    $this->numeroValidaciones = sizeof($this->validaciones);

    $this->parametros = $params;

    // Abre archivo a validar
    $arc = fopen($archivo, "r");

    // Se valida línea a línea
    $linea = 0;
    $i = 0;
    $errores = 0;
    while (($data = fgetcsv($arc, 10000, ",")) !== FALSE && $errores < $this->MAX_ERRORES) {
      $linea++;
      if ($linea <= $this->salto) {
        continue;
      }
      //$data = utf8_encode($data);
      $data = $this->to_utf8($data);
      $result = $this->validar($data);
      if ($result != "") {
        $resultado["resultado"] = false;
        $resultado["mensajes"][$i++] = array("mensaje" => $result,
            "linea" => $linea - $this->salto, "nivel" => "ERROR");
        $errores++;
      }
    }

    // Cierrar el archivo validado
    fclose($arc);

    //var_dump($resultado);
    return $resultado;
  }

  /**
   * validar method
   * 
   * Ejecución de validaciones propias del formato
   * 
   * @param type $columnas
   * @param type $validaciones
   * @param type $linea
   * @return boolean
   */
  private function validar($linea) {
    $result = true;
    $numeroDatos = count($linea);
    $mensaje = "";

    // Validación del número de columnas
    if ($numeroDatos < $this->numeroCampos) {
      $mensaje = "Número de columnas (" . $numeroDatos .
              ") no corresponde con las esperadas (" . $this->numeroCampos .
              ") para este formato.";
      $result = false;
    }

    // Validar los tipos de datos, longitudes máximas y demás relacionado con la
    // definifición de cada columna
    // TODO: Saltar para nulos permitidos
    for ($i = 0; $i < $this->numeroCampos && $result; $i++) {
      $obligatorio = ($this->columnas[$i]["@obligatorio"] == NULL) ? "S" :
              $this->columnas[$i]["@obligatorio"];

      if (strlen(trim("" . $linea[$i])) == 0 && $obligatorio == "S") {
        $mensaje = $mensaje . "<br/>" . "El campo [" .
                $this->columnas[$i]["@"] . "] es obligatorio.";
        $result = false;
      } else if (!(strlen(trim("" . $linea[$i])) == 0)) {

        switch ($this->columnas[$i]["@tipo"]) {
          case 'TEXTO':
            break;
          case 'FECHA':
            $formato = ($this->columnas[$i]["@formato"] == null) ? 'yyyy-mm-dd' :
                    $this->columnas[$i]["@formato"];
            if (!$this->verifica_fecha($linea[$i], $formato)) {
              $mensaje = $mensaje . "<br/>" . "El campo [" .
                      $this->columnas[$i]["@"] .
                      "] debe ser una fecha con formato " . $formato . ": " .
                      $linea[$i];
              $result = false;
            }
            break;
          case 'FECHA TIEMPO':
            $formato = ($this->columnas[$i]["@formato"] == null) ?
                    'yyyy-mm-dd hh24:mi:ss' : $this->columnas[$i]["@formato"];
            if (!$this->verifica_fecha_tiempo($linea[$i], $formato)) {
              $mensaje = $mensaje . "<br/>" . "El campo [" .
                      $this->columnas[$i]["@"] .
                      "] debe ser una fecha-tiempo con formato " . $formato . ": " .
                      $linea[$i];
              $result = false;
            }
            break;
          case 'TIEMPO':
            $formato = ($this->columnas[$i]["@formato"] == null) ?
                    'hh24:mi:ss' : $this->columnas[$i]["@formato"];
            if (!$this->verifica_tiempo($linea[$i], $formato)) {
              $mensaje = $mensaje . "<br/>" . "El campo [" .
                      $this->columnas[$i]["@"] .
                      "] debe ser un tiempo con formato " . $formato . ": " .
                      $linea[$i];
              $result = false;
            }
            break;
          case 'NUMERO':
            if ($result && !is_numeric($linea[$i])) {
              $mensaje = $mensaje . "<br/>" . "El campo [" .
                      $this->columnas[$i]["@"] . "] debe ser numérico: " .
                      $linea[$i];
              $result = false;
            }
            break;
          case 'ENTERO':
            if (!$this->int_ok($linea[$i])) {
              $mensaje = $mensaje . "<br/>" . "El campo [" .
                      $this->columnas[$i]["@"] . "] debe ser un número entero: " .
                      $linea[$i];
              $result = false;
            }
            break;
          default:
            $this->log("Error en tipo de dato en archivo xml de validación.", LOG_ERR);
            break;
        }
      }
      //echo $columnas[$i]["@posicion"] . "-" . $columnas[$i]["@"] . ":" . $linea[$i] . "|";
    }

    // Reglas de validación 
    if ($result && $this->numeroValidaciones > 0) {
      for ($i = 0; $i < $this->numeroValidaciones; $i++) {
        $regla = $this->completar_regla($this->validaciones[$i]["@"], $linea);
        $myApp = new Dual();
        $resultSet = $myApp->query($regla);
       
        if ( $this->validaciones[$i]["@esperado"] != $resultSet[0][0]["dato"]) {
          $result = false;
          $mensaje = $this->completar_regla($this->validaciones[$i]["@mensaje"], $linea);
        }
      }
    }

    return $mensaje;
  }

  /**
   * Checks if the given value represents integer
   */
  private function int_ok($val) {
    return ($val !== true) && ((string) (int) $val) === ((string) $val);
  }

  /**
   * completar_regla method
   * 
   * @param type $a_regla
   * @param type $datos
   * @return type
   */
  private function completar_regla($a_regla, $datos) {
    $result = $a_regla;
    $valor = "";

    // Primero reemplaza valores de parámetros
    foreach ($this->parametros as $key => $value) {
      $result = str_replace('%' . $key . '%', $value, $result);
    }

    // Reemplaza los campos por su valor o nombre
    for ($i = 0; $i < $this->numeroCampos; $i++) {
      if (strlen(trim("" . $datos[$i])) == 0 && !($this->columnas[$i]["@tipo"] == "TEXTO")) {
        $valor = "null";
      } else {
        switch ($this->columnas[$i]["@tipo"]) {
          case 'TEXTO':
            $valor = '\'' . $datos[$i] . '\'';
            break;
          case 'FECHA':
            $formato = ($this->columnas[$i]["@formato"] == null) ? 'yyyy-mm-dd' :
                    $this->columnas[$i]["@formato"];
            $valor = 'to_date(\'' . $datos[$i] . '\', \'' . $formato . '\')';
            break;
          case 'FECHA TIEMPO':
            $formato = ($this->columnas[$i]["@formato"] == null) ?
                    'yyyy-mm-dd hh24:mi:ss' : $this->columnas[$i]["@formato"];
            $valor = 'to_timestamp(\'' . $datos[$i] . '\', \'' . $formato . '\')';
            break;
          case 'TIEMPO':
            $formato = ($this->columnas[$i]["@formato"] == null) ?
                    'hh24:mi:ss' : $this->columnas[$i]["@formato"];
            $valor = 'to_timestamp(\'' . $datos[$i] . '\', \'' . $formato . '\')';
            break;
          default:
            $valor = $datos[$i];
            break;
        }
      }
//      $result = str_replace('%%', $this->columnas[$i]["@"], $result);
      // Nombre del campo
      $result = str_replace('#' . ($i + 1) . '#', $this->columnas[$i]["@"], $result);
      // Valor sin formatear
      $result = str_replace('?' . ($i + 1) . '?', $datos[$i], $result);
      // Valor formateado
      $result = str_replace('%' . ($i + 1) . '%', $valor, $result);
      $result = str_replace('%' . $this->columnas[$i]["@"] . '%', $valor, $result);
    }

    return $result;
  }

  /**
   * verifica_fecha method
   * @param type $fecha
   * @param type $formato
   * @return boolean
   */
  private function verifica_fecha($fecha, $formato) {
    $result = true;
    $sql = "select to_char(to_date('" . $fecha . "','" . $formato . "'), '" . $formato . "') as dato from dual";

    $myApp = new Dual();
    try {
      $resultSet = $myApp->query($sql);
      if ($resultSet[0][0]["dato"] != $fecha) {
        $result = false;
      }
    } catch (Exception $e) {
      $result = false;
    }
    return $result;
  }

  /**
   * verifica_fecha_tiempo method
   * @param type $fecha_tiempo
   * @param type $formato
   * @return boolean
   */
  private function verifica_fecha_tiempo($fechatiempo, $formato) {
    $result = true;
    $sql = "select to_char(to_timestamp('" . $fechatiempo . "','" . $formato . "'), '" . $formato . "') as dato from dual";

    $myApp = new Dual();
    try {
      $resultSet = $myApp->query($sql);
      if ($resultSet[0][0]["dato"] != $fechatiempo) {
        $result = false;
      }
    } catch (Exception $e) {
      $result = false;
    }
    return $result;
  }

  /**
   * verifica_tiempo method
   * @param type $fecha_tiempo
   * @param type $formato
   * @return boolean
   */
  private function verifica_tiempo($fechatiempo, $formato) {
    $result = true;
    $sql = "select to_char(to_timestamp('" . $fechatiempo . "','" . $formato . "'), '" . $formato . "') as dato from dual";

    $myApp = new Dual();
    try {
      $resultSet = $myApp->query($sql);
      if (strlen($fechatiempo) <= 0) {
        //echo "strlen: " + strlen($fechatiempo);
        $result = false;
      }
    } catch (Exception $e) {
      $result = false;
    }
    return $result;
  }


  private function nvl($val, $replace) {
    return isset($var) ? $var : $replace;
  }

  /**
   * 
   * @param type $in
   * @return type
   */
  protected function to_utf8($in) {
    $out = array();
    if (is_array($in)) {
      foreach ($in as $key => $value) {
        $out[$this->to_utf8($key)] = $this->to_utf8($value);
      }
    } elseif (is_string($in)) {
      //echo $in . ':' . mb_detect_encoding($in);
      if (mb_detect_encoding($in) == "UTF-8")// Al revés de lo que debería ser !=
        return utf8_encode($in);
      else
        return $in;
    } else {
      return $in;
    }
    return $out;
  }

}

?>
