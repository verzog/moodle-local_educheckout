# CLAUDE.md — Moodle Plugin Development

Guidance for Claude when developing Moodle plugins for Australian deployments.
These rules are drawn from real mistakes and CI failures — treat them as a
pre-flight checklist, not background reading.

**Requirements:** PHP 8.1+, Moodle 4.5+, plugin with `version.php` and
`$plugin->component`. CI via GitHub Actions only.

---

## 1. CI — GitHub Actions Setup

GitHub Actions (GHA) is the only supported CI provider. No Travis CI references.

### Configuration

- **Workflow file:** Copy `gha.dist.yml` from `moodle-plugin-ci` and save as
  `.github/workflows/moodle-ci.yml`.
- **AU timezone:** Add `TZ: Australia/Sydney` to the workflow `env` section so
  date/time tests respect AEST/AWST offsets.
- **Matrix:** Test across PHP 8.1, 8.2, 8.3 and databases `mysqli` + `pgsql`.

### CI Commands

| Command        | Purpose                        | AU Compliance Note                                    |
|----------------|--------------------------------|-------------------------------------------------------|
| `phplint`      | PHP syntax errors              | PHP 8.1+ compatibility                                |
| `codechecker`  | Moodle Coding Standards        | 4-space indent; new line before `{` on classes/fns    |
| `phpdoc`       | PHPDoc checker                 | Requires `@package`, `@copyright`, `@license`         |
| `validate`     | Plugin structure               | Checks `version.php` and Frankenstyle component name  |
| `savepoints`   | Upgrade step validation        | `db/upgrade.php` version increments                   |
| `mustache`     | Mustache template lint         | No hardcoded text; use `{{#str}}` tags                |
| `grunt`        | Compile/lint JS & CSS          | SCSS lint; AMD modules in `amd/src/`                  |
| `phpunit`      | Back-end unit tests            | Extends `advanced_testcase`; `$this->resetAfterTest()`|
| `behat`        | Acceptance tests               | Use DD/MM/YYYY in all scenarios                       |

### CI Failure Diagnosis

If **every** matrix job fails identically within a few seconds (before the
install step), suspect infrastructure — not code. Check for a retired runner
image, an org Actions policy, or a GitHub billing/spending limit. Diagnose
before pushing workflow edits; a blind runner-image bump wastes a cycle.

### Optimisation

- Remove unused steps (e.g. skip Mobile App testing for desktop-only plugins).
- Use Behat tags (`--tags="@local_myplugin"`) to focus tests during development.
- Use `--auto-rerun 3` in Behat to handle flaky test failures.

---

## 2. Australian Locale & Coding Style

### Language & Spelling

Use AU/UK English exclusively in `lang/en/` files.

| Forbidden  | Required   |
|------------|------------|
| Customize  | Customise  |
| Organize   | Organise   |
| Color      | Colour     |
| Behavior   | Behaviour  |
| Enrollment | Enrolment  |

### Dates & Currency

- Render all user-facing dates via `userdate()` — defaults to DD/MM/YYYY.
- Currency defaults to AUD with 2-decimal precision.

### Privacy — Australian Privacy Principles (APP)

Implement `privacy/classes/provider.php` using the Moodle Privacy API to
comply with the APP. See §4 Security Defaults for the `MOODLE_INTERNAL` rule.

---

## 3. Hard-Won Rules (Real CI Failures)

### 3.1 Language Files: Strict Ordering, No Section Comments

`phpcs` runs as `moodle-plugin-ci phpcs --max-warnings 0` — warnings fail
the build. The `moodle.Files.LangFilesOrdering` sniff requires:

- Every `$string['key']` in **ascending byte order** (`strcmp` / `LC_ALL=C sort`
  — case-sensitive; `:` < `A` < `_` < `a`).
- **No comments between strings.** The `// Navigation.`-style dividers are
  flagged as `UnexpectedComment`. Only the license header comment is allowed.

Add strings anywhere, then re-sort the whole file. Verify with:

```bash
grep -oP "^\$string\['\K[^']+" lang/en/local_myplugin.php > /tmp/k
LC_ALL=C sort /tmp/k | diff - /tmp/k && echo "ORDER OK"
```

Lang files do **not** get a `defined('MOODLE_INTERNAL') || die();` guard —
`phpcs` flags it as unnecessary there.

### 3.2 `fullname()` Needs the Full Name Field Set

