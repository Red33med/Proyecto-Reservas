<?php
include_once("../configurations/db.php");
include_once("../models/espacio.class.php");
include_once("../models/reserva.class.php");

class ReservaControlador {

    private $espacio;
    private $reserva;

    function __construct() {
        try {
            $this->espacio = new Espacio();
            $this->reserva = new Reserva();
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(array('respuesta' => 'error', 'mensaje' => 'Error de conexión a la base de datos'));
            exit;
        }
    }

    private function requireLogin() {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['user'])) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(array('respuesta' => 'error', 'mensaje' => 'Debe iniciar sesión'));
            exit;
        }
    }

    function listarEspacios() {
        header("Content-Type: application/json");
        try {
            $espacios = $this->espacio->listarTodos();
            echo json_encode(array("respuesta" => "ok", "espacios" => $espacios));
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(array("respuesta" => "error", "mensaje" => "No se pudieron listar los espacios"));
        }
    }

    function obtenerEspacio($id) {
        header("Content-Type: application/json");
        $espacio = $this->espacio->obtenerPorId($id);
        if ($espacio) {
            echo json_encode(array("respuesta" => "ok", "espacio" => $espacio));
        } else {
            http_response_code(404);
            echo json_encode(array("respuesta" => "error", "mensaje" => "Espacio no encontrado"));
        }
    }

    function verificarDisponibilidad($espacio_id, $fecha_inicio, $fecha_fin) {
        header("Content-Type: application/json");
        $espacio_data = $this->espacio->obtenerPorId($espacio_id);
        if (!$espacio_data || $espacio_data['estado'] !== '1') {
            http_response_code(404);
            echo json_encode(array("respuesta" => "error", "mensaje" => "Espacio no disponible"));
            return;
        }
        
        // Validar fechas
        $inicio = new DateTime($fecha_inicio);
        $fin = new DateTime($fecha_fin);
        $hoy = new DateTime();
        $hoy->setTime(0, 0, 0);

        if ($inicio < $hoy) {
            http_response_code(400);
            echo json_encode(array("respuesta" => "error", "mensaje" => "La fecha de inicio no puede ser anterior a hoy"));
            return;
        }

        if ($fin <= $inicio) {
            http_response_code(400);
            echo json_encode(array("respuesta" => "error", "mensaje" => "La fecha de fin debe ser posterior a la fecha de inicio"));
            return;
        }

        $disponible = $this->espacio->verificarDisponibilidad($espacio_id, $fecha_inicio, $fecha_fin);
        
        if ($disponible) {
            // Calcular el precio total
            $dias = $inicio->diff($fin)->days;
            $total = $dias * $espacio_data['precio_noche'];
            
            echo json_encode(array(
                "respuesta" => "ok", 
                "disponible" => true,
                "dias" => $dias,
                "precio_noche" => $espacio_data['precio_noche'],
                "total" => $total
            ));
        } else {
            echo json_encode(array("respuesta" => "ok", "disponible" => false));
        }
    }

    function crearReserva($espacio_id, $fecha_inicio, $fecha_fin) {
        $this->requireLogin();
        header("Content-Type: application/json");
        
        $usuario_id = $_SESSION['user']['id'];
        $espacio_data = $this->espacio->obtenerPorId($espacio_id);
        if (!$espacio_data || $espacio_data['estado'] !== '1') {
            http_response_code(404);
            echo json_encode(array("respuesta" => "error", "mensaje" => "Espacio no disponible"));
            return;
        }

        // Validar fechas
        $inicio = new DateTime($fecha_inicio);
        $fin = new DateTime($fecha_fin);
        $hoy = new DateTime();
        $hoy->setTime(0, 0, 0);

        if ($inicio < $hoy) {
            http_response_code(400);
            echo json_encode(array("respuesta" => "error", "mensaje" => "La fecha de inicio no puede ser anterior a hoy"));
            return;
        }

        if ($fin <= $inicio) {
            http_response_code(400);
            echo json_encode(array("respuesta" => "error", "mensaje" => "La fecha de fin debe ser posterior a la fecha de inicio"));
            return;
        }

        // Verificar disponibilidad
        $disponible = $this->espacio->verificarDisponibilidad($espacio_id, $fecha_inicio, $fecha_fin);
        
        if (!$disponible) {
            http_response_code(400);
            echo json_encode(array("respuesta" => "error", "mensaje" => "El espacio no está disponible en las fechas seleccionadas"));
            return;
        }

        // Calcular total
        $dias = $inicio->diff($fin)->days;
        $total = $dias * $espacio_data['precio_noche'];

        // Crear la reserva
        $reserva_id = $this->reserva->insertarReserva($usuario_id, $espacio_id, $fecha_inicio, $fecha_fin, $total);
        
        if ($reserva_id) {
            echo json_encode(array("respuesta" => "ok", "reserva_id" => $reserva_id, "mensaje" => "Reserva creada exitosamente"));
        } else {
            http_response_code(500);
            echo json_encode(array("respuesta" => "error", "mensaje" => "Error al crear la reserva"));
        }
    }

    function listarMisReservas() {
        $this->requireLogin();
        header("Content-Type: application/json");
        
        $usuario_id = $_SESSION['user']['id'];
        $reservas = $this->reserva->listarPorUsuario($usuario_id);
        echo json_encode(array("respuesta" => "ok", "reservas" => $reservas));
    }

    function listarTodasReservas() {
        $this->requireLogin();
        header("Content-Type: application/json");
        
        // Solo admin puede ver todas las reservas
        if ($_SESSION['user']['rol'] !== 'ADMIN') {
            http_response_code(403);
            echo json_encode(array('respuesta' => 'error', 'mensaje' => 'Acceso denegado'));
            return;
        }
        
        $reservas = $this->reserva->listarTodas();
        echo json_encode(array("respuesta" => "ok", "reservas" => $reservas));
    }

    function cancelarReserva($id) {
        $this->requireLogin();
        header("Content-Type: application/json");
        
        $usuario_id = $_SESSION['user']['id'];
        $resultado = $this->reserva->cancelarReserva($id, $usuario_id);
        
        if ($resultado) {
            echo json_encode(array("respuesta" => "ok", "mensaje" => "Reserva cancelada exitosamente"));
        } else {
            http_response_code(400);
            echo json_encode(array("respuesta" => "error", "mensaje" => "No se pudo cancelar la reserva"));
        }
    }

    function obtenerReservasPorEspacio($espacio_id, $mes = null, $anio = null) {
        header("Content-Type: application/json");
        
        $fecha_inicio = null;
        $fecha_fin = null;
        
        if ($mes && $anio) {
            $fecha_inicio = "$anio-$mes-01";
            $ultimo_dia = date("t", strtotime($fecha_inicio));
            $fecha_fin = "$anio-$mes-$ultimo_dia";
        }
        
        $reservas = $this->reserva->obtenerReservasPorEspacio($espacio_id, $fecha_inicio, $fecha_fin);
        echo json_encode(array("respuesta" => "ok", "reservas" => $reservas));
    }
}

