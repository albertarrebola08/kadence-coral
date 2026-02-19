jQuery(function ($) {
  const $form = $("#partitures-filtres");
  const $grid = $("#partitures-grid");
  const $loader = $("#partitures-loader");
  const $count = $("#partitures-count");

  let isLoading = false;
  let typingTimer = null;

  function carregarPartitures(reset = false) {
    if (isLoading) return;
    isLoading = true;

    if (reset) $form.find('input[name="paged"]').val(1);

    $loader && $loader.show();

    $.ajax({
      url: coralPartitures.ajaxurl,
      type: "POST",
      data: $form.serialize(),
      success: function (response) {
        $grid.html(response);

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

  function debounceReload() {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(function () {
      carregarPartitures(true);
    }, 250);
  }

  // INIT
  carregarPartitures(true);

  // inputs text
  $form.on("keyup", 'input[name="search"]', debounceReload);
  $form.on("keyup", 'input[name="numero"]', debounceReload);
  $form.on("keyup", 'input[name="llibre_txt"]', debounceReload);

  // ✅ ANY exact (input + change)
  $form.on("input", 'input[name="any"]', debounceReload);
  $form.on("change", 'input[name="any"]', function () {
    carregarPartitures(true);
  });

  // selects
  $form.on(
    "change",
    'select[name="genere"], select[name="tradicional"]',
    function () {
      carregarPartitures(true);
    },
  );

  // paginació
  $(document).on("click", ".pagination-next", function () {
    let page = parseInt($form.find('input[name="paged"]').val(), 10);
    $form.find('input[name="paged"]').val(page + 1);
    carregarPartitures(false);
  });

  $(document).on("click", ".pagination-prev", function () {
    let page = parseInt($form.find('input[name="paged"]').val(), 10);
    if (page > 1) {
      $form.find('input[name="paged"]').val(page - 1);
      carregarPartitures(false);
    }
  });

  // modal PDF
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

  // ordre A-Z (si el tornes a activar)
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
});
