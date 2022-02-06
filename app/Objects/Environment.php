<?php

class Environment {

  // app enviromental variables
  const app_hostname = "127.0.0.1:8181";


  // set the following to true and add a message to disable the site to visitors
  const maint_mode = TRUE;
  const maint_message = "You need to enable the site in Environment.php";

  // database enviromental variables
  const db_host = "localhost";      // database host machine
  const db_user = "dbuser";         // database username
  const db_pass = "dbpass";         // database password
  const db_name = "dbuser/dbname";  // database name
  const db_prefix = "";             // table prefix

  // Raven authentication module enviromental variables
  const raven_cookieKey = "";       // this needs to be 32+ random characters

  // read only mode
  const db_read_only = FALSE;       // prevent INSERT UPDATE DELETE database operations

}

?>
