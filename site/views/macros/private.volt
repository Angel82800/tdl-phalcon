{# macro for loading partial per user role #}

{#
 # this macro will load partial based on user role
 #  and also implements cascading check
 # searches down to the lower level roles if a specified partial is not found
 #  until reaching /partials
 #}

{%- macro load_partial(user_role, template_name) %}
  {# get current user role #}
  {# set user_role = getUserRole() #}

  {# get ACL user roles for cascading check #}
  {% set roles = getRoles() %}

  {# flag used for cascading check #}
  {% set started = 0 %}

  {# partial template url to load - defaults to /partials/partial_name.volt #}
  {% set template_url = router.getControllerName() ~ '/partials/' ~ template_name %}

  {% for role in roles %}
    {% if role == user_role %}
      {% set started = 1 %}
    {% endif %}

    {% if started %}
      {% if template_exists(router.getControllerName() ~ '/' ~ role ~ '/' ~ template_name) %}
        {% set template_url = router.getControllerName() ~ '/' ~ role ~ '/' ~ template_name %}
        {% break %}
      {% endif %}
    {% endif %}
  {% endfor %}

  {% if template_exists(template_url) %}
    {{ partial(template_url) }}
  {% else %}
    {# no partial has been found #}
  {% endif %}
{%- endmacro %}
