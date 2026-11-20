$(function () {
  const $form = $("#formReserva");
  const $tabla = $("#tablaReservas");
  const $turista = $("#reservaTurista");
  const $destino = $("#reservaDestino");
  const $fecha = $("#reservaFecha");
  const $personas = $("#reservaPersonas");
  const $mensaje = $("#mensajeReservas");
  const mostrarError = App.mensajeError($mensaje);
  const ids = {
    titulo: "#tituloFormReserva",
    guardar: "#btnGuardarReserva",
    cancelar: "#btnCancelarReserva",
  };

  function cargar() {
    App.api("reservas", "todos")
      .done(function (reservas) {
        $tabla.empty();
        $("#totalReservas").text(reservas.length);

        if (reservas.length === 0) {
          $tabla.append(App.filaVacia(7, "No hay reservas registradas."));
          return;
        }

        $.each(reservas, function (_, reserva) {
          $tabla.append(
            $("<tr>").append(
              App.celda(reserva.reserva_id, "text-muted"),
              $("<td>").append(
                $('<div class="fw-bold">').text(reserva.turista),
                $('<small class="text-muted">').text(reserva.email),
              ),
              $("<td>").append(
                $("<div>").text(reserva.destino),
                $('<small class="text-muted">').text(reserva.pais),
              ),
              App.celda(App.fecha(reserva.fecha_viaje)),
              App.celda(reserva.cantidad_personas, "text-center"),
              App.celda(App.moneda(reserva.total), "text-end fw-bold"),
              $('<td class="text-end">').append(
                App.botonesAcciones(reserva.reserva_id),
              ),
            ),
          );
        });
      })
      .fail(mostrarError);
  }

  function llenarSelect($select, textoInicial, items, crearOpcion) {
    const seleccionado = $select.val();

    $select.empty().append($('<option value="">').text(textoInicial));
    $.each(items, function (_, item) {
      $select.append(crearOpcion(item));
    });

    $select.val(seleccionado);
    if ($select.val() === null) {
      $select.val("");
    }
  }

  function cargarOpciones() {
    return $.when(App.api("turistas", "todos"), App.api("destinos", "todos"))
      .done(function (respuestaTuristas, respuestaDestinos) {
        llenarSelect(
          $turista,
          "Selecciona un turista",
          respuestaTuristas[0],
          function (turista) {
            return $("<option>")
              .val(turista.turista_id)
              .text(
                turista.apellido +
                  " " +
                  turista.nombre +
                  " (" +
                  turista.email +
                  ")",
              );
          },
        );

        llenarSelect(
          $destino,
          "Selecciona un destino",
          respuestaDestinos[0],
          function (destino) {
            return $("<option>")
              .val(destino.destino_id)
              .attr("data-costo", destino.costo)
              .text(
                destino.nombre +
                  " - " +
                  destino.pais +
                  " (" +
                  App.moneda(destino.costo) +
                  " c/u)",
              );
          },
        );

        actualizarTotal();
      })
      .fail(mostrarError);
  }

  function actualizarTotal() {
    const costo = Number($destino.find("option:selected").data("costo")) || 0;
    const personas = parseInt($personas.val(), 10) || 0;
    $("#reservaTotal").text(App.moneda(costo * personas));
  }

  function reiniciar() {
    App.limpiarFormulario($form);
    App.modoEdicion(ids, false, "reserva");
    actualizarTotal();
  }

  $destino.on("change", actualizarTotal);
  $personas.on("input change", actualizarTotal);

  $form.on("submit", function (evento) {
    evento.preventDefault();
    if (!App.formularioValido($form)) {
      return;
    }

    const accion = $("#reservaId").val() ? "actualizar" : "guardar";

    App.api("reservas", accion, $form.serialize())
      .done(function (respuesta) {
        App.mensaje($mensaje, "exito", respuesta.mensaje);
        reiniciar();
        cargar();
      })
      .fail(mostrarError);
  });

  $("#btnCancelarReserva").on("click", reiniciar);

  $tabla.on("click", ".btn-editar", function () {
    const id = $(this).data("id");

    $.when(cargarOpciones(), App.api("reservas", "uno", { id: id }))
      .done(function (_, respuestaReserva) {
        const reserva = respuestaReserva[0];

        $form.removeClass("was-validated");
        $("#reservaId").val(reserva.reserva_id);
        $turista.val(reserva.turista_id);
        $destino.val(reserva.destino_id);
        $fecha.val(reserva.fecha_viaje);
        $personas.val(reserva.cantidad_personas);
        actualizarTotal();
        App.modoEdicion(ids, true, "reserva");
        $form[0].scrollIntoView({ behavior: "smooth", block: "center" });
      })
      .fail(mostrarError);
  });

  $tabla.on("click", ".btn-eliminar", function () {
    const id = $(this).data("id");

    App.confirmar("¿Eliminar la reserva #" + id + "?", function () {
      App.api("reservas", "eliminar", { id: id })
        .done(function (respuesta) {
          App.mensaje($mensaje, "exito", respuesta.mensaje);
          if (String($("#reservaId").val()) === String(id)) {
            reiniciar();
          }
          cargar();
        })
        .fail(mostrarError);
    });
  });

  $(document).on("viajes:datos-actualizados", function () {
    cargarOpciones();
    cargar();
  });

  $("#tab-reservas").on("shown.bs.tab", cargarOpciones);

  $fecha.attr("min", App.hoy());
  cargarOpciones();
  cargar();
});
