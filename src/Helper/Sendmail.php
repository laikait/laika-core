<?php

/**
 * Laika PHP MVC Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MVC Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\Helper;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class Sendmail
{
    /**
     * @var ?PHPMailer $mailer The PHPMailer instance
     */
    protected ?PHPMailer $mailer;

    /**
     * @var array $config Mail configuration settings
     */
    private array $config;

    /**
     * @var int $maxAttachmentSize Max Attachment Size
     */
    protected int $maxAttachmentSize;

    public function __construct(?array $config = null)
    {
        // Set Config
        $this->config = $config ?: \do_hook('config.mail') ?: [];

        // Check Driver is Set in Mail Config File
        if (!isset($this->config['driver'])) {
            throw new Exception("Mail Driver Not Specified in Mail Config File.");
        }
        // Load Mailer
        $this->mailer = new PHPMailer(true);
        // Set X-Mailer Header
        $this->mailer->XMailer = 'LaikaMailer/1.0';
        // Set Max Attachment Size
        $this->maxAttachmentSize = 5 * 1024 * 1024; // 5MB

        // Check Driver is Supported
        $this->loadDriver($this->config['driver']);
        
        // Set Sender Info
        if (isset($this->config['from.email']) && !empty($this->config['from.email'])) {
            if (isset($this->config['from.name']) && !empty($this->config['from.name'])) {
                $this->setFrom($this->config['from.email'], $this->config['from.name']);
            } else {
                $this->setFrom($this->config['from.email']);
            }
        }
    }

    public function isHTML(bool $isHtml = true): self
    {
        $this->mailer->isHTML($isHtml);
        return $this;
    }

    public function setFrom(string $email, ?string $name = null): self
    {
        $this->mailer->setFrom($email, $name);
        return $this;
    }

    public function addTo(string $email, ?string $name = null): self
    {
        $this->mailer->addAddress($email, $name);
        return $this;
    }

    public function addReplyTo(string $email, ?string $name = null): self
    {
        $this->mailer->addReplyTo($email, $name);
        return $this;
    }

    public function addCC(string $email): self
    {
        $this->mailer->addCC($email);
        return $this;
    }

    public function addBCC(string $email): self
    {
        $this->mailer->addBCC($email);
        return $this;
    }

    public function maxAttachmentSize(int $size): self // In MB
    {
        $this->maxAttachmentSize = $size * 1024 * 1024;
        return $this;
    }
    public function attach(string $filePath, ?string $name = null): self
    {
        // Normalize path to avoid issues on Windows vs Linux
        $path = \realpath($filePath);

        // Check File is Valid
        if ($path === false || !\is_file($path)) {
            throw new Exception("Invalid file path: [{$filePath}]", 404);
        }

        // Check File is Readable
        if (!\is_readable($path)) {
            throw new Exception("File is not readable: [{$filePath}]", 403);
        }

        $name = $name ?: '';
        $this->mailer->addAttachment($path, $name);
        return $this;
    }

    public function attachMultiple(array $files): self
    {
        foreach ($files as $file) {
            // Normalize path to avoid issues on Windows vs Linux
            $path = \realpath($file);

            // Check File is Valid
            if ($path === false || !is_file($path)) {
                throw new Exception("Invalid file path: [{$file}]", 404);
            }

            // Check File is Readable
            if (!\is_readable($path)) {
                throw new Exception("File is not readable: [{$file}]", 403);
            }

            $this->mailer->addAttachment($path);
        }
        return $this;
    }

    public function attachFromString(string $content, string $name, ?string $mime = null): self
    {
        if (empty($name)) {
            throw new Exception("Attachment filename cannot be empty.", 422);
        }

        // Try to detect MIME if not provided
        $mime = $mime ?: \mime_content_type($name) ?: 'application/octet-stream';

        if (!$this->mailer->addStringAttachment($content, $name, 'base64', $mime)) {
            throw new Exception("Failed to Attach String Content: [{$name}]", 500);
        }

        return $this;
    }

    public function subject(string $subject): self
    {
        $this->mailer->Subject = $subject;
        return $this;
    }

    public function body(string $body): self
    {
        $this->mailer->Body = $body;
        return $this;
    }

    public function altBody(string $text): self
    {
        $this->mailer->AltBody = $text;
        return $this;
    }

    public function send(): bool
    {
        $status = false;
        try {
            if ($this->mailer->Subject === '') {
                throw new Exception("Email Subject Cannot be Empty.");
            }
            if ($this->mailer->Body === '') {
                throw new Exception("Email Body Cannot be Empty.");
            }
            // Send Mail & Get Status
            $status = $this->mailer->send();
        } catch (Exception $e) {
            \report_bug($e);
        } finally {
            // Clear Mailer Data
            $this->clear();
        }
        return $status;
    }

    public function clearAddresses(): self
    {
        $this->mailer->clearAddresses();
        return $this;
    }

    public function clearAttachments(): self
    {
        $this->mailer->clearAttachments();
        return $this;
    }

    public function clearCCs(): self
    {
        $this->mailer->clearCCs();
        return $this;
    }

    public function clearBCCs(): self
    {
        $this->mailer->clearBCCs();
        return $this;
    }

    public function clearReplyTos(): self
    {
        $this->mailer->clearReplyTos();
        return $this;
    }

    public function clear(): self
    {
        $this->mailer->clearAddresses();
        $this->mailer->clearAttachments();
        $this->mailer->clearCCs();
        $this->mailer->clearBCCs();
        $this->mailer->clearReplyTos();
        return $this;
    }

    public function getMailer(): PHPMailer
    {
        return $this->mailer;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function close()
    {
        if ($this->mailer && $this->mailer->SMTPKeepAlive) {
            $this->mailer->smtpClose();
        }
    }

    ##################################################################
    /*------------------------ INTERNAL API ------------------------*/
    ##################################################################

    private function loadDriver(string $driver): self
    {
        switch (\strtolower($driver)) {
            case 'smtp':
                $this->useSmtp();
                break;
            case 'sendmail':
                $this->useSendmail();
                break;
            case 'qmail':
                $this->useQmail();
                break;
            case 'mail':
                $this->useMail();
                break;            
            default:
                throw new Exception("Unsupported Mail Driver: [{$driver}]");
                break;
        }
        return $this;
    }

    // Use SMTP
    private function useSmtp(): self
    {
        if (!isset($this->config['host']) || empty($this->config['host'])) {
            throw new Exception("SMTP Config Key 'host' is Not Configured.");
        }
        if (!isset($this->config['username']) || empty($this->config['username'])) {
            throw new Exception("SMTP Config Key 'username' is Not Configured.");
        }
        if (!isset($this->config['password']) || empty($this->config['password'])) {
            throw new Exception("SMTP Config Key 'password' is Not Configured.");
        }
        if (!isset($this->config['port']) || empty($this->config['port'])) {
            throw new Exception("SMTP Config Key 'port' is Not Configured.");
        }

        $this->mailer->isSMTP();
        $this->mailer->Host     =   $this->config['host'];
        $this->mailer->SMTPAuth =   $this->config['auth'] ?? true;
        $this->mailer->Username =   $this->config['username'];
        $this->mailer->Password =   $this->config['password'];
        $this->mailer->Port     =   (int) ($this->config['port'] ?? 587);
        $this->mailer->SMTPKeepAlive = true;
        // Check SMTP Secure Type
        $secure = \strtolower($this->config['secure'] ?? '');
        $map = [
            'starttls' => PHPMailer::ENCRYPTION_STARTTLS,
            'tls'      => PHPMailer::ENCRYPTION_STARTTLS,
            'ssl'      => PHPMailer::ENCRYPTION_SMTPS,
            ''         => '',
        ];

        if (!\array_key_exists($secure, $map)) {
            throw new Exception("Invalid SMTP Secure Type: {$secure}");
        }

        $this->mailer->SMTPSecure = $map[$secure];
        // Advanced SMTP Options
        if (isset($this->config['options'])) {
            $this->mailer->SMTPOptions = $this->config['options'];
        }
        // Debug level
        if (isset($this->config['debug']) && $this->config['debug'] === true) {
            $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
        }
        return $this;
    }

    // Use Sendmail
    private function useSendmail(): self
    {
        // Windows Fallback
        if (\stripos(PHP_OS, 'WIN') === 0) {
            return $this->useMail();
        }

        $this->mailer->isSendmail();
        return $this;
    }

    // Use Qmail
    private function useQmail(): self
    {
        // Windows Fallback
        if (\stripos(PHP_OS, 'WIN') === 0) {
            return $this->useMail();
        }

        $this->mailer->isQmail();
        return $this;
    }

    // Use PHP Mail
    private function useMail(): self
    {
        $this->mailer->isMail();
        return $this;
    }

    public function __destruct()
    {
        $this->close();
        $this->mailer = null;
    }
}
