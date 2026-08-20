<?php

use EvolutionCMS\Core;
use EvolutionCMS\Mail;
use EvolutionCMS\Models\EventLog;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

if (!defined('EVO_SANITIZE_SEED')) {
    define('EVO_SANITIZE_SEED', 'event-log-mail-test-seed');
}

it('recognizes the mail-sent event type', function () {
    $event = new EventLog(['type' => EventLog::TYPE_MAIL_SENT]);

    expect(EventLog::TYPE_MAIL_SENT)->toBe(4)
        ->and($event->isMailSentType())->toBeTrue()
        ->and($event->isInformationType())->toBeFalse()
        ->and($event->isWarningType())->toBeFalse()
        ->and($event->isErrorType())->toBeFalse();
});

it('records only safe metadata for an accepted mail message', function () {
    $core = new class extends Core {
        public array $recordedEvents = [];

        public function __construct()
        {
        }

        public function logEvent($evtid, $type, $msg, $source = 'Parser')
        {
            $this->recordedEvents[] = compact('evtid', 'type', 'msg', 'source');
        }
    };

    $mail = new class extends Mail {
        public function attachCore(Core $core): void
        {
            $this->modx = $core;
        }

        public function recordSuccessfulSend(): void
        {
            $this->logSuccessfulSend();
        }
    };
    $mail->attachCore($core);
    $mail->isSMTP();
    $mail->Host = 'private.smtp.example';
    $mail->Username = 'private-user';
    $mail->Password = 'private-password';
    $mail->Subject = 'Private <subject>';
    $mail->Body = 'Private body';
    $mail->addAddress('private-recipient@example.test');
    $mail->addCC('private-copy@example.test');
    $mail->addBCC('private-blind-copy@example.test');
    $mail->addCustomHeader('X-Private-Token', 'private-header');

    $mail->recordSuccessfulSend();

    expect($core->recordedEvents)->toHaveCount(1);

    $event = $core->recordedEvents[0];

    expect($event)
        ->toMatchArray([
            'evtid' => 0,
            'type' => EventLog::TYPE_MAIL_SENT,
            'source' => 'Private <subject>',
        ])
        ->and($event['msg'])->toStartWith(
            'Mail accepted for delivery.<br>'
            . 'Method: SMTP<br>'
            . 'Subject: Private &lt;subject&gt;<br>'
            . 'To: private-recipient@example.test<br>'
            . 'CC: private-copy@example.test<br>'
            . 'BCC: private-blind-copy@example.test'
        )
        ->and($event['msg'])
        ->toContain('EvolutionCMS mail body:')
        ->not->toContain($mail->Host)
        ->not->toContain($mail->Username)
        ->not->toContain($mail->Password)
        ->not->toContain('private-header');
});

it('restores an encoded mail body only for successful-mail events', function () {
    $event = new EventLog();
    $event->setRawAttributes([
        'type' => EventLog::TYPE_MAIL_SENT,
        'description' => EventLog::appendMailBody('Mail accepted for delivery.', '<h1>Accepted body</h1>'),
    ], true);
    $legacyEvent = new EventLog();
    $legacyEvent->setRawAttributes([
        'type' => EventLog::TYPE_MAIL_SENT,
        'description' => 'Mail accepted for delivery.',
    ], true);

    expect($event->mailBody())->toBe('<h1>Accepted body</h1>')
        ->and($legacyEvent->mailBody())->toBeNull();
});

it('uses a safe bounded subject in the list with a legacy-compatible fallback', function () {
    $mailEvent = new EventLog();
    $mailEvent->setRawAttributes([
        'type' => EventLog::TYPE_MAIL_SENT,
        'list_source' => 'Subject <unsafe>',
    ]);
    $oldMailEvent = new EventLog();
    $oldMailEvent->setRawAttributes([
        'type' => EventLog::TYPE_MAIL_SENT,
        'list_source' => 'Mailer',
    ]);
    $ordinaryEvent = new EventLog();
    $ordinaryEvent->setRawAttributes([
        'type' => EventLog::TYPE_INFORMATION,
        'list_source' => '<legacy-source>',
    ]);

    expect(EventLog::mailSentListSource('  Subject <unsafe>  '))
        ->toBe('Subject <unsafe>')
        ->and(EventLog::mailSentListSource(" \r\n\t "))
        ->toBe(EventLog::MAIL_SENT_SOURCE_FALLBACK)
        ->and(EventLog::mailSentListSource(str_repeat('S', 100)))
        ->toHaveLength(EventLog::SOURCE_DISPLAY_LIMIT)
        ->toEndWith('...')
        ->and($mailEvent->list_source)
        ->toBe('Subject &lt;unsafe&gt;')
        ->and($oldMailEvent->list_source)
        ->toBe('Mailer')
        ->and($ordinaryEvent->list_source)
        ->toBe('<legacy-source>');
});

