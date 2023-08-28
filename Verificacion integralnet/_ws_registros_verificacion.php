<?php
$mysqli = new mysqli("localhost", "integral", "PUrrotYu6d9eP7SjC60j", "cecaitra_edicion");

if ($mysqli->connect_errno) {
 echo "Lo sentimos, SSTI está experimentando problemas de conexión.";
 exit;
}   
    
if (!$_GET["p"]) {
  echo "Lo sentimos, SSTI está experimentando problemas de consultas.";
  exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 'on');

$sentencia = $mysqli->prepare("SELECT EN.imagen1, EN.imagen2, EN.imagen3, EN.imagen4, EN.imagen_zoom, EN.id as identrada, EN.estado, EN.serie, EN.idprotocolo FROM entrada_impactados AS EN WHERE EN.estado = 260 AND EN.idprotocolo = ?");
$sentencia->bind_param('s', $protocolo);

// Establecer parámetros y ejecutar
$protocolo = $_GET["p"];
$sentencia->execute();

$resultado = $sentencia->get_result();

while($row = $resultado->fetch_object() ) {
  $row->estado = 26;
  $imagenes[] = $row;
}

echo json_encode($imagenes);
exit();

?>
