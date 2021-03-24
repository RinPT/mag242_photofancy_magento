<?php

namespace Photofancy\TemporaryChanges\Rewrite\Sales\Model\Order\Email;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\Template\TransportBuilderByStore;
use Magento\Sales\Model\Order\Email\Container\IdentityInterface;
use Magento\Sales\Model\Order\Email\Container\Template;
use Photofancy\TemporaryChanges\Rewrite\Framework\Mail\Template\TransportBuilder as PhotofancyTransportBuilder;

class SenderBuilder extends \Magento\Sales\Model\Order\Email\SenderBuilder
{
    /** @var $transportBuilder PhotofancyTransportBuilder */

    /** @var PhotofancyTransportBuilder */
    private $photofancyTransportBuilder;

    /** @var Filesystem */
    private $filesystem;

    public function __construct(
        Template $templateContainer,
        IdentityInterface $identityContainer,
        TransportBuilder $transportBuilder,
        PhotofancyTransportBuilder $photofancyTransportBuilder,
        Filesystem $filesystem,
        TransportBuilderByStore $transportBuilderByStore = null
    ) {
        $this->filesystem = $filesystem;
        parent::__construct($templateContainer, $identityContainer, $photofancyTransportBuilder, $transportBuilderByStore);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function send()
    {
        $this->configureEmailTemplate();

        $this->transportBuilder->addTo(
            $this->identityContainer->getCustomerEmail(),
            $this->identityContainer->getCustomerName()
        );

        $copyTo = $this->identityContainer->getEmailCopyTo();

        if (!empty($copyTo) && $this->identityContainer->getCopyMethod() == 'bcc') {
            foreach ($copyTo as $email) {
                $this->transportBuilder->addBcc($email);
            }
        }

        $this->addAttachment('agb');

        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
    }

    public function addAttachment($type)
    {
        switch ($type) {
            case 'agb':
                /**
                 * Need to attach pdf to the order email
                 * its located in filesystem: media_path/PhotoFancy-Terms.pdf
                 */
                $attachmentPath = 'photofancy-terms.pdf';
                $attachmentFilename = pathinfo($attachmentPath, PATHINFO_BASENAME);

                $mediapath = $this->filesystem->getDirectoryRead(DirectoryList::PUB . '/downloads')->getAbsolutePath();
                $attachmentPath = $mediapath . $attachmentPath;
                $attachmentBody = file_get_contents($attachmentPath);
                $attachment['body'] = $attachmentBody;
                $attachment['filename'] = $attachmentFilename;

                $this->transportBuilder->addAttachment($attachment);
                break;
        }
    }
}
