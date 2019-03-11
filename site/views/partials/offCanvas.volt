<div class="row expanded jumbo-nav off-canvas position-right" id="offCanvasRight" data-off-canvas data-transition="overlap">

  <div class="navbar">
    <div class="row expanded">
      <div class="small-6 columns kill-padding">
        <a href="/" style="border:none;outline:none;">
          <div class="logo-white" href="#homepage-intro">
            &nbsp;
          </div>
        </a>
      </div>
      <div class="small-6 text-right columns kill-padding">
        <ul class="menu right-menu">
          <li>  
            <a href="#">
              <i class="i-close-solo text-2em" data-close="offCanvasRight"></i>
            </a>
          </li>
        </ul>
      </div>
    </div>

    <div class="row expanded">
      <div class="small-12 text-right">
        <div class="jumbo-menu">
          <ul class="menu vertical jumbo-content">

            <li><h2><a href="/">Home</a></h2></li>
            <li><h2><a href="/details">Under the Hood</a></h2></li>
            <li><h2><a href="/about">About Us</a></h2></li>
            <li><h2><a href="/pricing">Pricing</a></h2></li>
            <li><h2><a href="/landing">Industry Risks</a></h2></li>
            <!-- <li><h2><a href="/partners">Partners</a></h2></li>-->
            <li><h2><a href="https://blog.todyl.com" target="_blank" data-close="offCanvasRight">Blog</a></h2></li>

            <li>
              <div class="row expanded text-right jumbo-sub-content">
              <div class="small-12 columns kill-padding ">
                  <a href="https://www.facebook.com/todylprotection/" target="_blank"><i class="i-facebook"></i></a>
                  <a href="https://twitter.com/todylprotection" target="_blank"><i class="i-twitter"></i></a>
                  <a href="https://www.linkedin.com/company/11264170/" target="_blank"><i class="i-linkedin"></i></a>
                  <br class="show-for-small-only"><br class="show-for-small-only">
                  <a href="https://calendly.com/todyl/specialist-call" target="_blank" alt="Speak to a Specialist" onclick="gtag_report_phoneLead();">
                    <i class="i-phone"></i>
                    <span class="oxygen"> 844-311-6900</span>
                  </a>  
                  
                  <br class="show-for-small-only"><br class="show-for-small-only">
                  {%- if not(logged_in is empty) %}
                    {{ link_to('dashboard', 'Your Dashboard') }}&nbsp;&nbsp;&nbsp;&nbsp;
                    <a href="/session/logout">Log Out</a>
                  {% else %}
                    {{ link_to('session/login', 'Log In') }}&nbsp;&nbsp;&nbsp;&nbsp;
                    <a href="/signup" onClick="fbq('trackCustom', 'MobileNav_Signup');">Sign Up</a>
                  {% endif %}
              </div>
            </li>

        </div>
      </div>
    </div>

  </div><!--navbar-->
</div><!--jumbo-nav-->



