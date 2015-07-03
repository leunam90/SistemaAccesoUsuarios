<?php
//Version 1.0. 2015
//---Librerias
require 'lib/conexion.php';

//----Funciones publicas del web service----
//Funcion que realiza el registro de un nuevo usuario, crea el usuario y le asigna el sistema y el tipo 
//de rol que ejercera en dicho sistema.
function registraUsuario($username, $password, $numDispositivos, $sistema, $rol, $idPersona) {
    $conn = new Conexion();
    try {
        $conn->conectar();
        $resp = "OK";
        $strQuery1 = "SELECT nombre_usuario_VC FROM t_usuarios WHERE nombre_usuario_VC = '$username'";
        try {
            $conn->obtDatos($strQuery1); //Verifica que no exista el usuario en la base de datos. No se deben repetir.
            if ($conn->filasConsultadas > 0) {//Si existe el usuario
                $resp = 'Ya existe ese usuario, intente de nuevo';
            } else {//Si no existe el usuario, se puede registrar.
                $encryptedPass = lockPassword($password);
                $strQuery2 = "INSERT INTO t_usuarios(nombre_usuario_VC, contrasena_usuario_VC,"
                        . "id_persona_INT, numero_dispositivos_max_INT, dispositivos_libres_INT, "
                        . "dispositivos_usados_INT, status_VC) VALUES('$username', '$encryptedPass',"
                        . "$idPersona, $numDispositivos, $numDispositivos, 0, 'ACTIVO')";
                try {
                    $conn->consulta($strQuery2); //Inserta el nombre de usuario
                    $strQuery3 = "SELECT * FROM t_usuarios WHERE nombre_usuario_VC = '$username'";
                    try {
                        $result = $conn->obtDatos($strQuery3); //Se busca el usuario que se acaba de crear
                        if ($conn->filasConsultadas > 0) {//Si se encontro el usuario.
                            foreach ($result as $dts) {
                                $idUsuario = $dts['id_usuario_INT']; //Se obtiene el id del nuevo usuario
                            }
                            $strQuery4 = "INSERT INTO t_sistemas(nombre_sistema_VC, rol_sistema_VC, id_usuario_INT, estatus_VC) "
                                    . "VALUES('$sistema', '$rol', $idUsuario, 'ACTIVO')";
                            try {
                                $conn->consulta($strQuery4); //Se insertan los permisos del nuevo usuario.
                                $resp = "OK";
                            } catch (Exception $ex) {
                                $resp = "ERROR: Hubo un error en la consulta. \n" . $ex;
                            }
                        } else {
                            $resp = "USUARIO NO ENCONTRADO";
                        }
                    } catch (Exception $ex) {
                        $resp = "ERROR: Hubo un error en la consulta. \n" . $ex;
                    }
                } catch (Exception $ex) {
                    $resp = "No se pudo crear el usuario. \n" . $ex;
                }
            }
        } catch (Exception $ex) {
            $resp = "ERROR: Hubo un error en la consulta. \n" . $ex;
        }
        $conn->cerrar();
    } catch (Exception $ex) {
        $resp =  "NO SE PUEDE CONECTAR. \n" . $ex;
    }
    return $resp;
}

