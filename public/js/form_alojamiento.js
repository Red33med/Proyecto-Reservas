document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("form-alojamiento");
  const btnGuardar = document.getElementById("btn-guardar");

  // Verificar si hay un ID en la URL (para edición)
  const urlParams = new URLSearchParams(window.location.search);
  const idParam = urlParams.get("id");

  if (idParam) {
    // Si hay un ID, estamos en modo edición
    cargarDatosAlojamiento(idParam);
  }

  btnGuardar.addEventListener("click", function () {
    const formData = new FormData(form);

    // Determinar si es INSERT o UPDATE
    const id = formData.get("id");
    const method = id ? "PUT" : "POST";

    // Enviar los datos al controlador
    fetch("../controllers/alojamiento.controller.php", {
      method: method,
      body: formData,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        console.log(data); // Para depuración
        if (data.respuesta === "ok") {
          alert("Alojamiento guardado exitosamente.");
          // Redirigir a la lista de alojamientos
          window.location.href = "alojamientos.html";
        } else {
          alert(
            "Hubo un problema al guardar el alojamiento. Intente nuevamente."
          );
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("Ocurrió un error de red. Intente nuevamente.");
      });
  });

  // Función para cargar los datos de un alojamiento para editar
  function cargarDatosAlojamiento(id) {
    fetch(`../controllers/alojamiento.controller.php?id=${id}`) // Usamos GET con parámetro
      .then((response) => response.json())
      .then((data) => {
        if (data.respuesta === "ok" && data.datos) {
          const alojamiento = data.datos;
          // Rellenar los campos del formulario
          document.getElementById("id_alojamiento").value = alojamiento.id;
          document.getElementById("nombre_alojamiento").value =
            alojamiento.nombre;
          document.getElementById("descripcion_alojamiento").value =
            alojamiento.descripcion;
          document.getElementById("ubicacion_alojamiento").value =
            alojamiento.ubicacion;
          document.getElementById("precio_noche_alojamiento").value =
            alojamiento.precio_noche;
          document.getElementById("capacidad_alojamiento").value =
            alojamiento.capacidad;
          document.getElementById("imagen_alojamiento").value =
            alojamiento.imagen;
          // El estado no se edita directamente en este formulario
        } else {
          alert("No se pudo cargar los datos del alojamiento.");
          window.location.href = "alojamientos.html"; // Volver a la lista
        }
      })
      .catch((error) => {
        console.error("Error al cargar alojamiento:", error);
        alert("Ocurrió un error al cargar los datos. Intente nuevamente.");
        window.location.href = "alojamientos.html";
      });
  }
});
