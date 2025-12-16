<?php
// No se usa ob_start aquí para forzar que no haya salida no deseada desde el principio
// Manejador de errores para capturar warnings/notices como excepciones
function handlePhpError($errno, $errstr, $errfile, $errline)
{
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("handlePhpError", E_ALL & ~E_DEPRECATED & ~E_STRICT);

try {
    // Incluir configuraciones y modelo
    // IMPORTANTE: Verificar que las rutas sean correctas
    $bdPath = __DIR__ . '/../configurations/db.php'; // Asumiendo controllers/alojamiento.controller.php
    $modelPath = __DIR__ . '/../models/alojamiento.class.php';

    if (!file_exists($bdPath) || !file_exists($modelPath)) {
        throw new Exception("Archivo de configuración o modelo no encontrado. Rutas: $bdPath, $modelPath");
    }

    include_once $bdPath;
    include_once $modelPath;

    class AlojamientoControlador
    {
        private $alojamiento;

        function __construct()
        {
            $this->alojamiento = new Alojamiento();
        }

        private function enviarRespuestaJSON($data, $statusCode = 200)
        {
            http_response_code($statusCode);
            header("Content-Type: application/json; charset=utf-8"); // Asegurar charset
            // No se usa ob_clean ni echo, solo print
            print json_encode($data, JSON_THROW_ON_ERROR); // JSON_THROW_ON_ERROR para capturar errores de codificación
            exit();
        }

        public function manejarSolicitud()
        {
            $method = $_SERVER['REQUEST_METHOD'];

            switch ($method) {
                case 'GET':
                    $id = $_GET['id'] ?? null;
                    if ($id) {
                        $this->obtenerPorId($id);
                    } else {
                        $this->obtenerTodos();
                    }
                    break;
                case 'POST':
                    $this->insertar($_POST['nombre'] ?? null, $_POST['descripcion'] ?? null, $_POST['ubicacion'] ?? null, $_POST['precio_noche'] ?? null, $_POST['capacidad'] ?? null, $_POST['imagen'] ?? null, $_POST['estado'] ?? '1');
                    break;
                case 'PUT':
                    $this->procesarPUT();
                    break;
                case 'DELETE':
                    $this->procesarDELETE();
                    break;
                default:
                    $this->enviarRespuestaJSON(['respuesta' => 'error', 'mensaje' => 'Método no permitido'], 405);
            }
        }

        private function procesarPUT()
        {
            $rawInput = file_get_contents("php://input");
            if ($rawInput === false || trim($rawInput) === '') {
                $this->enviarRespuestaJSON(['respuesta' => 'error', 'mensaje' => 'Cuerpo de la solicitud PUT vacío.'], 400);
            }

            $datos = json_decode($rawInput, true);

            // Verificación robusta de json_decode
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->enviarRespuestaJSON(['respuesta' => 'error', 'mensaje' => 'JSON inválido recibido. Detalles: ' . json_last_error_msg()], 400);
            }

            // Verificar si $datos es un array
            if (!is_array($datos)) {
                $this->enviarRespuestaJSON(['respuesta' => 'error', 'mensaje' => 'El cuerpo de la solicitud no es un objeto JSON válido.'], 400);
            }

            // Verificar campos requeridos
            $camposRequeridos = ['id', 'nombre', 'descripcion', 'ubicacion', 'precio_noche', 'capacidad', 'imagen'];
            foreach ($camposRequeridos as $campo) {
                if (!isset($datos[$campo])) {
                    $this->enviarRespuestaJSON(['respuesta' => 'error', 'mensaje' => "Falta el campo requerido: $campo"], 400);
                }
            }

            // Extraer valores
            $id = $datos['id'];
            $nombre = $datos['nombre'];
            $descripcion = $datos['descripcion'];
            $ubicacion = $datos['ubicacion'];
            $precio_noche = $datos['precio_noche'];
            $capacidad = $datos['capacidad'];
            $imagen = filter_var($datos['imagen'], FILTER_SANITIZE_URL); // <-- Añadir esta línea
            error_log("Controlador recibiendo imagen: " . $imagen);
            if ($imagen === false || !filter_var($imagen, FILTER_VALIDATE_URL)) {
                // La URL no es válida después de sanitizarla
                $this->enviarRespuestaJSON(['respuesta' => 'error', 'mensaje' => "La URL de la imagen no es válida."], 400);
            }
            $estado = $datos['estado'] ?? null; // Puede ser nulo si no se envía

            $this->actualizar($id, $nombre, $descripcion, $ubicacion, $precio_noche, $capacidad, $imagen, $estado);
        }

        private function procesarDELETE()
        {
            $rawInput = file_get_contents("php://input");
            if ($rawInput === false || trim($rawInput) === '') {
                $this->enviarRespuestaJSON(['respuesta' => 'error', 'mensaje' => 'Cuerpo de la solicitud DELETE vacío.'], 400);
            }

            $datos = json_decode($rawInput, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->enviarRespuestaJSON(['respuesta' => 'error', 'mensaje' => 'JSON inválido recibido en DELETE. Detalles: ' . json_last_error_msg()], 400);
            }

            if (!is_array($datos) || !isset($datos['id'])) {
                $this->enviarRespuestaJSON(['respuesta' => 'error', 'mensaje' => 'Falta el campo "id" en la solicitud DELETE.'], 400);
            }

            $this->eliminarLogico($datos['id']);
        }

        // --- Métodos de negocio ---
        private function insertar($nombre, $descripcion, $ubicacion, $precio_noche, $capacidad, $imagen, $estado)
        {
            try {
                $this->alojamiento->insertarRegistro($nombre, $descripcion, $ubicacion, $precio_noche, $capacidad, $imagen, $estado);
                $this->enviarRespuestaJSON(['respuesta' => 'ok']);
            } catch (Exception $e) {
                error_log("Error insertar alojamiento: " . $e->getMessage());
                $this->enviarRespuestaJSON(['respuesta' => 'error', 'mensaje' => $e->getMessage()], 500);
            }
        }

        private function actualizar($id, $nombre, $descripcion, $ubicacion, $precio_noche, $capacidad, $imagen, $estado)
        {
            try {
                $this->alojamiento->actualizarRegistro($id, $nombre, $descripcion, $ubicacion, $precio_noche, $capacidad, $imagen, $estado);
                $this->enviarRespuestaJSON(['respuesta' => 'ok']);
            } catch (Exception $e) {
                error_log("Error actualizar alojamiento: " . $e->getMessage());
                $this->enviarRespuestaJSON(['respuesta' => 'error', 'mensaje' => $e->getMessage()], 500);
            }
        }

        private function eliminarLogico($id)
        {
            try {
                $this->alojamiento->eliminarLogicoRegistro($id);
                $this->enviarRespuestaJSON(['respuesta' => 'ok']);
            } catch (Exception $e) {
                error_log("Error eliminar alojamiento: " . $e->getMessage());
                $this->enviarRespuestaJSON(['respuesta' => 'error', 'mensaje' => $e->getMessage()], 500);
            }
        }

        private function obtenerTodos()
        {
            try {
                $datos = $this->alojamiento->obtenerRegistros();
                if ($datos === false) {
                    // Si obtenerRegistros devuelve false, es un error interno
                    $this->enviarRespuestaJSON(['respuesta' => 'error', 'mensaje' => 'Error interno al obtener alojamientos.'], 500);
                } else {
                    $this->enviarRespuestaJSON(['respuesta' => 'ok', 'datos' => $datos]);
                }
            } catch (Exception $e) {
                error_log("Error obtener todos alojamientos: " . $e->getMessage());
                $this->enviarRespuestaJSON(['respuesta' => 'error', 'mensaje' => $e->getMessage()], 500);
            }
        }

        private function obtenerPorId($id)
        {
            try {
                $datos = $this->alojamiento->obtenerPorId($id);
                if ($datos) {
                    $this->enviarRespuestaJSON(['respuesta' => 'ok', 'datos' => $datos]);
                } else {
                    $this->enviarRespuestaJSON(['respuesta' => 'error', 'mensaje' => 'Alojamiento no encontrado.'], 404);
                }
            } catch (Exception $e) {
                error_log("Error obtener alojamiento por ID: " . $e->getMessage());
                $this->enviarRespuestaJSON(['respuesta' => 'error', 'mensaje' => $e->getMessage()], 500);
            }
        }
    }

    $controlador = new AlojamientoControlador();
    $controlador->manejarSolicitud();
} catch (JsonException $e) {
    // Captura errores de json_encode si se usara JSON_THROW_ON_ERROR
    http_response_code(500);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(['respuesta' => 'error', 'mensaje' => 'Error interno al codificar JSON.']);
    error_log("Error JsonException en controlador: " . $e->getMessage());
    exit();
} catch (Exception $e) {
    // Captura errores generales (por ejemplo, include_once fallido)
    http_response_code(500);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(['respuesta' => 'error', 'mensaje' => 'Error interno del servidor. Detalles: ' . $e->getMessage()]);
    error_log("Error general en controlador: " . $e->getMessage());
    exit();
}
