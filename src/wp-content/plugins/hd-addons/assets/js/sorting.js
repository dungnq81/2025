jQuery(function($) {
  $("table.widefat tbody th, table.widefat tbody td").css("cursor", "move");
  const _helper = function(event, ui) {
    ui.each(function() {
      $(this).width($(this).width());
    });
    return ui;
  };
  const _start = function(event, ui) {
    ui.item.css("background-color", "#ffffff");
    ui.item.children("td, th").css("border-bottom-width", "0");
    ui.item.css("outline", "1px solid #dfdfdf");
  };
  const _stop = function(event, ui) {
    ui.item.removeAttr("style");
    ui.item.children("td,th").css("border-bottom-width", "1px");
  };
  const _sort = function(e, ui) {
    ui.placeholder.find("td").each(function(key, value) {
      if (ui.helper.find("td").eq(key).is(":visible")) {
        $(this).show();
      } else {
        $(this).hide();
      }
    });
  };
  $("table.posts #the-list, table.pages #the-list").sortable({
    items: "tr:not(.inline-edit-row)",
    cursor: "move",
    axis: "y",
    containment: "table.widefat",
    scrollSensitivity: 40,
    helper: _helper,
    start: _start,
    stop: _stop,
    update: function(event, ui) {
      $("table.widefat tbody th, table.widefat tbody td").css("cursor", "default");
      $("table.widefat tbody").sortable("disable");
      ui.item.find(".check-column input").hide().after('<img alt="processing" src="images/wpspin_light.gif" class="waiting" style="margin-left: 6px;" />');
      $.post(
        ajaxurl,
        {
          action: "update-menu-order",
          order: $("#the-list").sortable("serialize")
        },
        function(response) {
          ui.item.find(".check-column input").show().siblings("img").remove();
          $("table.widefat tbody th, table.widefat tbody td").css("cursor", "move");
          $("table.widefat tbody").sortable("enable");
        }
      );
      $("table.widefat tbody tr").each(function() {
        let i = $("table.widefat tbody tr").index(this);
        if (i % 2 === 0) {
          $(this).addClass("alternate");
        } else {
          $(this).removeClass("alternate");
        }
      });
    },
    sort: _sort
  });
  $("table.tags #the-list").sortable({
    items: "tr:not(.inline-edit-row)",
    cursor: "move",
    axis: "y",
    containment: "table.widefat",
    scrollSensitivity: 40,
    helper: _helper,
    start: _start,
    stop: _stop,
    update: function(event, ui) {
      $("table.widefat tbody th, table.widefat tbody td").css("cursor", "default");
      $("table.widefat tbody").sortable("disable");
      ui.item.find(".check-column input").hide().after('<img alt="processing" src="images/wpspin_light.gif" class="waiting" style="margin-left: 6px;" />');
      $.post(
        ajaxurl,
        {
          action: "update-menu-order-tags",
          order: $("#the-list").sortable("serialize")
        },
        function(response) {
          ui.item.find(".check-column input").show().siblings("img").remove();
          $("table.widefat tbody th, table.widefat tbody td").css("cursor", "move");
          $("table.widefat tbody").sortable("enable");
        }
      );
      $("table.widefat tbody tr").each(function() {
        let i = $("table.widefat tbody tr").index(this);
        if (i % 2 === 0) {
          $(this).addClass("alternate");
        } else {
          $(this).removeClass("alternate");
        }
      });
    },
    sort: _sort
  });
});
//# sourceMappingURL=sorting.js.map
