<?php
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 572 $)
*/

$_['Database is not compatible ...'] = 'Database is not compatible with the extension.';
$_['Please re-install the exten...'] = 'Please re-install the extension at the \'Ka Extensions\' page.</br>
				It means you need to click \'Uninstall\' link and after the page refreshes click on the \'Install\' link. 
				That should help make the database up to date for the current version of the import extension.';
$_['ERROR: Stream filter is not...'] = 'ERROR: Stream filter is not found';
$_['The convert.iconv.* filter ...'] = 'The convert.iconv.* filter is not found on your server. You have to install it to the server because
				it is a mandatory component for running the import.';
$_['More information on convert...'] = 'More information on convert.iconv.* filter can be found here:
				<a target="_blank" href="https://www.ka-station.com/tickets/kb/faq.php?id=19">stream filter error</a>';
$_['\'CSV Product Import\' extens...'] = '\'CSV Product Import\' extension developed by';

$_['This script can be requeste...'] = 'This script can be requested at step 3 only';
$_['Wrong post parameters. Plea...'] = 'Wrong post parameters. Please verify that the file size is less than the maximum upload limit.';
$_['The Incoming images direc...']   = 'The \'Incoming images\' directory does not exist and it cannot be created, change the path of check file permissions.';
$_['Profile has been loaded suc...'] = 'Profile has been loaded succesfully';
$_['Profile was not loaded']         = 'Sorry, the profile was not loaded';
$_['Profile has been deleted su...'] = 'The profile has been deleted succesfully';
$_['Wrong field delimiter or in...'] = 'Wrong field delimiter or incorrect file format.';
$_['There are duplicated column...'] = 'There are duplicated column names in the file. Column names have to be unique in order to avoid errors in mapping.';
$_['Do not open the extension p...'] = 'Do not open the extension page in several tabs of the same browser. Import parameters may be lost.';
$_['Profile name is empty']          = 'Profile name is empty';
$_['Profile has been updated su...'] = 'Profile has been updated succesfully';
$_['Profile has been added succ...'] = 'Profile has been added succesfully';

