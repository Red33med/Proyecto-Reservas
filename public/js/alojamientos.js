// Función para obtener y mostrar todos los alojamientos
function obtenerRegistros() {
  fetch("../controllers/alojamiento.controller.php") // Ajusta la ruta si es necesario
    .then((response) => {
      // Verificar si la respuesta HTTP es exitosa (status 2xx)
      if (!response.ok) {
        // Si no es exitosa, intentar leer el cuerpo como texto para ver si hay un mensaje de error del servidor
        return response.text().then((text) => {
          console.error(
            "Error HTTP: ",
            response.status,
            response.statusText,
            "Cuerpo de la respuesta:",
            text
          );
          throw new Error(`HTTP error! status: ${response.status}`);
        });
      }
      // Si es exitosa, intentar parsear como JSON
      return response.json();
    })
    .then((response) => {
      console.log("Respuesta del servidor (depuración):", response); // Agregar esta línea para ver la respuesta exacta
      var content = "";
      if (response.respuesta === "ok" && Array.isArray(response.datos)) {
        if (response.datos.length === 0) {
          content = `<tr><td colspan="9" class="text-center">No se encontraron alojamientos.</td></tr>`;
        } else {
          response.datos.forEach((element) => {
            // CORRECCIÓN: Convertir precio_noche a número antes de usar toFixed
            const precioFormateado = parseFloat(element.precio_noche).toFixed(
              2
            );
            content += `
                        <tr>
                            <td>${element.id}</td>
                            <td>${element.nombre}</td>
                            <td>${element.descripcion.substring(0, 50)}${
              element.descripcion.length > 50 ? "..." : ""
            }</td>
                            <td>${element.ubicacion}</td>
                            <td>$${precioFormateado}</td> <!-- Usar la variable formateada -->
                            <td>${element.capacidad}</td>
                            <td><img src="${
                              element.imagen
                            }" alt="#" class="img-thumbnail" onerror="this.src='https://via.placeholder.com/100x100?text=Sin+Imagen';"></td> <!-- Asegurar comillas en src original y usar placeholder con dimensiones -->
                            <td>${
                              element.estado === "1" ? "Activo" : "Inactivo"
                            }</td> <!-- Comparar con cadena '1' -->
                            <td>
                                <button class="btn btn-warning btn-sm btn-custom" onClick="editarAlojamiento(${
                                  element.id
                                })">Editar</button>
                                <button class="btn btn-danger btn-sm btn-custom" onClick="eliminarAlojamiento(${
                                  element.id
                                })">Eliminar</button>
                            </td>
                        </tr>`;
          });
        }
      } else if (response.respuesta === "error") {
        content = `<tr><td colspan="9" class="text-center text-danger">Error del servidor: ${
          response.mensaje || "Mensaje no disponible"
        }</td></tr>`;
      } else {
        content = `<tr><td colspan="9" class="text-center">Respuesta inesperada del servidor.</td></tr>`;
      }
      document.getElementById("registros").innerHTML = content;
    })
    .catch((error) => {
      console.error("Error al obtener alojamientos:", error);
      document.getElementById(
        "registros"
      ).innerHTML = `<tr><td colspan="9" class="text-center text-danger">Error de red o del servidor al cargar los datos. Detalles: ${error.message}</td></tr>`;
    });
}

// Función para editar un alojamiento
function editarAlojamiento(id) {
  // Redirigir al formulario de edición pasando el ID como parámetro en la URL
  window.location.href = `form_alojamiento.html?id=${id}`;
}

// Función para eliminar un alojamiento (eliminación lógica)
function eliminarAlojamiento(id) {
  if (confirm("¿Estás seguro de que deseas eliminar este alojamiento?")) {
    fetch("../controllers/alojamiento.controller.php", {
      method: "DELETE",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ id: id }),
    })
      .then((response) => response.json())
      .then((response) => {
        if (response.respuesta === "ok") {
          obtenerRegistros(); // Refrescar la lista
        } else {
          alert(
            "Hubo un problema al eliminar el alojamiento: " +
              (response.mensaje || "Error desconocido.")
          );
        }
      })
      .catch((error) => {
        console.error("Error al eliminar alojamiento:", error);
        alert(
          "Ocurrió un error de red. Intente nuevamente. Detalles: " +
            error.message
        );
      });
  }
}

// Llamar a obtenerRegistros cuando la página cargue
obtenerRegistros();
