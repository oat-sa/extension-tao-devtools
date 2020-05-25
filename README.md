extension-tao-devtools
======================

extension that gather develepment tools for TAO

# Scripts
## Item generator

Generates given number of items and classes. 

### Run
```shell script
php index.php '\oat\taoDevTools\models\ItemGenerator'
```
### Options
```
  Optional Arguments:
    -i items_count, --items-count items_count (default: "2")
      Number of items in class. Can be int or range. Example: 5, 1-5
    -c class_count, --class-count class_count (default: "2")
      Number of classes in class. Can be int or range. Example: 5, 1-5',
    -n nesting_level, --nesting-level nesting_level (default: "3")
      Nesting level. Can be int or range. Example: 5, 1-5',
    -r own_root, --own_root own_root (default: true)
      All classes create under own root
    -k root_class, --root-class root_class (default: "http://www.tao.lu/Ontologies/TAOItem.rdf#Item")
      Root class
    -h help, --help help
      Prints a help statement
```
