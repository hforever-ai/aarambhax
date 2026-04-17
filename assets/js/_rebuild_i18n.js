/* Merge extra keys into _i18n_patch.json and rebuild translations.js. Run: node _rebuild_i18n.js */
const fs = require("fs");
const path = require("path");
const dir = __dirname;

function deepMerge(base, over) {
  if (over == null) return base;
  if (Array.isArray(over)) return over;
  if (
    typeof over === "object" &&
    typeof base === "object" &&
    base !== null &&
    !Array.isArray(base)
  ) {
    const out = { ...base };
    for (const k of Object.keys(over)) {
      if (
        k in base &&
        typeof base[k] === "object" &&
        base[k] !== null &&
        !Array.isArray(base[k]) &&
        typeof over[k] === "object" &&
        over[k] !== null &&
        !Array.isArray(over[k])
      ) {
        out[k] = deepMerge(base[k], over[k]);
      } else {
        out[k] = over[k];
      }
    }
    return out;
  }
  return over;
}

const patchPath = path.join(dir, "_i18n_patch.json");
const patch = JSON.parse(fs.readFileSync(patchPath, "utf8"));

const homeEn = {
  how: {
    eyebrow: "How it works",
    title: "Three steps. One flow.",
    sub: "Listen. Understand. Learn. — a simple rhythm for every chapter.",
    steps: [
      {
        label: "Step 1",
        title: "Pick your chapter",
        desc: "Class, board, subject — open the chapter you need. CG Board or CBSE.",
      },
      {
        label: "Step 2",
        title: "SAAVI explains",
        desc: "Audio-first explainers in Hinglish — slow, clear, no judgment.",
      },
      {
        label: "Step 3",
        title: "Practice + quiz",
        desc: "Mock exams and quick checks — repeat until the concept clicks.",
      },
    ],
  },
  testi: {
    eyebrow: "Social proof",
    title: "Early access — this is the vibe",
    sub: "Straight from pilot students and teachers (names illustrative).",
    items: [
      {
        quote:
          "I used to get doubts at night — now I ask SAAVI didi. I'm not scared.",
        author: "— Class 9, Raipur",
      },
      {
        quote:
          "Hinglish works for me. The textbook felt dry — now it feels like a story.",
        author: "— Class 7, Bilaspur",
      },
      {
        quote:
          "Photo doubt solve is a game changer. I get direction in 30 seconds.",
        author: "— Class 10, Jagdalpur",
      },
    ],
  },
  press: {
    eyebrow: "Press & community",
    tag0: "Coming soon — local press & school partners",
    tag1: "CG Board focus",
    tag2: "EdTech India",
  },
  faq: {
    eyebrow: "FAQ",
    title: "Questions everyone asks",
    full_faq_link: "Full FAQ →",
    items: [
      {
        q: "What is Shrutam?",
        a: "Shrutam is an audio-first learning app — SAAVI AI guides you in CG Board / CBSE Class 6–10 Science, Maths, and English.",
      },
      {
        q: "Is it for Hindi medium students?",
        a: "Yes. Hinglish + Hindi focus — at your pace, without shame.",
      },
      {
        q: "What is the price?",
        a: "Pro is roughly \u20b9199/month (intro pricing). Waitlist may get early perks.",
      },
      {
        q: "When is launch?",
        a: "May 20, 2026 — exact rollout will be announced via waitlist and email.",
      },
    ],
  },
};

