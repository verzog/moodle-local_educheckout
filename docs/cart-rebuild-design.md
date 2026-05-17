# Moodec Cart Rebuild — Design (for review)

Status: **DESIGN ONLY — no implementation in this PR.** Review and approve/redirect
before any code is written.

## 1. Goal

Replace the dead Moodec purchase flow with a new shopping cart and checkout built
from scratch for **Moodle 5.0 / PHP 8.2**, reusing the existing product catalogue
and the `enrol_moodec` enrolment leg.

## 2. Why the old flow is dead (recap)

`pages/checkout.php` → `MoodecGatewayPaypal::render()` posts an HTML form to PayPal
classic `www.paypal.com/cgi-bin/webscr` (`cmd=_cart`); PayPal then server-to-server
POSTs `payment/paypal/ipn.php`, which posts back `cmd=_notify-validate` and on
`VERIFIED` enrols the user.

This is **PayPal Payments Standard + IPN**. PayPal has retired the classic `webscr`
endpoints and is phasing out IPN in favour of the Orders v2 REST API + webhooks, so
the pay/confirm leg no longer functions. (On the `Moodle-Local_moodle5.0` branch the
AI port additionally overwrote `ipn.php` with a copy of the product page, so that
branch cannot confirm payment at all.)

## 3. What is kept vs replaced

**Kept / reused**
- Product model: `local_moodec_product`, `local_moodec_variation` tables and the
  `MoodecProduct*` classes (simple + variable/variation pricing, durations, groups).
- Enrolment leg: enrolment via the `enrol_moodec` plugin (fallback to `manual`),
  group assignment, and duration handling — logic lifted from the old
  `MoodecGateway::complete_enrolment()`, modernised.
- Catalogue/product pages (minor link changes only).

**Replaced / removed**
- `classes/cart.php` — serialized session+cookie blob cart. Replaced with a
  DB-backed cart.
- `classes/gateway.php`, `gateway_paypal.php`, `gateway_dps.php` and the entire
  `payment/` directory — removed. Payment is delegated to Moodle core.
- `classes/transaction.php` / `transaction_item.php` and the
  `local_moodec_transaction*` tables — superseded by a new order model that
  references core payment records (old tables retained read-only for historical
  reporting; see Migration).
- All `sprintf()`-built SQL in the touched code (currently SQL-injection-prone) —
  replaced with parameterised Moodle DML.

## 4. Architectural decision: build on Moodle `core_payment`

The new cart does **not** hand-roll any gateway, IPN, or PCI-sensitive code.
Checkout delegates to Moodle's built-in **Payments subsystem** (`core_payment`,
stable since Moodle 4.0):

- Moodec defines a *payable* = the cart total in the configured currency.
- Moodle's **maintained** gateway subplugins (`paygw_paypal`, `paygw_stripe`)
  perform the actual payment and verification.
- On a successful payment, core_payment invokes our
  `service_provider::deliver_order()`, which performs the enrolment and marks the
  order paid.

Consequence: PayPal vs Stripe becomes a site-admin configuration choice (which
gateway subplugin is enabled on the payment account) — **no moodec code change**
either way. Recommended: enable both, default to Stripe for reliability. This
supersedes the old per-plugin PayPal/DPS settings.

`core_payment` models one payable per `(component, paymentarea, itemid)`. A Moodec
cart holds multiple courses, so **the cart/order is the payable**:
`component = local_moodec`, `paymentarea = cart`, `itemid = local_moodec_order.id`,
`amount = order total`.

## 5. Data model (new tables)

`local_moodec_cart`
- `id`, `userid`, `currency`, `status` (open|ordered|cancelled),
  `timecreated`, `timemodified`. One `open` cart per user (unique index on
  `userid` where status=open enforced in code).

`local_moodec_cart_item`
- `id`, `cartid`, `productid`, `variationid` (0 for simple), `courseid`,
  `unitprice` (last-known, for display only), `timecreated`.
  Authoritative pricing is recomputed from the product/variation at checkout.

`local_moodec_order`
- `id`, `userid`, `cartid`, `currency`, `amount` (authoritative total at
  checkout), `status` (pending|paid|delivered|failed|cancelled),
  `paymentid` (FK to core `{payments}`), `timecreated`, `timemodified`.

`local_moodec_order_item`
- `id`, `orderid`, `productid`, `variationid`, `courseid`, `unitprice`,
  `enrolled` (0/1, for idempotent delivery).

