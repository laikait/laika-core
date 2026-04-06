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

use Laika\Core\Relay\Relays\Config;
use Laika\Core\Relay\Relays\File;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Laika\Core\Relay\Relays\Url;
use PHPMailer\PHPMailer\SMTP;
use Throwable;

class Sendmail
{
    /** @var ?PHPMailer $mailer The PHPMailer instance */
    protected ?PHPMailer $mailer;

    /** @var array $config Mail configuration settings */
    private array $config;

    /** @var int $maxAttachmentSize Max Attachment Size */
    protected int $maxAttachmentSize;

    /** @var array $supported_drivers Max Attachment Size */
    protected array $supported_drivers = ['smtp', 'sendmail', 'qmail', 'mail'];

    /*================================================================================*/
    /*================================= EXTERNAL API =================================*/
    /*================================================================================*/
    /**
     * @param ?array $config
     */
    public function init(?array $config): static
    {
        $this->close();
        $this->mailer = null;

        // Set Config
        $this->config = $config ?? Config::get('mail');

        // Check Driver is Set in Mail Config File
        if (!isset($this->config['mail.driver'])) {
            throw new Exception("Mail Driver Not Specified in Mail Config File.");
        }
        // Load Mailer
        $this->mailer = new PHPMailer(true);
        // Set X-Mailer Header
        $this->mailer->XMailer = 'LaikaMailer/1.0';
        // Set Max Attachment Size
        $this->maxAttachmentSize = 5 * 1024 * 1024; // 5MB

        // Check Driver is Supported
        $this->loadDriver($this->config['mail.driver']);

        // Set Charset
        $this->setCharset($this->config['mail.charset'] ?? 'UTF-8');
        
        // Set Sender Info
        $from_email = (empty($this->config['from.email']) && filter_var($this->config['from.email'], FILTER_VALIDATE_EMAIL)) ? Url::host() : $this->config['from.email'];
        $from_name = $this->config['from.name'] ?? 'Laika App';
        $this->addFrom($from_email, $from_name);

        return $this;
    }

    /**
     * Email is HTML
     * @param bool $isHtml Set Email is HTML
     * @return static
     */
    public function isHTML(bool $isHtml = true): static
    {
        $this->mailer->isHTML($isHtml);
        return $this;
    }

    /**
     * Email Character Set
     * @param string $charset
     * @return static
     */
    public function setCharset(string $charset): static
    {
        $this->mailer->CharSet = $charset;
        return $this;
    }

    /**
     * Add From Email
     * @param string $email
     * @param string $name Default is ''
     * @return static
     */
    public function addFrom(string $email, string $name = ''): static
    {
        $this->mailer->setFrom($email, $name);
        return $this;
    }

    /**
     * Add To Email
     * @param string $email
     * @param string $name Default is ''
     * @return static
     */
    public function addTo(string $email, string $name = ''): static
    {
        $this->mailer->addAddress($email, $name);
        return $this;
    }

    /**
     * Add Reply To
     * @param string $email
     * @param ?string $name
     * @return static
     */
    public function addReplyTo(string $email, ?string $name = null): static
    {
        $this->mailer->addReplyTo($email, $name);
        return $this;
    }

    /**
     * Add CC
     * @param string $email
     * @param string $name Default is ''
     * @return static
     */
    public function addCC(string $email, string $name = ''): static
    {
        $this->mailer->addCC($email, $name);
        return $this;
    }

    /**
     * Add BCC
     * @param string $email
     * @param string $name Default is ''
     * @return static
     */
    public function addBCC(string $email, string $name = ''): static
    {
        $this->mailer->addBCC($email, $name);
        return $this;
    }

    /**
     * Max Attachment Size
     * @param int $size in MB
     * @return static
     */
    public function maxAttachmentSize(int $size): static // In MB
    {
        $this->maxAttachmentSize = $size * 1024 * 1024;
        return $this;
    }

