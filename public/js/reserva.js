// Variables globales
let espacioSeleccionado = null;
let reservasEspacio = [];
let mesActual = new Date().getMonth();
let anioActual = new Date().getFullYear();
let fechaInicioSeleccionada = null;
let fechaFinSeleccionada = null;

const meses = [
  "Enero",
  "Febrero",
  "Marzo",
  "Abril",
  "Mayo",
  "Junio",
  "Julio",
  "Agosto",
  "Septiembre",
  "Octubre",
  "Noviembre",
  "Diciembre",
];

// Cargar espacios disponibles
async function cargarEspacios() {
  try {
    const response = await fetch(
      "../controllers/reserva.controller.php?accion=listarEspacios"
    );
    if (!response.ok) {
      const text = await response.text();
      console.error("Error listarEspacios:", text);
      throw new Error("Respuesta no válida del servidor");
    }
    const data = await response.json();

    if (data.respuesta === "ok") {
      mostrarEspacios(data.espacios);
    } else {
      throw new Error(data.mensaje || "No se pudieron cargar los espacios");
    }
  } catch (error) {
    console.error("Error al cargar espacios:", error);
    alert("Error al cargar los espacios disponibles");
  }
}

// Mostrar espacios en la vista
function mostrarEspacios(espacios) {
  const container = document.getElementById("espacios-lista");
  container.innerHTML = "";

  if (espacios.length === 0) {
    container.innerHTML =
      '<p class="no-data">No hay espacios disponibles en este momento.</p>';
    return;
  }

  espacios.forEach((espacio) => {
    const card = document.createElement("div");
    card.className = "espacio-card";
    card.innerHTML = `
            ${
              espacio.imagen
                ? `<img src="${espacio.imagen}" alt="${espacio.nombre}">`
                : '<div class="no-image">Sin imagen</div>'
            }
            <div class="espacio-info">
                <h3>${espacio.nombre}</h3>
                <p class="descripcion">${espacio.descripcion || ""}</p>
                <p class="ubicacion">📍 ${
                  espacio.ubicacion || "No especificada"
                }</p>
                <p class="capacidad">👥 Capacidad: ${
                  espacio.capacidad
                } personas</p>
                <p class="precio">$${parseFloat(espacio.precio_noche).toFixed(
                  2
                )} por noche</p>
                <button onclick="abrirModalReserva(${
                  espacio.id
                })" class="btn btn-primary">Reservar</button>
            </div>
        `;
    container.appendChild(card);
  });
}

// Abrir modal de reserva
async function abrirModalReserva(espacioId) {
  try {
    const response = await fetch(
      `../controllers/reserva.controller.php?accion=obtenerEspacio&id=${espacioId}`
    );
    const data = await response.json();

    if (data.respuesta === "ok") {
      espacioSeleccionado = data.espacio;
      mostrarDetalleEspacio(data.espacio);

      // Resetear formulario
      document.getElementById("fecha-inicio").value = "";
      document.getElementById("fecha-fin").value = "";
      document.getElementById("resumen-reserva").style.display = "none";
      document.getElementById("btn-confirmar-reserva").style.display = "none";

      // Cargar reservas del espacio para el calendario
      await cargarReservasEspacio(espacioId);

      // Mostrar calendario del mes actual
      generarCalendario();

      // Mostrar modal
      document.getElementById("modal-reserva").style.display = "block";
    }
  } catch (error) {
    console.error("Error al cargar espacio:", error);
    alert("Error al cargar los detalles del espacio");
  }
}

// Mostrar detalles del espacio en el modal
function mostrarDetalleEspacio(espacio) {
  const container = document.getElementById("espacio-detalle");
  container.innerHTML = `
        <div class="espacio-header">
            ${
              espacio.imagen
                ? `<img src="${espacio.imagen}" alt="${espacio.nombre}">`
                : ""
            }
            <div>
                <h3>${espacio.nombre}</h3>
                <p>${espacio.descripcion || ""}</p>
                <p><strong>Ubicación:</strong> ${
                  espacio.ubicacion || "No especificada"
                }</p>
                <p><strong>Capacidad:</strong> ${espacio.capacidad} personas</p>
                <p class="precio-destaque"><strong>Precio:</strong> $${parseFloat(
                  espacio.precio_noche
                ).toFixed(2)} por noche</p>
            </div>
        </div>
    `;
}

// Cerrar modal
function cerrarModal() {
  document.getElementById("modal-reserva").style.display = "none";
  espacioSeleccionado = null;
  reservasEspacio = [];
  fechaInicioSeleccionada = null;
  fechaFinSeleccionada = null;
}

