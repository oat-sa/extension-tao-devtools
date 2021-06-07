extension-tao-devtools
======================

extension that gather development tools for TAO

# Scripts
## Item generator

Generates a tree of items. 

## To install the extension for the dev tools do the following 

Step 1:
Go the directory where your System is installed

Step 2:
Make sure Composer is working on your local machine by running the following command inside tao-project root,
```
composer 
```
Step 3:
Run the command
```
composer require "oat-sa/extension-tao-devtools":"4.2.3"
```
Step 4:
Go-to Extension Manager dashboard and enable taoDevTools.

You will see the Change in UI of Extensions manager.

### Options
```
  Optional Arguments:
    -i items_count, --items-count items_count (default: "2")
      Number of items in class. Can be int or range. Example: 5, 1-5
    -c class_count, --class-count class_count (default: "2")
      Number of classes in class. Can be int or range. Example: 5, 1-5
    -n nesting_level, --nesting-level nesting_level (default: "3")
      Nesting level. Can be int or range. Example: 5, 1-5
    -r own_root, --own_root own_root (default: true)
      Create a tree under individual root
    -k root_class, --root-class root_class (default: "http://www.tao.lu/Ontologies/TAOItem.rdf#Item")
      Root class
    -h help, --help help
      Prints a help statement
```
