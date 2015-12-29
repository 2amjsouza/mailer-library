<?php
namespace Da\Tests\Queue;

use Da\Mailer\Model\MailMessage;
use Da\Mailer\Queue\Backend\Pdo\PdoMailJob;
use Da\Mailer\Queue\Backend\Pdo\PdoQueueStoreAdapter;
use Da\Mailer\Security\Cypher;
use Da\Tests\AbstractMySqlDatabaseTestCase;
use Da\Tests\Fixture\FixtureHelper;
use Da\Mailer\Queue\MailQueue;

class MailQueueTest extends AbstractMySqlDatabaseTestCase
{
    /**
     * @var MailQueue
     */
    private $mailQueuePdo;

    protected function setUp()
    {
        parent::setUp();

        $this->mailQueuePdo = new MailQueue(new PdoQueueStoreAdapter(self::getPdoQueueStoreConnection()));
    }

    public function testPdoEnqueDequeueAndAcknowledge()
    {
        $mailJob = FixtureHelper::getMailJob();

        $this->assertSame(self::getPdoQueueStoreConnection(), $this->mailQueuePdo->init());
        $this->assertTrue($this->mailQueuePdo->enqueue($mailJob));
        $this->assertTrue($this->mailQueuePdo->isEmpty() === false);

        $mailJob = $this->mailQueuePdo->dequeue();

        $this->assertTrue($this->mailQueuePdo->isEmpty() === true);

        $this->assertTrue(!empty($mailJob->getMessage()));

        $dequeuedMailMessage = MailMessage::fromArray(json_decode($mailJob->getMessage(), true));

        $this->assertEquals(FixtureHelper::getMailMessage(), $dequeuedMailMessage);

        $mailJob->markAsCompleted();
        $this->mailQueuePdo->ack($mailJob);

        $this->assertTrue($this->mailQueuePdo->dequeue() === null);
    }

    public function testPdoEnqueDequeueWithCypher()
    {
        $mailMessage = FixtureHelper::getMailMessage();
        $mailJob = new PdoMailJob(['message' => $mailMessage]);
        $cypher = new Cypher('I find your lack of faith disturbing.');

        $this->mailQueuePdo->setCypher($cypher);
        $this->assertSame($cypher, $this->mailQueuePdo->getCypher());

        $this->mailQueuePdo->init();

        $this->assertTrue($this->mailQueuePdo->enqueue($mailJob));
        $this->assertTrue($this->mailQueuePdo->isEmpty() === false);

        $dequeuedMailJob = $this->mailQueuePdo->dequeue();

        $this->assertTrue($dequeuedMailJob->isNewRecord() === false);
        $this->assertEquals($mailMessage, $dequeuedMailJob->getMessage());

    }
}
