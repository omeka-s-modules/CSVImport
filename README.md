# CSVImport

This module will allow users to import Entities from a simple CSV (comma separated value) file, and then map the CSV column data to Entity. Each row in the file represents metadata for a single Entity.

Most often, the import will create new Omeka S Items. It is also possible to import Users, and other modules can add other import types.

## Preparing your CSV file

CSV files for import must be encoded in UTF-8.

### Items import

Items import will take data from each row of your CSV file and create a new Item from that data.

The Global Settings allow you to set default data for each Item imported. You can specify

* Item Set(s) by their IDs
* Owner by email address
* Resource Template by name
* Class by term (e.g., dctype:Text -- consult the Classes lists under Vocabularies for allowed values)
* 

#### Property mapping

##### Auto-mapping

Column headers that conform to property values as seen in the Properties list under Vocabularies will be auto-mapped. For example, a CSV file with a column header `dcterms:title` will be automapped to the Dublin Core Title property when setting the mappings. 

#### Media mapping

Media for Items can be mapped to data in the columns. The options conform to the regular media options when adding an Item, with the exception that `Upload` is not an option for CSV files. 


#### Item Data mapping

Data in the CSV file can be mapped to override / augment the Gobal Settings described above. Clicking on the `Item Data` button will open mapping options that will override the Global Settings.

### Users import

Importing Users from a CSV file must specify the following

* Email address
* Role
* 

Optionally, a Display Name should be specified. For simplicity, the email address can also be mapped to the Display Name, though this is not recommended. New users will not be active until they confirm their registration via an email that will be sent to them.