// Procesar peticiones
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $controller = new ReservaControlador();
    $accion = isset($_REQUEST['accion']) ? $_REQUEST['accion'] : '';

    switch ($accion) {
        case 'listarEspacios':
            $controller->listarEspacios();
            break;
        
        case 'obtenerEspacio':
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $controller->obtenerEspacio($id);
            break;
        
        case 'verificarDisponibilidad':
            $espacio_id = isset($_REQUEST['espacio_id']) ? intval($_REQUEST['espacio_id']) : 0;
            $fecha_inicio = isset($_REQUEST['fecha_inicio']) ? $_REQUEST['fecha_inicio'] : '';
            $fecha_fin = isset($_REQUEST['fecha_fin']) ? $_REQUEST['fecha_fin'] : '';
            $controller->verificarDisponibilidad($espacio_id, $fecha_inicio, $fecha_fin);
            break;
        
        case 'crearReserva':
            $espacio_id = isset($_REQUEST['espacio_id']) ? intval($_REQUEST['espacio_id']) : 0;
            $fecha_inicio = isset($_REQUEST['fecha_inicio']) ? $_REQUEST['fecha_inicio'] : '';
            $fecha_fin = isset($_REQUEST['fecha_fin']) ? $_REQUEST['fecha_fin'] : '';
            $controller->crearReserva($espacio_id, $fecha_inicio, $fecha_fin);
            break;
        
        case 'listarMisReservas':
            $controller->listarMisReservas();
            break;
        
        case 'listarTodasReservas':
            $controller->listarTodasReservas();
            break;
        
        case 'cancelarReserva':
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $controller->cancelarReserva($id);
            break;
        
        case 'obtenerReservasPorEspacio':
            $espacio_id = isset($_REQUEST['espacio_id']) ? intval($_REQUEST['espacio_id']) : 0;
            $mes = isset($_REQUEST['mes']) ? $_REQUEST['mes'] : null;
            $anio = isset($_REQUEST['anio']) ? $_REQUEST['anio'] : null;
            $controller->obtenerReservasPorEspacio($espacio_id, $mes, $anio);
            break;
        
        default:
            header("Content-Type: application/json");
            http_response_code(400);
            echo json_encode(array("respuesta" => "error", "mensaje" => "Acción no válida"));
            break;
    }
}
?>
