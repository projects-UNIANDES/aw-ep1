$(function () {
  const $form = $("#formTurista");
  const $tabla = $("#tablaTuristas");
  const $mensaje = $("#mensajeTuristas");
  const mostrarError = App.mensajeError($mensaje);
  const ids = {
    titulo: "#tituloFormTurista",
    guardar: "#btnGuardarTurista",
    cancelar: "#btnCancelarTurista",
  };

  function cargar() {
    App.api("turistas", "todos")
      .done(function (turistas) {
        $tabla.empty();
        $("#totalTuristas").text(turistas.length);

        if (turistas.length === 0) {
          $tabla.append(App.filaVacia(6, "No hay turistas registrados."));
          return;
        }

        $.each(turistas, function (_, turista) {
          $tabla.append(
            $("<tr>").append(
              App.celda(turista.turista_id, "text-muted"),
              App.celda(turista.nombre, "fw-bold"),
              App.celda(turista.apellido),
              App.celda(turista.email),
              App.celda(turista.telefono),
              $('<td class="text-end">').append(
                App.botonesAcciones(turista.turista_id),
              ),
            ),
          );
        });
      })
      .fail(mostrarError);
  }

  function reiniciar() {
    App.limpiarFormulario($form);
    App.modoEdicion(ids, false, "turista");
  }

  function notificarCambio() {
    $(document).trigger("viajes:datos-actualizados");
  }

  $form.on("submit", function (evento) {
    evento.preventDefault();
    if (!App.formularioValido($form)) {
      return;
    }

    const accion = $("#turistaId").val() ? "actualizar" : "guardar";

    App.api("turistas", accion, $form.serialize())
      .done(function (respuesta) {
        App.mensaje($mensaje, "exito", respuesta.mensaje);
        reiniciar();
        cargar();
        notificarCambio();
      })
      .fail(mostrarError);
  });

  $("#btnCancelarTurista").on("click", reiniciar);

  $tabla.on("click", ".btn-editar", function () {
    App.api("turistas", "uno", { id: $(this).data("id") })
      .done(function (turista) {
        $form.removeClass("was-validated");
        $("#turistaId").val(turista.turista_id);
        $("#turistaNombre").val(turista.nombre);
        $("#turistaApellido").val(turista.apellido);
        $("#turistaEmail").val(turista.email);
        $("#turistaTelefono").val(turista.telefono);
        App.modoEdicion(ids, true, "turista");
        $form[0].scrollIntoView({ behavior: "smooth", block: "center" });
        $("#turistaNombre").trigger("focus");
      })
      .fail(mostrarError);
  });

  $tabla.on("click", ".btn-eliminar", function () {
    const id = $(this).data("id");
    const celdas = $(this).closest("tr").children();
    const nombre = celdas.eq(1).text() + " " + celdas.eq(2).text();

    App.confirmar('¿Eliminar al turista "' + nombre + '"?', function () {
      App.api("turistas", "eliminar", { id: id })
        .done(function (respuesta) {
          App.mensaje($mensaje, "exito", respuesta.mensaje);
          if (String($("#turistaId").val()) === String(id)) {
            reiniciar();
          }
          cargar();
          notificarCambio();
        })
        .fail(mostrarError);
    });
  });

  cargar();
});
