<?php

namespace Gulch\MonologTelegram;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Telegram Handler For Monolog
 *
 * This class helps you in logging your application events
 * into Telegram using it's API.
 *
 */
class TelegramHandler extends AbstractProcessingHandler
{
    const API_URL_QUERY = 'https://api.telegram.org/bot{TOKEN}/SendMessage';
    const EMOJI_MAP = [
        Logger::DEBUG => '🚧',
        Logger::INFO => '‍🗨',
        Logger::NOTICE => '✉',
        Logger::WARNING => '⚠',
        Logger::ERROR => '❗',
        Logger::CRITICAL => '🔥',
        Logger::ALERT => '🔔',
        Logger::EMERGENCY => '🚑',
    ];

    private $token;
    private $chat_id;

    public function __construct(string $token, string $chat_id)
    {
        parent::__construct();

        if (!extension_loaded('curl')) {
            throw new \Exception('cURL extension not loaded. This library requires cURL.');
        }

        $this->token = $token;
        $this->chat_id = $chat_id;
    }

    public function write(array $record): void
    {
        $message = $record['message'];
        $date = $record['datetime']->format("d.m.Y  H:i:s");
        $emoji = self::EMOJI_MAP[$record['level']];
        $text = $emoji . $message . PHP_EOL . $date;

        $this->send($text);
    }

    /**
     * Send message to chat_id via Telegram API
     * @param string $text Text Message
     * @return void
     *
     */
    public function send(string $text): void
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => str_replace('{TOKEN}', $this->token, self::API_URL_QUERY),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => http_build_query([
                'text' => $text,
                'chat_id' => $this->chat_id,
            ])
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            echo 'Telegram API not available :' . curl_error($ch);
        }

        $response = json_decode($response, true);

        if ($response['ok'] === false) {
            echo 'Telegram API response : ' . $response['description'];
        }
    }
}