    /**
     * Attach File
     * @param string $filePath
     * @param string $name  Default is ''
     * @return static
     */
    public function attach(string $filePath, string $name = ''): static
    {
        // Normalize path to avoid issues on Windows vs Linux
        $path = realpath($filePath);

        // Check File is Valid
        if ($path === false || !is_file($path)) {
            throw new Exception("Invalid file path: [{$filePath}]", 404);
        }

        // Check File is Readable
        if (!is_readable($path)) {
            throw new Exception("File is not readable: [{$filePath}]", 403);
        }

        if (filesize($path) > $this->maxAttachmentSize) {
            throw new Exception("Attachment exceeds max size limit: [{$filePath}]", 413);
        }

        $this->mailer->addAttachment($path, $name ?: File::name($path));
        return $this;
    }

    /**
     * Attach Multiple File
     * @param array $files
     * @return static
     */
    public function attachMultiple(array $files): static
    {
        foreach ($files as $file) {
            // Normalize path to avoid issues on Windows vs Linux
            $path = realpath($file);

            // Check File is Valid
            if ($path === false || !is_file($path)) {
                throw new Exception("Invalid file path: [{$file}]", 404);
            }

            // Check File is Readable
            if (!is_readable($path)) {
                throw new Exception("File is not readable: [{$file}]", 403);
            }

            if (filesize($path) > $this->maxAttachmentSize) {
                throw new Exception("Attachment exceeds max size limit: [{$file}]", 413);
            }

            $this->mailer->addAttachment($path, File::name($path));
        }
        return $this;
    }

    /**
     * Attach From String
     * @param string $content
     * @param string $name
     * @param ?string $mime Default is null
     * @return static
     */
    public function attachFromString(string $content, string $name, ?string $mime = null): static
    {
        if (empty($name)) {
            throw new Exception("Attachment filename cannot be empty.", 422);
        }

        // Try to detect MIME if not provided
        $mime = $mime ?: guess_mime_from_name($name);

        if (strlen($content) > $this->maxAttachmentSize) {
            throw new Exception("Attachment exceeds max size limit!", 413);
        }
        if (!$this->mailer->addStringAttachment($content, $name, 'base64', $mime)) {
            throw new Exception("Failed to Attach String Content: [{$name}]", 500);
        }

        return $this;
    }

    /**
     * Add Subject
     * @param string $subject
     * @return static
     */
    public function addSubject(string $subject): static
    {
        $this->mailer->Subject = $subject;
        return $this;
    }

    /**
     * Add Body
     * @param string $body
     * @return static
     */
    public function body(string $body): static
    {
        $this->mailer->Body = $body;
        return $this;
    }

    /**
     * Add Alt Body
     * @param string $body
     * @return static
     */
    public function altBody(string $text): static
    {
        $this->mailer->AltBody = $text;
        return $this;
    }

