
function nitIsValid(nit) {
  if (!nit) {
    return true;
  }

  const nitRegExp = new RegExp("^[0-9]+(-?[0-9kK])?$");

  if (!nitRegExp.test(nit)) {
    return false;
  }

  nit = nit.replace(/-/, "");
  const lastChar = nit.length - 1;
  const number = nit.substring(0, lastChar);
  const expectedCheker = nit.substring(lastChar, lastChar + 1).toLowerCase();

  let factor = number.length + 1;
  let total = 0;

  for (let i = 0; i < number.length; i++) {
    const character = number.substring(i, i + 1);
    const digit = parseInt(character, 10);

    total += digit * factor;
    factor = factor - 1;
  }

  const modulus = (11 - (total % 11)) % 11;
  const computedChecker = modulus == 10 ? "k" : modulus.toString();

  return expectedCheker === computedChecker;
}

jQuery(document).ready(function ($) {
  const spinner = wp.utils.spinner("#spinner");
  const $generateDialog = $("#dialog-generate-invoice");
  const $voidDialog = $("#digifact-void-modal");

  $("#customer-nit").bind("change paste keyup", function (e) {
    const $this = $(this);
    const $parent = $this.parent();
    const nit = $this.val();

    if (nit && nitIsValid(nit)) {
      $parent.removeClass("has-error");
    } else {
      $parent.addClass("has-error");
    }
  });

  /*-------------------------------------------------------------------------------
   * Generate Invoice Dialog
   *-------------------------------------------------------------------------------*/
  const generateDialog = $generateDialog.dialog({
    autoOpen: false,
    modal: true,
    width: 500,
    resizable: false,
    draggable: false,
    dialogClass: "generate-invoice-dialog",
    close: function () {
      $("#customer-nit").val("").parent().removeClass("has-error");
      $("#generate-invoice-btn").removeAttr("disabled");
      spinner.hide();
      $("#generate-invoice-form").get(0).reset();
      $("#order-id").val("");
      $("#customer-nit").val("");
      $("#customer-email").val("");
    },
    buttons: [
      {
        text: "Cancelar",
        class: "button-secondary",
        click: function () {
          $(this).dialog("close");
        },
      },
      {
        id: "generate-invoice-btn",
        text: "Generar factura",
        class: "button-primary",
        click: function () {
          spinner.show();
          $("#generate-invoice-btn").attr("disabled", "disabled");
          $("#generate-invoice-form").submit();
        },
      },
    ],
  });

  // delegate click event to table rows
  $("table.orders").on("click", ".generate_invoice", function (e) {
    e.preventDefault();

    const orderId = $(this).data("order-id");
    const nit = $(this).data("nit") || "";
    const email = $(this).data("email") || "";

    // Load data in dialog
    $("#order-id").val(orderId);
    $("#customer-nit").val(nit);
    $("#customer-email").val(email);
    $("#order-id--label").text(orderId);

    // open dialog
    generateDialog.dialog("open");
  });

  // Submit generate invoice form
  $("#generate-invoice-form").on("submit", function (e) {
    e.preventDefault();

    const orderId = $("#order-id").val();
    const nit = $("#customer-nit").val();
    const email = $("#customer-email").val();

    // Send data via AJAX
    $.post(ajaxurl, {
      action: "process_generate_invoice",
      order_id: orderId,
      customer_nit: nit,
      customer_email: email,
    })
      .done(function (response) {
        const { success, data } = response;
        if (success) {
          alert(data.message);
          location.reload();
        }
      })
      .fail(function (response) {
        const { data } = response.responseJSON;
        alert(data.message);
      })
      .always(function () {
        // Hide the modal after sending
        $generateDialog.dialog("close");
      });
  });

  /*-------------------------------------------------------------------------------
   * Void Invoice Dialog
   *-------------------------------------------------------------------------------*/

  const voidDialog = $voidDialog.dialog({
    autoOpen: false,
    width: 500,
    modal: true,
    buttons: [
      {
        id: "void-invoice-btn",
        text: "Enviar",
        class: "button-primary",
        click: function () {
          spinner.show();
          $("#void-invoice-btn").attr("disabled", "disabled");
          $("#void-form").submit();
        },
      },
      {
        id: "cancel-void-invoice-btn",
        text: "Cancelar",
        class: "button-secondary",
        click: function () {
          $(this).dialog("close");
        },
      },
    ],
    close: function () {
      $("#void-invoice-btn").removeAttr("disabled");
      $("#void-form")[0].reset();
      $("#invoice-id").val("");
      spinner.hide();
    },
  });

  $("#digifact-invoices-table").on("click", ".void-invoice", function (e) {
    e.preventDefault();
    const invoiceId = $(this).data("invoice-id");

    $("#void-form").find("#invoice-id").val(invoiceId);

    $voidDialog.dialog("open");
  });

  // Submit void invoice form
  $("#void-form").on("submit", function (e) {
    e.preventDefault();
    $("#void-invoice-btn").attr("disabled", "disabled");
    spinner.show();

    const invoiceId = $("#invoice-id", this).val();
    const reason = $("#void-form textarea#reason").val().trim();

    // Send data via AJAX
    $.post(ajaxurl, {
      action: "process_void_invoice",
      invoice_id: invoiceId,
      reason: reason,
    })
      .done(function (response) {
        console.log(response);
        const { success, data } = response;
        if (success == true) {
          alert(data);
          location.reload();
        } else {
          alert(`No se pudo anular la factura: ${data}`);
          spinner.hide();
        }
      })
      .fail(function (response) {
        console.log(response);
        const { data } = response;
        alert(`No se pudo anular la factura: ${data}`);
      })
      .always(function () {
        // Hide the modal after sending
        $voidDialog.dialog("close");
        $("#void-invoice-btn").removeAttr("disabled");
        spinner.hide();
      });
  });
});
