# Custom Query manager component

Generates templates for Custom Query feature in Keboola Connection platform.

# Usage

Create configuration in `data/config.json` with:
- `parameters`:
    - `backend`: one of supported backend: `synapse`, `snowflake`; required
    - `operation`: one of supported operation: `importFull`, `importIncremental`; required
    - `source`: one of supported operation: `table`, `fileAbs`; required
    - `columns`: list of columns; required
    - `primaryKeys`: list of primary keys; optional
- `action`: one of supported sync action to be run: `generate`; required

for example:
```json
{
  "parameters": {
    "backend": "synapse",
    "operation": "importFull",
    "source": "fileAbs",
    "columns": [
      "column1",
      "column2"
    ],
    "primaryKeys": [
      "column1"
    ]
  },
  "action": "generate"
}
```

Run component:
```shell
docker-compose run --rm dev
```

Will return JSON with query templates.

## Development
 
Clone this repository and init the workspace with following command:

```
git clone https://github.com/keboola/component.app-custom-query-manager
cd component.app-custom-query-manager
docker-compose build
docker-compose run --rm dev composer install --ignore-platform-req=ext-odbc --no-scripts
```

Run the test suite using this command:

```
docker-compose run --rm dev composer tests
```
 
# Integration

For information about deployment and integration with KBC, please refer to the [deployment section of developers documentation](https://developers.keboola.com/extend/component/deployment/) 
