<?php

include_once("../configurations/db.php"); // Ajusta la ruta si es necesario

class Alojamiento {
    private $conexion;
    private $nombreTabla = "espacios";

    function __construct() {
        global $host, $db_name, $user, $pass, $port;
        try {
            $this->conexion = new mysqli($host, $user, $pass, $db_name, $port);
            if ($this->conexion->connect_error) {
                throw new Exception("Error de conexión: " . $this->conexion->connect_error);
            }
            $this->conexion->set_charset("utf8");
        } catch (Exception $e) {
            error_log("Error conexión Alojamiento: " . $e->getMessage());
            throw $e; // Relanzar para que el controlador lo maneje
        }
    }

    function insertarRegistro($nombre, $descripcion, $ubicacion, $precio_noche, $capacidad, $imagen, $estado) {
        $consulta = "INSERT INTO $this->nombreTabla (nombre, descripcion, ubicacion, precio_noche, capacidad, imagen, estado) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($consulta);
        if (!$stmt) {
            throw new Exception("Error prepare insert: " . $this->conexion->error);
        }
        $stmt->bind_param("sssdiss", $nombre, $descripcion, $ubicacion, $precio_noche, $capacidad, $imagen, $estado);
        if (!$stmt->execute()) {
            throw new Exception("Error execute insert: " . $stmt->error);
        }
        $stmt->close();
    }

    function actualizarRegistro($id, $nombre, $descripcion, $ubicacion, $precio_noche, $capacidad, $imagen, $estado = null) {
        error_log("Modelo recibiendo imagen para ID $id: " . $imagen); // <-- Línea de depuración opcional
        if ($estado !== null) {
             $consulta = "UPDATE $this->nombreTabla SET nombre = ?, descripcion = ?, ubicacion = ?, precio_noche = ?, capacidad = ?, imagen = ?, estado = ? WHERE id = ?";
             $stmt = $this->conexion->prepare($consulta);
             if (!$stmt) {
                 throw new Exception("Error prepare update (con estado): " . $this->conexion->error);
             }
             $stmt->bind_param("sssdissi", $nombre, $descripcion, $ubicacion, $precio_noche, $capacidad, $imagen, $estado, $id);
        } else {
             $consulta = "UPDATE $this->nombreTabla SET nombre = ?, descripcion = ?, ubicacion = ?, precio_noche = ?, capacidad = ?, imagen = ? WHERE id = ?";
             $stmt = $this->conexion->prepare($consulta);
             if (!$stmt) {
                 throw new Exception("Error prepare update (sin estado): " . $this->conexion->error);
             }
             // CORRECCIÓN: Cambiar la cadena de tipos de "sssdiii" a "sssdiii" para 7 parámetros
             // La cadena "sssdiii" tiene 7 caracteres: s, s, s, d, i, i, i -> nombre, desc, ubic, prec, cap, img, id
             // Pero la consulta tiene 6 '?' en SET y 1 en WHERE, lo que da 7 en total. Eso está bien.
             // Los tipos s, s, s, d, i, s, i -> s(nombre), s(desc), s(ubic), d(prec), i(cap), s(img), i(id) -> "sssdisi"
             // ERROR EN ESTA LÍNEA ANTES: "sssdiii" era incorrecto, debería ser "sssdisi" si imagen es string (s) y id es integer (i)
             // Asumiendo 'imagen' es VARCHAR/TEXT -> tipo 's'
             // Asumiendo 'id' es INT -> tipo 'i'
             // Asumiendo 'capacidad' es INT -> tipo 'i'
             // Asumiendo 'precio_noche' es DECIMAL/DOUBLE -> tipo 'd'
             // Asumiendo 'ubicacion', 'descripcion', 'nombre' son VARCHAR/TEXT -> tipo 's'
             // SET nombre=?, descripcion=?, ubicacion=?, precio_noche=?, capacidad=?, imagen=? WHERE id=?
             // Tipos: s(nombre), s(desc), s(ubic), d(prec), i(cap), s(img), i(id) -> "sssdisi"
             $stmt->bind_param("sssdisi", $nombre, $descripcion, $ubicacion, $precio_noche, $capacidad, $imagen, $id);
        }

        if (!$stmt->execute()) {
            throw new Exception("Error execute update: " . $stmt->error);
        }
        $stmt->close();
    }

    function eliminarLogicoRegistro($id) {
        $consulta = "UPDATE $this->nombreTabla SET estado = '0' WHERE id = ?";
        $stmt = $this->conexion->prepare($consulta);
        if (!$stmt) {
            throw new Exception("Error prepare delete logico: " . $this->conexion->error);
        }
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new Exception("Error execute delete logico: " . $stmt->error);
        }
        $stmt->close();
    }

    function obtenerRegistros() {
        $respuesta = [];
        $consulta = "SELECT * FROM $this->nombreTabla WHERE estado = '1'";
        $resultado = $this->conexion->query($consulta);
        if ($resultado === false) {
             error_log("Error en obtenerRegistros: " . $this->conexion->error);
             return false; // Devuelve false para indicar error
        }
        if ($resultado->num_rows > 0) {
            while ($fila = $resultado->fetch_assoc()) {
                $respuesta[] = $fila;
            }
        }
        return $respuesta;
    }

    function obtenerPorId($id) {
        $stmt = $this->conexion->prepare("SELECT * FROM $this->nombreTabla WHERE id = ? AND estado = '1' LIMIT 1");
        if (!$stmt) {
            throw new Exception("Error prepare obtenerPorId: " . $this->conexion->error);
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        if ($resultado->num_rows > 0) {
            return $resultado->fetch_assoc();
        } else {
            return false;
        }
    }

    function __destruct() {
        if ($this->conexion) {
            $this->conexion->close();
        }
    }
}
?>