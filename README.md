iCalender Extension for Yii2 PHP Framework
===========================

This extension you can take and save any iCalender document to your database.

[![Latest Stable Version](https://poser.pugx.org/uguranyum/yii2-icalender/v/stable.png)](https://packagist.org/packages/uguranyum/yii2-icalender)
[![Total Downloads](https://poser.pugx.org/uguranyum/yii2-icalender/downloads.png)](https://packagist.org/packages/uguranyum/yii2-icalender)

REQUIREMENTS
------------
Yii2 PHP Framework


DOWNLOAD VIA COMPOSER
-------------------

```
composer require uguranyum/yii2-icalender
```



MIGRATE TO DATABASE
-------------------

```
yii migrate --migrationPath=@vendor/uguranyum/yii2-icalender/migration --interactive=0
```


ABOUT EXTENSION
------------
This extension you can and save any iCalender document to your database.
Extension first take iCalender to @vendor/uguranyum/yii2-icalender/temp folder.
After update calender information to the database.

Using:
```
$url        = 'http://ical.mac.com/ical/South32Korean32Holidays.ics';
$icalender  = new iCalender($url);
$icalender->cleanTemp();        //Clean the temp directory.
$icalender->id_calender_main    //Main Calender ID
```