// Cargar reservas del espacio
async function cargarReservasEspacio(espacioId) {
  try {
    const response = await fetch(
      `../controllers/reserva.controller.php?accion=obtenerReservasPorEspacio&espacio_id=${espacioId}&mes=${(
        mesActual + 1
      )
        .toString()
        .padStart(2, "0")}&anio=${anioActual}`
    );
    const data = await response.json();

    if (data.respuesta === "ok") {
      reservasEspacio = data.reservas;
    }
  } catch (error) {
    console.error("Error al cargar reservas:", error);
  }
}

// Generar calendario
function generarCalendario() {
  document.getElementById(
    "mes-anio"
  ).textContent = `${meses[mesActual]} ${anioActual}`;

  const calendario = document.getElementById("calendario");
  calendario.innerHTML = "";

  // Días de la semana
  const diasSemana = ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"];
  diasSemana.forEach((dia) => {
    const divDia = document.createElement("div");
    divDia.className = "dia-semana";
    divDia.textContent = dia;
    calendario.appendChild(divDia);
  });

  // Primer día del mes
  const primerDia = new Date(anioActual, mesActual, 1).getDay();

  // Días en blanco antes del primer día
  for (let i = 0; i < primerDia; i++) {
    const divVacio = document.createElement("div");
    divVacio.className = "dia vacio";
    calendario.appendChild(divVacio);
  }

  // Días del mes
  const diasEnMes = new Date(anioActual, mesActual + 1, 0).getDate();
  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);

  for (let dia = 1; dia <= diasEnMes; dia++) {
    const fecha = new Date(anioActual, mesActual, dia);
    const fechaStr = formatearFecha(fecha);

    const divDia = document.createElement("div");
    divDia.className = "dia";
    divDia.textContent = dia;
    divDia.dataset.fecha = fechaStr;

    // Deshabilitar fechas pasadas
    if (fecha < hoy) {
      divDia.classList.add("pasado");
    } else {
      // Verificar si está reservado
      const estaReservado = reservasEspacio.some((reserva) => {
        const inicio = new Date(reserva.fecha_inicio);
        const fin = new Date(reserva.fecha_fin);
        return fecha >= inicio && fecha < fin;
      });

      if (estaReservado) {
        divDia.classList.add("reservado");
      } else {
        divDia.classList.add("disponible");
        divDia.onclick = () => seleccionarFecha(fecha, divDia);
      }
    }

    calendario.appendChild(divDia);
  }
}

// Seleccionar fecha en el calendario
function seleccionarFecha(fecha, elemento) {
  const fechaStr = formatearFecha(fecha);

  if (
    !fechaInicioSeleccionada ||
    (fechaInicioSeleccionada && fechaFinSeleccionada)
  ) {
    // Iniciar nueva selección
    fechaInicioSeleccionada = fecha;
    fechaFinSeleccionada = null;
    document.getElementById("fecha-inicio").value = fechaStr;
    document.getElementById("fecha-fin").value = "";

    // Limpiar selecciones previas
    document
      .querySelectorAll(".dia.seleccionado")
      .forEach((d) => d.classList.remove("seleccionado"));
    elemento.classList.add("seleccionado");
  } else if (fechaInicioSeleccionada && !fechaFinSeleccionada) {
    // Seleccionar fecha fin
    if (fecha > fechaInicioSeleccionada) {
      fechaFinSeleccionada = fecha;
      document.getElementById("fecha-fin").value = fechaStr;

      // Marcar rango seleccionado
      document.querySelectorAll(".dia.disponible").forEach((d) => {
        const dFecha = new Date(d.dataset.fecha);
        if (
          dFecha > fechaInicioSeleccionada &&
          dFecha <= fechaFinSeleccionada
        ) {
          d.classList.add("seleccionado");
        }
      });
    } else {
      alert("La fecha de fin debe ser posterior a la fecha de inicio");
    }
  }
}

// Formatear fecha YYYY-MM-DD
function formatearFecha(fecha) {
  const año = fecha.getFullYear();
  const mes = (fecha.getMonth() + 1).toString().padStart(2, "0");
  const dia = fecha.getDate().toString().padStart(2, "0");
  return `${año}-${mes}-${dia}`;
}

// Navegar meses
async function mesAnterior() {
  mesActual--;
  if (mesActual < 0) {
    mesActual = 11;
    anioActual--;
  }
  await cargarReservasEspacio(espacioSeleccionado.id);
  generarCalendario();
}

async function mesSiguiente() {
  mesActual++;
  if (mesActual > 11) {
    mesActual = 0;
    anioActual++;
  }
  await cargarReservasEspacio(espacioSeleccionado.id);
  generarCalendario();
}

