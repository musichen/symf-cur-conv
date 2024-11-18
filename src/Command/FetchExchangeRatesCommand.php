<?php
namespace App\Command;

use App\Entity\ExchangeRate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:fetch-exchange-rates',
    description: 'Pulls and stores daily exchange rates from the ECB and CBR',
)]
class FetchExchangeRatesCommand extends Command
{
    private HttpClientInterface $httpClient;
    private EntityManagerInterface $em;

    public function __construct(HttpClientInterface $httpClient, EntityManagerInterface $em)
    {
        parent::__construct();
        $this->httpClient = $httpClient;
        $this->em = $em;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('source', InputArgument::OPTIONAL, 'The source of exchange rates (ECB or CBR)', 'ECB');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = strtoupper($input->getArgument('source'));

        $url = match ($source) {
            'ECB' => $_ENV['ECB_URL'] ?? getenv('ECB_URL'),
            'CBR' => $_ENV['CBR_URL'] ?? getenv('CBR_URL'),
            'NEW' => $_ENV['NEW_URL'] ?? getenv('NEW_URL'),
            default => null
        };

        if (!$url) {
            $output->writeln("<error>Invalid source provided. Use 'ECB' or 'CBR'.</error>");
            return Command::FAILURE;
        }

        // Fetch and parse XML data
        $response = $this->httpClient->request('GET', $url);
        $xml = simplexml_load_string($response->getContent());

        // Determine base currency and date, and clear existing rates for that date
        if ($source === 'ECB') {
            $baseCurrency = 'EUR';
            $date = (string) $xml->Cube->Cube['time'];
            $rates = $xml->Cube->Cube->Cube;
        } else { // CBR
            $baseCurrency = 'RUB';
            $date = (string) $xml['Date'];
            $rates = $xml->Valute;
        }

        $today = new \DateTime($date);
        $today->setTime(0, 0);

        // Clear existing rates for today's date and source
        $this->em->createQuery('DELETE FROM App\Entity\ExchangeRate e WHERE e.date = :today AND e.source = :source')
            ->setParameter('today', $today)
            ->setParameter('source', $source)
            ->execute();

        // Process each currency rate
        foreach ($rates as $rate) {
            $targetCurrency = $source === 'ECB' ? (string) $rate['currency'] : (string) $rate->CharCode;
            $nominal = $source === 'ECB' ? 1 : (int) $rate->Nominal;
            $value = $source === 'ECB' ? (float) $rate['rate'] : (float) str_replace(',', '.', $rate->Value);

            $exchangeRateValue = $value / $nominal;

            $exchangeRate = new ExchangeRate();
            $exchangeRate->setBaseCurrency($baseCurrency);
            $exchangeRate->setTargetCurrency($targetCurrency);
            $exchangeRate->setRate($exchangeRateValue);
            $exchangeRate->setSource($source);
            $exchangeRate->setDate($today);

            $this->em->persist($exchangeRate);
        }

        $this->em->flush();
        $output->writeln('Exchange rates updated successfully.');
        return Command::SUCCESS;
    }
}