// labels from the model file
//
$_['Custom multi-language field...'] = 'Custom multi-language field. Type';
$_['Variant option value']           = 'Variant option value';
$_['Model']                          = 'Model';
$_['A unique variant code']          = 'A unique variant code';
$_['GTIN']                           = 'GTIN';
$_['Not enabled on the product ...'] = 'Not enabled on the product variant settings page';
$_['Main Variant Image']             = 'Main Variant Image';
$_['Price']                          = 'Price';
$_['Regular variant price in pr...'] = 'Regular variant price in primary currency ';
$_['Special Price']                  = 'Special Price';
$_['Timeless special price for ...'] = 'Timeless special price for a default customer group in primary currency';
$_['Cost Price']                     = 'Cost Price';
$_['Product cost modifier in...'] = 'Product cost modifier in primary currency (%currency%)';
$_['This field is available whe...'] = 'This field is available when the <a target="_blank" href="https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=38194">Cost Price and Profit extension</a> is installed.';
$_['This field is available2...']    = 'This field is available when the <a target="_blank" href="https://www.ka-station.com/restricted_product_access_for_opencart_3">Restricted Product Access</a> is installed.';
$_['Quantity']                       = 'Quantity';
$_['Weight']                         = 'Weight';
$_['Weight class units declare...']  = 'Weight class units (declared in the store) can be used with the value. Example: 15.98lbs (no spaces).';
$_['A unique product code requi...'] = 'A unique product code required by Opencart';
$_['Product Identifiers']            = 'Product Identifiers';
$_['SKU']                            = 'SKU';
$_['UPC']                            = 'UPC';
$_['Universal Product Code']         = 'Universal Product Code';
$_['EAN']                            = 'EAN';
$_['European Article Number']        = 'European Article Number';
$_['JAN']                            = 'JAN';
$_['Japanese Article Number']        = 'Japanese Article Number';
$_['ISBN']                           = 'ISBN';
$_['International Standard Book...'] = 'International Standard Book Number';
$_['MPN']                            = 'MPN';
$_['Manufacturer Part Number']       = 'Manufacturer Part Number';
$_['Name']                           = 'Name';
$_['Product name']                   = 'Product name';
$_['Description']                    = 'Description';
$_['Product description']            = 'Product description';
$_['Category ID']                    = 'Category ID';
$_['Category Name']                  = 'Category Name';
$_['Full category path. Example...'] = 'Full category path. Example: category%cat_separator%%cat_separator%subcategory1%cat_separator%subcategory2';
$_['This product import extensi...'] = 'This product import extension can find or create categories by name.<br/>If you need to import other category fields please take a look at "<a href="https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=34399" target="_blank">CSV Category Import</a>" extension.';
$_['Sub-Category Name']              = 'Sub-Category Name';
$_['This is another way to defi...'] = 'This is another way to define sub-categories. This sub-category will go into the category defined by the "category name" field.';
$_['Sub-Sub-Category Name']          = 'Sub-Sub-Category Name';
$_['Main Product Image']             = 'Main Product Image';
$_['If you have a column combin...'] = 'If you have a column combining the main image and additional images, then select it for both fields. The first image will be used as the main image then';
$_['Additional Product Image']       = 'Additional Product Image';
$_['Multiple additional images ...'] = 'Multiple additional images are separated with a multi-value separator defined at <a target="_blank" href="%settings_url%">extension settings</a> page. See more information at <a target="_blank" href="https://www.ka-station.com/tickets/kb/faq.php?id=1">our help portal</a>.';
$_['Manufacturer']                   = 'Manufacturer';
$_['Manufacturer name']              = 'Manufacturer name';
$_['Regular product price in pr...'] = 'Regular product price in primary currency (%currency%)';
$_['Price & Inventory']              = 'Price & Inventory';
$_['Product cost in primary cur...'] = 'Product cost in primary currency (%currency%)';
$_['Available to Customer Groups']   = 'Available to Customer Groups';
$_['Customer Groups for a targ...']  = 'Customer Groups for <a target="_blank" href="https://www.ka-station.com/restricted_product_access_for_opencart_3">Restricted Product Access</a>. Values are separated by (%general_separator%)';
$_['Tax class']                      = 'Tax class';
$_['Existing tax class name fro...'] = 'Existing tax class name from the <a target="_blank" href="%taxes_url%">Taxes</a> page';
$_['Minimum Quantity']               = 'Minimum Quantity';
$_['Subtract Stock']                 = 'Subtract Stock';
$_['Out of Stock Status']            = 'Out of Stock Status';
$_['Existing stock status name ...'] = 'Existing stock status name from the <a target="_blank" href="%stock_statuses_url%">Stock Statuses</a> page';
$_['Status']                         = 'Status';
$_['Date Available']                 = 'Date Available';
$_['Format YYYY-MM-DD Example...']   = 'Format: YYYY-MM-DD, Example: 2012-03-25';
$_['Requires Shipping']              = 'Requires Shipping';
$_['1 - Yes, 0 - No.']               = '1 - Yes, 0 - No.';
$_['Shipping']                       = 'Shipping';
$_['Length']                         = 'Length';
$_['Length class units declare...']  = 'Length class units (declared in the store) can be used with the value. Example: 1.70m (no spaces)';
$_['Width']                          = 'Width';
$_['Height']                         = 'Height';
$_['Meta Tag Keywords']              = 'Meta Tag Keywords';
$_['SEO and Search']                 = 'SEO and Search';
$_['Meta Title']                     = 'Meta Title';
$_['Meta Tag Description']           = 'Meta Tag Description';
$_['Sort Order']                     = 'Sort Order';
$_['SEO Keyword']                    = 'SEO Keyword';
$_['SEO friendly URL for the pr...'] = 'SEO friendly URL for the product. Make sure that it is unique in the store.';
$_['Product Tags']                   = 'Product Tags';
$_['List of product tags separa...'] = 'List of product tags separated by comma';
$_['Related Product']                = 'Related Product';
$_['model identifier of the rel...'] = 'model identifier of the related product. Multiple values are allowed.';
$_['Downloads']                      = 'Downloads';
$_['Downloadable file(s)']           = 'Downloadable file(s)';
$_['Miscellaneous']                  = 'Miscellaneous';
$_['Location']                       = 'Location';
$_['This field is not used in f...'] = 'This field is not used in front-end but it can be defined for products';
$_['Layout']                         = 'Layout';
$_['Product layout']                 = 'Product layout';
$_['Points Required']                = 'Points Required';
$_['Number of reward points req...'] = 'Number of reward points required to make purchase';
$_['"Delete Product" Flag']          = '"Delete Product" Flag';
$_['Any non-empty value will be...'] = 'Any non-empty value will be treated as positive confirmation, be careful';
$_['Remove from Store']              = 'Remove from Store';
$_['product_id']                     = 'product_id';
$_['You import this value at yo...'] = 'You import this value at your own risk.';
$_['Customer Group']                 = 'Customer Group';
$_['Prioirity']                      = 'Prioirity';
$_['Date Start']                     = 'Date Start';
$_['Date End']                       = 'Date End';
$_['Reward Points']                  = 'Reward Points';
$_['Option Name']                    = 'Option Name';
$_['required']                       = 'required';
$_['Option Type']                    = 'Option Type';
$_['Option Value']                   = 'Option Value';
$_['Option Required']                = 'Option Required';
$_['Option Image']                   = 'Option Image';
$_['Value Sort Order']               = 'Value Sort Order';
$_['Sort order for option values']   = 'Sort order for option values';
$_['Group Sort Order']               = 'Group Sort Order';
$_['Sort order for option group...'] = 'Sort order for option groups. Empty cells are skipped.';
$_['Option Quantity']                = 'Option Quantity';
$_['Option Subtract']                = 'Option Subtract';
$_['Option Price']                   = 'Option Price';
$_['Product price modifier in...'] = 'Product price modifier in primary currency (%currency%)';
$_['Option Points']                  = 'Option Points';
$_['Option Weight']                  = 'Option Weight';
$_['Product Option Image']           = 'Product Option Image';
$_['Product Option View']            = 'Product Option View';
$_['possible values image tex...']   = 'possible values: image, text, regular';
$_['Author']                         = 'Author';
$_['Text field. Mandatory.']         = 'Text field. Mandatory.';
$_['Review Text']                    = 'Review Text';
$_['Mandatory.']                     = 'Mandatory.';
$_['Rating']                         = 'Rating';
$_['Mandatory. number (1 - 5)']      = 'Mandatory. number (1 - 5)';
$_['1 - enabled default value...']   = '1 - enabled (default value), 0 - disabled';
$_['Date Added']                     = 'Date Added';
$_['Recommended formatYYYY-MM-...']  = 'Recommended format:YYYY-MM-DD HH:MM:SS';
$_['Date Modified']                  = 'Date Modified';
$_['Profile name']                   = 'Profile name';
$_['Value']                          = 'Value';
$_['Option weight']                  = 'Option weight';
$_['Text']                           = 'Text';
$_['Image']                          = 'Image';
$_['Regular']                        = 'Regular';
$_['ISO-8859-1 (Western Europe)']    = 'ISO-8859-1 (Western Europe)';
$_['ISO-8859-5 (Cyrillc, DOS)']      = 'ISO-8859-5 (Cyrillc, DOS)';
$_['UNICODE (MS Excel text format)'] = 'UNICODE (MS Excel text format)';
$_['KOI-8R (Cyrillic, Unix)']        = 'KOI-8R (Cyrillic, Unix)';
$_['UTF-7']                          = 'UTF-7';
$_['UTF-8']                          = 'UTF-8';
$_['windows-1250 Central Europ...']  = 'windows-1250 (Central European languages)';
$_['windows-1251 (Cyrillc)']         = 'windows-1251 (Cyrillc)';
$_['windows-1252 Western langu...']  = 'windows-1252 (Western languages)';
$_['windows-1253 (Greek)']           = 'windows-1253 (Greek)';
$_['windows-1254 (Turkish)']         = 'windows-1254 (Turkish)';
$_['windows-1255 (Hebrew)']          = 'windows-1255 (Hebrew)';
$_['windows-1256 (Arabic)']          = 'windows-1256 (Arabic)';
$_['windows-1257 Baltic langua...']  = 'windows-1257 (Baltic languages)';
$_['windows-1258 (Vietnamese)']      = 'windows-1258 (Vietnamese)';
$_['Chinese Traditional (Big5)']     = 'Chinese Traditional (Big5)';
$_['CP932 (Japanese)']               = 'CP932 (Japanese)';
$_['The file looks like a nativ...'] = 'The file looks like a native MS Excel file. The extension works with CSV files only. Save your MS Excel file as UNICODE text file and try to import the data again.';
$_['main image cannot be saved -']   = 'main image cannot be saved -';
$_['Wrong date format in date ...']  = 'Wrong date format in \'date available\' field. We recommend to use YYYY-MM-DD. Ex. 2012-11-28';
$_['Unable to generate SEO frie...'] = 'Unable to generate SEO friendly URL for name';
$_['A relative path to the imag...'] = 'A relative path to the image file within \'image\' directory or URL.';
$_['If this field is specified ...'] = 'If this field is specified then the \'category name\' field will be ignored';
$_['Relative paths to image fil...'] = 'Relative paths to image files within \'image\' directory or URL.';
$_['Status Enabled can be def...']   = 'Status \'Enabled\' can be defined by \'1\' or \'Y\'. If the status column is not used then behavior depends on the extension settings.';
$_['Set this flag to a non...']      = '"Set this flag to a non empty value in order to remove the product from the stores selected on the prevous step (without real deletion from the database). It might be useful for multi-store solutions.';

$_['tab'] = 'Tab';
$_['semicolon'] = 'Semicolon';
$_['comma'] = 'Comma';
$_['pipe'] = 'Pipe';
$_['space'] = 'Space';

$_['The extension is not installed'] = 'The extension is not installed';

$_['txt_form_page_title'] = 'CSV Product Import';

$_['error_file_not_found']    = 'Sorry, the file was not found. Please verify that the file exists and its size is less than the maximum upload limit.';
$_['error_cannot_move_file']  = 'Uploaded File cannot be moved to a temporary directory ({dest_dir}). Check directory permissons.';

$_['txt_descr_master_model'] = 'When the master model is not empty, a new product will be created as a variant of that master product.';