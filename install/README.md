TODO: installation should really be done with a standard installation method and scripts but until those scripts are 
created and tested this text file describes how to install the software on a Rasberry Pi.

You need to have a fairly good idea when and how to make scripts executable, when to copy files as root (using sudo), 
how to activate init.d scripts etc.

Install your favourite web server (I am using lighttpd).
Also install php5-cgi and php5-sqlite.

The Python scripts in "emonitor" and "pow-mon" are placed in: "/usr/local/bin"

The start scripts in "emonitor" and "pow-mon" are placed in: "/etc/init.d"

The PHP scripts in "php" are placed in: "/var/www/html" (depends on how your web server is configurated):

The SQLite databases will be created in: "/var/db"

A handy tool to inspect the SQLite databases is phpLiteAdmin (https://www.phpliteadmin.org/).
