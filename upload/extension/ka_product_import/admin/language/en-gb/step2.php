<?php
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 572 $)
*/
$_['strongCautionstrong I...']       = '<strong>Caution!</strong> It is recommended to create <a href="%backup_link%" target="_blank">a database backup</a> before starting the import procedure
					because these chagnes are irreversible.';
$_['STEP 2 of 3']                    = 'STEP 2 of 3';
$_['Profile']                        = 'Save as Import Profile';
$_['Save']                           = 'Save';
$_['File size']                      = 'Currrent file size';
$_['Select corresponding column...'] = 'Select corresponding columns for product fields on all tabs. It is OK to skip fields or columns.';
$_['Product Field']                  = 'Product Field';
$_['Column in File']                 = 'Column in File';
$_['Set Default']                    = 'Set Default';
$_['Notes']                          = 'Notes';
$_['1 Only attributes declared...']  = 'Only attributes declared in the store will be imported. You can create new attributes <a href="%attribute_page_url%">here</a>';
$_['Attribute Name']                 = 'Attribute Name';
$_['Attribute Group']                = 'Attribute Group';
$_['Available filter groups are...'] = 'Available filter groups are listed below. You can create new filter groups <a href="%filter_page_url%">here</a>';
$_['Filter Group']                   = 'Filter Group';
$_['There are two option format...'] = 'There are two option formats available for importing the options. The <b>simple format</b> is used when you import
						existing options with several standard fields (price, quantity, weight). 
						The <b>extended format</b> is used to create new options and update all standard option fields.
						Both formats can be combined in single import.
						';
$_['Select the columns containg...'] = 'Select the columns containg option data. New options should be created beforehand at <a href="%option_page_url%">the options page</a>.';
$_['Field']                          = 'Field';
$_['Type / Description']             = 'Type / Description';
$_['The store contains more th...']  = '<p>
														The store contains more than %max_visible_options% options. Their fields are not displayed to speed up the page load.
														If you want to import simple options, please click on the button below to load fields for simple options.
													</p>';
$_['Load totaloptionsnotl...']       = 'Load %total_options_not_loaded% Options';
$_['If you need to import all o...'] = 'If you need to import all option properties and create new options please use the extended format.';
$_['Not available']                  = 'Not available';
$_['Product Discounts. You shou...'] = 'Product Discounts. You should specify at least \'quantity\' and \'price\' values to add new discount records.';
$_['Product Special Prices. You...'] = 'Product Special Prices. You should specify at least \'price\' value to add new special price records.';
$_['Product Reward Points.']         = 'Product Reward Points.';
$_['Product Profiles for recur...']  = 'Product Profiles (for recurring billing).';
$_['Customer reviews.']              = 'Customer reviews.';
$_['Product variants.']              = 'Product variants.';

$_['OPTION']                         = 'OPTION';
$_['TYPE']                           = 'TYPE';
$_['Required']                       = 'Required';
$_['Yes, this is a Variant option']  = 'Yes, this is a Variant option';
$_['Yes']                            = 'Yes';
$_['No']                             = 'No';
$_['Option group property']          = 'Option group property';
$_['Option Image View']              = 'Option Image View';
$_['Not available']                  = 'Not available';

$_['General']                        = 'General';
$_['Attributes']                     = 'Attributes';
$_['Filters']                        = 'Filters';
$_['Options']                        = 'Options';
$_['Discounts']                      = 'Discounts';
$_['Specials']                       = 'Specials';
$_['Product Profiles']               = 'Product Profiles';
$_['Reviews']                        = 'Reviews';
$_['SKU']                            = 'SKU';
$_['UPC']                            = 'UPC';
$_['EAN']                            = 'EAN';
$_['ISBN']                           = 'ISBN';
$_['MPN']                            = 'MPN';
$_['Main Properties']                = 'Main Properties';
$_['Simple Format']                  = 'Simple Format';
$_['Extended Format']                = 'Extended Format';
$_['Field']                          = 'Field';
$_['Column in File']                 = 'Column in File';
$_['Notes']                          = 'Notes';
$_['Done']                           = 'Done';

$_['txt_tab_general_intro'] = 'Main product fields are mapped on this tab. Minimum fields you have to map is a key
field marked with the red asterisk and any other field you wish to update. For example you can have a file with just model and
quantity columns';