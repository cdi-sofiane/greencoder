<?php

namespace App\Services;

use App\Entity\User;
use App\Services\MailServiceInterface;
use DateTime;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;
use Twig\Environment;

class MailerService
{
    const MAIL_RESET = "/mail/mail_reset";
    const MAIL_REGISTER = "/mail/mail_register";
    const MAIL_EXPIRED_FREE_ORDER = "/mail/mail_free_order_expiration";
    const MAIL_ALERT_DELETE_VIDEO_NOT_STORED = "/mail/mail_alert_unstored_video_to_delete";
    const MAIL_INVITE_COLLABORATOR_IN_ACCOUNT = "/mail/mail_invite_collaborator_to_account";
    const MAIL_INVITE_EXISTING_COLLABORATOR_IN_ACCOUNT = "/mail/mail_invite_existing_collaborator_to_account";
    const MAIL_ENCODE_ERROR = "/mail/mail_encode_error";
    const SUPPORT_MAIL = "support@vidmizer.com";
    public $mailer;
    public $JWTTokenManager;
    public $twig;

    public function __construct(
        MailerInterface $mailer,
        JWTTokenManagerInterface $JWTTokenManager,
        Environment $twig
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->JWTTokenManager = $JWTTokenManager;
    }

    /**
     * @param User $user
     * @throws TransportExceptionInterface
     */
    public function sendMail(User $user, $view, $data = null)
    {

        try {
            $subject = $data === null ? 'Connection' : $data['subject'];
            $mail = (new TemplatedEmail())
                ->from(new Address('no-replay@greenencoder.com', 'GreenEncoder'))
                ->to($user->getEmail())
                ->subject($subject)
                ->htmlTemplate($view . '.html.twig')
                ->context([
                    'user' => $user,
                    'jwtToken' => $this->JWTTokenManager->create($user),
                    'data' => $data
                ]);
            $this->mailer->send($mail);
        } catch (TransportExceptionInterface $e) {
            return (new JsonResponseMessage)->setCode(Response::HTTP_REQUEST_TIMEOUT)->setError("cannot connect to server smtp");
        }
    }


    public function mailInviteToAccount($user, $data)
    {
        $view = self::MAIL_INVITE_COLLABORATOR_IN_ACCOUNT;
        $payload = [
            'iat' => (new DateTime('now'))->getTimestamp(),
            'exp' => (new DateTime('now'))->modify('+ 1 month')->getTimestamp()
        ];
        $customToken['jwtToken'] = $this->JWTTokenManager->createFromPayload($user, $payload);
        $data = array_merge($data, $customToken);
        $data['subject'] = "Invitation à rejoindre le compte " . $data['toAccount']->getName() . " sur GreenEncoder";

        $this->send($user, $view, $data);
    }
    public function mailInviteExistingUserToAccount($user, $data)
    {
        $view = self::MAIL_INVITE_EXISTING_COLLABORATOR_IN_ACCOUNT;
        $payload = [
            'iat' => (new DateTime('now'))->getTimestamp(),
            'exp' => (new DateTime('now'))->modify('+ 1 month')->getTimestamp()
        ];
        $customToken['jwtToken'] = $this->JWTTokenManager->createFromPayload($user, $payload);
        $data = array_merge($data, $customToken);
        $data['subject'] = "Invitation à rejoindre le compte " . $data['toAccount']->getName() . " sur GreenEncoder";

        $this->send($user, $view, $data);
    }
    private function send(User $user, $view, $data = null)
    {
        try {
            $subject = $data['subject'];
            $mail = (new TemplatedEmail())
                ->from(new Address('no-replay@greenencoder.com', 'GreenEncoder'))
                ->to($user->getEmail())
                ->subject($subject)
                ->htmlTemplate($view . '.html.twig')
                ->context([
                    'user' => $user,
                    'data' => $data
                ]);
            $this->mailer->send($mail);
        } catch (TransportExceptionInterface $e) {
            return (new JsonResponseMessage)->setCode(Response::HTTP_REQUEST_TIMEOUT)->setError("cannot connect to server smtp");
        }
    }


    /**
     * @param User $user
     * @throws TransportExceptionInterface
     */
    public function sendErrorMail($mail, $view, $data = null)
    {

        try {
            $subject = $data === null ? 'Connection' : $data['subject'];
            $mail = (new TemplatedEmail())
                ->from(new Address('no-replay@greenencoder.com', 'GreenEncoder'))
                ->to($mail)
                ->subject($subject)
                ->htmlTemplate($view . '.html.twig')
                ->context([
                    'data' => $data
                ]);

            $this->mailer->send($mail);
        } catch (TransportExceptionInterface $e) {
            return (new JsonResponseMessage)->setCode(Response::HTTP_REQUEST_TIMEOUT)->setError('cannot connect to server smtp');
        }
    }

    /**
     * @param User $user
     * @throws TransportExceptionInterface
     */
    public function debugMailTwig($user, $view, $data = null)
    {
        $value = [
            'user' => $user,
            'data' => $data
        ];
        echo $this->twig->render(MailerService::MAIL_INVITE_EXISTING_COLLABORATOR_IN_ACCOUNT . '.html.twig',  $value);
    }
}
