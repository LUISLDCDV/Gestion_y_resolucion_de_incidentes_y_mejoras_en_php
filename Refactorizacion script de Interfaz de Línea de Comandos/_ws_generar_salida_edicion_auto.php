#!/usr/bin/php
<?php

function main()
{   
    echo "\n";
    echo "\n";
    echo "\n";
    echo "
 _______________________________________________________________
 |     ____    _    _     ___ ____    _    ____    ____  _____  |
 |    / ___|  / \  | |   |_ _|  _ \  / \  / ___|  |  _ \| ____| | 
 |    \___ \ / _ \ | |    | || | | |/ _ \ \___ \  | | | |  _|   |
 |     ___) / ___ \| |___ | || |_| / ___ \ ___) | | |_| | |___  | 
 |    |____/_/   \_\_____|___|____/_/   \_\____/  |____/|_____| |
 |                                                              |  
 |     _____ ____ ___ ____ ___ ___  _   _                       |
 |    | ____|  _ \_ _/ ___|_ _/ _ \| \ | |                      |
 |    |  _| | | | | | |    | | | | |  \| |                      |
 |    | |___| |_| | | |___ | | |_| | |\  |                      |
 |    |_____|____/___\____|___\___/|_| \_|                      |
 |______________________________________________________________|\n";
    echo "\n";
    echo "\n";
    echo "\n";
    echo "                          		  1 - Todos los protocolos sin CABA \n" ;					
    echo "\n";
    echo "\n";
    echo "                      			  2 - Por proyecto \n" ;
    echo "\n";
    echo "\n";
    echo " 	                                  3 - Solo proyectos con prioridad\n" ;
    echo "\n";
    echo "\n";
    echo " 	                                  4 - CABA (Grupal hasta 15 protocolos)\n" ;
    echo "\n";
    echo "\n";
    echo "\n";
	echo " 	                                  5 - Una expo cada X minutos\n" ;
    echo "\n";
    echo "\n";
    echo "\n";
    echo "\n";
    echo "\n Ingrese el tipo de salida :";
    fscanf(STDIN, "%s", $ingreso);
    echo "\n";
    if ($ingreso == '1' || $ingreso == '2' || $ingreso == '3'|| $ingreso == '4'|| $ingreso == '5'){
        echo "\n";
        print("Selecciono la opcion : " . $ingreso . "\n");
        echo "\n";
        return $ingreso;
    } else {
        echo "\n";
        echo "************************************************* \n";
        echo "\n";
        echo "**************  parametros invalidos  *********** \n";
        echo "\n";
        echo "************************************************* \n";
        echo "\n";
        sleep(1);
        return main();
    }
}

