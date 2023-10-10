<?php
// Heading
$_['heading_title']              = '定期付款';

// Text
$_['text_success']               = '成功: 定期付款設定已更新！';
$_['text_list']                  = '定期付款清單';
$_['text_add']                   = '新增定期付款';
$_['text_edit']                  = '定期付款 (#%s)';
$_['text_filter']                = '篩選';
$_['text_date_added']            = '新增日期';
$_['text_order']                 = '訂單 ID';
$_['text_customer']              = '會員';
$_['text_subscription_plan']     = 'Subscription Plan';
$_['text_product']               = '產品';
$_['text_quantity']              = '數量';
$_['text_trial']                 = 'Trial';
$_['text_subscription']          = '定期付款明細';
$_['text_subscription_trial']    = '%s 每 %d %s(s) for %d payment(s) then ';
$_['text_subscription_duration'] = '%s 每 %d %s(s) for %d payment(s)';
$_['text_subscription_cancel']   = '%s 每 %d %s(s) 至取消為止';
$_['text_cancel']                = '至取消為止';
$_['text_day']                   = '日';
$_['text_week']                  = '週';
$_['text_semi_month']            = '半月';
$_['text_month']                 = '月';
$_['text_year']                  = '年';
$_['text_date_next']             = '下期付款日期';
$_['text_remaining']             = '剩餘費用';
$_['text_payment_address']       = '會員地址';
$_['text_payment_method']        = '付款方式';
$_['text_shipping_address']      = '運送地址';
$_['text_shipping_method']       = '運送方式';
$_['text_history']               = '紀錄';
$_['text_history_add']           = '新增紀錄';

// Column
$_['column_subscription_id']     = '定期付款 ID';
$_['column_order_id']            = '訂單 ID';
$_['column_customer']            = '會員';
$_['column_comment']             = '備註';
$_['column_description']         = '說明';
$_['column_amount']              = '總計';
$_['column_notify']              = '通知會員';
$_['column_status']              = '狀態';
$_['column_date_added']          = '新增日期';
$_['column_product']             = '商品明細';
$_['column_quantity']            = '數量';
$_['column_total']               = 'Total';
$_['column_action']              = '管理';

// Entry
$_['entry_customer']             = 'Customer';
$_['entry_subscription_id']      = 'Subscription ID';
$_['entry_order_id']             = '訂單 ID';
$_['entry_subscription_plan']    = 'Subscription Plan';
$_['entry_trial_price']          = 'Trial Price';
$_['entry_trial_duration']       = 'Trial Duration';
$_['entry_trial_remaining']      = 'Trial Remaining';
$_['entry_trial_cycle']          = 'Trial Cycle';
$_['entry_trial_frequency']      = 'Trial Frequency';
$_['entry_trial_status']         = 'Trial Status';
$_['entry_price']                = 'Price';
$_['entry_duration']             = 'Duration';
$_['entry_remaining']            = 'Remaining';
$_['entry_cycle']                = 'Cycle';
$_['entry_frequency']            = 'Frequency';
$_['entry_date_next']            = 'Date Next';
$_['entry_comment']              = '備註';
$_['entry_amount']               = "總計";
$_['entry_notify']               = '通知會員';
$_['entry_override']             = '取代';
$_['entry_date_from']            = '日期(起)';
$_['entry_date_to']              = '日期(止)';
$_['entry_subscription_status']  = '訂閱狀態';

// Help
$_['help_trial_duration']   = 'The duration is the number of times the user will make a payment.';
$_['help_trial_cycle']      = 'Subscription amounts are calculated by the frequency and cycles.';
$_['help_trial_frequency']  = 'If you use a frequency of "week" and a cycle of "2", then the user will be billed every 2 weeks.';
$_['help_duration']         = 'The duration is the number of times the user will make a payment, set this to 0 if you want payments until they are cancelled.';
$_['help_cycle']            = 'Subscription amounts are calculated by the frequency and cycles.';
$_['help_frequency']        = 'If you use a frequency of "week" and a cycle of "2", then the user will be billed every 2 weeks.';

// Tab
$_['tab_order']                  = 'Orders';

// Error
$_['error_permission']           = 'Warning: You do not have permission to modify subscriptions!';
$_['error_status']               = 'Error: The subscription status does not match with the store status!';
$_['error_subscription']         = '警告: 訂閱資料不存在!';
$_['error_subscription_plan']    = '警告: 訂閱方案不存在!';
$_['error_subscription_status']  = 'Warning: Subscription status needs to be selected!';
$_['error_payment_method']       = '警告: 付款方式不存在!';
$_['error_service_type']		 = 'The service status has not been included with this transaction. If you see this error message, please contact your extension developer that handles the subscription services to resolve this issue!';
