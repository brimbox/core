<?php
/*
 * Copyright Kermit Will Richardson, Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 3 (GNU GPL v3)
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU GPL v3 for more details.
 *
 * You should have received a copy of the GNU GPL v3 along with this program.
 * If not, see http://www.gnu.org/licenses/
*/
/*
 * Database Change Log
 * 2012.1.3 header added
 * 2012.1.4 locked column removed from users table
 * 2012.1.5 sample owner name data added to sample data
 * 2012.1.6 archive column added in
 * 2012.1.7 sample dates to sortable style
 * 2012.1.8 secure field added after archive field
 * 2012.1.9 changed search functionality and changes field lengths, deleted extra fields
 * 2012.1.10 added viewer user permission
 * 2012.1.11 added fts and ftg tsvector columns and indexes
 * 2012.1.12 changed password storage to text for SHA256 crypt
 * 2012.1.13 utf-8 check implemented
 * 2012.1.14 updates add for fts and ftg
 * 2012.1.15 added cycles
 * 2012.1.16 change made in is_number function, added is_integer
 * 2012.1.17 sequence cache set to 1 from 3 on data_table and log_table
 * 2012.1.18 added attempts field to users table
 * 2012.1.19 added index on row_type
 * 2013.1.20 dropdown xml root made plural and PHP string repeater function implemented for list column initialization
 * 2014.1.21 users_table column userrole changed to array of smallint and renamed to userroles
 * 2014.1.22 SQL statement added to GRANT user role to db owner
 * 2014.1.23 JSON table added and XML table dropped, modules_table and users_table changes BIG change
 * 2015.1.24 ips column added to users_table
 * 2015.1.25 added username and note column to users_table, username to log table
 * 2015.1.26 added unique constraint to username column
 * //nomenclature change
 * 1.27 added docs table and renamed functions to start with bb
 * 1.28 text fields instead of varchar
 * 2.0 module_slug and module_url added
 * 2.3 added row to insert and changed trigger on modify date
*/

/*
 * Backup Change Log
 * 2012.1.2 Compatable with Version 2014.3.275
 * 2014.1.3 Database column userrole changed to userroles and from smallint to array of smallint
 * 2014.1.4 Convesion to JSON data structure, modules_table and users_table changed
 * 2014.1.5 added username to log and users tables and text field to users table
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
// defined here for include
define('BASE_CHECK', true);
// need connection string variables
include ("bb-config/bb_config.php");

/* BRIMBOX LOGIN */
$email = "admin";
$username = "admin";
$passwd = "password";

// SHA256 standard for the program
// use a 36 char text salt instead of a binary one
// simple but effective salt generator
$salt = md5(microtime());
// reduce to 16 and append
$hash = hash('sha512', $passwd . $salt);

$con_string = "host=" . DB_HOST . "  dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASSWORD;
$con = pg_connect($con_string);

if (!$con) {
    die("Cannot connect to database: " . pg_last_error());
}

echo "PHP Version: " . phpversion() . "<br>";
$arr_postgres = pg_version();
echo "Postgres Version: " . $arr_postgres['client'] . "<br>";

$query = "SELECT encoding FROM pg_database WHERE datname = '" . DB_NAME . "' AND encoding = 6";
$result = pg_query($con, $query);

// possibly a permission error
if ($result) {
    $num_rows = pg_num_rows($result);
    if ($num_rows == 0) {
        echo "Warning: Database not encoded to UTF-8.<br>";
    }
    else {
        echo "Database encoding is UTF-8.<br>";
    }
}

/* INSTALL plpgsql */
// Generally you want to issue this command logged in as the db owner
$query = "SELECT 1 FROM pg_language WHERE lanname='plpgsql';";
$result = pg_query($con, $query);
$num_rows = pg_num_rows($result);
if ($num_rows == 0) {
    $query = "CREATE LANGUAGE plpgsql;";
    @$result = pg_query($con, $query);
    if (!pg_result_error($result)) {
        die("<br>Error: Cannot proceed with installation. Please issue command \"CREATE LANGUAGE plpgsql;\" as the database owner.");
    }
}

/* GRANT PRIVILEGES TO OWNER */
// This is generally used in cPanel installs so you don't run the db off your cPanel password
if (DB_OWNER != "") {
    $query = "GRANT " . DB_USER . " TO " . DB_OWNER . ";";
    pg_query($con, $query);
}

/* INSTALL FUNCTIONS */
$body = "\$BODY\$";

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
CREATE OR REPLACE FUNCTION bb_join_date()
  RETURNS trigger AS
$body
DECLARE
BEGIN
NEW.join_date  = now();
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
SELECT substr($1, 2, strpos($1,':') - 2)::bigint WHERE $1 ~ E'^[A-Z]\\\\d+:.*';
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

