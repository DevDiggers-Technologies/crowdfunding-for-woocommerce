=== DevDiggers Crowdfunding for WooCommerce ===
Contributors: devdiggers
Plugin URI: https://github.com/DevDiggers-Technologies/crowdfunding-for-woocommerce
Author: DevDiggers
Author URI: https://devdiggers.com/
Tags: crowdfunding, fundraising, donation, kickstarter, backer
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
WC requires at least: 9.0
WC tested up to: 11.2
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Kickstarter style crowdfunding and donation campaigns for WooCommerce, with goals, reward tiers, a live progress bar and a backer wall.

== Description ==

You want to raise money for a project, a cause or a product that does not exist yet. A donation button does not show how close you are, and a separate fundraising platform takes a cut and sends your supporters somewhere else.

**DevDiggers Crowdfunding for WooCommerce** turns any campaign into a page on your own store. Set a goal and an end date, add reward tiers, and supporters pay through your normal WooCommerce checkout, so every payment gateway you already use keeps working. The campaign page shows the money raised, a progress bar, a countdown and everyone who backed it.

There is no platform fee, no outside service and no account to create. Contributions are ordinary WooCommerce orders in your own database.

= Quick links =

* [Documentation](https://docs.devdiggers.com/woocommerce-crowdfunding/)
* [Support](https://wordpress.org/support/plugin/devdiggers-crowdfunding-for-woocommerce/)
* [Source code on GitHub](https://github.com/DevDiggers-Technologies/crowdfunding-for-woocommerce)
* [Pro Demo](https://demo.devdiggers.com/woocommerce-crowdfunding/)
* [Upgrade to Pro](https://devdiggers.com/product/woocommerce-crowdfunding/)

= Who it is for =

* Schools, clubs and charities collecting money for a project
* Stores that pre-sell a new product and only build it once enough people back it
* Creators who want a campaign page on their own site instead of a third party platform
* Anyone who wants a donation page that shows real progress

= How it works =

1. Create a campaign under **Crowdfunding > Campaigns**: a story, a cover image, a goal, an end date and up to three reward tiers.
2. Put `[ddwccf_campaigns]` on a page, or link straight to the campaign.
3. Supporters pick a reward, choose an amount and pay through your WooCommerce checkout.
4. As soon as the order is paid, the contribution counts toward the goal. If the order is cancelled, refunded or fails, it comes off again.

= What the free plugin does =

=== Campaign page ===

* Amount raised, goal and a progress bar at the top of the product page
* Funded percentage, supporter count and days left
* A live countdown to the end date
* The organiser's name and avatar
* Story, Rewards and Supporters tabs in place of the usual product tabs
* Share buttons for Facebook, X, WhatsApp, LinkedIn, email and copy link
* Your own accent color for the progress bar and highlights

=== Giving ===

* Fixed contributions, or supporters choose the amount with quick pick buttons such as $10, $25, $50 and $100
* A minimum per campaign and an optional maximum per contribution
* Up to three reward tiers per campaign, each with a minimum amount, an optional quantity limit and an estimated delivery date. Limited rewards show how many are left and switch to Sold out on their own.
* Anonymous giving that hides the name from the public supporters wall
* A short public message of support
* Limit which payment gateways are offered when the cart holds a contribution
* Close a campaign automatically once it reaches its goal, or let it keep raising until the end date

=== Supporters ===

* A supporters wall with recent supporters, amounts and messages. It shows first name and last initial only.
* A **Backed Campaigns** page in My Account where supporters follow every campaign they backed

=== Admin ===

* **Dashboard**: amount raised, contributions, supporters, average contribution and success rate with period comparison, a trend chart, campaign status, top campaigns, recent contributions and campaigns ending soon
* **Campaigns**: progress, supporters, end date and status for every campaign, with search, sorting and bulk enable, disable and trash
* **Contributions**: every contribution with its order status, reward, message and whether it counts toward the goal, filterable by campaign
* **Setup wizard** that gets a new store ready in a few steps

=== Emails ===

Four notifications, each with its own on and off switch, using your WooCommerce email design:

* Thank you to the supporter
* New contribution to the organiser
* Goal reached
* Campaign ended

=== Shortcodes ===

* `[ddwccf_campaigns]` shows a grid of campaign cards, with options for columns, limit, order, status, pagination and hand picked IDs
* `[ddwccf_campaign id="123"]` shows the card of one campaign

= Free vs Pro =

The free plugin is a complete way to run your own campaigns. [Pro](https://devdiggers.com/product/woocommerce-crowdfunding/) turns your store into a fundraising platform where customers run campaigns too. Inside the free plugin you can see each Pro feature where it would sit, marked PRO.

=== Customer campaigns (Pro) ===

* Customers start and manage their own campaigns from My Account, with a sectioned editor and image upload.
* Approve new campaigns before they go live, exclude roles and limit how long a campaign can run.
* Creators see their supporters and request a payout once their campaign closes.

=== Payouts and fees (Pro) ===

* A platform fee as a percentage or fixed amount, with an override per campaign.
* "Cover the fee" so supporters can add the fee on top and the creator gets the full gift.
* Payout requests you mark as paid or reject, or pay straight into the creator's DevDiggers Wallet.

=== Kickstarter style funding (Pro) ===

* **All or nothing** campaigns that only release the money when the goal is met, with one click refunds of every supporter when it is missed.
* **Stretch goals** marked on the progress bar.
* **Campaign updates** in an Updates tab, emailed to every supporter in the background.
* **Unlimited** reward tiers.

=== Discovery and sharing (Pro) ===

* A filterable campaigns page with search, category, status and sort filters.
* Campaign categories and featured campaigns.
* An embed code that puts a live campaign card on other websites.
* Share button shape, style, size and label options.

=== Management (Pro) ===

* A Needs Attention list for approvals, payouts and refunds.
* CSV export of campaigns and contributions.
* A full editor for all ten email notifications, including campaign submitted, approved, update and payout emails.

== Installation ==

= Automatic installation =

1. In your WordPress admin, go to **Plugins > Add New**.
2. Search for "DevDiggers Crowdfunding for WooCommerce".
3. Click **Install Now**, then **Activate**.

= Manual installation =

1. Download the plugin zip.
2. Go to **Plugins > Add New > Upload Plugin** and upload the zip.
3. Activate the plugin.

= After activating =

The setup wizard opens. Then create your first campaign under **Crowdfunding > Campaigns > Add Campaign** and add `[ddwccf_campaigns]` to a page, since campaigns are hidden from the shop.

= Requirements =

* WordPress 6.5 or later
* WooCommerce 9.0 or later
* PHP 7.4 or later

== Frequently Asked Questions ==

= Which payment gateways work? =

All of them. Contributions go through the normal WooCommerce checkout, so any gateway your store uses accepts them. You can limit which gateways are offered for contributions under Configuration > Contributions.

= When does a contribution count toward the goal? =

When its order is paid, which means Processing or Completed. It is removed again if the order is cancelled, refunded or fails, and an order is never counted twice.

= Are campaigns shown in my shop? =

No. Campaigns are hidden from the shop and search, so they never mix with your products. Use the `[ddwccf_campaigns]` shortcode to list them on a page.

= Can supporters give anonymously? =

Yes, when you allow it. Their name is hidden from the public supporters wall, while you still see who gave.

= Can supporters choose how much to give? =

Yes. Choose "Supporters choose the amount" on a campaign and set a minimum. Quick pick amounts are set under Configuration > Contributions.

= How many reward tiers can a campaign have? =

Up to three in the free plugin. Pro has no limit.

= Can customers create their own campaigns? =

That is part of Pro, together with approvals, platform fees and creator payouts. In the free plugin you create every campaign from the admin and can name any user as its organiser.

= Does it work with the block checkout and HPOS? =

Yes. It works with the classic and block checkout and with WooCommerce High Performance Order Storage (HPOS).

= Will my campaigns be kept if I upgrade to Pro? =

Yes. Pro uses the same campaigns, contributions and settings, so nothing has to be set up again.

= Can I translate the plugin? =

Yes. Every string is translatable, and a `.pot` file ships in the `i18n` folder.

== Screenshots ==

1. Campaign page with progress, countdown, rewards and share buttons.
2. Contribution form with reward picker and quick amounts.
3. Supporters wall with messages.
4. Admin dashboard with KPIs and the contributions trend.
5. Campaigns list with progress and status.
6. Campaign editor with funding and reward tiers.
7. Contributions list with order status and counted badges.
8. Backed Campaigns in My Account.

== External services ==

The plugin itself sends no data to any outside service. Campaigns and contributions are stored in your WordPress database, and notifications go out through the WooCommerce mailer. It contains no licence checks, no update checks and no telemetry.

**1. Gravatar (avatar images)**

* What it is: Gravatar, the avatar service operated by Automattic, used through WordPress core's `get_avatar()`.
* What it is used for: Showing the organiser's avatar on the campaign page. This is standard WordPress behaviour.
* When data is sent: When a campaign page is viewed and avatars are enabled under **Settings > Discussion**. Turning avatars off there stops the requests.
* What data is sent: A hash of the organiser's email address inside the image URL, plus the usual request data of the visitor's browser such as IP address and user agent.
* Terms of Service: https://automattic.com/terms/
* Privacy Policy: https://automattic.com/privacy/

**2. Share buttons**

The share buttons are plain links. Nothing is loaded from the networks until a visitor clicks one, which opens that network's share page in a new tab with the campaign title and link. No tracking scripts are added.

* Facebook: https://www.facebook.com/privacy/policy/
* X: https://x.com/en/privacy
* WhatsApp: https://www.whatsapp.com/legal/privacy-policy
* LinkedIn: https://www.linkedin.com/legal/privacy-policy

The bundled DevDiggers framework, which draws the admin screens, connects to the two services below. Both run only inside the WordPress admin.

**3. DevDiggers extensions directory**

* What it is: A read only API on devdiggers.com that returns the public list of DevDiggers extensions.
* What it is used for: Showing available DevDiggers extensions on the Extensions admin page.
* When data is sent: Only when a logged in administrator opens the Extensions admin page. The response is cached for 24 hours.
* What data is sent: A standard outbound HTTP request only, meaning your server's IP address and a plugin user agent string. No personal data and no store data are sent. The page also loads each listed extension's image from devdiggers.com in the administrator's browser.
* Endpoint: https://devdiggers.com/wp-json/ddwcs/v1/plugins

**4. Newsletter subscription (optional)**

* What it is: A contact and newsletter endpoint on devdiggers.com.
* What it is used for: Adding your email address to the DevDiggers newsletter, only if you choose to subscribe.
* When data is sent: Only when an administrator submits the optional newsletter form in the plugin dashboard. Nothing is sent automatically.
* What data is sent: The email address you enter and your site URL.
* Endpoint: https://devdiggers.com/?fluentcrm=1&route=contact

The DevDiggers services are provided by DevDiggers. By using them you agree to the DevDiggers Terms and Conditions (https://devdiggers.com/terms-and-conditions/) and Privacy Policy (https://devdiggers.com/privacy-policy/).

== Source code and build tools ==

The admin and storefront JavaScript and CSS in `assets/` are compiled from the human readable source in the `src/` folder, which ships with the plugin. The full source, including the build configuration, is also published on GitHub:

https://github.com/DevDiggers-Technologies/crowdfunding-for-woocommerce

To build the assets from source:

1. Run `npm install` to install the build dependencies listed in `package.json`.
2. Run `npm run build` to compile the production assets into `assets/js` and `assets/css`.

The build configuration is in `webpack.config.js` and `babel.config.js`. Two third party libraries used by the admin screens ship unmodified under the MIT licence inside `devdiggers-framework/assets/js/`: Chart.js (https://www.chartjs.org) and Select2 (https://select2.org).

== Changelog ==

= 1.0.0 - 2026-10-17 =
* New: Crowdfunding and donation campaigns with a goal, an end date or both.
* New: Fixed or "choose your amount" contributions with quick pick amounts.
* New: Up to three reward tiers per campaign with quantity limits and delivery dates.
* New: Campaign progress card, countdown, Story, Rewards and Supporters tabs.
* New: Supporters wall with messages and anonymous giving.
* New: Share buttons and a Backed Campaigns page in My Account.
* New: Dashboard, Campaigns and Contributions screens and a setup wizard.
* New: Four email notifications and two shortcodes.

== Upgrade Notice ==

= 1.0.0 =
First release.
