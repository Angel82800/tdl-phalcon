{% if limited %}

<div class="row pt-2 hide-for-small-only">
    <div class="small-12 medium-10 medium-push-1 end columns download_container">
        <h4>Sorry, you've reached your device limit.</h4>
        <p>Please contact site administrator to upgrade your account.</p>
    </div>
</div>

{% else %}

<div class="row pt-2 hide-for-small-only">
    <div class="small-12 medium-10 medium-push-1 end columns download_container">
        <h3>Get Started</h3>

        <p class="pb-4">Install Todyl Defender to protect this device.</p>

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
                        <a href="?os=mac">Need it for Mac?</a>
                    {% elseif os == 'mac' %}
                        <img src="/img/download/apple-icon.png" /><br />
                        <div class="pt-1"><a class="button" href="javascript:void(0);" onclick="javascript:startDownload()">Download for Mac</a></div>
                        <a href="?os=win">Need it for Windows?</a>
                    {% endif %}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{% endif %}

<div class="row pt-2 show-for-small-only">
    <div class="small-10 small-push-1 medium-10 medium-push-1 end columns text-center vertical-center">
        <h3>Mobile Protection Coming Soon</h3>

        <p class="pt-2">Todyl Defender is currently available for desktops and laptops. We will notify you when protection is available for your mobile device.</p>
        <p class="pt-1">Log In on the desktop or laptop you want to protect to get started.</p>

        <p class="pt-1">
            <a href="/send-instructions" class="button">Send Installation Instructions</a>
        </p>
    </div>
</div>
