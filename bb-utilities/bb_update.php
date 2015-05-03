<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
Copyright (C) 2012 - 2015  Kermit Will Richardson, Brimbox LLC

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License Version 3 (“GNU GPL v3”)
as published by the Free Software Foundation. 

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU GPL v3 for more details. 

You should have received a copy of the GNU GPL v3 along with this program.
If not, see http://www.gnu.org/licenses/
*/

$main->check_permission("bb_brimbox", 5);

@unlink("bb-less/lessc.inc.php");
@unlink("bb-less/license.txt");
@unlink("bb-config/bb_admin_index.css");
@unlink("bb-config-default/bb_admin_index.css");

$query = "ALTER TABLE users_table ADD CONSTRAINT users_table_unique_username UNIQUE (username);";
@pg_query($con, $query);

$query = "INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details) " .
         "SELECT (SELECT max(module_order) + 1 FROM modules_table), 'bb-admin/bb_upload_docs.php', 'bb_upload_docs', 'Upload Documents', 'bb_brimbox', 4, 'Core', 6, 0, '', '{\"company\":\"Brimbox\",\"author\":\"Brimbox Staff\",\"license\":\"GNU GPL v3\",\"description\":\"This is the admin module used for uploading documents, usually support documents, to the database.\"}' WHERE NOT EXISTS (SELECT 1 FROM modules_table WHERE module_name = 'bb_upload_docs')";
@pg_query($con, $query);

$body = "\$BODY\$";

/* NEW FUNCTION NAMES */

$query = <<<EOT
CREATE OR REPLACE FUNCTION bb_list_unset(bit, integer)
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
CREATE OR REPLACE FUNCTION bb_list(bit, integer)
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
CREATE OR REPLACE FUNCTION bb_list_set(bit, integer)
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
CREATE OR REPLACE FUNCTION bb_is_integer(txt_input text)
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
CREATE OR REPLACE FUNCTION bb_is_number(txt_input text)
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
CREATE OR REPLACE FUNCTION bb_is_date(txt_input text)
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
CREATE OR REPLACE FUNCTION bb_change_date()
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
CREATE OR REPLACE FUNCTION bb_create_date()
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
CREATE OR REPLACE FUNCTION bb_modify_date()
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

$query = <<<EOT
CREATE OR REPLACE function bb_key(text)
 RETURNS bigint LANGUAGE sql IMMUTABLE AS
$body
SELECT substr($1,2, strpos($1,':')-2)::bigint WHERE $1 ~ E'^[A-Z]\\\\d+:.*';
$body
EOT;
pg_query($con, $query);

$query = <<<EOT
CREATE OR REPLACE function bb_value(text)
 RETURNS text LANGUAGE sql IMMUTABLE AS
$body
SELECT substr($1, strpos($1,':') + 1, length($1))::text WHERE $1 ~ E'^[A-Z]\\\\d+:.*';
$body
EOT;
pg_query($con, $query);

/* UPDATE TRIGGER WITH NEW FUNCTION NAMES */
$query = <<<EOT
DROP TRIGGER IF EXISTS ts1_modify_date ON data_table;
CREATE TRIGGER ts1_modify_date
  BEFORE UPDATE
  ON data_table
  FOR EACH ROW
  EXECUTE PROCEDURE bb_modify_date();
-- Trigger: ts2_bb_create_date on data_table
DROP TRIGGER IF EXISTS ts2_create_date ON data_table;
CREATE TRIGGER ts2_create_date
  BEFORE INSERT
  ON data_table
  FOR EACH ROW
  EXECUTE PROCEDURE bb_create_date();
EOT;
@$result = pg_query($con, $query);
    
$query = <<<EOT
DROP TRIGGER IF EXISTS ts1_update_change_date ON log_table;
CREATE TRIGGER ts1_update_change_date
  BEFORE INSERT OR UPDATE
  ON log_table
  FOR EACH ROW
  EXECUTE PROCEDURE bb_change_date();
EOT;
$result = pg_query($con, $query);

$query = <<<EOT
DROP TRIGGER IF EXISTS ts1_update_change_date ON modules_table;
CREATE TRIGGER ts1_update_bb_change_date
  BEFORE INSERT OR UPDATE
  ON modules_table
  FOR EACH ROW
  EXECUTE PROCEDURE bb_change_date();
EOT;
@$result = pg_query($con, $query);

$query = <<<EOT
DROP TRIGGER IF EXISTS ts1_update_change_date ON users_table;
CREATE TRIGGER ts1_update_bb_change_date
  BEFORE INSERT OR UPDATE
  ON users_table
  FOR EACH ROW
  EXECUTE PROCEDURE bb_change_date();
