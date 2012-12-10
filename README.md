# SilverSmith: Reshaping SilverStripe development
SilverSmith is a CLI tool for SilverStripe 3.0 development that will help you build projects faster and more easily. It features generators for code, templates, and content in addition to many other time-saving tools.

## Installation
Run this command in the root of your SilverStripe project directory.
```
git clone git://github.com/unclecheese/SilverSmith.git silversmith
```

To install the command globally, so that you can access the tools from any project, run the following command from the root of your project directory
```
silversmith/bin/cli_install
```
Once that is installed, you may optionally remove the "silversmith" directory from your project root.
```
rm -rf silversmith
```

## Get started in 60 seconds
SilverSmith provides example code that will help you see what it can do right away. In a new SilverStripe 3 project, run:
```
silversmith init --example
```
This will generate an example _project.yml file, which defines your page types and other data models, as well as a fixtures.txt file, which defines your site architecture. To create empty versions of these files, remove the --example flag.

```
silversmith build-code
```
This will generate PHP code for the project definition contained in _project.yml and build the database.

```
silversmith build-templates --autofill
```
This command creates templates for any page types that do not yet have one. The --autofill parameter will provide example template syntax for all of the fields and relationships contained in the model.

```
silversmith build-fixtures -seeding-level 3
```
This command will generate a site architecture (i.e. the CMS site tree) for the pages listed in fixtures.txt. The -seeding-level argument tells SilverSmith how much example content to inject into the new pages. The higher the level, the deeper the content generation. The maximum value is 3.


Refresh your site and you should have a series of pages and templates working!

## Getting Help

```
silversmith help
```
Shows a list of available commands and describes what they do

## Learning the YAML spec for _project.yml

```
silversmith spec
```
Shows an example _project.yml file with inline comments describing what each line means.

## Other useful features

### Content seeding
You can create content after the site architecture has been built, for example, adding products to your store.

```
silversmith seed-content Product -count 50
```
*Note*: The -count argument defaults to 10.
If the DataObject you want to create is paired with a holder page, you can specify its parent.

```
silversmith seed-content Product -parent my-store-page -parent-field StorePageID -count 50
```
*Note*: The -parent argument can contain a URL segment to the parent page or an integer representing its ID.
*Note*: The -parent-field argument defaults to ParentID.

Other arguments
```
silversmith seed-content NewsPage -parent 123 -count 50 --verbose -seeding-level 3
```
The --verbose argument shows a summary of every object as each is created.

### Content population
Sometimes after new fields are added, it is useful to re-seed the content into existing objects rather than creating new ones.

```
silversmith populate Product
```
If you have fields that already have authentic content that cannot be overwritten, you can specify the fields or relations to populate.

```
silversmith populate Product -fields Summary,Photo,Categories
```

## Staying up to date

```
silversmith upgrade
```
*Note*: This doesn't always work. SilverSmith will move to Composer in the near future to make the package management more simple. For now, it makes the most sense to run the following command:
```
git pull origin master
```
On your SilverSmith installation. If you have installed the CLI tools globally, this directory is /usr/local/lib/silversmith.

## Fixing MAMP

Users of MAMP will likely encounter an error:
*Can’t connect to local MySQL server through socket /tmp/mysql.sock*

This error should be detected when any SilverSmith command is executed. If you do encounter it, there is a simple fix:

```
silversmith fix-mamp
```
## More Information
Check out the video tutorial of the SilverSmith CLI features at LeftAndMain: http://www.leftandmain.com/category/silversmith-2/
