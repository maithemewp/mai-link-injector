# Changelog

## 1.4.1 (4/5/24)
* Fixed: Special characters caused some elements to get removed from the content.

## 1.4.0 (4/3/24)
* Added: New "Total link limit" setting to set the maximum amount of injected links on a page.
* Added: New "Element link limit" setting to set the maximum amount of links per element.
* Added: New "Keyword link limit" setting to optionally limit the amount of instances specific keywords get linked per-page.
* Added: Now you can use `mai-link-exclude` class to allow skipping elements and their children from getting links injected.
* Changed: Only the first link in a single element is counted.
* Changed: The links are now spread out among the total instances of the keywords.
* Changed: Skip links to the same page.

## 1.3.0 (3/7/24)
* Changed: Offsite links are now opened in a new tab. Added new `mai_link_injector_link_attributes` filter to override link attributes.
* Changed: Links are now ignored when inside of `<figure>` and `<figurecaption>` elements.
* Changed: Updated the updater.
* Fixed: Multiple links injected inside the same element were not counted separately.
* Fixed: Encoding compatibility with PHP 8.2.

## 1.2.1 (11/27/23)
* Changed: Update updater.

## 1.2.0 (3/20/23)
* Changed: Disabled pagination on repeater since it doesn't work correctly when we load from separate option.

## 1.1.0 (3/16/23)
* Added: New settings page to manage links and settings.
* Added: New `mai_link_injector` filter to programmatically modify settings.
* Added: Updater script.

## 1.0.0
Initial release