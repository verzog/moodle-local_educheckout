# EduCheckout Cart Rebuild — Design (for review)

Status: **DESIGN ONLY — no implementation in this PR.** All decisions locked
(§11). Governed by the repo-root `CLAUDE.md` (AU Moodle plugin standard), with
one deliberate project narrowing: **Moodle 5.0+ only**.

## 1. Goal

Replace the dead EduCheckout purchase flow with a new shopping cart and checkout built
from scratch, reusing the existing product catalogue and the `enrol_educheckout`
enrolment leg. **Target: Moodle 5.0+ / PHP 8.2+.** (Moodle 5.0 drops PHP 8.1, so
the PHP floor is 8.2; CI matrix PHP 8.2/8.3 × `mysqli`/`pgsql`.) The Moodle 5.1+
`public/` directory layout must be accounted for. Implementation integrates on
the **`main`** branch (Decision 1).

## 2. Why the old flow is dead (recap)

`pages/checkout.php` → `EduCheckoutGatewayPaypal::render()` posts an HTML form to PayPal
classic `www.paypal.com/cgi-bin/webscr` (`cmd=_cart`); PayPal then server-to-server
POSTs `payment/paypal/ipn.php`, which posts back `cmd=_notify-validate` and on
`VERIFIED` enrols the user.

This is **PayPal Payments Standard + IPN**. PayPal has retired the classic `webscr`
endpoints and is phasing out IPN, so the pay/confirm leg no longer functions. (On
the `Moodle-Local_moodle5.0` branch the AI port additionally overwrote `ipn.php`
with a copy of the product page, so that branch cannot confirm payment at all.)

## 3. What is kept vs replaced