const homeHi = {
  how: {
    eyebrow: "\u0915\u0948\u0938\u0947 \u0915\u093e\u092e \u0915\u0930\u0924\u093e \u0939\u0948",
    title: "Teen step. Ek flow.",
    sub: "Suno. Samjho. Seekho. \u2014 har chapter ke liye simple rhythm.",
    steps: [
      {
        label: "Step 1",
        title: "Apna chapter chuno",
        desc: "Class, board, subject \u2014 jo tumhara chapter hai wahi kholo. CG Board ya CBSE, dono.",
      },
      {
        label: "Step 2",
        title: "SAAVI samjhayegi",
        desc: "Audio-first explainers, Hinglish mein \u2014 slow, clear, bina judge kiye.",
      },
      {
        label: "Step 3",
        title: "Practice + quiz",
        desc: "Mock exams aur quick checks \u2014 jab tak concept clear na ho, repeat karo.",
      },
    ],
  },
  testi: {
    eyebrow: "Social proof",
    title: "Abhi early access \u2014 yeh hai vibe",
    sub: "Pilot students & teachers se seedha feedback (names illustrative).",
    items: [
      {
        quote:
          "Raat ko doubt hota tha \u2014 ab SAAVI didi se pooch leta hoon. Dar nahi lagta.",
        author: "\u2014 Class 9, Raipur",
      },
      {
        quote:
          "Hinglish samajh aata hai. Textbook dry lagti thi \u2014 ab story jaise lagti hai.",
        author: "\u2014 Class 7, Bilaspur",
      },
      {
        quote:
          "Photo se doubt solve \u2014 game changer. 30 sec mein direction mil jaata hai.",
        author: "\u2014 Class 10, Jagdalpur",
      },
    ],
  },
  press: {
    eyebrow: "Press & community",
    tag0: "Coming soon \u2014 local press & school partners",
    tag1: "CG Board focus",
    tag2: "EdTech India",
  },
  faq: {
    eyebrow: "FAQ",
    title: "Sawal jo sab poochte hain",
    full_faq_link: "Saara FAQ →",
    items: [
      {
        q: "Shrutam kya hai?",
        a: "Shrutam audio-first learning app hai \u2014 SAAVI AI tumhe CG Board / CBSE Class 6\u201310 Science, Maths, English mein guide karti hai.",
      },
      {
        q: "Kya Hindi medium ke liye hai?",
        a: "Haan. Hinglish + Hindi focus \u2014 tumhari speed pe, bina shame ke.",
      },
      {
        q: "Price kya hai?",
        a: "Pro roughly \u20b9199/month (intro pricing). Waitlist pe kuch early perks mil sakte hain.",
      },
      {
        q: "Launch kab hai?",
        a: "May 20, 2026 \u2014 exact rollout waitlist + email se announce hoga.",
      },
    ],
  },
};

const blogEn = {
  eyebrow: "Blog",
  title: "Seekhte Rahenge",
  lead: "Guides for CG Board & CBSE \u2014 Class 10 Science strategy, Hindi-medium learning, and simple science explainers in Hinglish.",
  c1t: "CG Board Class 10 Science 2026",
  c1d: "Syllabus priorities, practicals, revision rhythm \u2014 one practical prep guide.",
  c2t: "SAAVI vs tuition",
  c2d: "Cost, language, judgment, time \u2014 honest comparison for Hindi medium students.",
  c3t: "Photosynthesis \u2014 Class 6 Hindi",
  c3d: "Prakash sanshleshan basics: CO\u2082, oxygen, leaves \u2014 simple Hinglish explainer.",
  read: "Read \u2192",
};

const blogHi = {
  eyebrow: "\u092c\u094d\u0932\u0949\u0917",
  title: "Seekhte Rahenge",
  lead: "CG Board aur CBSE ke liye guides \u2014 Class 10 Science strategy, Hindi medium learning, aur science Hinglish mein.",
  c1t: "CG Board Class 10 Science 2026",
  c1d: "Syllabus focus, practicals, revision \u2014 ek practical prep guide.",
  c2t: "SAAVI vs tuition",
  c2d: "Daam, bhasha, judgment, time \u2014 Hindi medium students ke liye seedhi tulna.",
  c3t: "Photosynthesis \u2014 Class 6 Hindi",
  c3d: "Prakash sanshleshan: CO\u2082, oxygen, patte \u2014 simple Hinglish explainer.",
  read: "Padho \u2192",
};

const demoEn = {
  eyebrow: "Demo",
  title: "Chat simulation",
  sub: "Type a message \u2014 sample reply (frontend only).",
  input_label: "Your question",
  input_placeholder: "What is photosynthesis?",
  hint: "After you send a sample question, SAAVI\u2019s reply appears below.",
  btn: "Send \u2192",
  empty: "Type something first.",
};

