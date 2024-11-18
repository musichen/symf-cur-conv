<?php
namespace App\Tests\Command;

use App\Command\FetchExchangeRatesCommand;
use App\Entity\ExchangeRate;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class FetchExchangeRatesCommandTest extends TestCase
{
    public function testFetchesAndSavesExchangeRates()
    {
        // Realistic ECB XML response with multiple currencies
        $xmlContent = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<gesmes:Envelope xmlns:gesmes=\"http://www.gesmes.org/xml/2002-08-01\" xmlns=\"http://www.ecb.int/vocabulary/2002-08-01/eurofxref\">
    <gesmes:subject>Reference rates</gesmes:subject>
    <gesmes:Sender>
        <gesmes:name>European Central Bank</gesmes:name>
    </gesmes:Sender>
    <Cube>
        <Cube time='2024-11-08'>
            <Cube currency='USD' rate='1.0772'/>
            <Cube currency='CZK' rate='25.208'/>
        </Cube>
    </Cube>
</gesmes:Envelope>";

        // Create a MockResponse with the XML content
        $mockResponse = new MockResponse($xmlContent);
        $mockHttpClient = new MockHttpClient($mockResponse);

        // Mock EntityManager with expected behavior
        $mockEntityManager = $this->createMock(EntityManagerInterface::class);

        // Expect persist to be called twice for USD and CZK
        $mockEntityManager->expects($this->exactly(2))
            ->method('persist')
            ->with($this->isInstanceOf(ExchangeRate::class));

        $mockEntityManager->expects($this->once())
            ->method('flush');

        $command = new FetchExchangeRatesCommand($mockHttpClient, $mockEntityManager, 'ECB');

        // Set up CommandTester to run the command
        $application = new Application();
        $application->add($command);
        $commandTester = new CommandTester($application->find('app:fetch-exchange-rates'));

        // Execute the command
        $commandTester->execute([]);

        // Verify output
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Exchange rates updated successfully.', $output);
        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
