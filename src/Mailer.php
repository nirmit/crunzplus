<?php

namespace Crunz;

use Crunz\Configuration\Configurable;

class Mailer extends Singleton {

    use Configurable;
    protected $mailer;

    public function __construct( \Swift_Mailer $mailer = null ) {
        $this->configurable();
        $this->mailer = $mailer;
    }

    protected function getMailer() {
        if ( $this->mailer ) {
            return $this->mailer;
        }

        switch ( $this->config( 'mailer.transport' ) ) {
            case 'smtp':
                $transport = $this->getSmtpTransport();
                break;
            case 'mail':
                $transport = $this->getMailTranport();
                break;
            default:
                $transport = $this->getSendMailTransport();
        }

        return \Swift_Mailer::newInstance( $transport );
    }

    protected function getSmtpTransport() {
        return \Swift_SmtpTransport::newInstance( $this->config( 'smtp.host' ), $this->config( 'smtp.port' ), $this->config( 'smtp.encryption' ) )
            ->setUsername( $this->config( 'smtp.username' ) )
            ->setPassword( $this->config( 'smtp.password' ) );
    }

    protected function getMailTrasport() {
        return \Swift_MailTransport::newInstance();
    }

    protected function getSendMailTransport() {
        return \Swift_SendmailTransport::newInstance();
    }

    public function send( $subject, $message ) {
        $this->getMailer()->send( $this->getMessage( $subject, $message ) );
    }

    protected function getMessage( $subject, $message ) {
        return \Swift_Message::newInstance()
            ->setBody( $message )
            ->setSubject( $subject )
            ->setFrom( [$this->config( 'mailer.sender_email' ) => $this->config( 'mailer.sender_name' )] )
            ->setTo( $this->config( 'mailer.recipients' ) );
    }
}