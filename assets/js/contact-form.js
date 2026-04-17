/**
 * Contact page: reCAPTCHA v3 or Enterprise + POST JSON to AARAMBHAX_CONTACT_API.
 * Set AARAMBHAX_RECAPTCHA_SITE, AARAMBHAX_CONTACT_API; for Enterprise also AARAMBHAX_RECAPTCHA_ENTERPRISE = true.
 */
(function () {
  function $(id) {
    return document.getElementById(id);
  }

  function showStatus(el, ok, text) {
    if (!el) return;
    el.className = "contact-form-status " + (ok ? "contact-form-status--ok" : "contact-form-status--err");
    el.textContent = text;
    el.hidden = false;
  }

  function loadRecaptcha(siteKey, enterprise) {
    return new Promise(function (resolve, reject) {
      if (enterprise) {
        if (window.grecaptcha && window.grecaptcha.enterprise) {
          window.grecaptcha.enterprise.ready(resolve);
          return;
        }
        var se = document.createElement("script");
        se.src =
          "https://www.google.com/recaptcha/enterprise.js?render=" +
          encodeURIComponent(siteKey);
        se.async = true;
        se.onload = function () {
          window.grecaptcha.enterprise.ready(resolve);
        };
        se.onerror = reject;
        document.head.appendChild(se);
        return;
      }
      if (window.grecaptcha && window.grecaptcha.execute) {
        window.grecaptcha.ready(resolve);
        return;
      }
      var s = document.createElement("script");
      s.src =
        "https://www.google.com/recaptcha/api.js?render=" +
        encodeURIComponent(siteKey);
      s.async = true;
      s.onload = function () {
        window.grecaptcha.ready(resolve);
      };
      s.onerror = reject;
      document.head.appendChild(s);
    });
  }

  function executeRecaptcha(siteKey, enterprise) {
    if (enterprise) {
      return window.grecaptcha.enterprise.execute(siteKey, {
        action: "contact_form",
      });
    }
    return window.grecaptcha.execute(siteKey, { action: "contact_form" });
  }

  document.addEventListener("DOMContentLoaded", function () {
    var form = $("contact-api-form");
    var statusEl = $("contact-form-status");
    var submitBtn = $("contact-submit");
    if (!form || !submitBtn) return;

    var siteKey = window.AARAMBHAX_RECAPTCHA_SITE || "";
    var apiUrl = window.AARAMBHAX_CONTACT_API || "";
    var enterprise = !!window.AARAMBHAX_RECAPTCHA_ENTERPRISE;

    if (!siteKey || !apiUrl) {
      showStatus(
        statusEl,
        false,
        "Form backend not configured yet. Email hello@aarambhax.com directly."
      );
      submitBtn.disabled = true;
      return;
    }

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      showStatus(statusEl, true, "Sending…");
      submitBtn.disabled = true;

      loadRecaptcha(siteKey, enterprise)
        .then(function () {
          return executeRecaptcha(siteKey, enterprise);
        })
        .then(function (token) {
          var typeSel = $("cf-type");
          var typeVal = typeSel ? typeSel.value : "other";
          var payload = {
            source: "contact_page",
            name: ($("cf-name") && $("cf-name").value) || "",
            email: ($("cf-email") && $("cf-email").value) || "",
            phone: ($("cf-phone") && $("cf-phone").value) || "",
            type: typeVal,
            subject: ($("cf-subject") && $("cf-subject").value) || "Website inquiry",
            message: ($("cf-message") && $("cf-message").value) || "",
            recaptcha_token: token,
          };
          return fetch(apiUrl, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
          });
        })
        .then(function (res) {
          return res.json().then(function (data) {
            return { ok: res.ok, data: data };
          });
        })
        .then(function (r) {
          if (r.data && r.data.success) {
            showStatus(
              statusEl,
              true,
              "Message sent. We will reply soon. Check your inbox for a confirmation."
            );
            form.reset();
            if (typeof window.gtag === "function") {
              window.gtag("event", "generate_lead", {
                method: "contact_form",
              });
            }
          } else {
            var msg =
              (r.data && r.data.message) || "Something went wrong. Try again or email us.";
            showStatus(statusEl, false, msg);
          }
        })
        .catch(function () {
          showStatus(
            statusEl,
            false,
            "Network error. Try again or email hello@aarambhax.com."
          );
        })
        .finally(function () {
          submitBtn.disabled = false;
        });
    });
  });
})();
