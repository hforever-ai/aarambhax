(function () {
  var gaId =
    typeof window.AARAMBHAX_GA4 === "string" ? window.AARAMBHAX_GA4.trim() : "";
  var clarityId =
    typeof window.AARAMBHAX_CLARITY === "string"
      ? window.AARAMBHAX_CLARITY.trim()
      : "";

  if (gaId) {
    window.dataLayer = window.dataLayer || [];
    window.gtag = function () {
      window.dataLayer.push(arguments);
    };
    window.gtag("js", new Date());
    window.gtag("config", gaId);

    var g = document.createElement("script");
    g.async = true;
    g.src = "https://www.googletagmanager.com/gtag/js?id=" + encodeURIComponent(gaId);
    document.head.appendChild(g);
  }

  if (clarityId) {
    (function (c, l, a, r, i, t, y) {
      c[a] =
        c[a] ||
        function () {
          (c[a].q = c[a].q || []).push(arguments);
        };
      t = l.createElement(r);
      t.async = 1;
      t.src = "https://www.clarity.ms/tag/" + i;
      y = l.getElementsByTagName(r)[0];
      y.parentNode.insertBefore(t, y);
    })(window, document, "clarity", "script", clarityId);
  }

  window.aarambhaxTrack = function (eventName, params) {
    if (typeof window.gtag !== "function" || !gaId) return;
    window.gtag("event", eventName, params || {});
  };
})();
