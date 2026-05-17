# Moodec storefront (local_moodec)

Moodec is an eCommerce storefront for Moodle. Learners browse a catalogue of
courses, add them to a cart and pay through Moodle's core Payments subsystem
(`core_payment`); on successful payment they are enrolled via the companion
`enrol_moodec` plugin.

Targets **Moodle 5.0+ / PHP 8.2+**.

## Requirements

- Moodle 5.0 or later.
- The `enrol_moodec` enrolment plugin (declared as a dependency).
- A configured Moodle payment account with a gateway enabled
  (`paygw_paypal` and/or `paygw_stripe`, both shipped with Moodle core).

## Installing via uploaded ZIP file

1. Log in as an admin and go to
   _Site administration > Plugins > Install plugins_.
2. Upload the ZIP file and follow the prompts.
3. Check the plugin validation report and finish the installation.

## Installing manually

Copy the plugin into

    {your/moodle/dirroot}/local/moodec

then log in as an admin and go to _Site administration > Notifications_.

## Note on legacy data

The pre-2026 PayPal/IPN storefront stored purchases in
`local_moodec_transaction` / `local_moodec_trans_item`. Those tables are left
in place untouched for historical reference; they are **not** migrated into the
new order tables and are no longer displayed. Operators are responsible for
configuring correct tax rates and for tax/GST/VAT registration in their
jurisdiction.

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

Copyright (C) 2015 LearningWorks Ltd; modernisation Copyright (C) 2026
LearningWorks Ltd.
