# Moodec Cart Rebuild — Design (for review)

Status: **DESIGN ONLY — no implementation in this PR.** Review and approve/redirect
before any code is written. Governed by the repo-root `CLAUDE.md` (AU Moodle
plugin standard).

## 1. Goal

Replace the dead Moodec purchase flow with a new shopping cart and checkout built
from scratch, reusing the existing product catalogue and the `enrol_moodec`
enrolment leg. Target per `CLAUDE.md`: **PHP 8.1+, Moodle 4.5+** (CI matrix PHP
8.1/8.2/8.3 × `mysqli`/`pgsql`), mindful of the Moodle 5.1+ `public/` layout.

## 2. Why the old flow is dead (recap)

`pages/checkout.php` → `MoodecGatewayPaypal::render()` posts an HTML form to PayPal
classic `www.paypal.com/cgi-bin/webscr` (`cmd=_cart`); PayPal then server-to-server
POSTs `payment/paypal/ipn.php`, which posts back `cmd=_notify-validate` and on
`VERIFIED` enrols the user.

This is **PayPal Payments Standard + IPN**. PayPal has retired the classic `webscr`
endpoints and is phasing out IPN, so the pay/confirm leg no longer functions. (On
the `Moodle-Local_moodle5.0` branch the AI port additionally overwrote `ipn.php`
with a copy of the product page, so that branch cannot confirm payment at all.)

## 3. What is kept vs replaced

