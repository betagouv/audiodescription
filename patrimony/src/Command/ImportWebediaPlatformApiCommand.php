<?php

namespace App\Command;

use App\EntityManager\LanguageManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'ad:import:webedia-platform')]
class ImportWebediaPlatformApiCommand extends Command
{
    private string $authEndpoint;
    private string $clientId;
    private string $clientSecret;
    private string $audience;
    private string $rootUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LanguageManager $languageManager,
        private ParameterBagInterface $parameterBag,
    )
    {
        $this->authEndpoint = $this->parameterBag->get('webedia_api_platform.auth_endpoint');
        $this->clientId = $this->parameterBag->get('webedia_api_platform.client_id');
        $this->clientSecret = $this->parameterBag->get('webedia_api_platform.client_secret');
        $this->audience = $this->parameterBag->get('webedia_api_platform.audience');
        $this->rootUrl = $this->parameterBag->get('webedia_api_platform.root_url');
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $body = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'audience' => $this->audience,
            'grant_type' => 'client_credentials',
        ];

        $response = $this->httpClient->request('POST', $this->authEndpoint, [
            'json' => $body,
        ]);

        $accessToken = $response->toArray()['access_token'];

        $rootUrl = $this->rootUrl;
        $endpoint = $rootUrl . "/catalog/movie";

        $response = $this->httpClient->request('GET', $endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ],
        ]);

        $next = $response->toArray()['next'];

        $this->analyseMovies($response->toArray()['data']);

        if (isset($next) && !empty($next)) {
            $endpoint = $rootUrl . "/catalog/movie?after=";
            do {
                dump($next);
                $response = $this->httpClient->request('GET', $endpoint . $next, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Accept' => 'application/json',
                    ],
                ]);

                $next = $response->toArray()['next'];

                $this->analyseMovies($response->toArray()['data']);
            } while (isset($next) && !empty($next));
        }

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            // the command description shown when running "php bin/console list"
            ->setDescription('Import platform from Webedia API.')
            // the command help shown when running the command with the "--help" option
            ->setHelp('This command is used to import platform from Webedia API.')
        ;
    }

    private function analyseMovies($movies) {
        foreach($movies as $movie) {
            $products = $movie['products'];

            foreach($products as $product) {
                $languages = $product['language'];

                foreach($languages as $language) {
                    $this->languageManager->provide($language);
                }
            }
        }
    }
}