function arraydeProtocolos($mysqli,$seleccion)
{
	$municipiosConPrioridad = array(97,95,98,100,101,102,111,123,124,85,133,144,131,119,136,134,112,117,132,137,139,115,118,140,116,135,114); 
    $queryp = "SELECT id, municipio , equipo_serie  FROM protocolos_main 
		WHERE incorporacion_estado = 65
		AND estado = 0
		AND decripto = 4
		AND idexportacion =0";

  if($seleccion=='1' || $seleccion=='5'){$queryp = $queryp." AND municipio != 7 order by id asc ";}                                                                                   

  if($seleccion=='2'){$queryp = $queryp." AND municipio =".ingresoProyecto()." order by id asc";}

  if($seleccion=='3'){$queryp = $queryp." AND municipio IN (".implode(",",$municipiosConPrioridad).") order by id asc";}

  if($seleccion=='4'){$queryp = $queryp." AND municipio = 7 order by id asc";}

 if($seleccion=='1'){
    echo "1********************Todos***************** \n";
    
    $resultado = $mysqli->query($queryp);
    $datos = array();
	$protocolo = array();
	$equipo = array();

    while ($row = $resultado->fetch_object()) {
		$prot_ind = $row->id;
		$equipo_ind = $row->equipo_serie;

		if(controlarEstEntrada($prot_ind,$equipo_ind,$mysqli)){
			$datos['protocolo'][]       = $row->id;
			$datos['equipo'][]          = $row->equipo_serie;
			$datos['municipio'][]       = $row->municipio;
			$prot[]=$row->id;
		}
    }

	$total_protocolos = count($datos);

		if ($total_protocolos != 0) {
			foreach ($prot as $key => $val) {
				echo "\n*****************************";
				echo "\n".($key+1)." - ".$datos['protocolo'][$key]." - ".$datos['equipo'][$key]." - ".$datos['municipio'][$key]."\n";

				$protocolo = $datos['protocolo'][$key];
				$equipo = $datos['equipo'][$key];
				$municipio = $datos['municipio'][$key];
				$isGeneroSalida = generarSalida($protocolo,$equipo,$municipio,$mysqli);		
			}
		}else{
			echo "0NO ENCONTRO PROTOCOLOS****************";
			die;
		}
	

      
 }else if($seleccion=='2'){
	echo "2****************Por proyecto**************** \n";

	$resultado = $mysqli->query($queryp);
	$datos = array();
	$protocolo = array();
	$equipo = array();

    while ($row = $resultado->fetch_object()) {
		$prot_ind = $row->id;
		$equipo_ind = $row->equipo_serie;

		if(controlarEstEntrada($prot_ind,$equipo_ind,$mysqli)){
			$datos['protocolo'][]       = $row->id;
			$datos['equipo'][]          = $row->equipo_serie;
			$datos['municipio'][]       = $row->municipio;
			$prot[]=$row->id;
		}
    }

	$total_protocolos = count($datos);

	if ($total_protocolos != 0) {		
		foreach ($prot as $key => $val) {
			echo "\n*****************************";
			echo "\n".($key+1)." - ".$datos['protocolo'][$key]." - ".$datos['equipo'][$key]." - ".$datos['municipio'][$key]."\n";
			$protocolo = $datos['protocolo'][$key];
			$equipo = $datos['equipo'][$key];
			$municipio = $datos['municipio'][$key];
			$isGeneroSalida = generarSalida($protocolo,$equipo,$municipio,$mysqli);		
		}

	}else{
		echo "***NO ENCONTRO PROTOCOLOS****************";
		die;	
	}



 }else if($seleccion=='3'){

	echo "3*******************con prioridad***************** \n";

	$resultado = $mysqli->query($queryp);

    $datos = array();
	$protocolo = array();
	$equipo = array();

    while ($row = $resultado->fetch_object()) {

		$prot_ind = $row->id;
		$equipo_ind = $row->equipo_serie;

		if(controlarEstEntrada($prot_ind,$equipo_ind,$mysqli)){
			$datos['protocolo'][]       = $row->id;
			$datos['equipo'][]          = $row->equipo_serie;
			$datos['municipio'][]       = $row->municipio;
			$prot[]=$row->id;
		}
    }

	$total_protocolos = count($datos);
    
	
	if ($total_protocolos != 0) {	
		foreach ($prot as $key => $val) {
			echo "\n*****************************";
			echo "\n".($key+1)." - ".$datos['protocolo'][$key]." - ".$datos['equipo'][$key]." - ".$datos['municipio'][$key]."\n";
			$protocolo = $datos['protocolo'][$key];
			$equipo = $datos['equipo'][$key];
			$municipio = $datos['municipio'][$key];
			$isGeneroSalida = generarSalida($protocolo,$equipo,$municipio,$mysqli);		
		}
	}else{
		echo "0NO ENCONTRO PROTOCOLOS****************";
		die;
	}

 }else if($seleccion=='4'){
	echo "4*********************Grupal CABA************** \n";
	$resultado = $mysqli->query($queryp);
    $datos = array();
	$protocolo = array();
	$equipo = array();
    while ($row = $resultado->fetch_object()) {
		$prot_ind = $row->id;
		$equipo_ind = $row->equipo_serie;
		if(controlarEstEntrada($prot_ind,$equipo_ind,$mysqli)){
			$datos['protocolo'][]       = $row->id;
			$datos['equipo'][]          = $row->equipo_serie;
			$datos['municipio'][]       = $row->municipio;
			$prot[]=$row->id;
		}
    }
	$total_protocolos = count($datos);
	$arProtCaba = array();
	array_push($arProtCaba,$total_protocolos);
	$cantLimiteExpo = 40;
	if ($total_protocolos != 0) {
		foreach ($prot as $key => $val) {
			echo "\n*****************************";
			echo "\n".($key+1)." - ".$datos['protocolo'][$key]." - ".$datos['equipo'][$key]." - ".$datos['municipio'][$key]."\n";
			$protocolo = $datos['protocolo'][$key];
			$equipo    = $datos['equipo'][$key];
			$municipio = $datos['municipio'][$key];
			--$cantLimiteExpo;
			if ($cantLimiteExpo > 0){
				array_push($arProtCaba,$protocolo);
			}elseif($cantLimiteExpo == 0) {
				print("************\ntotal protocolos ".count($arProtCaba)."\n");
				$isGeneroSalida = generarSalidaCaba($arProtCaba,$equipo,$municipio,$mysqli);
				$cantLimiteExpo = 40;
				unset($arProtCaba);
				$arProtCaba = array();
			}
		}	
		if($cantLimiteExpo < 40 &&  $cantLimiteExpo>1){
			print("************\ntotal protocolos ".count($arProtCaba)."\n");
			//print("total protocolos ".count($arProtCaba)."\n");
			$isGeneroSalida = generarSalidaCaba($arProtCaba,$equipo,$municipio,$mysqli);
		}else{echo "PROTOCOLOS INSUFICIENTES****************";}
	}else{
		echo "***NO ENCONTRO PROTOCOLOS****************";
		die;
	}
 }else{
		echo "5*********************POR TIEMPO************** \n";
		$tiempoDescanso = ingresoTiempo();
		$resultado = $mysqli->query($queryp);
		$datos = array();
		$protocolo = array();
		$equipo = array();
		while ($row = $resultado->fetch_object()) {
			$prot_ind = $row->id;
			$equipo_ind = $row->equipo_serie;
			if(controlarEstEntrada($prot_ind,$equipo_ind,$mysqli)){
				$datos['protocolo'][]       = $row->id;
				$datos['equipo'][]          = $row->equipo_serie;
				$datos['municipio'][]       = $row->municipio;	
				$prot[]=$row->id;
			}
		}
		$total_protocolos = count($datos);
		if ($total_protocolos != 0) {	
			foreach ($prot as $key => $val) {
				echo "\n*****************************".date("d-m-y h:i:s");
				echo "\n".($key+1)." - ".$datos['protocolo'][$key]." - ".$datos['equipo'][$key]." - ".$datos['municipio'][$key]."\n";
				$protocolo = $datos['protocolo'][$key];
				$equipo = $datos['equipo'][$key];
				$municipio = $datos['municipio'][$key];
				$isGeneroSalida = generarSalida($protocolo,$equipo,$municipio,$mysqli);	
				sleep($tiempoDescanso);	
			}
		}else{
			echo "***NO ENCONTRO PROTOCOLOS****************";
			die;
		}
 }
}

