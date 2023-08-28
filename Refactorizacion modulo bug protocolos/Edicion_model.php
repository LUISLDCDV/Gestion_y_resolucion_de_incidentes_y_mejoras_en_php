<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
Class Edicion_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

	function coordenadas_banner($data)
	{
	    $default = $this->load->database('default', TRUE);
	    $default->insert('coordenadas_banner', $data);
	    return true;
	}

	function enviarAEdicion($serie,$protocolo)
	{
	    $ssti = $this->load->database('ssti', TRUE);
	    $edicion = $this->load->database('edicion', TRUE);

	    $sql_update = "UPDATE `entrada` AS `e` LEFT JOIN `equipos_main` AS `em` ON `em`.`serie` = `e`.`serie` SET `e`.`estado` = 60 WHERE `e`.`estado` = 25 ";

	    $sql_select = "SELECT * FROM `entrada` AS `e` WHERE `e`.`estado` = 60 ";

	    if ($serie) {
	        $sql_update .= "AND `e`.`serie` = '$serie'";
	        $sql_select .= "AND `e`.`serie` = '$serie'";
	    } elseif ($protocolo) {
	        $sql_update .= "AND `e`.`idprotocolo` = $protocolo";
	        $sql_select .= "AND `e`.`idprotocolo` = $protocolo";
	    } else {
	        $tipo_velocidad = array(1,2,2402,2412);

	        $sql_update .= "AND `em`.`tipo` IN (";
	        $sql_select .= "AND `em`.`tipo` IN (";
	        $cant = count($tipo_velocidad)-1;
	        for ($i = 0; $i <= $cant; $i++) {
	            $sql_update .= $tipo_velocidad[$i];
	            $sql_select .= $tipo_velocidad[$i];
	            if ($i != $cant) {
	                $sql_update .= ',';
	                $sql_select .= ',';
	            }
	        }
	        $sql_update .= ")";
	        $sql_select .= ")";
	    }
	    $ssti->query($sql_update);

	    $query = $ssti->query($sql_select);

	    $edicion->insert_batch('entrada',$query->result());

	    return $ssti->affected_rows();
	}

	function copiando()
	{
	    $edicion = $this->load->database('edicion', TRUE);
	    $query = $edicion->get_where('entrada', array('estado' => 61));
	    if ($query->num_rows() > 0) {
	        return TRUE;
	    }
	    return FALSE;
	}

	function transferirArchivos()
	{
	    // Si ya otro script está copiando, dejo que el otro termine y devuelvo error
	    if ($this->copiando()) {
	        return array('estado' => 'error', 'mensaje' => 'Ya hay activo un proceso de copiado.');
	    } else {
	        // Quito el límite de tiempo de ejecución, porque el copiado de archivos puede llegar a demorar varios minutos.
	        set_time_limit(0);

	        // Datos FTP
	        $ftp_server = '190.12.113.219';
	        $ftp_user = 'edicion';
	        $ftp_pass = 'B)k^>S5aefBBCd%Y';

            // Conecto al server
	        $conn_id = ftp_connect($ftp_server);
            // Devuelvo error de conexión
	        if ($conn_id === FALSE) {
	            return array('estado' => 'error', 'mensaje' => 'No se pudo conectar al servidor FTP.');
	        } else {
	            // iniciar sesión con nombre de usuario y contraseña
	            $login_result = ftp_login($conn_id, $ftp_user, $ftp_pass);

	            // Rutas
	            $origin_path = ORIGIN_PATH;
	            $destiny_path = DESTINY_PATH;

	            // Activo conexión pasiva
	            ftp_pasv($conn_id, TRUE);

	            $finalizar = FALSE;
	            while (!$finalizar) {
	                $edicion = $this->load->database('edicion', TRUE);
	                // Traigo todas las imágenes que hay que copiar
	                $edicion->where(array('estado' => 60));
	                $edicion->order_by('fecha_toma', 'ASC');
	                $query = $edicion->get('entrada', 1);
	                if ($query->num_rows() > 0) {
	                    $row = $query->row();
	                    // Lo marco como en proceso de copiado
	                    $edicion->where('id', $row->id);
	                    $edicion->update('entrada', array('estado' => 61));

	                    $exito = TRUE;
	                    //Cambio al directorio del protocolo de ese registro
	                    ftp_chdir($conn_id,"{$origin_path}/{$row->protocolo}/");
	                    for ($cont = 1; $cont <= 4; $cont++) {
	                        if (trim($row->{"imagen{$cont}"}) != '') {
	                            if (!ftp_get($conn_id, $destiny_path.$row->{"imagen{$cont}"}, $row->{"imagen{$cont}"}, FTP_BINARY)) {
	                                $exito = FALSE;
	                                // REINTENTAR!!!
	                            }
	                        }
	                    }
	                    if ($exito) {
	                        // Lo marco como copia exitosa
	                        $edicion->where('id', $row->id);
	                        $edicion->update('entrada', array('estado' => 62));
	                    } else {
	                        // Lo marco como copia fallida
	                        $edicion->where('id', $row->id);
	                        $edicion->update('entrada', array('estado' => 63));
	                    }
	                } else {
	                    $finalizar = TRUE;
	                }
	            }
            }
        }
        return TRUE;
    }

    function protocolosAsignados($id_usuario)
    {
      $edicion = $this->load->database('edicion', TRUE);
      $edicion->select('PA.protocolo');
      $edicion->from('protocolos_asignados as PA');
      $edicion->join('entrada as EN', 'EN.protocolo = PA.protocolo','left');
      $edicion->where('PA.editor',$id_usuario);
      $edicion->where('EN.estado',100);
      $edicion->group_by("PA.protocolo");
      $edicion->order_by("PA.fecha_asignacion","ASC");

      $query = $edicion->get();
      $row = $query->row();

      if (empty($row)) {
        $row = NULL;
      }

      return $row->protocolo;
    }

    function fotosEdicion()
    {
        // Guardo id de registro de la tabla entrada en $idfoto
        $idfoto = $this->getFotoAsignada($this->session->usuario['id']);

        if (!$idfoto) {
            if ($this->estaBloqueada('entrada')) {
                return FALSE;
            } else {
                $this->bloquearTabla('entrada','WRITE');
            }
        }

        $protocolo = $this->protocolosAsignados($this->session->usuario['id']);
	      $fotos = $this->getNombresFotos($idfoto,$protocolo);

        //$fotos = $this->getNombresFotos($idfoto);
        $fecha_asignada = date('Y-m-d H:i:s');

        if ($fotos && !$idfoto) {
            $this->desbloquearTabla();
            $this->asignarFoto($fotos->id,$this->session->usuario['id'],$fecha_asignada);
        } else {
            $default = $this->load->database('default', TRUE);
            $default->where('idusuario', $this->session->usuario['id']);
            $default->update('edicion_asignaciones', array('fecha' => $fecha_asignada));
        }

        if ($fotos) {
            $fotos->fecha = $fecha_asignada;
        }

        return $fotos;
    }


	function newFotosEdicion()
    {
        // Guardo id de registro de la tabla entrada en $idfoto
        $idfoto = $this->newgetFotoAsignada($this->session->usuario['id']);

        if (!$idfoto) {
            if ($this->estaBloqueada('entrada')) {
                return FALSE;
            } else {
                $this->bloquearTabla('entrada','WRITE');
            }
        }

        $fotos = $this->newgetNombresFotos($idfoto);
        $fecha_asignada = date('Y-m-d H:i:s');

        if ($fotos && !$idfoto) {
            $this->desbloquearTabla();
            $this->asignarFoto($fotos->id,$this->session->usuario['id'],$fecha_asignada);
        } else {
            $default = $this->load->database('default', TRUE);
            $default->where('idusuario', $this->session->usuario['id']);
            $default->update('edicion_asignaciones', array('fecha' => $fecha_asignada));
        }

		if ($fotos) {
            $fotos->fecha = $fecha_asignada;
        }

        return $fotos;
    }

	function newgetFotoAsignada($usuario)
    {
    		// CAMBIO evitar REPETIDAS
    		$default = $this->load->database('default', TRUE);

    		$default->select('idusuario, identrada, fecha');
    		$default->from('edicion_asignaciones');

    		$where = "idusuario = $usuario AND identrada NOT IN (SELECT identrada FROM edicion_asignaciones WHERE idusuario != $usuario)";
    		$default->where($where);

    		//$default->having('COUNT(*)', 1);
    		$query = $default->get();
        	$row = $query->row();
    		// FIN CAMBIO evitar REPETIDAS

			if (!$row) {
					// CAMBIO ELIMINAR REPETIDA
					$default = $this->load->database('default', TRUE);
					// Quito la asignación
					$default->delete('edicion_asignaciones', array('idusuario' => $usuario));
			return FALSE;
			}

        return $row->identrada;
    }


    function newgetNombresFotos($id = NULL)
    {
        $edicion = $this->load->database('edicion', TRUE);
        $edicion->select('e.id, e.serie, em.tipo, e.imagen1, e.imagen2, e.imagen3, e.imagen4, em.municipio, e.idprotocolo');
        $edicion->from('entrada as e');
        $edicion->join('equipos_main AS em','e.serie = em.serie','LEFT');

		if ($id) {
            $edicion->where('e.id', $id);
          } else {
            $edicion->where('e.estado', 62);
            $edicion->order_by('e.prioridad ASC, e.idprotocolo ASC');
            $azar = rand(0,1000);
            $edicion->limit(1,$azar);
          }

        $query = $edicion->get();

		    // CAMBIOS 2
		    $row = $query->row();
		    // Primero le cambio el estado a asignado
        $edicion->where('id', $row->id);
        $edicion->update('entrada', array('estado' => 64));
		    // FIN CAMBIOS 2

        return $query->row();
     }





    function getNombresFotos($id = NULL, $protocolo = NULL)
    //function getNombresFotos($id = NULL)
    {
        $edicion = $this->load->database('edicion', TRUE);
        $edicion->select('e.id, e.serie, em.tipo, e.imagen1, e.imagen2, e.imagen3, e.imagen4, em.municipio, e.idprotocolo');
        $edicion->from('entrada as e');
        $edicion->join('equipos_main AS em','e.serie = em.serie','LEFT');

        /*
		    // Si la foto no está asignada a un editor $id viene en NULL
        if ($id) {
            $edicion->where('e.id', $id);
        } else {
    		// Si no tiene foto asignada, busco por prioridad la más vieja OJO ver CAMBIO 3
    		//$edicion->group_by(array('e.prioridad', 'e.fecha_toma'));
        //Verifico que tenga un protocolo asignado si no busco del listado en general.

    		//$edicion->where('e.estado', 62);
        //$edicion->order_by('e.prioridad ASC, e.idprotocolo ASC');

			  // CAMBIO 3
			  // Las prioridades quedan para uso exepcional
			  // Se inicia desde protocolo mas viejo
    		// $edicion->order_by('e.prioridad ASC, e.fecha_toma ASC');
    		//$edicion->limit($this->session->usuario['id'],1);
			  // FIN CAMBIO 3

			  // CAMBIOS
			  //$azar = rand(0,100) * $this->session->usuario['id'];
			  // QUITAR AZAR
			  $azar = rand(0,1000);
			  //$azar = 0;
    		//$edicion->limit($azar,1);
			  //OJO en codeigniter es al reves $this->db->limit($limit, $start);
			  $edicion->limit(1,$azar);
			  // FIN CAMBIOS
        }
        */

        if ($protocolo) {
          $edicion->where('e.protocolo', $protocolo);
          $edicion->where('e.estado', 100);
        } else {
          if ($id) {
            $edicion->where('e.id', $id);
          } else {
            $edicion->where('e.estado', 62);
            $edicion->order_by('e.prioridad ASC, e.idprotocolo ASC');
            $azar = rand(0,1000);
            $edicion->limit(1,$azar);
          }
        }

        $query = $edicion->get();

		    // CAMBIOS 2
		    $row = $query->row();
		    // Primero le cambio el estado a asignado
        $edicion->where('id', $row->id);
        $edicion->update('entrada', array('estado' => 64));
		    // FIN CAMBIOS 2

        return $query->row();
     }

    function getFotoAsignada($usuario)
    {
        /*$default = $this->load->database('default', TRUE);
        $query = $default->get_where('edicion_asignaciones',array('idusuario' => $usuario));
        $row = $query->row();*/

    		// CAMBIO evitar REPETIDAS
    		$default = $this->load->database('default', TRUE);

    		$default->select('idusuario, identrada, fecha');
    		$default->from('edicion_asignaciones');
    		//$default->where('idusuario',$usuario);

    		$where = "idusuario = $usuario AND identrada NOT IN (SELECT identrada FROM edicion_asignaciones WHERE idusuario != $usuario)";
    		$default->where($where);

    		//$default->having('COUNT(*)', 1);
    		$query = $default->get();
        $row = $query->row();
    		// FIN CAMBIO evitar REPETIDAS

        if (!$row) {
    			// CAMBIO ELIMINAR REPETIDA
    			$default = $this->load->database('default', TRUE);
    			// Quito la asignación
    			$default->delete('edicion_asignaciones', array('idusuario' => $usuario));
          return FALSE;
        }

//         $fecha_inicio = new DateTime($row->fecha);
//         $desde_inicio = $fecha_inicio->diff(new DateTime());
//         $minutos = $desde_inicio->days * 1440 + $desde_inicio->h * 60 + $desde_inicio->i;

//         // Tiene foto asignada en los últimos 5 minutos
//         if ($minutos >= 5) {
//             $this->desasignarFoto($usuario,$row->identrada);
//             return FALSE;
//         }

        return $row->identrada;
    }


	




    function getAsignacion($usuario)
    {
        $default = $this->load->database('default', TRUE);
        $query = $default->get_where('edicion_asignaciones',array('idusuario' => $usuario));

        return $query->row();
    }

    function getAsignacion2($id_entrada)
    {
        $default = $this->load->database('default', TRUE);
        $query = $default->get_where('edicion_asignaciones',array('identrada' => $id_entrada));

        return $query->row();
    }

    function asignarFoto($id,$usuario,$fecha_asignada)
    {
        $edicion = $this->load->database('edicion', TRUE);
        // Primero le cambio el estado a asignado
        $edicion->where('id', $id);
        $edicion->update('entrada', array('estado' => 64));

        $default = $this->load->database('default', TRUE);
        // Segundo, inserto en la tabla asignaciones a qué usuario está asignada
        $default->insert('edicion_asignaciones', array('idusuario' => $usuario, 'identrada' => $id, 'fecha' => $fecha_asignada));
    }

    function desasignarFoto($usuario,$id)
    {
        $edicion = $this->load->database('edicion', TRUE);
        // La marco como desasignada
        $edicion->where('id', $id);
        $edicion->update('entrada', array('estado' => 62));

        $default = $this->load->database('default', TRUE);
        // Quito la asignación
        $default->delete('edicion_asignaciones', array('idusuario' => $usuario));
    }

