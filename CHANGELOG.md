# Changelog

All notable changes to this app will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [0.3.0] - 2024-07-08

### Added
- [#197](https://github.com/owncloud/configreport/pull/197) - feat: add daily transmission of config report to ownCloud/kiteworks for business intelligence
- [#200](https://github.com/owncloud/configreport/pull/200) - feat: read /etc/os-release or /etc/lsb-release to get Linux distro information
- [#201](https://github.com/owncloud/configreport/pull/201) - feat: add information to basic report data if running in docker

### Changed
- [#199](https://github.com/owncloud/configreport/pull/199) - fix: process phpinfo() in cli mode as well
- Depenedency updates, copyright headers added.


## [0.2.2]  - 2023-08-08

### Changed

- [#183](https://github.com/owncloud/configreport/pull/183) - Hide passwords from the config report
- [#187](https://github.com/owncloud/configreport/pull/187) - Always return an int from Symfony Command execute method 
- [#186](https://github.com/owncloud/configreport/pull/186) - Add helmich/phpunit-json-assert library
- Dependency updates

## [0.2.1] - 2022-04-07

### Added

- Add stats guest_count, renamed count to total_count - [#146](https://github.com/owncloud/configreport/issues/146)

### Changed

- Sanitize system config values - [#171](https://github.com/owncloud/configreport/issues/171)
- Elastic search credentials are obscured. [#170](https://github.com/owncloud/configreport/issues/170)


## 0.2.0 - 2019-04-16

### Added

- Include mounts information in report [#94](https://github.com/owncloud/configreport/issues/94)

### Changed

- Decouple from core, switching to own release cycle
- Drop PHP 5.6 support

[Unreleased]: https://github.com/owncloud/configreport/compare/v0.2.2..master
[0.3.0]: https://github.com/owncloud/configreport/compare/v0.2.2..v0.3.0
[0.2.2]: https://github.com/owncloud/configreport/compare/v0.2.1..v0.2.2
[0.2.1]: https://github.com/owncloud/configreport/compare/v0.2.0..v0.2.1

