<?php

//Version 1.0. 2015 Secretaria de Seguridad Pública del Estado de Yucatán. Departamento de Desarrollo de Software. Copyright 2015.
//---Librerias
require 'lib/conexion.php';

//----Funciones publicas del web service----
//Funcion que realiza el registro de un nuevo usuario, crea el usuario y le asigna el sistema y el tipo 
//de rol que ejercera en dicho sistema.
function registraUsuario($username, $password, $numDispositivos, $sistema, $rol, $idPersona) {
    $conn = new Conexion();
    try {
        $conn->conectar();
        $strQuery1 = "SELECT nombre_usuario_VC FROM t_usuarios WHERE nombre_usuario_VC = '$username' AND status_VC = 'ACTIVO'";
        try {
            $conn->obtDatos($strQuery1); //Verifica que no exista el usuario en la base de datos. No se deben repetir.
            if ($conn->filasConsultadas > 0) {//Si existe el usuario
                $resp = '0006'; //EXISTE EL USUARIO.
            } else {//Si no existe el usuario, se puede registrar.
                $encryptedPass = lockPassword($password);
                $strQuery2 = "INSERT INTO t_usuarios(nombre_usuario_VC, contrasena_usuario_VC,"
                        . "id_persona_INT, numero_dispositivos_max_INT, dispositivos_libres_INT, "
                        . "dispositivos_usados_INT, status_VC) VALUES('$username', '$encryptedPass',"
                        . "$idPersona, $numDispositivos, $numDispositivos, 0, 'ACTIVO')";
                try {
                    $conn->consulta($strQuery2); //Inserta el nombre de usuario
                    $strQuery3 = "SELECT * FROM t_usuarios WHERE nombre_usuario_VC = '$username' AND status_VC = 'ACTIVO'";
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
                                $resp = "OK, $idUsuario"; //USUARIO Y PERMISOS CREADOS.
                            } catch (Exception $ex) {
                                $resp = "0005"; //ERROR AL CREAR LOS PERMISOS DEL USUARIO.
                                $conn->generaErrorLog($ex);
                            }
                        } else {
                            $resp = "0004"; //USUARIO NO ENCONTRADO.
                        }
                    } catch (Exception $ex) {
                        $resp = "0002"; //ERROR EN LA CONSULTA.
                        $conn->generaErrorLog($ex);
                    }
                } catch (Exception $ex) {
                    $resp = "0003"; //ERROR AL CREAR EL USURIO.
                    $conn->generaErrorLog($ex);
                }
            }
        } catch (Exception $ex) {
            $resp = "0002"; //ERROR EN LA CONSULTA.
            $conn->generaErrorLog($ex);
        }        
    } catch (Exception $ex) {
        $resp = "0001"; //NO SE PUEDE CONECTAR AL SERVIDOR.
        $conn->generaErrorLog($ex);
    }
    $conn->cerrar();
    return $resp;
}

