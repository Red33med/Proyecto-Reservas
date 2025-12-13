document.getElementById("btn-guardar").addEventListener("click", function () {
  var formUsuario = document.getElementById("form-usuario");
  var data = new FormData(formUsuario);
  fetch("../controllers/user.controller.php", {
    method: "POST",
    body: data,
  })
    .then((response) => response.json())
    .then((response) => {
      console.log(response);
    });
});
