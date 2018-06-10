CSV Import (module for Omeka S)
===============================

[CSV Import] is a module for [Omeka S] and will allow users to import Entities
from a simple CSV (comma separated value) file, and then map the CSV column data
to Entity. Each row in the file represents metadata for a single Entity.

Furthermore, it’s possible to import directly files in formats [TSV] (tab
separated value), a simpler and more efficient format, and [ODS] (OpenDocument
Spreadsheet, the ISO standard office format for spreadsheets, managed by
[LibreOffice] and a lot of other tools) directly.

Most often, the import will create new Omeka S items. It is also possible to
import item sets, media and users, and other modules can add other import types.
It’s possible to import mixed resources in one file too.

See the [Omeka S user manual] for user documentation.

Installation
------------

See general end user documentation for [Installing a module].

To install CSV Import from the source, go to the root of the module, and run
either `composer install` or `gulp init` in order to get dependencies. The zip
is created with this command: `gulp zip --no-dev`.

To be able to import `ods`, the php extensions `zip` and `xml` should be
installed (default in most cases).

Quick test
----------

- Download the [example file]
- Go to the admin board > CSV Import.
- Upload the example file.
- Select the import type `Resource`
- Check the options `Automap with labels alone` and `Automap with user list`
  (the list should be the original one).
- Click `Next` in the upper right.
- Click `Import` with all default values.
- Wait some seconds, and your item sets, items and medias are imported!

Preparing your CSV file
-----------------------

### Format of the file

CSV files for import must be encoded in UTF-8.

The delimiter and the enclosure can be specified. The delimiter is generally the
"`,`", but it can be "`;`" or a tabulation. A file saved with tabulation as
delimiter allows to avoid most of the issues during import, because it is very
hard to add them in a spreadsheet. The enclosure is generally the double-quote
"`"`".

### Auto-mapping

Column headers that conform to property values as seen in the Properties list
under Vocabularies will be auto-mapped. For example, a CSV file with a column
header `dcterms:title` will be automapped to the Dublin Core Title property when
setting the mappings. It is possible to use long format too (`Dublin Core : Title`)
and even with  the label only (`Title`).

An option allows to set a personal list of headers to map the file automagically.
This is useful when the user uses a csv file that cannot be changed. In the text
field, each line should contains a header (with or without case), a "`=`" and
the property term or the mapping type. In that way, any header can be mapped to
any property and metadata. To reset to the default, remove all the values and
they will be reinitialized for the next import.

Importing entities
------------------

### Resources (Items, Item Sets, Media)

Resources import will take data from each row of your CSV file and create a new
Item from that data.

#### Global settings

The Global Settings allow you to set default data for each Item imported. You
can specify

* Item Set(s) by their IDs or any other unique identifier (generally
  `dcterms:identifier`)
* Owner by email address
* Resource Template by name
* Class by term (e.g., dctype:Text -- consult the Classes lists under
  Vocabularies for allowed values)

####  Media mapping

Media for items can be mapped to data in the columns via the button `Media source`.
The options conform to the regular media options when adding an Item, with the
exception that `Upload` is not an option for CSV files. Nevertheless, the module
[FileSideload] can be used to replace this feature and to upload files with
local paths on the server.

#### Metadata for Media

To import metadata for a media, the item should be imported first and it should
have a id or a unique identifier in order to attach the media to it. This
identifier must be available in one of the columns of the csv file.

### Item Data mapping

Data in the CSV file can be mapped to override / augment the Gobal Settings set
in the main tab. Clicking on the `Item Data` button will open mapping options
that will override the Global Settings.

### External modules

The data for content managed by external modules can be imported too, for example:

- [Mapping]
- [Folksonomy]

### Users

Importing Users from a CSV file must specify the following:

* Email address
* Role

Optionally, a Display Name should be specified. For simplicity, the email
address can also be mapped to the Display Name, though this is not recommended.
New users will not be active until they confirm their registration via an email
that will be sent to them.

Updating and deleting entities
------------------------------

In the main tab, an advanced option allows to update entities.

The update and the delete processes require that the resources to be identified
with a unique identifier, like the id or a specific `dcterms:identifier`.

In case of a duplicate, only the first will be processed. The processed
resources are logged.

**It is not possible to undo an update or a deletion.**

### Updating entities

Four modes of update are provided:

- append: add new data to complete the resource;
- revise: replace existing data to the resource by the ones set in each cell,
  except if empty (don’t modify data that are not provided, except default
  values);
- update: replace existing data to the resource by the ones set in each cell,
  even empty (don’t modify data that are not provided, except default values);
- replace: remove all properties of the resource, and fill new ones from the
  data.

### Deleting entities

The use of this option is dangerous.

**It is recommended to backup your base and your files before updating and deleting resources (see the warning and the license below).**

Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.

Troubleshooting
---------------

See online issues on the [Omeka forum] and the [module issues] page on GitHub.

License
-------

This plugin is published under [GNU/GPL].

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
details.

You should have received a copy of the GNU General Public License along with
this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

Contact
-------

Current maintainers:

* Roy Rosenzweig Center for History and New Media
* Daniel Berthereau (see [Daniel-KM] on GitHub)

Copyright
---------

* Copyright  Roy Rosenzweig Center for History and New Media, 2015-2017
* Copyright Daniel Berthereau, 2017-2018

[CSV Import]: https://github.com/Omeka-s-modules/CSVImport
[Omeka S]: https://omeka.org/s
[TSV]: https://en.wikipedia.org/wiki/Tab-separated_values
[ODS]: http://opendocumentformat.org/aboutODF
[LibreOffice]: https://www.libreoffice.org
[Omeka S user manual]: http://omeka.org/s/docs/user-manual/modules/csvimport/
[Installing a module]: http://dev.omeka.org/docs/s/user-manual/modules/#installing-modules
[example file]: https://github.com/Daniel-KM/Omeka-S-module-CSVImport/blob/master/test/CsvImportTest/_files/test_resources_heritage.ods
[FileSideload]: https://github.com/Omeka-s-modules/FileSideload
[Mapping]: https://github.com/Omeka-s-modules/Mapping
[Folksonomy]: https://github.com/Daniel-KM/Omeka-S-module-Folksonomy
[Omeka forum]: https://forum.omeka.org/c/omeka-s/modules
[module issues]: https://github.com/omeka-s-modules/CSVImport/issues
[GNU/GPL v3]: https://www.gnu.org/licenses/gpl-3.0.html
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
