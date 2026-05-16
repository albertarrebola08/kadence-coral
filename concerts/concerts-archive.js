jQuery(function ($) {
  const $archive = $("#concerts-archive");
  if (!$archive.length) {
    return;
  }

  if (typeof coralConcertsArchive === "undefined") {
    return;
  }

  const $form = $("#concerts-archive-filters");
  const $results = $("#concerts-archive-results");
  let loading = false;

  function getPage() {
    return Math.max(1, parseInt($form.find('input[name="concert_page"]').val(), 10) || 1);
  }

  function setPage(page) {
    $form.find('input[name="concert_page"]').val(Math.max(1, parseInt(page, 10) || 1));
  }

  function getYear() {
    return $form.find('select[name="any"]').val() || "tots";
  }

  function setLoading(state) {
    if (state) {
      $results.addClass("is-loading");
    } else {
      $results.removeClass("is-loading");
    }
  }

  function syncAddressBar(page, any) {
    if (!window.history || !history.replaceState) {
      return;
    }

    let url;
    try {
      url = new URL(coralConcertsArchive.base_url);
    } catch (error) {
      return;
    }
    if (any && any !== "tots") {
      url.searchParams.set("any", any);
    } else {
      url.searchParams.delete("any");
    }

    if (page > 1) {
      url.searchParams.set("concert_page", page);
    } else {
      url.searchParams.delete("concert_page");
    }

    history.replaceState({}, "", url.toString());
  }

  function carregarConcerts() {
    if (loading) {
      return;
    }

    const any = getYear();
    const concert_page = getPage();

    loading = true;
    setLoading(true);

    $.ajax({
      url: coralConcertsArchive.ajaxurl,
      type: "POST",
      data: {
        action: "coral_get_concerts_archive",
        nonce: coralConcertsArchive.nonce,
        any: any,
        concert_page: concert_page,
        per_page: coralConcertsArchive.per_page,
        archive_url: coralConcertsArchive.base_url,
      },
      success: function (response) {
        if (!response || !response.success) {
          $results.html("<p class=\"concerts-archive-empty\">No s’han pogut actualitzar els concerts.</p>");
          return;
        }

        $results.html(response.data.html);
        setPage(response.data.currentPage);
        syncAddressBar(response.data.currentPage, response.data.any);
      },
      error: function () {
        $results.html("<p class=\"concerts-archive-empty\">Error carregant els concerts.</p>");
      },
      complete: function () {
        loading = false;
        setLoading(false);
      },
    });
  }

  // Filtrat automàtic d'any
  $form.on("change", "#concert-any", function () {
    setPage(1);
    carregarConcerts();
  });

  // Paginació AJAX
  $results.on("click", ".concerts-pagination a[data-page]", function (e) {
    e.preventDefault();
    const page = parseInt($(this).data("page"), 10);
    if (!page || page < 1) {
      return;
    }
    setPage(page);
    carregarConcerts();
  });

  // Reset: torna a "tots els anys" sense recàrrega
  $(".concerts-filter-reset", $form).on("click", function (e) {
    e.preventDefault();
    $form.find('select[name="any"]').val("tots");
    setPage(1);
    carregarConcerts();
  });
});
