# Custom Query manager component

Generates templates for Custom Query feature in Keboola Connection platform.

# Usage

Create configuration in `data/config.json` with:
- `parameters`:
    - `backendType`: one of supported backend (e.g. `snowflake`); required
    - `operation`: one of supported operation (e.g. `tableCreate`); required
- `action`: one of supported sync action to be run - `generate`; required

for example:
```json
{
  "parameters": {
    "backendType": "snowflake",
    "operation": "tableCreate"
  },
  "action": "generate"
}
```

Run component:
```shell
docker-compose run --rm dev
```

Will return JSON with query template.

## Development
 
Clone this repository and init the workspace with following command:

```
git clone https://github.com/keboola/my-component
cd my-component
docker-compose build
docker-compose run --rm dev composer install --no-scripts
```

Run the test suite using this command:

```
docker-compose run --rm dev composer tests
```
 
# Integration

For information about deployment and integration with KBC, please refer to the [deployment section of developers documentation](https://developers.keboola.com/extend/component/deployment/) 