Never hand-pick `u.firstname, u.lastname` for a record passed to `fullname()` —
it raises an `E_USER_NOTICE` about missing phonetic/alternate fields. Use:

```php
$namefields = \core_user\fields::for_name()->get_sql('u', true)->selects;
$sql = "SELECT DISTINCT u.id{$namefields} FROM {user} u ...";
```

With `SELECT DISTINCT`, every `ORDER BY` column must appear in the select list
— PostgreSQL enforces this and CI will catch it.

### 3.3 Rewrite `@@PLUGINFILE@@` Before Formatting

Editor content saved via `file_postupdate_standard_editor()` stores embedded
images as `@@PLUGINFILE@@` placeholders. They will 404 on the rendered page
unless you rewrite before calling `format_text()`:

```php
format_text(
    file_rewrite_pluginfile_urls(
        $html, 'pluginfile.php',
        $context->id, 'local_myplugin', MY_FILEAREA, $record->id
    ),
    $format, ['context' => $context]
);
```

Every file area you serve must also be whitelisted in the plugin's
`local_myplugin_pluginfile()` callback, or it 404s even after rewriting.

### 3.4 AMD: Edit src, Rebuild build, Bump Version

`amd/src/*.js` is not what runs — Moodle loads `amd/build/*.min.js`. After
editing source, regenerate the minified bundle with grunt. If grunt is
unavailable, produce an equivalent hand-minified build matching the existing
wrapper style. Then bump `version.php` and purge all caches.

### 3.5 Bump `version.php` for Any Cached Asset Change

Templates, AMD, lang strings, DB schema, and capabilities all require a higher
`$plugin->version` to take effect on an existing install. When parallel PRs are
in flight, give each a distinct version number to avoid merge collisions.

### 3.6 Optional File Areas Need an Explicit Clear-on-Disable

`file_prepare_draft_area()` always populates the draft, so a hidden filemanager
still round-trips its existing files. Delete the area on save when a toggle is
off:

```php
if (!empty($data->haspanorama)) {
    file_save_draft_area_files(...);
} else {
    get_file_storage()->delete_area_files(
        $context->id, 'local_myplugin', FILEAREA_PANORAMA, $record->id
    );
}
```

Use `advcheckbox` + `$mform->hideIf('panorama_image', 'haspanorama', 'notchecked')`
for the reveal.

### 3.7 Form Actions: Point to Explicit `index.php`, Not the Directory

A `moodleform` action of `new moodle_url('/local/myplugin/')` (bare directory)
forces the web server to resolve the directory index on submit — a different,
stricter-permission path than the page load. If the plugin directory isn't
traversable by the web server user, the page loads fine but **Submit returns a
bare 403**. Always target the explicit endpoint the plugin registers:

```php
$mform = new myplugin_form(new moodle_url('/local/myplugin/index.php'));
```

### 3.8 Moodle 5.1+ `public/` Directory Layout

On Moodle 5.1+ the docroot is `<moodleroot>/public/`; plugins live at
`public/local/...`, `$CFG->dirroot` points at the `public/` dir, and
`config.php` may sit in `public/` (back-compat) or the project root. Account
for both layouts when reasoning about paths or bootstrap
(`require '../../config.php'`). Note: `moodle-plugin-ci` does not exercise the
`public/` split — passing CI says nothing about path resolution on 5.1+.

---

## 4. Security Defaults

- **`MOODLE_INTERNAL` guard:** Add `defined('MOODLE_INTERNAL') || die();` to
  every PHP file **except** lang files and `lib.php` files that contain only
  function declarations (phpcs flags the guard as unnecessary in those cases).
- **SQL:** Always use Moodle's query placeholders (`?` or named params) — never
  interpolate user input into SQL strings.
- **JS:** Set user/content text with `textContent` / `document.createTextNode`,
  never `innerHTML`.
- **Inline style/script from settings:** Strip the closing tag
  (`str_ireplace('</style', ...)`) to prevent breakout.
- **Scope admin custom CSS/JS** to the plugin's own pages unless the user
  explicitly requests site-wide — a bad rule shouldn't break all of Moodle.
- **Validate at boundaries** with the correct `PARAM_*` type; trust internal code.
- **Never commit secrets.** If a user pastes a stack trace or dump containing
  live cookies, tokens, or credentials, flag it and recommend rotation — do not
  echo decoded values back.

---

## 5. Plugin Structure & Boilerplate

