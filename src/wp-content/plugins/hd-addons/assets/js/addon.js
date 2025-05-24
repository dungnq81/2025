import { s as select2, $ } from "./_vendor.js";
select2();
document.addEventListener("DOMContentLoaded", () => {
  if (typeof codemirror_settings !== "undefined") {
    let initializeCodeMirror = function(elements, settings, type) {
      elements.forEach((el) => {
        if (!el.CodeMirror) {
          let editorSettings = settings ? { ...settings } : {};
          editorSettings.codemirror = {
            ...editorSettings.codemirror,
            indentUnit: 3,
            tabSize: 3,
            autoRefresh: true
          };
          el.CodeMirror = wp.codeEditor.initialize(el, editorSettings);
        }
      });
    };
    const codemirror_css = document.querySelectorAll(".codemirror_css");
    const codemirror_html = document.querySelectorAll(".codemirror_html");
    initializeCodeMirror(codemirror_css, codemirror_settings.codemirror_css);
    initializeCodeMirror(codemirror_html, codemirror_settings.codemirror_html);
  }
  const select2_multiple = $(".select2-multiple");
  $.each(select2_multiple, function(i, el) {
    $(el).select2({
      multiple: true,
      allowClear: true,
      width: "resolve",
      dropdownAutoWidth: true,
      placeholder: $(el).attr("placeholder")
    });
  });
  const select2_tags = $(".select2-tags");
  $.each(select2_tags, function(i, el) {
    $(el).select2({
      multiple: true,
      tags: true,
      allowClear: true,
      width: "resolve",
      dropdownAutoWidth: true,
      placeholder: $(el).attr("placeholder")
    });
  });
  const select2_ips = $(".select2-ips");
  $.each(select2_ips, function(i, el) {
    $(el).select2({
      multiple: true,
      tags: true,
      allowClear: true,
      width: "resolve",
      dropdownAutoWidth: true,
      placeholder: $(el).attr("placeholder"),
      createTag: function(params) {
        let term = $.trim(params.term);
        if (isValidIPRange(term)) {
          return {
            id: term,
            text: term
          };
        } else {
          return null;
        }
      }
    });
  });
  const select2_emails = $(".select2-emails");
  $.each(select2_emails, function(i, el) {
    $(el).select2({
      multiple: true,
      tags: true,
      allowClear: true,
      width: "resolve",
      dropdownAutoWidth: true,
      placeholder: $(el).attr("placeholder"),
      createTag: function(params) {
        let term = $.trim(params.term);
        if (isValidEmail(term)) {
          return {
            id: term,
            text: term
          };
        } else {
          return null;
        }
      }
    });
  });
});
jQuery(function($2) {
  $2(".addon-color-field").wpColorPicker();
  $2.fn.fadeOutAndRemove = function(speed) {
    return this.fadeOut(speed, function() {
      $2(this).remove();
    });
  };
  $2.fn.serializeObject = function() {
    let obj = {};
    let array = this.serializeArray();
    $2.each(array, function() {
      let name = this.name;
      let value = this.value || "";
      if (name.indexOf("[]") > -1) {
        name = name.replace("[]", "");
        if (!obj[name]) {
          obj[name] = [];
        }
        obj[name].push(value);
      } else {
        if (obj[name] !== void 0) {
          if (!Array.isArray(obj[name])) {
            obj[name] = [obj[name]];
          }
          obj[name].push(value);
        } else {
          obj[name] = value;
        }
      }
    });
    return obj;
  };
  $2(document).on("submit", "#_settings_form", function(e) {
    e.preventDefault();
    let $this = $2(this);
    let $data = $this.serializeObject();
    let btn_submit = $this.find('button[name="_submit_settings"]');
    let button_text = btn_submit.html();
    let button_text_loading = '<span class="ajax-loader">&nbsp;</span>';
    btn_submit.prop("disabled", true).html(button_text_loading);
    $2.ajax({
      type: "POST",
      url: ajaxurl,
      data: {
        action: "submit_settings",
        _data: $data,
        _ajax_nonce: $this.find('input[name="_wpnonce"]').val(),
        _wp_http_referer: $this.find('input[name="_wp_http_referer"]').val()
      }
    }).done(function(data) {
      $this.find("#_content").prepend(data);
      if (!window.location.hash || window.location.hash === "#global_setting_settings" || window.location.hash === "#custom_css_settings" || window.location.hash === "#custom_script_settings" || window.location.hash === "#custom_sorting_settings" && $data["order_reset"] !== void 0 || window.location.hash === "#base_slug_settings" && $data["base_slug_reset"] !== void 0) {
        window.location.reload();
      }
      setTimeout(() => {
        var _a, _b;
        (_b = (_a = $this.find("#_content")) == null ? void 0 : _a.find(".dismissible-auto")) == null ? void 0 : _b.fadeOutAndRemove(400);
      }, 4e3);
    }).always(function() {
      btn_submit.prop("disabled", false).html(button_text);
    });
  });
  const $filterTabs = $2(".filter-tabs");
  $filterTabs.each(function() {
    const $el = $2(this), $nav = $el.find(".tabs-nav"), $content = $el.find(".tabs-content"), $tabs = $nav.find("a"), initialHash = window.location.hash;
    const activateTab = (hash) => {
      const $tab = $nav.find(`a[href="${hash}"]`);
      $nav.find("a").removeClass("current");
      $content.find(".tabs-panel").hide();
      if ($tab.length) {
        $tab.addClass("current");
        $2(hash).show();
      } else {
        $tabs.first().addClass("current");
        $content.find(".tabs-panel").first().show();
        window.history.replaceState(null, null, window.location.pathname + window.location.search);
      }
    };
    activateTab(initialHash || $tabs.first().attr("href"));
    $nav.on("click", "a", function(e) {
      e.preventDefault();
      const hash = $2(this).attr("href");
      window.location.hash = hash;
      activateTab(hash);
      $2("html, body").animate({ scrollTop: $el.offset().top - $2("header").outerHeight() }, 300);
    });
    $2(window).on("hashchange", function() {
      activateTab(window.location.hash || $tabs.first().attr("href"));
    });
  });
});
function isValidEmail(email) {
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailPattern.test(email);
}
function isValidIPRange(range) {
  const ipPattern = /^(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})$/;
  const rangePattern = /^(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})-(\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5])$/;
  const cidrPattern = /^(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\/(\d|[1-2]\d|3[0-2])$/;
  if (ipPattern.test(range)) {
    return true;
  }
  if (rangePattern.test(range)) {
    const [startIP, endRange] = range.split("-");
    const endIP = startIP.split(".").slice(0, 3).join(".") + "." + endRange;
    return compareIPs(startIP, endIP) < 0;
  }
  return cidrPattern.test(range);
}
function compareIPs(ip1, ip2) {
  const ip1Parts = ip1.split(".").map(Number);
  const ip2Parts = ip2.split(".").map(Number);
  for (let i = 0; i < 4; i++) {
    if (ip1Parts[i] < ip2Parts[i]) return -1;
    if (ip1Parts[i] > ip2Parts[i]) return 1;
  }
  return 0;
}
//# sourceMappingURL=addon.js.map
