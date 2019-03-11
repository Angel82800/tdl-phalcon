<div class="row expanded navbar">
  <div class="small-6 medium-2 large-1 columns kill-padding">
    <a href="/">
      <div class="logo" href="#homepage-intro">
        &nbsp;
      </div>
    </a>
  </div>
  <div class="small-6 medium-10 large-11 kill-padding text-right columns">
      <ul class="menu right-menu">



        <li class="phone-number show-for-large">Call Us: 844-311-6900</li>

        {% if dispatcher.getControllerName() == 'details' %}
            <li class="show-for-large activeNav"><a href="/details">Under the Hood</a></li>
        {% else %}
            <li class="show-for-large"><a href="/details">Under the Hood</a></li>
        {% endif %}

        {% if dispatcher.getControllerName() == 'about' %}
            <li class="show-for-large activeNav"><a href="/about">About Us</a></li>
        {% else %}
            <li class="show-for-large"><a href="/about">About Us</a></li>
        {% endif %}

        {% if dispatcher.getControllerName() == 'pricing' %}
            <li class="show-for-large activeNav"><a href="/pricing">Pricing</a></li>
        {% else %}
            <li class="show-for-large"><a href="/pricing">Pricing</a></li>
        {% endif %}

        
        <li class="show-for-large"><a href="https://blog.todyl.com" target="_blank">Blog</a></li>
      {%- if not(logged_in is empty) %}
        <li class="hide-for-small-only">{{ link_to('dashboard', 'Your Dashboard') }}</li>
      {% else %}
        <li class="hide-for-small-only">{{ link_to('session/login', 'Log In') }}</li>
        <li  class="hide-for-small-only"><a href="/signup" class="button nav-button" onClick="fbq('track', 'Nav_Signup');">Sign Up</a></li>
      {% endif %}
        <li>
          <a href="#">
            <i class="i-menu text-2em" data-open="offCanvasRight" onClick="gtag('event', 'click', {'event_category':'more-menu-open', 'event_label':'base-interactions'});"></i>
          </a>
        </li>
    </ul>
  </div>
</div>


