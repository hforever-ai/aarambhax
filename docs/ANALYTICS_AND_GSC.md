# Analytics (GA4 + Clarity) and Google Search Console

## Configure GA4 and Microsoft Clarity

1. Open [`assets/js/analytics-config.js`](../assets/js/analytics-config.js) (or copy from [`assets/js/analytics-config.example.js`](../assets/js/analytics-config.example.js)).
2. Set **`window.AARAMBHAX_GA4`** to your GA4 **Measurement ID** (`G-XXXXXXXXXX`).
3. Set **`window.AARAMBHAX_CLARITY`** to your Clarity **Project ID**.
4. Deploy the updated file. Empty string = that tool stays off (safe for staging).

Scripts load on **all** site HTML pages (including `404.html`).

### GA4- **Admin → Data streams → Web** — copy Measurement ID.
- In GA4 **Admin → Events**, mark **`generate_lead`** as a **conversion** if you want waitlist intent in conversion reports.
- **Waitlist note:** the waitlist form uses **`mailto:`**. The site fires `generate_lead` with `{ method: "mailto" }` on **submit**. That measures **intent**, not whether the user actually sent email. For true conversion counts, use a form backend or ESP later.
- Optional: link **Search Console** to this GA4 property (**Admin → Product links**) to surface query data in GA (thresholds apply).

### Microsoft Clarity

- Create a project at [Clarity](https://clarity.microsoft.com/).
- Enable **masking** for sensitive fields (waitlist: name, email, city). Verify recordings do not show raw PII.
- **AI summaries / heatmaps** are configured in the Clarity UI, not in code.

### Custom events from code

After page load, if GA4 is configured:

```js
window.aarambhaxTrack("my_event", { key: "value" });
```

Do not put PII in event parameters.

---

## Google Search Console (no extra script on the site)

GSC is **verification + monitoring**, not a tag.

1. Add a property in [Google Search Console](https://search.google.com/search-console):
   - **Domain** property for `aarambhax.com` (recommended — covers `www` and apex), **or**
   - **URL-prefix** `https://www.aarambhax.com/`
2. **Verify** using the method Google shows:
   - Domain: **DNS TXT** record at your registrar, or
   - URL-prefix: HTML file upload, **HTML tag** (meta on homepage), or DNS.
3. **Sitemaps →** submit: `https://www.aarambhax.com/sitemap.xml`  
   (`robots.txt` already references this URL.)
4. After a few days, check **Performance** (queries, impressions, CTR), **Page indexing**, and **Core Web Vitals**.

---

## Privacy

The privacy policy summarizes GA4 and Clarity. Keep it aligned if you add more trackers or change data flows. India counsel review is still recommended for regulated processing.
