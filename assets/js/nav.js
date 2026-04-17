/* AARAMBHAX_MASTER_DESIGN — mobile nav, desktop company flyout, sticky scroll */
(function () {
  function $(sel, root) {
    return (root || document).querySelector(sel);
  }

  function closeAllNavFlyouts() {
    document.querySelectorAll("[data-nav-flyout]").forEach(function (fly) {
      var panel = fly.querySelector(".nav-flyout__panel");
      var btn = fly.querySelector(".nav-flyout__trigger");
      if (panel) panel.hidden = true;
      if (btn) btn.setAttribute("aria-expanded", "false");
      fly.classList.remove("is-open");
    });
  }

  function initNavFlyouts() {
    document.querySelectorAll("[data-nav-flyout]").forEach(function (fly) {
      var btn = fly.querySelector(".nav-flyout__trigger");
      var panel = fly.querySelector(".nav-flyout__panel");
      if (!btn || !panel) return;
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (window.closeLangDropdowns) window.closeLangDropdowns();
        var willOpen = panel.hidden;
        closeAllNavFlyouts();
        if (willOpen) {
          panel.hidden = false;
          btn.setAttribute("aria-expanded", "true");
          fly.classList.add("is-open");
        }
      });
      panel.addEventListener("click", function (e) {
        e.stopPropagation();
      });
      panel.querySelectorAll("a").forEach(function (a) {
        a.addEventListener("click", function () {
          closeAllNavFlyouts();
        });
      });
    });
    document.addEventListener("click", function () {
      closeAllNavFlyouts();
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") closeAllNavFlyouts();
    });
  }

  window.closeNavFlyouts = closeAllNavFlyouts;

  function initNav() {
    var nav = $("#main-nav");
    var hamburger = $("#nav-hamburger");
    var menu = $("#mobile-menu");
    var overlay = $("#menu-overlay");
    if (!nav || !hamburger || !menu || !overlay) return;

    var scrollLockY = 0;

    function openMenu() {
      scrollLockY = window.scrollY || window.pageYOffset || 0;
      document.body.classList.add("nav-open");
      document.body.style.position = "fixed";
      document.body.style.top = "-" + scrollLockY + "px";
      document.body.style.left = "0";
      document.body.style.right = "0";
      document.body.style.width = "100%";
      menu.classList.add("is-open");
      overlay.classList.add("is-visible");
      hamburger.classList.add("active");
      hamburger.setAttribute("aria-expanded", "true");
    }

    function closeMenu() {
      menu.classList.remove("is-open");
      overlay.classList.remove("is-visible");
      hamburger.classList.remove("active");
      hamburger.setAttribute("aria-expanded", "false");
      document.body.classList.remove("nav-open");
      document.body.style.position = "";
      document.body.style.top = "";
      document.body.style.left = "";
      document.body.style.right = "";
      document.body.style.width = "";
      window.scrollTo(0, scrollLockY);
    }

    function toggleMenu() {
      if (menu.classList.contains("is-open")) closeMenu();
      else openMenu();
    }

    hamburger.addEventListener("click", toggleMenu);
    overlay.addEventListener("click", closeMenu);
    menu.querySelectorAll("a").forEach(function (a) {
      a.addEventListener("click", function () {
        closeMenu();
      });
    });
    menu.querySelectorAll(".lang-dropdown__opt").forEach(function (btn) {
      btn.addEventListener("click", function () {
        closeMenu();
      });
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") closeMenu();
    });

    var lastScroll = 0;
    window.addEventListener(
      "scroll",
      function () {
        var y = window.scrollY || 0;
        nav.classList.toggle("nav-scrolled", y > 20);
        if (window.matchMedia("(min-width: 900px)").matches) {
          nav.classList.remove("nav-hidden");
          lastScroll = y;
          return;
        }
        if (y > lastScroll && y > 100) nav.classList.add("nav-hidden");
        else nav.classList.remove("nav-hidden");
        lastScroll = y;
      },
      { passive: true }
    );
  }

  function boot() {
    initNavFlyouts();
    initNav();
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();

