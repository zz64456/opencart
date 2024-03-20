<?php
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 572 $)
*/

$_['STEP 1 of 3']                    = 'STEP 1 of 3';
$_['This page allows you to imp...'] = 'This page allows you to import product data from a file in <a href="https://www.ka-station.com/tickets/kb/faq.php?id=36" target="_blank">CSV</a> format.';
$_['Profiles can store import p...'] = 'Profiles can store import parameters to simlify management of different import configurations. You can save import parameters to a profile on the next step';
$_['Profile']                        = 'Import Profile';
$_['no profiles present']            = 'no profiles present';
$_['bLocal computerb - y...']        = '<b>\'Local computer\'</b> - you upload a file from your computer.<br /><br /><b>\'Server\'</b> - file location is on the server where the site is installed. The file on the server should be resided within the store directory.';
$_['bShopeeb - ...']                 = '<b>\'Shopee\'</b> - you upload a shopee file to add products.<br /><br /><b>\'Kamod\'</b> - you upload an official-organized file to add products.';
$_['File Location']                  = 'File Location';
$_['Local computer']                 = 'Local computer';
$_['Shopee']                         = 'Shopee';
$_['Kamod']                          = 'Kamod';
$_['Server']                         = 'Server';
$_['A csv file is widely used d...'] = 'Please select your csv file here. You can download a sample file at <a href=&quot;https://www.ka-station.com/samples/oc3/csv_product_import/demo_products_utf8.csv&quot;>this url</a>';
$_['File']                           = 'File';
$_['Clear']                          = 'Clear';
$_['Max. file size (server limit)']  = 'Max. file size (server limit)';
$_['File path']                      = 'File path';
$_['Rename the file after succe...'] = 'Rename the file after successful import';
$_['Field Delimiter']                = 'Field Delimiter';
$_['select from predefined values']  = 'select from predefined values';
$_['You have to be aware of the...'] = 'You have to be aware of the import file charset. Use ISO-8859-1 if your data consists of Latin characters only.';
$_['File Charset']                   = 'File Charset';
$_['define manually']                = 'define manually';
$_['The mode affects only data ...'] = 'The mode affects only data with multiple records (categories, specials, discounts, etc.).<br/><br/> In the <b>\'Add\'</b> mode all related information is added to the product.<br /><br />In the <b>\'Replace\'</b> mode old records related to the product are deleted first. It might be useful for updating special prices, discounts.';
$_['Import Mode']                    = 'Import Mode';
$_['Add new records (safe)']         = 'Add new records (safe)';
$_['Replace old records']            = 'Replace old records';
$_['Store']                          = 'Store';
$_['It is a sub-category separa...'] = 'It is a sub-category separator. A separator of multiple product categories can be defined on the &quot;&lt;a href=&quot;%settings_url%&quot; target=&quot;_blank&quot;&gt;extension settings&lt;/a&gt;&quot; page.
								<br/><br/>Example: <p style=&quot;display:block;&quot;>category%cat_separator%subcategory1%cat_separator%subcategory2</p>';
$_['Sub-Category Separator']         = 'Sub-Category Separator';
$_['That option allows to add t...'] = 'That option allows to add the product to each category of the specified category path, not just the last one as it works by default.';
$_['Add product to each categor...'] = 'Add product to each category in path';
$_['IMPORTANT File names must ...']  = 'IMPORTANT: File names must consist of Latin characters only. Files with national characters in names will not be imported.';
$_['Path to Images Directory']       = 'Path to Images Directory';
$_['IMPORTANT Images provided ...']  = 'IMPORTANT: Images provided as URLs will be downloaded to your server and it may dramatically decrease speed of the import. Avoid using URLs in the import as long as you can.';
$_['Incoming Images Directory']      = 'Incoming Images Directory';
$_['URLs are not allowed due to...'] = 'URLs are not allowed due to server configuration settings (curl library not found and allow_url_fopen=false).';
$_['New products will be placed...'] = 'New products will be placed into this category if another one is not specified in the file.';
$_['Default Category for New Pr...'] = 'Default Category for New Products';
$_['Parent category for all cat...'] = 'Parent category for all categories defined in the file';
$_['Parent Category for Categories'] = 'Parent Category for Categories';
$_['Path to Source Directory']       = 'Path to Source Directory';
$_['Where to Get File Postfix']      = 'Where to Get File Postfix';
$_['Generate Random Postfixes']      = 'Generate Random Postfixes';
$_['Detect Postfixes in File Names'] = 'Detect Postfixes in File Names';
$_['Do Not Use Postfixes']           = 'Do Not Use Postfixes';
$_['Price multiplier leave emp...']  = 'Price multiplier (leave empty or set to 1 if the price should not be updated). Multiple price modifiers can be defined with price rules.';
$_['General Price Multiplier']       = 'General Price Multiplier';
$_['Import price rules allow to...'] = 'Import price rules allow to define price multipliers basing on the row content';
$_['Disable Store Products not ...'] = 'Disable Store Products not Presented in File';
$_['Do not Create New Products']     = 'Do not Create New Products';
$_['Import skip rules allow to ...'] = 'Import skip rules allow to skip lines of the file basing on its contents';
$_['Import replacements allow t...'] = 'Import replacements allow to replace cell content with another one';
$_['ampltbrampgt tags wil...']       = '&amp;lt;br&amp;gt; tags will be added to split new lines. Any existing html tags will show as regular characters in the description';
$_['Treat Description as Plain ...'] = 'Treat Description as Plain Text';
$_['if template product is spec...'] = 'if template product is specified then new products are created as a clone of the template product and updated with data from the file';
$_['Template Product']               = 'Template Product';
$_['Autocomplete']                   = 'Autocomplete';
$_['The field allows to add a p...'] = 'The field allows to add a prefix to the key fields (%key_fields%). It is useful when you import products from several suppliers with different code systems. In this case you can easily make them unique by adding different prefixes for them.';
$_['Key Field Prefix']               = 'Key Field Prefix';
$_['Please notice that some ma...']  = 'Please notice, that some major settings affecting the import routine are available on the <a target="_blank" href="%settings_url%">extension settings page</a>.';
$_['The file size exceeds limit...'] = 'The file size exceeds limits defined by the server. File content might be truncated. You should upload the file to the server manually through ftp/scp protocol and specify <b>"File Location"</b> as <b>"Server"</b>';
$_['Be careful. The file has xm...'] = 'Be careful. The file has xml extension. The import works with CSV files only.';
$_['Be careful. The file has MS...'] = 'Be careful. The file has MS Excel file extension. The import works with CSV files only. You can save the XLS file as a CSV file in MS Excel.';
$_['Be careful. The file has an...'] = 'Be careful. The file has an archive extension. The import works with CSV files only.';