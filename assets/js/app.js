const App = (function ($) {
  const API = "api.php";
  const ACCIONES_GET = ["todos", "uno"];
  const formatoMoneda = new Intl.NumberFormat("es-EC", {
    style: "currency",
    currency: "USD",
  });

  let modalConfirmar = null;
  let accionConfirmada = null;

  function api(modulo, accion, datos) {
    return $.ajax({
      url: API + "?" + $.param({ modulo: modulo, accion: accion }),
      method: ACCIONES_GET.includes(accion) ? "GET" : "POST",
      data: datos,
      dataType: "json",
    });
  }

  function mensaje($elemento, tipo, textos) {
    const lista = Array.isArray(textos) ? textos : [textos];

    $elemento
      .empty()
      .removeClass("d-none text-success text-danger")
      .addClass(tipo === "error" ? "text-danger" : "text-success");

    if (lista.length === 1) {
      $elemento.text(lista[0]);
    } else {
      $elemento.append(
        $('<ul class="mb-0 ps-3">').append(
          lista.map(function (texto) {
            return $("<li>").text(texto);
          }),
        ),
      );
    }
  }

  function mensajeError($elemento) {
    return function (xhr) {
      const respuesta = xhr.responseJSON || {};

      if (Array.isArray(respuesta.errores) && respuesta.errores.length > 0) {
        mensaje($elemento, "error", respuesta.errores);
      } else if (respuesta.error) {
        mensaje($elemento, "error", respuesta.error);
      } else if (xhr.status === 0) {
        mensaje($elemento, "error", "No se pudo conectar con el servidor.");
      } else {
        mensaje(
          $elemento,
          "error",
          "Ocurrió un error inesperado (" + xhr.status + ").",
        );
      }
    };
  }

  function confirmar(mensaje, alAceptar) {
    if (modalConfirmar === null) {
      modalConfirmar = new bootstrap.Modal(
        document.getElementById("modalConfirmar"),
      );
    }
    $("#modalConfirmarTexto").text(mensaje);
    accionConfirmada = alAceptar;
    modalConfirmar.show();
  }

  $(document).on("click", "#btnConfirmar", function () {
    const accion = accionConfirmada;
    accionConfirmada = null;
    modalConfirmar.hide();
    if (accion) {
      accion();
    }
  });

  function celda(valor, clases) {
    return $("<td>")
      .addClass(clases || "")
      .text(valor === null || valor === undefined ? "" : valor);
  }

  function filaVacia(columnas, mensaje) {
    return $("<tr>").append(
      $('<td class="text-center text-muted py-4">')
        .attr("colspan", columnas)
        .text(mensaje),
    );
  }

  function botonesAcciones(id) {
    return $(
      '<div class="btn-group btn-group-sm" role="group" aria-label="Acciones">',
    ).append(
      $(
        '<button type="button" class="btn btn-outline-primary btn-editar">Editar</button>',
      ).attr("data-id", id),
      $(
        '<button type="button" class="btn btn-outline-danger btn-eliminar">Eliminar</button>',
      ).attr("data-id", id),
    );
  }

  function moneda(valor) {
    return formatoMoneda.format(Number(valor) || 0);
  }

  function fecha(valor) {
    const partes = String(valor).split("-");
    return partes.length === 3
      ? partes[2] + "/" + partes[1] + "/" + partes[0]
      : valor;
  }

  function hoy() {
    const ahora = new Date();
    const mes = String(ahora.getMonth() + 1).padStart(2, "0");
    const dia = String(ahora.getDate()).padStart(2, "0");
    return ahora.getFullYear() + "-" + mes + "-" + dia;
  }

  function formularioValido($form) {
    const valido = $form[0].checkValidity();
    $form.addClass("was-validated");
    return valido;
  }

  function limpiarFormulario($form) {
    $form[0].reset();
    $form.removeClass("was-validated");
    $form.find('input[type="hidden"]').val("");
  }

  function modoEdicion(ids, editando, entidad) {
    $(ids.titulo).text((editando ? "Editar " : "Nuevo ") + entidad);
    $(ids.guardar).text(editando ? "Guardar cambios" : "Guardar");
    $(ids.cancelar).toggleClass("d-none", !editando);
  }

  return {
    api: api,
    mensaje: mensaje,
    mensajeError: mensajeError,
    confirmar: confirmar,
    celda: celda,
    filaVacia: filaVacia,
    botonesAcciones: botonesAcciones,
    moneda: moneda,
    fecha: fecha,
    hoy: hoy,
    formularioValido: formularioValido,
    limpiarFormulario: limpiarFormulario,
    modoEdicion: modoEdicion,
  };
})(jQuery);
