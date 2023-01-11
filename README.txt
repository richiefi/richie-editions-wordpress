=== Richie Editions WP ===
Contributors: makkeu
Donate link: https://www.richie.fi
Tags: richie, shortcode, editions
Requires at least: 5.0
Tested up to: 6.1.1
Stable Tag: 1.0.0
Requires PHP: 7.4
License: Copyright Richie OY

== Description ==

This plugin aims to make it easier to integrate Richie Editions e-paper content onto WordPress sites.

== Installation ==

1. Upload `richie-editions-wp` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in Wordpress.
3. Configure required settings under 'Settings -> Richie Editions'.
4. Provide custom filter hook for checking access:

```
function test_access( $issue ) {
    // http://wordpress.local/richie-editions-redirect/org/prod/uuid
    // Issue contains product code ([org]/[prod]) and issue guid, which can be used to check if user should get
    // access to the issue. Richie Editions secret is required in settings.
    if ( $issue->product === 'org/prod' && $issue->uuid === 'uuid' ) {
        return true;
    }
    return false;
}

add_filter('richie_editions_has_access', 'test_access');
```

OR provide a jwt token:

```
function get_user_jwt_token( $issue ) {
  $token = ...;
  return $token;
}

add_filter('richie_editions_user_jwt_token', 'get_user_jwt_token');
```


== Changelog ==

= 1.0.0 (10.01.2023) =
* Initial plugin code base

== Configuration ==

1. `Editions web url` - Full web url to the Richie Editions HTML5 server, can be https://<client>.ap.richiefi.net/<subtenant> or using configured cname
2. `Editions secret` - Provided secret which is used to calculate signature hash for signin urls (required if not using jwt token)
3. `Editions index range` - Select which index to use. To get available options, save hostname setting first.
4. `Editions error url` - Url to redirect user if opening issue fails (no access)

== Shortcode ==

Plugin provides `[richie_editions]` shortcode, which may be used to show grid of available issues.
Shortcode supports two attributes:
  - `product` (required): Richie Editions product id in form `[organization]/[product]`
  - `number_of_issues` (optional): Amount of issues to be shown. If omitted, shows all issues.

Example:
```
[richie_editions product="fleet_street_journal/fleet_street_journal" number_of_issues="10]
```

Short code renders a template, which the theme may overwrite.
This can be done by placing `richie-editions-index.php` inside `<theme_path>/richie-editions` folder.
A basic template as a base can be found inside plugin's templates folder.

== Technical Details ==

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

When you know whether a logged-in user can access a specific product, you implement the `richie_editions_can_access_product($product, $issue_uuid)` custom filter hook that will return a boolean value that indicates whether the user should be granted access to the specific product. Access checks are typically made on a per-product basis, but the plugin will also include the issue identifier in the call. Note that if the issue has been marked as free in Richie Editions, the plugin will grant access directly and this function will not be called.

### IF YOU DON'T KNOW THE PRODUCTS THE USER HAS ACCESS TO

The plugin can also delegate the access control decision to the Richie Editions service. In this scenario, you implement the `richie_editions_user_jwt_token()` filter hook that returns a JWT token as a string, or false if no token is available. The plugin will make a HTTP call to the Richie Editions backend to determine whether the token grants access to the given product.

### ERROR HANDLING

In both of these scenarios, the plugin will either 1) redirect the user to the requested issue in Richie Editions or, in the case of an error, redirect them to the error page you have configured the in plugin configuration.

