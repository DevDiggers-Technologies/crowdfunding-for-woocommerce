# Crowdfunding for WooCommerce – Fundraising Campaigns, Reward Tiers & Donations

| | |
|---|---|
| **Version** | 1.1.0 |
| **Requires** | WordPress 6.9+, WooCommerce 9.0+, PHP 7.4+ |
| **Tested up to** | WordPress 7.1, WooCommerce 11.3 |
| **License** | GPLv3 or later |
| **Text domain** | `devdiggers-crowdfunding-for-woocommerce` |

**Free Demo:** https://demo.devdiggers.com/crowdfunding-for-woocommerce/

**Pro Demo:** https://demo.devdiggers.com/woocommerce-crowdfunding/

Run crowdfunding and donation campaigns on your own WooCommerce store. Each campaign is a hidden WooCommerce product with a goal, an end date and optional reward tiers. Supporters pay through the normal checkout, and a contribution counts toward the goal once its order is paid.

## Features

- **Campaigns:** goal, end date or both, fixed or "choose your amount" contributions with a minimum, cover image and story.
- **Rewards:** as many tiers per campaign as you need, each with a minimum amount, quantity limit and estimated delivery.
- **Campaign page:** progress card, funded percentage, supporters, days left, live countdown, organiser, Story/Rewards/Supporters tabs and share buttons.
- **Giving:** quick pick amounts, maximum contribution, anonymous giving, public messages, gateway restriction, close on goal.
- **Supporters:** supporters wall and a Backed Campaigns page in My Account.
- **Admin:** dashboard with KPIs and trend chart, Campaigns and Contributions lists, setup wizard.
- **Emails:** thank you, new contribution, goal reached and campaign ended, each with an editable subject, heading and message, through the WooCommerce mailer.
- **Shortcodes:** `[ddwccf_campaigns]` and `[ddwccf_campaign id="123"]`.

### AI assistants (MCP)

Ask Claude, ChatGPT, Cursor, VS Code or any MCP client about your campaigns in plain words: "How is my crowdfunding doing this month?", "Which campaigns will miss their goal?", "Who backed the Library Books Drive?"

- Tools for an overview (raised, contributions, supporters, success rate against the previous period), campaigns with their progress (raised against goal, backers, days left), one campaign with its reward tiers, the backers of a campaign and the campaigns ending soon with their pace
- A Campaign performance review prompt
- Optional: let the assistant extend a campaign's end date or enable / disable a campaign, logged under WooCommerce > Status > Logs
- Same access as the Crowdfunding screens: store managers only
- Managed in DevDiggers Plugins > AI & MCP: one MCP server for every DevDiggers plugin, an on / off switch per plugin and per write tool (write tools start off), a Try panel and an activity log
- Needs WordPress 6.9+ (Abilities API) and the MCP Adapter, which WooCommerce 10.3+ bundles

Pro adds customer campaigns with approval, platform fees and payouts, all or nothing funding with refunds, stretch goals, campaign updates, categories, filters, embeds, CSV export and six more emails. See [readme.txt](readme.txt) for the full Free vs Pro list.

## Setup

1. Activate WooCommerce, then this plugin. The setup wizard opens.
2. Create a campaign under **Crowdfunding > Campaigns > Add Campaign**.
3. Add `[ddwccf_campaigns]` to a page. Campaigns are hidden from the shop catalog.

## Technical notes

- Campaign data lives in product meta `_ddwccf_event_data`, with sortable mirrors in `_ddwccf_collected` and `_ddwccf_end_date`.
- Contributions are counted on `woocommerce_order_status_processing|completed` and reversed on `cancelled|refunded|failed`. Orders are tagged with `_ddwccf_event_order` so lists work before payment, with HPOS.
- One daily cron (`ddwccf_daily_check`) sends the Campaign Ended email.
- MCP abilities live in `includes/integrations/mcp-integration.php` under the `crowdfunding/` namespace. Add, change or remove one with the `ddwccf_mcp_abilities` filter.
- No REST routes and no custom tables. The plugin's own code makes no outside calls; the bundled framework's two opt-in DevDiggers connections are listed under External services in readme.txt.

### Hooks

| Hook | Type | Purpose |
|---|---|---|
| `ddwccf_configuration` | filter | Change the loaded settings array |
| `ddwccf_is_event_closed` | filter | Decide whether a campaign accepts contributions |
| `ddwccf_campaign_tabs` | filter | Campaign page tabs |
| `ddwccf_event_data_before_save` | filter | Campaign data before it is saved |
| `ddwccf_mcp_abilities` | filter | Add, change or remove the AI assistant (MCP) tools |
| `ddwccf_event_saved` | action | After a campaign is saved |
| `ddwccf_contribution_counted` | action | A paid contribution was counted |
| `ddwccf_event_goal_reached` | action | A campaign reached its goal |

## Development

```bash
npm install
npm run build                                   # webpack, framework text domain, i18n
php -d zend.assertions=1 tests/framework-smoke.php   # logic and Free/Pro boundary check
npm run build:release                           # zip from .distignore
```

`src/` holds the LESS and JavaScript sources. `assets/` is the compiled output.

## Support

- Documentation: https://docs.devdiggers.com/woocommerce-crowdfunding/
- Support forum: https://wordpress.org/support/plugin/devdiggers-crowdfunding-for-woocommerce/
- Pro: https://devdiggers.com/product/woocommerce-crowdfunding/

## Changelog

See the Changelog section in [readme.txt](readme.txt).