// este código sirve para buscar las fotos de edición, pero no para transferir archivos
// 	function transferirArchivos()
// 	{
// 	    $cont = 1;
// 	    while ($this->estaBloqueada('entrada') && $cont < MAXIMOS_INTENTOS_BLOQUEADA) {
// 	            $cont++;
// 	    }
// 	    // Luego de los intentos sigue bloqueada, ¿proceso quedó colgado?
// 	    if ($this->estaBloqueada('entrada')) {
// 	        return FALSE;
// 	    } else {
// 	        $this->bloquearTabla('entrada');
// 	        $cont = 1;
// 	        $ftp_server = '186.153.121.11';
// 	        $ftp_user = 'equipos';
// 	        $ftp_pass = 'EqRem16ws';

// 	        while ($this->estaBloqueada('entrada') && $cont < MAXIMOS_INTENTOS_CONEXION) {
// 	            $cont++;
// 	        }
// 	    }
// 	}

	function estaBloqueada($tabla)
	{
	    $edicion = $this->load->database('edicion', TRUE);
        $sql = "SHOW OPEN TABLES FROM edicion LIKE '$tabla';";

        $query = $edicion->query($sql);

	    $row = $query->row();

	    return $row->Name_locked;
	}

	function bloquearTabla($tabla,$tipo_bloqueo)
	{
	    $edicion = $this->load->database('edicion', TRUE);
        $sql = "LOCK TABLE $tabla $tipo_bloqueo;";

        $edicion->query($sql);

	    return TRUE;
	}

	function desbloquearTabla()
	{
	    $edicion = $this->load->database('edicion', TRUE);
        $sql = "UNLOCK TABLES;";

        $edicion->query($sql);

	    return TRUE;
	}

	function equipoEnEdicion($serie)
	{
	    $ssti = $this->load->database('ssti', TRUE);
	    $query = $ssti->get_where('entrada', array('estado' => 25, 'serie' => $serie));
	    if ($query->num_rows() > 0) {
	        return TRUE;
	    }
	    return FALSE;
	}

	function protocoloEnEdicion($protocolo)
	{
	    $ssti = $this->load->database('ssti', TRUE);
	    $query = $ssti->get_where('entrada', array('estado' => 25, 'idprotocolo' => $protocolo));
	    if ($query->num_rows() > 0) {
	        return TRUE;
	    }
	    return FALSE;
	}

	function getCodigosDescartes()
	{

        $edicion = $this->load->database('edicion', TRUE);

        $edicion->order_by('codigo','asc');
        $query = $edicion->get('edicion_descartes');

	    return $query->result();
	}

	function guardarEdicionLocal($data)
	{
	    $default = $this->load->database('default', TRUE);
	    $default->trans_start();
	    $default->insert('edicion', $data);
	    $default->trans_complete();

	    if ($default->trans_status() === FALSE) {
            //Si fall� el insert.
	        $default->insert('edicion_erroresinsert', $data);
	    }
	    // Quito la asignación
	    //$default->delete('edicion_asignaciones', array('idusuario' => $data['usuario']));

      //Quito la asignacion por el identrada
      $default->delete('edicion_asignaciones', array('identrada' => $data['idtoma']));
	}

	function guardarEdicionSSTI($data,$imagen_zoom)
	{
	    $edicion = $this->load->database('edicion', TRUE);
	    // Revisar esta parte, cómo se si está aprubado? Es correcto este resultado?
	    if ($data['resultado'] == 99) {
	        $estado = 26;
	    } else {
	        $estado = 27;
	    }

	    $edicion->insert('edicion', $data);

	    $edicion->where('id', $data['idtoma']);
	    $edicion->update('entrada', array('estado' => $estado, 'dominio' => $data['dominio'], 'idedicion' => $edicion->insert_id(), 'tipo_vehiculo' => $data['tipo_vehiculo'], 'imagen_zoom' => $imagen_zoom));
	}



	function guardarDatosEdicion($datos_edicion)
	{
	    $default = $this->load->database('default', TRUE);
	    $default->trans_start();
	    $default->insert('edicion_datos', $datos_edicion);
	    $default->trans_complete();

      	//Quito la asignacion por el identrada
      	$default->delete('edicion_asignaciones', array('identrada' => $datos_edicion['identrada']));
	}


	

	function protocolosEnEdicionLocal($estados = NULL)
	{
	    $edicion = $this->load->database('edicion', TRUE);

	    $edicion->distinct('idprotocolo');
	    $edicion->select('idprotocolo');
	    if (!is_null($estados)) {
	        $edicion->where_in('estado',$estados);
	    }
	    $query = $edicion->get_where('entrada',array());

	    return $query->result();
	}

	function protocoloCompletamenteEditado($protocolo)
	{
	    $edicion = $this->load->database('edicion', TRUE);

	    $estados = array(60,61,62,63,64);
	    $edicion->where_in('estado',$estados);
	    $edicion->where('idprotocolo',$protocolo);
	    $query = $edicion->get('entrada');
	    if ($query->num_rows() > 0) {
	        return TRUE;
	    }
	    return FALSE;
	}

  /*
	function sincronizarProtocolo_original($protocolo)
	{
	    $exito = TRUE;
	    $ssti = $this->load->database('ssti', TRUE);
	    $edicion = $this->load->database('edicion', TRUE);

	    // Primero traigo cuales son los de este protocolo que hay que sincronizar con el SSTI
	    $estados = array(26,27);
	    $edicion->where_in('estado',$estados);
	    $edicion->where('idprotocolo',$protocolo);
	    $query = $edicion->get('entrada');

	    $registros_entrada = $query->result();

	    foreach ($registros_entrada as $registro_entrada) {
	        //$result_insert = TRUE;
	        //if (!$this->getEdicionSSTIByIdEntrada($registro_entrada->id)) {
	            $datos_edicion = $this->getEdicionByIdEntrada($registro_entrada->id);
	            //unset($datos_edicion->id);
              $datos_edicion->id = NULL;
	            $result_insert = $ssti->insert('edicion', $datos_edicion);
	        //}

	        if ($result_insert) {
    	        // Actualizo en el SSTI
    	        $data = array(
    	            'estado' => $registro_entrada->estado,
    	            'dominio' => $registro_entrada->dominio,
    	            'idedicion' => $ssti->insert_id(),
    	            'tipo_vehiculo' => $registro_entrada->tipo_vehiculo,
                );

    	        $ssti->where('id',$registro_entrada->id);
                $ssti->update('entrada',$data);

                $data = array(
                    'estado' => $registro_entrada->estado*10,
                );

                $edicion->where('id',$registro_entrada->id);
                $edicion->update('entrada', $data);
	        } else {
	            $exito = FALSE;
	        }
	    }
	    return $exito;
	}
  */


  function sincronizarProtocolo($protocolo)
	{
	    $exito = TRUE;
	    $ssti = $this->load->database('ssti', TRUE);
	    $edicion = $this->load->database('edicion', TRUE);

        $edicion->select('EN.estado, EN.imagen_zoom, ED.id, ED.tipo, ED.usuario, ED.frequest, ED.iprequest, ED.fasignacion, ED.idtoma, ED.token, ED.fresultado, ED.resultado,
        ED.valor, ED.tipo_vehiculo, ED.dominio, ED.infrac, ED.coords, ED.velreg, ED.imp_entrada');
        $edicion->from('entrada as EN');
        $edicion->join('edicion as ED','ED.id = EN.idedicion','INNER');
        $edicion->where('EN.idprotocolo',$protocolo);

        $query = $edicion->get();
	    $registros_entrada = $query->result();

	    foreach ($registros_entrada as $registro_entrada) {
              $datos_edicion = array('id' => NULL,
              'tipo' => $registro_entrada->tipo,
              'usuario' => $registro_entrada->usuario,
              'frequest' => $registro_entrada->frequest,
              'iprequest' => $registro_entrada->iprequest,
              'fasignacion' => $registro_entrada->fasignacion,
              'idtoma' => $registro_entrada->idtoma,
              'token' => $registro_entrada->token,
              'fresultado' => $registro_entrada->fresultado,
              'resultado' => $registro_entrada->resultado,
              'valor' => $registro_entrada->valor,
              'tipo_vehiculo' => $registro_entrada->tipo_vehiculo,
              'dominio' => $registro_entrada->dominio,
              'infrac' => $registro_entrada->infrac,
              'coords' => $registro_entrada->coords,
              'velreg' => $registro_entrada->velreg,
              'imp_entrada' => $registro_entrada->imp_entrada
            );
            $result_insert = $ssti->insert('edicion', $datos_edicion);

	        if ($result_insert) {
    	        // Actualizo en el SSTI
    	        $data = array(
    	            'estado' => $registro_entrada->estado,
    	            'dominio' => $registro_entrada->dominio,
    	            'idedicion' => $ssti->insert_id(),
    	            'tipo_vehiculo' => $registro_entrada->tipo_vehiculo,
    	            'imagen_zoom' => $registro_entrada->imagen_zoom
                );

                $ssti->where('id',$registro_entrada->idtoma);
                $ssti->update('entrada',$data);

                $data = array('estado' => $registro_entrada->estado*10);

                $edicion->where('id',$registro_entrada->idtoma);
                $edicion->update('entrada', $data);
	        } else {
	            $exito = FALSE;
	        }
	    }
	    return $exito;
	}


	function getEdicionByIdEntrada($identrada)
	{
	    $edicion = $this->load->database('edicion', TRUE);

      $edicion->order_by('id',"desc");
	    $query = $edicion->get_where('edicion', array('idtoma' => $identrada));
	    return $query->row();
	}

	function getEdicionSSTIByIdEntrada($identrada)
	{
	    $ssti = $this->load->database('edicion', TRUE);

	    $query = $ssti->get_where('edicion', array('idtoma' => $identrada));
	    return $query->row();
	}




  function busquedaProtocolo($estados, $idprotocolo)
	{
	    $edicion = $this->load->database('edicion', TRUE);

	    $edicion->select('id,idprotocolo');
	    $edicion->where_in('estado',$estados);
      $edicion->where('idprotocolo',$idprotocolo);

	    $query = $edicion->get_where('entrada',array());

	    return $query->result();
	}

  function updateEntrada($entradaInfo,$id_entrada)
	{
      $edicion = $this->load->database('edicion', TRUE);

			$edicion->where('id', $id_entrada);
			$edicion->update('entrada', $entradaInfo);

			return TRUE;
	}

  	function updateProtocolo($entradaInfo, $idprotocolo, $estados)
	{
      	$edicion = $this->load->database('edicion', TRUE);

		$edicion->where('idprotocolo', $idprotocolo);
	    $edicion->where_in('estado', $estados);
		$edicion->update('entrada', $entradaInfo);

		return TRUE;
	}


		/*
	function descartarProtocolo($entradaInfo, $idprotocolo)
	{
      	$edicion = $this->load->database('edicion', TRUE);

		$edicion->where('idprotocolo', $idprotocolo);
		$edicion->update('entrada', $entradaInfo);

		return TRUE;
	}
	*/




  function copiarArchivosSSTI($calidad = 50,$estado, $nroprotocolo)
  {
      $mnj = " OK v3.0<br>";
      set_time_limit(0);
      // Traigo todas las imágenes que hay que copiar de un protocolo
      $edicion = $this->load->database('edicion', TRUE);
      $edicion->select('imagen1, imagen2, imagen3, imagen4, serie');
      $edicion->from('entrada');
      $edicion->where('idprotocolo',$nroprotocolo);
      $edicion->where('estado',$estado);
      $edicion->order_by('fecha_toma', 'ASC');
      $query = $edicion->get();
      if ($query->num_rows() > 0) {
        $count = 0;

        //CREAR CARPETA X PROTOCOLO
        $carpetaProtocolo = '/mnt/Disco2/' . $nroprotocolo;
        if (!file_exists($carpetaProtocolo)) {
          mkdir($carpetaProtocolo, 0777, true);
        }

        //COPIAR FOTOS
        foreach ($query->result() as $row){
        $count++;
        $verfotoSSTI = "http://edicion.cecaitra.com/modulos/ver-foto-tam-araujo.php?p=" . $nroprotocolo . "&f=";
        $imagenNombre = $row->imagen1;
        $imagenNombre2 = str_replace(" ", "%20", $imagenNombre);
        //c= Calidad de la foto (00 a 100)
        $rutaFoto = $verfotoSSTI.$imagenNombre2."&c=$calidad";
        //$imagen = file_get_contents($rutaFoto);
        $copiarEn = '/mnt/Disco2/' . $nroprotocolo . '/' . $imagenNombre;
        //$save = file_put_contents($copiarEn,$imagen);

        $this->captureImage($rutaFoto,$copiarEn);
        //copy($rutaFoto, $copiarEn);

        $mnj .= $count . ".- " .$row->imagen1 . " ";
        //die(var_dump($row->imagen2));

        if(!is_null($row->imagen2)){
          $imagenNombre = $row->imagen2;
          $imagenNombre2 = str_replace(" ", "%20", $imagenNombre);
          $rutaFoto = $verfotoSSTI.$imagenNombre2."&t=9";
          $copiarEn = '/mnt/Disco2/' . $nroprotocolo . '/' . $imagenNombre;
          $this->captureImage($rutaFoto,$copiarEn);
          $mnj .= $row->imagen2 . " " ;
        }

        if(!is_null($row->imagen3)){
          $imagenNombre = $row->imagen3;
          $imagenNombre2 = str_replace(" ", "%20", $imagenNombre);
          $rutaFoto = $verfotoSSTI.$imagenNombre2."&t=9";
          $copiarEn = '/mnt/Disco2/' . $nroprotocolo . '/' . $imagenNombre;
          $this->captureImage($rutaFoto,$copiarEn);
          $mnj .= $row->imagen3 . " " ;
        }

        if(!is_null($row->imagen4)){
          $imagenNombre = $row->imagen4;
          $imagenNombre2 = str_replace(" ", "%20", $imagenNombre);
          $rutaFoto = $verfotoSSTI.$imagenNombre2."&t=9";
          $copiarEn = '/mnt/Disco2/' . $nroprotocolo . '/' . $imagenNombre;
          $this->captureImage($rutaFoto,$copiarEn);
          $mnj .= $row->imagen4 . " " ;
        }

        $mnj .="<br>" ;
        }

        //$row = $query->row();
        // Lo marco como en proceso de copiado
        /*$edicion->where('id', $row->id);
        $edicion->update('entrada', array('estado' => 61));
        $exito = TRUE;*/

        /*if ($exito) {
          // Lo marco como copia exitosa
          $edicion->where('id', $row->id);
          $edicion->update('entrada', array('estado' => 62));
        } else {
          // Lo marco como copia fallida
          $edicion->where('id', $row->id);
          $edicion->update('entrada', array('estado' => 63));
        }*/

      } else {
        $mnj = " El nro de protocolo: $nroprotocolo para el estado: $estado no tiene fotos para copiar. ";
      }

        return $mnj;
  }


  function captureImage($origin, $destination)
  {
    $mi_curl = curl_init ($origin);
    $fp_destination = fopen ($destination, "w");
    curl_setopt ($mi_curl, CURLOPT_FILE, $fp_destination);
    curl_setopt ($mi_curl, CURLOPT_HEADER, 0);
    //curl_setopt($mi_curl, CURLOPT_RETURNTRANSFER, 1);
    //curl_setopt($mi_curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec ($mi_curl);
    curl_close ($mi_curl);
    fclose ($fp_destination);

    return TRUE;
  }


  function getProtocoloToma($id)
	{
		$edicion = $this->load->database('edicion', TRUE);
		$edicion->select('idprotocolo');
		$edicion->from('entrada');
		$edicion->where('id',$id);

		$query = $edicion->get();
		$row = $query->row();
		return $row->idprotocolo;
	}

  function getProtocolo($imagen)
	{
	  $edicion = $this->load->database('edicion', TRUE);

	  $edicion->select('idprotocolo');
      $edicion->from('entrada');
      $edicion->where('imagen1',$imagen);
      $edicion->or_where('imagen2',$imagen);
      $edicion->or_where('imagen3',$imagen);
      $edicion->or_where('imagen4',$imagen);

      $query = $edicion->get();
      $row = $query->row();
      return $row->idprotocolo;
	}

	function editores_en_accion()
	{
	    // Variable $tiempoIntervalo seteo cuantos minutos para atrás quiero filtrar los registros, partiendo desde la hora actual.
	    $tiempoIntervalo = "-10";

	    $sql = "SELECT EA.idusuario, CONCAT (U.apellido, ' ', U.nombre) as nombre , EA.identrada, DATE_FORMAT(EA.fecha, '%H:%i:%s') as fecha,  EN.idprotocolo, EN.serie, EN.imagen1, MU.descrip
	    FROM edicion.edicion_asignaciones EA
	    LEFT JOIN cecaitra_edicion.entrada EN ON EN.id = EA.identrada
	    LEFT JOIN cecaitra_edicion.equipos_main EM ON EN.serie = EM.serie
	    LEFT JOIN cecaitra_edicion.municipios MU ON MU.id = EM.municipio
	    LEFT JOIN edicion.usuarios U ON U.id = EA.idusuario
      WHERE EA.fecha > DATE_ADD(NOW(), INTERVAL $tiempoIntervalo MINUTE) AND U.rol IN (2,5)
      GROUP BY nombre, EN.idprotocolo
	    ORDER BY EA.fecha DESC";

	    $query = $this->db->query($sql);

	    return  $query->result();
	}

	function totalEditoresEnAccion()
	{
	    $tiempoIntervalo = "-10";
	    $where = "ea.fecha > DATE_ADD(NOW(), INTERVAL $tiempoIntervalo MINUTE)";
	    $default = $this->load->database('default', TRUE);
	    $default->join('edicion_asignaciones AS ea', 'u.id = ea.idusuario');
	    $default->where($where);
	    $default->where_in('u.rol', array(2,5));

	    return $default->count_all_results('usuarios AS u');
	}

	function existProtocolo($protocolo)
	{
	    $edicion = $this->load->database('edicion', TRUE);

	    $edicion->select('idprotocolo');
	    $edicion->from('entrada');
	    $edicion->where('idprotocolo',$protocolo);
	    $query = $edicion->get();
	    if ($query->num_rows() > 0) {
	        return TRUE;
	    }
	    return FALSE;
	}

	function updateProtocoloPrioridad($protocolo , $prioridad = 1)
	{
	    $edicion = $this->load->database('edicion', TRUE);

	    $this->db->trans_start();

	    $edicion->where('idprotocolo', $protocolo);
	    $edicion->update('entrada', array('prioridad' => $prioridad));

	    $this->db->trans_complete();

	    if ($this->db->trans_status() === FALSE)
	    {
	        return false;
	    }
	    return true;
	}

	function actualizarEstadoPrioridad($protocolos = NULL)
	{
	    $edicion = $this->load->database('edicion', TRUE);

	    $this->db->trans_start();
	    $edicion->where('estado',64);
      if ($protocolos || $protocolos != NULL) {
        $edicion->where_not_in('idprotocolo',$protocolos);
      }
	    $edicion->update('entrada', array('prioridad' => 0, 'estado' => 62));
      $this->db->trans_complete();

	    if ($this->db->trans_status() === FALSE) {
	        return false;
	    }else{
	        $default = $this->load->database('default', TRUE);
	        //$default->truncate('edicion_asignaciones');
          //Borro todas las asignaciones menos los protocolos asignados.
          $default->delete('edicion_asignaciones', array('asignado' => 0));
	        return true;
	    }
	}

	function contarTotalAsignaciones()
	{
	    $default = $this->load->database('default', TRUE);

	    $default->select('*');
	    $default->from('edicion_asignaciones');
      //Busco todas las asignaciones menos las de los protocolos asignados.
      $default->where('asignado',0);

	    $query = $default->get();
	    if ($query->num_rows() >= 0) {
	        return $query->num_rows();
	    }
	    return FALSE;
	}

	function listar_protocolos_estado()
	{
        $edicion = $this->load->database('edicion', TRUE);

        $edicion->select('idprotocolo');
        $edicion->from('entrada');
        $edicion->where('estado',260);
	    $edicion->or_where('estado',270);
	    $edicion->group_by('idprotocolo');
	    $edicion->limit(50);

        $query = $edicion->get();
        return $query->result_array();

	}

	function protocolosNotInEstado($protocolo, $estados)
	{
	    $edicion = $this->load->database('edicion', TRUE);

	    $edicion->select('count(id) as cantidad');
	    $edicion->from('entrada');
	    $edicion->where_in('idprotocolo', $protocolo);
	    $edicion->where_not_in('estado', $estados);

	    $query = $edicion->get();
        $row = $query->row();
	    return $row->cantidad;
	}

	function protocolosEstado($estados)
	{
	    $edicion = $this->load->database('edicion', TRUE);

	    $edicion->select('EN.idprotocolo, EN.serie');
	    $edicion->from('entrada as EN');
	    $edicion->where_in('EN.estado', $estados);
	    $edicion->group_by('EN.idprotocolo');

	    $query = $edicion->get();
	    return $query->result_array();
	}

	function protocolosEstadoAsignados($estados)
	{
	    $edicion = $this->load->database('edicion', TRUE);

	    $edicion->select('PC.id as idprotocolo, EM.serie, PC.registros as cantidad');
	    $edicion->from('protocolos_control as PC');
		$edicion->join('equipos_main as EM', 'EM.id = PC.idequipo','left');
	    $edicion->where('PC.estado', $estados);
	    $edicion->order_by('EM.serie', 'ASC');

	    $query = $edicion->get();
	    return $query->result_array();
	}


	
	function updateEstadoProtocolosControl($protocolo, $estado)
	{
	    $edicion = $this->load->database('edicion', TRUE);
	    $edicion->trans_start();

	    $edicion->set('estado', $estado);
	    $edicion->where('id', $protocolo);
	    $edicion->update('protocolos_control');
	    $edicion->trans_complete();

	    if ($edicion->trans_status() === FALSE)
	    {
	        return false;
	    }
	    return true;
	}





	function setProtocoloAsignado($data){
	    $edicion = $this->load->database('edicion', TRUE);
	    $edicion->insert('protocolos_asignados', $data);
	    return true;
	}

	function updateEstado($estado,$protocolo)
	{
	    $edicion = $this->load->database('edicion', TRUE);

	    $edicion->trans_start();

	    $edicion->set('estado',$estado);
	    $edicion->where('protocolo', $protocolo);
	    $edicion->update('entrada');
	    $edicion->trans_complete();

	    if ($edicion->trans_status() === FALSE)
	    {
	        return false;
	    }
	    return true;
	}

	function contarPresunciones($estados = NULL)
	{
	    $edicion = $this->load->database('edicion', TRUE);

	    if (!is_null($estados)) {
	        $edicion->where_in('estado',$estados);
	    }

	    return $edicion->count_all_results('entrada');
	}


  function tipoEquipo_total($estados, $serie)
	{
	    $edicion = $this->load->database('edicion', TRUE);
	    $edicion->where_not_in('estado',$estados);
			$edicion->like('serie',$serie);

	    return $edicion->count_all_results('entrada');
	}




	function contarPresuncionesEditadas($fecha_desde,$fecha_hasta)
	{
	    $default = $this->load->database('default', TRUE);

	    $default->where('fecha_editado >=', $fecha_desde);
	    $default->where('fecha_editado <=', $fecha_hasta);

	    return $default->count_all_results('edicion');
	}

	function reestablecer_estado($idtoma , $estado)
	{
	    $edicion = $this->load->database('edicion', TRUE);
	    $this->db->trans_start();

	    $edicion->where('id', $idtoma);
	    $edicion->update('entrada', array('estado' => $estado));

	    $this->db->trans_complete();

	    if ($this->db->trans_status() === FALSE)
	    {
	        return false;
	    }
	    return true;
	}

	function imagenesAprobadas($esvelocidad, $fechaMIN, $fechaMAX, $limit, $offset)
	{
	    $sql = "SELECT e.tipo_infraccion, e.estado, e.fecha_editado, e.usuario, en.imagen1, en.imagen2, en.imagen3, en.imagen4, u.nombre, u.apellido
	    FROM edicion.edicion AS e
        JOIN edicion.usuarios AS u ON u.id = e.usuario
        JOIN cecaitra_edicion.entrada AS en ON e.idtoma = en.id
        WHERE e.estado = 1";

	    if ($esvelocidad){
	        $sql .= " AND e.tipo_infraccion = 9 ";
	    }else{
	        $sql .= " AND e.tipo_infraccion IN (99,98)";
	    }

	    $sql .= " AND e.fecha_editado BETWEEN '$fechaMIN' AND '$fechaMAX'
                LIMIT {$offset},{$limit}";

	    $query = $this->db->query($sql);
	    return  $query->result();
	}

	function contarAprobadas($fecha_min, $fecha_max)
	{
	    $sql = "SELECT COUNT(*) AS cantidad FROM `edicion` WHERE estado = 1 AND fecha_editado BETWEEN '$fecha_min' AND '$fecha_max'";

	    $query = $this->db->query($sql);
	    $result = $query->row();
	    return $result->cantidad;
	}

	function get_copias_fallidas()
	{
	    $edicion = $this->load->database('edicion', TRUE);

	    $edicion->select('idprotocolo, serie, count(*) as cantidad');
	    $edicion->from('entrada');
	    $edicion->where('estado',63);
	    $edicion->group_by('idprotocolo');
	    $query = $edicion->get();
	    return $query->result_array();
	}

	function listadoImpactar()
	{
		$edicion = $this->load->database('edicion', TRUE);

		$sql = "SELECT E.idprotocolo, EM.serie as equipo_serie, E.serie as serie_entrada ,MUN.descrip as proyecto,COUNT(E.id) as total, SUM(IF(E.estado=26,1,0)) AS aprobadas, SUM(IF(E.estado=27,1,0)) AS descartes
		FROM entrada AS E
		LEFT JOIN protocolos_control as PC ON E.idprotocolo = PC.id
		LEFT JOIN equipos_main as EM ON EM.id = PC.idequipo
		LEFT JOIN municipios as MUN ON MUN.id = EM.municipio
		WHERE E.estado < 100
		GROUP BY E.idprotocolo
		HAVING SUM(IF(E.estado=60,1,0)) = 0 AND SUM(IF(E.estado=62,1,0)) = 0 AND SUM(IF(E.estado=63,1,0)) = 0 AND SUM(IF(E.estado=64,1,0)) = 0
		ORDER BY MUN.descrip ASC, EM.serie ASC";

		$query = $edicion->query($sql);
		return $query->result_array();
	}

	function listadoProtocolosAsignados_original()
	{
	    $edicion = $this->load->database('edicion', TRUE);

	    $edicion->select('count(EN.protocolo) as totalAsignadas,PA.protocolo, PA.editor_nombre, EN.serie, PA.editor, DATE_FORMAT(PA.fecha_asignacion, "%d/%m/%Y - %H:%i:%s") AS fecha_asignacion, EN.estado, M.descrip');
	    $edicion->from('protocolos_asignados as PA');
	    $edicion->join('entrada as EN', 'EN.protocolo = PA.protocolo','left');
	    $edicion->join('equipos_main as EM', 'EN.serie = EM.serie','left');
	    $edicion->join('municipios as M', 'M.id = EM.municipio','left');
	    $edicion->where('EN.estado',100);
		$edicion->or_where('EN.estado',110);
	    $edicion->group_by('PA.protocolo');
	    $edicion->order_by("PA.editor_nombre","ASC");
	    $edicion->order_by("PA.fecha_asignacion","ASC");

	    $query = $edicion->get();
	    return $query->result();
	}

/// ACA CARLOS
	function listadoProtocolosAsignados($estados)
	{
		$edicion = $this->load->database('edicion', TRUE);

	    $edicion->select('PA.protocolo, PA.editor_nombre, PA.editor, PA.fecha_asignacion, EM.serie, PC.registros as cantidad');
	    $edicion->from('protocolos_asignados as PA');
		$edicion->join('protocolos_control as PC', 'PC.id = PA.protocolo','left');
		$edicion->join('equipos_main as EM', 'EM.id = PC.idequipo','left');
	    $edicion->where_in('PC.estado', $estados);
	    $edicion->order_by('PA.editor_nombre', 'ASC');
	    $edicion->order_by('PA.fecha_asignacion', 'ASC');

	    $query = $edicion->get();
	    return $query->result();
	}

	function listadoProtocolosAsignados_original2()
	{
		$edicion = $this->load->database('edicion', TRUE);

		$sql = "SELECT PA.protocolo, PA.editor_nombre, PA.editor, PA.fecha_asignacion
		FROM protocolos_asignados as PA
		WHERE PA.protocolo IN(
		SELECT EN.idprotocolo FROM entrada as EN
		WHERE EN.estado = 100 OR EN.estado = 110
		GROUP BY EN.idprotocolo
		)
		GROUP BY PA.protocolo
		ORDER BY PA.editor_nombre ASC, PA.fecha_asignacion ASC
		";

		$query = $edicion->query($sql);
		return $query->result();
	}


	

	function add_edicion_asignacion($usuario,$id,$fecha_asignada,$asignado = 0)
  {
    $default = $this->load->database('default', TRUE);
    // Segundo, inserto en la tabla asignaciones a qué usuario está asignada
    $default->insert('edicion_asignaciones', array('idusuario' => $usuario, 'identrada' => $id, 'fecha' => $fecha_asignada, 'asignado' => $asignado));
  }

  function registrosProtocolosAsignados($protocolo)
	{
	    $edicion = $this->load->database('edicion', TRUE);

	    $edicion->select('EN.id');
	    $edicion->from('entrada as EN');
	    $edicion->where('EN.idprotocolo',$protocolo);
	    $edicion->order_by("EN.id","ASC");

	    $query = $edicion->get();
	    return $query->result();

	}

	function getMunicipios()
	{
	    $edicion = $this->load->database('edicion', TRUE);

	    $edicion->select('MU.id, MU.descrip');
	    $edicion->from('municipios as MU');

	    $query = $edicion->get();
	    return $query->result();
	}

	function verificacionEquipos($municipio = null,$tipoEquipo = null)
	{
	    $edicion = $this->load->database('edicion', TRUE);

	    $sql = "SELECT EN.protocolo, EN.falta as Fecha, EN.serie,
                COUNT(CASE WHEN EN.estado = 60 OR EN.estado = 61 OR EN.estado = 62 OR EN.estado = 63 OR EN.estado = 100  THEN 1 ELSE NULL END) AS 'otros',
                COUNT(CASE WHEN EN.estado = 64 THEN 1 ELSE NULL END) AS 'asignado',
                COUNT(CASE WHEN EN.estado = 110 THEN 1 ELSE NULL END) AS 'preseleccion',
                COUNT(CASE WHEN EN.estado = 26 THEN 1 ELSE NULL END) AS 'aprobadas26',
                COUNT(CASE WHEN EN.estado = 260 THEN 1 ELSE NULL END) AS 'aprobadas260',
                COUNT(CASE WHEN EN.estado = 27 THEN 1 ELSE NULL END) AS 'desaprobadas27',
                COUNT(CASE WHEN EN.estado = 270 THEN 1 ELSE NULL END) AS 'desaprobadas270'
                FROM entrada AS EN";

	    if($tipoEquipo){
	        foreach ($tipoEquipo as $equipo){
	            reset($tipoEquipo);
	            if ($equipo === key($tipoEquipo)) {
	                $sql .= " WHERE EN.serie LIKE '%$equipo%' AND";
	            }

	            end($tipoEquipo);
	            if ($equipo === key($tipoEquipo)) {
	                $sql .= " WHERE EN.serie LIKE '%$equipo%'";
	            }
	        }
	    }

	    $query = $edicion->query($sql);
	    return $query->result();

	}




	function resumenProduccion($fecha_1,$fecha_2)
	{	    
	    $edicion = $this->load->database('default', TRUE);
	    
	    $sql = "SELECT 
		SUM(IF(e.estado = 1,1,0)) AS  aprobadas,
		SUM(IF(e.estado = 0,1,0)) AS  descartes
		FROM edicion AS e   
		WHERE e.fecha_editado BETWEEN '$fecha_1 00:00:00' AND '$fecha_2 23:59:59'";

	    $query = $edicion->query($sql);
	    return $query->result();
		 
	}



	function protocolosEA()
	{
		$edicion = $this->load->database('edicion', TRUE);

	    $edicion->select('EA.serie, EA.idprotocolo, COUNT(EA.idprotocolo) as cantidad, EA.imagen1, EA.prioridad, MUN.descrip as proyecto');
	    $edicion->from('entrada_auxiliar as EA');
		$edicion->join('protocolos_control as PC', 'PC.id = EA.idprotocolo','left');
		$edicion->join('equipos_main as EM', 'EM.id = PC.idequipo','left');
		$edicion->join('municipios as MUN', 'MUN.id = EM.municipio','left');

	    $edicion->order_by('MUN.descrip', 'ASC');
		$edicion->order_by('EA.idprotocolo', 'ASC');
	    $edicion->group_by('EA.idprotocolo');

	    $query = $edicion->get();
	    return $query->result();
	}

	function registrosEA()
	{
		$edicion = $this->load->database('edicion', TRUE);

	    $edicion->select('COUNT(EA.id) as total');
	    $edicion->from('entrada_auxiliar as EA');

	    $query = $edicion->get();
      	return $query->row();
	}

	function controlar_protocolo($protocolo = NULL)
	{	    

		$edicion = $this->load->database('edicion', TRUE);
		$edicion->select('*');
		$edicion->from('protocolos_control');
		$edicion->where('id', $protocolo);
		$edicion->where('(estado', 10);
		$edicion->or_where('estado', 20);
		$query = $edicion->get();
		$ok = $query->num_rows();
		
		

		if($ok > 0){
			//PROTOCOLOS NORMAL
			$edicion2 = $this->load->database('edicion', TRUE);
			$edicion2->select('*');
			$edicion2->from('entrada');
			$edicion2->where('idprotocolo', $protocolo);
			$query2 = $edicion2->get();
			$ok2 = $query2->num_rows();
			


			if ($ok2>0) {
				//PROTOCOLOS EN ENTRADA 
				$this->session->set_flashdata('error', "EL PROTOCOLO: $protocolo.  SE ENCUENTRA EDITANDOSE.");
				$this->edicion_model->eliminarEA($protocolo);				
			}else {
				//PROTOCOLOS OK PARA ENVIAR A ENTRADA
				return $edicion->affected_rows();
			}
			
		
		}else{
			//PROTOCOLOS QUE NO SE ENCUENTRAN COPIADOS NORMAL
			$edicion2 = $this->load->database('edicion', TRUE);
			$edicion2->select('*');
			$edicion2->from('protocolos_control');
			$edicion2->where('id', $protocolo);
			$query2 = $edicion2->get();
			$ok2 = $query2->num_rows();			

			if($ok2>0){
				//PROTOCOLOS QUE YA FUERON INGRESADOS ANTERIORMENTE.
				$this->session->set_flashdata('error', "EL PROTOCOLO: $protocolo.  YA INGRESO");
				$this->edicion_model->eliminarEA($protocolo);				
				return $edicion->affected_rows();
				
			}else{
				//PROTOCOLOS QUE AUN NO SE TERMINANRON DE COPIAR.
				$this->session->set_flashdata('error', "EL PROTOCOLO: $protocolo. NO SE TERMINO DE TRANFERIR");
				return $edicion->affected_rows();
				
			}

		}
		
	}


	function copiar_entrada($protocolo = NULL)
	{	    
	    $edicion = $this->load->database('edicion', TRUE);
	    
	    $sql = "INSERT INTO entrada SELECT * FROM entrada_auxiliar WHERE idprotocolo = $protocolo";

	    $query = $edicion->query($sql);
		return $edicion->affected_rows();
	}

	function eliminarEA($protocolo)
    {
		$edicion = $this->load->database('edicion', TRUE);

		$edicion->where('idprotocolo', $protocolo);
		$edicion->delete('entrada_auxiliar');

      	return TRUE;
    }



}