//Funcion que valida el usuario con el que se intenta ingresar a un sistema, verifica el dispositivo desde donde esta ingresando
//esta vinculado con la cuenta.
function login($username, $password, $imei, $numSerie, $macAddress, $tipoDispositvo, $sistema) {
    $conn = new Conexion();
    try {
        $conn->conectar();
        $sql = "SELECT * FROM t_usuarios WHERE nombre_usuario_VC = '$username' AND status_VC = 'ACTIVO'";
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
                    $idDisp = $imei . $numSerie . $macAddress; //Se crea el identificador unico del dispositivo
                    $idDispositivo = trim($idDisp);
                    $sql2 = "SELECT * FROM t_dispositivos_vinculados WHERE id_dispositivo_VC = '$idDispositivo' AND status_VC = 'ACTIVO'";
                    try {
                        $result2 = $conn->obtDatos($sql2);
                        if ($conn->filasConsultadas > 0) {//Si existe el dispositivo
                            $sql3 = "SELECT * FROM t_dispositivos_vinculados WHERE id_dispositivo_VC = '$idDispositivo' AND id_usuario_INT = $idUsuario AND status_VC = 'ACTIVO'";
                            try {
                                $result3 = $conn->obtDatos($sql3);
                                if ($conn->filasConsultadas > 0) {//Si el dispositivo esta ligado al usuario ingresado
                                    $sql4 = "SELECT * FROM t_sistemas WHERE id_usuario_INT = $idUsuario AND estatus_VC = 'ACTIVO'";
                                    try {
                                        $result4 = $conn->obtDatos($sql4);
                                        if ($conn->filasConsultadas > 0) {//Si existe el registro
                                            foreach ($result4 as $dts4) {
                                                $nombreSistema = $dts4['nombre_sistema_VC'];
                                                $idUsuarioSistema = $dts4['id_usuario_INT'];
                                            }
                                            if ($sistema == $nombreSistema && $idUsuario == $idUsuarioSistema) {//SI TIENE PERMISOS PARA ENTRAR AL SISTEMA.
                                                $resp = "OK"; //ACCESO.
                                            }
                                        } else {
                                            $resp = "0012"; //NO TIENE PERMISO AL SISTEMA.
                                        }
                                    } catch (Exception $ex) {
                                        $resp = "0002";
                                        $conn->generaErrorLog($ex);
                                    }
                                } else {//Si el dispositivo no pertecene a la cuenta
                                    $resp = "0009"; //DISPOSITIVO NO PERTENECE A LA CUENTA.
                                }
                            } catch (Exception $ex) {
                                $resp = "0002"; //ERROR EN LA CONSULTA.
                                $conn->generaErrorLog($ex);
                            }
                        } else {//Sino esta registrado el dispostiivo
                            $resp = "0008, $idDispositivo, $idUsuario"; //DISPOSITIVO NO VINCULADO.
                        }
                    } catch (Exception $ex) {
                        $resp = "0002"; //ERROR EN LA CONSULTA.
                        $conn->generaErrorLog($ex);                        
                    }
                } else {
                    $resp = "0007"; //USUARIO O CONTRASEÑA INCORRECTOS
                }
            } else {//Sino se encontro al usuario
                $resp = "0007"; //USUARIO O CONTRASEÑA INCORRECTOS
            }
        } catch (Exception $ex) {
            $resp = "0002"; //ERROR EN LA CONSULTA.
            $conn->generaErrorLog($ex);
        }
    } catch (Exception $ex) {
        $resp = "0001"; //NO SE PUEDE CONECTAR AL SERVIDOR.
        $conn->generaErrorLog($ex);
    }
    $conn->cerrar();
    return $resp;
}

//Realiza la vinculacion de un dispositivo con una cuenta, verifica si el dispositivo no esta vinculado con otra cuenta.
function vincular($idUsuario, $idDisp, $tipoDispositivo) {
    $conn = new Conexion();
    $idDispositivo = trim($idDisp);
    try {
        $conn->conectar();
        $sql = "SELECT * FROM t_usuarios WHERE id_usuario_INT = $idUsuario";
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
                            . "VALUES($idUsuario, '$idDispositivo', '$tipoDispositivo', 'ACTIVO')";
                    try {
                        $conn->consulta($sql2);
                        $numDisponibleDisp = $numDisponibleDisp - 1;
                        $numOcupadosDisp = $numOcupadosDisp + 1;
                        $sql3 = "UPDATE t_usuarios SET dispositivos_libres_INT = $numDisponibleDisp, dispositivos_usados_INT = $numOcupadosDisp "
                                . "WHERE id_usuario_INT = $idUsuario";
                        try{
                            $conn->consulta($sql3);//Se actualizan los campos numeros disponibles y usados en la tabla de usuarios.
                            $resp = "OK"; //VINCULADO.
                        } catch (Exception $ex) {
                            $resp = "0011";//ERROR AL ACTUALIZAR EL NUMERO DE DISPOSTIVOS USADOS EN t_usuarios.
                        }                        
                    } catch (Exception $ex) {
                        $resp = "0011"; //ERROR AL VINCULAR DISPOSITIVO.
                        $conn->generaErrorLog($ex); 
                    }
                } else {
                    $resp = "0010"; //NO QUEDAN ESPACIOS PARA VINCULAR DISPOSITIVOS.
                }
            } else {
                $resp = "0004"; //USUARIO NO ENCONTRADO.
            }
        } catch (Exception $ex) {
            $resp = "0002"; //ERROR EN LA CONSULTA.
            $conn->generaErrorLog($ex); 
        }
    } catch (Exception $ex) {
        $resp = "0001"; //NO SE PUDO CONECTAR AL SERVIDOR.
        $conn->generaErrorLog($ex); 
    }
    $conn->cerrar();
    return $resp;
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