function generarSalidaCaba($aProtocolos,$equipo,$municipio,$mysqli){
	$numeroExportacion    = SelectExportacionesMain($mysqli);
	$IdUser = 749; 
	$InserDeExportacion   = InsertExportacionesMain($numeroExportacion, $municipio, $IdUser, $mysqli);
	$exit				  = salidaCaba($InserDeExportacion, $municipio, $numeroExportacion, $aProtocolos, $mysqli);
	echo "---------\n";
	echo "Se genero la exportacion # " . $numeroExportacion . "  Del proyecto " . $municipio;
	echo "\n";
	echo "---------\n";

}

function generarSalida($protocolo,$equipo,$municipio,$mysqli){
	$numeroExportacion    = SelectExportacionesMain($mysqli);
	$IdUser = 749; 
	$InserDeExportacion   = InsertExportacionesMain($numeroExportacion,$municipio,$IdUser,$mysqli);
	$exit				  = salida($InserDeExportacion,$municipio,$numeroExportacion,$protocolo,$mysqli);
	echo "---------\n";
	echo "Se genero la exportacion # " . $numeroExportacion . " Con el Id : " . $InserDeExportacion . " Del proyecto " . $municipio;
	echo "\n";
	echo "---------\n";
}

function ingresoTiempo(){
	echo "\n Ingrese el tipo de intervalo :";
    fscanf(STDIN, "%s", $tiempo);
    echo "\n";
    if ($tiempo>1){
        echo "\n";
        print("Selecciono : " . $tiempo . " Minutos ");
        echo "\n";
        return $tiempo*60;
    } else {
        echo "\n";
        echo "************************************************* \n";
        echo "\n";
        echo "**************  parametros invalidos  *********** \n";
        echo "\n";
        echo "************************************************* \n";
        echo "\n";
        sleep(1);
        return ingresoTiempo();
    }
}