**Kept / reused**
- Product model: `local_educheckout_product`, `local_educheckout_variation` tables and the
  product classes (simple + variable/variation pricing, durations, groups),
  reimplemented as autoloaded `\local_educheckout\` namespaced classes.
- Enrolment leg: enrolment via the `enrol_educheckout` plugin (fallback `manual`),
  group assignment, duration handling — logic lifted from the old
  `EduCheckoutGateway::complete_enrolment()`, modernised.
- Catalogue/product pages (rebuilt to standard; link changes).

**Replaced / removed (all legacy PHP goes — see §10 CI note)**
- `classes/cart.php` — serialized session+cookie blob cart → DB-backed cart with
  a guest/session cart that merges on login (Decision 4).
- `classes/gateway*.php` and the entire `payment/` directory → removed; payment
  delegated to Moodle core_payment.
- `classes/transaction*.php` and the old report code → removed. The
  `local_educheckout_transaction*` **tables and their data are left untouched in the
  database but are no longer surfaced** (Decision 3 — no migration).
- All `sprintf()`-built SQL → parameterised Moodle DML (`CLAUDE.md` §4).
- The legacy hardcoded 13-currency list (`local_educheckout_get_currencies()`) →
  full supported currency set (Decision 5).
- Non-standard file headers / `require_once config.php` in class files →
  standard Moodle header + autoloading + `MOODLE_INTERNAL` guard.

## 4. Architectural decision: build on Moodle `core_payment`

The new cart does **not** hand-roll any gateway, IPN, or PCI-sensitive code.
Checkout delegates to Moodle's built-in **Payments subsystem** (`core_payment`):

- EduCheckout defines a *payable* = the cart total (incl. tax) in the order currency.
- Both **core** gateway subplugins — `paygw_paypal` **and** `paygw_stripe`,
  which ship with Moodle core — are enabled on the payment account (Decision 2).
  The user picks PayPal or Stripe at the core-rendered pay step.
- On success, core_payment invokes our `service_provider::deliver_order()`,
  which enrols the user and marks the order paid.

No bundled SDK and no third-party libraries (so no `thirdpartylibs.xml`, smaller
PCI/security surface — `CLAUDE.md` §5). `core_payment` models one payable per
`(component, paymentarea, itemid)`; a EduCheckout cart holds multiple courses, so
**the order is the payable**: `component = local_educheckout`,
`paymentarea = cart`, `itemid = local_educheckout_order.id`.

## 5. Data model (new tables)

`local_educheckout_cart` — `id, userid (0 for guest), sessionkey (guest cart),
currency, status (open|ordered|cancelled), timecreated, timemodified`. One `open`
cart per user; guest carts keyed by `sessionkey`.

`local_educheckout_cart_item` — `id, cartid, productid, variationid, courseid,
unitprice (display only), timecreated`. Authoritative pricing recomputed at
checkout.

`local_educheckout_order` — `id, userid, cartid, currency, netamount, taxamount,
taxrate, taxinclusive, amount (gross = net + tax), status
(pending|paid|delivered|failed|cancelled), paymentid (FK core {payments}),
timecreated, timemodified`.

`local_educheckout_order_item` — `id, orderid, productid, variationid, courseid,
unitprice, nettax (per-item tax for the invoice breakdown), enrolled (0/1,
idempotent delivery)`.

`db/install.xml` adds these; `db/upgrade.php` uses incrementing savepoints
(`CLAUDE.md` §3.5). **No upgrade step migrates legacy `local_educheckout_transaction*`
data** (Decision 3). A Moodle **privacy provider**
(`privacy/classes/provider.php`) covers the four new tables for APP compliance
(`CLAUDE.md` §2); customer country used for tax is read from the existing Moodle
user profile (not separately collected).

## 6. Components & flow

1. **Cart ops** — `cart.php` (server-rendered, Mustache, `{{#str}}` only) plus an
   AMD module in `amd/src/` (built to `amd/build/`, `CLAUDE.md` §3.4) calling new
   external functions in `classes/external/` (`cart_add/remove/get`). Guests may
   add to a session-keyed cart; capability + sesskey enforced. On login, the
   guest cart merges into the user's open cart (dedupe by product/variation;
   drop courses already actively enrolled). JS sets text via `textContent`,
   never `innerHTML` (`CLAUDE.md` §4).
2. **Checkout** — `pages/checkout.php`: `require_login()`, load open cart, drop
   disabled products / already-enrolled courses, recompute prices, **compute tax
   (§8.2)**, create `local_educheckout_order` (pending) recording net/tax/gross +
   currency, render the core_payment pay region for the gross amount. Any
   `moodleform` action targets an explicit `index.php` (`CLAUDE.md` §3.7).
3. **Payment** — entirely core_payment + the chosen gateway (PayPal or Stripe).
   No educheckout payment code.
4. **Delivery** — `classes/payment/service_provider.php` implementing
   `\core_payment\local\callback\service_provider`: `get_payable` (returns gross
   + order currency), `get_success_url`, `deliver_order` (enrol via
   `enrol_educheckout`/`manual`, correct duration incl. 0 = unlimited, group add,
   **idempotent** via `order_item.enrolled`, fire events, send a tax-itemised
   receipt). Dates via `userdate()`; product summaries rendered through
   `file_rewrite_pluginfile_urls()` + a `local_educheckout_pluginfile()` whitelist
   (`CLAUDE.md` §3.3).

## 7. Observability & security

Parameterised DML only; capability checks everywhere; events
(`cart_updated`, `order_paid`, `order_delivery_failed`); admin notified and
failure recorded (not swallowed) on delivery error; `fullname()` fed via
`\core_user\fields::for_name()` on the orders/receipt report (`CLAUDE.md` §3.2).

## 8. Locale, currency & tax — `CLAUDE.md` §2

### 8.1 Locale & currency

- All user-facing text in `lang/en/local_educheckout.php`, **ascending byte order,
  no interspersed comments** (§3.1). Legacy lang files use US spellings
  (e.g. "Enrollment") and section-comment dividers — both eliminated. Audit for
  `-ize/-or` → `-ise/-our`.
- Dates via `userdate()` (DD/MM/YYYY).
- **All currencies supported** (Decision 5): the store currency is admin-chosen
  from the full ISO-4217 set that the configured core payment gateway(s)
  support (sourced from core, not a hardcoded list). **Default = AUD**
  (`CLAUDE.md` §2), 2-decimal display via the currency's minor-unit rules. The
  store operates in one configured currency that matches the core payment
  account; the order records its currency. Per-shopper currency switching
  (per-currency price lists + per-currency payment accounts) is a **confirmed
  later phase**, not v1 (§11 Remaining Item C).

### 8.2 Tax

New tax capability (addresses the consumer/tax-law gap previously flagged):

- **Settings** (site admin → plugin settings): `tax_enable` (bool);
  `tax_label` (string, default "GST"); `tax_rate` (decimal %, default 10.0);
  `tax_mode` = `inclusive` (displayed prices already include tax) or
  `exclusive` (tax added at checkout); optional per-country override map
  (`country → rate`) for VAT/GST destinations, defaulting to the base rate.
- **Computation** at checkout: resolve the applicable rate (per-country override
  → base rate; customer country from the Moodle user profile), compute per-item
  and order-level net/tax/gross, persist on `local_educheckout_order` /
  `local_educheckout_order_item`. Rate 0 / `tax_enable=off` ⇒ pure net (covers
  tax-exempt education jurisdictions).
- **Display**: cart, checkout and the emailed/HTML receipt show a net + tax
  (label + rate) + gross breakdown; invoice-style receipt suitable as a tax
  receipt.
- Per-product/per-category tax classes are a **confirmed later phase**, not v1
  (global rate + optional per-country override only) — §11 Remaining Item C.
- This is operator-configurable tooling, not tax advice; the README states the
  operator is responsible for correct rates and registration.

## 9. Migration & packaging

- Remove all legacy classes + `payment/`; drop their `require_once`s from
  `lib.php`; remove PayPal/DPS + old currency settings from `settings.php`
  (replaced by core Payments admin + new currency/tax settings).
  `version.php`: standard header, bump `version`,
  **`requires` = the Moodle 5.0 baseline version**, set `maturity`, bump the
  `enrol_educheckout` dependency to the rebuilt enrol plugin's new version.
- **README** rebuilt to the `tool_pluginskel` template, and **must explicitly
  state**: (a) existing `local_educheckout_transaction*` data is **not migrated** and
  no longer displayed, remaining untouched in the DB (Decision 3); (b) the
  operator is responsible for configuring correct tax rates and for tax/GST/VAT
  registration and compliance in their jurisdiction.
- **`enrol_educheckout`** rebuild is **authorised and designed** — see
  `verzog/moodle-enrol_educheckout` PR #1 (`docs/enrol-rebuild-design.md`): a
  minimal, conformant Moodle 5.0+ enrolment plugin (admin-only unenrol, no bulk
  ops in v1). The two rebuilds and their version dependency are coordinated
  across the two PRs, both integrating on `main`.

## 10. CLAUDE.md compliance — what it changed / pinned

- **Target Moodle 5.0+ / PHP 8.2+** — deliberate project narrowing; `CLAUDE.md`
  updated to match. No 4.x built or tested.
- **CI workstream added**: `.github/workflows/moodle-ci.yml` from
  `moodle-plugin-ci` `gha.dist.yml`, `env: TZ: Australia/Sydney`, matrix PHP
  8.2/8.3 × mysqli/pgsql against Moodle 5.0+ branches, warnings-as-failures,
  triggered on `main`.
- **No retained legacy PHP**: old Transactions report removed; with no data
  migration historical data stays unsurfaced and is documented in the README.
- **Currency**: AUD remains the `CLAUDE.md` §2 default, but all
  gateway-supported currencies are selectable (Decision 5).
- **Tax**: new configurable tax subsystem; receipt doubles as a tax receipt.
- **Two-plugin scope**: `CLAUDE.md` applies independently to `local_educheckout` and
  `enrol_educheckout`, both targeting Moodle 5.0+ and integrating on `main`.

## 11. Decisions (locked)

1. **Integration branch** — both repos standardise on **`main`** as the
   Moodle 5.0+ integration line and CI trigger; making `main` the repo default
   is a one-off GitHub admin step at implementation start. (Supersedes the
   earlier `Moodle-Local_moodle5.0` / `moodle_enrol_educheckout50` target names.)
2. **Gateways** — **both** PayPal and Stripe, via the **core** `paygw_paypal`
   and `paygw_stripe` subplugins. No custom gateway code.
3. **Legacy data** — **no migration.** Old tables/data left untouched and
   unsurfaced; old report removed; flagged in README.
4. **Guest cart** — **guest cart that merges on login.** Session-keyed cart for
   anonymous users, merged into the user's DB cart at login; checkout still
   forces login before payment.
5. **Currency** — **all currencies supported**, admin-selected from the full
   gateway-supported set; **default AUD**; single store currency matching the
   core payment account (no per-shopper currency switching in v1).
6. **Tax** — **configurable tax capability added**: enable flag, label, base
   rate, inclusive/exclusive mode, optional per-country override; net/tax/gross
   recorded per order and shown on an invoice-style receipt.
7. **`enrol_educheckout` rebuild** — **authorised.** Minimal conformant Moodle 5.0+
   rebuild (admin-only unenrol, no v1 bulk ops) designed in
   `verzog/moodle-enrol_educheckout` PR #1.

### Remaining items

- **A. `enrol_educheckout`** — ✅ authorised, designed, and its open questions
  resolved (admin-only unenrol; `main` branch; no v1 bulk ops) in PR #1 of
  `moodle-enrol_educheckout`.
- **B. Branch naming** — ✅ resolved: standardise on **`main`** (Decision 1).
- **C. Future phases (confirmed later, not v1)** — per-shopper multi-currency
  (per-currency price lists + payment accounts) and per-product/category tax
  classes are deferred to a later phase by reviewer decision.

All open decisions are now closed; the design is ready for an
implementation go-ahead.
