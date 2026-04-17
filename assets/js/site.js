(function () {
  function prefersReducedMotion() {
    return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  }

  function initReveal() {
    if (prefersReducedMotion()) {
      document.querySelectorAll(".reveal").forEach(function (el) {
        el.classList.add("visible");
      });
      return;
    }
    document.querySelectorAll(".reveal").forEach(function (el) {
      var obs = new IntersectionObserver(
        function (entries, o) {
          entries.forEach(function (en) {
            if (en.isIntersecting) {
              en.target.classList.add("visible");
              o.unobserve(en.target);
            }
          });
        },
        { threshold: 0.12 }
      );
      obs.observe(el);
    });
  }

  function animateCounters() {
    document.querySelectorAll("[data-count]").forEach(function (el) {
      var raw = el.getAttribute("data-count");
      var target = parseInt(raw, 10);
      if (Number.isNaN(target)) return;
      var prefix = el.getAttribute("data-prefix") || "";
      var suffix = el.getAttribute("data-suffix") || "";
      if (prefersReducedMotion()) {
        el.textContent = prefix + target + suffix;
        return;
      }
      var dur = 1100;
      var started = false;
      function run() {
        if (started) return;
        started = true;
        var t0 = performance.now();
        function frame(now) {
          var t = Math.min(1, (now - t0) / dur);
          var eased = 1 - (1 - t) * (1 - t);
          var val = Math.round(target * eased);
          el.textContent = prefix + val + suffix;
          if (t < 1) requestAnimationFrame(frame);
          else el.textContent = prefix + target + suffix;
        }
        requestAnimationFrame(frame);
      }
      var io = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (en) {
            if (en.isIntersecting) {
              run();
              io.disconnect();
            }
          });
        },
        { threshold: 0.15 }
      );
      io.observe(el);
    });
  }

  function initFaq() {
    document.querySelectorAll(".faq-q").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var panelId = btn.getAttribute("aria-controls");
        var panel = panelId ? document.getElementById(panelId) : null;
        var item = btn.closest(".faq-item");
        var expanded = btn.getAttribute("aria-expanded") === "true";
        var next = !expanded;
        btn.setAttribute("aria-expanded", next ? "true" : "false");
        if (panel) panel.hidden = !next;
        if (item) item.classList.toggle("open", next);
        var icon = btn.querySelector(".faq-icon");
        if (icon) icon.textContent = next ? "\u2212" : "+";
      });
    });
  }

  function initCountdown() {
    var root = document.getElementById("countdown");
    if (!root) return;
    root.setAttribute("role", "region");
    var launch = new Date("2026-05-20T00:00:00+05:30");
    function tr(k, fallback) {
      if (typeof window.getTranslation !== "function") return fallback;
      var v = window.getTranslation(k);
      return v != null && v !== "" ? v : fallback;
    }
    function aria() {
      root.setAttribute(
        "aria-label",
        tr(
          "waitlist.countdown_aria",
          "Shrutam launch countdown, May 20 2026. Values update on screen only."
        )
      );
    }
    aria();
    document.addEventListener("langChange", aria);
    function tick() {
      var now = new Date();
      var ms = launch - now;
      if (ms <= 0) {
        root.textContent = tr("common.cd_live", "Launch day!");
        return;
      }
      var s = Math.floor(ms / 1000);
      var days = Math.floor(s / 86400);
      var h = Math.floor((s % 86400) / 3600);
      var m = Math.floor((s % 3600) / 60);
      var sec = s % 60;
      var msg =
        days +
        " " +
        tr("common.cd_days", "days") +
        ", " +
        h +
        " " +
        tr("common.cd_hours", "hours") +
        ", " +
        m +
        " " +
        tr("common.cd_mins", "minutes") +
        ", " +
        sec +
        " " +
        tr("common.cd_secs", "seconds") +
        " " +
        tr("common.cd_until", "until launch");
      root.textContent = msg;
    }
    tick();
    var intervalMs = prefersReducedMotion() ? 60000 : 1000;
    setInterval(tick, intervalMs);
  }

  function initWaitlistLeadEvent() {
    var form = document.querySelector('form[action^="mailto:"]');
    if (!form) return;
    form.addEventListener("submit", function () {
      if (typeof window.gtag === "function") {
        window.gtag("event", "generate_lead", { method: "mailto" });
      }
    });
  }

  function initFaqDirectoryPage() {
    var root = document.querySelector(".faq-directory");
    if (!root) return;
    var search = document.getElementById("faq-page-search");
    var items = root.querySelectorAll(".faq-item[data-faq-cats]");
    function applyFilter() {
      var q = (search && search.value ? search.value : "").toLowerCase().trim();
      var cat = root.getAttribute("data-active-cat") || "all";
      items.forEach(function (item) {
        var cats = (item.getAttribute("data-faq-cats") || "").toLowerCase();
        var text = item.textContent.toLowerCase();
        var okCat = cat === "all" || cats.indexOf(cat) !== -1;
        var okSearch = !q || text.indexOf(q) !== -1;
        item.style.display = okCat && okSearch ? "" : "none";
      });
    }
    if (search) search.addEventListener("input", applyFilter);
    root.querySelectorAll("[data-faq-cat-btn]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var c = btn.getAttribute("data-faq-cat-btn") || "all";
        root.setAttribute("data-active-cat", c);
        root.querySelectorAll("[data-faq-cat-btn]").forEach(function (b) {
          b.classList.toggle(
            "active",
            (b.getAttribute("data-faq-cat-btn") || "all") === c
          );
        });
        applyFilter();
      });
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    initReveal();
    animateCounters();
    initFaq();
    initCountdown();
    initWaitlistLeadEvent();
    initFaqDirectoryPage();
  });
})();
