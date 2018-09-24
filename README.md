# Micro Service Reactor

## Usage

```php
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->input  = $input;
        $this->output = $output;
        
        $this->microServiceReactor = new MicroServiceReactor('127.0.0.1', 9001, 'passport', 1000);

        $this->microServiceReactor->setController([$this, 'generate']);
        $this->microServiceReactor->setLogger([$this, 'log']);

        $this->microServiceReactor->process();
    }
```

## License

The Soft Deletable Bundle is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
