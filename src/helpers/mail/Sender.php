<?php
  
  namespace PF\helpers\mail;
  
  /**
   * This class serves for sending emails
   *
   * @author    Pavel Filípek <www.filipek-czech.cz>
   * @copyright © 2016, Proclient s.r.o.
   * @created   05.10.2016
   */
  class Sender
  {
    const MAIL_TYPE_HTML = 'text/html';
    const MAIL_TYPE_TEXT = 'text/plain';
    
    private static $mailer = NULL;
  
    /**
     * Mailer constructor.
     *
     * @param string|NULL $tmpDir
     * @param string|NULL $server
     * @param string|NULL $user
     * @param string|NULL $password
     */
    public function __construct($tmpDir = NULL, $server = NULL, $user = NULL, $password = NULL) {
      \Swift::init(function () use ($tmpDir) {
        \Swift_Preferences::getInstance()->setTempDir(((is_null($tmpDir)) ? sys_get_temp_dir() : $tmpDir));
      });
      
      // create the Transport
      if (is_null($server) || is_null($user) || is_null($password)) {
        $transport = \Swift_MailTransport::newInstance();
      } else {
        $transport = \Swift_SmtpTransport::newInstance($server, 25)->setUsername($user)->setPassword($password);
      }
      
      if (is_null(self::$mailer)) {
        // create the Mailer using your created Transport
        self::$mailer = \Swift_Mailer::newInstance($transport);
      }
    }
  
    /**
     * Function for send email by function mail
     *
     * @param string       $subject subject of email
     * @param              $messageText
     * @param array        $from    sender array('example@google.com' => 'Test sender')
     * @param array        $to      recipient array('test@google.com', 'example@google.com' => 'Test sender', ...)
     * @param array        $cc      recipient array('test@google.com', 'example@google.com' => 'Test sender', ...)
     * @param array        $bcc     recipient array('test@google.com', 'example@google.com' => 'Test sender', ...)
     * @param string       $type    text/html, text/plain
     * @param Attachment[] $attachments
     *
     * @return boolean|array
     */
    public function sendMail($subject, $messageText, $from, $to, array $cc = [], array $bcc = [], $type = self::MAIL_TYPE_HTML, array $attachments = []) {
      $messageText = <<<MESSAGE
  <html>
  <head>
    <meta charset="utf-8" />
    <title>{$subject}</title>
  </head>
  <body>
    {$messageText}
  </body>
  </html>
MESSAGE;
      
      
      // Create a message
      $message = \Swift_Message::newInstance($subject)->setFrom((($from) ? $from : []))->setTo($to)->setBody($messageText, $type);
      
      // copy and blind copy
      if ($cc) {
        $message->setCc($cc);
      }
      if ($bcc) {
        $message->setBcc($bcc);
      }
      
      // attachments
      foreach ($attachments as /* @var Attachment $attachment */ $attachment) {
        /* @var $mailAttachment \Swift_Attachment */
        $mailAttachment = (($attachment->getPath()) ? \Swift_Attachment::fromPath($attachment->getPath()) : \Swift_Attachment::newInstance());
        $mailAttachment->setFilename($attachment->getName())->setContentType($attachment->getContentType());
        
        if (!is_null($attachment->getData())) {
          $mailAttachment->setBody($attachment->getData());
        }
        
        $message->attach($mailAttachment);
      }
      
      $failed = [];
      $res    = self::$mailer->send($message, $failed);
      if (!$failed && (bool)$res) {
        return TRUE;
      }
      
      return $failed;
    }
  }