A Moodle **privacy provider** is added for the cart/order tables (the legacy
plugin predates the Privacy API; required for Moodle 5.0).

## 6. Components & flow

1. **Add/remove/view cart** — server-rendered `pages/cart.php` plus an AMD module
   calling new Moodle external (web service) functions in
   `classes/external/` (`cart_add`, `cart_remove`, `cart_get`). All require login,
   capability checks, and `sesskey`/external token. Add-to-cart requires login
   (self-registration already recommended in the plugin README).
2. **Checkout** — `pages/checkout.php`:
   - `require_login()`, load the user's open cart.
   - `refresh()` equivalent: drop disabled products, drop courses the user is
     already actively enrolled in, recompute prices, surface a "these changed"
     notice.
   - Create a `local_moodec_order` (status `pending`) snapshotting items + total.
   - Render the core_payment pay region for
     `component=local_moodec, paymentarea=cart, itemid=order.id`.
3. **Payment** — handled entirely by core_payment + the enabled gateway subplugin
   (hosted/redirect or embedded per gateway). No moodec payment code.
4. **Delivery** — `classes/payment/service_provider.php` implementing
   `\core_payment\local\callback\service_provider`:
   - `get_payable($paymentarea, $itemid)` → amount + currency + payment account.
   - `get_success_url(...)` → receipt page.
   - `deliver_order(...)` → for each order item: enrol via
     `enrol_get_plugin('moodec')` (fallback `manual`) with correct
     start/end (simple vs variation duration; 0 = unlimited), add to group if set,
     set `order_item.enrolled=1`. **Idempotent**: skip already-enrolled items so a
     repeated callback cannot double-enrol. Mark order `delivered`, close the cart
     (`status=ordered`), fire events, send receipt to user.

## 7. Observability, security, correctness

- Parameterised DML everywhere; no string-built SQL.
- Capability checks on every page/WS; `context_system` for store, `context_course`
  for enrolment.
- Moodle events: `\local_moodec\event\cart_updated`,
  `\local_moodec\event\order_paid`, `\local_moodec\event\order_delivery_failed`.
- Admin notified on delivery failure (course missing, enrol method absent);
  failures recorded on the order, not silently swallowed.
- Currency taken from the core payment account / order, validated against the
  cart currency.

## 8. Migration & packaging

- `db/install.xml`: add the four new tables.
- `db/upgrade.php`: create new tables; leave `local_moodec_transaction*` in place
  read-only so the existing Transactions report keeps working for historical data.
  (Optional later step: backfill old transactions into `local_moodec_order` — out
  of scope for the first cut; flag for decision.)
- Delete `payment/`, `classes/gateway*.php`, `classes/cart.php`,
  `classes/transaction*.php`; remove their `require_once`s from `lib.php`; remove
  the PayPal/DPS settings from `settings.php` (replaced by core Payments admin).
- `version.php`: bump `version`, set `requires` to the Moodle 5.0 baseline, set
  `maturity`, and bump the `enrol_moodec` dependency to the rebuilt enrol plugin's
  new version.
- **`enrol_moodec`**: out of scope for the cart rebuild itself, but it must be
  installable on Moodle 5.0 first — its `version.php` still declares 2014 metadata
  (tracked separately). The new cart depends on a working `enrol_moodec`.

## 9. Testing

- PHPUnit: cart add/remove/dedupe, price recompute, order creation,
  `service_provider::get_payable()`, idempotent `deliver_order()` (incl.
  already-enrolled and unlimited-duration cases).
- Behat: browse → add to cart → checkout → pay via a sandbox/test gateway →
  user enrolled + receipt shown; and the "item disabled / already enrolled at
  checkout" path.
- Manual: full run against a real gateway sandbox before go-live.

## 10. Open decisions for reviewer

1. Confirm target is the **Moodle 5.0** line and which branch this work should
   eventually merge into (`master` vs `Moodle-Local_moodle5.0`).
2. Gateway(s) to enable: **Stripe**, **PayPal**, or both (no code impact; affects
   test setup and go-live).
3. Historical-data backfill into the new order tables: do it, or leave the old
   Transactions report read-only over the legacy tables?
4. Guest browsing: keep "must be logged in to add to cart" (simplest, matches old
   checkout behaviour) or add a session cart that merges on login?
