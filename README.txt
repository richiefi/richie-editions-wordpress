=== Plugin Name ===
Contributors: (this should be a list of wordpress.org userid's)
Donate link: https://www.richie.fi
Tags: richie, shortcode, editions
Requires at least: 5.0
Tested up to: 6.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

# Richie Editions WordPress Plugin

This plugin aims to make it easier to integrate Richie Editions e-paper content onto WordPress sites.

It has two main components:

## Issue index shortcodes

The plugin will fetch and parse Richie Editions JSON feeds and embed them onto pages using the **richie_editions** shortcode. For example:

```
[richie_editions product="fleet_street_journal/fleet_street_journal" number_of_issues="10"]
```

The above shortcode invocation will translate into a list of ten clickable cover images of the _Fleet Street Journal_ newspaper (the product code is entered as an [organization]/[product] pair). When end users select an issue, they will be redirected to an access control URL that's also implemented by the plug-in.

## Access control

Richie Editions focuses on premium (paid for) e-paper content, so access to the content is checked whenever a user opens an issue. This plugin supports two distinct access control schemes:

### IF YOU HAVE DIRECT KNOWLEDGE OF THE PRODUCTS THE USER CAN ACCESS

When you know whether a logged-in user can access a specific product, you implement the `richie_editions_can_access_product($product, $issue_uuid)` global function that will return a boolean value that indicates whether the user should be granted access to the specific product. Access checks are typically made on a per-product basis, but the plugin will also include the issue identifier in the call. Note that if the issue has been marked as free in Richie Editions, the plugin will grant access directly and this function will not be called.

### IF YOU DON'T KNOW THE PRODUCTS THE USER HAS ACCESS TO

The plugin can also delegate the access control decision to the Richie Editions service. In this scenario, you implement the `richie_editions_user_jwt_token()` function that returns a JWT token as a string, or NULL if no token is available. The plugin will make a HTTP call to the Richie Editions backend to determine whether the token grants access to the given product.

### ERROR HANDLING

In both of these scenarios, the plugin will either 1) redirect the user to the requested issue in Richie Editions or, in the case of an error, redirect them to the error page you have configured the in plugin configuration.