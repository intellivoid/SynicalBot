<?php

    namespace AdminCommands;

    use Longman\TelegramBot\Commands\UserCommand;
    use Longman\TelegramBot\Entities\ServerResponse;
    use Longman\TelegramBot\Exception\TelegramException;
    use Longman\TelegramBot\Request;
    use Synical\Exceptions\CannotUpdateChatMembersCacheException;
    use Synical\Exceptions\DatabaseException;
    use SynicalBot;
    use TelegramClientManager\Abstracts\TelegramChatType;
    use TelegramClientManager\Exceptions\InvalidSearchMethod;
    use TelegramClientManager\Exceptions\TelegramClientNotFoundException;
    use TelegramClientManager\Objects\DetectedClients;

    class AdminCacheCommand extends UserCommand
    {
        /**
         * @var string
         */
        protected $name = 'AdminCache';

        /**
         * @var string
         */
        protected $description = 'Update the admin cache, to take into account new admins/admin permissions.';

        /**
         * @var string
         */
        protected $usage = '/admincache';

        /**
         * @var string
         */
        protected $version = '1.0.0';

        /**
         * Command execute method
         *
         * @return ServerResponse
         * @throws TelegramException
         * @throws \TelegramClientManager\Exceptions\DatabaseException
         * @throws InvalidSearchMethod
         * @throws TelegramClientNotFoundException
         * @noinspection DuplicatedCode
         */
        public function execute(): ServerResponse
        {
            DetectedClients::findClients(SynicalBot::getTelegramClientManager(), $this->getMessage(), $this->getCallbackQuery());

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

            try
            {
                $ChatMemberCache = SynicalBot::getSynicalEngine()->getChatMemberCacheManager()->getChatMemberCache($this->getMessage()->getChat(), true);

                return Request::sendMessage([
                    'chat_id' => $this->getMessage()->getChat()->getId(),
                    'reply_to_message_id' => $this->getMessage()->getMessageId(),
                    'parse_mode' => 'html',
                    'text' => 'Success! updated ' . count($ChatMemberCache->AdministratorPermissions) . ' user(s)'
                ]);
            }
            catch (CannotUpdateChatMembersCacheException $e)
            {
                return Request::sendMessage([
                    'chat_id' => $this->getMessage()->getChat()->getId(),
                    'reply_to_message_id' => $this->getMessage()->getMessageId(),
                    'parse_mode' => 'html',
                    'text' => 'Failed to update chat member cache, try again later.'
                ]);
            }
            catch (DatabaseException $e)
            {
                $ReferenceID = SynicalBot::getLogHandler()->logException($e, 'Worker');
                /** @noinspection PhpUnhandledExceptionInspection */
                return Request::sendMessage([
                    'chat_id' => $this->getMessage()->getChat()->getId(),
                    'reply_to_message_id' => $this->getMessage()->getMessageId(),
                    'parse_mode' => 'html',
                    'text' =>
                        'Oops! Something went wrong! contact someone in @IntellivoidDiscussions\n\n' .
                        'Error Code: <code>' . $ReferenceID . '</code>\n' .
                        'Object: <code>AdminCache</code>'
                ]);
            }
        }
    }