function ingresoProyecto(){
	echo "\n Ingrese el municipio - proyecto :";
    fscanf(STDIN, "%s", $proy);
    echo "\n";
    if ($proy>0){
        echo "\n";
        print("Selecciono : " . $proy . "\n");
        echo "\n";
        return $proy;
    } else {
        echo "\n";
        echo "************************************************* \n";
        echo "\n";
        echo "**************  parametros invalidos  *********** \n";
        echo "\n";
        echo "************************************************* \n";
        echo "\n";
        sleep(1);
        return ingresoProyecto();
    }
}

function Conexion()
{
    include("/home/municipios/www-gestion/mysql.php"); 
	$mysqli = new mysqli($mysql_server, $mysql_user, $mysql_pass, $mysql_db);
    if ($mysqli->connect_errno) {
        echo "\n";
        echo "Lo sentimos, SSTI está experimentando problemas de conexión.";
        echo "\n";
        exit;
    }
    return $mysqli;
}

function SelectExportacionesMain($mysqli)
{
	$queryp = "SELECT numero FROM exportaciones_main ORDER BY numero DESC LIMIT 1";
	$result_ult = $mysqli->query($queryp);
	$row = $result_ult->fetch_object();
	$ultima = $row->numero+1;
	return $ultima;
}

function controlExpoaux($idprotocolo,$mysqli)
{
	$queryp = "SELECT idexportacion FROM exportaciones_aux WHERE idprotocolo = $idprotocolo ORDER BY idexportacion DESC LIMIT 1";

	$result    = $mysqli->query($queryp);


	
	if ($result && $result->num_rows == 0){
		echo "Protocolo : $idprotocolo -validado ok exposAux " ;
		return true;
	}else{
		echo "protocolo con expo en exp-aux \n";
		updateErrorImpacto($idprotocolo,$mysqli);
		return false;
	}
}

function InsertExportacionesMain($numeroExportacion,$municipio,$IdUser,$mysqli)
{
	$query_ins = "INSERT INTO exportaciones_main (numero,fecha,estado,municipio,idusuario) VALUES (".$numeroExportacion.",NOW(),1,".$municipio.",".$IdUser.")";

	if ($result_ins    = $mysqli->query($query_ins)) {

		$idexportacion = $mysqli->insert_id;

		return $idexportacion;
	} else {
		echo $mysqli->error;
		echo "La exportacion $numeroExportacion no se genero  \n";
		if($municipio == 7){
			die;
		}else{
			echo "El protocolo no realizo salida.  \n";
		}
	}

}

function controlarEstEntrada($prot_ind,$equipo_ind,$mysqli){
	if(controlExpoaux($prot_ind,$mysqli)){				
		if ((substr_compare($equipo_ind, "LUTEC", 0, 3) == 0)|| (substr_compare($equipo_ind, "DTV", 0, 2) == 0)) {						
			return verificaImpactoCompletoLuces($prot_ind,$mysqli);
		}			
		else{			
			return verificaImpactoCompleto($prot_ind,$mysqli);
		}
	}
}

function verificaImpactoCompletoLuces($prot_ind,$mysqli){
	//-- Verificar que haya al menos un registro en estado = 26 para generar salida edicion --------------------	
	$query_aprobadas = "SELECT idprotocolo, COUNT(*) AS cant 
	FROM entrada WHERE estado = 26 AND idprotocolo = $prot_ind
	GROUP BY idprotocolo";
	$result = $mysqli->query($query_aprobadas);
	if ($result === false) {
		// Manejo de error en caso de que la consulta haya fallado
		echo "Error en la consulta: " . $mysqli->error . "\n";
		return false;
	}
	if ($result->num_rows == 0) {
		echo "Error en impacto luces \n";	
		updateErrorImpacto($prot_ind, $mysqli);
		return false;
	}else{
		echo "-validado ok entrada26 \n" ;
		return true;
	}	
}

