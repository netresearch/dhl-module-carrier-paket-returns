# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## 2.3.0

Magento 2.4.7 compatibility release

### Changed

- Switch from Zend_Measurement to Magento Framework Measurement
- Use escaper instead of block for escaping in templates

### Removed

- Support for PHP 7.x
- Support for PHP 8.1

### Changed

- Switch from _DHL Retoure API_ to _DHL Parcel DE Returns API_.

## 2.2.2

### Changed

- Establish compatibility to `netresearch/module-shipping-core:2.9.0`.

### Fixed

- Prevent broken styling in module configuration, reported via [#50](https://github.com/netresearch/dhl-shipping-m2/issues/50).

## 2.2.1

### Fixed

- Do not persist empty QR labels from cross-border returns.

## 2.2.0

Magento 2.4.4 compatibility release

### Added

- Support for Magento 2.4.4

### Removed

- Support for PHP 7.1

## 2.1.0

### Added

- Import DHL Business Customer Portal _Receiver IDs_ CSV file to module configuration.
- Configure carrier title.

### Removed

- Remove obsolete config fields.

## 2.0.3

### Changed

- Move returns feature to shipping core and ui modules.

## 2.0.2

### Changed

- Print order number instead of billing number as customer reference on shipping labels.

## 2.0.1

### Changed

- Use `netresearch/module-shipping-core` package for three-letter country code calculation.

### Fixed

- Add missing sandbox _Receiver ID_ configuration for Italy.

## 2.0.0

### Changed

- Replace shipping core package dependency.

## 1.1.1

### Changed

- Update infobox text in module configuration.

## 1.1.0

Magento 2.4 compatibility release

### Added

- Support for Magento 2.4

### Removed

- Support for Magento 2.2

### Changed

- Update sandbox _Receiver IDs_ according to developer portal listing.

### Fixed

- Allow letters in _Participation Numbers_ configuration.

## 1.0.1

Bugfix release

### Fixed

- Update _Receiver IDs_ configuration setting for sandbox access. 

## 1.0.0

Initial release
