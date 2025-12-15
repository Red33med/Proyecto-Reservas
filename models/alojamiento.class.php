<?php

// Asegúrate de que este archivo incluya db.php
include_once("../configurations/db.php"); // Ajusta la ruta según tu estructura

class Alojamiento {
    private $id;
    private $nombre;
    private $descripcion;
    private $ubicacion;
    private $precio_noche;
    private $capacidad;
    private $imagen;
    private $estado; // Aunque la columna en BD es ENUM, la variable interna puede ser int/string
    private $nombreTabla = "espacios"; // Cambiado a "espacios" para coincidir con tu base de datos
    private $conexion;

    function __construct() {
        global $host, $db_name, $user, $pass, $port;
        try {
            $this->conexion = new mysqli($host, $user, $pass, $db_name, $port);
            if ($this->conexion->connect_error) {
                throw new Exception("Error de conexión a la base de datos: " . $this->conexion->connect_error);
            }
            // Establecer el charset para evitar problemas de codificación
            $this->conexion->set_charset("utf8");
        }
        catch (Exception $e) {
            // Manejar error de conexión de forma adecuada
            error_log("Error conexión Alojamiento: " . $e->getMessage());
            die("Error fatal de conexión a la base de datos."); // Opcional: detener la ejecución si no se puede conectar
        }
    }

    function insertarRegistro($nombre, $descripcion, $ubicacion, $precio_noche, $capacidad, $imagen, $estado) {
        try {
            // CORRECCIÓN: Usar sentencias preparadas para evitar inyección SQL
            $consulta = "INSERT INTO $this->nombreTabla (nombre, descripcion, ubicacion, precio_noche, capacidad, imagen, estado) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($consulta);

            if (!$stmt) {
                throw new Exception("Error en la preparación de la consulta: " . $this->conexion->error);
            }

            $stmt->bind_param("sssdiss", $nombre, $descripcion, $ubicacion, $precio_noche, $capacidad, $imagen, $estado);

            if (!$stmt->execute()) {
                throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
            }

            $stmt->close();

        } catch (Exception $e) {
            // Lanzar la excepción para que el controlador la maneje
            throw $e;
        }
    }


    function actualizarRegistro($id, $nombre, $descripcion, $ubicacion, $precio_noche, $capacidad, $imagen, $estado) {
        try {
            // CORRECCIÓN: Usar sentencias preparadas para evitar inyección SQL
            $consulta = "UPDATE $this->nombreTabla SET nombre = ?, descripcion = ?, ubicacion = ?, precio_noche = ?, capacidad = ?, imagen = ?, estado = ? WHERE id = ?";
            $stmt = $this->conexion->prepare($consulta);

            if (!$stmt) {
                throw new Exception("Error en la preparación de la consulta UPDATE: " . $this->conexion->error);
            }

            $stmt->bind_param("sssdissi", $nombre, $descripcion, $ubicacion, $precio_noche, $capacidad, $imagen, $estado, $id);

            if (!$stmt->execute()) {
                throw new Exception("Error al ejecutar la consulta UPDATE: " . $stmt->error);
            }

            $stmt->close();

        } catch (Exception $e) {
            throw $e;
        }
    }


    function eliminarLogicoRegistro($id) {
        try {
            // CORRECCIÓN: Cambiar estado a '0' (cadena) si es ENUM
            $consulta = "UPDATE $this->nombreTabla SET estado = '0' WHERE id = ?"; // Asumiendo '0' es el valor inactivo en el ENUM
            $stmt = $this->conexion->prepare($consulta);
            if (!$stmt) {
                throw new Exception("Error en la preparación de la consulta DELETE LÓGICO: " . $this->conexion->error);
            }
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception("Error al ejecutar la consulta DELETE LÓGICO: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            throw $e;
        }
    }

    function obtenerRegistros() {
        try {
            error_log("Alojamiento::obtenerRegistros - Iniciando consulta."); // Log de depuración
            $respuesta = [];
            // CORRECCIÓN: Comparar estado con la cadena '1'
            $consulta = "SELECT * FROM $this->nombreTabla WHERE estado = '1'";

            // Ejecutar la consulta y verificar si hay error
            $resultado = $this->conexion->query($consulta);
            if ($resultado === false) {
                 $errorMsg = "Error al ejecutar la consulta SELECT: " . $this->conexion->error;
                 error_log("Alojamiento::obtenerRegistros - " . $errorMsg); // Log de error específico
                 throw new Exception($errorMsg);
            }

            error_log("Alojamiento::obtenerRegistros - Consulta ejecutada, filas encontradas: " . $resultado->num_rows); // Log de depuración

            // Verificar si hay filas
            if ($resultado->num_rows > 0) {
                while ($fila = $resultado->fetch_assoc()) {
                    $respuesta[] = $fila;
                }
            } else {
                // No hay filas, pero no es un error, es una condición esperada
                error_log("Alojamiento::obtenerRegistros - No se encontraron registros con estado='1'.");
            }

            return $respuesta;

        } catch (Exception $e) {
            // Manejar el error de la consulta SELECT
            error_log("Error en obtenerRegistros Alojamiento: " . $e->getMessage());
            // Devolver false para que el controlador lo maneje como un error
            return false;
        }
    }

    // Nuevo método para obtener un alojamiento por su ID
    function obtenerPorId($id) {
        try {
            // Usar sentencias preparadas para evitar inyección SQL
            // CORRECCIÓN: Comparar estado con la cadena '1'
            $stmt = $this->conexion->prepare("SELECT * FROM $this->nombreTabla WHERE id = ? AND estado = '1' LIMIT 1");
            if (!$stmt) {
                throw new Exception("Error en la preparación de la consulta SELECT BY ID: " . $this->conexion->error);
            }
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows > 0) {
                return $resultado->fetch_assoc(); // Devuelve los datos del alojamiento encontrado
            } else {
                return false; // Alojamiento no encontrado o inactivo
            }
        } catch (Exception $e) {
            error_log("Error en obtenerPorId Alojamiento: " . $e->getMessage());
            return false;
        }
    }


    function __destruct() { // Corregido: debe ser __destruct
        if ($this->conexion) {
            $this->conexion->close();
        }
    }
}

?>