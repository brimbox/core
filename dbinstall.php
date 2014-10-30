<?php
/*
Copyright (C) 2012 - 2013 Kermit Will Richardson, Brimbox LLC

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License Version 3 (�GNU GPL v3�)
as published by the Free Software Foundation. 

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU GPL v3 for more details. 

You should have received a copy of the GNU GPL v3 along with this program.
If not, see http://www.gnu.org/licenses/
*/
/* Database Change Log
   2012.1.3 header added
   2012.1.4 locked column removed from users table
   2012.1.5 sample owner name data added to sample data
   2012.1.6 archive column added in
   2012.1.7 sample dates to sortable style
   2012.1.8 secure field added after archive field
   2012.1.9 changed search functionality and changes field lengths, deleted extra fields
   2012.1.10 added viewer user permission
   2012.1.11 added fts and ftg tsvector columns and indexes
   2012.1.12 changed password storage to text for SHA256 crypt
   2012.1.13 utf-8 check implemented
   2012.1.14 updates add for fts and ftg
   2012.1.15 added cycles
   2012.1.16 change made in is_number function, added is_integer
   2012.1.17 sequence cache set to 1 from 3 on data_table and log_table
   2012.1.18 added attempts field to users table
   2012.1.19 added index on row_type
   2013.1.20 dropdown xml root made plural and PHP string repeater function implemented for list column initialization
   2014.1.21 users_table column userrole changed to array of smallint and renamed to userroles
   2014.1.22 SQL statement added to GRANT user role to db owner
   2014.1.23 JSON table added and XML table dropped, modules_table and users_table changes BIG change
*/

/* Backup Change Log
   2012.1.2 Compatable with Version 2014.3.275
   2014.1.3 Database column userrole changed to userroles and from smallint to array of smallint
   2014.1.4 Convesion to JSON data structure, modules_table and users_table changed
*/

?>
<html>
<head>
<meta http-equiv="Pragma" content="no-cache">
<!-- Pragma content set to no-cache tells the browser not to cache the page
This may or may not work in IE -->
<meta http-equiv="expires" content="0">
</head>
<?php
//defined here for include
define('BASE_CHECK', true);
//need connection string variables
include("bb-config/bb_config.php");

/* BRIMBOX LOGIN */
$email = "admin";
$passwd = "password";
$db_version = "2014.1.22";
$program_version = "2014.4.397";
$backup_version = "2014.1.3";
//SHA256 standard for the program
//use a 36 char text salt instead of a binary one
//simple but effective salt generator
$salt = md5(microtime());
//reduce to 16 and append
$hash = hash('sha512', $passwd . $salt);

$con_string = "host=" . DB_HOST . "  dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASSWORD;
$con = pg_connect($con_string);

    if (!$con)
        {
        die("Cannot connect to database: " . pg_last_error());
        }

echo "PHP Version: " . phpversion() . "<br>";
$arr_postgres = pg_version();
echo "Postgres Version: " . $arr_postgres['client'] . "<br>";

$query = "SELECT encoding FROM pg_database WHERE datname = '" . DB_NAME . "' AND encoding = 6";
$result = pg_query($con, $query);

//possibly a permission error
if ($result)
	{
	$num_rows = pg_num_rows($result);
	if ($num_rows == 0)
		{
		echo "Warning: Database not encoded to UTF-8.<br>";
		}
	else
		{
		echo "Database encoding is UTF-8.<br>";	
		}
	}


/* INSTALL plpgsql */
// Generally you want to issue this command logged in as the db owner
$query = "SELECT 1 FROM pg_language WHERE lanname='plpgsql';";
$result = pg_query($con, $query);
$num_rows = pg_num_rows($result);
if ($num_rows == 0)
    {
    $query = "CREATE LANGUAGE plpgsql;";
    @$result = pg_query($con, $query);
    if (!pg_result_error($result))
        {
        die("<br>Error: Cannot proceed with installation. Please issue command \"CREATE LANGUAGE plpgsql;\" as the database owner."); 
        }
    }
    
/* GRANT PRIVILEGES TO OWNER */
//This is generally used in cPanel installs so you don't run the db off your cPanel password
if (DB_OWNER <> "")
    {
    $query = "GRANT " . DB_USER . " TO " . DB_OWNER . ";";
    pg_query($con, $query);
    }

