<?php
/*
 * Copyright (C) Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 3 (�GNU GPL v3�)
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

include ($abspath . "/bb-less/bb_less_substituter.php");

$less = new bb_less_substituter();

$less->parse_less_file($abspath . "/bb-box/bb_box.less", $abspath . "/bb-box/bb_box.css");

@unlink($abspath . "/bb-utilities/bb_styles.less");
@unlink($abspath . "/bb-utilities/bb_styles.css");

?>