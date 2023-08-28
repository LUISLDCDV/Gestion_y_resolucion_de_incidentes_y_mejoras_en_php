#!/usr/bin/php
<?php

class SalidasEdicion{

	function salida($idexportacion,$proyecto,$numeroExportacion,$SalidaDeProtocolos,$mysqli){
		
		
					
		if ($idexportacion>0) {

			mkdir("/mnt/Expo/Expo" . $proyecto['municipio'] . "-EnProceso-" . $numeroExportacion['exportacion']);	
			//mkdir("C:/Users/dfranco/Desktop/ENFORCER/Expo" . $proyecto['municipio'] . "-EnProceso-" . $numeroExportacion['exportacion']);	
							
		foreach ($SalidaDeProtocolos as $idprotocolo) {

			//considerar para municipio 7 todo como único U
			/*
			if ($proyecto['municipio']==7) {
				$tipo="D"; 
			} else {
				$tipo="U";
			}
			*/
			//si el tipo es UNICO; se marca el protocolo como EN EXPORTACION
			/*if ($tipo=="U") {
				$this->tipoUnico($numeroExportacion,$idprotocolo,$mysqli);
			}*/
			
			$tipo="U";
			$this->actualizarProtocolo($numeroExportacion,$idprotocolo,$mysqli,$idexportacion);
			$this->insertExpoAux($mysqli,$idexportacion,$idprotocolo,$tipo);
		}
			
		} else {
			echo " Error interno al incluir los protocolos en la exportacion. Consulte con el Administrador ";
		}

	}


	function main(){

		////////////////Selecciona Usuario Que orden de proceso realizar/////////////////////////////////////
			echo "\n";
			echo "\n";
			echo "\n";
			echo "
 ____    _    _     ___ ____    _    ____    ____  _____ 
/ ___|  / \  | |   |_ _|  _ \  / \  / ___|  |  _ \| ____|
\___ \ / _ \ | |    | || | | |/ _ \ \___ \  | | | |  _|  
 ___) / ___ \| |___ | || |_| / ___ \ ___) | | |_| | |___ 
|____/_/   \_\_____|___|____/_/   \_\____/  |____/|_____|
															
 _____ ____ ___ ____ ___ ___  _   _ 
