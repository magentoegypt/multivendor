<?php
namespace MagentoEgypt\VendorExtend\Plugin\Model;

class Credit
{
    protected $messages = [
        'refund_by_credit' => "Order refunded #%1, Creditmemo #%2",
        'refund_spent_credit' => "Refund spent credit(s) from cancelled order #%1",
        'spend_credit' => "Spent credit on order #%1",
        'admin_add_credit' => "Admin add %1 credits to your credit account.",
        'admin_subtract_credit' => "Admin subtract %1 credits from your credit account.",
        'buy_credit' => "Buy %1 credits from store",
        'withdraw_credit' => "Withdraw Money",
        'cancel_withdrawal' => "Cancel Withdrawal Request",
        'item_commission' => "Commission of order #%1, item %2 x %3",
        'order_payment' => "Credit from order #%1, invoice #%2",
        'item_commission_refund' => "Refund Commission of order #%1, item %2 x %3",
        'order_payment_refund' => "Refund order #%1, Creditmemo #%2",
        'vendor_refund_spent_credit' => "Refund spent credit(s) from order #%1"
    ];

    public function afterGetData($subject, $data) {
        foreach ($data as &$item) {
            if(isset($this->messages[$item['type']])) {
                $vendorDescription = isset($item['vendor_description']) ? json_decode($item['vendor_description'],true) : [];
                if(!empty($vendorDescription)) {
                    $template = __($this->messages[$item['type']], $vendorDescription);
                } else {
                    $template = __($this->messages[$item['type']]);
                }
                $item['description'] = $template;
            }
        }
        return $data;
    }

}
