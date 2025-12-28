jQuery(function ($) {
  const $form = $("#partitures-filtres");
  const $grid = $("#partitures-grid");
  const $loader = $("#partitures-loader");
  const $count = $("#partitures-count");

  let isLoading = false;

  /* =========================
     AJAX LOAD
  ========================= */
  function carregarPartitures(reset = false) {
    if (isLoading) return;
    isLoading = true;

    if (reset) {
      $form.find('input[name="paged"]').val(1);
    }

    $loader && $loader.show();

    $.ajax({
      url: coralPartitures.ajaxurl,
      type: "POST",
      data: $form.serialize(),
      success: function (response) {
        const page = parseInt($form.find('input[name="paged"]').val());

        if (page === 1) {
          $grid.html(response);
        } else {
          $grid.append(response);
        }

        // Comptador de resultats
        if ($count) {
          const total = $grid.find(".partitura-card").length;
          $count.text(total + " resultats");
        }
      },
      error: function () {
        $grid.html("<p>Error carregant les partitures.</p>");
      },
      complete: function () {
        isLoading = false;
        $loader && $loader.hide();
      },
    });
  }

  /* =========================
     INIT
  ========================= */
  carregarPartitures(true);

  /* =========================
     CERCA (OBRA / AUTOR)
  ========================= */
  $form.on("keyup", 'input[name="search"]', function () {
    carregarPartitures(true);
  });

  /* =========================
     FILTRES (TAXONOMIES)
  ========================= */
  $form.on("change", "select", function () {
    carregarPartitures(true);
  });

  /* =========================
     PAGINACIÓ AJAX
  ========================= */
  $(document).on("click", ".pagination-next", function () {
    let page = parseInt($form.find('input[name="paged"]').val());
    $form.find('input[name="paged"]').val(page + 1);
    carregarPartitures();
  });

  $(document).on("click", ".pagination-prev", function () {
    let page = parseInt($form.find('input[name="paged"]').val());
    if (page > 1) {
      $form.find('input[name="paged"]').val(page - 1);
      carregarPartitures(true);
    }
  });

  /* =========================
     MODAL PDF
  ========================= */
  $(document).on("click", ".btn-preview", function (e) {
    e.preventDefault();
    const pdf = $(this).data("pdf");
    $("#pdf-modal iframe").attr("src", pdf);
    $("#pdf-modal").addClass("open");
  });

  $("#pdf-modal-close, #pdf-modal-backdrop").on("click", function () {
    $("#pdf-modal iframe").attr("src", "");
    $("#pdf-modal").removeClass("open");
  });

  $("#btn-order-title").on("click", function () {
    const $orderInput = $form.find('input[name="order"]');
    const current = $orderInput.val();

    if (current === "ASC") {
      $orderInput.val("DESC");
      $(this).text("Z–A");
    } else {
      $orderInput.val("ASC");
      $(this).text("A–Z");
    }

    $form.find('input[name="orderby"]').val("title");

    carregarPartitures(true);
  });
}); //end listener
