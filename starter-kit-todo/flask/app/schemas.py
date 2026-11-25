"""Schémas marshmallow : validation des entrées (load).

Règle : une entrée n'est jamais utilisée sans passer par un schéma. Un schéma décrit EXACTEMENT ce que le client
a le droit d'envoyer ; avec unknown = EXCLUDE, tout autre champ est ignoré (défense contre le mass assignment).
"""

from marshmallow import EXCLUDE, Schema, ValidationError, fields, validate, validates_schema


class LoginSchema(Schema):
    """Exemple fourni de schéma d'entrée."""

    class Meta:
        unknown = EXCLUDE

    username = fields.String(required=True, validate=validate.Length(min=1, max=50))
    password = fields.String(required=True, validate=validate.Length(min=1, max=255))


def _not_blank(value: str) -> None:
    if not value.strip():
        raise ValidationError("Title must not be blank.")


class TodoInputSchema(Schema):
    class Meta:
        unknown = EXCLUDE

    title = fields.String(required=True, validate=[validate.Length(max=100), _not_blank])


class StrictBoolean(fields.Field):
    """Booléen JSON STRICT : seuls true et false sont acceptés.

    fields.Boolean de marshmallow accepterait aussi "true", "yes", 1... : trop permissif pour un contrat d'API.
    """

    default_error_messages = {"invalid": "Not a valid boolean: expected true or false."}

    def _deserialize(self, value, attr, data, **kwargs):
        if not isinstance(value, bool):
            raise self.make_error("invalid")
        return value


class TodoPatchSchema(Schema):
    """PATCH /todos/{id} : modification PARTIELLE. title et done sont facultatifs, mais l'un des deux est exigé.

    Les autres champs (id, created_at...) sont ignorés grâce à unknown = EXCLUDE : ils ne se modifient pas.
    """

    class Meta:
        unknown = EXCLUDE

    title = fields.String(validate=[validate.Length(max=100), _not_blank])  # mêmes règles qu'à la création
    done = StrictBoolean()

    @validates_schema
    def _at_least_one_field(self, data, **kwargs):
        if "title" not in data and "done" not in data:
            raise ValidationError("At least one of 'title' or 'done' is required.", field_name="body")
