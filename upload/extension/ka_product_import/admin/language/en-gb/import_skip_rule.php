<?php
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 572 $)
*/

$_['txt_list_page_title']           = 'Import Skip Rules';
$_['txt_form_page_title']           = 'Import Skip Rule';

$_['txt_title_column_name']              = 'Column Name';
$_['txt_title_pattern']                  = 'Value Pattern';
$_['txt_title_rule_action']              = 'Rule Action';
$_['txt_title_sort_order']               = 'Sort Order';

$_['Include Line']                   = 'Import Row';
$_['Exclude Line']                   = 'Skip Row';

$_['txt_tooltip_pattern'] = 'The pattern may contain wildcards<br>? - any single character<br>* - any 
multiple characters<br>[] - possible set of characters.<br>Example: gr[ae]y*';

$_['<p>Here you can specify rul...'] = '<p>Here you can specify rules for skipping rows of a csv product file. The rules are split by groups for convenience. The rules are checked by their priority.
							Once a row matches one of the rules, the rule action will be executed and further checking will be stopped for that row.
							 </p>
							<p>To apply rules to your import file, you need to select the \'Import Skip Rules\' group on the \'Extra\' tab of the first import step.</p>';
							
$_['Operation has been complete...'] = 'Operation has been completed successfully';
$_['Invalid form data. Please v...'] = 'Invalid form data. Please verify all fields and submit it again.';

$_['Manage import groups'] = 'Manage import groups';
$_['Import Group'] = 'Import Group';
$_['Action'] = 'Action';

$_['Please select the import group first'] = 'Please select the import group first';

$_['The column name is a mandatory field'] = 'The column name is a mandatory field';
$_['The pattern is a mandatory field'] = 'The pattern is a mandatory field';