<?php

   namespace MyProducts\Config\Development;
   use \Iplox\Config;
   
   class DbConfig extends Config
   {
        const PROVIDER = 'mysql';
        const NAME = 'ExampleDb';
        const USER = 'root';
        const PASSWORD = '';
   }
