<div class="panels_container dashboard-container ajax-panels">
    <div class="row expanded" data-equalizer="first-row" data-equalize-on="medium">
        <div class="small-12 large-8 columns" data-equalizer-watch="first-row">
            <div class="panel">
                <div id="blocks_header"><h4>Data Protected by Device</h4></div>

                <div class="data_protected_container">
                    <div class="spinner"></div>
                    <canvas id="chart_blocks" height="250px"></canvas>
                    <div id="no_chart_data"><h2><i class="i-time text-4em mb-2 text-light-grey"></i></h2><br>Your data will be displayed here after a few minutes of online activity.</div>
                </div>
            </div>
        </div>

        <div class="large-4 small-12 columns" data-equalizer-watch="first-row">
            <div class="panel">
                <h4>Internet Threat Level</h4>

                <div class="threat_level">
                    <img id="level_image" />
                    <div id="level_text"></div>
                </div>

                <div id="threat_description"></div>

                {% if is_admin %}
                <div class="panel_action_container">
                    <a id="btn_edit_threat_level" class="button">
                        <i class="i-edit icon-xl"></i>
                    </a>
                </div>
                {% endif %}
            </div>
        </div>
    </div>

    <div class="row expanded" data-equalizer="second-row" data-equalize-on="large">
        <div class="large-8 small-12 columns" data-equalizer-watch="second-row">
            <div class="panel">
                <div class="clearfix">
                    <h4 class="float-left" style="max-width: 90%">Your Protected Devices</h4>
                    <div class="row float-right">
                        <a href="/user-device" class="show_all hide-for-small-only">
                            <span id="see_all_devices">See all </span>&nbsp;<i class="i-menu text-1 icon-small-offset"></i>
                        </a>
                        <a href="/user-device" class="show_all show-for-small-only text-mid-grey"><i class="icon-right"></i></a>
                    </div>
                </div>

                <div class="devices_container spinner"></div>
            </div>
        </div>

        <div class="large-4 small-12 columns" data-equalizer-watch="second-row">
            <div class="panel">
                <div id="indicators_header">&nbsp;</div>

                <canvas id="chart_indicators" height="180px"></canvas>

                <div id="latest_threat" class="sub_header"></div>
            </div>
        </div>
    </div>

    <div id="stats_container" class="row expanded" data-equalizer="third-row">
        <div class="small-12 large-4 columns" data-equalizer-watch="third-row">
            <div class="panel">
                <h2 class="stat-value"></h2>
                <p class="text-mid-grey"></p>
            </div>
        </div>

        <div class="small-12 large-4 columns" data-equalizer-watch="third-row">
            <div class="panel">
                <h2 class="stat-value"></h2>
                <p class="text-mid-grey"></p>
            </div>
        </div>

        <div class="small-12 large-4 columns" data-equalizer-watch="third-row">
            <div class="panel">
                <h2 class="stat-value"></h2>
                <p class="text-mid-grey"></p>
            </div>
        </div>
    </div>
</div>

<div class="reveal large_reveal" id="dlg_manage_threat_level" data-reveal>
    <div class="small-12 columns">
        <h4>Change Internet Threat Level</h4>
        <button class="close-button" data-close aria-label="Close modal" type="button">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <form id="frm_edit_threat_level" type="post" enctype="multipart/form-data" data-abide novalidate>
        <div class="row expanded threat_level_container">
        {% for threat_level in threat_levels %}
            <div class="small-3 columns">
                <label class="threat_level">
                    <input type="radio" name="threat_level" value="{{ threat_level.pk_id }}" {{ threat_level.is_active ? 'checked' : '' }} />
                    <div>{{ threat_level.title }}</div>
                </label>
            </div>
        {% endfor %}
        </div>

        <div class="row expanded">
            <div class="small-12 columns">
                <textarea name="threat_level_description"></textarea>
            </div>
        </div>
    </form>

    <div class="row">
        <div class="small-12 medium-3 medium-offset-6 columns">
            <a class="button hollow deactive btn-wide" data-close>Cancel</a>
        </div>

        <div class="small-12 medium-3 columns end">
            <a class="button btn-wide" href="javascript:void(0);" id="btn_confirm_topic">Update</a>
        </div>
    </div>
</div>

<script type="text/javascript">
var chart_blocks, chart_indicators;

// preload images
var laptop_green = new Image(),
desktop_grey = new Image();
laptop_green.src = '/img/dashboard/1x/icon-laptop-green@1x.png';
desktop_grey.src = '/img/dashboard/1x/icon-desktop-grey@1x.png';

document.addEventListener("DOMContentLoaded", function() {
    // line shadow
    // let draw = Chart.controllers.line.prototype.draw;
    // Chart.controllers.line.prototype.draw = function() {
    //     draw.apply(this, arguments);
    //     let ctx = this.chart.chart.ctx;
    //     let _stroke = ctx.stroke;
    //     ctx.stroke = function() {
    //         ctx.save();
    //         ctx.shadowColor = '#999';
    //         ctx.shadowBlur = 1;
    //         ctx.shadowOffsetX = 0;
    //         ctx.shadowOffsetY = 1;
    //         _stroke.apply(this, arguments);
    //         ctx.restore();
    //     }
    // };
});

// threat level descriptions
var threat_level_descriptions = {
};

{% for threat_level in threat_levels %}
threat_level_descriptions['{{ threat_level.pk_id }}'] = '{{ threat_level.description }}';
{% endfor %}

</script>
