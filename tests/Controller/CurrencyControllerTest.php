<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\ExchangeRate;
use Doctrine\ORM\EntityManagerInterface;

class CurrencyControllerTest extends WebTestCase
{
    private static $client = null;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::$client = self::$client ?? static::createClient();

        // Access the EntityManager from the client’s container
        $this->entityManager = self::$client->getContainer()->get('doctrine')->getManager();

        // Clear existing data to ensure a clean state for each test
        $this->entityManager->createQuery('DELETE FROM App\Entity\ExchangeRate')->execute();

        // Add EUR-USD and EUR-CZK rates for indirect conversion via EUR
        $eurToUsd = new ExchangeRate();
        $eurToUsd->setBaseCurrency('EUR');
        $eurToUsd->setTargetCurrency('USD');
        $eurToUsd->setRate(1.0772);
        $eurToUsd->setSource('ECB');
        $eurToUsd->setDate(new \DateTime());

        $eurToCzk = new ExchangeRate();
        $eurToCzk->setBaseCurrency('EUR');
        $eurToCzk->setTargetCurrency('CZK');
        $eurToCzk->setRate(25.208);
        $eurToCzk->setSource('ECB');
        $eurToCzk->setDate(new \DateTime());

        $this->entityManager->persist($eurToUsd);
        $this->entityManager->persist($eurToCzk);
        $this->entityManager->flush();
    }

    public function testConvertUsdToCzk()
    {
        $client = self::$client;

        // Send a POST request to the API endpoint with the data for conversion
        $client->request('POST', '/api/convert', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'from' => 'USD',
            'to' => 'CZK',
            'amount' => 100,
        ]));

        // Assert that the HTTP status is 200 OK
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        // Parse the JSON response
        $data = json_decode($client->getResponse()->getContent(), true);

        // Calculate the expected conversion amount
        // Convert 100 USD -> EUR, then EUR -> CZK
        $expectedAmount = (100 / 1.0772) * 25.208;

        // Assert the response contains the expected fields and values
        $this->assertEquals('USD', $data['from']);
        $this->assertEquals('CZK', $data['to']);
        $this->assertEquals(100, $data['original_amount']);
        $this->assertEquals($expectedAmount, $data['converted_amount']);
    }

    protected function tearDown(): void
    {
        // Clean up database after each test to prevent data contamination
        $this->entityManager->createQuery('DELETE FROM App\Entity\ExchangeRate')->execute();
        $this->entityManager->close();
        parent::tearDown();
    }
}