/* INSTALL FUNCTIONS*/
$body = "\$BODY\$";

$query = <<<EOT
CREATE OR REPLACE FUNCTION list_reset(bit, integer)
  RETURNS bit AS
$body
  DECLARE
    right_pad INTEGER := 2000 - $2;
    left_pad INTEGER := $2 - 1;
    char_pad CHAR(2000) := lpad('', left_pad ,'0') || '1' || lpad('', right_pad, '0');
    bit_pad BIT(2000) := char_pad::bit(2000);
  BEGIN
    RETURN  ~bit_pad & $1;
  END;
$body
  LANGUAGE plpgsql VOLATILE
  COST 100;
EOT;
pg_query($con, $query);

$query = <<<EOT
CREATE OR REPLACE FUNCTION list_retrieve(bit, integer)
  RETURNS integer AS
$body
  BEGIN
    RETURN substring($1 FROM $2 FOR 1)::int;
  END;
$body
  LANGUAGE plpgsql VOLATILE
  COST 100;
EOT;
pg_query($con, $query);

$query = <<<EOT
CREATE OR REPLACE FUNCTION list_update(bit, integer)
  RETURNS bit AS
$body
  DECLARE
    right_pad INTEGER := 2000 - $2;
    left_pad INTEGER := $2 - 1;
    char_pad CHAR(2000) := lpad('', left_pad ,'0') || '1' || lpad('', right_pad, '0');
    bit_pad BIT(2000) := char_pad::bit(2000);
  BEGIN
    RETURN  bit_pad | $1;
  END;
$body
  LANGUAGE plpgsql VOLATILE
  COST 100;
EOT;
pg_query($con, $query);

$query = <<<EOT
CREATE OR REPLACE FUNCTION is_integer(txt_input text)
RETURNS INTEGER AS
$body
DECLARE nbr_value INTEGER DEFAULT NULL;
BEGIN
    BEGIN
        nbr_value := txt_input::INTEGER;
        EXCEPTION WHEN OTHERS THEN
        RETURN 0;
    END;
RETURN 1;
END;
$body
LANGUAGE plpgsql VOLATILE
COST 100;
EOT;
pg_query($con, $query);

$query = <<<EOT
CREATE OR REPLACE FUNCTION is_number(txt_input text)
RETURNS INTEGER AS
$body
DECLARE nbr_value FLOAT DEFAULT NULL;
BEGIN
    BEGIN
        nbr_value := txt_input::FLOAT;
        EXCEPTION WHEN OTHERS THEN
        RETURN 0;
    END;
RETURN 1;
END;
$body
LANGUAGE plpgsql VOLATILE
COST 100;
EOT;
pg_query($con, $query);

$query = <<<EOT
CREATE OR REPLACE FUNCTION is_date(txt_input text)
RETURNS INTEGER AS
$body
DECLARE dt_value TIMESTAMP DEFAULT NULL;
BEGIN
    BEGIN
        dt_value := txt_input::TIMESTAMP;
        EXCEPTION WHEN OTHERS THEN
        RETURN 0;
    END;
RETURN 1;
END;
$body
LANGUAGE plpgsql VOLATILE
COST 100;
EOT;
pg_query($con, $query);



$query = <<<EOT
CREATE OR REPLACE FUNCTION change_date()
  RETURNS trigger AS
$body
DECLARE
BEGIN
NEW.change_date  = now();
RETURN NEW;
END;
$body
  LANGUAGE plpgsql VOLATILE
  COST 100;
EOT;
pg_query($con, $query);

$query = <<<EOT
CREATE OR REPLACE FUNCTION create_date()
  RETURNS trigger AS
$body
DECLARE
BEGIN
NEW.create_date  = now();
NEW.modify_date  = now();
RETURN NEW;
END;
$body
  LANGUAGE plpgsql VOLATILE
  COST 100;
EOT;
pg_query($con, $query);

$query = <<<EOT
CREATE OR REPLACE FUNCTION modify_date()
  RETURNS trigger AS
$body
DECLARE
BEGIN
NEW.modify_date  = now();
RETURN NEW;
END;
$body
  LANGUAGE plpgsql VOLATILE
  COST 100;
