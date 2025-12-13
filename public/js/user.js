function obtenerRegistros() {
  fetch("../controllers/user.controller.php")
    .then((response) => response.json())
    .then((response) => {
      var content = "";
      response.datos.forEach((element) => {
        content += `
                    <tr>
                        <td>${element.id}</td>
                        <td>${element.nombre}</td>
                        <td>${element.correo}</td>
                        <td>${element.cedula}</td>
                        <td>${element.telefono}</td>
                        <td>${element.rol}</td>
                        <td>${element.estado}</td>
                        <td><button class="btn btn-success">Editar</button> 
                            <button class="btn btn-danger" onClick="eliminarUsuario(${element.id})">Eliminar</button>
                            </td>
                    </tr>`;
      });
      document.getElementById("registros").innerHTML = content;
    });
}

function eliminarUsuario(id) {
  fetch("../controllers/user.controller.php", {
    method: "DELETE",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ id: id }),
  })
    .then((response) => response.json())
    .then((response) => {
      if (response.respuesta == "ok") {
        obtenerRegistros();
      }
    });
}

obtenerRegistros();
