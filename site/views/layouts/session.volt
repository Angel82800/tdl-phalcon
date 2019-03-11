{% if 'index2' == dispatcher.getControllerName() %}

  <div class="row show-for-medium">
    <div class="medium-6 medium-centered columns">
      <img class="todyl-logo-top" src="/img/todyl-logo-dark.png" />
    </div>
  </div>

{% endif %}

{{ partial('partials/sessionNavigation') }}

<div class="container">
	{{ content() }}

  <div class="show-for-small-only mobile-grey" style="height: 2em;"></div>
</div>

{{ partial('partials/footer') }}
