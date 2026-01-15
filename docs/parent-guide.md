# Parent Guide

## Parent dashboard

Visit `/parent`.

From here you can:
- Link child accounts
- See child progress, coins, recent completions
- Jump to Parent Controls for each child

## Linking a child account

In this starter build, linking uses:
- Child username
- Child email

This is simple and works well for demos and small installations.

For higher security on production, see `docs/technical.md` for a recommended “Parent Link Code” flow.

## Parent controls

Visit `/parent/settings`.

Controls can be set per child:

### Time limits

- Daily minutes limit (0 = no limit)
- Students are blocked from activities once the limit is reached

### Content restrictions

- Optional max age group
- Optional allowed categories
  - If you check any categories, only those categories will be available

### Weekly report

- Completed activities and coins earned per day (last 7 days)

