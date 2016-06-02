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

function getmsg($mbox, $mid) {
    // input $mbox = IMAP stream, $mid = message id
    // output all the following:
    // global $htmlmsg,$plainmsg,$charset,$attachments;
    // the message may in $htmlmsg, $plainmsg, or both
    // $htmlmsg = $plainmsg = $charset = '';
    // $attachments = array();
    // HEADER
    $h = imap_header($mbox, $mid);
    // add code here to get date, from, to, cc, subject...
    // BODY
    $s = imap_fetchstructure($mbox, $mid);
    if (!isset($s->parts)) // not multipart
    $msg = getpart($mbox, $mid, $s, 0); // no part-number, so pass 0
    else { // multipart: iterate through each part
        foreach ($s->parts as $partno0 => $p) $msg = getpart($mbox, $mid, $p, $partno0 + 1);
    }
    return $msg;
}

function getpart($mbox, $mid, $p, $partno) {
    // $partno = '1', '2', '2.1', '2.1.3', etc if multipart, 0 if not multipart
    // global $htmlmsg,$plainmsg,$charset,$attachments;
    $htmlmsg = $plainmsg = $charset = '';
    $attachments = array();

    // DECODE DATA
    $data = ($partno) ? imap_fetchbody($mbox, $mid, $partno) : // multipart
    imap_body($mbox, $mid); // not multipart
    // Any part may be encoded, even plain text messages, so check everything.
    if ($p->encoding == 4) $data = quoted_printable_decode($data);
    elseif ($p->encoding == 3) $data = base64_decode($data);
    // no need to decode 7-bit, 8-bit, or binary
    // PARAMETERS
    // get all parameters, like charset, filenames of attachments, etc.
    $params = array();
    if (isset($p->parameters)) foreach ($p->parameters as $x) $params[strtolower($x->attribute) ] = $x->value;
    if (isset($p->dparameters)) foreach ($p->dparameters as $x) $params[strtolower($x->attribute) ] = $x->value;

    // ATTACHMENT
    // Any part with a filename is an attachment,
    // so an attached text file (type 0) is not mistaken as the message.
    if (isset($params['filename']) || isset($params['name'])) {
        // filename may be given as 'Filename' or 'Name' or both
        $filename = ($params['filename']) ? $params['filename'] : $params['name'];
        // filename may be encoded, so see imap_mime_header_decode()
        $attachments[$filename] = $data; // this is a problem if two files have same name
        
    }

    // TEXT
    elseif ($p->type == 0 && $data) {
        // Messages may be split in different parts because of inline attachments,
        // so append parts together with blank row.
        if (strtolower($p->subtype) == 'plain') $htmlmsg.= nl2br(htmlentities(trim($data)));
        else $htmlmsg.= nl2br(trim($data));
        $charset = $params['charset']; // assume all parts are same charset
        
    }

    // EMBEDDED MESSAGE
    // Many bounce notifications embed the original message as type 2,
    // but AOL uses type 1 (multipart), which is not handled here.
    // There are no PHP functions to parse embedded messages,
    // so this just appends the raw source to the main message.
    elseif ($p->type == 2 && $data) {
        $htmlmsg.= nl2br(trim($data));
    }

    // SUBPART RECURSION
    if (isset($p->parts)) {
        foreach ($p->parts as $partno0 => $p2) getpart($mbox, $mid, $p2, $partno . '.' . ($partno0 + 1)); // 1.2, 1.2.1, etc.
        
    }
    // Brimbox only needs htmlmsg, could also return $plainmsg,$charset,$attachments
    // these two functions were originally written with globals, however they were eliminated for Brimbox
    // $msg = array('charset'=>$charset, 'htmlmsg'=>$htmlmsg);
    $msg = new stdClass();
    $msg->charset = $charset;
    $msg->htmlmsg = $htmlmsg;

    return $msg;
}

?>