EOT;
pg_query($con, $query);



    
$query = "select * from pg_tables WHERE schemaname = 'public' and tablename = 'data_table'";
$result = pg_query($con, $query);
$num_rows = pg_num_rows($result);
$do_data_table = false;
if ($num_rows == 0)
    {
    $do_data_table = true;
    }
	
$list_zeros = str_repeat("0", 2000);

$query = <<<EOT
CREATE TABLE data_table
(
  id bigserial NOT NULL,
  row_type integer NOT NULL DEFAULT (0),
  key1 bigint NOT NULL DEFAULT (-1),
  key2 bigint NOT NULL DEFAULT (-1),
  c01 character varying(255) NOT NULL DEFAULT ''::character varying,
  c02 character varying(255) NOT NULL DEFAULT ''::character varying,
  c03 character varying(255) NOT NULL DEFAULT ''::character varying,
  c04 character varying(255) NOT NULL DEFAULT ''::character varying,
  c05 character varying(255) NOT NULL DEFAULT ''::character varying,
  c06 character varying(255) NOT NULL DEFAULT ''::character varying,
  c07 character varying(255) NOT NULL DEFAULT ''::character varying,
  c08 character varying(255) NOT NULL DEFAULT ''::character varying,
  c09 character varying(255) NOT NULL DEFAULT ''::character varying,
  c10 character varying(255) NOT NULL DEFAULT ''::character varying,
  c11 character varying(255) NOT NULL DEFAULT ''::character varying,
  c12 character varying(255) NOT NULL DEFAULT ''::character varying,
  c13 character varying(255) NOT NULL DEFAULT ''::character varying,
  c14 character varying(255) NOT NULL DEFAULT ''::character varying,
  c15 character varying(255) NOT NULL DEFAULT ''::character varying,
  c16 character varying(255) NOT NULL DEFAULT ''::character varying,
  c17 character varying(255) NOT NULL DEFAULT ''::character varying,
  c18 character varying(255) NOT NULL DEFAULT ''::character varying,
  c19 character varying(255) NOT NULL DEFAULT ''::character varying,
  c20 character varying(255) NOT NULL DEFAULT ''::character varying,
  c21 character varying(255) NOT NULL DEFAULT ''::character varying,
  c22 character varying(255) NOT NULL DEFAULT ''::character varying,
  c23 character varying(255) NOT NULL DEFAULT ''::character varying,
  c24 character varying(255) NOT NULL DEFAULT ''::character varying,
  c25 character varying(255) NOT NULL DEFAULT ''::character varying,
  c26 character varying(255) NOT NULL DEFAULT ''::character varying,
  c27 character varying(255) NOT NULL DEFAULT ''::character varying,
  c28 character varying(255) NOT NULL DEFAULT ''::character varying,
  c29 character varying(255) NOT NULL DEFAULT ''::character varying,
  c30 character varying(255) NOT NULL DEFAULT ''::character varying,
  c31 character varying(255) NOT NULL DEFAULT ''::character varying,
  c32 character varying(255) NOT NULL DEFAULT ''::character varying,
  c33 character varying(255) NOT NULL DEFAULT ''::character varying,
  c34 character varying(255) NOT NULL DEFAULT ''::character varying,
  c35 character varying(255) NOT NULL DEFAULT ''::character varying,
  c36 character varying(255) NOT NULL DEFAULT ''::character varying,
  c37 character varying(255) NOT NULL DEFAULT ''::character varying,
  c38 character varying(255) NOT NULL DEFAULT ''::character varying,
  c39 character varying(255) NOT NULL DEFAULT ''::character varying,
  c40 character varying(255) NOT NULL DEFAULT ''::character varying,
  c41 character varying(255) NOT NULL DEFAULT ''::character varying,
  c42 character varying(255) NOT NULL DEFAULT ''::character varying,
  c43 character varying(255) NOT NULL DEFAULT ''::character varying,
  c44 character varying(255) NOT NULL DEFAULT ''::character varying,
  c45 character varying(255) NOT NULL DEFAULT ''::character varying,
  c46 character varying(255) NOT NULL DEFAULT ''::character varying,
  c47 character varying(255) NOT NULL DEFAULT ''::character varying,
  c48 character varying(255) NOT NULL DEFAULT ''::character varying,  
  c49 character varying(65536) NOT NULL DEFAULT ''::character varying,
  c50 character varying(65536) NOT NULL DEFAULT ''::character varying,
  archive smallint NOT NULL DEFAULT 0,
  secure smallint NOT NULL DEFAULT 0,
  create_date timestamp with time zone,
  modify_date timestamp with time zone,
  owner_name character varying(255) NOT NULL DEFAULT ''::character varying,
  updater_name character varying(255) NOT NULL DEFAULT ''::character varying,
  list_string bit(2000) NOT NULL DEFAULT B'$list_zeros'::"bit",
  fts tsvector,
  ftg tsvector,  
  CONSTRAINT data_table_pkey PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);
