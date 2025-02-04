<?php
namespace Da\Mailer\Queue\Backend\Sqs;

use Da\Mailer\Exception\InvalidCallException;
use Da\Mailer\Queue\Backend\MailJobInterface;
use Da\Mailer\Queue\Backend\QueueStoreAdapterInterface;

class SqsQueueStoreAdapter implements QueueStoreAdapterInterface
{
    /**
     * @var string the name of the queue to store the messages
     */
    private $queueName;
    /**
     * @var string the URL of the queue to store the messages
     */
    private $queueUrl;
    /**
     * @var SqsQueueStoreConnection
     */
    protected $connection;

    /**
     * PdoQueueStoreAdapter constructor.
     *
     * @param SqsQueueStoreConnection $connection
     * @param string $queueName the name of the queue in the SQS where the mail jobs are stored
     */
    public function __construct(SqsQueueStoreConnection $connection, $queueName = 'mail_queue')
    {
        $this->connection = $connection;
        $this->queueName = $queueName;
        $this->init();
    }

    /**
     * @return SqsQueueStoreAdapter
     */
    public function init()
    {
        $this->getConnection()->connect();

        // create new queue or get existing one
        $queue = $this->getConnection()->getInstance()->createQueue([
            'QueueName' => $this->queueName,
        ]);
        $this->queueUrl = $queue['QueueUrl'];

        return $this;
    }

    /**
     * @return SqsQueueStoreConnection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param MailJobInterface|SqsMailJob $mailJob
     *
     * @return bool whether it has been successfully queued or not
     */
    public function enqueue(MailJobInterface $mailJob)
    {
        $result = $this->getConnection()->getInstance()->sendMessage([
            'QueueUrl' => $this->queueUrl,
            'MessageBody' => $mailJob->getMessage(),
            'DelaySeconds' => $mailJob->getDelaySeconds(),
            'Attempt' => $mailJob->getAttempt(),
        ]);
        $messageId = $result['MessageId'];

        return $messageId !== null && is_string($messageId);
    }

    /**
     * Returns a MailJob fetched from Amazon SQS.
     *
     * @return MailJobInterface|SqsMailJob
     */
    public function dequeue()
    {
        $result = $this->getConnection()->getInstance()->receiveMessage([
            'QueueUrl' => $this->queueUrl,
        ]);

        if (empty($result['Messages'])) {
            return null;
        }

        $result = array_shift($result['Messages']);

        return new SqsMailJob([
            'id' => $result['MessageId'],
            'receiptHandle' => $result['ReceiptHandle'],
            'message' => $result['Body'],
            'attempt' => $result['Attempt'],
        ]);
    }

    /**
     * @param MailJobInterface|SqsMailJob $mailJob
     *
     * @return bool
     */
    public function ack(MailJobInterface $mailJob)
    {
        if ($mailJob->isNewRecord()) {
            throw new InvalidCallException('SqsMailJob cannot be a new object to be acknowledged');
        }

        if ($mailJob->getDeleted()) {
            $this->getConnection()->getInstance()->deleteMessage([
                'QueueUrl' => $this->queueUrl,
                'ReceiptHandle' => $mailJob->getReceiptHandle(),
            ]);

            return true;
        } elseif ($mailJob->getVisibilityTimeout() !== null) {
            $this->getConnection()->getInstance()->changeMessageVisibility([
                'QueueUrl' => $this->queueUrl,
                'ReceiptHandle' => $mailJob->getReceiptHandle(),
                'VisibilityTimeout' => $mailJob->getVisibilityTimeout(),
            ]);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty(): bool
    {
        $response = $this->getConnection()->getInstance()->getQueueAttributes([
            'QueueUrl' => $this->queueUrl,
            'AttributeNames' => ['ApproximateNumberOfMessages'],
        ]);

        return $response['Attributes']['ApproximateNumberOfMessages'] === 0;
    }
}