$query = "select * from pg_tables WHERE schemaname = 'public' and tablename = 'data_table'";
$result = pg_query($con, $query);
$num_rows = pg_num_rows($result);
$do_data_table = false;
if ($num_rows == 0) {
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
  c01 text NOT NULL DEFAULT ''::text,
  c02 text NOT NULL DEFAULT ''::text,
  c03 text NOT NULL DEFAULT ''::text,
  c04 text NOT NULL DEFAULT ''::text,
  c05 text NOT NULL DEFAULT ''::text,
  c06 text NOT NULL DEFAULT ''::text,
  c07 text NOT NULL DEFAULT ''::text,
  c08 text NOT NULL DEFAULT ''::text,
  c09 text NOT NULL DEFAULT ''::text,
  c10 text NOT NULL DEFAULT ''::text,
  c11 text NOT NULL DEFAULT ''::text,
  c12 text NOT NULL DEFAULT ''::text,
  c13 text NOT NULL DEFAULT ''::text,
  c14 text NOT NULL DEFAULT ''::text,
  c15 text NOT NULL DEFAULT ''::text,
  c16 text NOT NULL DEFAULT ''::text,
  c17 text NOT NULL DEFAULT ''::text,
  c18 text NOT NULL DEFAULT ''::text,
  c19 text NOT NULL DEFAULT ''::text,
  c20 text NOT NULL DEFAULT ''::text,
  c21 text NOT NULL DEFAULT ''::text,
  c22 text NOT NULL DEFAULT ''::text,
  c23 text NOT NULL DEFAULT ''::text,
  c24 text NOT NULL DEFAULT ''::text,
  c25 text NOT NULL DEFAULT ''::text,
  c26 text NOT NULL DEFAULT ''::text,
  c27 text NOT NULL DEFAULT ''::text,
  c28 text NOT NULL DEFAULT ''::text,
  c29 text NOT NULL DEFAULT ''::text,
  c30 text NOT NULL DEFAULT ''::text,
  c31 text NOT NULL DEFAULT ''::text,
  c32 text NOT NULL DEFAULT ''::text,
  c33 text NOT NULL DEFAULT ''::text,
  c34 text NOT NULL DEFAULT ''::text,
  c35 text NOT NULL DEFAULT ''::text,
  c36 text NOT NULL DEFAULT ''::text,
  c37 text NOT NULL DEFAULT ''::text,
  c38 text NOT NULL DEFAULT ''::text,
  c39 text NOT NULL DEFAULT ''::text,
  c40 text NOT NULL DEFAULT ''::text,
  c41 text NOT NULL DEFAULT ''::text,
  c42 text NOT NULL DEFAULT ''::text,
  c43 text NOT NULL DEFAULT ''::text,
  c44 text NOT NULL DEFAULT ''::text,
  c45 text NOT NULL DEFAULT ''::text,
  c46 text NOT NULL DEFAULT ''::text,
  c47 text NOT NULL DEFAULT ''::text,
  c48 text NOT NULL DEFAULT ''::text,  
  c49 text NOT NULL DEFAULT ''::text,
  c50 text NOT NULL DEFAULT ''::text,
  archive smallint NOT NULL DEFAULT 0,
  secure smallint NOT NULL DEFAULT 0,
  create_date timestamp with time zone,
  modify_date timestamp with time zone,
  owner_name text NOT NULL DEFAULT ''::text,
  updater_name text NOT NULL DEFAULT ''::text,
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
  BEFORE UPDATE OF
  key1, c01, c02, c03, c04, c05, c06, c07, c08, c09, c10, c11, c12, c13, c14, c15, c16, c17, c18, c19, c20, c21, c22, c23, c24, c25, c26, c27, c28, c29, c30,
  c31, c32, c33, c34, c35, c36, c37, c38, c39, c40, c41, c42, c43, c44, c45, c46, c47, c48,  c49, c50, list_string
  ON data_table
  FOR EACH ROW
  EXECUTE PROCEDURE bb_modify_date();
-- Trigger: ts2_create_date on data_table
CREATE TRIGGER ts2_create_date
  BEFORE INSERT
  ON data_table
  FOR EACH ROW
  EXECUTE PROCEDURE bb_create_date();
EOT;
if ($do_data_table) {
    $result = pg_query($con, $query);
    echo "Data Table Created<br>";
}

$query = <<<EOT
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05, c41, owner_name, updater_name)
VALUES (8, 1, 'Carla', 'Horse', 'Wisconsin', '2010-03-05', 'Fixed', 'C46:Arabian', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05,c41, owner_name, updater_name)
VALUES (5, 1, 'Berry', 'Horse', 'Missouri', '1999-04-02', 'Not Fixed', 'C47:Belgian', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05,c41, owner_name, updater_name)
VALUES (4, 1, 'Bernard', 'Dog', 'Wisconsin', '2004-07-01', 'Fixed', 'C44:Border Collie', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05,c41, owner_name, updater_name)
VALUES (3, 1, 'Anne', 'Cat', 'Missouri', '2009-07-11', 'Fixed', 'C42:Burmese', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05,c41, owner_name, updater_name)
VALUES (1, 1, 'Angie', 'Dog', 'Wisconsin', '2001-01-05', 'Not Fixed', 'C43:Akita', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05,c41, owner_name, updater_name)
VALUES (2, 1, 'Bambi', 'Dog', 'Michigan', '2005-03-21', 'Fixed', 'C45:Boston Terrier', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05,c41, owner_name, updater_name)
VALUES (7, 1, 'Bessy', 'Horse', 'Michigan', '2010-05-03', 'Fixed', 'C46:Arabian', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05,c41, owner_name, updater_name)
VALUES (6, 1, 'Bradford', 'Cat', 'Wisconsin', '2006-05-07', 'Not Fixed', 'C41:American Bobtail', 'admin', 'admin');
INSERT INTO data_table (id, row_type, key1, c01, c02, c03, c49, owner_name, updater_name)
VALUES (9, 2, 1, 'Food', '125.00', 'Debit', 'Angie needed some wet food.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, key1, c01, c02, c03, c49, owner_name, updater_name)
VALUES (10, 2, 1, 'Bone', '5.50', 'Debit', 'Angie likes real bones only.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, key1, c01, c02, c03, c49, owner_name, updater_name)
VALUES (11, 2, 2, 'Dog House', '75.00', 'Debit', 'Bambi needed a new doghouse roof.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, key1, c01, c02, c03, c49, owner_name, updater_name)
VALUES (12, 2, 2, 'Collar', '50.00', 'Credit', 'Bambi needed a new dog collar.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, key1, c01, c02, c03, c49, owner_name, updater_name)
VALUES (13, 2, 2, 'Treats', '15.00', 'Debit', 'Bambi ran out of treats.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, key1, c01, c02, c03, c49, owner_name, updater_name)
VALUES (14, 2, 3, 'Litter', '30.00', 'Credit', 'Anne needed some more litter.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, key1, c01, c02, c03, c49, owner_name, updater_name)
VALUES (15, 2, 3, 'Food', '10.00', 'Debit', 'Anne only likes wet food.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, key1, c01, c02, c03, c49, owner_name, updater_name)
VALUES (16, 2, 4, 'Food', '56.00', 'Debit', 'Bernard will only eat dry food.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, key1, c01, c02, c03, c49, owner_name, updater_name)
VALUES (17, 2, 4, 'Medicine', '75.00', 'Debit', 'Bernard got his flea medicine.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, key1, c01, c02, c03, c49, owner_name, updater_name)
VALUES (18, 2, 5, 'Food', '12.50', 'Debit', 'Berry is wild about carrots.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, key1, c01, c02, c03, c49, owner_name, updater_name)
VALUES (19, 2, 5, 'Hay', '50.00', 'Debit', 'Berry was getting low on Hay.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, key1, c01, c02, c03, c49, owner_name, updater_name)
VALUES (20, 2, 6, 'Food', '10.00', 'Debit', 'Bradford is supposed to get fresh chicken.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, key1, c01, c02, c03, c49, owner_name, updater_name)
VALUES (21, 2, 6, 'Food', '22.50', 'Debit', 'Bradford likes a combination of wet and dry food.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, key1, c01, c02, c03, c49, owner_name, updater_name)
VALUES (22, 2, 7, 'Medicine', '600.00', 'Debit', 'Bessy needed an antibiotic.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, key1, c01, c02, c03, c49, owner_name, updater_name)
VALUES (23, 2, 7, 'Hay', '125.00', 'Debit', 'Bessy was getting low on hay.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, key1, c01, c02, c03, c49, owner_name, updater_name)
VALUES (24, 2, 8, 'Food', '200.00', 'Debit', 'Carla needed some hay.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, key1, c01, c02, c03, c49, owner_name, updater_name)
VALUES (25, 2, 8, 'Food', '100.00', 'Debit', 'Carla ran out of sweet oats.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c49, owner_name, updater_name)
VALUES (47, 3, 'Belgian', 'Belgium', 'Horse', 'The Belgian draft horse was developed in the fertile pastures of Belgium. It was also there that the forefather of all draft horses was first bred—a heavy black horse used as knights’ mounts called the Flemish.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c49, owner_name, updater_name)
VALUES (46, 3, 'Arabian', 'Middle Eastern', 'Horse', 'Theorized to be the oldest breed in the world, Arabians were constant companions of the first documented breeders of the Arabian horse, the Bedouin people--nomadic tribesmen of Arabia who relied on the horse for survival.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c49, owner_name, updater_name)
VALUES (45, 3, 'Boston Terrier', 'American', 'Dog', 'Boston Terriers have been popular since their creation a little more than a century ago. They were originally bred to be fighting dogs, but today, they’re gentle, affectionate companions with tuxedo-like markings that earned them the nickname “American Gentleman.”', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c49, owner_name, updater_name)
VALUES (40, 3, 'American Shorthair', 'North Americca', 'Cat', 'The American Short-hair is known as a healthy, hardy breed with few genetic defects, not surprising since the breed developed from hardy domestic stock. A relatively large gene pool helps keep the breed healthy. The standard emphasizes that the American Short-hair should be a ''true breed of working cat'' and that no part of the anatomy should be exaggerated as to foster weakness.  \n  \nThe most striking and best known color is the silver tabby; more than one- third of all American Shorthairs exhibit this color. With the black markings set against the brilliant silver background, the pattern is dynamic and memorable.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c49, owner_name, updater_name)
VALUES (44, 3, 'Border Collie', 'Scotland and England', 'Dog', 'The Border Collie dog breed was developed to gather and control sheep in the hilly border country between Scotland and England. He is known for his intense stare, or “eye,” with which he controls his flock. He’s a dog with unlimited energy, stamina, and working drive, all of which make him a premier herding dog; he’s still used today to herd sheep on farms and ranches around the world. The highly trainable and intelligent Border Collie also excels in various canine sports, including obedience, flyball, agility, tracking, and flying disc competitions.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c49, owner_name, updater_name)
VALUES (43, 3, 'Akita', 'Japan', 'Dog', 'The Akita is a large and powerful dog breed with a noble and intimidating presence. He was originally used for guarding royalty and nobility in feudal Japan. The Akita also tracked and hunted wild boar, black bear, and sometimes deer. He is a fearless and loyal guardian of his family. The Akita does not back down from challenges and does not frighten easily. Yet he is also an affectionate, respectful, and amusing dog when properly trained and socialized.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c49, owner_name, updater_name)
VALUES (41, 3,  'American Bobtail', 'North American', 'Cat', 'Bobtails are slow to develop, reaching maturity somewhere between two and three years. Like bobcats, the Bobtail''s hind legs are slightly longer than the front legs, and the feet are large and round and may have toe tufts.  \n  \nThe Bobtail''s most noted feature, its succinct tail, is one-third to one-half the length of an ordinary cat''s, and should not extend below the hock. Like the Manx, the Bobtail''s tail appears to be governed by a dominant gene. The tail is straight and articulate but may curve, have bumps or be slightly knotted. Bobtails with no tails (also called rumpies) are not acceptable because of the health problems associated with the shortened spine.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c49, owner_name, updater_name)
VALUES (42, 3, 'Burmese', 'Southeast Asia', 'Cat', 'The Burmese''s body style has changed over the years. The 1953 standard described the Burmese as medium, dainty, and long. By 1957 the standard was changed to midway between Domestic Shorthair and Siamese. The words ''somewhat compact'' were added to the standard in 1959; the word somewhat was dropped from the standard somewhat later. Since then, the standard has remained virtually un-changed.  \n  \nOver the last 20 years or so a difference of opinion has developed among breeders as to the favored conformation of the breed. One group favors the European Burmese, longer, narrower muzzles with a less pronounced nose break and a slightly narrower head. The other favors the contemporary Burmese, shorter, broader muzzle, pronounced nose break, and broader, rounder head shapes. Because of this, two conformation types exist today. In the CFA, the European Burmese has just been accepted as a breed in his own right in the miscellaneous class. (In International Division shows they are eligible for championship.) In CFF, CCA, and UFO, the breed is recognized under the name ''Foreign Burmese''. TCA recognizes the classic and traditional Burmese.  \n  \nOne of the main differences between the two breeds, besides the head and body type, is that the European Burmese comes in additional colors. Because the Burmese was crossbred with European Siamese lines that possessed the red gene, the colors red and cream were introduced, producing six additional colors.', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05, c06, c07, c08, owner_name, updater_name)
VALUES (38, 4, 'Byrd', 'Libby', 'libby@nulla.com', '920-845-2142', '8509 Eston St.', 'Milwaukee', 'WI', '54772', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05, c06, c07, c08, owner_name, updater_name)
VALUES (37, 4, 'Bradshaw', 'Honorato', 'honorato@dolorquisque.com', '715-794-5298', '7630 Proin Avenue', 'Appleton', 'WI', '54254', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05, c06, c07, c08, owner_name, updater_name)
VALUES (36, 4, 'Booker', 'Hadassah', 'hadassahs@libero.net', '715-631-4440', '5162 Vulputate Avenue', 'Green Bay', 'WI', '53224', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05, c06, c07, c08, owner_name, updater_name)
VALUES (35, 4, 'Blake', 'Kristen', 'blake@commodo.com', '920-594-5897', '7357 Commodo Road', 'Madison', 'WI', '53812', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05, c06, c07, c08, owner_name, updater_name)
VALUES (34, 4, 'Benjamin', 'Jana', 'jana@tacitisociosqu.com', '920-787-1680', '8512 Molestie. Ave', 'Davenport', 'WI', '53116', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05, c06, c07, c08, owner_name, updater_name)
VALUES (33, 4, 'Bray', 'Odessa', 'odessa@nullam.net', '314-648-2537', '7460 Dolora Ave', 'Columbia', 'MO', '65224', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05, c06, c07, c08, owner_name, updater_name)
VALUES (32, 4, 'Beasley', 'Yvette', 'beasley@nuncio.org', '314-722-5245', '5762 Felis Street', 'Springfield', 'MO', '64523', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05, c06, c07, c08, owner_name, updater_name)
VALUES (31, 4, 'Baldwin', 'Julian', 'julianbaldwin@urna.net', '417-877-0605', '143 Accord St.', 'Saint Louis', 'MO', '63826', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05, c06, c07, c08, owner_name, updater_name)
VALUES (30, 4, 'Anthony', 'Wesley', 'wesley@liberomauris.com', '314-841-5975', '134 Nuncon Street', 'Columbia', 'MO', '63442', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05, c06, c07, c08, owner_name, updater_name)
VALUES (29, 4, 'Brown', 'Caleb', 'brown@atarcu.edu', '231-719-8618', '5391 Ettle St.', 'Lansing', 'MI', '48554', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05, c06, c07, c08, owner_name, updater_name)
VALUES (28, 4, 'Brock', 'Chaim', 'chaim@ultriciesornareelit.com', '231-291-7539', '2345 Erma Rd.', 'Sterling Heights', 'MI', '49224', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05, c06, c07, c08, owner_name, updater_name)
VALUES (27, 4, 'Bonner', 'Madaline', 'bonner@yahoo.com', '517-111-2927', '3948 Scelerisque Road', 'Sterling Heights', 'MI', '48262', 'admin', 'admin');
INSERT INTO data_table (id, row_type, c01, c02, c03, c04, c05, c06, c07, c08, owner_name, updater_name)
VALUES (26, 4, 'Avila', 'Lacey', 'lacey6@nullaInteger.com', '231-493-1047', '479 Inwood Rd.', 'Flint', 'MI', '48513', 'admin', 'admin');
UPDATE data_table SET fts = to_tsvector(c01 || ' ' || regexp_replace(c01, E'(\\W)+', ' ', 'g') || ' ' || c02 || ' ' || regexp_replace(c02, E'(\\W)+', ' ', 'g') || ' ' || c03 || regexp_replace(c03, E'(\\W)+', ' ', 'g') || ' ' || ' ' || c05 || regexp_replace(c05, E'(\\W)+', ' ', 'g') || ' ' || ' ' || c06 || regexp_replace(c06, E'(\\W)+', ' ', 'g')), ftg = to_tsvector(c01 || ' ' || regexp_replace(c01, E'(\\W)+', ' ', 'g') || ' ' || c02 || ' ' || regexp_replace(c02, E'(\\W)+', ' ', 'g') || ' ' || c03 || regexp_replace(c03, E'(\\W)+', ' ', 'g') || ' ' || ' ' || c05 || regexp_replace(c05, E'(\\W)+', ' ', 'g') || ' ' || ' ' || c06 || regexp_replace(c06, E'(\\W)+', ' ', 'g')) WHERE row_type = 1;
UPDATE data_table SET fts = to_tsvector(c01 || ' ' || regexp_replace(c01, E'(\\W)+', ' ', 'g') || ' ' || c49 || ' ' || regexp_replace(c49, E'(\\W)+', ' ', 'g')), ftg = to_tsvector(c01 || ' ' || regexp_replace(c01, E'(\\W)+', ' ', 'g') || ' ' || c49 || ' ' || regexp_replace(c49, E'(\\W)+', ' ', 'g'))  WHERE row_type = 2;
UPDATE data_table SET fts = to_tsvector(c01 || ' ' || regexp_replace(c01, E'(\\W)+', ' ', 'g') || ' ' || c02 || ' ' || regexp_replace(c02, E'(\\W)+', ' ', 'g') || ' ' || c03 || regexp_replace(c03, E'(\\W)+', ' ', 'g') || ' ' || ' ' || c49 || regexp_replace(c49, E'(\\W)+', ' ', 'g')),
ftg = to_tsvector(c01 || ' ' || regexp_replace(c01, E'(\\W)+', ' ', 'g') || ' ' || c02 || ' ' || regexp_replace(c02, E'(\\W)+', ' ', 'g') || ' ' || c03 || regexp_replace(c03, E'(\\W)+', ' ', 'g') || ' ' || ' ' || c49 || regexp_replace(c49, E'(\\W)+', ' ', 'g')) WHERE row_type = 3;
UPDATE data_table SET fts = to_tsvector(c01 || ' ' || regexp_replace(c01, E'(\\W)+', ' ', 'g') || ' ' || c02 || ' ' || regexp_replace(c02, E'(\\W)+', ' ', 'g') || ' ' || c03 || regexp_replace(c03, E'(\\W)+', ' ', 'g') || ' ' || ' ' || c04 || regexp_replace(c04, E'(\\W)+', ' ', 'g') || ' ' || ' ' || c05 || regexp_replace(c05, E'(\\W)+', ' ', 'g') || ' ' || ' ' || c06 || regexp_replace(c06, E'(\\W)+', ' ', 'g') || ' ' || ' ' || c07 || regexp_replace(c07, E'(\\W)+', ' ', 'g') || ' ' || ' ' || c08 || regexp_replace(c08, E'(\\W)+', ' ', 'g')),
ftg = to_tsvector(c01 || ' ' || regexp_replace(c01, E'(\\W)+', ' ', 'g') || ' ' || c02 || ' ' || regexp_replace(c02, E'(\\W)+', ' ', 'g') || ' ' || c03 || regexp_replace(c03, E'(\\W)+', ' ', 'g') || ' ' || ' ' || c04 || regexp_replace(c04, E'(\\W)+', ' ', 'g') || ' ' || ' ' || c05 || regexp_replace(c05, E'(\\W)+', ' ', 'g') || ' ' || ' ' || c06 || regexp_replace(c06, E'(\\W)+', ' ', 'g') || ' ' || ' ' || c07 || regexp_replace(c07, E'(\\W)+', ' ', 'g') || ' ' || ' ' || c08 || regexp_replace(c08, E'(\\W)+', ' ', 'g')) WHERE row_type = 4;
SELECT setval('data_table_id_seq', (SELECT MAX(id) FROM data_table));
EOT;
if ($do_data_table) {
    $result = pg_query($con, $query);
    echo "Data Table Populated<br>";
}

$query = "select * from pg_tables WHERE schemaname = 'public' and tablename = 'log_table'";
$result = pg_query($con, $query);
$num_rows = pg_num_rows($result);
$do_log_table = false;
if ($num_rows == 0) {
    $do_log_table = true;
}

$query = <<<EOT
CREATE TABLE log_table
(
  id bigserial NOT NULL,
  username text NOT NULL DEFAULT ''::text,
  email text NOT NULL DEFAULT ''::text,
  ip_address cidr,
  action text NOT NULL DEFAULT ''::text,
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
  EXECUTE PROCEDURE bb_change_date();
EOT;
if ($do_log_table) {
    $result = pg_query($con, $query);
    echo "Log Table Created (No Population)<br>";
}

$query = "select * from pg_tables WHERE schemaname = 'public' and tablename = 'modules_table'";
$result = pg_query($con, $query);
$num_rows = pg_num_rows($result);
$do_modules_table = false;
if ($num_rows == 0) {
    $do_modules_table = true;
}

$query = <<<EOT
CREATE TABLE modules_table
(
  id serial NOT NULL,
  module_order int,
  module_path text NOT NULL DEFAULT ''::text,
  module_name text NOT NULL DEFAULT ''::text,
  friendly_name text NOT NULL DEFAULT ''::text,
  interface text NOT NULL DEFAULT ''::text,
  module_type smallint,
  module_version text NOT NULL DEFAULT ''::text,
  standard_module smallint,
  module_files text NOT NULL DEFAULT ''::text,
  module_details text NOT NULL DEFAULT ''::text,
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
  EXECUTE PROCEDURE bb_change_date();
EOT;
if ($do_modules_table) {
    $result = pg_query($con, $query);
    echo "Module Table Created<br>";
}

$query = <<<EOT
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (0, 'bb-primary/bb_delete.php', 'bb_delete', 'Delete', 'bb_brimbox', 0, 'Core', 2, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the delete hidden page which is activated from the \"Delete\" link when records are returned on the standard pages. It allows for deleting individual records or cascaded deletes which include all child records."}'); 
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (0, 'bb-primary/bb_archive.php', 'bb_archive', 'Archive', 'bb_brimbox', 0, 'Core', 2, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the archive hidden module which is activated from the \"Archive\" link when records are returned on the standard pages. It allows for archiving cascaded records, which include all child records."}'); 
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (0, 'bb-primary/bb_secure.php', 'bb_secure', 'Secure', 'bb_brimbox', 0, 'Core', 2, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the secure hidden module which is activated from the \"Secure\" link when records are returned on the standard pages. It allows for securing cascaded records, which include all child records, so that records can be hidden from guest users."}'); 
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (0, 'bb-primary/bb_listchoose.php', 'bb_listchoose', 'List Choose', 'bb_brimbox', 0, 'Core', 2, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is a hidden page which is activated from the \"List\" link when records are returned on the standard pages. It allows for adding an individual records to lists."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (0, 'bb-primary/bb_join.php', 'bb_join', 'Join', 'bb_brimbox', 0, 'Core', 2, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is a hidden page which is activated from \"Join\" links when records are returned on the standard pages. It allows for joining records in Many to Many relationships."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (1, 'bb-primary/bb_guest.php', 'bb_guest', 'Guest', 'bb_brimbox', 1, 'Core', 0, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the guest landing page which includes the logout link, basic session stats such as user an database, and space to place customized links and information regarding the database."}'); 
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (1, 'bb-primary/bb_viewer.php', 'bb_viewer', 'Viewer', 'bb_brimbox', 2, 'Core', 0, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the viewer landing page which includes the logout link, basic session stats such as user an database, and space to place customized links and information regarding the database."}'); 
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (1, 'bb-primary/bb_home.php', 'bb_home', 'Home', 'bb_brimbox', 3, 'Core', 0, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the home landing page for users, superusers, and admins which includes the logout link, basic session stats such as user an database, and space to place customized links and information regarding the database."}'); 
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (2, 'bb-primary/bb_browse.php', 'bb_browse', 'Browse', 'bb_brimbox', 3, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the standard browse page for users. It allows users to browse data by choosing the first character of the data in a particular column."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (3, 'bb-primary/bb_lookup.php', 'bb_lookup', 'Lookup', 'bb_brimbox', 3, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the standard lookup page for users. It allows users to browse data by choosing in a particular column by value or part of value."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (4, 'bb-primary/bb_search.php', 'bb_search', 'Search', 'bb_brimbox', 3, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the standard search page for users. It allows user to search data based on content using boolean expressions."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (5, 'bb-primary/bb_view.php', 'bb_view', 'View', 'bb_brimbox', 3, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the standard view page used when drilling down into child records."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (6, 'bb-primary/bb_details.php', 'bb_details', 'Details', 'bb_brimbox', 3,'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the standard details page used when viewing details of a particular record. It also has functionality for changing the linking of a record."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (7, 'bb-primary/bb_cascade.php', 'bb_cascade', 'Cascade', 'bb_brimbox', 3, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the standard cascade tab used to view a record and all its child records all at once."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (8, 'bb-primary/bb_input.php', 'bb_input',  'Input', 'bb_brimbox', 3, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the standard input page for inputting data to the database. It checks for keys, required fields, and data validation."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (9, 'bb-primary/bb_queue.php', 'bb_queue', 'Queue', 'bb_brimbox', 3, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the standard queue page used for receiving email sent to the database."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (10, 'bb-primary/bb_listview.php', 'bb_listview', 'List', 'bb_brimbox', 3, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the standard page for viewing records contained in a list."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (1, 'bb-admin/bb_create_lists.php', 'bb_create_lists', 'Manage Lists', 'bb_brimbox', 4, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the superuser module used in creating lists by layout type."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (2, 'bb-admin/bb_dropdowns.php', 'bb_dropdowns', 'Manage Dropdowns', 'bb_brimbox', 4, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the superuser module used when creating dropdowns for the input page."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (3, 'bb-admin/bb_upload_data.php', 'bb_upload_data', 'Upload Data', 'bb_brimbox', 4, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the admin module used for bulk loads of data to the database."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (4, 'bb-admin/bb_upload_docs.php', 'bb_upload_docs', 'Upload Documents', 'bb_brimbox', 4, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the admin module used for uploading documents, usually support documents, to the database."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (5, 'bb-admin/bb_query_alias.php', 'bb_query_alias', 'Query Alias', 'bb_brimbox', 4, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the admin module used for constructing SELECT gueries, as would be used in reports."}');
INSERT INTO modules_table (module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (6, 'bb-admin/bb_translate.php', 'bb_translate', 'Translation', 'bb_brimbox', 4, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the module for translating Brimbox text, defining existing text strings with alternative or foreign text."}'); 
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (1, 'bb-admin/bb_manage_users.php', 'bb_manage_users', 'Manage Users', 'bb_brimbox', 5, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the admin module used for managing users and their permissions."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (2, 'bb-admin/bb_manage_log.php', 'bb_manage_log', 'Manage Log', 'bb_brimbox', 5, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the admin module used to log certain actions in the database."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (3, 'bb-admin/bb_layout_names.php', 'bb_layout_names', 'Set Layout Names', 'bb_brimbox', 5, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the admin module used for setting up layouts and their respective links."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (4, 'bb-admin/bb_column_names.php', 'bb_column_names', 'Set Column Names', 'bb_brimbox', 5, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the admin module used for setting up column names, their row and order properties, and their validation values."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (5, 'bb-admin/bb_create_key.php', 'bb_create_key', 'Create Key', 'bb_brimbox', 5, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the admin module for defining a key column for unique values."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (6, 'bb-admin/bb_manage_modules.php', 'bb_manage_modules', 'Manage Modules', 'bb_brimbox', 5, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the admin module for uploading modules and installing program updates."}');
INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details)
VALUES (7, 'bb-admin/bb_backup_restore.php', 'bb_backup_restore', 'Backup and Restore', 'bb_brimbox', 5, 'Core', 6, '', '{"company":"Brimbox","author":"Brimbox Staff","license":"GNU GPL v3","description":"This is the module for backing up, cleansing, and restoring an encrypted brimbox database."}');
EOT;
if ($do_modules_table) {
    $result = pg_query($con, $query);
    echo "Module Table Populated<br>";
}

$query = "select * from pg_tables WHERE schemaname = 'public' and tablename = 'users_table'";
$result = pg_query($con, $query);
$num_rows = pg_num_rows($result);
$do_users_table = false;
if ($num_rows == 0) {
    $do_users_table = true;
}

$query = <<<EOT
CREATE TABLE users_table
(
  id serial NOT NULL,
  username text NOT NULL DEFAULT ''::text,
  email text NOT NULL DEFAULT ''::text,
  hash text NOT NULL DEFAULT ''::text,
  salt text NOT NULL DEFAULT ''::text,
  attempts smallint NOT NULL DEFAULT 0,
  userroles text[] NOT NULL DEFAULT '{"0_bb_brimbox"}',
  fname text NOT NULL DEFAULT ''::text,
  minit text NOT NULL DEFAULT ''::text,
  lname text NOT NULL DEFAULT ''::text,
  notes text NOT NULL DEFAULT ''::text,
  jsondata text NOT NULL DEFAULT ''::text,
  reset text NOT NULL DEFAULT ''::text,
  ips cidr[] NOT NULL DEFAULT '{0.0.0.0/0,0:0:0:0:0:0:0:0/0}',
  change_date timestamp with time zone,
  CONSTRAINT users_table_pkey PRIMARY KEY (id),
  CONSTRAINT users_table_unique_username UNIQUE (username),
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
  EXECUTE PROCEDURE bb_change_date();
EOT;
if ($do_users_table) {
    $result = pg_query($con, $query);
    echo "Users Table Created<br>";
}

$query = <<<EOT
INSERT INTO users_table(username, email, hash, salt, userroles)
VALUES ('$username', '$email', '$hash', '$salt', '{"5_bb_brimbox"}');
EOT;
if ($do_users_table) {
    $result = pg_query($con, $query);
    echo "Users Table Populated<br>";
}

$query = "select * from pg_tables WHERE schemaname = 'public' and tablename = 'json_table'";
$result = pg_query($con, $query);
$num_rows = pg_num_rows($result);
$do_json_table = false;
if ($num_rows == 0) {
    $do_json_table = true;
}

$query = <<<EOT
CREATE TABLE json_table
(
  id serial NOT NULL,
  lookup text NOT NULL DEFAULT ''::text,
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
  EXECUTE PROCEDURE bb_change_date();
EOT;
if ($do_json_table) {
    $result = pg_query($con, $query);
    echo "JSON Table Created<br>";
}

$query = <<<EOT
INSERT INTO json_table (lookup, jsondata)
VALUES('bb_layout_names','{"1":{"singular":"Animal","plural":"Animals","parent":"0","order":"1","secure":"0","autoload":"0","relate":"0"},"2":{"singular":"Expense","plural":"Expenses","parent":"1","order":"2","secure":"0","autoload":"0","relate":"0"},"joins":{"1":{"join1":1,"join2":4},"2":{"join1":7,"join2":10}},"3":{"singular":"Breed","plural":"Breeds","parent":"0","order":"3","secure":"0","autoload":"0","relate":"1"},"4":{"singular":"Owner","plural":"Owners","parent":"0","order":"4","secure":"0","autoload":"0","relate":"0"}}');
INSERT INTO json_table (lookup, jsondata)
VALUES('bb_column_names','{"2":{"1":{"name":"Topic","row":"1","length":"short","order":"1","type":"bb_brimbox_text","display":"0","required":"1","secure":"0","search":"1","relate":""},"2":{"name":"Cost","row":"1","length":"short","order":"2","type":"bb_brimbox_money","display":"0","required":"0","secure":"0","search":"0","relate":""},"3":{"name":"Type","row":"1","length":"short","order":"3","type":"bb_brimbox_text","display":"0","required":"0","secure":"0","search":"1","relate":""},"49":{"name":"Note","row":"2","length":"note","order":"4","type":"","display":"0","required":"1","secure":"0","search":"1","relate":""},"primary":1,"count":4,"alternative":{"bb_brimbox":{"1":{"row":"1","length":"short","order":"1","display":"0"},"2":{"row":"1","length":"short","order":"2","display":"0"},"3":{"row":"1","length":"short","order":"3","display":"0"},"49":{"row":"2","length":"note","order":"4","display":"0"},"primary":1,"count":4}}},"1":{"1":{"name":"Name","row":"1","length":"medium","order":"1","type":"bb_brimbox_text","display":"0","required":"1","secure":"0","search":"1","relate":""},"2":{"name":"Type","row":"1","length":"medium","order":"2","type":"bb_brimbox_text","display":"0","required":"0","secure":"0","search":"1","relate":""},"3":{"name":"Location","row":"2","length":"medium","order":"3","type":"bb_brimbox_text","display":"0","required":"0","secure":"0","search":"1","relate":""},"4":{"name":"Birthday","row":"2","length":"medium","order":"4","type":"bb_brimbox_date","display":"0","required":"0","secure":"0","search":"0","relate":""},"5":{"name":"Fixed","row":"3","length":"medium","order":"5","type":"bb_brimbox_text","display":"0","required":"0","secure":"0","search":"0","relate":""},"41":{"name":"Breed","row":"3","length":"medium","order":"6","type":"bb_brimbox_text","display":"0","required":"0","secure":"0","search":"0","relate":"3"},"primary":1,"count":6,"alternative":{"bb_brimbox":{"1":{"row":"1","length":"medium","order":"1","display":"0"},"2":{"row":"1","length":"medium","order":"2","display":"0"},"3":{"row":"2","length":"medium","order":"3","display":"0"},"4":{"row":"2","length":"medium","order":"4","display":"0"},"5":{"row":"3","length":"medium","order":"5","display":"0"},"41":{"row":"3","length":"medium","order":"6","display":"0"},"primary":1,"count":6}}},"fields":{"row":{"name":"Row","alternative":true},"length":{"name":"Length","alternative":true},"order":{"name":"Order","alternative":true},"type":{"name":"Type"},"display":{"name":"Display","alternative":true},"required":{"name":"Required"},"secure":{"name":"Secure"},"search":{"name":"Search"},"relate":{"name":"Relate"}},"properties":{"primary":{"name":"Primary"},"count":{"name":"Count"},"unique":{"name":"Unique"}},"4":{"1":{"name":"Last Name","row":"1","length":"medium","order":"1","type":"bb_brimbox_text","display":"0","required":"0","secure":"0","search":"1","relate":""},"2":{"name":"First Name","row":"1","length":"medium","order":"2","type":"bb_brimbox_text","display":"0","required":"0","secure":"0","search":"1","relate":""},"3":{"name":"Email","row":"2","length":"medium","order":"3","type":"bb_brimbox_text","display":"0","required":"0","secure":"0","search":"1","relate":""},"4":{"name":"Phone","row":"2","length":"medium","order":"4","type":"bb_brimbox_text","display":"0","required":"0","secure":"0","search":"1","relate":""},"5":{"name":"Address","row":"2","length":"medium","order":"5","type":"bb_brimbox_text","display":"0","required":"0","secure":"0","search":"1","relate":""},"6":{"name":"City","row":"3","length":"medium","order":"6","type":"bb_brimbox_text","display":"0","required":"0","secure":"0","search":"1","relate":""},"7":{"name":"State","row":"3","length":"medium","order":"7","type":"bb_brimbox_text","display":"0","required":"0","secure":"0","search":"1","relate":""},"8":{"name":"Zip","row":"3","length":"medium","order":"8","type":"bb_brimbox_text","display":"0","required":"0","secure":"0","search":"1","relate":""},"primary":1,"count":8,"alternative":{"bb_brimbox":{"1":{"row":"1","length":"medium","order":"1","display":"0"},"2":{"row":"1","length":"medium","order":"2","display":"0"},"3":{"row":"2","length":"medium","order":"3","display":"0"},"4":{"row":"2","length":"medium","order":"4","display":"0"},"5":{"row":"2","length":"medium","order":"5","display":"0"},"6":{"row":"3","length":"medium","order":"6","display":"0"},"7":{"row":"3","length":"medium","order":"7","display":"0"},"8":{"row":"3","length":"medium","order":"8","display":"0"},"primary":1,"count":8}}},"3":{"1":{"name":"Name","row":"1","length":"medium","order":"1","type":"bb_brimbox_text","display":"0","required":"0","secure":"0","search":"1","relate":""},"2":{"name":"Origination","row":"2","length":"medium","order":"2","type":"bb_brimbox_text","display":"0","required":"0","secure":"0","search":"1","relate":""},"3":{"name":"Type","row":"3","length":"medium","order":"3","type":"bb_brimbox_text","display":"0","required":"0","secure":"0","search":"1","relate":""},"49":{"name":"Description","row":"4","length":"note","order":"4","type":"","display":"0","required":"0","secure":"0","search":"1","relate":""},"primary":1,"count":4,"alternative":{"bb_brimbox":{"1":{"row":"1","length":"medium","order":"1","display":"0"},"2":{"row":"2","length":"medium","order":"2","display":"0"},"3":{"row":"3","length":"medium","order":"3","display":"0"},"49":{"row":"4","length":"note","order":"4","display":"0"},"primary":1,"count":4}}}}');
INSERT INTO json_table (lookup, jsondata)
VALUES('bb_dropdowns','{"1":{"5":{"0":"Fixed","1":"Not Fixed","multiselect":"0"}},"2":{"3":{"0":"Credit","1":"Debit","2":"No Charge","multiselect":"0"}},"properties":{"multiselect":{"name":"Multiselect"}},"3":{"3":{"0":"Cat","1":"Dog","2":"Horse","multiselect":"0"}}}');
INSERT INTO json_table (lookup, jsondata)
VALUES('bb_create_lists','{"1":{"2":{"name":"Cat","archive":0,"description":"This is the cat list."},"1":{"name":"Dog","archive":0,"description":"This is the dog list."}},"2":{"1":{"name":"Vet","description":"This is the vet list.","archive":0}}}');
INSERT INTO json_table (lookup, jsondata)
VALUES('bb_box_translate','{"Queue":"Inbox"}');
EOT;
if ($do_json_table) {
    $result = pg_query($con, $query);
    echo "JSON Table Populated<br>";
}

$query = "select * from pg_tables WHERE schemaname = 'public' and tablename = 'docs_table'";
$result = pg_query($con, $query);
$num_rows = pg_num_rows($result);
$do_docs_table = false;
if ($num_rows == 0) {
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
if ($do_docs_table) {
    $result = pg_query($con, $query);
    echo "Docs Table Created (No Population)<br>";
}

$query = "select * from pg_tables WHERE schemaname = 'public' and tablename = 'state_table'";
$result = pg_query($con, $query);
$num_rows = pg_num_rows($result);
$do_state_table = false;
if ($num_rows == 0) {
    $do_state_table = true;
}

$query = <<<EOT
CREATE TABLE state_table
(
  id serial NOT NULL,
  statedata text[] NOT NULL DEFAULT '{}',
  postdata text NOT NULL DEFAULT '',
  change_date timestamp with time zone,
  CONSTRAINT state_table_pkey PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);
ALTER SEQUENCE state_table_id_seq RESTART CYCLE;
-- Trigger: ts1_update_change_date on xml_table
CREATE TRIGGER ts1_update_change_date
  BEFORE INSERT OR UPDATE
  ON state_table
  FOR EACH ROW
  EXECUTE PROCEDURE bb_change_date();
EOT;
if ($do_state_table) {
    $result = pg_query($con, $query);
    echo "State Table Created (No Population)<br>";
}

$query = "select * from pg_tables WHERE schemaname = 'public' and tablename = 'join_table'";
$result = pg_query($con, $query);
$num_rows = pg_num_rows($result);
$do_join_table = false;
if ($num_rows == 0) {
    $do_join_table = true;
}

$query = <<<EOT
CREATE TABLE join_table
(
  id bigserial NOT NULL,
  join1 bigint NOT NULL,
  join2 bigint NOT NULL,
  join_date timestamp with time zone,
  CONSTRAINT join_table_pkey PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);
ALTER SEQUENCE join_table_id_seq RESTART CYCLE;
CREATE INDEX join_table_idx_join1
  ON join_table
  USING btree
  (join1);
CREATE INDEX join_table_idx_join2
  ON join_table
  USING btree
  (join2);
-- Trigger: ts1_update_change_date on _table
CREATE TRIGGER ts1_join_date
  BEFORE INSERT
  ON join_table
  FOR EACH ROW
  EXECUTE PROCEDURE bb_join_date();
EOT;


if ($do_join_table) {
    $result = pg_query($con, $query);
    echo "Join Table Created<br>";
}

$query = <<<EOT
INSERT INTO join_table (join1, join2)
VALUES (1, 35);
INSERT INTO join_table (join1, join2)
VALUES (3, 32);
INSERT INTO join_table (join1, join2)
VALUES (3, 31);
INSERT INTO join_table (join1, join2)
VALUES (5, 30);
INSERT INTO join_table (join1, join2)
VALUES (4, 37);
INSERT INTO join_table (join1, join2)
VALUES (7, 29);
INSERT INTO join_table (join1, join2)
VALUES (6, 36);
INSERT INTO join_table (join1, join2)
VALUES (8, 34);
INSERT INTO join_table (join1, join2)
VALUES (4, 35);
INSERT INTO join_table (join1, join2)
VALUES (1, 34);
INSERT INTO join_table (join1, join2)
VALUES (2, 27);
INSERT INTO join_table (join1, join2)
VALUES (2, 28);
INSERT INTO join_table (join1, join2)
VALUES (6, 38);
EOT;


if ($do_join_table) {
    $result = pg_query($con, $query);
    echo "Join Table Populated<br>";
}

echo "<body><p>You have successfully installed the database. You may delete this file now.</p></body>";
