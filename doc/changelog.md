# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

# 1.2.8 - 2017-08-17
### Fixed
* Term now return excerpt instead of description
* various minor bugfix

# 1.2.7 - 2017-08-15
### Added
* Multisite support
### Changed
* edition url now handled as filter

# 1.2.6 - 2017-08-14
### Fixed
* Nginx compatibility issue
### Changed
* no more wp-config.php symlink, included in /web

# 1.2.5 - 2017-08-08
### Added
* Rocket Term model
* allow submenu page removal
### Changed
* ACF depth recursion max to 4

# 1.2.4 - 2017-08-06
### Changed
* set term description to content var to be iso with post

# 1.2.3 - 2017-08-05
### Added
* Added ACF-cleaner plugin
* Added ACF Group support for ACF Helper

# 1.2.2
### Added
* WooCommerce Support
  - see [timber integration](https://github.com/timber/timber/wiki/WooCommerce-Integration) for more details about templates.
* Added Query String support
 
# 1.2.1 - 2017-05-04
### Added
* Route options : value / assert / convert
 
### Fixed 
* double construct

# 1.2.0 - 2017-04-27
### Added
* Files manipulation are now included inside this project
* Route Override from Symfony/Route to raterize Customer Application with Silex syntax
* Added configuration file in documentation as sample
### Changed 
* app/cms to web/app for public folder accessibility protection


### Changed
* Fixed add_to_twig function registration

## 1.1.1 - 2017-02-19
### Added
* post-types-order plugin

## 1.1.0 - 2017-01-20
### Changed ###
* Main folder is now in `web/edition` and configuration in `app/cms`
* Removed johnpbloc/wordpress custom installer

## 1.0.0 - 2016-11-05 ##
### Added ###
* Composer update
* Use of ApplicationTrait

## 0.0.1 - 2016-11-05 ##
### Added ###
* Update rewrite and staging content
* Taxonomies, menus, post types from config
* Mu-plugins

## 0.0.0 - 2016-11-05 ##
### Added ###
* First version
