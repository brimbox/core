<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php

/*
 * Copyright (C) Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 3 (“GNU GPL v3”)
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
?>
<?php
// EOT stands for end of text

/* BEFORE AND AFTER CREATE STRINGS FOR RESTORE */
/* ORDER */
// data_table
// log_table
// modules_table
// users_table
// json_table

$list_zeros = str_repeat ( "0", 2000 );

$data_before_eot = <<<EOT
CREATE TABLE data_table
(
  id bigint NOT NULL,
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
EOT;
$data_after_eot = <<<EOT
--build indexes
CREATE INDEX data_table_gist_fts
  ON data_table
  USING gin
  (fts);
CREATE INDEX data_table_gist_ftg
  ON data_table
  USING gin
  (ftg);
CREATE INDEX data_table_idx_key1
  ON data_table
  USING btree
  (key1);
CREATE INDEX data_table_idx_key2
  ON data_table
  USING btree
  (key2);
--trigger: ts1_bb_modify_date on data_table
CREATE TRIGGER ts1_bb_modify_date
  BEFORE UPDATE
  ON data_table
  FOR EACH ROW
  EXECUTE PROCEDURE bb_modify_date();
--trigger: ts2_bb_create_date on data_table
CREATE TRIGGER ts2_bb_create_date
  BEFORE INSERT
  ON data_table
  FOR EACH ROW
  EXECUTE PROCEDURE bb_create_date();
--set up the sequence
CREATE SEQUENCE data_table_id_seq CYCLE;
ALTER SEQUENCE data_table_id_seq OWNED BY data_table.id;
ALTER TABLE data_table ALTER COLUMN id SET DEFAULT NEXTVAL('data_table_id_seq');
SELECT setval('data_table_id_seq', (SELECT max(id) + 1 FROM data_table));
EOT;

$log_before_eot = <<<EOT
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
--set up the sequence
ALTER SEQUENCE log_table_id_seq RESTART CYCLE;
EOT;
$log_after_eot = <<<EOT
--trigger: bb_change_date
CREATE TRIGGER ts1_update_bb_change_date
  BEFORE INSERT OR UPDATE
  ON log_table
  FOR EACH ROW
  EXECUTE PROCEDURE bb_change_date();
EOT;

$modules_before_eot = <<<EOT
CREATE TABLE modules_table
(
  id serial NOT NULL,
  module_order int,
  module_path text NOT NULL DEFAULT ''::text,
  module_name text NOT NULL DEFAULT ''::text,
  module_slug text NOT NULL DEFAULT ''::text,
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
EOT;
$modules_after_eot = <<<EOT
--trigger: bb_change_date
CREATE TRIGGER ts1_update_change_date
  BEFORE INSERT OR UPDATE
  ON modules_table
  FOR EACH ROW
  EXECUTE PROCEDURE bb_change_date();
EOT;

$users_before_eot = <<<EOT
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
  ips cidr[] NOT NULL DEFAULT '{0.0.0.0/0,0:0:0:0:0:0:0:0/0}',
  change_date timestamp with time zone,
  CONSTRAINT users_table_pkey PRIMARY KEY (id),
  CONSTRAINT users_table_unique_username UNIQUE (username),
  CONSTRAINT users_table_unique_email UNIQUE (email)
)
WITH (
  OIDS=FALSE
);
--set up the sequence
ALTER SEQUENCE users_table_id_seq RESTART CYCLE;
EOT;
$users_after_eot = <<<EOT
--trigger: bb_change_date
CREATE TRIGGER ts1_update_bb_change_date
  BEFORE INSERT OR UPDATE
  ON users_table
  FOR EACH ROW
  EXECUTE PROCEDURE bb_change_date();
EOT;

$json_before_eot = <<<EOT
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
--set up the sequence
ALTER SEQUENCE json_table_id_seq RESTART CYCLE;
EOT;
$json_after_eot = <<<EOT
--trigger: bb_change_date
CREATE TRIGGER ts1_update_bb_change_date
  BEFORE INSERT OR UPDATE
  ON json_table
  FOR EACH ROW
  EXECUTE PROCEDURE bb_change_date();
EOT;
?>