<nav id="top-menu" class="landing_menu" data-responsive-toggle="top-menu" class="hide-for-small-only">
  <div class="row expanded navbar">
    <div class="medium-2 large-1 columns kill-padding">
      <a href="/">
        <div class="logo" href="#homepage-intro">
          &nbsp;
        </div>
      </a>
    </div>

    <div class="medium-10 large-11 kill-padding text-right columns">
      <ul class="menu right-menu">
        <li class="phone-number show-for-xlarge">
         Call Us: 844-311-6900
        </li>
        <li><a href="https://blog.todyl.com" target="_blank">Cybersecurity Blog</a></li>
        {%- if not(logged_in is empty) %}
          <li>{{ link_to('dashboard', 'Your Dashboard') }}</li>
        {% else %}
          <li>{{ link_to('session/login', 'Log In') }}</li>
          <li><a href="/signup" class="button nav-button" onClick="fbq('trackCustom', 'Nav_Signup');">Sign Up</a></li>
        {% endif %}
          <!--<li>
            <a href="#">
              <i class="i-menu text-blue text-2em" data-open="off-canvas-left"></i>
            </a>
          </li>-->
      </ul>
    </div>
</nav>
