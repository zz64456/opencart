<?php
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 572 $)
*/

$_['-Enabled- for all']              = '\'Enabled\' for all';
$_['-Disabled- for all']             = '\'Disabled\' for all';
$_['Enabled for products with...']   = '\'Enabled\' for products with quantity &gt; 0';

$_['General'] = 'General';
$_['Separators'] = 'Separators';
$_['Optimization'] = 'Optimization';

$_['model'] = 'Model';
$_['sku'] = 'SKU';
$_['upc'] = 'UPC';
$_['ean'] = 'EAN';

$_['Setting']                        = 'Setting';
$_['Value']                          = 'Value';

// tab general 

$_['txt_title_ka_product_import_create_options'] = 'Create new product options from the file';
$_['txt_title_ka_product_import_generate_seo_keyword'] = 'Generate SEO keyword for new products';
$_['txt_title_ka_product_import_enable_product_id'] = 'Enable product_id column in the column selection';
$_['txt_title_ka_product_import_status_for_new_products'] = 'Set status for new products';
$_['txt_title_ka_product_import_status_for_existing_products'] = 'Set status for existing products';
$_['txt_title_ka_product_import_key_fields'] = 'Key fields';
$_['txt_title_ka_product_import_default_out_of_stock_status_id'] = "Default 'Out Of Stock' status";

// tab separators

$_['txt_title_ka_product_import_general_separator']  = 'General separator for multiple values';
$_['txt_title_ka_product_import_multicat_separator'] = 'Separator for multiple values in the <b>category</b> field';
$_['txt_title_ka_product_import_related_products_separator'] = 'Separator for multiple values in the <b>related product</b> field';
$_['txt_title_ka_product_import_image_separator'] = 'Separator for multiple values in the <b>additional image</b> field';
$_['txt_title_ka_product_import_options_separator'] = 'Separator for multiple values in the <b>product option</b> cell field';
$_['txt_title_ka_product_import_parse_simple_option_value'] = 'Parse SIMPLE OPTIONS value';
$_['txt_title_ka_product_import_simple_option_separator'] = 'SIMPLE OPTIONS value separator';
$_['txt_title_ka_product_import_simple_option_field_order'] = 'Field order in SIMPLE OPTION value';

// tab optimization

$_['txt_title_ka_product_import_update_interval']   = 'Script update interval in seconds (5-25)';
$_['txt_title_ka_product_import_skip_img_download'] = 'Skip downloading images for existing files';
$_['txt_title_ka_product_import_enable_macfix']     = 'Better compatibility with files generated on Mac';
$_['txt_title_ka_product_import_compare_as_is']     = 'Ignore letter-case and leading/trailing spaces in string comparison';

$_['txt_title_ka_product_import_save_max_date'] = 'Set maximum date for empty dates';

//
// tooltips
//

// tab general 

$_['txt_tooltip_ka_product_import_create_options'] = 'If you enable this setting then new product options will be created otherwise they will be skipped';
$_['txt_tooltip_ka_product_import_generate_seo_keyword'] = 'SEO keyword is generated when it is not defined in the file';
$_['txt_tooltip_ka_product_import_enable_product_id'] = '';
$_['txt_tooltip_ka_product_import_status_for_new_products'] = 'This option is ignored if the status field is defined in the file';
$_['txt_tooltip_ka_product_import_status_for_existing_products'] = 'This option is ignored if the status field is defined in the file';
$_['txt_tooltip_ka_product_import_key_fields'] = 'The key field is mandatory for each product record in the file unless you use \'product_id\' for updating products.';

// tab separators

$_['txt_tooltip_ka_product_import_general_separator'] = 'General separator for multiple values';
$_['txt_tooltip_ka_product_import_multicat_separator'] = 'Separator for multiple values in the <b>category</b> field';
$_['txt_tooltip_ka_product_import_related_products_separator'] = 'Separator for multiple values in the <b>related product</b> field';
$_['txt_tooltip_ka_product_import_image_separator'] = 'Separator for multiple values in the <b>additional image</b> field';
$_['txt_tooltip_ka_product_import_options_separator'] = 'Separator for multiple values in the <b>product option</b> cell field';
$_['txt_tooltip_ka_product_import_parse_simple_option_value'] = 'Parse value defined as SIMPLE OPTION';
$_['txt_tooltip_ka_product_import_simple_option_separator'] = 'You can use \r and \n escape codes for defining a new line separator';
$_['txt_tooltip_ka_product_import_simple_option_field_order'] = 'Specify fields and their order in the field. Use ; as a field separator.
<br /> These fields can be used: <b>%simple_option_fields%</b>';

// tab optimization

$_['txt_tooltip_ka_product_import_update_interval']   = 'Reduce this value if you experience server connection issues during the import. Default value is 15.';
$_['txt_tooltip_ka_product_import_skip_img_download'] = 'This option is applicable to image URLs only';
$_['txt_tooltip_ka_product_import_enable_macfix']     = 'It slows down the import of big files significantly. Avoid using this parameter.';
$_['txt_tooltip_ka_product_import_compare_as_is']     = 'Ignore letter-case and leading/trailing spaces in string comparison';
$_['txt_tooltip_ka_product_import_save_max_date']       = 'Set the end date (2099-01-01) for discount/special date ranges when it is omitted';