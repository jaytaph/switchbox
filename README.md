switchbox
=========

[![Build Status](https://travis-ci.org/jaytaph/switchbox.png?branch=master)](https://travis-ci.org/jaytaph/switchbox)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/jaytaph/switchbox/badges/quality-score.png?s=aea3f301d603f969b43ab5c230fe5e902d0c40e5)](https://scrutinizer-ci.com/g/jaytaph/switchbox/)
[![Code Coverage](https://scrutinizer-ci.com/g/jaytaph/switchbox/badges/coverage.png?s=1fe2f48eeb0a017f7b77085fab3472107fc02103)](https://scrutinizer-ci.com/g/jaytaph/switchbox/)

Implementation of a PHP client for the v2 telehash protocol

More information about telehash can be found at http://telehash.org



Installation
------------
  
 - Clone repository
 - Download composer.phar from getcomposer.org
 - Run the following command from the repository root to install dependencies
  
       
        php composer.phar install from the repository root


 - Run the test application: 
 
       
        php app.php

On initial run, it will create a new seed.json file in your project root. This will
be your public and private keys, and your nodename.

You can telnet to TCP port 42424 for an admin interface to control the application.
