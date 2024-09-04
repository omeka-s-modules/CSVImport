CSV Import
===============================

The CSV Import module allows you to import items, item sets, media, and users into your Omeka S install from a CSV (comma-separated values), TSV (tab-separated values), or ODS (OpenDocument Spreadsheet) file. This module is only available to Global Administrator and Supervisor users.

You can also use CSV Import to modify existing items by adding extra metadata, replacing or deleting existing metadata, and deleting items. These are advanced features and must be used with caution. 

CSV Import requires your Omeka S installation to have PHP working in order to run background import jobs. Before using CSV Import, you should confirm that PHP is being recognized from the System Information page. 

To ingest media with CSV Import, the material must be online already and accessible via a URL or format-specific identifier. You cannot upload files from your computer using this module. 

Your CSV file must have a header row in order for the module to process it correctly, so you may need to add a row at the top with column names.

If you have multiple inputs for a single property, you can separate them with a secondary multivalue separator. For example, a work with multiple authors (E.B. White and William Strunk Jr.) with the column for Creator containing "E.B. White;William Strunk Jr" has a semicolon (;) as the multivalue separator. 

To find the terms you should use for your column headers, go to the Vocabularies tab from the admin dashboard. Click on the number of properties for the vocabulary you want to use (for example, Dublin Core).

In the table of vocabulary properties, there is a column for Term. Use the Term as the column heading for the property you want to automap in CSV Import. For example, "dcterms:abstract" would automap to the Dublin Core property "Abstract" and "foaf:firstName" would automap to the Friend of a Friend property "firstName". You can manually map each column to its corresponding property, and you are required to manually map non-metadata columns, such as the file URL for upload.

When ingesting media, choose the intended sourcing method from the dropdown:

- HTML
- IIIF image (link)
- IIIF presentation (link)
- oEmbed (link)
- URL
- YouTube (link)
- Other options may appear here based on your active modules, such as File Sideload.

## Advanced features

The "Action" setting allows you to change the action of process from a straight import to one of the following options:

- Create a new resource: Default option. Each row in the CSV will become a new resource (default import).
- Append data to the resource: Add new data to the resource, based on an identifier for an existing resource. (Cannot be undone.) This option allows you to supply multiple values for the same item; each row will be appended (that is, you can append one title to an item in one row, and append another title to the same item in another row). Note that you cannot supply resource template or class assignations in the rows of your CSV with an Append process; you will get an error.
- Revise data of the resource: Replace existing data of the resource with data from the CSV, except if the corresponding cell in the CSV is empty. (Cannot be undone.)
- Update data of the resource: Replace existing data of the resource with data from the CSV, even when the corresponding cell in the CSV is empty. (Cannot be undone.)
- Replace all data of the resource: Remove all properties of the resource, and fill with new information from the sheet. (Cannot be undone.)
- Delete the resource: Delete all matching resources. (Cannot be undone.)

See the [Omeka S user manual](http://omeka.org/s/docs/user-manual/modules/csvimport/) for user documentation.

Installation
------------

See general end user documentation for [Installing a module](http://omeka.org/s/docs/user-manual/modules/#installing-modules).

To install CSV Import from the source, go to the root of the module, and run `composer install`. Users
using the pre-packaged downloads from the Releases page or the omeka.org module directory don't need
to worry about this step.

To be able to import `ods`, the php extensions `zip` and `xml` should be installed (default in most cases).

Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check your archives regularly so you can roll back if needed.

Troubleshooting
---------------

See online issues on the [Omeka forum] and the [module issues] page on GitHub.

License
-------

This plugin is published under [GNU/GPL v3].

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

Contact
-------

Current maintainers:

* Roy Rosenzweig Center for History and New Media
* Daniel Berthereau (see [Daniel-KM] on GitHub)

Copyright
---------

* Copyright Roy Rosenzweig Center for History and New Media, 2015-2019
* Copyright Daniel Berthereau, 2017-2019

[CSV Import]: https://github.com/Omeka-s-modules/CSVImport
[Omeka S]: https://omeka.org/s
[TSV]: https://en.wikipedia.org/wiki/Tab-separated_values
[ODS]: http://opendocumentformat.org/aboutODF
[LibreOffice]: https://www.libreoffice.org
[Omeka forum]: https://forum.omeka.org/c/omeka-s/modules
[module issues]: https://github.com/omeka-s-modules/CSVImport/issues
[GNU/GPL v3]: https://www.gnu.org/licenses/gpl-3.0.html
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"

