document.getElementById("btn-registrar").addEventListener("click", function () {
  const form = document.getElementById("form-registro");
  const password = document.getElementById("password_registro").value;
  const confirmPassword = document.getElementById(
    "confirm_password_registro"
  ).value;
  const errorMessageContrasenas = document.getElementById(
    "mensaje_error_contrasenas"
  );
  const errorMessageServidor = document.getElementById(
    "mensaje_error_servidor"
  );

  // Validar que las contraseñas coincidan
  if (password !== confirmPassword) {
    errorMessageContrasenas.classList.remove("d-none");
    errorMessageServidor.classList.add("d-none"); // Oculta error del servidor si había
    return; // Detener la ejecución si no coinciden
  }

  // Ocultar mensaje de error de contraseñas si coinciden
  errorMessageContrasenas.classList.add("d-none");
  // Ocultar mensaje de error del servidor (por si acaso)
  errorMessageServidor.classList.add("d-none");

  // Preparar los datos del formulario, excluyendo 'confirm_password'
  const formData = new FormData(form);
  // Eliminamos el campo 'confirm_password' porque no lo queremos enviar al backend
  formData.delete("confirm_password");

  // Enviar los datos al controlador de usuario
  fetch("../controllers/user.controller.php", {
    // Ajusta la ruta si es necesario
    method: "POST",
    body: formData,
  })
    .then((response) => {
      // Verificar si la respuesta HTTP es exitosa (status 2xx)
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      // Intentar parsear la respuesta como JSON
      return response.json();
    })
    .then((data) => {
      console.log(data); // Para depuración

      // Verificar si la respuesta indica éxito
      if (data.respuesta === "ok") {
        alert("Usuario registrado exitosamente.");
        // Opcional: Limpiar el formulario o redirigir a login
        form.reset();
        // window.location.href = 'login.html'; // Descomentar si quieres redirigir automáticamente
      } else if (data.respuesta === "error") {
        // Si la respuesta indica error, mostrar el mensaje específico en el div
        let mensajeAlerta = "Hubo un problema al registrar el usuario.";
        // Identificar el tipo de error basado en el mensaje del backend
        if (data.mensaje.includes("Duplicate entry")) {
          if (data.mensaje.includes("correo")) {
            mensajeAlerta =
              "El correo electrónico ingresado ya está registrado. Por favor, use otro.";
          } else if (data.mensaje.includes("cedula")) {
            mensajeAlerta =
              "La cédula ingresada ya está registrada. Por favor, ingrese otra.";
          } else if (data.mensaje.includes("telefono")) {
            mensajeAlerta =
              "El número de teléfono ingresado ya está registrado. Por favor, use otro.";
          } else {
            // Si es un Duplicate entry pero no coincide con los campos comunes
            mensajeAlerta =
              "Ya existe un usuario con uno de los datos ingresados (correo, cédula, teléfono). Por favor, verifique sus datos.";
          }
        } else {
          // Para otros tipos de errores no identificados específicamente
          mensajeAlerta += ` Detalles: ${data.mensaje}`;
        }
        // Mostrar el mensaje en el div correspondiente
        errorMessageServidor.textContent = mensajeAlerta; // Asigna el texto
        errorMessageServidor.classList.remove("d-none"); // Muestra el div
      } else {
        // Caso raro donde la respuesta no es ni "ok" ni "error"
        errorMessageServidor.textContent =
          "La respuesta del servidor no es válida.";
        errorMessageServidor.classList.remove("d-none");
      }
    })
    .catch((error) => {
      // Captura errores de red o problemas de parsing JSON si el backend devuelve algo inesperado
      if (error instanceof SyntaxError) {
        console.error("Error de parsing JSON:", error.message);
        // Mostrar error de parsing en el div
        errorMessageServidor.textContent =
          "La respuesta del servidor no es válida o está en un formato incorrecto.";
        errorMessageServidor.classList.remove("d-none");
      } else {
        console.error("Error de red o del servidor:", error);
        errorMessageServidor.textContent =
          "Ocurrió un error de red o del servidor. Intente nuevamente.";
        errorMessageServidor.classList.remove("d-none");
      }
    });
});