    /**
     * Send Email
     * @return void
     */
    public function send(): void
    {
        try {
            if ($this->mailer->Subject === '') {
                throw new Exception("Email Subject Cannot be Empty.");
            }
            if ($this->mailer->Body === '') {
                throw new Exception("Email Body Cannot be Empty.");
            }
            
            $this->mailer->send();
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), (int) $e->getCode(), $e);
        } finally {
            // Clear Mailer Data
            $this->clear();
        }
    }

    /**
     * Clear Addresses
     * @return static
     */
    public function clearAddresses(): static
    {
        $this->mailer->clearAddresses();
        return $this;
    }

    /**
     * Clear Attachments
     * @return static
     */
    public function clearAttachments(): static
    {
        $this->mailer->clearAttachments();
        return $this;
    }

    /**
     * Clear CCs
     * @return static
     */
    public function clearCCs(): static
    {
        $this->mailer->clearCCs();
        return $this;
    }

    /**
     * Clear BCCs
     * @return static
     */
    public function clearBCCs(): static
    {
        $this->mailer->clearBCCs();
        return $this;
    }

    /**
     * Clear Reply Tos
     * @return static
     */
    public function clearReplyTos(): static
    {
        $this->mailer->clearReplyTos();
        return $this;
    }

    /**
     * Clear All
     * @return static
     */
    public function clear(): static
    {
        $this->mailer->clearAddresses();
        $this->mailer->clearAttachments();
        $this->mailer->clearCCs();
        $this->mailer->clearBCCs();
        $this->mailer->clearReplyTos();
        $this->mailer->Subject = '';
        $this->mailer->Body    = '';
        $this->mailer->AltBody = '';
        $this->mailer->isHTML(false);
        return $this;
    }

    /**
     * Get Mailer
     * @return PHPMailer
     */
    public function getMailer(): PHPMailer
    {
        return $this->mailer;
    }

    /**
     * Close Connection
     * @return void
     */
    public function close(): void
    {
        if ($this->mailer && $this->mailer->SMTPKeepAlive) {
            $this->mailer->smtpClose();
        }
    }

    /*================================================================================*/
    /*================================= INTERNAL API =================================*/
    /*================================================================================*/
    /**
     * Load Driver
     * @return static
     */
    private function loadDriver(): static
    {
        switch (strtolower($this->config['mail.driver'])) {
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
                throw new Exception("Unsupported Mail Driver: [{$this->config['mail.driver']}]. Sendmail Only Supports " . join(', ', $this->supported_drivers));
        }
        return $this;
    }

    /**
     * Use SMTP
     * @return static
     */
    private function useSmtp(): static
    {
        if (!isset($this->config['smtp.host']) || empty($this->config['smtp.host'])) {
            throw new Exception("SMTP Config Key 'host' is Not Configured.");
        }
        if (!isset($this->config['smtp.username']) || empty($this->config['smtp.username'])) {
            throw new Exception("SMTP Config Key 'username' is Not Configured.");
        }
        if (!isset($this->config['smtp.password']) || empty($this->config['smtp.password'])) {
            throw new Exception("SMTP Config Key 'password' is Not Configured.");
        }
        if (!isset($this->config['smtp.port']) || empty($this->config['smtp.port'])) {
            throw new Exception("SMTP Config Key 'port' is Not Configured.");
        }

        $this->mailer->isSMTP();
        $this->mailer->Host     =   $this->config['smtp.host'];
        $this->mailer->SMTPAuth =   $this->config['smtp.auth'] ?? true;
        $this->mailer->Username =   $this->config['smtp.username'];
        $this->mailer->Password =   $this->config['smtp.password']; // Decrypt Before Use. Require Edit This Line
        $this->mailer->Port     =   (int) ($this->config['smtp.port'] ?? 587);
        $this->mailer->SMTPKeepAlive = true;
        // Check SMTP Secure Type
        $secure = strtolower($this->config['smtp.secure'] ?? '');
        $map = [
            'starttls' => PHPMailer::ENCRYPTION_STARTTLS,
            'tls'      => PHPMailer::ENCRYPTION_STARTTLS,
            'ssl'      => PHPMailer::ENCRYPTION_SMTPS,
            ''         => '',
        ];

        if (!array_key_exists($secure, $map)) {
            throw new Exception("Invalid SMTP Secure Type: {$secure}");
        }

        $this->mailer->SMTPSecure = $map[$secure];
        // Advanced SMTP Options
        if (isset($this->config['smtp.options'])) {
            $this->mailer->SMTPOptions = $this->config['smtp.options'];
        }
        // Debug level
        if (isset($this->config['mail.debug']) && $this->config['mail.debug'] === true) {
            $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
        }
        return $this;
    }

    /**
     * Use Sendmail
     * @return static
     */
    private function useSendmail(): static
    {
        // Windows Fallback
        if (stripos(PHP_OS, 'WIN') === 0) {
            return $this->useMail();
        }

        $this->mailer->isSendmail();
        return $this;
    }

    /**
     * Use Qmail
     * @return static
     */
    private function useQmail(): static
    {
        // Windows Fallback
        if (stripos(PHP_OS, 'WIN') === 0) {
            return $this->useMail();
        }

        $this->mailer->isQmail();
        return $this;
    }

    /**
     * Use PHP Mail
     * @return static
     */
    private function useMail(): static
    {
        $this->mailer->isMail();
        return $this;
    }

    /**
     * Close Mailer Connection
     */
    public function __destruct()
    {
        $this->close();
        $this->mailer = null;
    }
}