| ____|  _ \_ _/ ___|_ _/ _ \| \ | |
|  _| | | | | | |    | | | | |  \| |
| |___| |_| | | |___ | | |_| | |\  |
|_____|____/___\____|___\___/|_| \_|
												
					\n";
			echo "\n";
			echo "\n";
			echo "\n";
			echo "\n";
			echo "1 - Ingrese numero de Proyecto	" ;	
			echo "\n";
			echo "\n";
			fscanf(STDIN, "%s", $Proyecto);
			echo "\n";
			if(isset($Proyecto) && strlen($Proyecto) <= '3'){
			echo "\n";
			print ("Selecciono el proyecto Numero : ".$Proyecto."\n");
			echo "\n";
			//sleep(5);
			return $Proyecto;
			}else {
			echo "\n";
			echo "************************************************* \n";
			echo "\n";
			echo "**************  parametros invalidos  *************** \n";
			echo "\n";
			echo "************************************************* \n";
			echo "\n";
			return self::main();
			}
		}


	function arraydeProtocolos(){

		//$protocolo           = readline("Ingrese numero de Protocolo: ");
		echo "\n";
		echo "Ingrese Protocolos  \n";
		fscanf(STDIN, "%s", $protocolo);
		echo "\n";
		echo "\n";
		echo "\n";
		echo "\n";


		//$protocolos = explode(",",$protocolo);
		return $protocolo;
	}

	function individual(){

		echo "\n";
		echo "1 -¿ Desea realizar una salida de edicion por protocolo (SI/NO) y presione ENTER ? " ;	
		echo "\n";
		echo "A) SI";
		echo "\n";
		echo "B) NO";
		echo "\n";
		fscanf(STDIN, "%s", $opcion);
		echo "\n";
		if(isset($opcion) && strlen($opcion) <= '3' && ($opcion == 'NO' || $opcion == 'SI')){
		echo "\n";
		print ("Selecciono la opcion  ".$opcion. "\n");
		echo "\n";
		//sleep(5);
		return $opcion;
		}else {
		echo "\n";
		echo "************************************************* \n";
		echo "\n";
		echo "**************  parametros invalidos  *************** \n";
		echo "\n";
		echo "************************************************* \n";
		echo "\n";
		}
	}	

	function parametrosLuces(){

	
		echo "\n";
		echo "1 - Si es Luces Ingrese una L y luego Presione Enter , De lo contrario solo Presione Enter " ;	
		echo "\n";
		echo "\n";
		fscanf(STDIN, "%s", $luces);
		echo "\n";
		if($luces == 'L' || $luces == 'l' ){
		echo "Procesando... LUCES...";
		echo "\n";
		return $luces;

		}elseif($luces == NULL){
			echo "Procesando...";
			echo "\n";
			return $luces;
		}else{
		echo "\n";
		echo "************************************************* \n";
		echo "\n";
		echo "**************  parametros invalidos  *********** \n";
		echo "\n";
		echo "************************************************* \n";
		echo "\n";
		return self::parametrosLuces();
		}
	}


	

	public function Conexion(){

		include("/home/municipios/www-gestion/mysql.php");
		//$mysqli = new mysqli("127.0.0.1","root","", "cecaitra_edicion");
		$mysqli = new mysqli($mysql_server, $mysql_user, $mysql_pass, $mysql_db);
		if ($mysqli->connect_errno) {
			echo "\n";
			echo "Lo sentimos, SSTI está experimentando problemas de conexión.";
			echo "\n";
			exit;
		}
		return $mysqli;
	}

	function SelectExportacionesMain($mysqli){

		$query_ultimo="SELECT numero FROM exportaciones_main ORDER BY numero DESC LIMIT 1"; 

		$result            = $mysqli->query($query_ultimo);
		$row 		       = $result->fetch_array();
		$ultima ['exportacion'] = $row['numero']+1 ;

		return $ultima ;
	}


	function InsertExportacionesMain($numeroExportacion,$proyecto,$IdUser,$mysqli){

	$query_ins="INSERT INTO exportaciones_main (numero,fecha,estado,municipio,idusuario) 
	VALUES (" . $numeroExportacion['exportacion'] . ",NOW(),1," . $proyecto['municipio'] . "," . $IdUser['iduser'] . ")"; 
		if($result_ins    = $mysqli->query($query_ins)){

			$idexportacion =$mysqli->insert_id;

				return $idexportacion ;
		}else{
			echo $mysqli->error; 
		}
		/* Cierra la conexión */
		$mysqli->close();	
	}

	function IdExportacion($numeroExportacion,$proyecto,$mysqli){

		$query_sele="SELECT id,estado FROM exportaciones_main WHERE numero= '".$numeroExportacion['exportacion']."'  AND municipio = '".$proyecto['municipio']."' " ;
		
		$result            = $mysqli->query($query_sele);
		$row 		       = $result->fetch_array();
		$id ['idexportacion'] = $row['id']+1 ;

		return $id ;
	}

	function actualizarProtocolo($numeroExportacion,$idprotocolo,$mysqli,$idexportacion){
		$query_upd = "UPDATE protocolos_main SET estado=99, numero_exportacion= '".$numeroExportacion['exportacion']."' , idexportacion= '".$idexportacion."' WHERE id= $idprotocolo AND idexportacion = 0"; 
		if($result = $mysqli->query($query_upd)){
			//echo "Protocolo pasado a estado 99: $idprotocolo \n" ;
			//echo $query_upd;
		} else {
			echo $mysqli->error; 
		}  
	}

	function insertExpoAux($mysqli,$idexportacion,$idprotocolo,$tipo){
		$query_ins="INSERT INTO exportaciones_aux (idexportacion,idprotocolo,tipo) VALUES (" . $idexportacion . "," . $idprotocolo . ",'" . $tipo . "')"; 	
		if ($result   = $mysqli->query($query_ins)) {
			$id =$mysqli->insert_id;
			//echo "Protocolo incorporado : $idprotocolo \n" ;
			//echo $query_ins;
		}else{
			echo $mysqli->error; 
		}

	}

	function verificaInpactoCompleto($mysqli,$protocolos){

		//-- Verificar que no haya nada en 25 para generar salida esicion --------------------	
		$query_aprobadas = "SELECT idprotocolo, COUNT(*) AS cant FROM entrada WHERE estado = 25 AND idprotocolo IN ($protocolos)
		GROUP BY idprotocolo";
		$result    = $mysqli->query($query_aprobadas);

		return $this->filtroProtocolos($result,$protocolos,$mysqli);
	}

	function filtroProtocolos($result,$protocolos,$mysqli){

		$protocolosBienInpactados = array();
		$protocolosFalloInpacto   = array();

		while($verifico = $result->fetch_object()) {//identifico los protocolos con registros en estado 25 

			if ($verifico->cant != 0 ){	
				// protocolos identificados con reg en 25 de actualizan en protocolo main como "error de inpacto"				
				array_push($protocolosFalloInpacto,$verifico->idprotocolo);
				$this->updateErrorImpacto($verifico->idprotocolo,$mysqli);
			}		
		}
		//retorno los protocolos bien inpactados para que continuen ciclo
		$listaProtocolos 		  = explode(",",$protocolos);
		$protocolosBienInpactados = array_diff($listaProtocolos,$protocolosFalloInpacto);

		return $protocolosBienInpactados;
	}

	function updateErrorImpacto($protocolo,$mysqli){

		$query_upd="UPDATE protocolos_main SET incorporacion_estado=63 WHERE id='$protocolo' AND  incorporacion_estado=65";

		if($result = $mysqli->query($query_upd)){
			echo "Protocolo : $protocolo pasado a incorporacion_estado 63 FALLO IMPACTO de INO \n" ;
		} else {
			echo $mysqli->error;
		}  
	}

}	