- **Standard PHP header** on every file:
  ```php
  // This file is part of Moodle - https://moodle.org/
  ```
  followed by a `@package`, `@copyright`, `@license` docblock.
- **`README.md`** follows the `moodle-tool_pluginskel` template: short
  description, "Installing via uploaded ZIP file", "Installing manually",
  Requirements, License (GPLv3 block matching file headers).
- **Third-party libraries:** declare in `thirdpartylibs.xml`, keep the upstream
  `LICENSE` in-tree, and attribute in README (name, version, license, copyright
  holder, upstream URL, "unmodified").
- **Privacy API:** implement `privacy/classes/provider.php` for APP compliance.

---

## 6. Local Development

Install the CI toolchain:

```bash
php composer.phar create-project moodlehq/moodle-plugin-ci ../moodle-plugin-ci ^4
```

Run CodeSniffer locally:

```bash
# Check violations
../moodle-plugin-ci/vendor/bin/phpcs ./index.php

# Auto-fix formatting (tabs -> spaces, etc.)
../moodle-plugin-ci/vendor/bin/phpcbf ./index.php
```

---

## 7. Pre-PR Checklist

- [ ] `php -l` clean on every changed `.php` file.
- [ ] Lang file re-sorted; no interspersed comments.
- [ ] AU/UK English throughout `lang/en/` (check `-ise`, `-our`, `Enrolment`).
- [ ] `@@PLUGINFILE@@` rewritten anywhere editor content is rendered.
- [ ] New file areas whitelisted in `*_pluginfile()`.
- [ ] AMD `build/` regenerated if `src/` changed.
- [ ] `version.php` bumped (distinct number if parallel PRs).
- [ ] User-facing strings in `lang/en/` — no hardcoded text anywhere.
- [ ] Dates rendered via `userdate()`; currency defaults to AUD.
- [ ] No `innerHTML` with untrusted/user data.
- [ ] Privacy API implemented (`privacy/classes/provider.php`).
- [ ] Workflow file is GitHub Actions only — no Travis CI references.
- [ ] `TZ: Australia/Sydney` present in workflow `env`.
- [ ] Form actions point to explicit `index.php`, not a bare directory URL.
- [ ] PR diff confirmed to contain the intended change before merging.

---

## 8. Workflow

- One concern per PR; branch off the latest `main`.
- If a branch falls behind merged work, rebase onto `origin/main` and
  force-with-lease.
- Open PRs ready for review (not draft); keep PR bodies to a summary + test plan.
- CI runs: `phplint`, `phpcs --max-warnings 0`, `phpdoc`, `validate`,
  `savepoints`, `phpunit --fail-on-warning`, `behat` across a PHP x Moodle-branch
  matrix. Warnings are failures.
- Don't merge until the fix commits are on the branch. Commits pushed after a
  merge are stranded with no open PR.

---

## 9. Deployment & Live-Site Diagnosis

CI green and PR merged does **not** mean the change is live. Most "still broken
after the fix" reports are deployment issues, not code.

### Merge != Deploy

`main` on GitHub never touches a running site. After merging: redeploy the
files, then run the Moodle upgrade (*Site admin -> Notifications* or
`php admin/cli/upgrade.php`). `version.php` must be bumped (§3.5) or no
upgrade/cache-purge fires.

### Verify the Bytes on Disk

Grep a known marker from the new code in the deployed file before trusting it:

```bash
grep -n "MARKER_FROM_THE_FIX" /path/to/moodle/local/myplugin/index.php
```

Don't assume a deploy worked — confirm the file changed.

### Deploy Ownership

Deploying as a non-web user (rsync/git pull as a deploy account) sets the wrong
group, so the web server can't traverse the plugin directory -> 403 on
directory-resolved requests. Match owner:group to the rest of the Moodle tree:

```bash
rsync -a --chown=appuser:www-data ...
```

Re-apply after every deploy if needed.

### Get Server Evidence Before Theorising

For "works in CI / fails on server", read the per-vhost nginx logs — not the
global ones:

```bash
sudo tail -n 50 /var/log/nginx/<vhost>-error.log
sudo grep ' 403 ' /var/log/nginx/<vhost>-access.log | tail
```

The access log line shows exact method and URL. `(13: Permission denied)` means
filesystem permissions; a WAF or nginx `deny` logs differently. Get this first —
hours were lost theorising (WAF, redirects, framework layout) before the log
named the actual cause.
