<?php
$SQL = array();

$SQL[] = "UPDATE core_reputation_index SET type='image_id' where app='gallery' and type='id';";
$SQL[] = "UPDATE core_reputation_index SET type='comment_id' where app='gallery' and type='pid';";
