document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("form-alojamiento");
  const btnGuardar = document.getElementById("btn-guardar");

  const urlParams = new URLSearchParams(window.location.search);
  const idParam = urlParams.get("id");

  if (idParam) {
    cargarDatosAlojamiento(idParam);
  }

  btnGuardar.addEventListener("click", function () {
    const formData = new FormData(form);
    const id = formData.get("id");
    const method = id ? "PUT" : "POST";

    if (method === "PUT") {
      // Enviar como JSON
      const datosParaActualizar = {
        id: formData.get("id"),
        nombre: formData.get("nombre"),
        descripcion: formData.get("descripcion"),
        ubicacion: formData.get("ubicacion"),
        precio_noche: formData.get("precio_noche"),
        capacidad: formData.get("capacidad"),
        imagen: formData.get("imagen"),
        // No enviar 'estado' a menos que se desee editar
      };

      console.log(
        "Valor de 'imagen' enviado en PUT:",
        datosParaActualizar.imagen
      );

      fetch("../controllers/alojamiento.controller.php", {
        method: method,
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(datosParaActualizar),
      })
        .then((response) => {
          if (!response.ok) {
            return response.text().then((text) => {
              try {
                // Intenta parsear como JSON de error
                const jsonData = JSON.parse(text);
                return { ok: false, status: response.status, json: jsonData };
              } catch (e) {
                // Si no es JSON, devuelve texto
                console.error("Respuesta no JSON del servidor:", text);
                return { ok: false, status: response.status, text: text };
              }
            });
          }
          return response.json().then((json) => ({ ok: true, json }));
        })
        .then((result) => {
          if (result.ok) {
            if (result.json.respuesta === "ok") {
              alert("Alojamiento actualizado exitosamente.");
              window.location.href = "alojamientos.html";
            } else {
              alert(
                "Error: " + (result.json.mensaje || "Mensaje no disponible")
              );
            }
          } else {
            if (result.json) {
              alert(
                "Error del servidor (JSON): " +
                  (result.json.mensaje || "Error desconocido")
              );
            } else if (result.text) {
              console.error("Error del servidor (HTML/Texto):", result.text);
              alert(
                "Error del servidor (no es un JSON válido). Revisa la consola."
              );
            }
          }
        })
        .catch((error) => {
          console.error("Error en PUT:", error);
          alert("Error de red o del servidor. Detalles: " + error.message);
        });
    } else {
      // Enviar como FormData para POST
      fetch("../controllers/alojamiento.controller.php", {
        method: method,
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.respuesta === "ok") {
            alert("Alojamiento guardado exitosamente.");
            window.location.href = "alojamientos.html";
          } else {
            alert("Error: " + (data.mensaje || "Mensaje no disponible"));
          }
        })
        .catch((error) => {
          console.error("Error en POST:", error);
          alert("Error de red o del servidor. Detalles: " + error.message);
        });
    }
  });

  function cargarDatosAlojamiento(id) {
    fetch(`../controllers/alojamiento.controller.php?id=${id}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.respuesta === "ok" && data.datos) {
          const alojamiento = data.datos;
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
        } else {
          alert(
            "Error al cargar datos: " +
              (data.mensaje || "Mensaje no disponible")
          );
          window.location.href = "alojamientos.html";
        }
      })
      .catch((error) => {
        console.error("Error al cargar alojamiento:", error);
        alert("Error de red al cargar datos. Detalles: " + error.message);
        window.location.href = "alojamientos.html";
      });
  }
});