ALTER SEQUENCE data_table_id_seq CYCLE;
CREATE INDEX data_table_gist_fts
  ON data_table
  USING gin
  (fts);
CREATE INDEX data_table_gist_ftg
  ON data_table
  USING gin
  (ftg);
CREATE INDEX data_table_idx_row_type
  ON data_table
  USING btree
  (row_type);
CREATE INDEX data_table_idx_key1
  ON data_table
  USING btree
  (key1);
CREATE INDEX data_table_idx_key2
  ON data_table
  USING btree
  (key2);
-- Trigger: ts1_modify_date on data_table
CREATE TRIGGER ts1_modify_date
  BEFORE UPDATE
  ON data_table
  FOR EACH ROW
  EXECUTE PROCEDURE modify_date();
-- Trigger: ts2_create_date on data_table
CREATE TRIGGER ts2_create_date
  BEFORE INSERT
  ON data_table
  FOR EACH ROW
  EXECUTE PROCEDURE create_date();
EOT;
if ($do_data_table)
    {
    $result = pg_query($con, $query);
    echo "Data Table Created<br>";
    }
    
$query = <<<EOT
INSERT INTO data_table (row_type,key1,c01,c02,c03,c04,c05,c06,owner_name,updater_name)
VALUES (1,-1,'Angie','Terrier Mix','Jess Hammers','2001-01-05','Traverse City','Dog','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c04,c05,c06,owner_name,updater_name)
VALUES (1,-1,'Bambi','German Shepherd','Jamer Dewitt','2005-03-21','Buckley','Dog','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c04,c05,c06,owner_name,updater_name)
VALUES (1,-1,'Anne','Tabby','Jess Howards','2009-07-11','Traverse City','Cat','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c04,c05,c06,owner_name,updater_name)
VALUES (1,-1,'Bernard','St. Bernard','Mike Howard','2004-07-01','Traverse City','Dog','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c04,c05,c06,owner_name,updater_name)
VALUES (1,-1,'Berry','Paint ','Mary Davis','1999-04-02','Interlochen','Horse','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c04,c05,c06,owner_name,updater_name)
VALUES (1,-1,'Bradford','Tiger Cat','Jack Winters','2006-05-07','Traverse City','Cat','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c04,c05,c06,owner_name,updater_name)
VALUES (1,-1,'Bessy','Quarter Horse','Jill Jackson','2010-05-03','Traverse City','Horse','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c04,c05,c06,owner_name,updater_name)
VALUES (1,-1,'Carla','Palamino','BJ Wells','2010-03-05','Traverse City','Horse','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c49,owner_name,updater_name)
VALUES (2,1,'Food','125.00','Debit','Angie needed some wet food.','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c49,owner_name,updater_name)
VALUES (2,1,'Bone','5.50','Debit','Angie likes real bones only.','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c49,owner_name,updater_name)
VALUES (2,2,'Dog House','75.00','Debit','Bambi needed a new doghouse roof.','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c49,owner_name,updater_name)
VALUES (2,2,'Collar','50.00','Credit','Bambi needed a new dog collar.','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c49,owner_name,updater_name)
VALUES (2,2,'Treats','15.00','Debit','Bambi ran out of treats.','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c49,owner_name,updater_name)
VALUES (2,3,'Litter','30.00','Credit','Anne needed some more litter.','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c49,owner_name,updater_name)
VALUES (2,3,'Food','10.00','Debit','Anne only likes wet food.','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c49,owner_name,updater_name)
VALUES (2,4,'Food','56.00','Debit','Bernard will only eat dry food.','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c49,owner_name,updater_name)
VALUES (2,4,'Medicine','75.00','Debit','Bernard got his flea medicine.','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c49,owner_name,updater_name)
VALUES (2,5,'Food','12.50','Debit','Berry is wild about carrots.','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c49,owner_name,updater_name)
VALUES (2,5,'Hay','50.00','Debit','Berry was getting low on Hay.','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c49,owner_name,updater_name)
VALUES (2,6,'Food','10.00','Debit','Bradford is supposed to get fresh chicken.','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c49,owner_name,updater_name)
VALUES (2,6,'Food','22.50','Debit','Bradford likes a combination of wet and dry food.','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c49,owner_name,updater_name)
VALUES (2,7,'Medicine','600.00','Debit','Bessy needed an antibiotic.','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c49,owner_name,updater_name)
VALUES (2,7,'Hay','125.00','Debit','Bessy was getting low on hay.','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c49,owner_name,updater_name)
VALUES (2,8,'Food','200.00','Debit','Carla needed some hay.','Sample','Sample');
INSERT INTO data_table (row_type,key1,c01,c02,c03,c49,owner_name,updater_name)
VALUES (2,8,'Food','100.00','Debit','Carla ran out of sweet oats.','Sample','Sample');
UPDATE data_table SET fts = to_tsvector(c01 || ' ' || regexp_replace(c01, E'(\\W)+', ' ', 'g') || ' ' || c49 || ' ' || regexp_replace(c49, E'(\\W)+', ' ', 'g')), ftg = to_tsvector(c01 || ' ' || regexp_replace(c01, E'(\\W)+', ' ', 'g') || ' ' || c49 || ' ' || regexp_replace(c49, E'(\\W)+', ' ', 'g'))  WHERE row_type = 2;
UPDATE data_table SET fts = to_tsvector(c01 || ' ' || regexp_replace(c01, E'(\\W)+', ' ', 'g') || ' ' || c02 || ' ' || regexp_replace(c02, E'(\\W)+', ' ', 'g') || ' ' || c03 || regexp_replace(c03, E'(\\W)+', ' ', 'g') || ' ' || ' ' || c05 || regexp_replace(c05, E'(\\W)+', ' ', 'g') || ' ' || ' ' || c06 || regexp_replace(c06, E'(\\W)+', ' ', 'g')), ftg = to_tsvector(c01 || ' ' || regexp_replace(c01, E'(\\W)+', ' ', 'g') || ' ' || c02 || ' ' || regexp_replace(c02, E'(\\W)+', ' ', 'g') || ' ' || c03 || regexp_replace(c03, E'(\\W)+', ' ', 'g') || ' ' || ' ' || c05 || regexp_replace(c05, E'(\\W)+', ' ', 'g') || ' ' || ' ' || c06 || regexp_replace(c06, E'(\\W)+', ' ', 'g')) WHERE row_type = 1;
EOT;
if ($do_data_table)
    {
    $result = pg_query($con, $query);
    echo "Data Table Populated<br>";
    }
	
$query = "select * from pg_tables WHERE schemaname = 'public' and tablename = 'log_table'";
$result = pg_query($con, $query);
$num_rows = pg_num_rows($result);
$do_log_table = false;
if ($num_rows == 0)
    {
    $do_log_table = true;
    }
 
    
$query = <<<EOT
CREATE TABLE log_table
(
  id bigserial NOT NULL,
  email character varying(255) NOT NULL DEFAULT ''::character varying,
  ip_address cidr,
  action character varying(255) NOT NULL DEFAULT ''::character varying,
  change_date timestamp with time zone,  
  CONSTRAINT log_table_pkey PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);
ALTER SEQUENCE log_table_id_seq CYCLE;
-- Trigger: ts1_update_change_date on log_table
CREATE TRIGGER ts1_update_change_date
  BEFORE INSERT OR UPDATE
  ON log_table
  FOR EACH ROW
  EXECUTE PROCEDURE change_date();
EOT;
if ($do_log_table)
    {
    $result = pg_query($con, $query);
    echo "Log Table Created (No Population)<br>";
    }

    
$query = "select * from pg_tables WHERE schemaname = 'public' and tablename = 'modules_table'";
$result = pg_query($con, $query);
$num_rows = pg_num_rows($result);
$do_modules_table = false;
if ($num_rows == 0)
    {
    $do_modules_table = true;
    }

$query = <<<EOT
CREATE TABLE modules_table
(
  id serial NOT NULL,
  module_order int,
  module_path text,
  module_name character varying(255),
  friendly_name character varying(255),
  interface character varying(255),
  module_type smallint,
  module_version character varying(255),
  standard_module smallint,
  maintain_state smallint,
  module_files text,
  module_details text,
  change_date timestamp with time zone,
  CONSTRAINT modules_table_pkey PRIMARY KEY (id),
  CONSTRAINT modules_table_unique_module_name UNIQUE (module_name)
)
WITH (
  OIDS=FALSE
);
ALTER SEQUENCE modules_table_id_seq RESTART CYCLE;
CREATE TRIGGER ts1_update_change_date
  BEFORE INSERT OR UPDATE
  ON modules_table
  FOR EACH ROW
  EXECUTE PROCEDURE change_date();
EOT;
if ($do_modules_table)
    {
    $result = pg_query($con, $query);
    echo "Module Table Created<br>";
    }

$query = <<<EOT
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (0, 'bb-primary/bb_delete.php', 'bb_delete', 'Delete', 'bb_brimbox', 0, 'Core', 2, 0, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the delete hidden page which is activated from the \"delete\" link when records are returned on the standard pages. It allows for deleting individual records or cascaded deletes which include all child records."}'); 
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (0, 'bb-primary/bb_archive.php', 'bb_archive', 'Archive', 'bb_brimbox', 0, 'Core', 2, 0, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the archive hidden module which is activated from the \"archive\" link when records are returned on the standard pages. It allows for archiving cascaded records, which include all child records."}'); 
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (0, 'bb-primary/bb_secure.php', 'bb_secure', 'Secure', 'bb_brimbox', 0, 'Core', 2, 0, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the secure hidden module which is activated from the \"secure\" link when records are returned on the standard pages. It allows for securing cascaded records, which include all child records, so that records can be hidden from guest users."}'); 
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (0, 'bb-primary/bb_listchoose.php', 'bb_listchoose', 'List Choose', 'bb_brimbox', 0, 'Core', 2, 0, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is a hidden page which is activated from the \"list\" link when records are returned on the standard pages. It allows for adding an individual records to lists."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (1, 'bb-primary/bb_guest.php', 'bb_guest', 'Guest', 'bb_brimbox', 1, 'Core', 0, 1, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the guest landing page which includes the logout link, basic session stats such as user an database, and space to place customized links and information regarding the database."}'); 
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (1, 'bb-primary/bb_viewer.php', 'bb_viewer', 'Viewer', 'bb_brimbox', 2, 'Core', 0, 1, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the viewer landing page which includes the logout link, basic session stats such as user an database, and space to place customized links and information regarding the database."}'); 
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (1, 'bb-primary/bb_home.php', 'bb_home', 'Home', 'bb_brimbox', 3, 'Core', 0, 1, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the home landing page for users, superusers, and admins which includes the logout link, basic session stats such as user an database, and space to place customized links and information regarding the database."}'); 
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (2, 'bb-primary/bb_browse.php', 'bb_browse', 'Browse', 'bb_brimbox', 3, 'Core', 6, 1, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the standard browse page for users. It allows users to browse data by choosing the first character of the data in a particular column."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (3, 'bb-primary/bb_lookup.php', 'bb_lookup', 'Lookup', 'bb_brimbox', 3, 'Core', 6, 1, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the standard lookup page for users. It allows users to browse data by choosing in a particular column by value or part of value."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (4, 'bb-primary/bb_search.php', 'bb_search', 'Search', 'bb_brimbox', 3, 'Core', 6, 1, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the standard search page for users. It allows user to search data based on content using boolean expressions."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (5, 'bb-primary/bb_view.php', 'bb_view', 'View', 'bb_brimbox', 3, 'Core', 6, 1, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the standard view page used when drilling down into child records."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (6, 'bb-primary/bb_details.php', 'bb_details', 'Details', 'bb_brimbox', 3,'Core', 6, 1, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the standard details page used when viewing details of a particular record. It also has functionality for changing the linking of a record."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (7, 'bb-primary/bb_cascade.php', 'bb_cascade', 'Cascade', 'bb_brimbox', 3, 'Core', 6, 1, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the standard cascade tab used to view a record and all its child records all at once."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (8, 'bb-primary/bb_input.php', 'bb_input', 'Input', 'bb_brimbox', 3, 'Core', 6, 1, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the standard input page for inputting data to the database. It checks for keys, required fields, and data validation."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (9, 'bb-primary/bb_queue.php', 'bb_queue', 'Queue', 'bb_brimbox', 3, 'Core', 6, 1, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the standard queue page used for receiving email sent to the database."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (10, 'bb-primary/bb_listview.php', 'bb_listview', 'List', 'bb_brimbox', 3, 'Core', 6, 1, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the standard page for viewing records contained in a list."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (1, 'bb-admin/bb_create_lists.php', 'bb_create_lists', 'Manage Lists', 'bb_brimbox', 4, 'Core', 6, 0, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the superuser module used in creating lists by layout type."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (2, 'bb-admin/bb_dropdowns.php', 'bb_dropdowns', 'Manage Dropdowns', 'bb_brimbox', 4, 'Core', 6, 0, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the superuser module used when creating dropdowns for the input page."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (3, 'bb-admin/bb_upload_data.php', 'bb_upload_data', 'Upload Data', 'bb_brimbox', 4, 'Core', 6, 0, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the admin module used for bulk loads of data to the database."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (1, 'bb-admin/bb_manage_users.php', 'bb_manage_users', 'Manage Users', 'bb_brimbox', 5, 'Core', 6, 0, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the admin module used for managing users and their permissions."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (2, 'bb-admin/bb_manage_log.php', 'bb_manage_log', 'Manage Log', 'bb_brimbox', 5, 'Core', 6, 0, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the admin module used to log certain actions in the database."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (3, 'bb-admin/bb_layout_names.php', 'bb_layout_names', 'Set Layout Names', 'bb_brimbox', 5, 'Core', 6, 0, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the admin module used for setting up layouts and their respective links."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (4, 'bb-admin/bb_column_names.php', 'bb_column_names', 'Set Column Names', 'bb_brimbox', 5, 'Core', 6, 0, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the admin module used for setting up column names, their row and order properties, and their validation values."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (5, 'bb-admin/bb_create_key.php', 'bb_create_key', 'Create Key', 'bb_brimbox', 5, 'Core', 6, 0, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the admin module for defining a key column for unique values."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (6, 'bb-admin/bb_manage_modules.php', 'bb_manage_modules', 'Manage Modules', 'bb_brimbox', 5, 'Core', 6, 0, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the admin module for uploading modules and installing program updates."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)
VALUES (7, 'bb-admin/bb_backup_restore.php', 'bb_backup_restore', 'Backup and Restore', 'bb_brimbox', 5, 'Core', 6, 0, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU v3","description":"This is the module for backing up, cleansing, and restoring an encrypted brimbox database."}');
EOT;
if ($do_modules_table)
    {
    $result = pg_query($con, $query);
    echo "Module Table Populated<br>";
    }
    
$query = "select * from pg_tables WHERE schemaname = 'public' and tablename = 'users_table'";
$result = pg_query($con, $query);
$num_rows = pg_num_rows($result);
$do_users_table = false;
if ($num_rows == 0)
    {
    $do_users_table = true;
    }

$query = <<<EOT
CREATE TABLE users_table
(
  id serial NOT NULL,
  email character varying(255),
  hash text,
  salt text,
  attempts smallint NOT NULL DEFAULT 0,
  userroles text[] NOT NULL DEFAULT '{"5_bb_brimbox"}',
  fname character varying(255),
  minit character varying(255),
  lname character varying(255),
  ips cidr[] NOT NULL DEFAULT ''{0.0.0.0/0,0:0:0:0:0:0:0:0/0}'',
  change_date timestamp with time zone,
  CONSTRAINT users_table_pkey PRIMARY KEY (id),
  CONSTRAINT users_table_unique_email UNIQUE (email)
)
WITH (
  OIDS=FALSE
);
ALTER SEQUENCE users_table_id_seq RESTART CYCLE;
-- Trigger: ts1_update_change_date on users_table
CREATE TRIGGER ts1_update_change_date
  BEFORE INSERT OR UPDATE
  ON users_table
  FOR EACH ROW
  EXECUTE PROCEDURE change_date();
EOT;
if ($do_users_table)
    {
    $result = pg_query($con, $query);
    echo "Users Table Created<br>";
    }

$query = <<<EOT
INSERT INTO users_table(email, hash, salt, userroles)
VALUES ('$email', '$hash', '$salt', '{"5_bb_brimbox"}');
EOT;
if ($do_users_table)
    {
    $result = pg_query($con, $query);
    echo "Users Table Populated<br>";
    }
    
    
$query = "select * from pg_tables WHERE schemaname = 'public' and tablename = 'json_table'";
$result = pg_query($con, $query);
$num_rows = pg_num_rows($result);
$do_json_table = false;
if ($num_rows == 0)
    {
    $do_json_table = true;
    }
   
$query = <<<EOT
CREATE TABLE json_table
(
  id serial NOT NULL,
  lookup character varying(255) NOT NULL DEFAULT ''::character varying,
  jsondata text,
  change_date timestamp with time zone,
  CONSTRAINT json_table_pkey PRIMARY KEY (id),
  CONSTRAINT json_table_unique_lookup UNIQUE (lookup)
)
WITH (
  OIDS=FALSE
);
ALTER SEQUENCE json_table_id_seq RESTART CYCLE;
-- Trigger: ts1_update_change_date on xml_table
CREATE TRIGGER ts1_update_change_date
  BEFORE INSERT OR UPDATE
  ON json_table
  FOR EACH ROW
  EXECUTE PROCEDURE change_date();
EOT;
if ($do_json_table)
    {
    $result = pg_query($con, $query);
    echo "JSON Table Created<br>";
    }
     
$query = <<<EOT
INSERT INTO json_table (lookup, jsondata)
VALUES('bb_layout_names','{"1":{"singular":"Animal","plural":"Animals","parent":0,"order":1,"secure":0,"autoload":""},"2":{"singular":"Expense","plural":"Expenses","parent":1,"order":2,"secure":0,"autoload":""}}');
INSERT INTO json_table (lookup, jsondata)
VALUES('bb_column_names','{"2":{"layout":{"primary":1,"count":4,"unique":0},"1":{"name":"Topic","row":1,"length":"short","order":1,"type":"Text","required":1,"secure":0,"search":1},"2":{"name":"Cost","row":1,"length":"short","order":2,"type":"Money","required":0,"secure":0,"search":0},"3":{"name":"Type","row":1,"length":"short","order":3,"type":"Text","required":0,"secure":0,"search":1},"49":{"name":"Note","row":2,"length":"note","order":4,"type":"","required":0,"secure":0,"search":1}},"1":{"layout":{"primary":1,"count":6,"unique":0},"1":{"name":"Name","row":1,"length":"medium","order":1,"type":"Text","required":1,"secure":0,"search":1},"2":{"name":"Breed","row":1,"length":"medium","order":2,"type":"Text","required":0,"secure":0,"search":1},"6":{"name":"Type","row":2,"length":"medium","order":3,"type":"Text","required":0,"secure":0,"search":1},"4":{"name":"Birthday","row":2,"length":"medium","order":4,"type":"Date","required":0,"secure":0,"search":0},"3":{"name":"Owner","row":3,"length":"medium","order":5,"type":"Text","required":0,"secure":0,"search":1},"5":{"name":"Location","row":3,"length":"medium","order":6,"type":"Text","required":0,"secure":0,"search":1}}}');
INSERT INTO json_table (lookup, jsondata)
VALUES('bb_dropdowns','{"1":{"6":["Cat","Dog","Horse"]},"2":{"3":["Credit","Debit","No Charge"]}}');
INSERT INTO json_table (lookup, jsondata)
VALUES('bb_create_lists','{"1":{"1":{"name":"Dog","archive":0,"description":0},"2":{"name":"Cat","archive":0,"description":0}}}');
EOT;
if ($do_json_table)
    {
    $result = pg_query($con, $query);
    echo "JSON Table Populated<br>";
    }
    
    

echo "<body><p>You have successfully installed the database. You may delete this file now.</p></body>";