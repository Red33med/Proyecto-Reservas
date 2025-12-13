<?php


class Usuario {
    private $id;
    private $nombre;
    private $correo;
    private $password;
    private $cedula;
    private $telefono;
    private $rol;
    private $estado;
    private $nombreTabla = "usuarios";
    private $conexion;

    function __construct() {
        global $host, $db_name, $user, $pass, $port;
        try {
            $this->conexion = new mysqli($host, $user, $pass, $db_name);
        }
        catch (Exception $e) {
            var_dump($e->getMessage());

        }

    }

    function insertarRegistro($nombre, $correo, $password, $cedula, $telefono, $rol) {
        try {
            $consulta = "INSERT INTO $this->nombreTabla (nombre, correo, password, cedula, telefono, rol) VALUES ('$nombre', '$correo', '$password', '$cedula', '$telefono', '$rol')";
            $this->conexion->query($consulta);
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
    }


    function actualizarRegistro($id, $nombre, $correo, $password, $cedula, $telefono, $rol) {
        try {
            $consulta = "UPDATE $this->nombreTabla SET nombre = '$nombre', correo = '$correo', password = '$password', cedula = '$cedula', telefono = '$telefono', rol = '$rol' WHERE id = $id";
            $this->conexion->query($consulta);
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
    }


    function eliminarRegistro($id) {
        try {
            $consulta = "DELETE FROM $this->nombreTabla WHERE id = '$id'";
            $this->conexion->query($consulta);
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
    }


    function eliminarLogicoRegistro($id) {
        try {
            $consulta = "UPDATE $this->nombreTabla SET estado = 0 WHERE id = '$id'"; 
            $this->conexion->query($consulta);
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
    }

    function obtenerRegistros() {
        try {
            $respuesta = [];
            $consulta = "SELECT * FROM $this->nombreTabla WHERE estado = 1";
            $resultado = $this->conexion->query($consulta);
            if ($resultado->num_rows > 0) {
                while ($fila = $resultado->fetch_assoc()) {
                    $respuesta[] = $fila;
                }
            }
            return $respuesta;

        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
    }

    function _destruct() {
        if ($this->conexion) {
            $this->conexion->close();
        }
    }
}

$objUsuario = new Usuario();
// $objUsuario->insertarRegistro('juan','juan@gmail.com','0954345779','0988087656','USUARIO');
// $objUsuario->actualizarRegistro("2", "Alfredo", "alfredo@gmail.com", "0954345000", "0988087000", "USUARIO");
// $objUsuario->eliminarRegistro("2");
// $objUsuario->eliminarLogicoRegistro("4");
// var_dump($objUsuario->obtenerRegistros());

?>