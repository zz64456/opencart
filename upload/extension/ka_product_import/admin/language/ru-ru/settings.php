<?php
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 572 $)
*/

$_['Enabled for products with...']   = '"Включено" для товаров с количеством &gt; 0';
$_['-Enabled- for all']              = '"Включено" для всех';
$_['-Disabled- for all']             = '"Отключено" для всех';

$_['General'] = 'Общие';
$_['Separators'] = 'Разделители';
$_['Optimization'] = 'Оптимизация';

$_['model'] = 'Модель';
$_['sku']   = 'СКУ';
$_['upc']   = 'УПЦ';
$_['ean']   = 'ЕАН';

$_['Setting']                        = 'Настройка';
$_['Value']                          = 'Значение';

// tab general 

$_['txt_title_ka_product_import_create_options'] = 'Создать новые опции товара из файла';
$_['txt_title_ka_product_import_generate_seo_keyword'] = 'Создать ключевое слово SEO для новых товаров';
$_['txt_title_ka_product_import_enable_product_id'] = 'Включить столбец product_id в выборе столбца';

$_['txt_title_ka_product_import_status_for_new_products'] = 'Установить статус для новых товаров';
$_['txt_title_ka_product_import_status_for_existing_products'] = 'Установить статус для существующих товаров';
$_['txt_title_ka_product_import_key_fields'] = 'Ключевые поля';
$_['txt_title_ka_product_import_default_out_of_stock_status_id'] = "Дефоултный статус отсутствия товара";

// tab separators

$_['txt_title_ka_product_import_general_separator']  = 'Общий разделитель для нескольких значений';
$_['txt_title_ka_product_import_multicat_separator'] = 'Разделитель для нескольких значений в поле <b>category</b>';
$_['txt_title_ka_product_import_related_products_separator'] = 'Разделитель для нескольких значений в поле <b>related product</b>';
$_['txt_title_ka_product_import_image_separator'] = 'Разделитель для нескольких значений в поле <b>additional images</b>';
$_['txt_title_ka_product_import_options_separator'] = 'Разделитель для нескольких значений в поле <b>product option</b>';

$_['txt_title_ka_product_import_parse_simple_option_value'] = 'Разобрать значение ПРОСТЫХ ОПЦИЙ';
$_['txt_title_ka_product_import_simple_option_separator'] = 'Разделитель значений в ПРОСТОЙ ОПЦИИ';
$_['txt_title_ka_product_import_simple_option_field_order'] = 'Порядок полей в значении ПРОСТОЙ ОПЦИИ';

// tab optimization

$_['txt_title_ka_product_import_update_interval']   = 'Интервал обновления скрипта в секундах (5-25)';
$_['txt_title_ka_product_import_skip_img_download'] = 'Пропустить загрузку изображений для существующих файлов';
$_['txt_title_ka_product_import_enable_macfix']     = 'Улучшенная совместимость с csv-файлами, созданными на MacOS';
$_['txt_title_ka_product_import_compare_as_is']     = 'Игнорируйте регистр букв и отступы при сравнении';

$_['txt_title_ka_product_import_save_max_date'] = 'Сохранять максимальную дату вместо 0000-00-00';

//
// tooltips
//

// tab general 

$_['txt_tooltip_ka_product_import_create_options'] = 'Если вы включите этот параметр, то будут созданы новые опции товара из файла, в противном случае они будут пропущены.';
$_['txt_tooltip_ka_product_import_generate_seo_keyword'] = 'Ключевое слово SEO генерируется, когда оно не определено в файле';
$_['txt_tooltip_ka_product_import_enable_product_id'] = '';
$_['txt_tooltip_ka_product_import_status_for_new_products'] = 'Этот параметр игнорируется, если поле статуса определено в файле';
$_['txt_tooltip_ka_product_import_status_for_existing_products'] = 'Этот параметр игнорируется, если поле статуса определено в файле';
$_['txt_tooltip_ka_product_import_key_fields'] = 'Ключевое поле обязательно для каждой записи товара в файле, если только вы не используете \'product_id\' для обновления товаров.';

// tab separators

$_['txt_tooltip_ka_product_import_general_separator'] = 'Общий разделитель для нескольких значений';
$_['txt_tooltip_ka_product_import_multicat_separator'] = 'Разделитель для нескольких значений в поле <b>category</b>';
$_['txt_tooltip_ka_product_import_related_products_separator'] = 'Разделитель для нескольких значений в поле <b>related product</b>';
$_['txt_tooltip_ka_product_import_image_separator'] = 'Разделитель для нескольких значений в поле <b>additional images</b>';
$_['txt_tooltip_ka_product_import_options_separator'] = 'Разделитель для нескольких значений в поле <b>product option</b>';

$_['txt_tooltip_ka_product_import_parse_simple_option_value'] = 'Оставьте этот параметр пустым, если у вас есть одно значение в ячейке на строку';
$_['txt_tooltip_ka_product_import_simple_option_separator'] = 'Вы можете использовать escape-коды \r и \ n для определения нового разделителя строк';
$_['txt_tooltip_ka_product_import_simple_option_field_order'] = 'Укажите поля и их порядок в поле. Используйте ; в качестве разделителя полей.
									<br /> Эти поля могут быть использованы: <b>%simple_option_fields%</b>';

// tab optimization

$_['txt_tooltip_ka_product_import_update_interval']   = 'Уменьшите это значение, если во время импорта возникают проблемы с подключением к серверу. Значение по умолчанию равно 15.';
$_['txt_tooltip_ka_product_import_skip_img_download'] = 'этот параметр применим только к URL-адресам изображений';
$_['txt_tooltip_ka_product_import_enable_macfix']     = 'Значительно замедляет процесс импорта больших файлов. Избегайте его использования.';
$_['txt_tooltip_ka_product_import_compare_as_is']     = 'Улучшает поиск значений, но немного замедляет импорт. Лучше оставить включённым.';
