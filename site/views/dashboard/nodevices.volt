<div class="row pt-2 hide-for-small-only">
    <div class="small-12 medium-10 medium-push-1 end columns download_container">
        {% if is_beta %}
        <h1>Thank you for signing up for Todyl Beta.</h1>
        {% else %}
        <h1>Get Started</h1>
        {% endif %}

        <h4 class="text-light-grey pb-4">Install Todyl Defender to protect this device.</h4>

        <div class="row">

            <div class="small-12 medium-6 text-center columns pin_section">
                <div class="inner_section">
                    <h5>Step 1. Click to Copy Your Setup PIN</h5>

                    <div class="pin_info pt-1">
                        <p>
                            <a href="javascript:void(0);" id="copy_pin">
                                Click to Copy<span id="copy-confirm" class="copiedtext">Copied</span>
                            </a><span id="pin_no"><?php echo $pin; ?></span>
                        </p>
                        <!--<img src="/img/download/placeholder.png" />-->
                    </div>

                    <p class="pt-2">You'll be asked for this after installation.</p>
                </div>
            </div>

            <div class="small-12 medium-6 end text-center columns download_section">
                <div class="inner_section">
                    <h5>Step 2. Download and Install Todyl Defender</h5>

                    <div class="pt-1">
                    {% if os == 'win' %}
                        <img src="/img/download/windows-icon.png" /><br />
                        <div class="pt-1"><a class="button" href="javascript:void(0);" onclick="javascript:startDownload()">Download for Windows</a></div>
                        <a href="?os=mac" class="text-light-grey">Need it for Mac?</a>
                    {% elseif os == 'mac' %}
                        <img src="/img/download/apple-icon.png" /><br />
                        <div class="pt-1"><a class="button" href="javascript:void(0);" onclick="javascript:startDownload()">Download for Mac</a></div>
                        <a href="?os=win" class="text-light-grey">Need it for Windows?</a>
                    {% endif %}
                    </div>
                </div>
            </div>
        </div>

        <p class="text-mid-grey pt-2">Having Trouble? Check out the <a href="/support">Frequently Asked Questions</a>, our watch our 

            {% if os == 'win' %}
            <a href="https://youtu.be/qAhGycWM6is?t=1m1s" target="blank"> Quick Install Video</a>.
                
            {% elseif os == 'mac' %}
                <a href="https://youtu.be/smMgHH4dGUM?t=1m1s" target="blank"> Quick Install Video</a>.
            {% endif %}

    </p>
    </div>
</div>

<div class="row show-for-small-only full-height-container">
    <div class="small-10 small-push-1 medium-10 medium-push-1 end columns text-center vertical-center">
        <h3>Mobile Protection Coming Soon</h3>

        <p class="pt-2">Todyl Defender is currently available for desktops and laptops. We will notify you when protection is available for your mobile device.</p>
        <p class="pt-1">Log In on the desktop or laptop you want to protect to get started.</p>

        <p class="pt-1">
            <a href="/send-instructions" class="button">Send Installation Instructions</a>
        </p>
    </div>
</div>

