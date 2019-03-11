<div id="homepage-nav" class="hide-for-small-only">
  <div class="top-bar">
    <div class="row" data-equalizer="top-bar">

      <div class="top-bar-left">
        <ul class="menu" data-magellan>
          <li class="menu-logo">
            <a class="top-bar-logo" href="#homepage-intro">
              <img src="/img/home/1x/nav-logo-b-g@1x.png" srcset="/img/home/2x/nav-logo-b-g@2x.png 1000w, /img/home/3x/nav-logo-b-g@3x.png 2000w" alt="Todyl Logo" />
            </a>
          </li>
        </ul>
      </div>

      <div class="top-bar-right" data-equalizer-watch="top-bar">
        <ul class="menu" data-magellan>
          <li class="phone-number">
            Call: <strong>844-311-6900</strong>
          </li>

          <li>
            <a href="#homepage-risk">Am I at Risk?</a>
          </li>
          <li>
            <a href="#homepage-services">Todyl Protection</a>
          </li>
          <li>
            <a href="#homepage-trends">Hacking Trends</a>
          </li>

          {%- if not(logged_in is empty) %}
            <li>{{ link_to('dashboard', 'Your Dashboard') }}</li>
          {% else %}
            <li>{{ link_to('session/login', 'Log In') }}</li>
          {% endif %}

        </ul>
      </div>

    </div>
  </div>
</div>