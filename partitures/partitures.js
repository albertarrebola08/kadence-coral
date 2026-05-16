jQuery(function ($) {
  const $form = $("#partitures-filtres");
  const $grid = $("#partitures-grid");
  const $loader = $("#partitures-loader");
  const $count = $("#partitures-count");

  let isLoading = false;
  let typingTimer = null;

  // modal PDF compartit per partitures i fitxes
  $(document).on("click", ".btn-preview", function (e) {
    e.preventDefault();
    const pdf = $(this).data("pdf");
    if (!pdf || !$("#pdf-modal").length) {
      return;
    }
    $("#pdf-modal iframe").attr("src", pdf);
    $("#pdf-modal").addClass("open").attr("aria-hidden", "false");
  });

  $(document).on("click", "#pdf-modal-close, #pdf-modal-backdrop", function () {
    $("#pdf-modal iframe").attr("src", "");
    $("#pdf-modal").removeClass("open").attr("aria-hidden", "true");
  });

  if (!$form.length || !$grid.length) {
    return;
  }

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

  // paginacio
  $(document).on("click", ".partitures-page-link[data-page]", function () {
    const page = parseInt($(this).data("page"), 10);
    if (!page || page < 1 || $(this).hasClass("is-active")) {
      return;
    }

    $form.find('input[name="paged"]').val(page);
    carregarPartitures(false);
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
