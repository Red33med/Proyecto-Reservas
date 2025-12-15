// Verificar sesión al cargar
window.addEventListener("DOMContentLoaded", function () {
  verificarSesion();
  cargarEspacios();
  cargarMisReservas();
});

function cerrarSesion() {
  sessionStorage.clear();
  localStorage.clear();
  window.location.href = "login.html";
}

function verificarSesion() {
  const user = JSON.parse(sessionStorage.getItem("user") || "{}");
  if (!user.id) {
    window.location.href = "login.html";
    return;
  }
  document.getElementById("usuario-nombre").textContent = user.nombre;
}

// Funcionalidad de favoritos
document.querySelectorAll(".favorite-btn").forEach((btn) => {
  btn.addEventListener("click", function (e) {
    e.preventDefault();
    const icon = this.querySelector(".material-icons");
    if (icon.textContent === "favorite_border") {
      icon.textContent = "favorite";
      icon.classList.remove("text-muted");
      icon.classList.add("text-danger");
    } else {
      icon.textContent = "favorite_border";
      icon.classList.remove("text-danger");
      icon.classList.add("text-muted");
    }
  });
});

// Funcionalidad del calendario
const calendarDays = document.querySelectorAll(
  ".calendar-day:not(.unavailable)"
);
let selectedStart = null;
let selectedEnd = null;

calendarDays.forEach((day) => {
  day.addEventListener("click", function () {
    const dayNum = parseInt(this.textContent);

    // Si no hay fecha de inicio o ya hay inicio y fin, reiniciar
    if (!selectedStart || (selectedStart && selectedEnd)) {
      // Limpiar selección anterior
      calendarDays.forEach((d) => {
        d.classList.remove("selected-start", "selected-end", "in-range");
      });

      selectedStart = dayNum;
      selectedEnd = null;
      this.classList.add("selected-start");
    }
    // Si hay inicio pero no fin
    else {
      selectedEnd = dayNum;

      // Asegurar que el fin sea después del inicio
      if (selectedEnd < selectedStart) {
        [selectedStart, selectedEnd] = [selectedEnd, selectedStart];
      }

      // Aplicar clases
      calendarDays.forEach((d) => {
        const num = parseInt(d.textContent);
        d.classList.remove("selected-start", "selected-end", "in-range");

        if (num === selectedStart) {
          d.classList.add("selected-start");
        } else if (num === selectedEnd) {
          d.classList.add("selected-end");
        } else if (num > selectedStart && num < selectedEnd) {
          d.classList.add("in-range");
        }
      });

      // Actualizar el input de fechas
      updateDateInput();
    }
  });
});

// Actualizar input de fechas
function updateDateInput() {
  if (selectedStart && selectedEnd) {
    const dateInput = document.querySelector("input[readonly]");
    dateInput.value = `${selectedStart} Oct - ${selectedEnd} Oct`;
  }
}

// Navegación del calendario (mes anterior/siguiente)
const monthNames = [
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
let currentMonth = 9; // Octubre (0-indexed)
let currentYear = 2023;

document.querySelectorAll(".calendar-card .btn-link").forEach((btn, index) => {
  btn.addEventListener("click", function () {
    if (index === 0) {
      // Mes anterior
      currentMonth--;
      if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
      }
    } else {
      // Mes siguiente
      currentMonth++;
      if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
      }
    }

    // Actualizar título del mes
    const monthTitle = this.parentElement.querySelector(".fw-bold");
    monthTitle.textContent = `${monthNames[currentMonth]} ${currentYear}`;
  });
});

// Filtros interactivos
const filterButtons = document.querySelectorAll(".filter-btn");
filterButtons.forEach((btn) => {
  btn.addEventListener("click", function () {
    this.classList.toggle("active");
    if (this.classList.contains("active")) {
      this.style.backgroundColor = "var(--primary)";
      this.style.color = "white";
      this.style.borderColor = "var(--primary)";
    } else {
      this.style.backgroundColor = "white";
      this.style.color = "";
      this.style.borderColor = "#e2e8f0";
    }
  });
});

// Búsqueda
document.querySelector(".btn-primary").addEventListener("click", function () {
  const destination = document.querySelector(
    'input[placeholder*="dónde"]'
  ).value;
  const dates = document.querySelector("input[readonly]").value;
  const guests = document.querySelector("select").value;

  console.log("Búsqueda:", { destination, dates, guests });

  // Mostrar alerta de búsqueda
  alert(`Buscando alojamientos en ${destination} para ${dates} (${guests})`);
});

// Animación de scroll en las tarjetas
const cards = document.querySelectorAll(".accommodation-card");
const observer = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = "0";
        entry.target.style.transform = "translateY(20px)";
        setTimeout(() => {
          entry.target.style.transition = "all 0.5s ease";
          entry.target.style.opacity = "1";
          entry.target.style.transform = "translateY(0)";
        }, 100);
      }
    });
  },
  { threshold: 0.1 }
);

cards.forEach((card) => observer.observe(card));

// Menú de ordenamiento
const sortMenu = document.querySelector(".d-flex.align-items-center.gap-1");
if (sortMenu) {
  sortMenu.style.cursor = "pointer";
  sortMenu.addEventListener("click", function () {
    const options = [
      "Recomendados",
      "Precio: menor a mayor",
      "Precio: mayor a menor",
      "Mejor valorados",
      "Más recientes",
    ];
    const currentText = this.querySelector(".small").textContent;
    const currentIndex = options.findIndex((opt) => currentText.includes(opt));
    const nextIndex = (currentIndex + 1) % options.length;

    this.querySelector(
      ".small"
    ).textContent = `Ordenar por: ${options[nextIndex]}`;
  });
}

// Ver detalles de alojamiento
document.querySelectorAll(".accommodation-card a").forEach((link) => {
  link.addEventListener("click", function (e) {
    e.preventDefault();
    const title = this.closest(".accommodation-card").querySelector(
      ".card-title"
    ).textContent;
    alert(`Abriendo detalles de: ${title}`);
  });
});

// Selector de huéspedes mejorado
const guestSelect = document.querySelector("select");
guestSelect.addEventListener("change", function () {
  console.log("Huéspedes seleccionados:", this.value);
});

// Input de destino con autocompletado (simulado)
const destinationInput = document.querySelector('input[placeholder*="dónde"]');
destinationInput.addEventListener("focus", function () {
  this.select();
});

destinationInput.addEventListener("input", function () {
  const suggestions = [
    "Madrid, España",
    "Barcelona, España",
    "Valencia, España",
    "Sevilla, España",
    "Bilbao, España",
  ];
  // Aquí podrías agregar un dropdown con sugerencias
  console.log("Buscando destinos:", this.value);
});
