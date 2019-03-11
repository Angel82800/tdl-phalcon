<div id="homepage-intro" data-magellan-target="homepage-intro">
    <div class="row hide-for-small-only" id="intro-nav">
        <div class="top-bar-left">
            <ul class="menu" data-magellan>
              <li class="menu-logo">
                &nbsp;
              </li>
            </ul>
        </div>

        <div class="top-bar-right" data-equalizer-watch="top-bar">
            <ul class="menu" data-magellan>
              <li class="phone-numberv">
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

    <div class="row homepage-intro-info vertical-center">
        <div class="small-10 small-centered columns">
            <div class="hide-for-small-only">
                <img src="/img/home/1x/todyl-logo@1x.png" srcset="/img/home/2x/todyl-logo@2x.png 1000w, /img/home/3x/todyl-logo@3x.png 2000w" alt="Todyl Logo" />
            </div>

            <div class="large-caption">
                We're making powerful cybersecurity simple, <span class="hide-for-small-only"><br /></span>affordable, and accessible to everyone.
            </div>

            <div class="sub-caption">
                As large companies spend more on cybersecurity, hackers are targeting small <span class="hide-for-small-only"><br /></span>businesses and home offices, and many don’t have access to the protection they need.
            </div>

        </div>
    </div>
</div>