function verificaImpactoCompleto($prot_ind,$mysqli){
	//-- Verificar que no haya nada en 25 para generar salida edicion
	$query_aprobadas = "SELECT idprotocolo, COUNT(*) AS cant 
	FROM entrada WHERE estado = 25 AND idprotocolo =$prot_ind
	GROUP BY idprotocolo";
	$result = $mysqli->query($query_aprobadas);
	if ($result === false) {
		// Manejo de error en caso de que la consulta haya fallado
		echo "Error en la consulta: " . $mysqli->error . "\n";	
		return false;
	}
	if ($result->num_rows == 0) {
    	echo "- validado ok -entrada25 \n" ;
		return true;
	}else{
		echo "Error en impacto normal \n";	
		updateErrorImpacto($prot_ind,$mysqli);
		return false;
	}
}

function updateErrorImpacto($prot_ind,$mysqli){
	$query_upd="UPDATE protocolos_main SET incorporacion_estado=63 WHERE id='$prot_ind' and incorporacion_estado=65";
	$result  = $mysqli->query($query_upd);
	if($result){
		echo "Protocolo : $prot_ind pasado a incorporacion_estado 63 FALLO IMPACTO de INO \n" ;
	} else {
		echo $mysqli->error;
	}  
}

function salida($idexportacion,$municipio,$numeroExportacion,$protocolo,$mysqli){	
	if ($idexportacion>0) {
		mkdir("/mnt/Expo/Expo" . $municipio . "-EnProceso-" . $numeroExportacion);	
		$tipo="U";
		actualizarProtocolo($numeroExportacion,$protocolo,$mysqli,$idexportacion);
		insertExpoAux($mysqli,$idexportacion,$protocolo,$tipo);		
	} else {
		echo " Error interno al incluir los protocolos en la exportacion. Consulte con el Administrador ";
	}
}

function salidaCaba($idexportacion,$municipio,$numeroExportacion,$protocolo,$mysqli){				
	if ($idexportacion>0) {
		mkdir("/mnt/Expo/Expo" . $municipio . "-EnProceso-" . $numeroExportacion);		
		foreach ($protocolo as $idprotocolo) {
			$tipo="U";
			actualizarProtocoloCaba($numeroExportacion,$idprotocolo,$mysqli,$idexportacion);
			insertExpoAux($mysqli,$idexportacion,$idprotocolo,$tipo);
		}
	} else {
		echo " Error interno al incluir los protocolos en la exportacion. Consulte con el Administrador ";
	}
}

function actualizarProtocolo($numeroExportacion,$idprotocolo,$mysqli,$idexportacion){
	$query_upd = "UPDATE protocolos_main SET estado=99, numero_exportacion= '".$numeroExportacion."' , idexportacion= '".$idexportacion."' WHERE id= $idprotocolo AND idexportacion = 0"; 
	if($result = $mysqli->query($query_upd)){
	} else {
		echo $mysqli->error; 
		echo "\n";
        echo "******Error update - se volvera a intentar en 5min *********** \n";
        echo "\n";
		sleep(300);//5min
		return actualizarProtocolo();
	}  
}

function actualizarProtocoloCaba($numeroExportacion,$idprotocolo,$mysqli,$idexportacion){
	$query_upd = "UPDATE protocolos_main SET estado=99, numero_exportacion= '".$numeroExportacion."' , idexportacion= '".$idexportacion."' , incorporacion_estado = 52   WHERE id= '".$idprotocolo."' AND idexportacion = 0"; 
	if($result = $mysqli->query($query_upd)){
	} else {
		echo $mysqli->error; 
		echo "\n";
        echo "******Error update - se volvera a intentar en 5min *********** \n";
        echo "\n";
		sleep(300);//5min
		return actualizarProtocolo();
	}  
}

function insertExpoAux($mysqli,$idexportacion,$idprotocolo,$tipo){
	$query_ins="INSERT INTO exportaciones_aux (idexportacion,idprotocolo,tipo) VALUES (" . $idexportacion . "," . $idprotocolo . ",'" . $tipo . "')"; 	
	if ($result   = $mysqli->query($query_ins)) {
		$id =$mysqli->insert_id;
	}else{
		echo $mysqli->error; 
		echo "\n";
        echo "******Error update - se volvera a intentar en 5min *********** \n";
        echo "\n";
		sleep(300);//5min
		return insertExpoAux();
	}
}

$seleccion = main();

$mysqli = Conexion();

$protocolos = arraydeProtocolos($mysqli,$seleccion);

?>