**Kept / reused**
- Product model: `local_moodec_product`, `local_moodec_variation` tables and the
  product classes (simple + variable/variation pricing, durations, groups),
  reimplemented as autoloaded `\local_moodec\` namespaced classes.
- Enrolment leg: enrolment via the `enrol_moodec` plugin (fallback `manual`),
  group assignment, duration handling — logic lifted from the old
  `MoodecGateway::complete_enrolment()`, modernised.
- Catalogue/product pages (rebuilt to standard; link changes).

**Replaced / removed (all legacy PHP goes — see §10 CI note)**
- `classes/cart.php` — serialized session+cookie blob cart → DB-backed cart.
- `classes/gateway*.php` and the entire `payment/` directory → removed; payment
  delegated to Moodle core.
- `classes/transaction*.php` and the `local_moodec_transaction*` tables →
  superseded by a new order model referencing core payment records.
- All `sprintf()`-built SQL → parameterised Moodle DML (`CLAUDE.md` §4).
- Non-standard file headers / `require_once config.php` in class files →
  standard Moodle header + autoloading + `MOODLE_INTERNAL` guard.

## 4. Architectural decision: build on Moodle `core_payment`

The new cart does **not** hand-roll any gateway, IPN, or PCI-sensitive code.
Checkout delegates to Moodle's built-in **Payments subsystem** (`core_payment`,
stable since Moodle 4.0 — within the 4.5+ target):

- Moodec defines a *payable* = the cart total in the configured currency.
- Moodle's **maintained** gateway subplugins (`paygw_paypal`, `paygw_stripe`)
  perform the actual payment and verification.
- On success, core_payment invokes our `service_provider::deliver_order()`,
  which enrols the user and marks the order paid.

Consequence: PayPal vs Stripe is a site-admin config choice (which gateway
subplugin is enabled on the payment account) — **no moodec code change** either
way, and **no bundled SDK** (so no `thirdpartylibs.xml` and a smaller PCI/security
surface, satisfying `CLAUDE.md` §5).

`core_payment` models one payable per `(component, paymentarea, itemid)`. A Moodec
cart holds multiple courses, so **the order is the payable**:
`component = local_moodec`, `paymentarea = cart`, `itemid = local_moodec_order.id`.

## 5. Data model (new tables)

`local_moodec_cart` — `id, userid, currency, status (open|ordered|cancelled),
timecreated, timemodified`. One `open` cart per user.

`local_moodec_cart_item` — `id, cartid, productid, variationid, courseid,
unitprice (display only), timecreated`. Authoritative pricing recomputed at
checkout.

`local_moodec_order` — `id, userid, cartid, currency, amount, status
(pending|paid|delivered|failed|cancelled), paymentid (FK core {payments}),
timecreated, timemodified`.

`local_moodec_order_item` — `id, orderid, productid, variationid, courseid,
unitprice, enrolled (0/1, idempotent delivery)`.

`db/install.xml` adds these; `db/upgrade.php` uses incrementing savepoints
(`CLAUDE.md` §3.5, CI `savepoints`). A Moodle **privacy provider**
(`privacy/classes/provider.php`) covers all four tables for APP compliance
(`CLAUDE.md` §2).

## 6. Components & flow

1. **Cart ops** — `cart.php` (server-rendered, Mustache, `{{#str}}` only) plus an
   AMD module in `amd/src/` (built to `amd/build/`, `CLAUDE.md` §3.4) calling new
   external functions in `classes/external/` (`cart_add/remove/get`). Login +
   capability + sesskey enforced. JS sets text via `textContent`, never
   `innerHTML` (`CLAUDE.md` §4).
2. **Checkout** — `pages/checkout.php`: `require_login()`, load open cart, drop
   disabled products / already-enrolled courses, recompute prices, create
   `local_moodec_order` (pending), render the core_payment pay region for the
   order. Any `moodleform` action targets an explicit `index.php`
   (`CLAUDE.md` §3.7).
3. **Payment** — entirely core_payment + the enabled gateway. No moodec payment
   code.
4. **Delivery** — `classes/payment/service_provider.php` implementing
   `\core_payment\local\callback\service_provider`: `get_payable`,
   `get_success_url`, `deliver_order` (enrol via `enrol_moodec`/`manual`, correct
   duration incl. 0 = unlimited, group add, **idempotent** via
   `order_item.enrolled`, fire events, send receipt). Dates shown via
   `userdate()`; product summaries rendered through
   `file_rewrite_pluginfile_urls()` + a `local_moodec_pluginfile()` whitelist
   (`CLAUDE.md` §3.3).

## 7. Observability & security

Parameterised DML only; capability checks everywhere; events
(`cart_updated`, `order_paid`, `order_delivery_failed`); admin notified and
failure recorded (not swallowed) on delivery error; `fullname()` fed via
`\core_user\fields::for_name()` on the orders/receipt report (`CLAUDE.md` §3.2).

## 8. Locale (AU) — `CLAUDE.md` §2

- All user-facing text in `lang/en/local_moodec.php`, **ascending byte order,
  no interspersed comments** (§3.1). Legacy lang files use US spellings
  (e.g. "Enrollment") and section-comment dividers — both must be eliminated in
  the rebuilt file. Audit for `-ize/-or` → `-ise/-our`.
- Dates via `userdate()` (DD/MM/YYYY). **Default currency AUD**, 2-decimal —
  see Open Decision 3 re: relationship to the core payment account currency.

## 9. Migration & packaging

- Remove all legacy classes + `payment/`; drop their `require_once`s from
  `lib.php`; remove PayPal/DPS settings from `settings.php` (replaced by core
  Payments admin). `version.php`: standard header, bump `version`,
  `requires` = Moodle 4.5 baseline, set `maturity`, bump the `enrol_moodec`
  dependency to the rebuilt enrol plugin's new version. README rebuilt to the
  `tool_pluginskel` template (`CLAUDE.md` §5).
- Legacy `local_moodec_transaction*` data: see Open Decision 4.
- **`enrol_moodec`** is a separate plugin and must also conform to `CLAUDE.md`
  (its own CI workflow, version.php, privacy provider). It still ships 2014
  metadata and will not install on 4.5+. The cart depends on it — see Open
  Decision 5.

## 10. CLAUDE.md compliance — what it changed / pinned

- **Target** is now **Moodle 4.5+ / PHP 8.1+** (was "5.0"); resolves the prior
  "which version" open decision.
- **CI workstream added**: `.github/workflows/moodle-ci.yml` from
  `moodle-plugin-ci` `gha.dist.yml`, `env: TZ: Australia/Sydney`, matrix PHP
  8.1/8.2/8.3 × mysqli/pgsql, warnings-as-failures. Neither repo currently has
  CI.
- **No retained legacy PHP on the CI branch**: `phpcs --max-warnings 0` would
  fail on the 2015 code (tabs, headers, `sprintf` SQL, lang comments).
  Therefore any kept feature (e.g. the Transactions report) must be
  **reimplemented** as conforming code, not left as legacy files. This pushes
  Open Decision 4 toward "migrate + reimplement", not "keep old code read-only".
- **Default currency AUD** (was "from payment account / Stripe default").
- **Two-plugin scope**: `CLAUDE.md` applies independently to `local_moodec` and
  `enrol_moodec` (each: header, version.php, lang ordering, privacy, own CI).

## 11. Open decisions for reviewer

1. **Branch/workflow naming**: repo default branch is `master`; `CLAUDE.md` §8/§9
   say branch/rebase off `main`. Rename default to `main`, or keep `master` and
   read `CLAUDE.md`'s `main` as "the default branch"? (Affects the CI workflow
   triggers and rebase instructions.)
2. **Gateway(s)** to enable: Stripe, PayPal, or both (no code impact; affects
   test setup and go-live).
3. **Currency**: `CLAUDE.md` mandates AUD default. core_payment charges in the
   payment *account's* currency. Confirm AUD is the live selling currency, or
   define the rule when the account currency differs from the AUD display
   default.
4. **Legacy transactions**: migrate `local_moodec_transaction*` into the new
   order tables and reimplement the report to standard (recommended, given the
   no-legacy-PHP CI rule), or drop the historical report entirely?
5. **`enrol_moodec`**: authorise a parallel conforming rebuild/fix of
   `enrol_moodec` (its own branch + PR), since the cart cannot function without
   it installing on 4.5+.
6. **Guest cart**: require login to add to cart (simplest, matches old checkout)
   or support a session cart that merges on login?
