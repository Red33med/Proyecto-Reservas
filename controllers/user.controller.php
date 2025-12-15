<?php
include_once("../configurations/db.php");
include_once("../models/user.class.php");

class UsuarioControlador {

    private $user;

    function __construct() {
        $this->user = new Usuario();
    }

    function insertar($nombre, $correo, $password, $cedula, $telefono, $rol) {
        $this->user->insertarRegistro($nombre, $correo, $password, $cedula, $telefono, $rol);
        header("Content-Type: application/json");
        echo json_encode(array("respuesta" => "ok"));
    }

    function actualizar($id, $nombre, $correo, $password, $cedula, $telefono, $rol) {
        $this->user->actualizarRegistro($id, $nombre, $correo, $password, $cedula, $telefono, $rol);
        header("Content-Type: application/json");
        echo json_encode(array("respuesta" => "ok"));
    }

    // SOLO SE USA UN METODO DE ELIMINACION
    // function eliminar($id) {
    //     $this->user->eliminarRegistro($id);
    //     header("content-type: application/json");
    //     echo json_encode(array("respuesta" => "ok"));
    // }

    function eliminarLogico($id) {
        $this->user->eliminarLogicoRegistro($id);
        header("Content-Type: application/json");
        echo json_encode(array("respuesta" => "ok"));
    }

    function obtenerTodos() {
        header("Content-Type: application/json");
        echo json_encode(array("respuesta" => "ok", "datos" => $this->user->obtenerRegistros()));
    }

    function obtenerPorCorreo($correo) {
        try {
            header("Content-Type: application/json");
            $datos = $this->user->obtenerPorCorreo($correo);
            if ($datos) {
                 echo json_encode(array("respuesta" => "ok", "datos" => $datos));
            } else {
                 echo json_encode(array("respuesta" => "error", "mensaje" => "Usuario no encontrado o inactivo."));
            }
        } catch (Exception $e) {
            header("Content-Type: application/json");
            echo json_encode(array("respuesta" => "error", "mensaje" => $e->getMessage()));
            error_log("Error en UsuarioControlador::obtenerPorCorreo: " . $e->getMessage());
        }
    }


    function obtenerUn($id) {
        header("Content-Type: application/json");
        echo json_encode(array("respuesta" => "ok", "datos" => $this->user->obtenerUnRegistro($id)));
    }

}

$objUsuarioControlador = new UsuarioControlador();
$method = $_SERVER['REQUEST_METHOD']; //ajax javascript


// 1. LEEMOS EL JSON QUE ENVÍA JS (Sirve para POST, PUT y DELETE)
$json = file_get_contents("php://input");
$datos = json_decode($json, true);

// 2. Parche de seguridad: Si no es JSON, intentamos leer formulario normal
if (empty($datos)) {
    $datos = $_POST;
}

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $objUsuarioControlador->obtenerUn($_GET['id']);
        } else {
            $objUsuarioControlador->obtenerTodos();
        }
        break;

    case 'POST':
        // CORRECCIÓN: Usamos $datos[...] en lugar de $_POST[...]
        // Usamos '?? null' para evitar warnings si falta algún dato
        $objUsuarioControlador->insertar(
            $datos['nombre'] ?? null, 
            $datos['correo'] ?? null, 
            $datos['password'] ?? null, 
            $datos['cedula'] ?? null, 
            $datos['telefono'] ?? null, 
            $datos['rol'] ?? 'USUARIO'
        );
        break;

    case 'PUT':
        // Aquí password lleva '??' comillas vacías por si no la cambian
        $objUsuarioControlador->actualizar(
            $datos['id'] ?? null, 
            $datos['nombre'] ?? null, 
            $datos['correo'] ?? null, 
            $datos['password'] ?? '', 
            $datos['cedula'] ?? null, 
            $datos['telefono'] ?? null, 
            $datos['rol'] ?? 'USUARIO'
        );
        break;

    case 'DELETE':
        $objUsuarioControlador->eliminarLogico($datos['id'] ?? 0);
        break;

    default:
        http_response_code(405);
        echo json_encode(array("respuesta" => "error", "mensaje" => "Metodo no permitido"));
        break;
}
// // FORMA TRADICIONAL PARA ENTENDER COMO FUNCIONA
// switch ($method) {
//     case 'GET':
//         if (isset($_GET['id'])) {
//             $objUsuarioControlador->obtenerUn($_GET['id']);
//         } else {
//             $objUsuarioControlador->obtenerTodos();
//         }
//         break;
//     case 'POST':
//         $objUsuarioControlador->insertar($_POST['nombre'], $_POST['correo'], $_POST['password'], $_POST['cedula'], $_POST['telefono'], $_POST['rol']);
//         break;
//     case 'PUT':
//         $data = file_get_contents("php://input"); // Lee todo el cuerpo de la solicitud
//         $datos = json_decode($data, true); // Decodifica el JSON en un array asociativo
//         $objUsuarioControlador->actualizar($datos['id'], $datos['nombre'], $datos['correo'], $datos['password'], $datos['cedula'], $datos['telefono'], $datos['rol']);
//         break;
//     case 'DELETE':
//         $data = file_get_contents("php://input"); // Lee todo el cuerpo de la solicitud
//         $datos = json_decode($data, true); // Decodifica el JSON en un array asociativo
//         $objUsuarioControlador->eliminarLogico($datos['id']);
//         break;
//     default:
//         http_response_code(405); // Código HTTP correcto para "Método no permitido"
//         echo "Método no permitido";
//         break;
// }

?>