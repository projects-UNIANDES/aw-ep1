$(function () {
  const $form = $("#formDestino");
  const $tabla = $("#tablaDestinos");
  const $mensaje = $("#mensajeDestinos");
  const mostrarError = App.mensajeError($mensaje);
  const ids = {
    titulo: "#tituloFormDestino",
    guardar: "#btnGuardarDestino",
    cancelar: "#btnCancelarDestino",
  };

  function cargar() {
    App.api("destinos", "todos")
      .done(function (destinos) {
        $tabla.empty();
        $("#totalDestinos").text(destinos.length);

        if (destinos.length === 0) {
          $tabla.append(App.filaVacia(6, "No hay destinos registrados."));
          return;
        }

        $.each(destinos, function (_, destino) {
          $tabla.append(
            $("<tr>").append(
              App.celda(destino.destino_id, "text-muted"),
              App.celda(destino.nombre, "fw-bold"),
              App.celda(destino.pais),
              App.celda(
                destino.descripcion,
                "text-truncate col-descripcion",
              ).attr("title", destino.descripcion || ""),
              App.celda(App.moneda(destino.costo), "text-end"),
              $('<td class="text-end">').append(
                App.botonesAcciones(destino.destino_id),
              ),
            ),
          );
        });
      })
      .fail(mostrarError);
  }

  function reiniciar() {
    App.limpiarFormulario($form);
    App.modoEdicion(ids, false, "destino");
  }

  function notificarCambio() {
    $(document).trigger("viajes:datos-actualizados");
  }

  $form.on("submit", function (evento) {
    evento.preventDefault();
    if (!App.formularioValido($form)) {
      return;
    }

    const accion = $("#destinoId").val() ? "actualizar" : "guardar";

    App.api("destinos", accion, $form.serialize())
      .done(function (respuesta) {
        App.mensaje($mensaje, "exito", respuesta.mensaje);
        reiniciar();
        cargar();
        notificarCambio();
      })
      .fail(mostrarError);
  });

  $("#btnCancelarDestino").on("click", reiniciar);

  $tabla.on("click", ".btn-editar", function () {
    App.api("destinos", "uno", { id: $(this).data("id") })
      .done(function (destino) {
        $form.removeClass("was-validated");
        $("#destinoId").val(destino.destino_id);
        $("#destinoNombre").val(destino.nombre);
        $("#destinoPais").val(destino.pais);
        $("#destinoDescripcion").val(destino.descripcion || "");
        $("#destinoCosto").val(destino.costo);
        App.modoEdicion(ids, true, "destino");
        $form[0].scrollIntoView({ behavior: "smooth", block: "center" });
        $("#destinoNombre").trigger("focus");
      })
      .fail(mostrarError);
  });

  $tabla.on("click", ".btn-eliminar", function () {
    const id = $(this).data("id");
    const nombre = $(this).closest("tr").children().eq(1).text();

    App.confirmar('¿Eliminar el destino "' + nombre + '"?', function () {
      App.api("destinos", "eliminar", { id: id })
        .done(function (respuesta) {
          App.mensaje($mensaje, "exito", respuesta.mensaje);
          if (String($("#destinoId").val()) === String(id)) {
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
