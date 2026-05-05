<?php
namespace Mstore\ContactUs\Model;
use Mstore\ContactUs\Api\ContactUsInterface;
use Magento\Contact\Model\MailInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;

class ContactUsRepository implements ContactUsInterface
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var MailInterface
     */
    private $mail;

    /**
     * @param Context $context
     * @param MailInterface $mail
     */
    public function __construct(
        Context $context,
        MailInterface $mail
    ) {
        $this->context = $context;
        $this->mail = $mail;
    }

    /**
     * @inheritdoc
     */
    public function sendContactUs($name, $email, $phone, $comment){
        if ($this->validatedParams($name, $email, $phone, $comment)) {
            try {
                $this->sendEmail(["name"=>$name, "email"=>$email, "phone"=>$phone, "comment"=>$comment]);
                return 'Thanks for contacting us with your comments and questions. We\'ll respond to you very soon.';
            } catch (LocalizedException $e) {
                throw $e;
            } catch (\Exception $e) {
                throw $e;
            }   
        }
    }

    /**
     * @param array $post Post data from contact form
     * @return void
     */
    private function sendEmail($post)
    {
        $this->mail->send(
            $post['email'],
            ['data' => $post]
        );
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function validatedParams($name, $email, $phone, $comment)
    {
        if (trim($name) === '') {
            throw new LocalizedException(__('Enter the Name and try again.'));
        }
        if (trim($comment) === '') {
            throw new LocalizedException(__('Enter the comment and try again.'));
        }
        if (false === \strpos($email, '@')) {
            throw new LocalizedException(__('The email address is invalid. Verify the email address and try again.'));
        }

        return true;
    }
}