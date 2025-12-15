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
    

    // function insertarRegistro($nombre, $correo, $password, $cedula, $telefono, $rol) {
    //     try {
    //         // 1. Encriptamos la contraseña (¡OBLIGATORIO!)
    //         $hashPassword = password_hash($password, PASSWORD_DEFAULT);

    //         $consulta = "INSERT INTO $this->nombreTabla (nombre, correo, password, cedula, telefono, rol) VALUES ('$nombre', '$correo', '$hashPassword', '$cedula', '$telefono', '$rol')";
    //         $this->conexion->query($consulta);
    //     } catch (Exception $e) {
    //         var_dump($e->getMessage());
    //     }
    // }


    function insertarRegistro($nombre, $correo, $password, $cedula, $telefono, $rol) {
        try {
            // 1. Encriptamos la contraseña (¡OBLIGATORIO!)
            $hashPassword = password_hash($password, PASSWORD_DEFAULT);

            // CORRECCIÓN: Usar sentencias preparadas para evitar inyección SQL
            $consulta = "INSERT INTO $this->nombreTabla (nombre, correo, password, cedula, telefono, rol) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($consulta);

            if (!$stmt) {
                throw new Exception("Error en la preparación de la consulta: " . $this->conexion->error);
            }

            $stmt->bind_param("ssssss", $nombre, $correo, $hashPassword, $cedula, $telefono, $rol);

            if (!$stmt->execute()) {
                // Capturar específicamente el error de clave duplicada (SQLSTATE 23000)
                if ($stmt->errno === 1062) { // Código de error MySQL para clave duplicada
                    $errorMsg = $stmt->error;
                    // Determinar qué campo está duplicado basado en el mensaje de error
                    if (strpos($errorMsg, 'correo') !== false) {
                        throw new Exception("El correo electrónico ingresado ya está registrado.");
                    } elseif (strpos($errorMsg, 'cedula') !== false) {
                        throw new Exception("La cédula ingresada ya está registrada.");
                    } elseif (strpos($errorMsg, 'telefono') !== false) {
                        throw new Exception("El número de teléfono ingresado ya está registrado.");
                    } else {
                        throw new Exception("Ya existe un usuario con uno de los datos ingresados (correo, cédula, teléfono).");
                    }
                } else {
                     // Otro error de ejecución
                    throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
                }
            }

            $stmt->close();

        } catch (Exception $e) {
            // Lanzar la excepción para que el controlador la maneje
            throw $e;
        }
    }


    // function actualizarRegistro($id, $nombre, $correo, $password, $cedula, $telefono, $rol) {
    //     try {

    //         if (!empty($password)) {
    //             // Si se proporciona una nueva contraseña, la encriptamos
    //             $hashPassword = password_hash($password, PASSWORD_DEFAULT);
    //             $consulta = "UPDATE $this->nombreTabla SET nombre = '$nombre', correo = '$correo', password = '$hashPassword', cedula = '$cedula', telefono = '$telefono', rol = '$rol' WHERE id = $id";    
    //             $this->conexion->query($consulta);
    //         } else {
    //             // Si no se proporciona una nueva contraseña, no la actualizamos
    //             $consulta = "UPDATE $this->nombreTabla SET nombre = '$nombre', correo = '$correo', cedula = '$cedula', telefono = '$telefono', rol = '$rol' WHERE id = $id";
    //             $this->conexion->query($consulta);
    //         }

    //     } catch (Exception $e) {
    //         var_dump($e->getMessage());
    //     }
    // }

    function actualizarRegistro($id, $nombre, $correo, $password, $cedula, $telefono, $rol) {
        // Similar a insertarRegistro, usar sentencias preparadas y manejar errores
        try {
            // Encriptar contraseña solo si se va a actualizar
            if ($password) {
                $hashPassword = password_hash($password, PASSWORD_DEFAULT);
                $consulta = "UPDATE $this->nombreTabla SET nombre = ?, correo = ?, password = ?, cedula = ?, telefono = ?, rol = ? WHERE id = ?";
                $stmt = $this->conexion->prepare($consulta);
                $stmt->bind_param("ssssssi", $nombre, $correo, $hashPassword, $cedula, $telefono, $rol, $id);
            } else {
                // Si no se actualiza la contraseña
                $consulta = "UPDATE $this->nombreTabla SET nombre = ?, correo = ?, cedula = ?, telefono = ?, rol = ? WHERE id = ?";
                $stmt = $this->conexion->prepare($consulta);
                $stmt->bind_param("sssssi", $nombre, $correo, $cedula, $telefono, $rol, $id);
            }

            if (!$stmt) {
                throw new Exception("Error en la preparación de la consulta UPDATE: " . $this->conexion->error);
            }

            if (!$stmt->execute()) {
                if ($stmt->errno === 1062) { // Clave duplicada
                    $errorMsg = $stmt->error;
                    if (strpos($errorMsg, 'correo') !== false) {
                        throw new Exception("El correo electrónico ingresado ya está registrado por otro usuario.");
                    } elseif (strpos($errorMsg, 'cedula') !== false) {
                        throw new Exception("La cédula ingresada ya está registrada por otro usuario.");
                    } elseif (strpos($errorMsg, 'telefono') !== false) {
                        throw new Exception("El número de teléfono ingresado ya está registrado por otro usuario.");
                    } else {
                        throw new Exception("Ya existe un usuario con uno de los datos ingresados (correo, cédula, teléfono).");
                    }
                } else {
                    throw new Exception("Error al ejecutar la consulta UPDATE: " . $stmt->error);
                }
            }

            $stmt->close();

        } catch (Exception $e) {
            throw $e;
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

    function obtenerPorCorreo($correo) {
        try {
            // Usar sentencias preparadas para evitar inyección SQL
            $stmt = $this->conexion->prepare("SELECT id, nombre, correo, password, rol, estado FROM $this->nombreTabla WHERE correo = ? AND estado = 1 LIMIT 1");
            if (!$stmt) {
                throw new Exception("Error en la preparación de la consulta SELECT BY CORREO: " . $this->conexion->error);
            }
            $stmt->bind_param("s", $correo);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows > 0) {
                return $resultado->fetch_assoc(); // Devuelve los datos del usuario encontrado
            } else {
                return false; // Usuario no encontrado o inactivo
            }
        } catch (Exception $e) {
            error_log("Error en obtenerPorCorreo Usuario: " . $e->getMessage());
            return false;
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