//Funcion que valida el usuario con el que se intenta ingresar a un sistema, verifica el dispositivo desde donde esta ingresando
//esta vinculado con la cuenta.
function login($username, $password, $imei, $numSerie, $macAddress, $tipoDispositvo, $sistema) {
    $conn = new Conexion();
    $conn->conectar();
    $sql = "SELECT * FROM t_usuarios WHERE nombre_usuario_VC = '$username'";
    try {
        $result = $conn->obtDatos($sql);
        if ($conn->filasConsultadas > 0) {//Si existe usuario
            foreach ($result as $dts) {
                $idUsuario = $dts['id_usuario_INT'];
                $nombreUsuario = $dts['nombre_usuario_VC'];
                $passwordenBD = $dts['contrasena_usuario_VC'];
            }
            if (crypt($password, $passwordenBD) == $passwordenBD) {//Si la contraseña es igual a la ingresada
                //$resp = 'OK';
                $idDispositivo = $imei . $numSerie . $macAddress; //Se crea el identificador unico del dispositivo
                $sql2 = "SELECT * FROM t_dispositivos_vinculados WHERE id_dispositivo_VC = '$idDispositivo'";
                try {
                    $result2 = $conn->obtDatos($sql2);
                    if ($conn->filasConsultadas > 0) {//Si existe el dispositivo
                        $sql3 = "SELECT * FROM t_dispositivos_vinculados WHERE id_dispositivo_VC = '$idDispositivo' AND id_usuario_INT = $idUsuario";
                        try {
                            $result3 = $conn->obtDatos($sql3);
                            if ($conn->filasConsultadas > 0) {//Si el dispositivo esta ligado al usuario ingresado
                                $resp = 'OK';
                            } else {//Si el dispositivo no pertecene a la cuenta
                                $resp = 'El dispositivo no pertenece a la cuenta ingresada';
                            }
                        } catch (Exception $ex) {
                            $resp = "Error en consulta " . $ex;
                        }
                    } else {//Sino esta registrado el dispostiivo
                        $resp = 'dispositivo no vinculado, ¿Desea vincular';
                    }
                } catch (Exception $ex) {
                    $resp = "Error en consulta " . $ex;
                }
            } else {
                $resp = "Usuario o contraseña incorrecto";
            }
        } else {//Sino se encontro al usuario
            $resp = "Usuario o contraseña incorrecto";
        }
    } catch (Exception $ex) {
        $resp = "Algo salio mal en la consulta " . $ex;
    }
    $conn->cerrar();
    return $resp;
}

//Realiza la vinculacion de un dispositivo con una cuenta, verifica si el dispositivo no esta vinculado con otra cuenta.
function vincular($idUsuario, $idDispositivo, $tipoDispositivo) {
    $conn = new Conexion();
    $conn->conectar();
    $sql = "SELECT numero_dispositvos_INT FROM t_usuarios WHERE id_usuario_INT = $idUsuario";
    try {
        $result = $conn->obtDatos($sql);
        if ($conn->filasConsultadas > 0) {//Si existe el usuario, se obtiene su cantidad de dispositivos maximos.
            foreach ($result as $dts) {
                $numMaximoDisp = $dts['numero_dispositivos_max_INT'];
                $numDisponibleDisp = $dts['dispositivos_libres_INT'];
                $numOcupadosDisp = $dts['dispositivos_usados_INT'];
            }
            if ($numDisponibleDisp <= $numMaximoDisp && $numOcupadosDisp < $numMaximoDisp) {//Si el numero disponible es menor o igual al maximo permitido y si los ocupados son menor al maximo permitido.
                $sql2 = "INSERT INTO t_dispositivos_vinculados(id_usuario_INT, id_dispositivo_VC, tipo_dispositivo_VC, status_VC) "
                        . "VALUES($idUsuario, '$idDispositivo', '$$tipoDispositivo', 'ACTIVO')";
                try {
                    $conn->consulta($sql2);
                    $resp = "OK";
                } catch (Exception $ex) {
                    $resp = "Hubo un error en la insercion" . $ex;
                }
                //$resp = "OK";
            } else {
                $resp = "no quedan espacios";
            }
        } else {
            $resp = "No existe el usuario";
        }
    } catch (Exception $ex) {
        $resp = $ex;
    }
}

//---Bloque de funciones propias del web service---------
function lockPassword($password, $digito = 7) {
    $set_salt = './1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $salt = sprintf('$2a$%02d$', $digito);
    for ($i = 0; $i < 22; $i++) {
        $salt .= $set_salt[mt_rand(0, 22)];
    }
    return crypt($password, $salt);
}

?>