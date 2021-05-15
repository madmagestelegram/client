# Telegram client

Telegram client for php. Based on [telegram bot types](https://github.com/madmagestelegram/Types)

# Install
`composer require madmagestelegram/client`

# Usage

```php
// Basic client instance,
// its possible redefine guzzle client on second argument with own options, if necessary
$client = new \MadmagesTelegram\Client\Client('BOT_TOKEN');

// Chat id.
// Usually received in webhook or (https://core.telegram.org/bots/api#getupdates)
$chatId = 0;

// Simple text message
$client->sendMessage($chatId, 'Hello world');

// Simple text + disable message notification
$client->sendMessage($chatId, 'Here is silent message', null, null, null, true);


// It`s possible to send a file
$file = new \MadmagesTelegram\Types\Type\InputFile('/var/photos/some-photo.jpg');

// Here we can send "photo" as photo
$client->sendPhoto($chatId, $file);
// or document
$sentMessage = $client->sendDocument($chatId, $file);

// $sentMessage is instance of \MadmagesTelegram\Types\Type\Message
// As we send document in few lines upper, the property "document" is filled in returned message,
// so we can print it, accessing by getter
print_r($sentMessage->getDocument());

// it prints something like...
// MadmagesTelegram\Types\Type\Document Object
// (
//     [fileId:protected] => ...
//     [fileUniqueId:protected] => ...
//     [thumb:protected] => ...
//     [fileName:protected] => ...
//     [mimeType:protected] => ...
//     [fileSize:protected] => ...
// )

```

# API
All methods definitions defined [here](https://github.com/madmagestelegram/Types/blob/master/src/TypedClient.php)