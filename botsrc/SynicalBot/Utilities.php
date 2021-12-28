<?php

namespace SynicalBot;

use Exception;
use Longman\TelegramBot\Entities\Message;
use pop\pop;
use SynicalBot;
use TelegramClientManager\Abstracts\SearchMethods\TelegramClientSearchMethod;
use TelegramClientManager\Exceptions\DatabaseException;
use TelegramClientManager\Exceptions\InvalidSearchMethod;
use TelegramClientManager\Exceptions\TelegramClientNotFoundException;
use TelegramClientManager\Objects\DetectedClients;
use TelegramClientManager\Objects\TelegramClient;
use TelegramClientManager\Utilities\Hashing;

/**
 *
 */
class Utilities
{
    /**
     * Attempts to resolve the targeted user
     *
     * @param Message $message
     * @param DetectedClients $clients
     * @param bool $reply_only
     * @return TelegramClient|null
     * @throws DatabaseException
     * @throws InvalidSearchMethod
     * @throws SynicalBot\Exceptions\CannotFindTargetUserException
     */
    public static function findTarget(Message $message, DetectedClients $clients, bool $reply_only = true): ?TelegramClient
    {
        if (
            strlen(trim($message->getText(true))) == 0 &&
            $message->getReplyToMessage() == null
        ) {
            return null;
        }

        if ($message->getText(true) !== null && strlen($message->getText(true)) > 0) {
            $Username = trim(str_ireplace('@', (string)null, $message->getText(true)));

            if (strlen($Username) > 0) {
                try {
                    return SynicalBot::getTelegramClientManager()->getTelegramClientManager()->getClient(
                        TelegramClientSearchMethod::byUsername, $Username
                    );
                } catch (Exception $e) {
                    unset($e);
                }
            }

            if (stripos(strtoupper(trim($message->getText(true))), 'TEL-') !== false) {
                try {
                    return SynicalBot::getTelegramClientManager()->getTelegramClientManager()->getClient(
                        TelegramClientSearchMethod::byPublicId, trim($message->getText(true))
                    );
                } catch (Exception $e) {
                    unset($e);
                }
            }
        }

        if ($clients->ReplyToUserClient !== null) {
            if ($clients->ReplyToSenderChatClient !== null)
                return $clients->ReplyToSenderChatClient;

            return $clients->ReplyToUserClient;
        }

        if ($clients->MentionUserClients !== null) {
            if (count($clients->MentionUserClients) > 0)
                return $clients->MentionUserClients[array_keys($clients->MentionUserClients)[0]];
        }

        if ($reply_only == false) {
            if ($clients->SenderChatClient !== null)
                return $clients->SenderChatClient;

            if ($clients->UserClient !== null)
                return $clients->UserClient;
        }

        // Final attempt with pop
        if ($message->getText(true) !== null && strlen($message->getText(true)) > 0) {
            // NOTE: Argument parsing is done with pop now.
            $options = pop::parse($message->getText(true));

            $TargetTelegramParameter = array_values($options)[(count($options) - 1)];
            if (is_bool($TargetTelegramParameter)) {
                $TargetTelegramParameter = array_keys($options)[(count($options) - 1)];
            }

            $EstimatedPrivateID = Hashing::telegramClientPublicID((int)$TargetTelegramParameter, (int)$TargetTelegramParameter);

            try {
                return SynicalBot::getTelegramClientManager()->getTelegramClientManager()->getClient(
                    TelegramClientSearchMethod::byPublicId, $EstimatedPrivateID
                );
            } catch (TelegramClientNotFoundException $e) {
                unset($e);
            }

            try {
                return SynicalBot::getTelegramClientManager()->getTelegramClientManager()->getClient(
                    TelegramClientSearchMethod::byPublicId, $TargetTelegramParameter
                );
            } catch (TelegramClientNotFoundException $e) {
                unset($e);
            }

            try {
                return SynicalBot::getTelegramClientManager()->getTelegramClientManager()->getClient(
                    TelegramClientSearchMethod::byUsername, str_ireplace('@', (string)null, $TargetTelegramParameter)
                );
            } catch (TelegramClientNotFoundException $e) {
                unset($e);
            }
        }

        throw new SynicalBot\Exceptions\CannotFindTargetUserException('Cannot resolve user ' . $message->getText(true));
    }

    /**
     * Returns the text other than the mention and command entity itself
     * @param Message $message
     * @return string|null
     */
    public static function parseArg(Message $message): ?string
    {
        $reply = $message->getReplyToMessage();
        $text = $message->getText();
        if ($reply == null) {
            return substr(strstr($text, " "), " ", 2);
        }
        return $text;
    }

    /**
     * Extract time from the message object (can be used for getting time for tban, tmute, yada yada
     * format: "5d"
     * @param Message $message
     * @return int|null (time in seconds)
     */
    public static function parseTime(Message $message): ?int
    {
        $toParse = self::parseArg($message);
        if ($toParse != null) {
            $timeStr = $toParse[0];
            $unitStr = $toParse[1];
            switch ($unitStr) {
                case "h":
                    return intval($timeStr, 10) * 3600;
                case "m":
                    return intval($timeStr, 10) * 60;
                case "s":
                    return intval($timeStr, 10);
            }
        }
        return 0;
    }
}