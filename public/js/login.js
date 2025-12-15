document
  .getElementById("form-login")
  .addEventListener("submit", function (event) {
    event.preventDefault(); // Evita el envío tradicional del formulario

    const correo = document.getElementById("correo_login").value;
    const password = document.getElementById("password_login").value;

    // Opcional: Validar campos vacíos aquí antes de enviar
    if (!correo || !password) {
      alert("Por favor, ingrese correo y contraseña.");
      return;
    }

    // Preparar datos para enviar
    const formData = new FormData();
    formData.append("correo", correo);
    formData.append("password", password);

    // Enviar credenciales al backend
    fetch("../controllers/login.controller.php", {
      // Ajusta la ruta según tu estructura
      method: "POST",
      body: formData,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        console.log("Respuesta del backend:", data); // Para depuración

        if (data.respuesta === "ok") {
          // Inicio de sesión exitoso
          if (data.usuario.rol === "ADMIN") {
            // Redirigir al panel de gestión de usuarios
            window.location.href = "users.html";
          } else {
            // Redirigir a una pantalla en blanco temporal o mostrar datos en consola
            // Por ahora, simplemente mostramos un mensaje o redirigimos a una pantalla genérica
            // Si solo quieres ver los datos en consola, quita la redirección y haz console.log(data.usuario)
            console.log(
              "Datos del usuario logueado (Rol no ADMIN):",
              data.usuario
            );
            alert(`Bienvenido, ${data.usuario.nombre}. Redirigiendo...`); // Mensaje temporal
            // window.location.href = 'inicio_usuario.html'; // Redirige a una página para usuarios no admin (crea esta página si es necesario)
          }
        } else if (data.respuesta === "error") {
          // Mostrar mensaje de error del backend
          alert(
            data.mensaje || "Credenciales incorrectas o error en el servidor."
          );
        } else {
          alert("Respuesta inesperada del servidor.");
        }
      })
      .catch((error) => {
        console.error("Error en la solicitud de login:", error);
        alert("Ocurrió un error de red o del servidor. Intente nuevamente.");
      });
  });