// Verificar disponibilidad
async function verificarDisponibilidad() {
  const fechaInicio = document.getElementById("fecha-inicio").value;
  const fechaFin = document.getElementById("fecha-fin").value;

  if (!fechaInicio || !fechaFin) {
    alert("Por favor seleccione las fechas de inicio y fin");
    return;
  }

  try {
    const response = await fetch(
      `../controllers/reserva.controller.php?accion=verificarDisponibilidad&espacio_id=${espacioSeleccionado.id}&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`
    );
    const data = await response.json();

    if (data.respuesta === "ok") {
      if (data.disponible) {
        // Mostrar resumen
        document.getElementById("resumen-dias").textContent = data.dias;
        document.getElementById("resumen-precio-noche").textContent =
          parseFloat(data.precio_noche).toFixed(2);
        document.getElementById("resumen-total").textContent = parseFloat(
          data.total
        ).toFixed(2);
        document.getElementById("resumen-reserva").style.display = "block";
        document.getElementById("btn-confirmar-reserva").style.display =
          "inline-block";
      } else {
        alert("El espacio no está disponible en las fechas seleccionadas");
        document.getElementById("resumen-reserva").style.display = "none";
        document.getElementById("btn-confirmar-reserva").style.display = "none";
      }
    }
  } catch (error) {
    console.error("Error al verificar disponibilidad:", error);
    alert("Error al verificar disponibilidad");
  }
}

// Confirmar reserva
async function confirmarReserva() {
  const fechaInicio = document.getElementById("fecha-inicio").value;
  const fechaFin = document.getElementById("fecha-fin").value;

  if (!confirm("¿Confirmar la reserva?")) {
    return;
  }

  try {
    const formData = new FormData();
    formData.append("accion", "crearReserva");
    formData.append("espacio_id", espacioSeleccionado.id);
    formData.append("fecha_inicio", fechaInicio);
    formData.append("fecha_fin", fechaFin);

    const response = await fetch("../controllers/reserva.controller.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (data.respuesta === "ok") {
      alert("¡Reserva creada exitosamente!");
      cerrarModal();
      cargarMisReservas();
    } else {
      alert(data.mensaje || "Error al crear la reserva");
    }
  } catch (error) {
    console.error("Error al crear reserva:", error);
    alert("Error al crear la reserva");
  }
}

// Cargar mis reservas
async function cargarMisReservas() {
  try {
    const response = await fetch(
      "../controllers/reserva.controller.php?accion=listarMisReservas"
    );
    const data = await response.json();

    if (data.respuesta === "ok") {
      mostrarMisReservas(data.reservas);
    }
  } catch (error) {
    console.error("Error al cargar reservas:", error);
  }
}

// Mostrar mis reservas
function mostrarMisReservas(reservas) {
  const container = document.getElementById("mis-reservas-lista");
  container.innerHTML = "";

  if (reservas.length === 0) {
    container.innerHTML = '<p class="no-data">No tienes reservas aún.</p>';
    return;
  }

  reservas.forEach((reserva) => {
    const card = document.createElement("div");
    card.className = `reserva-card estado-${reserva.estado}`;

    const fechaInicio = new Date(reserva.fecha_inicio).toLocaleDateString(
      "es-ES"
    );
    const fechaFin = new Date(reserva.fecha_fin).toLocaleDateString("es-ES");

    card.innerHTML = `
            <div class="reserva-header">
                <h3>${reserva.espacio_nombre}</h3>
                <span class="badge badge-${
                  reserva.estado
                }">${reserva.estado.toUpperCase()}</span>
            </div>
            <p><strong>Ubicación:</strong> ${
              reserva.ubicacion || "No especificada"
            }</p>
            <p><strong>Fechas:</strong> ${fechaInicio} - ${fechaFin}</p>
            <p><strong>Total:</strong> $${parseFloat(reserva.total).toFixed(
              2
            )}</p>
            <p><strong>Código QR:</strong> ${reserva.codigo_qr}</p>
            ${
              reserva.estado === "pendiente"
                ? `<button onclick="cancelarReserva(${reserva.id})" class="btn btn-danger">Cancelar Reserva</button>`
                : ""
            }
        `;

    container.appendChild(card);
  });
}

// Cancelar reserva
async function cancelarReserva(reservaId) {
  if (!confirm("¿Está seguro de cancelar esta reserva?")) {
    return;
  }

  try {
    const formData = new FormData();
    formData.append("accion", "cancelarReserva");
    formData.append("id", reservaId);

    const response = await fetch("../controllers/reserva.controller.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (data.respuesta === "ok") {
      alert("Reserva cancelada exitosamente");
      cargarMisReservas();
    } else {
      alert(data.mensaje || "Error al cancelar la reserva");
    }
  } catch (error) {
    console.error("Error al cancelar reserva:", error);
    alert("Error al cancelar la reserva");
  }
}

// Cerrar modal al hacer clic fuera
window.onclick = function (event) {
  const modal = document.getElementById("modal-reserva");
  if (event.target === modal) {
    cerrarModal();
  }
};
