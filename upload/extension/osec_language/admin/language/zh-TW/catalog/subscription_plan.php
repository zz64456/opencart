<?php
// Heading
$_['heading_title']          = '訂閱方案';

// Text
$_['text_success']           = '成功: 訂閱方案資料已更新!';
$_['text_list']              = '訂閱方案清單';
$_['text_add']               = '新增訂閱方案';
$_['text_edit']              = '編輯訂閱方案';
$_['text_subscription']      = '訂閱方案';
$_['text_trial']             = '試訂方案';
$_['text_day']               = '天';
$_['text_week']              = '週';
$_['text_semi_month']        = '半月';
$_['text_month']             = '月';
$_['text_year']              = '年';

// Entry
$_['entry_name']            = '訂閱方案名稱';
$_['entry_trial_duration']  = '試訂期間';
$_['entry_trial_cycle']     = '試訂週期數';
$_['entry_trial_frequency'] = '試訂週期別';
$_['entry_trial_status']    = '試訂狀態';
$_['entry_duration']        = '訂閱期';
$_['entry_cycle']           = '週期數';
$_['entry_frequency']       = '週期別';
$_['entry_status']          = '狀態';
$_['entry_sort_order']      = '排序';

// Column
$_['column_name']           = '訂閱方案名稱';
$_['column_sort_order']     = '排序';
$_['column_action']         = '管理';

// Help
$_['help_trial_duration']   = '訂閱期間是用戶將進行付款的次數。';
$_['help_trial_cycle']      = 'Subscription amounts are calculated by the frequency and cycles.';
$_['help_trial_frequency']  = 'If you use a frequency of "week" and a cycle of "2", then the user will be billed every 2 weeks.';
$_['help_duration']         = 'The duration is the number of times the user will make a payment, set this to 0 if you want payments until they are cancelled.';
$_['help_cycle']            = '使用週期數搭配週期別，以設定計費週期。';
$_['help_frequency']        = '如果你的週期別選擇 "週" 且 週期數選擇 "2"，則代表每 2 週計費一次。';

// Error
$_['error_warning']         = '警告: Please check the form carefully for errors!';
$_['error_permission']      = '警告: You do not have permission to modify subscription plans!';
$_['error_name']            = 'Subscription Plan Name must be greater than 3 and less than 255 characters!';
$_['error_trial_duration']  = 'Trial duration must be greater than 0!';
$_['error_product']         = '警告: This subscription plans cannot be deleted as it is currently assigned to %s products!';