<?php
// Iniciar buffer de salida para capturar cualquier salida no deseada
ob_start();

// Incluir configuraciones y modelo
include_once("../configurations/db.php");
include_once("../models/alojamiento.class.php");

class AlojamientoControlador {

    private $alojamiento;

    function __construct() {
        $this->alojamiento = new Alojamiento();
    }

    function insertar($nombre, $descripcion, $ubicacion, $precio_noche, $capacidad, $imagen, $estado) {
        try {
            $this->alojamiento->insertarRegistro($nombre, $descripcion, $ubicacion, $precio_noche, $capacidad, $imagen, $estado);
            $this->enviarRespuestaJSON(array("respuesta" => "ok"));
        } catch (Exception $e) {
            $this->enviarRespuestaJSON(array("respuesta" => "error", "mensaje" => $e->getMessage()), 500);
            error_log("Error en AlojamientoControlador::insertar: " . $e->getMessage());
        }
    }

    function actualizar($id, $nombre, $descripcion, $ubicacion, $precio_noche, $capacidad, $imagen, $estado) {
        try {
            $this->alojamiento->actualizarRegistro($id, $nombre, $descripcion, $ubicacion, $precio_noche, $capacidad, $imagen, $estado);
            $this->enviarRespuestaJSON(array("respuesta" => "ok"));
        } catch (Exception $e) {
            $this->enviarRespuestaJSON(array("respuesta" => "error", "mensaje" => $e->getMessage()), 500);
            error_log("Error en AlojamientoControlador::actualizar: " . $e->getMessage());
        }
    }

    function eliminarLogico($id) {
        try {
            $this->alojamiento->eliminarLogicoRegistro($id);
            $this->enviarRespuestaJSON(array("respuesta" => "ok"));
        } catch (Exception $e) {
            $this->enviarRespuestaJSON(array("respuesta" => "error", "mensaje" => $e->getMessage()), 500);
            error_log("Error en AlojamientoControlador::eliminarLogico: " . $e->getMessage());
        }
    }

    function obtenerTodos() {
        try {
            $datos = $this->alojamiento->obtenerRegistros();
            // Asegurarse de que la obtención no devolvió false o null por error
            if ($datos !== false && is_array($datos)) {
                 $this->enviarRespuestaJSON(array("respuesta" => "ok", "datos" => $datos));
            } else if ($datos === false) {
                 // El modelo devolvió false, lo que indica un error interno
                 $this->enviarRespuestaJSON(array("respuesta" => "error", "mensaje" => "Error interno al obtener los alojamientos."), 500);
            } else {
                 // El modelo devolvió algo que no es un array ni false, pero no debería pasar
                 $this->enviarRespuestaJSON(array("respuesta" => "ok", "datos" => [])); // Devuelve array vacío
            }
        } catch (Exception $e) {
            $this->enviarRespuestaJSON(array("respuesta" => "error", "mensaje" => $e->getMessage()), 500);
            error_log("Error en AlojamientoControlador::obtenerTodos: " . $e->getMessage());
        }
    }

    function obtenerPorId($id) {
        try {
            $datos = $this->alojamiento->obtenerPorId($id);
            if ($datos) {
                 $this->enviarRespuestaJSON(array("respuesta" => "ok", "datos" => $datos));
            } else {
                 $this->enviarRespuestaJSON(array("respuesta" => "error", "mensaje" => "Alojamiento no encontrado."), 404);
            }
        } catch (Exception $e) {
            $this->enviarRespuestaJSON(array("respuesta" => "error", "mensaje" => $e->getMessage()), 500);
            error_log("Error en AlojamientoControlador::obtenerPorId: " . $e->getMessage());
        }
    }

    // Método centralizado para enviar respuesta JSON
    private function enviarRespuestaJSON($data, $statusCode = 200) {
        http_response_code($statusCode);
        header("Content-Type: application/json");
        // Limpiar el buffer por si acaso hubo alguna salida previa no deseada
        ob_clean();
        // Enviar el JSON
        echo json_encode($data);
        // Finalizar la ejecución del script después de enviar la respuesta
        exit();
    }
}

$objAlojamientoControlador = new AlojamientoControlador();
$method = $_SERVER['REQUEST_METHOD']; //ajax javascript

// Obtener el ID si está presente en la URL (para GET específico)
$idGet = isset($_GET['id']) ? intval($_GET['id']) : null;

switch ($method) {
    case 'GET':
        if ($idGet !== null) {
            $objAlojamientoControlador->obtenerPorId($idGet);
        } else {
            $objAlojamientoControlador->obtenerTodos();
        }
        break;
    case 'POST':
        $objAlojamientoControlador->insertar($_POST['nombre'], $_POST['descripcion'], $_POST['ubicacion'], $_POST['precio_noche'], $_POST['capacidad'], $_POST['imagen'], $_POST['estado']);
        break;
    case 'PUT':
        $data = file_get_contents("php://input"); // Lee todo el cuerpo de la solicitud
        $datos = json_decode($data, true); // Decodifica el JSON en un array asociativo
        $objAlojamientoControlador->actualizar($datos['id'], $datos['nombre'], $datos['descripcion'], $datos['ubicacion'], $datos['precio_noche'], $datos['capacidad'], $datos['imagen'], $datos['estado']);
        break;
    case 'DELETE':
        $data = file_get_contents("php://input"); // Lee todo el cuerpo de la solicitud
        $datos = json_decode($data, true); // Decodifica el JSON en un array asociativo
        $objAlojamientoControlador->eliminarLogico($datos['id']);
        break;
    default:
        http_response_code(405); // Código HTTP correcto para "Método no permitido"
        header("Content-Type: application/json");
        echo json_encode(array("respuesta" => "error", "mensaje" => "Método no permitido"));
        break;
}

// Limpiar el buffer al final (por si acaso algo se queda colgado)
ob_end_flush();

?>