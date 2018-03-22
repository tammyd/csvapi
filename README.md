# CSVAPI

Provides a simple JSON API around any CSV file on the web.

Supports basic sorting and filtering of data. 

## Setup

### Requirements

* php >= 5.5 (Note: I have only tested with php 7)
* [composer](https://getcomposer.org/download/)

### Install dependencies

`$ composer install`

## Run

Locally, you can run the application using php's built in server using your port of choice

```
$ cd web
$ php -S 0.0.0.0:8000
```

## Usage

For all requests, [csv_file] must be a fully qualified url accessible by the php server.
The csv file's first row *must* contain the column headers.

### View all data

`http://0.0.0.0:8000/data?source=[csv_file]`

### Sort data

#### Sort ascending

`http://0.0.0.0:8000/data?source=[csv_file]&sort=[column]`

#### Sort specifying sort direction

`http://0.0.0.0:8000/data?source=[csv_file]&sort=[column]&sortDir=[asc|desc]`

#### Filter data

`http://0.0.0.0:8000/data?source=[csv_file]&filter=[filter_statement]`

The api supports both simple and combined filters.

A simple filter is one that only filters the data on a single column. 

A simple `[filter_statement]` is a statement that follows the format `[column][comparator][value]`. For example,
a csv file that had a `count` column could be filtered with statements like `count=1` or `count>=10`.

Supported comparators are `<`, `<=`, `=`, `>=`, `>`, and `!=`

Combination filters are also available, and can be used to perform AND or OR operations on simple filters.
They are represented in the filter statement using the keys `_AND_` and `_OR_`.

For example, a csv file that has both a `count` and `hits` columns could be filtered with statements like `count>1_AND_hits<10`.
Multiple operations can be chained together, and are evaluated left to right, so 

```
X AND Y OR Z => (X AND Y) OR Z
A OR B OR C and D => ((A OR B) OR C) AND D
```

#### Examples

* Lets look at the [airline safety data used by the FiveThirtyEight](https://github.com/fivethirtyeight/data/tree/master/airline-safety)

`http://0.0.0.0:8000/data?source=https://raw.githubusercontent.com/fivethirtyeight/data/master/airline-safety/airline-safety.csv`

* Sort descending by # of fatalities between 2000-2014

`http://0.0.0.0:8000/data?source=https://raw.githubusercontent.com/fivethirtyeight/data/master/airline-safety/airline-safety.csv&sortDir=desc&sort=fatalities_00_14`

* Only show airlines that have had 0 fatal incidents

`http://0.0.0.0:8000/data?source=https://raw.githubusercontent.com/fivethirtyeight/data/master/airline-safety/airline-safety.csv&filter=fatal_accidents_00_14=0_AND_fatal_accidents_85_99=0`


## Tech

Application built using [Silex]("silex.sensiolabs.org")

## Todo

* Error handling
* Performance handling. Add caching, etc. This is not the most performant way to handle data :S
* Support additional output formats (csv, xml, jsonp, etc)
* Support other input formats
* Tests
* Convert to python and host on AWS Lambda?