it('bounds subjects and recipient groups in mail-sent events', function () {
    $core = new class extends Core {
        public array $recordedEvents = [];

        public function __construct()
        {
        }

        public function logEvent($evtid, $type, $msg, $source = 'Parser')
        {
            $this->recordedEvents[] = compact('evtid', 'type', 'msg', 'source');
        }
    };

    $mail = new class extends Mail {
        public function attachCore(Core $core): void
        {
            $this->modx = $core;
        }

        public function recordSuccessfulSend(): void
        {
            $this->logSuccessfulSend();
        }
    };
    $mail->attachCore($core);
    $mail->isSMTP();
    $mail->Subject = str_repeat('S', 300);

    foreach (range(1, 11) as $index) {
        $mail->addAddress("recipient-{$index}@example.test");
    }

    $mail->recordSuccessfulSend();

    $message = $core->recordedEvents[0]['msg'];

    expect($message)
        ->toContain('Subject: ' . str_repeat('S', 252) . '...')
        ->toContain('recipient-10@example.test')
        ->toContain('... (+1 more)')
        ->not->toContain('recipient-11@example.test')
        ->not->toContain('<br>CC:')
        ->not->toContain('<br>BCC:')
        ->not->toContain('(none)');
});

it('does not record the simulated PHP mail success used in debug mode', function () {
    $core = new class extends Core {
        public array $recordedEvents = [];

        public function __construct()
        {
            $this->debug = true;
        }

        public function logEvent($evtid, $type, $msg, $source = 'Parser')
        {
            $this->recordedEvents[] = compact('evtid', 'type', 'msg', 'source');
        }
    };

    $mail = new class extends Mail {
        public function attachCore(Core $core): void
        {
            $this->modx = $core;
        }

        public function recordSuccessfulSend(): void
        {
            $this->logSuccessfulSend();
        }
    };
    $mail->attachCore($core);
    $mail->isMail();
    $mail->addAddress('private-recipient@example.test');

    $mail->recordSuccessfulSend();

    expect($core->recordedEvents)->toBeEmpty();
});

it('logs only after the underlying mail send reports success', function () {
    $makeCore = static function (bool $result, bool $throw = false): Core {
        $mail = new class($result, $throw) extends Mail {
            public function __construct(
                private readonly bool $result,
                private readonly bool $throw
            ) {
                parent::__construct($throw);
            }

            public function attachCore(Core $core): void
            {
                $this->modx = $core;
            }

            public function postSend(): bool
            {
                if ($this->throw) {
                    throw new PHPMailerException('transport failed');
                }

                return $this->result;
            }
        };

        $core = new class($mail) extends Core {
            public array $recordedEvents = [];

            public function __construct(private readonly Mail $testMail)
            {
            }

            public function getMail(): Mail
            {
                return $this->testMail;
            }

            public function logEvent($evtid, $type, $msg, $source = 'Parser')
            {
                $this->recordedEvents[] = compact('evtid', 'type', 'msg', 'source');
            }

            public function setConfig($name, $value)
            {
            }
        };
        $mail->attachCore($core);

        return $core;
    };

    $params = [
        'to' => 'private-recipient@example.test',
        'from' => 'private-sender@example.test',
        'fromname' => 'Private sender',
        'subject' => 'Private subject',
        'body' => 'Private body',
        'type' => 'text',
    ];

    $successfulCore = $makeCore(true);
    $failedCore = $makeCore(false);
    $exceptionCore = $makeCore(false, true);

    expect($successfulCore->sendmail($params))->toBeTrue()
        ->and($successfulCore->recordedEvents)->toHaveCount(1)
        ->and($failedCore->sendmail($params))->toBeFalse()
        ->and($failedCore->recordedEvents)->toBeEmpty();

    expect(fn () => $exceptionCore->sendmail($params))
        ->toThrow(PHPMailerException::class, 'transport failed');
    expect(array_column($exceptionCore->recordedEvents, 'type'))
        ->not->toContain(EventLog::TYPE_MAIL_SENT);
    $successfulMessage = $successfulCore->recordedEvents[0]['msg'];
    expect($successfulMessage)
        ->toContain('Subject: Private subject')
        ->toContain('To: private-recipient@example.test')
        ->not->toContain('private-sender@example.test')
        ->not->toContain('Private sender')
        ->not->toContain('Private body');
});

it('does not treat mail-sent events as error-email triggers', function () {
    $core = file_get_contents(dirname(__DIR__, 3) . '/src/Core.php');

    expect($core)
        ->toContain('if ($type <= EventLog::TYPE_ERROR');
});

it('renders the mail-sent state in the event list and details', function () {
    $root = dirname(__DIR__, 4);
    $list = file_get_contents($root . '/manager/views/page/eventlog.blade.php');
    $details = file_get_contents($root . '/manager/views/page/eventlog_details.blade.php');
    $languageFiles = glob($root . '/core/lang/*/global.php');

    expect($list)
        ->toContain('EventLog::TYPE_MAIL_SENT')
        ->toContain('$_style[\'icon_mail\']')
        ->toContain("'event_log.source as list_source'")
        ->toContain('[+list_source+]');

    expect($details)
        ->toContain('@case(EvolutionCMS\Models\EventLog::TYPE_MAIL_SENT)')
        ->toContain("ManagerTheme::getLexicon('eventlog_mail_sent')")
        ->toContain('sandbox=""')
        ->toContain('referrerpolicy="no-referrer"')
        ->toContain('srcdoc="{{ $log->mailBody() }}"');

    expect($languageFiles)->not->toBeEmpty();
    foreach ($languageFiles as $languageFile) {
        expect(file_get_contents($languageFile))
            ->toContain("\$_lang['eventlog_mail_sent']");
    }
});