const demoHi = {
  eyebrow: "Demo",
  title: "Chat simulation",
  sub: "Message likho \u2014 sample reply (sirf frontend).",
  input_label: "Tumhara sawal",
  input_placeholder: "Photosynthesis kya hai?",
  hint: "Sample question bhejne ke baad neeche SAAVI ka jawab dikhega.",
  btn: "Bhejo \u2192",
  empty: "Pehle kuch likho.",
};

patch.en.home = deepMerge(patch.en.home || {}, homeEn);
patch.hi.home = deepMerge(patch.hi.home || {}, homeHi);
patch.en.blog_page = deepMerge(patch.en.blog_page || {}, blogEn);
patch.hi.blog_page = deepMerge(patch.hi.blog_page || {}, blogHi);
patch.en.shrutam = deepMerge(patch.en.shrutam || {}, { demo: demoEn });
patch.hi.shrutam = deepMerge(patch.hi.shrutam || {}, { demo: demoHi });

patch.en.about = deepMerge(patch.en.about || {}, {
  timeline_eyebrow: "Timeline",
  timeline_note: "Key milestones on the journey.",
});
patch.hi.about = deepMerge(patch.hi.about || {}, {
  timeline_eyebrow: "Timeline",
  timeline_note: "Chota sa journey map.",
});

patch.en.waitlist = deepMerge(patch.en.waitlist || {}, {
  form_note:
    "Submitting opens your email client. Required fields are marked in the form.",
});
patch.hi.waitlist = deepMerge(patch.hi.waitlist || {}, {
  form_note:
    "Form submit se tumhara email client khulega. Zaroori fields form mein mark kiye gaye hain.",
});

patch.en.common = deepMerge(patch.en.common || {}, { soon: "Soon" });
patch.hi.common = deepMerge(patch.hi.common || {}, {
  soon: "\u091c\u0932\u094d\u0926",
});

patch.mr.home = deepMerge(patch.mr.home || {}, {
  faq: { full_faq_link: "सर्व FAQ →" },
});
patch.te.home = deepMerge(patch.te.home || {}, {
  faq: { full_faq_link: "మొత్తం FAQ →" },
});

patch.mr.blog_page = deepMerge(patch.mr.blog_page || {}, {
  read: "वाचा →",
});
patch.te.blog_page = deepMerge(patch.te.blog_page || {}, {
  read: "\u0c1a\u0c26\u0c35\u0c02\u0c21\u0c3f \u2192",
});

fs.writeFileSync(patchPath, JSON.stringify(patch, null, 2), "utf8");

const T = JSON.parse(
  fs.readFileSync(path.join(dir, "_translations_raw.json"), "utf8")
);
for (const lang of ["hi", "en", "mr", "te"]) {
  T[lang] = deepMerge(T[lang], patch[lang] || {});
}
const wrapper =
  "/* Aarambhax i18n \u2014 spec + patches. */\n(function () {\n" +
  "  function merge(over, base) {\n" +
  "    if (over == null) return JSON.parse(JSON.stringify(base));\n" +
  '    if (typeof base === "object" && base !== null && !Array.isArray(base)) {\n' +
  "      var out = {};\n" +
  "      for (var k in base) {\n" +
  "        if (Object.prototype.hasOwnProperty.call(over, k)) {\n" +
  "          out[k] = merge(over[k], base[k]);\n" +
  "        } else {\n" +
  "          out[k] = base[k];\n" +
  "        }\n" +
  "      }\n" +
  "      for (var k2 in over) {\n" +
  "        if (!Object.prototype.hasOwnProperty.call(out, k2)) out[k2] = over[k2];\n" +
  "      }\n" +
  "      return out;\n" +
  "    }\n" +
  "    return over;\n" +
  "  }\n" +
  "  var RAW = " +
  JSON.stringify(T) +
  ";\n" +
  "  window.TRANSLATIONS = {\n" +
  "    hi: RAW.hi,\n" +
  "    en: RAW.en,\n" +
  "    mr: merge(RAW.mr, RAW.hi),\n" +
  "    te: merge(RAW.te, RAW.hi),\n" +
  "  };\n" +
  "})();\n";

fs.writeFileSync(path.join(dir, "translations.js"), wrapper, "utf8");
console.log("OK: _i18n_patch.json + translations.js");