$salida               = new SalidasEdicion(); 
$proyecto['municipio']= $salida->main();
$SalidaDeProtocolos   = $salida->arraydeProtocolos();
$opcionIndividual     = $salida->individual();

if($opcionIndividual == 'NO'){
	$esLuces              = $salida->parametrosLuces();
	$mysqli               = $salida->Conexion();
	$isGeneroSalida = 0;
	if($esLuces == NULL){
		// Vamos a verificar si se realizo bien el inpacto de integral a 314
		$seInpacto        	  = $salida->verificaInpactoCompleto($mysqli,$SalidaDeProtocolos);
		if (count($seInpacto) > 0) {
			$isGeneroSalida++;
			$numeroExportacion    = $salida->SelectExportacionesMain($mysqli);
			$IdUser 	['iduser']= 749 ; //Fdiaz
			$InserDeExportacion   = $salida->InsertExportacionesMain($numeroExportacion,$proyecto,$IdUser,$mysqli);
			$exit				  = $salida->salida($InserDeExportacion,$proyecto,$numeroExportacion,$seInpacto,$mysqli);
		} 
	}else{
		$isGeneroSalida++;
		$SalidaDeProtocolos = explode(",",$SalidaDeProtocolos);
		$numeroExportacion    = $salida->SelectExportacionesMain($mysqli);
		$IdUser 	['iduser']= 749 ; //Fdiaz
		$InserDeExportacion   = $salida->InsertExportacionesMain($numeroExportacion,$proyecto,$IdUser,$mysqli);
		$exit				  = $salida->salida($InserDeExportacion,$proyecto,$numeroExportacion,$SalidaDeProtocolos,$mysqli);
	}
	if( $isGeneroSalida > 0 ){
		echo "\n";
		echo "---------\n";
		echo "Se genero la exportacion # ".$numeroExportacion['exportacion']." Con el Id : ".$InserDeExportacion." Del proyecto ".$proyecto['municipio'] ;
		echo "\n";
		echo "---------\n";
	} else {
		echo " *NINGUN PROTOCOLO ES VALIDO \n";
	}
}else{
	$SalidaDeProtocolos = explode(",",$SalidaDeProtocolos);
	$esLuces              = $salida->parametrosLuces();
	foreach($SalidaDeProtocolos as $SalidaDeProtocolos){
		$mysqli           = $salida->Conexion();
		$isGeneroSalida = 0;
		if($esLuces == NULL){
			// Vamos a verificar si se realizo bien el inpacto de integral a 314
			$seInpacto        	  = $salida->verificaInpactoCompleto($mysqli,$SalidaDeProtocolos);
			if (count($seInpacto) > 0) {
				$isGeneroSalida++;
				$numeroExportacion    = $salida->SelectExportacionesMain($mysqli);
				$IdUser 	['iduser']= 749 ; //Fdiaz
				$InserDeExportacion   = $salida->InsertExportacionesMain($numeroExportacion,$proyecto,$IdUser,$mysqli);
				$exit				  = $salida->salida($InserDeExportacion,$proyecto,$numeroExportacion,$seInpacto,$mysqli);
			} 
		}else{
			$isGeneroSalida++;
			//$SalidaDeProtocolos = explode(",",$SalidaDeProtocolos);
			$numeroExportacion    = $salida->SelectExportacionesMain($mysqli);
			$IdUser 	['iduser']= 749 ; //Fdiaz
			$InserDeExportacion   = $salida->InsertExportacionesMain($numeroExportacion,$proyecto,$IdUser,$mysqli);
			$exit				  = $salida->salida($InserDeExportacion,$proyecto,$numeroExportacion,$SalidaDeProtocolos,$mysqli);
		}
		if( $isGeneroSalida > 0 ){
			echo "\n";
			echo "---------\n";
			echo "Se genero la exportacion # ".$numeroExportacion['exportacion']." Con el Protocolo : ".$SalidaDeProtocolos." Del proyecto ".$proyecto['municipio'] ;
			echo "\n";
			echo "---------\n";
		} else {
			echo " *NINGUN PROTOCOLO ES VALIDO\n";
		}
	}
}
?>
