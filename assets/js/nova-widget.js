/**
 * Nova — on-site help widget (static replies). Swap findReply for an API when AI is ready.
 */
(function () {
  var STORAGE_OPEN = "nova_widget_open";
  var RUPEE = "\u20b9";

  var QUICK = [
    { label: "Launch kab hai?", q: "launch date" },
    { label: "Price?", q: "price kitna" },
    { label: "Waitlist", q: "waitlist" },
    { label: "Kaunse boards?", q: "boards" },
    { label: "SAAVI kya hai?", q: "saavi" },
    { label: "Contact / help", q: "contact" },
  ];

  /** @type {{ keys: string[], parts: ({ text: string, strong?: boolean, link?: string, label?: string, ext?: boolean } | { br: true })[] }[]} */
  var RULES = [
    {
      keys: ["launch", "kab", "date", "2026", "may", "rollout"],
      parts: [
        { text: "Shrutam " },
        { text: "May 20, 2026", strong: true },
        { text: " ko launch ho raha hai. Early perks ke liye " },
        { link: "/waitlist/", label: "waitlist" },
        { text: " join karo." },
      ],
    },
    {
      keys: ["price", "kitna", "cost", "paid", "free", "rupee", "199", "pro"],
      parts: [
        { text: "Roughly " },
        { text: "free tier + Pro (~" + RUPEE + "199/mo intro)", strong: true },
        { text: " — final launch pe confirm hoga. Details: " },
        { link: "/faq/", label: "FAQ" },
        { text: "." },
      ],
    },
    {
      keys: ["waitlist", "join", "reserve", "spot"],
      parts: [
        { text: "Yahan se spot book karo: " },
        { link: "/waitlist/", label: "Join waitlist →" },
      ],
    },
    {
      keys: ["board", "cbse", "cg", "chhattisgarh", "class"],
      parts: [
        { text: "Focus: " },
        { text: "CG Board + CBSE, Class 6–10", strong: true },
        { text: " (Science-heavy journey)." },
      ],
    },
    {
      keys: ["saavi", "didi", "teacher", "ai tutor", "hinglish"],
      parts: [
        { text: "", strong: false },
        { text: "SAAVI", strong: true },
        { text: " tumhari AI teacher didi hai — Hinglish, audio-first, bina judge kiye. " },
        { link: "/saavi/", label: "SAAVI page →" },
      ],
    },
    {
      keys: ["shrutam", "app", "download"],
      parts: [
        { text: "", strong: false },
        { text: "Shrutam", strong: true },
        { text: " hamara learning app hai. Overview: " },
        { link: "/shrutam/", label: "Shrutam →" },
        { text: " · Live app: " },
        { link: "https://shrutam.ai", label: "shrutam.ai", ext: true },
      ],
    },
    {
      keys: ["school", "partner", "b2b", "bulk"],
      parts: [
        { text: "School partnerships: " },
        { link: "/schools/", label: "Schools page" },
        { text: " ya " },
        { link: "mailto:schools@aarambhax.com", label: "schools@aarambhax.com", ext: true },
      ],
    },
    {
      keys: ["privacy", "data", "safe", "cookie"],
      parts: [
        { text: "Privacy summary: " },
        { link: "/privacy/", label: "Privacy policy →" },
      ],
    },
    {
      keys: ["hello", "hi", "hey", "namaste", "help"],
      parts: [
        { text: "Hey! Main Nova — quick help ke liye yahan hun. Neeche chips try karo ya sawal likho. Full list: " },
        { link: "/faq/", label: "FAQ" },
        { text: "." },
      ],
    },
    {
      keys: ["contact", "email", "mail", "reach", "human"],
      parts: [
        { text: "Humse connect: " },
        { link: "/contact/", label: "Contact page" },
        { text: " · " },
        { link: "mailto:hello@aarambhax.com", label: "hello@aarambhax.com", ext: true },
      ],
    },
    {
      keys: ["blog", "article", "read"],
      parts: [
        { text: "Guides yahan: " },
        { link: "/blog/", label: "Blog — Seekhte Rahenge →" },
      ],
    },
    {
      keys: ["internet", "offline", "net"],
      parts: [
        { text: "Live AI ke liye internet chahiye; offline notes direction app mein. " },
        { link: "/faq/", label: "FAQ" },
        { text: " dekho." },
      ],
    },
  ];

  var DEFAULT_PARTS = [
    { text: "Iska seedha jawab abhi knowledge base mein nahi hai — " },
    { link: "/faq/", label: "FAQ" },
    { text: " dekho ya " },
    { link: "/contact/", label: "contact" },
    { text: " karo. " },
    { text: "(Full AI yahan jald integrate hoga.)", em: true },
  ];

  function normalize(s) {
    return String(s || "")
      .toLowerCase()
      .replace(/[^\w\u0900-\u097F\s]/g, " ");
  }

  function findRule(text) {
    var n = normalize(text);
    if (!n.trim()) return null;
    for (var i = 0; i < RULES.length; i++) {
      var rule = RULES[i];
      for (var j = 0; j < rule.keys.length; j++) {
        if (n.indexOf(rule.keys[j]) !== -1) return rule.parts;
      }
    }
    return DEFAULT_PARTS;
  }

  function renderParts(parts, container) {
    for (var i = 0; i < parts.length; i++) {
      var p = parts[i];
      if (p.br) {
        container.appendChild(document.createElement("br"));
        continue;
      }
      if (p.link) {
        var a = document.createElement("a");
        a.href = p.link;
        a.textContent = p.label || p.link;
        if (p.ext) {
          a.rel = "noopener noreferrer";
          a.target = "_blank";
        }
        container.appendChild(a);
        continue;
      }
      if (p.text === "") continue;
      var tag = p.strong ? "strong" : p.em ? "em" : "span";
      var el = document.createElement(tag);
      el.textContent = p.text;
      container.appendChild(el);
    }
  }

  function init() {
    if (document.getElementById("nova-widget-root")) return;

    var root = document.createElement("div");
    root.id = "nova-widget-root";
    root.className = "nova-widget";

    var launcher = document.createElement("button");
    launcher.type = "button";
    launcher.className = "nova-launcher";
    launcher.id = "nova-launcher";
    launcher.setAttribute("aria-expanded", "false");
    launcher.setAttribute("aria-controls", "nova-panel");

    var launcherInner = document.createElement("span");
    launcherInner.className = "nova-launcher-inner";
    launcherInner.setAttribute("aria-hidden", "true");
    launcher.appendChild(launcherInner);

    var launcherLabel = document.createElement("span");
    launcherLabel.className = "nova-launcher-label";
    launcherLabel.textContent = "Nova";
    launcher.appendChild(launcherLabel);

    var panel = document.createElement("div");
    panel.className = "nova-panel";
    panel.id = "nova-panel";
    panel.setAttribute("role", "dialog");
    panel.setAttribute("aria-modal", "true");
    panel.setAttribute("aria-labelledby", "nova-title");
    panel.hidden = true;

    var head = document.createElement("div");
    head.className = "nova-panel-head";

    var headText = document.createElement("div");
    var title = document.createElement("div");
    title.className = "nova-title";
    title.id = "nova-title";
    title.textContent = "Talk to Nova";
    var sub = document.createElement("div");
    sub.className = "nova-sub";
    sub.textContent = "Quick help · AI upgrade coming";
    headText.appendChild(title);
    headText.appendChild(sub);

    var closeBtn = document.createElement("button");
    closeBtn.type = "button";
    closeBtn.className = "nova-close";
    closeBtn.id = "nova-close";
    closeBtn.setAttribute("aria-label", "Close chat");
    closeBtn.textContent = "\u00d7";

    head.appendChild(headText);
    head.appendChild(closeBtn);

    var messagesEl = document.createElement("div");
    messagesEl.className = "nova-messages";
    messagesEl.id = "nova-messages";
    messagesEl.setAttribute("aria-live", "polite");

    var quickEl = document.createElement("div");
    quickEl.className = "nova-quick";
    quickEl.id = "nova-quick";

    var form = document.createElement("form");
    form.className = "nova-form";
    form.id = "nova-form";
    form.setAttribute("autocomplete", "off");

    var label = document.createElement("label");
    label.setAttribute("for", "nova-input");
    label.className = "sr-only";
    label.textContent = "Message";

    var input = document.createElement("input");
    input.type = "text";
    input.id = "nova-input";
    input.className = "nova-input";
    input.placeholder = "Type a question\u2026";
    input.maxLength = 500;

    var sendBtn = document.createElement("button");
    sendBtn.type = "submit";
    sendBtn.className = "nova-send";
    sendBtn.textContent = "Send";

    form.appendChild(label);
    form.appendChild(input);
    form.appendChild(sendBtn);

    panel.appendChild(head);
    panel.appendChild(messagesEl);
    panel.appendChild(quickEl);
    panel.appendChild(form);

    root.appendChild(launcher);
    root.appendChild(panel);
    document.body.appendChild(root);

    function renderQuick() {
      quickEl.textContent = "";
      QUICK.forEach(function (item) {
        var b = document.createElement("button");
        b.type = "button";
        b.className = "nova-chip";
        b.textContent = item.label;
        b.addEventListener("click", function () {
          sendUser(item.q);
        });
        quickEl.appendChild(b);
      });
    }

    function appendUserBubble(text) {
      var wrap = document.createElement("div");
      wrap.className = "nova-msg nova-msg-user";
      var bubble = document.createElement("div");
      bubble.className = "nova-bubble";
      bubble.textContent = text;
      wrap.appendChild(bubble);
      messagesEl.appendChild(wrap);
      messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function appendNovaBubble(parts) {
      var wrap = document.createElement("div");
      wrap.className = "nova-msg nova-msg-nova";
      var bubble = document.createElement("div");
      bubble.className = "nova-bubble";
      renderParts(parts, bubble);
      wrap.appendChild(bubble);
      messagesEl.appendChild(wrap);
      messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function sendUser(text) {
      var t = String(text || "").trim();
      if (!t) return;
      appendUserBubble(t);
      appendNovaBubble(findRule(t));
    }

    function setOpen(open) {
      panel.hidden = !open;
      launcher.setAttribute("aria-expanded", open ? "true" : "false");
      try {
        if (open) sessionStorage.setItem(STORAGE_OPEN, "1");
        else sessionStorage.removeItem(STORAGE_OPEN);
      } catch (e) {}
      if (open) {
        input.focus();
        if (!messagesEl.dataset.seeded) {
          messagesEl.dataset.seeded = "1";
          appendNovaBubble([
            { text: "Hi! Main " },
            { text: "Nova", strong: true },
            {
              text: " — Aarambhax quick help. Puchho: launch, price, boards, SAAVI, waitlist\u2026",
            },
          ]);
        }
      }
    }

    launcher.addEventListener("click", function () {
      setOpen(panel.hidden);
    });
    closeBtn.addEventListener("click", function () {
      setOpen(false);
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && !panel.hidden) setOpen(false);
    });

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var v = input.value;
      input.value = "";
      sendUser(v);
    });

    renderQuick();

    try {
      if (sessionStorage.getItem(STORAGE_OPEN) === "1") setOpen(true);
    } catch (e) {}
  }

  document.addEventListener("DOMContentLoaded", init);
})();
