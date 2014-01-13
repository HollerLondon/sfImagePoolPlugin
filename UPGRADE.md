UPGRADE TO RACKSPACE OPENCLOUD FROM CLOUDFILES
----------------------------------------------

Updating your project to use php-opencloud:

*Step 1*: Update `lib/vendor/rackspace` location to https://github.com/rackspace/php-opencloud.git/tags/V1.6.0 (you need version 1.6.0 because 1.7 onwards requires composer and doesn't work with symfony 1.4)

*Step 2*: Remove `autoload.yml` entry for rackspace

*Step 3*: Update `auth_host` in config - see _Customise plugin options_

*Step 4*: Clear cache, and you're good to go
