<?php
declare(strict_types=1);

namespace Tests\Unit\Transactions;

use App\Transactions\TransactionType;
use App\Transactions\TransactionValidator;
use App\Transactions\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TransactionValidatorTest extends TestCase
{
    private TransactionValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new TransactionValidator();
    }

    /** @return array<string,mixed> */
    private static function valid(): array
    {
        // The first row of the supplied history, as JSON would carry it.
        return [
            'reference' => 'TXN00001',
            'amount' => -87.18,
            'timestamp' => '2024-05-16 07:22:18.808433',
            'description' => 'Municipal Tax Payment',
            'type' => 'debit',
            'account_number' => 'NLINGB1944573686',
        ];
    }

    public function testAValidRowBecomesATypedInput(): void
    {
        $input = $this->validator->validate(self::valid());

        self::assertSame('TXN00001', $input->reference);
        self::assertSame('-87.18', $input->amount);
        self::assertSame('2024-05-16 07:22:18.808433', $input->occurredAt->format('Y-m-d H:i:s.u'));
        self::assertSame('Municipal Tax Payment', $input->description);
        self::assertSame(TransactionType::Debit, $input->type);
        self::assertSame('NLINGB1944573686', $input->accountNumber);
    }

    public function testReferenceIsOptional(): void
    {
        $body = self::valid();
        unset($body['reference']);
        self::assertNull($this->validator->validate($body)->reference);

        $body['reference'] = '   ';
        self::assertNull($this->validator->validate($body)->reference);
    }

    public function testAmountAcceptsStringsAndKeepsTwoDecimals(): void
    {
        self::assertSame('473.45', $this->validator->validate(['amount' => '473.45'] + self::valid())->amount);
        self::assertSame('42.60', $this->validator->validate(['amount' => 42.6] + self::valid())->amount);
        self::assertSame('100.00', $this->validator->validate(['amount' => 100] + self::valid())->amount);
    }

    public function testIsoTimestampsAreAccepted(): void
    {
        $input = $this->validator->validate(['timestamp' => '2024-05-16T07:22:18Z'] + self::valid());
        self::assertSame('2024-05-16 07:22:18', $input->occurredAt->format('Y-m-d H:i:s'));
    }

    public function testTypeIsCaseInsensitive(): void
    {
        self::assertSame(TransactionType::Credit, $this->validator->validate(['type' => 'CREDIT'] + self::valid())->type);
    }

    public function testUnknownFieldsAreIgnored(): void
    {
        $input = $this->validator->validate(['category' => 'Groceries', 'id' => 'x'] + self::valid());
        self::assertSame('Municipal Tax Payment', $input->description);
    }

    public function testAllMissingFieldsAreReportedTogether(): void
    {
        try {
            $this->validator->validate([]);
            self::fail('expected a ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(['amount', 'timestamp', 'description', 'type', 'account_number'], array_keys($e->fields));
            self::assertSame('is required', $e->fields['amount']);
        }
    }

    /** @return iterable<string, array{string, mixed, string}> */
    public static function invalidFields(): iterable
    {
        yield 'amount text' => ['amount', 'twelve', 'must be a decimal number such as -87.18'];
        yield 'amount bool' => ['amount', true, 'must be a number'];
        yield 'amount huge' => ['amount', '1000000000000', 'is out of range'];
        yield 'timestamp garbage' => ['timestamp', 'yesterday', 'must look like 2024-05-16 07:22:18 or 2024-05-16T07:22:18Z'];
        yield 'timestamp impossible' => ['timestamp', '2024-13-45 07:22:18', 'is not a valid date-time'];
        yield 'description empty' => ['description', '   ', 'must not be empty'];
        yield 'description long' => ['description', str_repeat('x', 256), 'must be at most 255 characters'];
        yield 'description not string' => ['description', 12, 'must be a string'];
        yield 'type wrong' => ['type', 'transfer', 'must be debit or credit'];
        yield 'account long' => ['account_number', str_repeat('N', 35), 'must be at most 34 characters'];
        yield 'reference long' => ['reference', str_repeat('T', 65), 'must be at most 64 characters'];
    }

    #[DataProvider('invalidFields')]
    public function testEachFieldIsCheckedWithAClearMessage(string $field, mixed $value, string $message): void
    {
        try {
            $this->validator->validate([$field => $value] + self::valid());
            self::fail("expected $field to fail");
        } catch (ValidationException $e) {
            self::assertSame([$field => $message], $e->fields);
        }
    }
}
