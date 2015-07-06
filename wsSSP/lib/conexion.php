<?php
//Version. 1.0. 2015. Secretaria de Seguridad Pública del Estado de Yucatán. Departamento de Desarrollo de Software. Copyright 2015.
class Conexion {

    private $servidor;
    private $baseDatos;
    private $usuario;
    private $password;
    var $filasModificadas;
    var $filasConsultadas;

    public function __construct() {
        $this->servidor = "localhost";
        $this->usuario = "root";
        $this->password = "123456"; //"srv.D3m01";
        $this->baseDatos = "db_ssp_users";
    }

    public function __get($name) {
        return $this->$name;
    }

    public function __set($name, $value) {
        $this->$name = $value;
    }

    private $linkId;

    /** Conectar con la base de datos */
    public function conectar() {
        //Establecer conexion con el servidor
        $this->linkId = @mysql_connect($this->servidor, $this->usuario, $this->password);
        if ($this->linkId == true) {//Si se conecto al servidor.
            //Asignar la conexion a la base de datos
            if (!mysql_select_db($this->baseDatos)) {
                throw new Exception("No se pudo conectar  a la base de datos");
            }
        } else {//Si no se conecto al servidro
            throw new Exception("No se pudo conectar al servidor");
        }
    }

    //Cerrar la conexion a la base de datos
    public function cerrar() {
        @mysql_close($this->link_id);
    }

    /**
      Ejecutar una consulta en la base de datos para insertar, modificar o eliminar registros
      @return boolean TRUE si la consulta se ejecuto con exito, FALSE en caso contrario.
     * */
    public function consulta($query) {
        $result = mysql_query($query, $this->linkId);
        if (!$result) {
            throw new Exception(mysql_error(), mysql_errno());
        }
        $this->filasModificadas = mysql_affected_rows($this->linkId);
        return $result;
    }

    /**
     * Consultar la base de datos y guardar el resultado en una matriz.
     * @return array Una matriz con los datos devueltos por la consulta.
     */
    public function obtDatos($query) {
        $matriz = array();
        // Realizar una consulta a la base de datos
        $result = mysql_query($query, $this->linkId);
        if (!$result) {
            throw new Exception(mysql_error(), mysql_errno());
        }
        // Recorrer cada registro de la tabla y guardarlo en la matriz
        while ($registro = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $matriz[] = $registro;
        }
        $this->filasConsultadas = mysql_num_rows($result);
        // Liberar los recursos de la consulta
        @mysql_free_result($result);
        // Devolver la matriz
        return $matriz;
    }
/*
 * Funcion para generar archivos de logs.
 */
    function generaLogs($user, $nombre, $device, $devicename, $acceso, $ip) {
        //Definimos la hora de la accion
        ini_set('date.timezone', 'America/Mexico_City');
        $fecha = date("Y/m/d");
        $hora = str_pad(date("H:i:s"), 10, " "); //hhmmss;
        //Definimos el contenido de cada registro de accion por usuario.
        $usuario = strtoupper(str_pad($user, 15, " "));
        $nombre = strtoupper(str_pad($nombre, 15, " "));
        $device = strtoupper(str_pad($device, 15, " "));
        $cadena = $hora . $usuario . $nombre . " " . $device . $devicename . " " . $acceso . " " . $ip;
        //Creamos dinamicamente el nombre del archivo por dia
        $pre = "log";
        $date = date("ymd"); //aammddhhmmss
        $fileName = $pre . $date;
        //echo "$fileName";
        $f = fopen("logs/$fileName.TXT", "a");
        fputs($f, $cadena . "\r\n") or die("no se pudo crear o insertar el fichero");
        fclose($f);
    }
    
    function generaErrorLog($error)
    {
        ini_set('date.timezone', 'America/Mexico_City');
        $fecha = date("Y-m-d");
        $hora = str_pad(date("H:i:s"), 10, " ");
        $errorMessage = strtoupper(str_pad($error, 15, " "));
        $message = $fecha." ".$hora.": Se genero el error: " . $errorMessage;
        $prefijo = "logError";
        $date = date("Ymd");
        $fileName = $prefijo.$date;
        $f = fopen("logs/$fileName.TXT", "a");
        fputs($f, $message . "\r\n") or die("No se pudo crear el archivo");
        fclose($f);
    }

//end generaLogs function
}

?>
