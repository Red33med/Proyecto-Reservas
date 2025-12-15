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

    // function __construct() {
    //     global $host, $db_name, $user, $pass, $port;
    //     try {
    //         $this->conexion = new mysqli($host, $user, $pass, $db_name);
    //     }
    //     catch (Exception $e) {
    //         var_dump($e->getMessage());

    //     }

    // }
    function __construct() {
        // Opción A: Incluir el archivo aquí mismo para asegurar que lea las variables
        require '../configurations/db.php'; 
        
        // Opción B (Si la A falla): Escribe las credenciales directo aquí para probar
        // $host = 'localhost'; $user = 'root'; $pass = ''; $db_name = 'tuben_db';

        try {
            // Creamos la conexión usando las variables que acabamos de cargar
            $this->conexion = new mysqli($host, $user, $pass, $db_name);
            
            // Verificamos si hubo error en la conexión
            if ($this->conexion->connect_error) {
                die("Falló la conexión a la BD: " . $this->conexion->connect_error);
            }

        } catch (Exception $e) {
            die("Error crítico: " . $e->getMessage());
        }
    }
    

    function insertarRegistro($nombre, $correo, $password, $cedula, $telefono, $rol) {
        try {
            // 1. Encriptamos la contraseña (¡OBLIGATORIO!)
            $hashPassword = password_hash($password, PASSWORD_DEFAULT);

            $consulta = "INSERT INTO $this->nombreTabla (nombre, correo, password, cedula, telefono, rol) VALUES ('$nombre', '$correo', '$hashPassword', '$cedula', '$telefono', '$rol')";
            $this->conexion->query($consulta);
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
    }


    function actualizarRegistro($id, $nombre, $correo, $password, $cedula, $telefono, $rol) {
        try {

            if (!empty($password)) {
                // Si se proporciona una nueva contraseña, la encriptamos
                $hashPassword = password_hash($password, PASSWORD_DEFAULT);
                $consulta = "UPDATE $this->nombreTabla SET nombre = '$nombre', correo = '$correo', password = '$hashPassword', cedula = '$cedula', telefono = '$telefono', rol = '$rol' WHERE id = $id";    
                $this->conexion->query($consulta);
            } else {
                // Si no se proporciona una nueva contraseña, no la actualizamos
                $consulta = "UPDATE $this->nombreTabla SET nombre = '$nombre', correo = '$correo', cedula = '$cedula', telefono = '$telefono', rol = '$rol' WHERE id = $id";
                $this->conexion->query($consulta);
            }

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

    function obtenerUnRegistro($id) {
        try {
            $consulta = "SELECT * FROM $this->nombreTabla WHERE id = '$id'";
            $resultado = $this->conexion->query($consulta);
            if ($resultado->num_rows > 0) {
                return $resultado->fetch_assoc();
            } else {
                return null;
            }
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
// $objUsuario->actualizarRegistro("13", "mateo", "mateo@gmail.com", "555555", "0954345000", "0988087000", "USUARIO");
// $objUsuario->eliminarRegistro("2");
// $objUsuario->eliminarLogicoRegistro("4");
// var_dump($objUsuario->obtenerRegistros());

?>