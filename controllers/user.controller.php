<?php
include_once("../configurations/db.php");
include_once("../models/user.class.php");

class UsuarioControlador {

    private $user;

    function __construct() {
        $this->user = new Usuario();
    }

    function insertar($nombre, $correo, $cedula, $telefono, $rol) {
        $this->user->insertarRegistro($nombre, $correo, $cedula, $telefono, $rol);
        header("Content-Type: application/json");
        echo json_encode(array("respuesta" => "ok"));
    }

    function actualizar($id, $nombre, $correo, $cedula, $telefono, $rol) {
        $this->user->actualizarRegistro($id, $nombre, $correo, $cedula, $telefono, $rol);
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

}

$objUsuarioControlador = new UsuarioControlador();
$method = $_SERVER['REQUEST_METHOD']; //ajax javascript


// FORMA TRADICIONAL PARA ENTENDER COMO FUNCIONA
switch ($method) {
    case 'GET':
        $objUsuarioControlador->obtenerTodos();
        break;
    case 'POST':
        $objUsuarioControlador->insertar($_POST['nombre'], $_POST['correo'], $_POST['cedula'], $_POST['telefono'], $_POST['rol']);
        break;
    case 'PUT':
        $data = file_get_contents("php://input"); // Lee todo el cuerpo de la solicitud
        $datos = json_decode($data, true); // Decodifica el JSON en un array asociativo
        $objUsuarioControlador->actualizar($datos['id'], $datos['nombre'], $datos['correo'], $datos['cedula'], $datos['telefono'], $datos['rol']);
        break;
    case 'DELETE':
        $data = file_get_contents("php://input"); // Lee todo el cuerpo de la solicitud
        $datos = json_decode($data, true); // Decodifica el JSON en un array asociativo
        $objUsuarioControlador->eliminarLogico($datos['id']);
        break;
    default:
        http_response_code(405); // Código HTTP correcto para "Método no permitido"
        echo "Método no permitido";
        break;
}

// // Unificación de datos
// $input = array_merge($_POST, json_decode(file_get_contents("php://input"), true) ?? []);

// switch limpio
// match ($method) {
//     'GET'    => $objUsuarioControlador->obtenerTodos(),
//     'POST'   => $objUsuarioControlador->insertar($input),       // ¡Pasa todo el array!
//     'PUT'    => $objUsuarioControlador->actualizar($input),     // ¡Mucho más limpio!
//     'DELETE' => $objUsuarioControlador->eliminarLogico($input['id']),
//     default  => http_response_code(405),
// };



?>