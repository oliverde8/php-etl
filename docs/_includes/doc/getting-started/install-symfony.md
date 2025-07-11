1. Start by installing the necessary dependencies
```sh
    composer require oliverde8/php-etl-bundle
```

2. in `/config/` create a directory `etl`

3. Enable bundle:
```php
\Oliverde8\PhpEtlBundle\Oliverde8PhpEtlBundle::class => ['all' => true],
```

4. **Optional** You can enable queue's if you have an interface allowing users to execute etl processes (Easy Admin for example).
```yaml
framework:
  messenger:
    routing:
        "Oliverde8\PhpEtlBundle\Message\EtlExecutionMessage": async
```

5. **Optional:** Enable creation of individual files for each log by editing the monolog.yaml
```yaml
etl:
  type: service
  id: Oliverde8\PhpEtlBundle\Services\ChainExecutionLogger
  level: debug
  channels: ["!event"] 
```