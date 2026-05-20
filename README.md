# EduCheckout storefront (local_educheckout)

EduCheckout is an eCommerce storefront for Moodle. Learners browse a catalogue of
courses, add them to a cart and pay through Moodle's core Payments subsystem
(`core_payment`); on successful payment they are enrolled via the companion
`enrol_educheckout` plugin.

**Version 1.0.0 (beta) — Moodle 5.0+ / PHP 8.2+**

## The EduCheckout plugin suite

EduCheckout is a collection of four Moodle plugins that together provide a
self-hosted course store:

| Plugin | Type | Purpose |
|---|---|---|
| `local_educheckout` *(this repo)* | Local | Storefront: catalogue, cart, checkout, orders |
| [`enrol_educheckout`](https://github.com/verzog/moodle-enrol_educheckout) | Enrolment | Enrols learners into courses on purchase |
| [`block_educheckout`](https://github.com/verzog/moodle-block_educheckout) | Block | Sidebar block with catalogue link and mini cart |
| [`theme_educheckout`](https://github.com/verzog/moodle-theme_educheckout) | Theme | Optional branded theme for the storefront pages |

## Features

- **Course catalogue** — browsable grid of products, filterable by admin-defined
  categories, with configurable pagination.
- **Product variations** — each product can have multiple priced variations
  (e.g. Standard / Premium). Two product types are supported:
  - *Simple* — self-paced course with optional enrolment duration and group.
  - *Session* — scheduled delivery with date/time, venue, and seat capacity per
    variation.
- **Product images** — upload a custom image per product, or it falls back to the
  course overview image.
- **Shopping cart** — learners can add multiple products and variations before
  checking out; abandoned carts are cleaned up nightly.
- **Payments** — delegates to Moodle's `core_payment` subsystem; any configured
  gateway works (PayPal, Stripe, and others shipped with Moodle core).
- **Automatic enrolment** — on successful payment, learners are enrolled via
  `enrol_educheckout` into each purchased course, with optional group assignment
  and time-limited enrolment.
- **Tax engine** — configurable tax label (GST, VAT, etc.), rate, and
  inclusive/exclusive mode; per-country tax rate overrides for multi-jurisdiction
  deployments.
- **Order management** — admin UI to view all orders; receipt page for learners.
- **Messaging** — Moodle message API notification sent on order completion.
- **Privacy API** — implements `privacy/classes/provider.php` for Australian
  Privacy Principles (APP) compliance.
- **Web services** — external functions for cart and checkout operations (used
  by the companion block).
- **Scheduled task** — nightly cart cleanup runs at 03:00 (site time).

## Requirements

- Moodle 5.0 or later.
- PHP 8.2 or later.
- The `enrol_educheckout` enrolment plugin (declared as a dependency).
- A configured Moodle payment account with a gateway enabled
  (`paygw_paypal` and/or `paygw_stripe`, both shipped with Moodle core).

## Installing via uploaded ZIP file

1. Log in as an admin and go to
   _Site administration > Plugins > Install plugins_.
2. Upload the ZIP file and follow the prompts.
3. Check the plugin validation report and finish the installation.

> **Note:** Install `enrol_educheckout` first (or upload both ZIPs in the same
> session). The storefront declares `enrol_educheckout` as a dependency and the
> installer will warn if it is missing.

## Installing manually

Copy the plugin into

    {your/moodle/dirroot}/local/educheckout

then log in as an admin and go to _Site administration > Notifications_.

## Configuration

Go to _Site administration > Local plugins > EduCheckout storefront_.

| Setting | Description |
|---|---|
| Payment account | The `core_payment` account that collects payments. |
| Currency | Default store currency (default: AUD). |
| Products per page | Number of products shown per catalogue page (default: 12). |
| Enable tax | Toggle the tax line on the cart and checkout pages. |
| Tax label | Label shown to learners (e.g. GST, VAT). |
| Tax rate | Percentage rate applied to product prices (e.g. 10). |
| Tax mode | *Exclusive* — tax is added on top of the product price. *Inclusive* — tax is already included in the listed price. |
| Country tax overrides | One `CC=rate` entry per line to apply a different rate for specific two-letter country codes. |

## Admin pages

Under _Site administration > Local plugins_:

- **Manage products** — create, edit, enable/disable, and delete products and
  their variations. Requires `local/educheckout:manageproducts`.
- **Manage categories** — create and organise store categories. Requires
  `local/educheckout:manageproducts`.
- **Orders** — view all completed orders across the site. Requires
  `local/educheckout:viewallorders`.

## Capabilities

| Capability | Default | Purpose |
|---|---|---|
| `local/educheckout:manageproducts` | Manager | Create and edit products and categories. |
| `local/educheckout:viewallorders` | Manager | View all learner orders across the site. |

## Note on legacy data

The pre-2026 PayPal/IPN storefront stored purchases in
`local_educheckout_transaction` / `local_educheckout_trans_item`. Those tables are
left in place untouched for historical reference; they are **not** migrated into
the new order tables and are no longer displayed. Operators are responsible for
configuring correct tax rates and for tax/GST/VAT registration in their
jurisdiction.

## Credits and acknowledgements

EduCheckout storefront is a rename and continuation of the **Moodec
storefront** plugin (`local_moodec`) originally written in 2015 by
**Thomas Threadgold** at **LearningWorks Ltd**
([github.com/LearningWorks](https://github.com/LearningWorks)). The
catalogue, cart, ordering and checkout architecture in this codebase
descend directly from that work.

Sincere thanks to Thomas and LearningWorks for the prior art this plugin
is built on.

## License

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program. If not, see <https://www.gnu.org/licenses/>.

Original Moodec storefront, Copyright (C) 2015 Thomas Threadgold /
LearningWorks Ltd; renaming and ongoing maintenance Copyright (C) 2026
the EduCheckout contributors.
