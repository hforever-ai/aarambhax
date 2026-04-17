/* Language switcher + data-i18n. Loads after translations.js (window.TRANSLATIONS). */
(function () {
  var STORAGE_KEY = "shrutam_lang";

  var LANG_HTML = {
    hi: "hi-IN",
    en: "en-IN",
    mr: "mr-IN",
    te: "te-IN",
  };

  /** Native names for the dropdown trigger (matches option labels) */
  var LANG_LABELS = {
    hi: "हिंदी",
    en: "English",
    mr: "मराठी",
    te: "తెలుగు",
  };

  function detectLanguage() {
    try {
      var saved = localStorage.getItem(STORAGE_KEY);
      if (saved && window.TRANSLATIONS && window.TRANSLATIONS[saved]) return saved;
    } catch (e) {}
    var b = (navigator.language || "hi").split("-")[0];
    if (b === "mr" || b === "te" || b === "en") return b;
    return "hi";
  }

  var currentLang = detectLanguage();

  function getTranslation(key, lang) {
    if (!window.TRANSLATIONS) return null;
    if (!lang) lang = currentLang;
    var parts = key.split(".");
    function walk(pack) {
      var cur = pack;
      for (var i = 0; i < parts.length; i++) {
        if (cur == null) return null;
        var p = parts[i];
        if (Array.isArray(cur)) {
          var idx = parseInt(p, 10);
          if (String(idx) !== p || idx < 0 || idx >= cur.length) return null;
          cur = cur[idx];
        } else if (typeof cur === "object") {
          if (!Object.prototype.hasOwnProperty.call(cur, p)) return null;
          cur = cur[p];
        } else return null;
      }
      return typeof cur === "string" ? cur : null;
    }
    var order = [lang, "en", "hi"];
    var seen = {};
    for (var j = 0; j < order.length; j++) {
      var L = order[j];
      if (!L || seen[L]) continue;
      seen[L] = true;
      var pack = window.TRANSLATIONS[L];
      if (!pack) continue;
      var t = walk(pack);
      if (t != null) return t;
    }
    return null;
  }

  function setHtmlLang(lang) {
    document.documentElement.lang = LANG_HTML[lang] || "hi-IN";
    document.documentElement.setAttribute("data-lang", lang);
  }

  function closeAllLangDropdowns() {
    document.querySelectorAll("[data-lang-dropdown]").forEach(function (wrap) {
      wrap.classList.remove("is-open");
      var menu = wrap.querySelector(".lang-dropdown__menu");
      var btn = wrap.querySelector(".lang-dropdown__btn");
      if (menu) menu.hidden = true;
      if (btn) btn.setAttribute("aria-expanded", "false");
    });
  }

  function openDropdown(wrap) {
    closeAllLangDropdowns();
    wrap.classList.add("is-open");
    var menu = wrap.querySelector(".lang-dropdown__menu");
    var btn = wrap.querySelector(".lang-dropdown__btn");
    if (menu) menu.hidden = false;
    if (btn) btn.setAttribute("aria-expanded", "true");
  }

  function updateLangDropdownLabels(lang) {
    var name = LANG_LABELS[lang] || lang;
    document.querySelectorAll("[data-lang-current]").forEach(function (el) {
      el.textContent = name;
    });
  }

  function applyTranslations(lang) {
    if (!window.TRANSLATIONS || !window.TRANSLATIONS[lang]) lang = "hi";
    currentLang = lang;
    try {
      localStorage.setItem(STORAGE_KEY, lang);
    } catch (e) {}
    setHtmlLang(lang);

    document.querySelectorAll("[data-i18n]").forEach(function (el) {
      var key = el.getAttribute("data-i18n");
      var text = getTranslation(key, lang);
      if (text == null) return;
      el.textContent = text;
    });

    document.querySelectorAll("[data-i18n-placeholder]").forEach(function (el) {
      var key = el.getAttribute("data-i18n-placeholder");
      var text = getTranslation(key, lang);
      if (text != null) el.setAttribute("placeholder", text);
    });

    document.querySelectorAll("[data-i18n-aria-label]").forEach(function (el) {
      var key = el.getAttribute("data-i18n-aria-label");
      var text = getTranslation(key, lang);
      if (text != null) el.setAttribute("aria-label", text);
    });

    document.querySelectorAll(".lang-btn").forEach(function (btn) {
      var code = btn.getAttribute("data-lang");
      var on = code === lang;
      btn.classList.toggle("active", on);
      btn.setAttribute("aria-pressed", on ? "true" : "false");
    });

    updateLangDropdownLabels(lang);

    document.dispatchEvent(
      new CustomEvent("langChange", { detail: { lang: lang } })
    );
  }

  function initLangDropdowns() {
    document.querySelectorAll("[data-lang-dropdown]").forEach(function (wrap) {
      var toggle = wrap.querySelector(".lang-dropdown__btn");
      var menu = wrap.querySelector(".lang-dropdown__menu");
      if (!toggle || !menu) return;
      menu.addEventListener("click", function (e) {
        e.stopPropagation();
      });
      toggle.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (menu.hidden) openDropdown(wrap);
        else closeAllLangDropdowns();
      });
    });
    document.addEventListener("click", function () {
      closeAllLangDropdowns();
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") closeAllLangDropdowns();
    });
  }

  window.applyTranslations = applyTranslations;
  window.getCurrentLang = function () {
    return currentLang;
  };
  window.getTranslation = getTranslation;

  document.addEventListener("DOMContentLoaded", function () {
    initLangDropdowns();
    document.querySelectorAll(".lang-btn").forEach(function (btn) {
      btn.addEventListener("click", function (e) {
        e.stopPropagation();
        var code = btn.getAttribute("data-lang");
        if (code) {
          applyTranslations(code);
          closeAllLangDropdowns();
        }
      });
    });
    applyTranslations(currentLang);
  });
})();
