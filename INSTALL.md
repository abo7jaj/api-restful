# DATABASE

1. Create a database
2. Import the file located in /includes/SQL/db.sql
3. you should have 4 tables : 
    * accounts      : manage authentifications
    * activity      : log events
    * api_auth_fail : ban system
    * authors       : a data sample
4. configure DB in /includes/configuration.php

# Apache server

Create a vhost, the application must be installed in root directory 
We use CURL and MySQLi modules for PHP
Nothing specific ..., setups are made in htaccess files included.

# output

many logfiles are created, by default there are under the root directory in ../logs/
we recommand to install a logrotate : [http://www.linuxcommand.org/man_pages/logrotate8.html](http://www.linuxcommand.org/man_pages/logrotate8.html)