EOT;
@$result = pg_query($con, $query);

$query = <<<EOT
DROP TRIGGER IF EXISTS ts1_update_change_date ON json_table;
CREATE TRIGGER ts1_update_change_date
  BEFORE INSERT OR UPDATE
  ON json_table
  FOR EACH ROW
  EXECUTE PROCEDURE bb_change_date();
EOT;
@$result = pg_query($con, $query);

/* NEW TABLE */
/* will not run if exists */
$query = "select * from pg_tables WHERE schemaname = 'public' and tablename = 'docs_table'";
$result = pg_query($con, $query);
$num_rows = pg_num_rows($result);
$do_docs_table = false;
if ($num_rows == 0)
    {
    $do_docs_table = true;
    }    
$query = <<<EOT
CREATE TABLE docs_table
(
  id bigserial NOT NULL,
  document bytea,
  filename text NOT NULL DEFAULT ''::text,
  username text NOT NULL DEFAULT ''::text,
  level smallint NOT NULL DEFAULT 0,
  change_date timestamp with time zone,  
  CONSTRAINT docs_table_pkey PRIMARY KEY (id),
  CONSTRAINT docs_table_unique_filename UNIQUE (filename)
)
WITH (
  OIDS=FALSE
);
ALTER SEQUENCE docs_table_id_seq CYCLE;
-- Trigger: ts1_update_change_date on docs_table
CREATE TRIGGER ts1_update_change_date
  BEFORE INSERT OR UPDATE
  ON docs_table
  FOR EACH ROW
  EXECUTE PROCEDURE bb_change_date();
EOT;
if ($do_docs_table)
    {
    $result = pg_query($con, $query);
    }
    
/* ALTER EVERYTHING TO TEXT */
/* This should run without errors */
for ($i=1;$i<=50;$i++)
    {
    $col = $main->pad("c",$i);
    $query = "ALTER TABLE data_table ALTER COLUMN " . $col . " TYPE text;";
    $result = pg_query($con, $query);
    $query = "ALTER TABLE data_table ALTER COLUMN " . $col . " SET DEFAULT ''::text;;";
    $result = pg_query($con, $query);

    }    
foreach (array("owner_name","updater_name") as $value)
    {
    $col = $main->pad("c",$i);
    $query = "ALTER TABLE data_table ALTER COLUMN " . $value . " TYPE text;";
    $result = pg_query($con, $query);
    $query = "ALTER TABLE data_table ALTER COLUMN " . $value . " SET DEFAULT ''::text;";
    $result = pg_query($con, $query);   

    }
foreach (array("username","email","action") as $value)
    {
    $col = $main->pad("c",$i);
    $query = "ALTER TABLE log_table ALTER COLUMN " . $value . " TYPE text;";
    $result = pg_query($con, $query);
    $query = "ALTER TABLE log_table ALTER COLUMN " . $value . " SET DEFAULT ''::text;";
    $result = pg_query($con, $query);    
    }
foreach (array("module_path","module_name","friendly_name","interface","module_version","module_files","module_details") as $value)
    {
    $col = $main->pad("c",$i);
    $query = "ALTER TABLE modules_table ALTER COLUMN " . $value . " TYPE text;";
    $result = pg_query($con, $query);
    $query = "ALTER TABLE modules_table ALTER COLUMN " . $value . " SET NOT NULL;";
    $result = pg_query($con, $query);
    $query = "ALTER TABLE modules_table ALTER COLUMN " . $value . " SET DEFAULT ''::text;";
    $result = pg_query($con, $query);
    }
foreach (array("username","email","hash","salt","fname","minit","lname","notes") as $value)
    {
    $col = $main->pad("c",$i);
    $query = "ALTER TABLE users_table ALTER COLUMN " . $value . " TYPE text;";
    $result = pg_query($con, $query);
    $query = "UPDATE users_table SET " . $value . " = '' WHERE " . $value . " IS NULL;";
    $result = pg_query($con, $query);
    $query = "ALTER TABLE users_table ALTER COLUMN " . $value . " SET NOT NULL;";
    $result = pg_query($con, $query);
    $query = "ALTER TABLE users_table ALTER COLUMN " . $value . " SET DEFAULT ''::text;";
    $result = pg_query($con, $query);
    }
foreach (array("lookup","jsondata") as $value)
    {
    $col = $main->pad("c",$i);
    $query = "ALTER TABLE json_table ALTER COLUMN " . $value . " TYPE text;";
    $result = pg_query($con, $query);
    $query = "ALTER TABLE json_table ALTER COLUMN " . $value . " SET DEFAULT ''::text;";
    $result = pg_query($con, $query);
    }



?>