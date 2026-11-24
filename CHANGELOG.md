# Changelog

All notable changes to DevDiggers Crowdfunding for WooCommerce.

## 1.1.0 - 2026-11-24

Your campaigns can now be read by AI assistants, and the bundled DevDiggers framework moves to 1.3.0.

### New

- **AI assistants (MCP):** ask Claude, ChatGPT, Cursor, VS Code or any MCP client about your campaigns in plain words: an overview with period comparison, campaigns with their progress against goal, one campaign with its reward tiers, the backers of a campaign and the campaigns ending soon with their pace, plus a Campaign performance review prompt.
- **AI write tools:** optionally let the assistant extend a campaign's end date or enable or disable a campaign. They stay off until you allow them, and every change is logged under WooCommerce > Status > Logs.
- **DevDiggers Plugins > AI & MCP:** connect Claude, ChatGPT, Claude Code, Codex, Cursor, VS Code or any MCP client by signing in and clicking Allow in WordPress, with nothing to copy. An on and off switch per plugin, an "Allow changes" switch per write tool, a Try panel and an activity log with filters.

### Improved

- **DevDiggers framework 1.3.0:** the title bar with Save stays visible on edit screens while you scroll on desktop, and admin charts and pickers keep working when a theme or plugin replaces the shared Select2 script.

## 1.0.0 - 2026-10-17

First release on WordPress.org.

### New

- **Campaigns:** run crowdfunding and donation campaigns with a goal, an end date or both, a cover image and a story.
- **Flexible giving:** a fixed contribution, or supporters choose their own amount above a minimum, with quick pick amounts and an optional maximum.
- **Reward tiers:** as many per campaign as you need, each with a minimum amount, a quantity limit and an estimated delivery date. Sold out tiers switch off on their own. The "No reward" choice can be removed or renamed.
- **Campaign page:** progress card with amount raised, funded percentage, supporters, days left, a live countdown and the organiser.
- **Campaign tabs:** Story, Rewards and Supporters replace the usual product tabs.
- **Supporters wall:** recent supporters with their message, with anonymous giving for people who prefer it.
- **Share buttons:** Facebook, X, WhatsApp, LinkedIn, email and copy link. No tracking scripts.
- **Backed Campaigns:** a page in My Account where supporters follow every campaign they backed.
- **Dashboard:** amount raised, contributions, supporters, average contribution and success rate, with a trend chart, top campaigns, recent contributions and campaigns ending soon.
- **Campaigns and Contributions lists** with search, sorting, filters and bulk actions.
- **Emails:** thank you to the supporter, new contribution, goal reached and campaign ended, each with its own switch and an editable subject, heading and message.
- **Shortcodes:** `[ddwccf_campaigns]` for a campaigns grid and `[ddwccf_campaign id="123"]` for one campaign card.
- **Setup wizard** that gets a new store ready in a few steps.

### Compatibility

- Works with the classic and block checkout and with WooCommerce High Performance Order Storage (HPOS).
- Contributions only count once their order is paid, and come off again if it is cancelled, refunded or fails.
