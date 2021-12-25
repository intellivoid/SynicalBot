<?php

/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpUnused */
/** @noinspection PhpIllegalPsrClassPathInspection */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use SpamProtectionBot;

/**
 * Info command
 *
 * Gets executed when user sends /info
 */

class InfoCommand extends SystemCommand {
    /**
     * @var string
     */
    protected $name = 'info';

    /**
     * @var string
     */
    protected $description = 'Gets executed when user sends /info';

    /**
     * @var string
     */
    protected $usage = '/info';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $private_only = false;

    /**
     * The whois command used for finding targets
     *
     * @var WhoisCommand|null
     */
    public $WhoisCommand = null;

    /**
     * Command execute method
     *
     * @return ServerResponse
     * @throws TelegramException
     * @noinspection DuplicatedCode
     */
    public function execute(): ServerResponse
    {
        // Ignore forwarded commands
        if($this->getMessage()->getForwardFrom() !== null || $this->getMessage()->getForwardFromChat())
        {
            return Request::emptyResponse();
        }
        $user = $this->getMessage()->getFrom();

        if ($user == null) {
            return Request::sendMessage([
                "chat_id" => $this->getMessage()->getChat()->getId(),
                "reply_to_message_id" => $this->getMessage()->getMessageId(),
                "parse_mode" => "html",
                "text" => "User not found"
            ]);
        }
        $txt = "<b>User info</b>\n";
        $txt .= "<b>FirstName</b>: " . "<code>" . $user->getFirstName() . "</code>\n";
        $txt .= "<b>LastName</b>: " . "<code>" . $user->getLastName() . "</code>\n";
        // $txt .= "<b>Username</b>:" . "<code>" . $user->getUsername() . "</code>\n";
        $txt .= "<b>UserID</b>: " . "<code>" . $user->getId() . "</code>\n";
        $txt .= "<b>Mention</b>: " . $user->tryMention() . "\n";
        return Request::sendMessage([
            "chat_id" => $this->getMessage()->getChat()->getId(),
            "reply_to_message_id" => $this->getMessage()->getMessageId(),
            "parse_mode" => "html",
            "text" => $txt
        ]);
    }
}