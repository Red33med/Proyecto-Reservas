<?php

class Reserva {
    private $id;
    private $usuario_id;
    private $espacio_id;
    private $fecha_inicio;
    private $fecha_fin;
    private $total;
    private $codigo_qr;
    private $estado;
    private $nombreTabla = "reservas";
    private $conexion;

    function __construct() {
        global $host, $db_name, $user, $pass, $port;
        try {
            $this->conexion = new mysqli($host, $user, $pass, $db_name, $port);
            $this->conexion->set_charset("utf8mb4");
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }

    function insertarReserva($usuario_id, $espacio_id, $fecha_inicio, $fecha_fin, $total) {
        try {
            // Generar código QR único
            $codigo_qr = $this->generarCodigoQR($usuario_id, $espacio_id, $fecha_inicio);
            
            $stmt = $this->conexion->prepare("INSERT INTO $this->nombreTabla (usuario_id, espacio_id, fecha_inicio, fecha_fin, total, codigo_qr, estado) VALUES (?, ?, ?, ?, ?, ?, 'pendiente')");
            $stmt->bind_param('iissds', $usuario_id, $espacio_id, $fecha_inicio, $fecha_fin, $total, $codigo_qr);
            $stmt->execute();
            $id = $this->conexion->insert_id;
            $stmt->close();
            return $id;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    function listarPorUsuario($usuario_id) {
        try {
            $stmt = $this->conexion->prepare("
                SELECT r.*, e.nombre as espacio_nombre, e.descripcion as espacio_descripcion, 
                       e.ubicacion, e.imagen, e.precio_noche
                FROM $this->nombreTabla r
                INNER JOIN espacios e ON r.espacio_id = e.id
                WHERE r.usuario_id = ?
                ORDER BY r.fecha_inicio DESC
            ");
            $stmt->bind_param('i', $usuario_id);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $reservas = $resultado->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $reservas;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    function listarTodas() {
        try {
            $stmt = $this->conexion->prepare("
                SELECT r.*, e.nombre as espacio_nombre, u.nombre as usuario_nombre, u.correo as usuario_correo
                FROM $this->nombreTabla r
                INNER JOIN espacios e ON r.espacio_id = e.id
                INNER JOIN usuarios u ON r.usuario_id = u.id
                ORDER BY r.fecha_inicio DESC
            ");
            $stmt->execute();
            $resultado = $stmt->get_result();
            $reservas = $resultado->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $reservas;
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return [];
        }
    }

    function obtenerPorId($id) {
        try {
            $stmt = $this->conexion->prepare("
                SELECT r.*, e.nombre as espacio_nombre, e.descripcion as espacio_descripcion, 
                       e.ubicacion, e.imagen, e.precio_noche
                FROM $this->nombreTabla r
                INNER JOIN espacios e ON r.espacio_id = e.id
                WHERE r.id = ?
            ");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $reserva = $resultado->fetch_assoc();
            $stmt->close();
            return $reserva;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    function actualizarEstado($id, $estado) {
        try {
            $stmt = $this->conexion->prepare("UPDATE $this->nombreTabla SET estado = ? WHERE id = ?");
            $stmt->bind_param('si', $estado, $id);
            $stmt->execute();
            $stmt->close();
            return true;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    function cancelarReserva($id, $usuario_id) {
        try {
            $stmt = $this->conexion->prepare("UPDATE $this->nombreTabla SET estado = 'cancelada' WHERE id = ? AND usuario_id = ?");
            $stmt->bind_param('ii', $id, $usuario_id);
            $stmt->execute();
            $affected = $this->conexion->affected_rows;
            $stmt->close();
            return $affected > 0;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    function obtenerReservasPorEspacio($espacio_id, $fecha_inicio = null, $fecha_fin = null) {
        try {
            $sql = "SELECT * FROM $this->nombreTabla WHERE espacio_id = ? AND estado != 'cancelada'";
            
            if ($fecha_inicio && $fecha_fin) {
                $sql .= " AND ((fecha_inicio <= ? AND fecha_fin >= ?) OR (fecha_inicio >= ? AND fecha_fin <= ?))";
            }
            
            $sql .= " ORDER BY fecha_inicio ASC";
            
            $stmt = $this->conexion->prepare($sql);
            
            if ($fecha_inicio && $fecha_fin) {
                $stmt->bind_param('issss', $espacio_id, $fecha_fin, $fecha_inicio, $fecha_inicio, $fecha_fin);
            } else {
                $stmt->bind_param('i', $espacio_id);
            }
            
            $stmt->execute();
            $resultado = $stmt->get_result();
            $reservas = $resultado->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $reservas;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    private function generarCodigoQR($usuario_id, $espacio_id, $fecha_inicio) {
        // Genera un código único basado en los datos de la reserva
        $data = $usuario_id . '-' . $espacio_id . '-' . $fecha_inicio . '-' . time();
        return 'QR-' . strtoupper(substr(md5($data), 0, 12));
    }

    function __destruct() {
        if ($this->conexion) {
            $this->conexion->close();
        }
    }
}

?>
