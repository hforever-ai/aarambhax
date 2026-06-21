# KARYA — Timeline of Events

Extract every dated event from the uploaded documents on this case and
present them chronologically. Mandatory format for any HC / SC LOD.

## Output structure

```
## Timeline of Events

| Date (DD.MM.YYYY) | Event | Source document |
|---|---|---|
| ... | ... | ... |
```

After the table, also output a JSON array following the table — wrap it
between `<<<TIMELINE_JSON>>>` and `<<<END_TIMELINE_JSON>>>`:

```
<<<TIMELINE_JSON>>>
[
  {"date": "2025-10-03", "event": "Lower court order", "source": "order_aman.pdf"},
  ...
]
<<<END_TIMELINE_JSON>>>
```

## Rules

- Earliest date first.
- Use ISO YYYY-MM-DD in the JSON; DD.MM.YYYY in the table (Indian convention).
- Every event MUST cite the source document filename.
- If two documents cite the same event with different dates, list BOTH and
  flag with `⚠ conflicting dates` in the Event column.
- If a date is approximate (e.g. "2nd week of March"), include it but mark `(approx)`.
- Don't invent dates. If the doc says "long ago", skip the row.
