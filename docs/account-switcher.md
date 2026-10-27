# Account switcher

A separate plugin (`com.tinsever.wcf.accountSwitcher`) in this repository. It
works with or without the forum.

Users link their accounts and switch between them from the user panel without
logging in again. Links are transitive: linking A with B and B with C lets A
reach C. Accounts with multi-factor authentication ask for it before the switch.

- Settings: Configuration > Options > Users > Account Switcher
- Permission: "Can switch between linked accounts" in the user group options

Install `com.tinsever.wcf.accountSwitcher.tar` from the package server or the
[releases](https://github.com/tinsever/woltlab-forum/releases).

## Upgrading from Forum 1.0.0 Beta 2 or older

Up to Beta 2 the account switcher was part of the forum package. Beta 3 removed
it, so install the plugin to keep it. The plugin takes over the existing links
on installation, in either order. Its settings and group permissions are taken
over as well if the plugin is installed before the forum is updated.
