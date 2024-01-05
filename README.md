Copyright (c) 2023 Greg LAMY <greg@bytespin.net>

This is a public project hosted on GitHub : https://github.com/ByteSpin/MessengerDedupeBundle

This was originally developed as part of an ETL project.

ByteSpin/MessengerDedupeBundle is a Symfony 6.3 bundle that tries to help managing messenger_messages unicity when used with Doctrine transport.

> [!NOTE]
>
> This project is still at alpha state and has not yet been fully tested outside its parent project.
>
> **Feel free to submit bug and/or pull requests!**
>
> You can check the [CHANGELOG](CHANGELOG) to see the latest improvements and fixes.

Just keep in mind that I want to keep it as simple as possible!

Requirements
------------
- php 8.2+
- Symfony 6.3+

Installation
------------

First install the bundle:
```
composer require bytespin/messenger-dedupe-bundle
```

Then updates the database schema:
```
php bin/console doctrine:schema:update --force
```

Manual bundle registration
--------------------------

You will need to manually register the bundle in your application.

To do this, follow these steps:

1. Open the file `config/bundles.php` in your Symfony application.

2. Add the following line to the array returned by this file:

    ```php
        ByteSpin\MessengerDedupeBundle\MessengerDedupeBundle::class => ['all' => true],
    ```

3. Save the file. Your bundle is now registered and ready to be used in your application.

Make sure to perform this step after you have installed the bundle using Composer, but before you use any of its features in your application.

Configuration
-------------

You will have to configure the entity manager to be used with the ByteSpin\MessengerDedupeBundle entities.
This has to be done once after installation.
We provide a script to automatise this step ; please run :
```shell
bin/console bytespin:configure-messenger-dedupe
```

If you prefer to do this by yourself, add the following lines just within your entity manager 'mappings:' key in doctrine.yaml :

```yaml
# src/config/packages/doctrine.yaml
doctrine:
   dbal:
   (...)
   orm:
   (...)
   entity_managers:
      your_entity_manager:
      (...)
      mappings:
         ByteSpin\MessengerDedupeBundle:
         is_bundle: false
         type: attribute
         dir: '%kernel.project_dir%/vendor/bytespin/messenger-dedupe-bundle/src/Entity'
         prefix: ByteSpin\MessengerDedupeBundle\Entity
         alias: MessengerDedupeBundle



```

> [!IMPORTANT]
>
> If your project contains entities mapped to multiple entity managers, be careful to not use the auto_mapping: true in your doctrine configuration.
>
> This would prevent the getManagerForClass() function used in the bundle to get the correct entity manager to work properly!
>
> This could happen if you decide to use the MessengerDedupeBundle with a shared messenger_messages table between multiple symfony projects.
>
> In such case :
> - Choose the correct entity manager when you run the configuration script,
> - Be sure to remove the 'auto_mapping: true' key from your doctrine.yaml (or set it to false),
> - Be sure that ALL your entities are correctly mapped in the 'mappings:' sections of your doctrine.yaml



# Message deduplication
This feature avoids same messages (YOU decide what is same in this case) accumulation in the messenger_messages
table when Doctrine transport is used, with the help of a custom Middleware and Envelope Stamp.

Usage
------------
You must enable the deduplication Middleware in your messenger.yaml config file :
```yaml
# config/packages/messenger.yaml

framework:
    messenger:
        buses:
            messenger.bus.default:
                middleware:
                    - ByteSpin\MessengerDedupeBundle\Middleware\DeduplicationMiddleware
```

> [!NOTE]
>
> The deduplication middleware must be executed before any other custom or standard symfony middleware
>
> Put it first in the middleware list will do the trick


Don't forget to use the following class and initialize your message bus :
```php
use ByteSpin\MessengerDedupeBundle\Messenger\Stamp\HashStamp;
use ByteSpin\MessengerDedupeBundle\Processor\HashProcessor;
```

```php
public function __construct(
        private MessageBusInterface $messageBus,
        private HashProcessor $hashProcessor,
    ) {
    }

```
When you need to dispatch a message, you first have to calculate a hash of what makes this message unique

For example :
```php
$messageHash = $this->hashProcessor->makeHash('TheMessageType'.$TheFirstVariable.$TheSecondVariable);
```

Then you can dispatch your message using the custom stamp :

```php
$this->messageBus->dispatch(
                new Envelope(
                    $message,
                    [
                        new TransportNamesStamp('async'),
                        new HashStamp($messageHash),
                    ]
                )
            );
```

> [!NOTE]
> Any message dispatched without any HashStamp will be ignored by the middleware


That's all!

When a message is dispatched with the HashStamp stamp through the doctrine transport, the deduplication middleware
will first check that a similar hash does not exist :
- If Yes, will return the envelope (message is not dispatched to avoid duplication)
- If No, will save the hash and dispatch the message

An event subscriber is in charge of removing the hash when the message has been processed

Usage between multiple Symfony Applications
-------------------------------------------

In more complex architectures, a Symfony application (let's call it the Initiator App) can be used to generate some messages to
some other remote Symfony applications (let's call them the Remote App).
The DeduplicationHash is always stored on the Initiator App.
The Remote App can consume the message but the DeduplicationHash is still stored on the Initiator App.

With the help of a simple EventSubscriber listening to WorkerMessageHandledEvent and/or WorkerMessageFailedEvent, a specific RemoveDedupeHash
message can be dispatched by the Remote App to the correct Initiator App transport/queue for the DedupeHash to be removed.

In such case :
- all the applications must use the ByteSpin/MessengerDedupeBundle to avoid MessageDecodingFailedException
- all the applications must share a compatible messenger transports/queues configuration
- the bundle provides a new InitiatorStamp to be included in generated messages.
- the bundle also provides a new MessageHandler that listens to remotely generated RemoveDedupeHash messages

For example, the Initiator App generates a message :
```php
(...)
$this->messageBus->dispatch(
                new Envelope(
                    $message,
                    [
                        new TransportNamesStamp('remote_async_transport'),
                        new HashStamp($messageHash),
                        new InitiatorStamp('initiator_async_transport')
                    ]
                )
            );
```

On the Remote App, a simple EventSubscriber is in charge of dispatching the RemoveHash message to the Initiator App :
```php
<?php

use ByteSpin\MessengerDedupeBundle\Messenger\Stamp\HashStamp;
use ByteSpin\MessengerDedupeBundle\Messenger\Stamp\InitiatorStamp;
use ByteSpin\MessengerDedupeBundle\Model\RemoveDedupeHash;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

readonly class MessageHandledOrFailedEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,

    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageHandledEvent::class => 'onMessageProcessed',
            WorkerMessageFailedEvent::class => 'onMessageProcessed',
        ];
    }

    public function onMessageProcessed(WorkerMessageHandledEvent $event): void
    {
        // remove hash on remote message initiator, only if a remote initiator has been defined
        $envelope = $event->getEnvelope();
        $hashStamp = $envelope->last(HashStamp::class);
        $initiatorStamp = $envelope->last(InitiatorStamp::class);
        if ($hashStamp && $initiatorStamp) {
            $transportName = $initiatorStamp->getInitiator();
            $hash = $hashStamp->getHash();
            $this->messageBus->dispatch(
                new Envelope(
                    new RemoveDedupeHash(
                        $hash
                    ),
                    [
                        new TransportNamesStamp($transportName),
                    ]
                )
            );

        }
    }
}
```


Licence
-------

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
