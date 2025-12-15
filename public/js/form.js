// 1. EVENTO DE CARGA: Revisar si estamos en Modo Edición
document.addEventListener("DOMContentLoaded", function () {
  // Buscamos si hay un ID en la URL (ej: form.html?id=13)
  const urlParams = new URLSearchParams(window.location.search);
  const idUsuario = urlParams.get("id");

  if (idUsuario) {
    // --- MODO EDICIÓN DETECTADO ---
    console.log("Editando usuario ID:", idUsuario);

    // 1. Cambiamos el título visualmente
    document.getElementById("titulo-form").innerText = "Editar Usuario";

    // 2. Guardamos el ID en el input oculto (hidden)
    document.getElementById("id").value = idUsuario;

    // 3. Pedimos los datos actuales al Backend para rellenar el formulario
    fetch(`../controllers/user.controller.php?id=${idUsuario}`)
      .then((response) => response.json())
      .then((data) => {
        // Verificamos si la respuesta trajo datos
        if (data.datos) {
          const u = data.datos;
          // Rellenamos los inputs
          document.getElementById("nombre").value = u.nombre;
          document.getElementById("correo").value = u.correo;
          document.getElementById("cedula").value = u.cedula;
          document.getElementById("telefono").value = u.telefono;
          // Seleccionamos el rol correcto en el select
          document.getElementById("rol").value = u.rol;

          // NOTA: El campo 'password' NO se rellena por seguridad.
          // Si el usuario quiere cambiarla, escribirá una nueva.
        } else {
          alert("No se encontró el usuario");
          window.location.href = "users.html";
        }
      })
      .catch((error) => console.error("Error cargando usuario:", error));
  }
});

// 2. EVENTO CLICK: Guardar los cambios (Crear o Actualizar)
document.getElementById("btn-guardar").addEventListener("click", function () {
  var formUsuario = document.getElementById("form-usuario");

  // Convertimos los datos del formulario a un Objeto JSON limpio
  var formData = new FormData(formUsuario);
  var datos = {};
  formData.forEach((value, key) => (datos[key] = value));

  // DEBUG: Esto imprimirá en la consola (F12) qué datos se están capturando.
  // Si ves todo vacío aquí, revisa que tus inputs en HTML tengan name="nombre", name="correo", etc.
  console.log("Datos capturados:", datos);

  // --- DECISIÓN CRÍTICA ---
  // Si el campo oculto 'id' tiene valor, usamos PUT. Si no, POST.
  var metodo = datos.id ? "PUT" : "POST";

  // --- VALIDACIONES PREVIAS (Para evitar errores en PHP) ---

  // 1. Validar campos básicos: Si alguno falta o está vacío, NO enviamos nada.
  if (!datos.nombre || !datos.correo || !datos.cedula || !datos.telefono) {
    alert(
      "Por favor completa todos los campos obligatorios (Nombre, Correo, Cédula, Teléfono).\n\nNota: Si llenaste todo y sale esto, verifica que tus inputs en HTML tengan el atributo 'name'."
    );
    return; // Detenemos la ejecución
  }

  // 2. Validación extra: Si es usuario NUEVO (POST), la contraseña es obligatoria.
  if (metodo === "POST" && !datos.password) {
    alert("La contraseña es obligatoria para nuevos usuarios");
    return; // Detenemos aquí
  }

  // Enviamos la petición al servidor
  fetch("../controllers/user.controller.php", {
    method: metodo,
    headers: {
      "Content-Type": "application/json", // ¡Importante para que PHP entienda el JSON!
    },
    body: JSON.stringify(datos), // Empaquetamos todo como texto JSON
  })
    .then((response) => response.json()) // Esperamos respuesta JSON del servidor
    .then((response) => {
      if (response.respuesta === "ok") {
        alert("Guardado correctamente");
        window.location.href = "users.html"; // Redirigimos a la lista
      } else {
        // Si PHP devolvió error (ej: "Correo duplicado"), lo mostramos
        alert("Error del servidor: " + (response.mensaje || "Desconocido"));
      }
    })
    .catch((error) => {
      console.error("Error de red:", error);
      alert("Error de conexión con el servidor. Revisa la consola.");
    });
});
