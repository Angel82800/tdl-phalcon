<!-- off-canvas title bar for 'small' screen -->
<div class="title-bar mobile-title" data-responsive-toggle="top-menu" data-hide-for="medium">
    <div class="title-bar-left">
        <button class="menu-icon" type="button" data-open="off-canvas-left"></button>
    </div>
    <div class="title-bar-right">
        <img src="/img/small-white-logo.png" alt="Todyl Logo"/>
    </div>
</div>

<!-- off-canvas left menu -->
<div class="off-canvas position-left mobile-nav" id="off-canvas-left" data-off-canvas>
    <div class="title-bar">
        <div class="title-bar-left">
            <button class="close-button" aria-label="Close menu" type="button" data-close="">
                <span aria-hidden="true">×</span>
            </button>
        </div>
        <div class="title-bar-right">
            <img src="/img/small-white-logo.png" alt="Todyl Logo">
        </div>
    </div>
    <ul class="vertical menu mobile-nav-menu" data-accordion-menu>
        <li>{{ link_to('/', 'What is Todyl') }}</li>
        <li>{{ link_to('/products/shield', 'Products and Services') }}</li>
        {# This is being removed for now until we figure out products
        <li>
            <a href="#">Products and Services</a>
            <ul class="menu vertical nested">
                <div class="row collapse">
                    <div class="small-4 columns">
                        <li>
                            <a href="/products/shield">
                                <img src="/img/shield-blue.png"/>
                                <span>Shield</span>
                            </a>
                        </li>
                    </div>
                    <div class="small-4 columns">
                        <li>
                            <a href="/products/guidance">
                                <img src="/img/guidance-blue.png"/>
                                <span>Guidance</span>
                            </a>
                        </li>
                    </div>
                    <div class="small-4 columns">
                        <li>
                            <a href="/products/support">
                                <img src="/img/support-blue.png"/>
                                <span>Support</span>
                            </a>
                        </li>
                    </div>
                </div>
            </ul>
        </li>
        #}
        <li>{{ link_to('pricing', 'Pricing and Comparison') }}</li>
        <li>{{ link_to('contact', 'Contact Us') }}</li>
    </ul>
    <div class="bottom-bar row collapse">
        <div class="small-6 columns" style="border-right: 1px solid #21628E;">
            {{ link_to('/session/login', 'Login') }}
        </div>
        <div class="small-6 columns">
            {{ link_to('/signup', 'Sign Up') }}
        </div>
    </div>
</div>

