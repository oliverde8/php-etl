#### Creating an ETL chain

Each chain is declared in a single file. The name of the chain is the name of the file created in `/config/etl/`.

Example:
```yaml
chain:
  "Dummy Step":
    operation: rule-engine-transformer
    options:
      add: true
      columns:
        test:
          rules:
            - get : {field: [0, 'uid']}
```

#### Executing a chain

```sh
./bin/console etl:execute demo '[["test1"],["test2"]]' '{"opt1": "val1"}'
```

The first argument is the input, depending on your chain it can be empty.
The second are parameters that will be available in the context of each link in the chain.

#### Get a definition

```sh
./bin/console etl:get-definition demo
```

#### Get definition graph

```sh
./bin/console etl:definition:graph demo
```

This will return a mermaid graph. Adding a `-u` will return the url to the mermaid graph image.