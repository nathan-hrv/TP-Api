"""Documentation EXACTE (partie 2.3) : le contrat ne ment pas sur la sécurité.

Pour chaque opération de docs/openapi.yaml :
  - si elle est déclarée protégée (bearerAuth), l'appeler sans jeton doit renvoyer 401, et le contrat doit le dire ;
  - si elle est déclarée publique (security: []), l'appeler sans jeton ne doit PAS renvoyer 401 « Unauthenticated ».
"""

import yaml


def _operations(client):
    spec = yaml.safe_load(client.get("/api/openapi.yaml").data)
    for path, operations in spec["paths"].items():
        for method, operation in operations.items():
            if method != "parameters":
                yield path.replace("{id}", "1"), method, operation


def test_security_declared_in_the_contract_matches_the_code(client):
    for url, method, operation in _operations(client):
        protected = bool(operation.get("security"))
        response = getattr(client, method)(url, json={})

        if protected:
            assert response.status_code == 401, f"{method.upper()} {url} devrait exiger un jeton"
            assert "401" in operation["responses"], f"{method.upper()} {url} : 401 non documentée"
        else:
            assert response.get_json() != {"message": "Unauthenticated."}, f"{method.upper()} {url} est publique"


def test_every_response_has_a_description(client):
    spec = yaml.safe_load(client.get("/api/openapi.yaml").data)
    named = spec["components"]["responses"]
    for _, method, operation in _operations(client):
        assert operation.get("summary"), f"{method} sans résumé"
        for status, response in operation["responses"].items():
            if "$ref" in response:
                response = named[response["$ref"].split("/")[-1]]
            assert response.get("description"), f"{method} {status} sans description"
