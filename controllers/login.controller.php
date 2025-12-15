<?php
session_start(); // Iniciar sesión para almacenar datos del usuario

include_once("../configurations/db.php");
include_once("../models/user.class.php");

// Verificar que se recibieron las credenciales
if (!isset($_POST['correo']) || !isset($_POST['password'])) {
    header("Content-Type: application/json");
    echo json_encode(array("respuesta" => "error", "mensaje" => "Faltan credenciales."));
    exit;
}

$correo = $_POST['correo'];
$password = $_POST['password'];

try {
    $usuarioModel = new Usuario();
    $usuario = $usuarioModel->obtenerPorCorreo($correo);

    if ($usuario && password_verify($password, $usuario['password'])) {
        // Credenciales correctas
        // Iniciar sesión: Almacenar datos relevantes en $_SESSION
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_nombre'] = $usuario['nombre'];
        $_SESSION['user_correo'] = $usuario['correo'];
        $_SESSION['user_rol'] = $usuario['rol'];
        // No almacenamos la contraseña en la sesión por seguridad

        // Devolver éxito y datos del usuario (sin la contraseña)
        $respuesta_usuario = array(
            'id' => $usuario['id'],
            'nombre' => $usuario['nombre'],
            'correo' => $usuario['correo'],
            'rol' => $usuario['rol']
        );

        header("Content-Type: application/json");
        echo json_encode(array("respuesta" => "ok", "usuario" => $respuesta_usuario));
    } else {
        // Credenciales incorrectas
        header("Content-Type: application/json");
        echo json_encode(array("respuesta" => "error", "mensaje" => "Correo o contraseña incorrectos."));
    }
} catch (Exception $e) {
    // Error general en el proceso
    header("Content-Type: application/json");
    echo json_encode(array("respuesta" => "error", "mensaje" => "Error interno del servidor."));
    error_log("Error en login_process.php: " . $e->getMessage()); // Registrar error
}

?>