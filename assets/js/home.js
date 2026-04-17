/* Homepage: feature carousel dots + SAAVI demo (loads only on index). */
(function () {
  function initCarousel() {
    var track = document.querySelector(".home-feature-track");
    var dots = document.querySelectorAll(".home-carousel-dot");
    if (!track || !dots.length) return;

    function goTo(i) {
      var card = track.children[i];
      if (!card) return;
      track.scrollTo({ left: card.offsetLeft, behavior: "smooth" });
      dots.forEach(function (d, j) {
        var on = j === i;
        d.classList.toggle("is-active", on);
        if (on) d.setAttribute("aria-current", "true");
        else d.removeAttribute("aria-current");
      });
    }

    dots.forEach(function (dot, i) {
      dot.addEventListener("click", function () {
        goTo(i);
      });
    });

    var scrollTimer;
    track.addEventListener(
      "scroll",
      function () {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(function () {
          var cards = track.children;
          var best = 0;
          var bestDist = Infinity;
          var mid = track.scrollLeft + track.clientWidth / 2;
          for (var c = 0; c < cards.length; c++) {
            var el = cards[c];
            var cx = el.offsetLeft + el.offsetWidth / 2;
            var dist = Math.abs(cx - mid);
            if (dist < bestDist) {
              bestDist = dist;
              best = c;
            }
          }
          dots.forEach(function (d, j) {
            var on = j === best;
            d.classList.toggle("is-active", on);
            if (on) d.setAttribute("aria-current", "true");
            else d.removeAttribute("aria-current");
          });
        }, 60);
      },
      { passive: true }
    );
  }

  function initDemo() {
    var go = document.getElementById("home-demo-go");
    var input = document.getElementById("home-demo-in");
    var out = document.getElementById("home-demo-out");
    if (!go || !input || !out) return;

    var replies = [
      "Dekho! Paudha ek magic chef hai — dhoop + paani + CO2 se sugar aur oxygen. Samjha?",
      "Fraction = pizza ka hissa. 4 mein se 1 = 1/4. Easy!",
    ];

    function runDemo() {
      var t = input.value.trim();
      if (!t) {
        var emptyMsg =
          (typeof getTranslation === "function" &&
            getTranslation("shrutam.demo.empty")) ||
          "Pehle kuch likho.";
        out.textContent = emptyMsg;
        return;
      }
      out.textContent =
        "SAAVI: " + replies[Math.floor(Math.random() * replies.length)];
    }

    go.addEventListener("click", runDemo);
    input.addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        runDemo();
      }
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    initCarousel();
    initDemo();
  });
})();
