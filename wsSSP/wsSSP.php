<?php
//Version 1.0. 2015. Secretaria de Seguridad Pública del Estado de Yucatán. Departamento de Desarrollo de Software. Copyright 2015.
    //---Librerias---
    require 'wsFunctions.php';
    require 'lib/nusoap.php';
    $server = new nusoap_server();
    $server->configureWSDL("wsSSP", "urn:wsSSP");
    
    //---Registro de funciones---
    $server->register("registraUsuario", 
            array("username"=>'xsd:string', 
                "password"=>'xsd:string',
                "numDispositivos"=>'xsd:int',
                "sistema"=>'xsd:string',
                "rol"=>'xsd:string',
                "idPersona"=>'xsd:int'), 
            array("return"=>'xsd:string'));
    $server->register("login", 
            array("username"=>'xsd:string', 
                "password"=>'xsd:string',
                "imei"=>'xsd:string',
                "numSerie"=>'xsd:string',
                "macAddress"=>'xsd:string',
                "tipoDispositivo"=>'xsd:string',
                "sistema"=>'xsd:string'),
            array("return"=>'xsd:string'));
    $server->register("vincular", 
            array("idUsuario"=>'xsd:int', 
                "idDispositivo"=>'xsd:string',
                "tipoDispositivo"=>'xsd:string'),
            array("return"=>'xsd:string'));
    
    $HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
    $server->service($HTTP_RAW_POST_DATA);
?>