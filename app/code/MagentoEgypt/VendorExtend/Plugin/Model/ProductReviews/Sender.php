<?php 
namespace MagentoEgypt\VendorExtend\Plugin\Model\ProductReviews;

class Sender
{
	protected $request;

    public function __construct(
       \Magento\Framework\App\RequestInterface $request
    ) {
       $this->request = $request;
    }

    public function beforeSendCouponCodeEmail( $subject, $data, $storeId = null, $emailTemplate = "lof_product_reviews_email_settings_review_product_templates")
    {
    	$post = $this->request->getPostValue();
    	if(isset($post['email']) && !empty($post['email'])) {
    		$data['customer_email'] = $post['email'];
    	}
    	return [$data, $storeId, $emailTemplate];
    }
}