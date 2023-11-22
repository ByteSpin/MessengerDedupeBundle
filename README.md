Copyright (c) 2023 Greg LAMY <greg@bytespin.net>
This is a public project hosted on GitHub : https://github.com/ByteSpin/MessengerDedupeBundle

This was originally developed as part of an ETL project.

ByteSpin/MessengerDedupeBundle is a Symfony 6.3 bundle that tries to help managing messenger_messages unicity when used with Doctrine transport.

> [!NOTE]
>
> This project is still at alpha state and has not yet been fully tested outside its parent project.
>
> **Feel free to submit bug and/or pull requests!**

Just keep in mind that I want to keep it as simple as possible!

Requirements
------------
- php 8.2+
- Symfony 6.3+

Installation
------------

composer require bytespin/messenger-dedupe-bundle

# Message deduplication
This feature avoids same messages (YOU decide what is same in this case) accumulation in the messenger_messages 
table when Doctrine transport is used, with the help of a custom Middleware and Envelope Stamp.

Usage
------------
You must enable the deduplication Middleware in your messenger.yaml config file :
```
//config/packages/messenger.yaml

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
```
use ByteSpin\MessengerDedupeBundle\Messenger\Stamp\HashStamp;
use ByteSpin\MessengerDedupeBundle\Processor\HashProcessor;
```

```
public function __construct(
        private MessageBusInterface $messageBus,
        private HashProcessor $hashProcessor,
    ) {
    }

```
When you need to dispatch a message, you first have to calculate a hash of what makes this message unique

For example :
```             
$messageHash = $this->hashProcessor->makeHash('TheMessageType'.$TheFirstVariable.$TheSecondVariable);
```

Then you can dispatch your message using the custom stamp :

```
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


Licence
-------

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
