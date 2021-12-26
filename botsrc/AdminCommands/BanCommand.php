<?php

    namespace AdminCommands;

    use Longman\TelegramBot\Commands\UserCommand;
    use Longman\TelegramBot\Entities\ServerResponse;
    use Longman\TelegramBot\Exception\TelegramException;
    use Longman\TelegramBot\Request;
    use SynicalBot;
    use TelegramClientManager\Abstracts\TelegramChatType;
    use TelegramClientManager\Exceptions\DatabaseException;
    use TelegramClientManager\Exceptions\InvalidSearchMethod;
    use TelegramClientManager\Exceptions\TelegramClientNotFoundException;
    use TelegramClientManager\Objects\DetectedClients;
    use TelegramClientManager\Utilities\Helper;

    class BanCommand extends UserCommand
    {
        /**
         * @var string
         */
        protected $name = 'Ban';

        /**
         * @var string
         */
        protected $description = 'Bans the targeted user from the chat';

        /**
         * @var string
         */
        protected $usage = '/ban';

        /**
         * @var string
         */
        protected $version = '1.0.0';

        /**
         * Command execute method
         *
         * @return ServerResponse
         * @throws TelegramException
         * @throws DatabaseException
         * @throws InvalidSearchMethod
         * @throws TelegramClientNotFoundException
         * @noinspection DuplicatedCode
         */
        public function execute(): ServerResponse
        {
            $DetectedClients = DetectedClients::findClients(SynicalBot::getTelegramClientManager(), $this->getMessage(), $this->getCallbackQuery());

            // Ignore forwarded commands
            if($this->getMessage()->getForwardFrom() !== null || $this->getMessage()->getForwardFromChat())
            {
                return Request::emptyResponse();
            }

            // Prevent the use of this command in private chats
            if($this->getMessage()->getChat()->isPrivateChat())
            {
                return Request::sendMessage([
                    'chat_id' => $this->getMessage()->getChat()->getId(),
                    'reply_to_message_id' => $this->getMessage()->getMessageId(),
                    'parse_mode' => 'html',
                    'text' => 'This command is made to be used in group chats, not in private messages!'
                ]);
            }

            // Ignore the request if it's not a group or supergroup (eg; channels or new features that wasn't added)
            if(
                $this->getMessage()->getChat()->getType() !== TelegramChatType::Group &&
                $this->getMessage()->getChat()->getType() !== TelegramChatType::SuperGroup
            )
            {
                return Request::emptyResponse();
            }

            $ChatMemberCache = AdminCacheCommand::getChatMemberCache($this->getMessage());
            if($ChatMemberCache == null)
            {
                return Request::emptyResponse();
            }

            $CallerPermissions = $ChatMemberCache->getAdministratorUser($this->getMessage()->getFrom()->getId());
            if($CallerPermissions == null || $CallerPermissions->CanRestrictMembers == false)
            {
                return Request::sendMessage([
                    'chat_id' => $this->getMessage()->getChat()->getId(),
                    'reply_to_message_id' => $this->getMessage()->getMessageId(),
                    'parse_mode' => 'html',
                    'text' => 'You do not have the required permissions to ban users from this chat'
                ]);
            }

            try
            {
                $TargetUser = SynicalBot\Utilities::findTarget($this->getMessage(), $DetectedClients);
            }
            catch(SynicalBot\Exceptions\CannotFindTargetUserException $e)
            {
                return Request::sendMessage([
                    'chat_id' => $this->getMessage()->getChat()->getId(),
                    'reply_to_message_id' => $this->getMessage()->getMessageId(),
                    'parse_mode' => 'html',
                    'text' => 'Cannot resolve user. If you reply to one of their messages, I\'ll be able to interact with them.'
                ]);
            }

            if($TargetUser == null)
            {
                return Request::sendMessage([
                    'chat_id' => $this->getMessage()->getChat()->getId(),
                    'reply_to_message_id' => $this->getMessage()->getMessageId(),
                    'parse_mode' => 'html',
                    'text' => 'You need to specify a user to ban'
                ]);
            }

            $TargetPermissions = $ChatMemberCache->getAdministratorUser($TargetUser->User->ID);
            if($TargetPermissions !== null)
            {
                if($TargetPermissions->IsOwner)
                {
                    return Request::sendMessage([
                        'chat_id' => $this->getMessage()->getChat()->getId(),
                        'reply_to_message_id' => $this->getMessage()->getMessageId(),
                        'parse_mode' => 'html',
                        'text' => 'You cannot ban the owner of the group'
                    ]);
                }

                if($TargetPermissions->IsAdmin)
                {
                    return Request::sendMessage([
                        'chat_id' => $this->getMessage()->getChat()->getId(),
                        'reply_to_message_id' => $this->getMessage()->getMessageId(),
                        'parse_mode' => 'html',
                        'text' => 'You cannot ban a chat administrator without demoting first.'
                    ]);
                }
            }

            $BanResults = Request::banChatMember([
                'chat_id' => $this->getMessage()->getChat()->getId(),
                'user_id' => $TargetUser->User->ID
            ]);

            if($BanResults->isOk())
            {
                return Request::sendMessage([
                    'chat_id' => $this->getMessage()->getChat()->getId(),
                    'reply_to_message_id' => $this->getMessage()->getMessageId(),
                    'parse_mode' => 'html',
                    'text' => 'Banned ' . Helper::generateMention($TargetUser)
                ]);
            }

            return Request::sendMessage([
                'chat_id' => $this->getMessage()->getChat()->getId(),
                'reply_to_message_id' => $this->getMessage()->getMessageId(),
                'parse_mode' => 'html',
                'text' => $BanResults->getDescription() . ' (' . $BanResults->getErrorCode() . ')'
            ]);
        }
    }