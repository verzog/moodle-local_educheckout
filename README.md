# EduCheckout storefront (local_educheckout)

EduCheckout is an eCommerce storefront for Moodle. Learners browse a catalogue of
courses, add them to a cart and pay through Moodle's core Payments subsystem
(`core_payment`); on successful payment they are enrolled via the companion
`enrol_educheckout` plugin.

Targets **Moodle 5.0+ / PHP 8.2+**.

## Requirements

- Moodle 5.0 or later.
- The `enrol_educheckout` enrolment plugin (declared as a dependency).
- A configured Moodle payment account with a gateway enabled
  (`paygw_paypal` and/or `paygw_stripe`, both shipped with Moodle core).

## Installing via uploaded ZIP file

1. Log in as an admin and go to
   _Site administration > Plugins > Install plugins_.
2. Upload the ZIP file and follow the prompts.
3. Check the plugin validation report and finish the installation.

## Installing manually

Copy the plugin into

    {your/moodle/dirroot}/local/educheckout

then log in as an admin and go to _Site administration > Notifications_.

## Note on legacy data

The pre-2026 PayPal/IPN storefront stored purchases in
`local_educheckout_transaction` / `local_educheckout_trans_item`. Those tables are left
in place untouched for historical reference; they are **not** migrated into the
new order tables and are no longer displayed. Operators are responsible for
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
